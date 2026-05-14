<?php
/**
 * Configuración general de la aplicación JAAP
 */

declare(strict_types=1);

require_once __DIR__ . '/env.php';

// ── Aplicación ───────────────────────────────────────────────
define('APP_NAME',    'JAAP – Junta Administradora de Agua Potable');
define('APP_VERSION', '1.0.0');
define('APP_URL',     $_ENV['APP_URL'] ?? 'http://localhost/jaaps');

// ── Sesión ──────────────────────────────────────────────────
define('SESSION_NAME',      'jaap_session');
define('SESSION_LIFETIME',  7200); // 2 horas en segundos

// ── API migo.pe – DNI lookup ─────────────────────────────────
// El token se lee del archivo .env (ver .env.example)
define('MIGO_API_TOKEN', $_ENV['MIGO_API_TOKEN'] ?? '');
define('MIGO_API_URL',   'https://api.migo.pe/api/v1/dni');

// ── Tarifa mensual (S/.) ─────────────────────────────────────
define('TARIFA_MENSUAL', 12.00);

// ── Zonas disponibles ────────────────────────────────────────
define('ZONAS', [
    'porvenir'       => 'Porvenir',
    'tunas'          => 'Tunas',
    'cerro_de_pasco' => 'Cerro de Pasco',
]);

// ── Grados de instrucción ────────────────────────────────────
define('GRADOS_INSTRUCCION', [
    'sin_instruccion'       => 'Sin instrucción',
    'primaria_incompleta'   => 'Primaria incompleta',
    'primaria_completa'     => 'Primaria completa',
    'secundaria_incompleta' => 'Secundaria incompleta',
    'secundaria_completa'   => 'Secundaria completa',
    'tecnico'               => 'Técnico',
    'universitario'         => 'Universitario',
    'posgrado'              => 'Posgrado',
]);

// ── Estado civil ─────────────────────────────────────────────
define('ESTADOS_CIVILES', [
    'soltero'     => 'Soltero/a',
    'casado'      => 'Casado/a',
    'conviviente' => 'Conviviente',
    'viudo'       => 'Viudo/a',
    'divorciado'  => 'Divorciado/a',
]);

// ── Estados de abonado ───────────────────────────────────────
define('ESTADOS_ABONADO', [
    'activo'     => 'Activo',
    'inactivo'   => 'Inactivo',
    'suspendido' => 'Suspendido',
]);

// ── Métodos de pago ──────────────────────────────────────────
define('METODOS_PAGO', [
    'efectivo'      => 'Efectivo',
    'transferencia' => 'Transferencia bancaria',
    'deposito'      => 'Depósito',
    'otro'          => 'Otro',
]);

// ── Zona horaria ─────────────────────────────────────────────
date_default_timezone_set('America/Lima');

// ── Include del motor de BD ──────────────────────────────────
require_once __DIR__ . '/database.php';

// ── Inicio de sesión seguro ──────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_name(SESSION_NAME);
    session_start();
}

// ── Helpers globales ─────────────────────────────────────────
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(APP_URL . '/login.php');
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();
    if (!in_array($_SESSION['user_rol'] ?? '', $roles, true)) {
        http_response_code(403);
        die('<p>Acceso denegado.</p>');
    }
}

function flash(string $key, string $message = ''): string
{
    if ($message !== '') {
        $_SESSION['flash'][$key] = $message;
        return '';
    }
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function currentUser(): array
{
    return [
        'id'     => $_SESSION['user_id']  ?? 0,
        'nombre' => $_SESSION['user_nombre'] ?? '',
        'rol'    => $_SESSION['user_rol']  ?? '',
    ];
}

/**
 * Genera correlativo de código de abonado: AB-0001, AB-0002, …
 */
function generarCodigo(): string
{
    $pdo = getDB();
    $row = $pdo->query("SELECT MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)) AS max FROM abonados")->fetch();
    $next = ($row['max'] ?? 0) + 1;
    return 'AB-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

/**
 * Genera número de recibo: REC-YYYYNNNNN
 */
function generarNumeroRecibo(): string
{
    $pdo  = getDB();
    $anio = date('Y');
    $row  = $pdo->query(
        "SELECT COUNT(*) AS total FROM pagos WHERE YEAR(fecha_pago) = $anio"
    )->fetch();
    $next = ($row['total'] ?? 0) + 1;
    return 'REC-' . $anio . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
}
