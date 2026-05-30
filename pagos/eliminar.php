<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Pago no válido.');
    redirect(APP_URL . '/pagos/index.php');
}

$stmt = $pdo->prepare("
    SELECT p.id, p.numero_recibo, p.monto_total, p.fecha_pago,
           CONCAT(a.apellidos, ' ', a.nombres) AS abonado, a.codigo
    FROM pagos p
    JOIN abonados a ON a.id = p.abonado_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pago = $stmt->fetch();

if (!$pago) {
    flash('error', 'Pago no encontrado.');
    redirect(APP_URL . '/pagos/index.php');
}

try {
    $pdo->prepare("DELETE FROM pagos WHERE id = ?")->execute([$id]);
    flash('success', 'Pago ' . $pago['numero_recibo'] . ' eliminado correctamente.');
} catch (PDOException $e) {
    flash('error', 'Error al eliminar el pago.');
}

redirect(APP_URL . '/pagos/index.php');
