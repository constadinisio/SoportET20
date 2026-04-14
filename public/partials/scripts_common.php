<?php
declare(strict_types=1);

// Common JS injected by authenticated pages.
// Requires $currentUser (array) from auth_guard.php.
?>
<script>
    window.CURRENT_USER = <?= json_encode($currentUser, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        })[c]);
    }

    async function logout() {
        try {
            await fetch('../backend/auth.php', { method: 'DELETE', credentials: 'include' });
        } catch (_) { /* ignore network errors on logout */ }
        sessionStorage.removeItem('user');
        window.location.href = 'index.php';
    }

    // ============================================================
    // Campanita de notificaciones (auto-init si existe #notifBellBtn)
    // ============================================================
    (function initNotifBell() {
        const btn = document.getElementById('notifBellBtn');
        if (!btn) return;

        const badge  = document.getElementById('notifBellBadge');
        const panel  = document.getElementById('notifBellPanel');
        const list   = document.getElementById('notifBellList');
        const markAll = document.getElementById('notifBellMarkAll');
        const POLL_MS = 30000;

        function hacerCuanto(iso) {
            const diff = (Date.now() - new Date(iso.replace(' ', 'T')).getTime()) / 1000;
            if (diff < 60)      return 'hace ' + Math.max(1, Math.floor(diff)) + ' s';
            if (diff < 3600)    return 'hace ' + Math.floor(diff / 60) + ' min';
            if (diff < 86400)   return 'hace ' + Math.floor(diff / 3600) + ' h';
            return 'hace ' + Math.floor(diff / 86400) + ' d';
        }

        const TIPO_COLOR = {
            'TICKET_CREADO':   'bg-amber-500',
            'TICKET_CRITICO':  'bg-rose-500',
            'TICKET_RESUELTO': 'bg-emerald-500',
            'SISTEMA':         'bg-slate-500',
        };

        async function refrescarCount() {
            try {
                const res = await fetch('../backend/notificaciones.php?count=1', { credentials: 'include' });
                if (!res.ok) return;
                const data = await res.json();
                actualizarBadge(data.count || 0);
            } catch (_) { /* silencio: polling resiliente */ }
        }

        function actualizarBadge(n) {
            if (n > 0) {
                badge.textContent = n > 99 ? '99+' : String(n);
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        async function cargarLista() {
            list.innerHTML = '<div class="p-8 text-center text-slate-500 text-sm">Cargando…</div>';
            try {
                const res = await fetch('../backend/notificaciones.php', { credentials: 'include' });
                if (!res.ok) throw new Error('fetch');
                const data = await res.json();
                actualizarBadge(data.no_leidas || 0);
                renderLista(data.notificaciones || []);
            } catch (_) {
                list.innerHTML = '<div class="p-8 text-center text-rose-500 text-sm">Error al cargar.</div>';
            }
        }

        function renderLista(items) {
            if (items.length === 0) {
                list.innerHTML = '<div class="p-8 text-center text-slate-500 text-sm">Sin notificaciones.</div>';
                return;
            }
            list.innerHTML = items.map(n => {
                const color = TIPO_COLOR[n.tipo] || 'bg-slate-500';
                const tituloCls = n.leida == 1 ? 'text-slate-400' : 'text-white font-bold';
                const bgCls = n.leida == 1 ? '' : 'bg-blue-500/5';
                return `<div data-notif-id="${Number(n.id)}" data-entidad="${escapeHtml(n.entidad_tipo || '')}" data-entidad-id="${escapeHtml(n.entidad_id || '')}"
                             class="notif-row px-5 py-3 cursor-pointer hover:bg-slate-800/50 transition-colors ${bgCls}">
                    <div class="flex items-start gap-3">
                        <span class="${color} w-2 h-2 rounded-full mt-2 flex-shrink-0"></span>
                        <div class="flex-1 min-w-0">
                            <p class="${tituloCls} text-sm">${escapeHtml(n.titulo)}</p>
                            <p class="text-slate-400 text-xs mt-1 line-clamp-2">${escapeHtml(n.mensaje)}</p>
                            <p class="text-slate-600 text-[10px] mt-1 font-mono">${hacerCuanto(n.creada_en)}</p>
                        </div>
                    </div>
                </div>`;
            }).join('');

            list.querySelectorAll('.notif-row').forEach(row => {
                row.addEventListener('click', () => onClickNotif(row));
            });
        }

        async function onClickNotif(row) {
            const id = Number(row.dataset.notifId);
            const entidad = row.dataset.entidad;
            const entidadId = row.dataset.entidadId;

            try {
                await fetch('../backend/notificaciones.php', {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ id }),
                });
            } catch (_) { /* ignore */ }

            panel.classList.add('hidden');

            if (entidad === 'ticket' && entidadId) {
                const targetHome = 'home.php#tickets';
                if (!window.location.pathname.endsWith('home.php')) {
                    window.location.href = targetHome;
                } else {
                    window.location.hash = 'tickets';
                    if (typeof showTab === 'function') showTab('tickets');
                }
            }
            refrescarCount();
        }

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const abierto = !panel.classList.contains('hidden');
            if (abierto) {
                panel.classList.add('hidden');
            } else {
                panel.classList.remove('hidden');
                cargarLista();
            }
        });

        document.addEventListener('click', (e) => {
            if (!panel.classList.contains('hidden') && !e.target.closest('#notifBellRoot')) {
                panel.classList.add('hidden');
            }
        });

        markAll.addEventListener('click', async (e) => {
            e.stopPropagation();
            try {
                await fetch('../backend/notificaciones.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ accion: 'todas' }),
                });
                await cargarLista();
            } catch (_) { /* ignore */ }
        });

        refrescarCount();
        setInterval(refrescarCount, POLL_MS);
    })();
</script>
