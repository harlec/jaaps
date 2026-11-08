<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
requireLogin();
$pdo = getDB();

$periodos = $pdo->query(
    "SELECT id, nombre, anio, semestre, monto_total FROM periodos_cobro ORDER BY anio DESC, semestre"
)->fetchAll();

$periodoId = (int)($_GET['periodo_id'] ?? 0);
if ($periodoId === 0) {
    foreach ($periodos as $p) {
        if ((int)$p['anio'] === (int)date('Y') && $p['semestre'] === (date('n') >= 7 ? '2' : '1')) { $periodoId = (int)$p['id']; break; }
    }
    if ($periodoId === 0 && !empty($periodos)) $periodoId = (int)$periodos[0]['id'];
}

$periodoActual = null;
foreach ($periodos as $p) { if ((int)$p['id'] === $periodoId) { $periodoActual = $p; break; } }

$zona      = $_GET['zona'] ?? '';
$whereZona = $zona !== '' ? "AND a.zona = ?" : '';
$params    = [$periodoId];
if ($zona !== '') $params[] = $zona;

// Todos los abonados activos y en baja temporal, con marca de si ya pagaron el período
$porZona = [];
$total   = 0; $pagaron = 0;
if ($periodoActual) {
    $stmt = $pdo->prepare("
        SELECT a.codigo, a.nombres, a.apellidos, a.zona, a.estado,
               EXISTS (SELECT 1 FROM pagos p WHERE p.abonado_id = a.id AND p.periodo_id = ?) AS pagado
        FROM abonados a
        WHERE a.estado IN ('activo','suspension_temporal')
          $whereZona
        ORDER BY a.zona, a.apellidos, a.nombres
    ");
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $d) {
        $porZona[$d['zona']][] = $d;
        $total++;
        if ($d['pagado']) $pagaron++;
    }
}
$monto = (float)($periodoActual['monto_total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lista de cobranza – <?= $periodoActual ? e($periodoActual['nombre']) : '' ?> | JAAPS</title>
  <style>
    @page { size: A4; margin: 10mm 12mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 12px; color: #111; background: #f3f4f6; }
    .toolbar { background: #0d9488; color: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; gap: 12px; }
    .toolbar button { background: #fff; color: #0d9488; border: none; padding: 7px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; }
    .toolbar a { color: rgba(255,255,255,.85); text-decoration: none; font-size: 12px; }
    .page { width: 210mm; background: #fff; margin: 16px auto; padding: 12mm; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
    h1 { font-size: 15px; }
    .sub { font-size: 11px; color: #555; margin: 2px 0 10px; border-bottom: 2px solid #0d9488; padding-bottom: 6px; }
    .zona-title { background: #0f766e; color: #fff; font-size: 11px; font-weight: bold; padding: 4px 8px; margin-top: 12px; text-transform: uppercase; letter-spacing: .05em; break-after: avoid; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 10px; text-transform: uppercase; color: #374151; background: #f3f4f6; padding: 4px 6px; border-bottom: 1px solid #9ca3af; }
    td { padding: 6px; border-bottom: 1px solid #d1d5db; }
    tr { break-inside: avoid; }
    .n { width: 28px; color: #6b7280; font-size: 10px; }
    .cod { width: 62px; font-family: 'Courier New', monospace; font-size: 11px; }
    .chk { width: 52px; text-align: center; }
    .chk span { display: inline-block; width: 15px; height: 15px; border: 1.5px solid #111; vertical-align: middle; font-weight: 900; font-size: 13px; line-height: 12px; }
    tr.ok td { background: #ecfdf5; }
    .cobro { font-size: 11px; }
    .obs { font-size: 10px; font-weight: bold; }
    .cobro { width: 90px; }
    .obs { width: 110px; }
    @media print {
      body { background: #fff; }
      .toolbar { display: none; }
      .page { margin: 0; padding: 0; box-shadow: none; width: 100%; }
      * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>
<div class="toolbar">
  <a href="deudores.php?periodo_id=<?= $periodoId ?>&zona=<?= urlencode($zona) ?>">← Volver</a>
  <span><?= $total ?> abonados · <?= $pagaron ?> pagaron · <?= $total - $pagaron ?> pendientes</span>
  <button onclick="window.print()">Imprimir</button>
</div>

<div class="page">
  <h1>JAAPS – Lista de cobranza</h1>
  <p class="sub">
    <?= $periodoActual ? e($periodoActual['nombre']) : '' ?> · S/ <?= number_format($monto, 2) ?> por abonado ·
    <?= $zona !== '' ? e(ZONAS[$zona] ?? $zona) : 'Todas las zonas' ?> · Impreso el <?= date('d/m/Y') ?><br>
    Pagaron: <?= $pagaron ?> · Pendientes: <?= $total - $pagaron ?> · Baja temporal se indica en Obs.
  </p>

  <?php if (!$porZona): ?>
    <p style="text-align:center;padding:30px;color:#16a34a;font-weight:bold">✓ No hay abonados.</p>
  <?php endif; ?>

  <?php foreach ($porZona as $z => $lista): $i = 0; ?>
  <div class="zona-title"><?= e(ZONAS[$z] ?? $z) ?> — <?= count($lista) ?></div>
  <table>
    <thead>
      <tr><th class="n">#</th><th class="cod">Código</th><th>Apellidos y nombres</th><th class="chk">Yape</th><th class="chk">Efectivo</th><th class="cobro">Monto S/</th><th class="obs">Obs.</th></tr>
    </thead>
    <tbody>
      <?php foreach ($lista as $d): $i++; ?>
      <tr class="<?= $d['pagado'] ? 'ok' : '' ?>">
        <td class="n"><?= $i ?></td>
        <td class="cod"><?= e($d['codigo']) ?></td>
        <td><strong><?= e(mb_strtoupper($d['apellidos'] . ', ' . $d['nombres'])) ?></strong></td>
        <td class="chk"><span><?= $d['pagado'] ? 'X' : '' ?></span></td>
        <td class="chk"><span></span></td>
        <td class="cobro"><?= $d['pagado'] ? number_format($monto, 2) : '' ?></td>
        <td class="obs"><?= $d['estado'] === 'suspension_temporal' ? 'Baja temporal' : '' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endforeach; ?>
</div>
</body>
</html>
