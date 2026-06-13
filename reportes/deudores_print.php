<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
requireLogin();
$pdo = getDB();

// Períodos
$periodos = $pdo->query(
    "SELECT id, nombre, anio, semestre, monto_total FROM periodos_cobro ORDER BY anio DESC, semestre"
)->fetchAll();

$periodoId = (int)($_GET['periodo_id'] ?? 0);
if ($periodoId === 0) {
    foreach ($periodos as $p) {
        if ((int)$p['anio'] === 2026 && $p['semestre'] === '1') { $periodoId = (int)$p['id']; break; }
    }
    if ($periodoId === 0 && !empty($periodos)) $periodoId = (int)$periodos[0]['id'];
}

$periodoActual = null;
foreach ($periodos as $p) { if ((int)$p['id'] === $periodoId) { $periodoActual = $p; break; } }

$zona = $_GET['zona'] ?? '';
$whereZona = $zona !== '' ? "AND a.zona = ?" : '';
$params    = [$periodoId];
if ($zona !== '') $params[] = $zona;

$deudores   = [];
$totalDeuda = 0.0;
if ($periodoActual) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.codigo, a.dni, a.nombres, a.apellidos, a.zona, a.telefono
        FROM abonados a
        WHERE a.estado = 'activo'
          AND a.id NOT IN (SELECT p.abonado_id FROM pagos p WHERE p.periodo_id = ?)
          $whereZona
        ORDER BY a.zona, a.apellidos, a.nombres
    ");
    $stmt->execute($params);
    $deudores   = $stmt->fetchAll();
    $totalDeuda = count($deudores) * (float)$periodoActual['monto_total'];
}

// Agrupar por zona
$porZona = [];
foreach ($deudores as $d) {
    $porZona[$d['zona']][] = $d;
}

