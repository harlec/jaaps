<?php
/**
 * api/buscar_abonado.php
 * Búsqueda AJAX de abonados por nombre, apellido, DNI o código.
 * GET ?q=texto
 * Devuelve JSON array de resultados.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo '[]';
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo '[]'; exit; }

$pdo  = getDB();
$like = '%' . $q . '%';

$stmt = $pdo->prepare("
    SELECT id, codigo, dni, nombres, apellidos, zona
    FROM abonados
    WHERE (dni LIKE ? OR nombres LIKE ? OR apellidos LIKE ? OR codigo LIKE ?)
      AND estado = 'activo'
    ORDER BY apellidos, nombres
    LIMIT 10
");
$stmt->execute([$like, $like, $like, $like]);
$rows = $stmt->fetchAll();

echo json_encode($rows);
