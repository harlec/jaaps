<?php
/**
 * api/dni.php
 * Proxy seguro para consulta DNI via migo.pe
 * GET ?dni=12345678
 * Devuelve JSON compatible con la respuesta de migo.pe
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// Requiere sesión activa (no debe ser accesible sin autenticar)
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

if (MIGO_API_TOKEN === 'TU_TOKEN_AQUI' || MIGO_API_TOKEN === '') {
    echo json_encode(['success' => false, 'message' => 'Token de API no configurado. Edita config/config.php']);
    exit;
}

// Llamada a migo.pe
$payload = json_encode(['token' => MIGO_API_TOKEN, 'dni' => $dni]);

$ch = curl_init(MIGO_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => $payload,
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

if ($httpCode === 403) {
    echo json_encode(['success' => false, 'message' => 'Token de API inválido o sin créditos.']);
    exit;
}

if ($httpCode === 404) {
    echo json_encode(['success' => false, 'message' => 'DNI no encontrado en la base de datos.']);
    exit;
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Respuesta inesperada del servicio.']);
    exit;
}

// Reenviar respuesta de migo.pe directamente
echo json_encode($data);
