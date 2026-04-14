<?php
declare(strict_types=1);

/**
 * bin/heartbeat.php - Heartbeat / ping CLI script for EduMonitor-ET20
 *
 * Recorre todas las computadoras con IP asignada, intenta abrir un socket TCP
 * contra puertos tipicos de Windows (SMB/445 con fallback a RPC/135) y ajusta
 * el estado de la PC en base al resultado del ping + horario escolar.
 *
 * Reglas de transicion de estado (solo se aplican cambios selectivos para no
 * pisar decisiones humanas como MANTENIMIENTO o FUERA_SERVICIO):
 *
 *   Ping OK  + horario escolar   -> OPERATIVA    (solo si estaba HIBERNANDO)
 *   Ping OK  + fuera de horario  -> ALERTA       (solo si estaba OPERATIVA/HIBERNANDO)
 *   Ping FAIL + horario escolar  -> sin cambios  (puede ser un recreo / cambio de hora)
 *   Ping FAIL + fuera de horario -> HIBERNANDO   (solo si estaba OPERATIVA)
 *
 * Uso:
 *   php bin/heartbeat.php              # ejecucion real
 *   php bin/heartbeat.php --dry-run    # solo imprime, no escribe en DB
 *
 * No debe ejecutarse desde un contexto web: se valida php_sapi_name() === 'cli'.
 */

// -----------------------------------------------------------------------------
// Guarda: este script solo debe correr en CLI.
// Evita que un admin curioso lo dispare via navegador.
// -----------------------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Este script solo puede ejecutarse desde la linea de comandos (CLI).\n";
    exit(1);
}

// -----------------------------------------------------------------------------
// Configuracion de horario escolar (modificar aqui si cambia el turno).
// Dias: 1 (Lunes) ... 7 (Domingo), segun date('N').
// Horas: formato 24hs en enteros.
// -----------------------------------------------------------------------------
const HORARIO_DIAS_HABILES   = [1, 2, 3, 4, 5]; // Lun-Vie
const HORARIO_HORA_INICIO    = 7;               // 07:00
const HORARIO_HORA_FIN       = 18;              // 18:00 (exclusivo)

// Puertos TCP a probar (primero SMB, luego RPC como fallback).
const PING_PUERTOS           = [445, 135];
const PING_TIMEOUT_SEGUNDOS  = 1;

// -----------------------------------------------------------------------------
// Argumentos CLI
// -----------------------------------------------------------------------------
$dryRun = in_array('--dry-run', $argv, true);

// -----------------------------------------------------------------------------
// Cargar funciones compartidas (registrarAccion).
// functions.php instala un exception handler pensado para HTTP/JSON; lo
// sobrescribimos para no confundir la salida CLI.
// -----------------------------------------------------------------------------
require __DIR__ . '/../backend/functions.php';

