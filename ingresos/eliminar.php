<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Ingreso no válido.');
    redirect(APP_URL . '/ingresos/index.php');
}

$stmt = $pdo->prepare("SELECT id, monto FROM ingresos_otros WHERE id = ?");
$stmt->execute([$id]);
$ingreso = $stmt->fetch();

if (!$ingreso) {
    flash('error', 'Ingreso no encontrado.');
    redirect(APP_URL . '/ingresos/index.php');
}

try {
    $pdo->prepare("DELETE FROM ingresos_otros WHERE id = ?")->execute([$id]);
    flash('success', 'Ingreso de S/ ' . number_format((float)$ingreso['monto'], 2) . ' eliminado correctamente.');
} catch (PDOException $e) {
    flash('error', 'Error al eliminar el ingreso.');
}

redirect(APP_URL . '/ingresos/index.php');
