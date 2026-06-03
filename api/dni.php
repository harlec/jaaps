<?php
/**
 * api/dni.php
 * Proxy seguro para consulta DNI via apisunat.harlec.com.pe
 * GET ?dni=12345678
 * Devuelve JSON: {success, nombres, apellido_pat, apellido_mat, apellidos, nombre_completo}
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$dni = trim($_GET['dni'] ?? '');

if (!preg_match('/^\d{8}$/', $dni)) {
    echo json_encode(['success' => false, 'message' => 'DNI inválido. Debe tener 8 dígitos.']);
    exit;
}

if (SUNAT_API_TOKEN === '') {
    echo json_encode(['success' => false, 'message' => 'Token de API no configurado.']);
    exit;
}

$ch = curl_init(SUNAT_API_URL . '/' . $dni);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET        => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . SUNAT_API_TOKEN,
        'Accept: application/json',
    ],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || $response === false) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión con el servicio de DNI.']);
    exit;
}

if ($httpCode === 401 || $httpCode === 403) {
    echo json_encode(['success' => false, 'message' => 'Token de API inválido o sin acceso.']);
    exit;
}

if ($httpCode === 404) {
    echo json_encode(['success' => false, 'message' => 'DNI no encontrado en la base de datos.']);
    exit;
}

if ($httpCode === 429) {
    echo json_encode(['success' => false, 'message' => 'Límite diario de consultas alcanzado.']);
    exit;
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Respuesta inesperada del servicio.']);
    exit;
}

// Si la API devolvió un error en el cuerpo
if (!isset($data['nombres']) && !isset($data['apellido_pat'])) {
    $code = $data['code'] ?? '';
    $msg  = match($code) {
        'SOURCE_ERROR'      => 'El servicio RENIEC no está disponible en este momento. Intenta en unos minutos.',
        'PLAN_RESTRICTION'  => 'El plan actual no tiene acceso a consultas de DNI.',
        'INVALID_FORMAT'    => 'DNI inválido.',
        default             => $data['error'] ?? $data['message'] ?? 'Error del servicio de DNI.',
    };
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Normalizar campos de la respuesta
$nombres    = strtoupper(trim($data['nombres']      ?? ''));
$apellPat   = strtoupper(trim($data['apellido_pat'] ?? ''));
$apellMat   = strtoupper(trim($data['apellido_mat'] ?? ''));
$apellidos  = trim("$apellPat $apellMat");

if ($nombres === '' && $apellidos === '') {
    echo json_encode(['success' => false, 'message' => 'No se obtuvieron datos para ese DNI.']);
    exit;
}

echo json_encode([
    'success'        => true,
    'nombres'        => $nombres,
    'apellido_pat'   => $apellPat,
    'apellido_mat'   => $apellMat,
    'apellidos'      => $apellidos,
    'nombre_completo'=> trim("$apellidos $nombres"),
]);
