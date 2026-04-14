<?php
declare(strict_types=1);

// backend/estadisticas.php - KPIs y datos agregados para el dashboard.
// Solo ADMIN y TECNICO pueden consultar (para el director/supervisor).

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/session_check.php';

header('Content-Type: application/json');
requiereRol(['ADMIN', 'TECNICO']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método no permitido.'], 405);
}

// ── KPIs ────────────────────────────────────────────────────────────────────
$kpis = [];

$stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado IN ('ABIERTO','EN_PROGRESO')");
$kpis['tickets_abiertos'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query(
    "SELECT COUNT(*) FROM tickets
     WHERE estado IN ('RESUELTO','CERRADO')
       AND cerrado_en >= NOW() - INTERVAL 7 DAY"
);
$kpis['resueltos_ultima_semana'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query(
    "SELECT
        SUM(estado = 'OPERATIVA')      AS operativas,
        SUM(estado <> 'OPERATIVA')     AS no_operativas,
        COUNT(*)                       AS total
     FROM computadoras"
);
$pcs = $stmt->fetch();
$kpis['pcs_operativas']    = (int)($pcs['operativas'] ?? 0);
$kpis['pcs_no_operativas'] = (int)($pcs['no_operativas'] ?? 0);
$kpis['pcs_total']         = (int)($pcs['total'] ?? 0);
$kpis['pcs_operativas_pct'] = $kpis['pcs_total'] > 0
    ? round(($kpis['pcs_operativas'] / $kpis['pcs_total']) * 100, 1)
    : 0;

$stmt = $pdo->query(
    "SELECT AVG(TIMESTAMPDIFF(HOUR, creado_en, cerrado_en))
     FROM tickets
     WHERE estado IN ('RESUELTO','CERRADO')
       AND cerrado_en IS NOT NULL
       AND cerrado_en >= NOW() - INTERVAL 30 DAY"
);
$avg = $stmt->fetchColumn();
$kpis['tiempo_medio_resolucion_horas'] = $avg !== false && $avg !== null
    ? round((float)$avg, 1)
    : null;

// ── Tickets por aula (solo abiertos/en progreso) ───────────────────────────
$stmt = $pdo->query(
    "SELECT c.id_aula AS aula, t.estado, COUNT(*) AS cnt
     FROM tickets t
     JOIN computadoras c ON c.id = t.id_pc
     WHERE t.estado IN ('ABIERTO','EN_PROGRESO')
     GROUP BY c.id_aula, t.estado
     ORDER BY c.id_aula"
);
$ticketsPorAula = $stmt->fetchAll();

// ── Estado de PCs (todas) ──────────────────────────────────────────────────
$stmt = $pdo->query("SELECT estado, COUNT(*) AS cnt FROM computadoras GROUP BY estado");
$estadoPcs = $stmt->fetchAll();

// ── Tickets por tipo (últimos 30 días) ─────────────────────────────────────
$stmt = $pdo->query(
    "SELECT tipo, COUNT(*) AS cnt
     FROM tickets
     WHERE creado_en >= NOW() - INTERVAL 30 DAY
     GROUP BY tipo
     ORDER BY cnt DESC"
);
$ticketsPorTipo = $stmt->fetchAll();

// ── Resolución semanal (últimas 8 semanas) ─────────────────────────────────
$stmt = $pdo->query(
    "SELECT
        DATE(DATE_SUB(cerrado_en, INTERVAL WEEKDAY(cerrado_en) DAY)) AS inicio_semana,
        COUNT(*)                                                     AS resueltos,
        ROUND(AVG(TIMESTAMPDIFF(HOUR, creado_en, cerrado_en)), 1)    AS tiempo_medio_h
     FROM tickets
     WHERE estado IN ('RESUELTO','CERRADO')
       AND cerrado_en IS NOT NULL
       AND cerrado_en >= NOW() - INTERVAL 8 WEEK
     GROUP BY inicio_semana
     ORDER BY inicio_semana ASC"
);
$resolucionSemanal = $stmt->fetchAll();

// ── Top 5 PCs con más incidencias (histórico) ──────────────────────────────
$stmt = $pdo->query(
    "SELECT t.id_pc, c.id_aula, c.nombre, COUNT(*) AS incidencias
     FROM tickets t
     JOIN computadoras c ON c.id = t.id_pc
     GROUP BY t.id_pc, c.id_aula, c.nombre
     ORDER BY incidencias DESC
     LIMIT 5"
);
$topPcs = $stmt->fetchAll();

jsonResponse([
    'kpis'               => $kpis,
    'tickets_por_aula'   => $ticketsPorAula,
    'estado_pcs'         => $estadoPcs,
    'tickets_por_tipo'   => $ticketsPorTipo,
    'resolucion_semanal' => $resolucionSemanal,
    'top_pcs'            => $topPcs,
]);
