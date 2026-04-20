<?php
declare(strict_types=1);

// public/qr_print.php - Generación e impresión de QRs por PC.
// Cada QR lleva a home.php?pc=<id>, que abre el formulario de ticket con la PC preseleccionada.
// Acceso: ADMIN y TECNICO.

require __DIR__ . '/partials/auth_guard.php';

if (!in_array($currentUser['rol'], ['ADMIN', 'TECNICO'], true)) {
    header('Location: home.php');
    exit;
}

require __DIR__ . '/../backend/db.php';

// --- Filtro opcional por aula ---
$filtroAula = isset($_GET['id_aula']) ? trim((string)$_GET['id_aula']) : '';

// Listado de aulas activas (para el selector)
$aulas = $pdo
    ->query('SELECT id, nombre FROM aulas WHERE activa = 1 ORDER BY id')
    ->fetchAll();

// PCs (filtradas por aula si corresponde)
if ($filtroAula !== '') {
    $stmt = $pdo->prepare(
        'SELECT c.id, c.id_aula, c.nombre, a.nombre AS aula_nombre
         FROM computadoras c
         JOIN aulas a ON a.id = c.id_aula
         WHERE c.id_aula = ?
         ORDER BY c.id'
    );
    $stmt->execute([$filtroAula]);
} else {
    $stmt = $pdo->query(
        'SELECT c.id, c.id_aula, c.nombre, a.nombre AS aula_nombre
         FROM computadoras c
         JOIN aulas a ON a.id = c.id_aula
         ORDER BY c.id_aula, c.id'
    );
}
$pcs = $stmt->fetchAll();

// --- Base URL (absoluta) para los QRs ---
// Ejemplo: http://et20.local/public/home.php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/public/qr_print.php')), '/');
$ticketBaseUrl = $scheme . '://' . $host . $base . '/home.php';

