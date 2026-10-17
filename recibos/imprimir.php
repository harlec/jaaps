<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/recibo.php';
requireLogin();
$pdo = getDB();

$periodoId = (int)($_GET['periodo_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM periodos_cobro WHERE id = ?");
$stmt->execute([$periodoId]);
$periodo = $stmt->fetch();
if (!$periodo) {
    flash('error', 'Período no válido.');
    redirect(APP_URL . '/recibos/index.php');
}

$validaFecha = fn(string $f): bool => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) && strtotime($f) !== false;
$emision     = trim($_GET['fecha_emision'] ?? '');
$venc        = trim($_GET['fecha_vencimiento'] ?? '');
if (!$validaFecha($emision)) $emision = date('Y-m-d');
if (!$validaFecha($venc))    $venc    = $periodo['fecha_vencimiento'];

$monto    = max(0, (float)($_GET['monto'] ?? $periodo['monto_total']));
$concepto = trim($_GET['concepto'] ?? '') ?: 'Cuota de agua potable';
$mes      = trim($_GET['mes'] ?? '') ?: $periodo['nombre'];
$zona     = $_GET['zona'] ?? '';
$soloDeud = ($_GET['solo_deudores'] ?? '') === '1';
$numIni   = ($_GET['numero_inicial'] ?? '') !== '' ? max(0, (int)$_GET['numero_inicial']) : null;

// Solo abonados activos (excluye cortados, suspensión temporal e inactivos)
$where  = ["a.estado = 'activo'"];
$params = [];
if ($zona !== '' && isset(ZONAS[$zona])) { $where[] = 'a.zona = ?'; $params[] = $zona; }
if ($soloDeud) {
    $where[]  = 'a.id NOT IN (SELECT p.abonado_id FROM pagos p WHERE p.periodo_id = ?)';
    $params[] = $periodoId;
}
$stmt = $pdo->prepare("
    SELECT a.codigo, a.nombres, a.apellidos
    FROM abonados a
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.zona, a.apellidos, a.nombres
");
$stmt->execute($params);
$abonados = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recibos – <?= e($periodo['nombre']) ?> | JAAPS</title>
  <style><?= reciboCss() ?></style>
</head>
<body>
<div class="toolbar">
  <a href="<?= APP_URL ?>/recibos/index.php">← Volver</a>
  <span><?= count($abonados) ?> recibos · <?= e($periodo['nombre']) ?> · A6 horizontal · 4 por hoja A4</span>
  <button onclick="window.print()">Imprimir</button>
</div>
<?php if (!$abonados): ?>
  <p style="text-align:center;padding:40px;color:#6b7280">No hay abonados activos que cumplan los filtros.</p>
<?php endif; ?>
<?php
$recibos = [];
foreach ($abonados as $i => $a) {
    $recibos[] = [
        'titulo'      => 'INGRESO',
        'etiqueta'    => 'Recibí del Sr.',
        'numero'      => $numIni !== null ? str_pad((string)($numIni + $i), 6, '0', STR_PAD_LEFT) : '',
        'nombre'      => $a['apellidos'] . ', ' . $a['nombres'],
        'monto'       => $monto,
        'concepto'    => $concepto,
        'mes'         => $mes,
        'fecha'       => $emision,
        'vencimiento' => $venc,
    ];
}
renderRecibos($recibos);
?>
</body>
</html>
