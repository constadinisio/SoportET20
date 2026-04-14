<?php
declare(strict_types=1);

// Required vars: $currentUser (array), $activePage (string).
// $activePage ∈ {dashboard, tickets, status, aulas, pcs, usuarios, auditoria}.
// Pages of the same group (home: dashboard/tickets/status; admin: aulas/pcs/usuarios/auditoria)
// render as in-page tab buttons. Cross-group items render as <a> links to the other page.

$homePages  = ['dashboard', 'tickets', 'status'];
$adminPages = ['aulas', 'pcs', 'usuarios', 'auditoria'];
$onHome  = in_array($activePage, $homePages, true);
$onAdmin = in_array($activePage, $adminPages, true);
$isAdmin = ($currentUser['rol'] ?? '') === 'ADMIN';

function sidebarItem(string $id, string $label, string $icon, bool $sameGroup, string $activePage, string $href): string
{
    $base     = 'w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all';
    $active   = 'text-blue-500 bg-blue-600/10 font-bold';
    $inactive = 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200';
    $cls      = ($id === $activePage) ? $active : $inactive;
    $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    if ($sameGroup) {
        return '<button onclick="showTab(\'' . $id . '\')" id="btn-' . $id . '" class="tab-btn ' . $base . ' ' . $cls . '">'
            . $icon
            . '<span class="text-sm">' . $labelEsc . '</span>'
            . '</button>';
    }

    return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="' . $base . ' ' . $inactive . '">'
        . $icon
        . '<span class="text-sm">' . $labelEsc . '</span>'
        . '</a>';
}

$iconDashboard = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>';
$iconTickets   = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9V5.2a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2V9"/><path d="M2 15v3.8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V15"/><path d="M2 9c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2"/><path d="M2 15c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2"/></svg>';
$iconPc        = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
$iconAula      = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>';
$iconUsers     = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$iconAudit     = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>';
$iconLogo      = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
$iconUser      = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
$iconLogout    = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';
?>
<aside class="w-72 bg-[#0b111a] border-r border-slate-800 flex flex-col h-screen sticky top-0">
    <div class="p-8">
        <div class="flex items-center gap-3 mb-10">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white">
                <?= $iconLogo ?>
            </div>
            <div>
                <h2 class="text-white font-bold text-lg leading-none">SoportET20</h2>
                <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold mt-1 block">ET20 SYSTEM</span>
            </div>
        </div>

        <nav class="space-y-2">
            <?php
            // --- Home group: Dashboard / Tickets / Estado de PC ---
            $homeItems = [
                ['dashboard', 'Dashboard',      $iconDashboard],
                ['tickets',   'Tickets',        $iconTickets],
                ['status',    'Estado de PC',   $iconPc],
            ];
            foreach ($homeItems as [$id, $label, $icon]) {
                $href = 'home.php#' . $id;
                echo sidebarItem($id, $label, $icon, $onHome, $activePage, $href);
            }
            ?>

            <?php if ($isAdmin): ?>
                <div class="border-t border-slate-800 mt-4 pt-4 space-y-2">
                    <div class="text-[10px] uppercase tracking-widest font-bold text-slate-600 px-4 mb-2">Administración</div>
                    <?php
                    $adminItems = [
                        ['aulas',     'Aulas',        $iconAula],
                        ['pcs',       'Computadoras', $iconPc],
                        ['usuarios',  'Usuarios',     $iconUsers],
                        ['auditoria', 'Auditoría',    $iconAudit],
                    ];
                    foreach ($adminItems as [$id, $label, $icon]) {
                        $href = 'admin.php#' . $id;
                        echo sidebarItem($id, $label, $icon, $onAdmin, $activePage, $href);
                    }
                    ?>
                </div>
            <?php endif; ?>
        </nav>
    </div>

    <div class="mt-auto p-6 border-t border-slate-800">
        <div class="bg-[#0f172a] rounded-2xl p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-400">
                    <?= $iconUser ?>
                </div>
                <div>
                    <p class="text-white text-xs font-bold"><?= htmlspecialchars($currentUser['nombre'] ?: $currentUser['usuario'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-slate-500 text-[10px] uppercase font-bold"><?= htmlspecialchars($currentUser['rol'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <button onclick="logout()" class="text-slate-500 hover:text-rose-500 transition-colors" title="Cerrar sesión">
                <?= $iconLogout ?>
            </button>
        </div>
    </div>
</aside>