$pageTitle  = 'SoportET20 ET20 - Imprimir QR';
$activePage = 'qr';
require __DIR__ . '/partials/head.php';
?>
<body class="bg-[#0b111a] text-slate-200 min-h-screen flex print:bg-white print:text-black">

    <div class="print:hidden">
        <?php require __DIR__ . '/partials/sidebar.php'; ?>
    </div>

    <div class="flex-1 flex flex-col">
        <header class="h-20 border-b border-slate-800 flex items-center justify-between px-8 bg-[#0b111a]/50 backdrop-blur-md sticky top-0 z-10 print:hidden">
            <div>
                <h1 class="text-white text-xl font-bold tracking-tight">Imprimir QRs por PC</h1>
                <p class="text-slate-500 text-xs">Cada QR abre el formulario de ticket con la PC pre-seleccionada.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="home.php" class="px-4 py-2 text-sm font-bold text-slate-400 hover:text-white transition-colors">← Volver</a>
                <button id="btnDescargarZip" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span id="btnZipLabel">Descargar ZIP</span>
                </button>
                <button onclick="window.print()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl shadow-lg shadow-blue-600/20 transition-all">
                    Imprimir
                </button>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto print:p-0">
            <!-- Filtros (solo en pantalla) -->
            <form method="get" class="max-w-[1400px] mx-auto mb-8 flex items-end gap-4 print:hidden">
                <div class="flex-1 max-w-sm">
                    <label class="text-slate-300 text-xs font-bold uppercase tracking-widest block mb-2">Filtrar por aula</label>
                    <select name="id_aula" class="w-full bg-[#0f172a] border border-slate-700 rounded-xl px-4 py-3 text-white outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas las aulas</option>
                        <?php foreach ($aulas as $a): ?>
                            <option value="<?= htmlspecialchars($a['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $filtroAula === $a['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['id'] . ' — ' . $a['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="px-5 py-3 bg-[#0f172a] hover:bg-slate-800 border border-slate-700 rounded-xl text-sm font-bold text-slate-200 transition-all">
                    Aplicar
                </button>
                <div class="ml-auto text-xs text-slate-500">
                    <?= count($pcs) ?> PC(s) · <?= count($aulas) ?> aula(s) activas
                </div>
            </form>

            <?php if (empty($pcs)): ?>
                <div class="max-w-xl mx-auto text-center py-16 text-slate-500 print:hidden">
                    No se encontraron PCs<?= $filtroAula !== '' ? ' en el aula seleccionada' : '' ?>.
                </div>
            <?php else: ?>
                <div id="qrGrid" class="max-w-[1400px] mx-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 print:grid-cols-4 print:gap-2">
                    <?php foreach ($pcs as $pc):
                        $url = $ticketBaseUrl . '?pc=' . rawurlencode($pc['id']);
                    ?>
                        <div class="qr-card bg-white border border-slate-200 rounded-xl p-4 flex flex-col items-center text-slate-900 break-inside-avoid print:rounded-none print:border-slate-400">
                            <div class="text-[10px] uppercase tracking-widest font-bold text-slate-500 mb-1">SoportET20 · ET20</div>
                            <div class="qr-box mb-2"
                                 data-url="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                                 data-pc="<?= htmlspecialchars($pc['id'], ENT_QUOTES, 'UTF-8') ?>"
                                 data-aula="<?= htmlspecialchars($pc['id_aula'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="text-center">
                                <div class="text-sm font-black leading-tight"><?= htmlspecialchars($pc['id'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-[11px] text-slate-600 leading-tight">
                                    Aula <?= htmlspecialchars($pc['id_aula'], ENT_QUOTES, 'UTF-8') ?>
                                    <?= $pc['nombre'] ? ' · ' . htmlspecialchars($pc['nombre'], ENT_QUOTES, 'UTF-8') : '' ?>
                                </div>
                                <div class="text-[9px] text-slate-400 mt-1">Escaneá para reportar un problema</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/qrcode.min.js"></script>
    <script src="assets/js/jszip.min.js"></script>
    <script>
        // Librería: qrcode-generator (Kazuhiko Arase). Auto-version con typeNumber=0.
        // Niveles: 'L', 'M', 'Q', 'H'.

        function crearQrDataUrl(texto, cellSize, margin, nivelCorreccion) {
            // typeNumber = 0 → auto-detecta la versión mínima requerida (1–40).
            const qr = qrcode(0, nivelCorreccion || 'M');
            qr.addData(texto);
            qr.make();
            return qr.createDataURL(cellSize, margin);
        }

        // Render en pantalla: cada .qr-box recibe un <img> con el QR.
        document.querySelectorAll('.qr-box').forEach(function (el) {
            const url = el.getAttribute('data-url');
            if (!url) return;
            try {
                const dataUrl = crearQrDataUrl(url, 4, 2, 'M');
                const img = document.createElement('img');
                img.src = dataUrl;
                img.alt = 'QR ' + (el.getAttribute('data-pc') || '');
                img.width = 140;
                img.height = 140;
                img.style.imageRendering = 'pixelated';
                el.appendChild(img);
            } catch (err) {
                console.error('Error generando QR para', el.getAttribute('data-pc'), err);
                el.textContent = 'Error QR';
            }
        });

        // --- Descarga masiva: empaqueta todos los QRs visibles en un .zip ---
        const btnZip   = document.getElementById('btnDescargarZip');
        const btnLabel = document.getElementById('btnZipLabel');

        function sanitizarNombre(s) {
            return String(s).replace(/[^A-Za-z0-9_\-]+/g, '_').replace(/^_+|_+$/g, '') || 'item';
        }

        async function descargarZip() {
            const boxes = document.querySelectorAll('.qr-box');
            if (boxes.length === 0) {
                alert('No hay PCs para exportar.');
                return;
            }
            if (typeof JSZip === 'undefined') {
                alert('No se pudo cargar JSZip.');
                return;
            }

            btnZip.disabled = true;
            const textoOriginal = btnLabel.textContent;

            try {
                const zip = new JSZip();
                const total = boxes.length;
                let hechos = 0;

                // Index legible para soporte: pc_id,aula,url
                const filas = [['id_pc', 'id_aula', 'url']];

                for (const el of boxes) {
                    const pcId  = el.getAttribute('data-pc')  || 'pc';
                    const aula  = el.getAttribute('data-aula') || 'sin_aula';
                    const url   = el.getAttribute('data-url')  || '';

                    let base64;
                    try {
                        // cellSize=16, margin=4 → PNG ~560px, nivel H para tolerancia a manchas.
                        const dataUrl = crearQrDataUrl(url, 16, 4, 'H');
                        base64 = dataUrl.split(',')[1];
                    } catch (err) {
                        console.warn('No se pudo generar QR para', pcId, err);
                        continue;
                    }
                    const folder = 'aula_' + sanitizarNombre(aula);
                    const filename = sanitizarNombre(pcId) + '.png';
                    zip.folder(folder).file(filename, base64, { base64: true });

                    filas.push([pcId, aula, url]);

                    hechos++;
                    btnLabel.textContent = `Generando ${hechos}/${total}…`;
                    // Ceder al event loop para que el label se actualice
                    if (hechos % 10 === 0) {
                        await new Promise(r => setTimeout(r, 0));
                    }
                }

                // index.csv (separador ; para compatibilidad con Excel en español)
                const csv = filas
                    .map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(';'))
                    .join('\r\n');
                zip.file('index.csv', csv);

                btnLabel.textContent = 'Empaquetando…';
                const blob = await zip.generateAsync({ type: 'blob', compression: 'DEFLATE' });

                const nombreZip = 'soportet20_qrs_' + new Date().toISOString().slice(0, 10) + '.zip';
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = nombreZip;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => URL.revokeObjectURL(a.href), 1000);

                btnLabel.textContent = 'Listo ✓';
                setTimeout(() => { btnLabel.textContent = textoOriginal; }, 1500);
            } catch (err) {
                console.error('Error generando ZIP:', err);
                alert('Error generando el ZIP. Revisá la consola.');
                btnLabel.textContent = textoOriginal;
            } finally {
                btnZip.disabled = false;
            }
        }

        if (btnZip) btnZip.addEventListener('click', descargarZip);
    </script>

    <style>
        @media print {
            @page { margin: 10mm; }
            aside, header, footer, nav, button, form { display: none !important; }
            .qr-card { page-break-inside: avoid; }
            body { background: #fff !important; color: #000 !important; }
        }
    </style>
</body>
</html>
