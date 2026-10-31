<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/recibo.php';
requireLogin();
$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT p.*, a.nombres, a.apellidos, a.zona, c.nombre AS concepto, pc.nombre AS periodo, pc.fecha_inicio, pc.fecha_fin, pc.monto_total AS monto_periodo
    FROM pagos p
    JOIN abonados a ON a.id = p.abonado_id
    JOIN conceptos c ON c.id = p.concepto_id
    LEFT JOIN periodos_cobro pc ON pc.id = p.periodo_id
    WHERE p.id = ?
");
$stmt->execute([(int)($_GET['pago_id'] ?? 0)]);
$p = $stmt->fetch();
if (!$p) {
    flash('error', 'Pago no válido.');
    redirect(APP_URL . '/abonados/index.php');
}

$mes = $p['periodo'] ?? '';
if (!empty($p['fecha_inicio'])) {
    $mes = MESES_ES[(int)date('n', strtotime($p['fecha_inicio']))] . ' - '
         . MESES_ES[(int)date('n', strtotime($p['fecha_fin']))] . ' ' . date('Y', strtotime($p['fecha_fin']));
}
$monto = (float)($p['monto_periodo'] ?? $p['monto_total']);
$nombre  = $p['apellidos'] . ', ' . $p['nombres'];
$archivo = 'recibo_' . ($p['numero_recibo'] ?: $p['id']) . '.jpg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recibo <?= e($p['numero_recibo'] ?? '') ?> | JAAPS</title>
  <style>
    <?= reciboCss() ?>
    .toolbar { margin-bottom: 16px; }
    #captura { width: 148.5mm; height: 105mm; margin: 0 auto 24px; background: #fff; position: relative; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
    .sello { position: absolute; width: 78mm; right: 10mm; bottom: 12mm; transform: rotate(-12deg); opacity: .9; pointer-events: none; }
  </style>
</head>
<body>
<div class="toolbar">
  <a href="<?= APP_URL ?>/abonados/index.php">← Volver</a>
  <span><?= e($nombre) ?> · <?= e($mes) ?> · CANCELADO</span>
  <button id="btnJpg" type="button">Descargar JPG</button>
</div>

<div id="captura">
  <?php renderRecibo([
      'titulo'      => 'INGRESO',
      'etiqueta'    => 'Recibí del Sr.',
      'numero'      => (string)($p['numero_recibo'] ?? ''),
      'nombre'      => $nombre,
      'monto'       => $monto,
      'concepto'    => 'Cuota de agua potable',
      'mes'         => $mes,
      'etiqueta_fecha' => 'PAGO',
      'sector'      => ZONAS[$p['zona']] ?? $p['zona'],
      'fecha'       => $p['fecha_pago'],
      'vencimiento' => null,
  ]); ?>
  <img class="sello" src="<?= APP_URL ?>/img/sello_cancelado.png" alt="Cancelado">
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
async function descargarJpg() {
  const btn = document.getElementById('btnJpg');
  btn.disabled = true;
  try {
    const canvas = await html2canvas(document.getElementById('captura'), { scale: 3, backgroundColor: '#ffffff' });
    const a = document.createElement('a');
    a.href = canvas.toDataURL('image/jpeg', 0.95);
    a.download = <?= json_encode($archivo) ?>;
    a.click();
  } finally {
    btn.disabled = false;
  }
}
document.getElementById('btnJpg').addEventListener('click', descargarJpg);
</script>
</body>
</html>
