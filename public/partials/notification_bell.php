<?php
declare(strict_types=1);

// Campanita de notificaciones in-app. Incluir dentro del <header> de cada página protegida.
// El JS vive en scripts_common.php y se inicializa si encuentra #notifBellBtn en el DOM.
?>
<div class="relative" id="notifBellRoot">
    <button id="notifBellBtn" type="button" class="p-2.5 text-slate-400 hover:bg-slate-800 rounded-xl transition-colors relative" title="Notificaciones">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        <span id="notifBellBadge" class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-[#0b111a]">0</span>
    </button>

    <div id="notifBellPanel" class="hidden absolute right-0 top-12 w-96 bg-[#0f172a] border border-slate-800 rounded-2xl shadow-2xl overflow-hidden z-50">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-800">
            <h3 class="text-white font-bold text-sm">Notificaciones</h3>
            <button id="notifBellMarkAll" class="text-[10px] uppercase tracking-wider text-blue-500 hover:text-blue-400 font-bold">Marcar todas leídas</button>
        </div>
        <div id="notifBellList" class="max-h-[480px] overflow-y-auto divide-y divide-slate-800">
            <div class="p-8 text-center text-slate-500 text-sm">Cargando…</div>
        </div>
    </div>
</div>