// Resumen por zona
$resumenZona = [];
foreach ($porZona as $z => $lista) {
    $resumenZona[$z] = [
        'cantidad' => count($lista),
        'subtotal' => count($lista) * (float)$periodoActual['monto_total'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deudores – <?= $periodoActual ? e($periodoActual['nombre']) : 'Reporte' ?> | JAAP</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      color: #111;
      background: #f3f4f6;
    }

    /* ── Barra de herramientas (solo pantalla) ── */
    .toolbar {
      background: #0d9488;
      color: white;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .toolbar span { font-size: 13px; font-weight: bold; }
    .toolbar button {
      background: white;
      color: #0d9488;
      border: none;
      padding: 7px 18px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: bold;
      cursor: pointer;
    }
    .toolbar button:hover { background: #f0fdfa; }
    .toolbar a {
      color: rgba(255,255,255,.8);
      font-size: 12px;
      text-decoration: none;
    }
    .toolbar a:hover { color: white; }

    /* ── Hoja ── */
    .page {
      width: 210mm;
      min-height: 297mm;
      background: white;
      margin: 16px auto;
      padding: 14mm 14mm 12mm;
      box-shadow: 0 2px 12px rgba(0,0,0,.12);
    }

    /* ── Cabecera del documento ── */
    .doc-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #0d9488;
      padding-bottom: 8px;
      margin-bottom: 10px;
    }
    .doc-header .org { font-size: 13px; font-weight: bold; color: #0d9488; }
    .doc-header .sub { font-size: 10px; color: #555; margin-top: 2px; }
    .doc-header .meta { text-align: right; font-size: 10px; color: #555; }
    .doc-header .meta strong { display: block; font-size: 13px; color: #111; }

    /* ── Totales ── */
    .totales {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      margin-bottom: 12px;
    }
    .total-card {
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 7px 10px;
    }
    .total-card .label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
    .total-card .value { font-size: 16px; font-weight: bold; color: #dc2626; margin-top: 2px; }
    .total-card .value.dark { color: #111; }
    .total-card .note  { font-size: 9px; color: #9ca3af; margin-top: 1px; }

    /* ── Resumen por zona ── */
    .zona-summary {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 8px 12px;
      margin-bottom: 14px;
    }
    .zona-summary table { width: 100%; border-collapse: collapse; }
    .zona-summary th { font-size: 9px; color: #6b7280; text-transform: uppercase; text-align: left; padding: 2px 6px; }
    .zona-summary td { font-size: 10px; padding: 3px 6px; border-top: 1px solid #e5e7eb; }
    .zona-summary tr:first-child td { border-top: none; }
    .zona-summary .amt { text-align: right; font-weight: bold; color: #dc2626; }

    /* ── Tabla de deudores ── */
    .zona-block { margin-bottom: 14px; page-break-inside: avoid; }
    .zona-title {
      background: #0f766e;
      color: white;
      font-size: 10px;
      font-weight: bold;
      padding: 4px 8px;
      border-radius: 4px 4px 0 0;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    table.deudores {
      width: 100%;
      border-collapse: collapse;
      font-size: 10px;
    }
    table.deudores thead th {
      background: #f3f4f6;
      text-align: left;
      padding: 5px 6px;
      font-size: 9px;
      color: #374151;
      text-transform: uppercase;
      letter-spacing: .03em;
      border-bottom: 1px solid #d1d5db;
    }
    table.deudores tbody td {
      padding: 6px 6px;
      border-bottom: 1px solid #f3f4f6;
      vertical-align: top;
    }
    table.deudores tbody tr:last-child td { border-bottom: 1px solid #d1d5db; }
    table.deudores tbody tr:nth-child(even) td { background: #fafafa; }
    .mono { font-family: 'Courier New', monospace; }
    .monto-cell { text-align: right; font-weight: bold; color: #dc2626; white-space: nowrap; }
    .obs-cell { border-bottom: 1px solid #9ca3af !important; min-width: 80px; }

    /* subtotal de zona */
    .zona-foot td {
      background: #f0fdfa !important;
      font-weight: bold;
      font-size: 10px;
      color: #0f766e;
      padding: 4px 6px;
      border-top: 1px solid #0d9488 !important;
    }
    .zona-foot .monto-cell { color: #0f766e; }

    /* ── Total general ── */
    .total-general {
      border: 2px solid #dc2626;
      border-radius: 6px;
      padding: 8px 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 12px;
    }
    .total-general .left { font-size: 11px; font-weight: bold; color: #111; }
    .total-general .right { font-size: 18px; font-weight: bold; color: #dc2626; }

    /* ── Firma ── */
    .firmas {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-top: 24px;
      padding-top: 10px;
    }
    .firma-box { text-align: center; }
    .firma-box .linea {
      border-top: 1px solid #374151;
      margin-bottom: 4px;
      margin-top: 28px;
    }
    .firma-box .label { font-size: 9px; color: #6b7280; text-transform: uppercase; }

    /* ── Pie de página ── */
    .doc-footer {
      border-top: 1px solid #e5e7eb;
      margin-top: 14px;
      padding-top: 6px;
      font-size: 9px;
      color: #9ca3af;
      display: flex;
      justify-content: space-between;
    }

    /* ── Ajustes de impresión ── */
    @media print {
      body { background: white; }
      .toolbar { display: none; }
      .page { margin: 0; padding: 10mm 12mm 10mm; box-shadow: none; width: 100%; }
      .zona-block { page-break-inside: avoid; }
    }
  </style>
</head>
<body>

<!-- Barra de herramientas (solo en pantalla) -->
<div class="toolbar">
  <div style="display:flex;align-items:center;gap:16px">
    <span>Vista previa – Reporte de Deudores</span>
    <a href="deudores.php?periodo_id=<?= $periodoId ?>&zona=<?= urlencode($zona) ?>">← Volver</a>
  </div>
  <button onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
</div>

<!-- Hoja -->
<div class="page">

  <!-- Cabecera -->
  <div class="doc-header">
    <div>
      <div class="org"><?= APP_NAME ?></div>
      <div class="sub">Junta Administradora de Agua Potable</div>
    </div>
    <div class="meta">
      <strong>REPORTE DE DEUDORES</strong>
      <?php if ($periodoActual): ?>
        <?= e($periodoActual['nombre']) ?><br>
      <?php endif; ?>
      Fecha: <?= date('d/m/Y') ?><br>
      Hora:  <?= date('H:i') ?>
    </div>
  </div>

  <?php if ($periodoActual && !empty($deudores)): ?>

  <!-- Totales -->
  <div class="totales">
    <div class="total-card">
      <div class="label">Total deudores</div>
      <div class="value"><?= count($deudores) ?></div>
      <div class="note">abonados sin pago</div>
    </div>
    <div class="total-card">
      <div class="label">Deuda total</div>
      <div class="value">S/ <?= number_format($totalDeuda, 2) ?></div>
      <div class="note">por cobrar</div>
    </div>
    <div class="total-card">
      <div class="label">Monto por abonado</div>
      <div class="value dark">S/ <?= number_format((float)$periodoActual['monto_total'], 2) ?></div>
      <div class="note">tarifa semestral</div>
    </div>
    <div class="total-card">
      <div class="label">Zonas</div>
      <div class="value dark"><?= count($resumenZona) ?></div>
      <div class="note"><?= implode(', ', array_map(fn($z) => ZONAS[$z] ?? $z, array_keys($resumenZona))) ?></div>
    </div>
  </div>

  <!-- Resumen por zona -->
  <div class="zona-summary">
    <table>
      <thead>
        <tr>
          <th>Zona</th>
          <th>Cant. deudores</th>
          <th style="text-align:right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($resumenZona as $z => $r): ?>
        <tr>
          <td><?= e(ZONAS[$z] ?? $z) ?></td>
          <td><?= $r['cantidad'] ?> abonado<?= $r['cantidad'] !== 1 ? 's' : '' ?></td>
          <td class="amt">S/ <?= number_format($r['subtotal'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Tabla por zona -->
  <?php
  $numGlobal = 0;
  foreach ($porZona as $z => $lista):
    $subtotal = count($lista) * (float)$periodoActual['monto_total'];
  ?>
  <div class="zona-block">
    <div class="zona-title"><?= e(ZONAS[$z] ?? $z) ?> — <?= count($lista) ?> deudor<?= count($lista) !== 1 ? 'es' : '' ?></div>
    <table class="deudores">
      <thead>
        <tr>
          <th style="width:24px">#</th>
          <th style="width:70px">DNI</th>
          <th>Apellidos y Nombres</th>
          <th style="width:60px">Teléfono</th>
          <th style="width:52px;text-align:right">Monto</th>
          <th style="width:120px">Observación / Pagó</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lista as $d): $numGlobal++ ?>
        <tr>
          <td class="mono" style="color:#9ca3af;font-size:9px"><?= $numGlobal ?></td>
          <td class="mono"><?= e($d['dni']) ?></td>
          <td>
            <strong><?= e($d['apellidos'] . ', ' . $d['nombres']) ?></strong>
          </td>
          <td style="color:#6b7280"><?= e($d['telefono'] ?? '—') ?></td>
          <td class="monto-cell">S/ <?= number_format((float)$periodoActual['monto_total'], 2) ?></td>
          <td class="obs-cell">&nbsp;</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="zona-foot">
          <td colspan="4" style="text-align:right">Subtotal <?= e(ZONAS[$z] ?? $z) ?>:</td>
          <td class="monto-cell">S/ <?= number_format($subtotal, 2) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endforeach; ?>

  <!-- Total general -->
  <div class="total-general">
    <div class="left">TOTAL GENERAL — <?= count($deudores) ?> abonados deudores</div>
    <div class="right">S/ <?= number_format($totalDeuda, 2) ?></div>
  </div>

  <!-- Firmas -->
  <div class="firmas">
    <div class="firma-box">
      <div class="linea"></div>
      <div class="label">Responsable de cobranza</div>
    </div>
    <div class="firma-box">
      <div class="linea"></div>
      <div class="label">Presidente JAAP</div>
    </div>
  </div>

  <?php else: ?>
    <p style="text-align:center;padding:40px;color:#16a34a;font-weight:bold">
      ✓ Todos los abonados han pagado este período.
    </p>
  <?php endif; ?>

  <!-- Pie -->
  <div class="doc-footer">
    <span><?= APP_NAME ?> — Generado el <?= date('d/m/Y \a \l\a\s H:i') ?></span>
    <span>Período: <?= $periodoActual ? e($periodoActual['nombre']) : '—' ?></span>
  </div>

</div>
</body>
</html>