set_exception_handler(function (Throwable $e): void {
    fwrite(STDERR, sprintf(
        "[%s] ERROR heartbeat: %s en %s:%d\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    exit(1);
});

// -----------------------------------------------------------------------------
// Conexion a la base de datos (misma config que backend/db.php pero sin los
// efectos colaterales HTTP).
// -----------------------------------------------------------------------------
function conectarDb(): PDO
{
    $host    = getenv('DB_HOST') ?: 'localhost';
    $db      = getenv('DB_NAME') ?: 'soportet20_db';
    $user    = getenv('DB_USER') ?: 'root';
    $pass    = getenv('DB_PASS') ?: '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    return new PDO($dsn, $user, $pass, $options);
}

/**
 * Intenta un ping TCP contra una IP probando los puertos configurados.
 * Usa fsockopen en lugar de shell_exec/exec (mas seguro, portable, sin ICMP).
 * Devuelve true si alguno de los puertos respondio.
 */
function pingTcp(string $ip): bool
{
    foreach (PING_PUERTOS as $puerto) {
        // Suprimir warning con @ y capturar explicitamente via $errno.
        $errno  = 0;
        $errstr = '';
        $sock = @fsockopen($ip, $puerto, $errno, $errstr, PING_TIMEOUT_SEGUNDOS);
        if ($sock !== false) {
            fclose($sock);
            return true;
        }
    }
    return false;
}

/**
 * Determina si el timestamp actual cae dentro del horario escolar.
 */
function enHorarioEscolar(int $timestamp): bool
{
    $diaSemana = (int) date('N', $timestamp); // 1..7
    $hora      = (int) date('G', $timestamp); // 0..23
    if (!in_array($diaSemana, HORARIO_DIAS_HABILES, true)) {
        return false;
    }
    return $hora >= HORARIO_HORA_INICIO && $hora < HORARIO_HORA_FIN;
}

/**
 * Decide el estado destino segun ping + horario + estado actual.
 * Retorna null si no corresponde ningun cambio.
 */
function decidirNuevoEstado(bool $pingOk, bool $enHorario, string $estadoActual): ?string
{
    // Nunca pisar estados de decision humana.
    if ($estadoActual === 'MANTENIMIENTO' || $estadoActual === 'FUERA_SERVICIO') {
        return null;
    }

    if ($pingOk && $enHorario) {
        // PC viva durante el dia: normal. Solo transicionar desde HIBERNANDO
        // para no pisar ALERTA (flag de seguridad).
        return $estadoActual === 'HIBERNANDO' ? 'OPERATIVA' : null;
    }

    if ($pingOk && !$enHorario) {
        // Alguien esta usando la PC fuera de horas -> ALERTA.
        if ($estadoActual === 'OPERATIVA' || $estadoActual === 'HIBERNANDO') {
            return 'ALERTA';
        }
        return null;
    }

    if (!$pingOk && $enHorario) {
        // Puede ser apagada entre clases; no molestar.
        return null;
    }

    // !pingOk && !enHorario -> HIBERNANDO (solo si venia OPERATIVA).
    if ($estadoActual === 'OPERATIVA') {
        return 'HIBERNANDO';
    }
    return null;
}

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------
$inicio         = time();
$timestampIso   = date('Y-m-d H:i:s', $inicio);
$enHorario      = enHorarioEscolar($inicio);
$totalPcs       = 0;
$onlinePcs      = 0;
$cambiosEstado  = 0;

try {
    $pdo = conectarDb();

    $stmt = $pdo->query(
        "SELECT id, nombre, ip, estado
         FROM computadoras
         WHERE ip IS NOT NULL AND ip <> ''"
    );
    $computadoras = $stmt->fetchAll();

    $updateStmt = $pdo->prepare(
        "UPDATE computadoras SET estado = :estado WHERE id = :id"
    );

    foreach ($computadoras as $pc) {
        $totalPcs++;
        $pcId          = (string) $pc['id'];
        $ip            = (string) $pc['ip'];
        $estadoActual  = (string) $pc['estado'];

        // Nunca dejar que una PC rompa el loop completo.
        try {
            $pingOk = pingTcp($ip);
        } catch (Throwable $e) {
            fwrite(STDERR, sprintf(
                "[%s] WARN ping fallo para %s (%s): %s\n",
                $timestampIso,
                $pcId,
                $ip,
                $e->getMessage()
            ));
            $pingOk = false;
        }

        if ($pingOk) {
            $onlinePcs++;
        }

        $nuevoEstado = decidirNuevoEstado($pingOk, $enHorario, $estadoActual);
        if ($nuevoEstado === null || $nuevoEstado === $estadoActual) {
            continue;
        }

        $detalle = sprintf(
            'PC %s (%s): %s -> %s (ping=%s, horario_escolar=%s)',
            $pcId,
            $ip,
            $estadoActual,
            $nuevoEstado,
            $pingOk ? 'OK' : 'FAIL',
            $enHorario ? 'SI' : 'NO'
        );

        if ($dryRun) {
            echo "[DRY-RUN] {$detalle}\n";
            $cambiosEstado++;
            continue;
        }

        try {
            $updateStmt->execute([
                ':estado' => $nuevoEstado,
                ':id'     => $pcId,
            ]);
            registrarAccion($pdo, null, 'HEARTBEAT_CAMBIO_ESTADO', $detalle);
            $cambiosEstado++;
        } catch (Throwable $e) {
            fwrite(STDERR, sprintf(
                "[%s] WARN update fallo para %s: %s\n",
                $timestampIso,
                $pcId,
                $e->getMessage()
            ));
        }
    }

    $prefijo = $dryRun ? 'Heartbeat [DRY-RUN]' : 'Heartbeat';
    printf(
        "[%s] %s: %d PCs, %d online, %d state changes\n",
        $timestampIso,
        $prefijo,
        $totalPcs,
        $onlinePcs,
        $cambiosEstado
    );
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, sprintf(
        "[%s] FATAL heartbeat: %s\n",
        $timestampIso,
        $e->getMessage()
    ));
    exit(1);
}
