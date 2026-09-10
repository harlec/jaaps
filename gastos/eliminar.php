<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Gasto no válido.');
    redirect(APP_URL . '/gastos/index.php');
}

$stmt = $pdo->prepare("SELECT id, monto FROM gastos WHERE id = ?");
$stmt->execute([$id]);
$gasto = $stmt->fetch();

if (!$gasto) {
    flash('error', 'Gasto no encontrado.');
    redirect(APP_URL . '/gastos/index.php');
}

try {
    $pdo->prepare("DELETE FROM gastos WHERE id = ?")->execute([$id]);
    flash('success', 'Gasto de S/ ' . number_format((float)$gasto['monto'], 2) . ' eliminado correctamente.');
} catch (PDOException $e) {
    flash('error', 'Error al eliminar el gasto.');
}

redirect(APP_URL . '/gastos/index.php');
