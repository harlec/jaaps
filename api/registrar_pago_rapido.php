<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$abonadoId  = (int)($_POST['abonado_id']  ?? 0);
$conceptoId = (int)($_POST['concepto_id'] ?? 0);
$periodoId  = (int)($_POST['periodo_id']  ?? 0) ?: null;
$monto      = (float)($_POST['monto']     ?? 0);

if ($abonadoId <= 0 || $conceptoId <= 0 || $monto <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$pdo = getDB();

// Verificar que no exista ya un pago para este abonado en el mismo período
if ($periodoId) {
    $dup = $pdo->prepare("SELECT id FROM pagos WHERE abonado_id = ? AND periodo_id = ? LIMIT 1");
    $dup->execute([$abonadoId, $periodoId]);
    if ($dup->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Este abonado ya tiene un pago registrado para este período']);
        exit;
    }
}

try {
    $numeroRecibo = generarNumeroRecibo();
    $ins = $pdo->prepare("
        INSERT INTO pagos
          (abonado_id, concepto_id, periodo_id, numero_recibo,
           monto, descuento, interes, monto_total,
           fecha_pago, metodo_pago, registrado_por)
        VALUES
          (:abonado_id, :concepto_id, :periodo_id, :numero_recibo,
           :monto, 0, 0, :monto_total,
           CURDATE(), 'efectivo', :registrado_por)
    ");
    $ins->execute([
        ':abonado_id'     => $abonadoId,
        ':concepto_id'    => $conceptoId,
        ':periodo_id'     => $periodoId,
        ':numero_recibo'  => $numeroRecibo,
        ':monto'          => $monto,
        ':monto_total'    => $monto,
        ':registrado_por' => currentUser()['id'],
    ]);
    echo json_encode(['success' => true, 'numero_recibo' => $numeroRecibo]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar: ' . $e->getMessage()]);
}
