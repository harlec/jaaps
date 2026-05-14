<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect(APP_URL . '/abonados/index.php'); }

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, codigo, nombres, apellidos FROM abonados WHERE id = ?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) { redirect(APP_URL . '/abonados/index.php'); }

// Verificar si tiene pagos antes de eliminar
$pagosCount = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE abonado_id = ?");
$pagosCount->execute([$id]);
if ((int)$pagosCount->fetchColumn() > 0) {
    flash('error', "No se puede eliminar al abonado {$a['codigo']} porque tiene pagos registrados. Cambia su estado a Inactivo.");
    redirect(APP_URL . '/abonados/index.php');
}

try {
    $pdo->prepare("DELETE FROM hijos WHERE abonado_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM inscripciones WHERE abonado_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM abonados WHERE id = ?")->execute([$id]);
    flash('success', "Abonado {$a['codigo']} – {$a['apellidos']}, {$a['nombres']} eliminado.");
} catch (PDOException $e) {
    flash('error', 'No se pudo eliminar el abonado: ' . $e->getMessage());
}

redirect(APP_URL . '/abonados/index.php');
