<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/recibo.php';
requireLogin();
$pdo = getDB();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT g.*, gc.nombre AS concepto
    FROM gastos g
    JOIN gasto_conceptos gc ON gc.id = g.concepto_id
    WHERE g.id = ?
");
$stmt->execute([$id]);
$g = $stmt->fetch();
if (!$g) {
    flash('error', 'Gasto no encontrado.');
    redirect(APP_URL . '/gastos/index.php');
}

$ts     = strtotime($g['fecha_gasto']);
$numero = str_pad((string)$g['id'], 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recibo de egreso Nº <?= e($numero) ?> | JAAPS</title>
  <style><?= reciboCss() ?></style>
</head>
<body>
<div class="toolbar">
  <a href="<?= APP_URL ?>/gastos/index.php">← Volver a gastos</a>
  <span>Recibo de egreso Nº <?= e($numero) ?> · A6 en hoja A4</span>
  <button onclick="window.print()">Imprimir</button>
</div>
<?php renderRecibos([[
    'titulo'      => 'EGRESO',
    'etiqueta'    => 'Pagado a:',
    'numero'      => $numero,
    'nombre'      => $g['proveedor'] ?? '',
    'monto'       => $g['monto'],
    'concepto'    => $g['concepto'] . ($g['descripcion'] ? ' – ' . $g['descripcion'] : ''),
    'mes'         => MESES_ES[(int)date('n', $ts)] . ' ' . date('Y', $ts),
    'fecha'       => $g['fecha_gasto'],
    'vencimiento' => null,
]]); ?>
</body>
</html>
