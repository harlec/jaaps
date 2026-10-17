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

$estado = $_GET['estado'] ?? '';
$pagado = $_GET['pagado'] ?? '';

$where       = ['1=1'];
$whereParams = [];
if ($estado !== '') {
    $where[]       = 'a.estado = ?';
    $whereParams[] = $estado;
}
if ($pagado === 'si') {
    $where[]       = 'a.id IN (SELECT p.abonado_id FROM pagos p WHERE p.periodo_id = ?)';
    $whereParams[] = $periodoId;
} elseif ($pagado === 'no') {
    $where[]       = 'a.id NOT IN (SELECT p.abonado_id FROM pagos p WHERE p.periodo_id = ?)';
    $whereParams[] = $periodoId;
}
$whereStr = implode(' AND ', $where);

$selectParams = array_merge([$periodoId], $whereParams);
$stmt = $pdo->prepare("
    SELECT a.id, a.codigo, a.dni, a.nombres, a.apellidos, a.zona, a.telefono,
           a.estado, a.fecha_inscripcion,
           EXISTS (SELECT 1 FROM pagos p2 WHERE p2.abonado_id = a.id AND p2.periodo_id = ?) AS pagado
    FROM abonados a
    WHERE $whereStr
    ORDER BY a.zona, a.apellidos, a.nombres
");
$stmt->execute($selectParams);
$abonados = $stmt->fetchAll();

$totalPagados   = count(array_filter($abonados, fn($a) => (bool)$a['pagado']));
$totalNoPagados = count($abonados) - $totalPagados;

$filtroEstadoLabel = $estado !== '' ? (ESTADOS_ABONADO[$estado] ?? $estado) : 'Todos';
$filtroPagadoLabel = $pagado === 'si' ? 'Pagaron' : ($pagado === 'no' ? 'No pagaron' : 'Todos');

$qsVolver = http_build_query(['periodo_id' => $periodoId, 'estado' => $estado, 'pagado' => $pagado]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Abonados – <?= $periodoActual ? e($periodoActual['nombre']) : 'Reporte' ?> | JAAP</title>
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

    /* ── Filtros aplicados ── */
    .filtros {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 6px 12px;
      margin-bottom: 12px;
      font-size: 10px;
      color: #374151;
      display: flex;
      gap: 18px;
      flex-wrap: wrap;
    }
    .filtros strong { color: #111; }

    /* ── Totales ── */
    .totales {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      margin-bottom: 14px;
    }
    .total-card {
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 7px 10px;
    }
    .total-card .label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
    .total-card .value { font-size: 16px; font-weight: bold; color: #111; margin-top: 2px; }
    .total-card .value.green { color: #059669; }
    .total-card .value.red   { color: #dc2626; }

    /* ── Tabla de abonados ── */
    table.abonados {
      width: 100%;
      border-collapse: collapse;
      font-size: 10px;
    }
    table.abonados thead th {
      background: #f3f4f6;
      text-align: left;
      padding: 5px 6px;
      font-size: 9px;
      color: #374151;
      text-transform: uppercase;
      letter-spacing: .03em;
      border-bottom: 1px solid #d1d5db;
    }
    table.abonados tbody td {
      padding: 6px 6px;
      border-bottom: 1px solid #f3f4f6;
      vertical-align: top;
    }
    table.abonados tbody tr:last-child td { border-bottom: 1px solid #d1d5db; }
    table.abonados tbody tr:nth-child(even) td { background: #fafafa; }
    .mono { font-family: 'Courier New', monospace; }
    .badge {
      display: inline-block;
      padding: 1px 7px;
      border-radius: 999px;
      font-size: 9px;
      font-weight: bold;
    }
    .badge.si   { background: #d1fae5; color: #059669; }
    .badge.no   { background: #fee2e2; color: #dc2626; }
    .badge.activo     { background: #dcfce7; color: #15803d; }
    .badge.inactivo   { background: #f3f4f6; color: #6b7280; }
    .badge.cortado { background: #fee2e2; color: #dc2626; }
    .badge.suspension_temporal { background: #fef3c7; color: #b45309; }

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
      table.abonados { page-break-inside: auto; }
      table.abonados tr { page-break-inside: avoid; }
    }
  </style>
</head>
<body>

<!-- Barra de herramientas (solo en pantalla) -->
<div class="toolbar">
  <div style="display:flex;align-items:center;gap:16px">
    <span>Vista previa – Reporte de Abonados</span>
    <a href="abonados.php?<?= $qsVolver ?>">← Volver</a>
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
      <strong>REPORTE DE ABONADOS</strong>
      <?php if ($periodoActual): ?>
        <?= e($periodoActual['nombre']) ?><br>
      <?php endif; ?>
      Fecha: <?= date('d/m/Y') ?><br>
      Hora:  <?= date('H:i') ?>
    </div>
  </div>

  <!-- Filtros aplicados -->
  <div class="filtros">
    <span>Estado: <strong><?= e($filtroEstadoLabel) ?></strong></span>
    <span>Pago: <strong><?= e($filtroPagadoLabel) ?></strong></span>
    <span>Período: <strong><?= $periodoActual ? e($periodoActual['nombre']) : '—' ?></strong></span>
  </div>

  <!-- Totales -->
  <div class="totales">
    <div class="total-card">
      <div class="label">Total abonados</div>
      <div class="value"><?= count($abonados) ?></div>
    </div>
    <div class="total-card">
      <div class="label">Pagaron</div>
      <div class="value green"><?= $totalPagados ?></div>
    </div>
    <div class="total-card">
      <div class="label">No pagaron</div>
      <div class="value red"><?= $totalNoPagados ?></div>
    </div>
  </div>

  <!-- Tabla -->
  <table class="abonados">
    <thead>
      <tr>
        <th style="width:24px">#</th>
        <th style="width:55px">Código</th>
        <th style="width:65px">DNI</th>
        <th>Apellidos y Nombres</th>
        <th style="width:70px">Zona</th>
        <th style="width:55px">Estado</th>
        <th style="width:45px">Pagó</th>
        <th style="width:60px">Teléfono</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($abonados)): ?>
        <tr><td colspan="8" style="text-align:center;padding:20px;color:#6b7280">No se encontraron abonados con estos filtros.</td></tr>
      <?php endif; ?>
      <?php foreach ($abonados as $i => $a): ?>
      <tr>
        <td class="mono" style="color:#9ca3af;font-size:9px"><?= $i + 1 ?></td>
        <td class="mono"><?= e($a['codigo']) ?></td>
        <td class="mono"><?= e($a['dni']) ?></td>
        <td><strong><?= e($a['apellidos'] . ', ' . $a['nombres']) ?></strong></td>
        <td><?= e(ZONAS[$a['zona']] ?? $a['zona']) ?></td>
        <td><span class="badge <?= e($a['estado']) ?>"><?= e(ESTADOS_ABONADO[$a['estado']] ?? $a['estado']) ?></span></td>
        <td><span class="badge <?= (bool)$a['pagado'] ? 'si' : 'no' ?>"><?= (bool)$a['pagado'] ? 'Sí' : 'No' ?></span></td>
        <td style="color:#6b7280"><?= e($a['telefono'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Pie -->
  <div class="doc-footer">
    <span><?= APP_NAME ?> — Generado el <?= date('d/m/Y \a \l\a\s H:i') ?></span>
    <span>Período: <?= $periodoActual ? e($periodoActual['nombre']) : '—' ?></span>
  </div>

</div>
</body>
</html>
