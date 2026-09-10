<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Balance General';
$activePage = 'balance';
$pdo        = getDB();

// Rango de fechas — por defecto, el año en curso
$fechaDesde = trim($_GET['fecha_desde'] ?? '');
$fechaHasta = trim($_GET['fecha_hasta'] ?? '');
if ($fechaDesde === '' || !strtotime($fechaDesde)) $fechaDesde = date('Y') . '-01-01';
if ($fechaHasta === '' || !strtotime($fechaHasta)) $fechaHasta = date('Y-m-d');

// ── Ingresos por cuotas de abonados (tabla pagos) ───────────────────────
$stmtPagos = $pdo->prepare("
    SELECT c.nombre AS concepto, COALESCE(SUM(p.monto_total),0) AS total
    FROM pagos p
    JOIN conceptos c ON c.id = p.concepto_id
    WHERE p.fecha_pago BETWEEN ? AND ?
    GROUP BY c.id, c.nombre
    ORDER BY total DESC
");
$stmtPagos->execute([$fechaDesde, $fechaHasta]);
$ingresosPagos      = $stmtPagos->fetchAll();
$totalIngresosPagos = array_sum(array_column($ingresosPagos, 'total'));

// ── Otros ingresos (alquiler de local, caja anterior, donaciones, etc.) ─
$stmtOtros = $pdo->prepare("
    SELECT ic.nombre AS concepto, COALESCE(SUM(i.monto),0) AS total
    FROM ingresos_otros i
    JOIN ingreso_conceptos ic ON ic.id = i.concepto_id
    WHERE i.fecha_ingreso BETWEEN ? AND ?
    GROUP BY ic.id, ic.nombre
    ORDER BY total DESC
");
$stmtOtros->execute([$fechaDesde, $fechaHasta]);
$ingresosOtros      = $stmtOtros->fetchAll();
$totalIngresosOtros = array_sum(array_column($ingresosOtros, 'total'));

$totalIngresos = $totalIngresosPagos + $totalIngresosOtros;

// ── Gastos ───────────────────────────────────────────────────────────────
$stmtGastos = $pdo->prepare("
    SELECT gc.nombre AS concepto, COALESCE(SUM(g.monto),0) AS total
    FROM gastos g
    JOIN gasto_conceptos gc ON gc.id = g.concepto_id
    WHERE g.fecha_gasto BETWEEN ? AND ?
    GROUP BY gc.id, gc.nombre
    ORDER BY total DESC
");
$stmtGastos->execute([$fechaDesde, $fechaHasta]);
$gastosPorConcepto = $stmtGastos->fetchAll();
$totalGastos       = array_sum(array_column($gastosPorConcepto, 'total'));

$balance = $totalIngresos - $totalGastos;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Balance General</h1>
        <p class="text-sm text-gray-400">Ingresos y egresos del período seleccionado</p>
      </div>
      <button onclick="window.print()" class="print:hidden inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
        </svg>
        Imprimir / Guardar PDF
      </button>
    </div>

    <!-- Filtro de fechas -->
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4 print:hidden">
      <div class="flex flex-col sm:flex-row gap-3 items-end">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
          <input type="date" name="fecha_desde" value="<?= e($fechaDesde) ?>"
                 class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
          <input type="date" name="fecha_hasta" value="<?= e($fechaHasta) ?>"
                 class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        </div>
        <button type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
          Filtrar
        </button>
      </div>
    </form>

    <!-- Encabezado imprimible -->
    <div class="hidden print:block">
      <p class="text-base font-bold text-gray-800"><?= APP_NAME ?> — Balance General</p>
      <p class="text-xs text-gray-500">Período: <?= date('d/m/Y', strtotime($fechaDesde)) ?> — <?= date('d/m/Y', strtotime($fechaHasta)) ?> &nbsp;·&nbsp; Generado el <?= date('d/m/Y H:i') ?></p>
    </div>

    <!-- Tarjetas resumen -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">Total ingresos</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">S/ <?= number_format($totalIngresos, 2) ?></p>
        <p class="text-xs text-gray-400 mt-0.5">Cuotas + otros ingresos</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">Total gastos</p>
        <p class="text-2xl font-bold text-red-600 mt-1">S/ <?= number_format($totalGastos, 2) ?></p>
        <p class="text-xs text-gray-400 mt-0.5">Egresos del período</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border-2 <?= $balance >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' ?> px-5 py-4">
        <p class="text-xs font-medium <?= $balance >= 0 ? 'text-emerald-700' : 'text-red-700' ?>">Balance</p>
        <p class="text-2xl font-bold <?= $balance >= 0 ? 'text-emerald-700' : 'text-red-700' ?> mt-1">S/ <?= number_format($balance, 2) ?></p>
        <p class="text-xs <?= $balance >= 0 ? 'text-emerald-600' : 'text-red-600' ?> mt-0.5"><?= $balance >= 0 ? 'Superávit' : 'Déficit' ?></p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

      <!-- Ingresos -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-700">Ingresos</h2>
          <span class="text-sm font-bold text-emerald-600">S/ <?= number_format($totalIngresos, 2) ?></span>
        </div>
        <div class="divide-y divide-gray-50">
          <div class="px-5 py-2 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">Cuotas de abonados</div>
          <?php if (empty($ingresosPagos)): ?>
            <div class="px-5 py-3 text-sm text-gray-400">Sin pagos en este período.</div>
          <?php endif; ?>
          <?php foreach ($ingresosPagos as $row): ?>
            <div class="px-5 py-2.5 flex items-center justify-between text-sm">
              <span class="text-gray-600"><?= e($row['concepto']) ?></span>
              <span class="font-medium text-gray-800">S/ <?= number_format((float)$row['total'], 2) ?></span>
            </div>
          <?php endforeach; ?>
          <div class="px-5 py-2 flex items-center justify-between text-xs font-semibold text-gray-500">
            <span>Subtotal cuotas</span>
            <span>S/ <?= number_format($totalIngresosPagos, 2) ?></span>
          </div>

          <div class="px-5 py-2 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">Otros ingresos</div>
          <?php if (empty($ingresosOtros)): ?>
            <div class="px-5 py-3 text-sm text-gray-400">Sin otros ingresos en este período.</div>
          <?php endif; ?>
          <?php foreach ($ingresosOtros as $row): ?>
            <div class="px-5 py-2.5 flex items-center justify-between text-sm">
              <span class="text-gray-600"><?= e($row['concepto']) ?></span>
              <span class="font-medium text-gray-800">S/ <?= number_format((float)$row['total'], 2) ?></span>
            </div>
          <?php endforeach; ?>
          <div class="px-5 py-2 flex items-center justify-between text-xs font-semibold text-gray-500">
            <span>Subtotal otros ingresos</span>
            <span>S/ <?= number_format($totalIngresosOtros, 2) ?></span>
          </div>
        </div>
      </div>

      <!-- Gastos -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-700">Gastos</h2>
          <span class="text-sm font-bold text-red-600">S/ <?= number_format($totalGastos, 2) ?></span>
        </div>
        <div class="divide-y divide-gray-50">
          <?php if (empty($gastosPorConcepto)): ?>
            <div class="px-5 py-3 text-sm text-gray-400">Sin gastos en este período.</div>
          <?php endif; ?>
          <?php foreach ($gastosPorConcepto as $row): ?>
            <div class="px-5 py-2.5 flex items-center justify-between text-sm">
              <span class="text-gray-600"><?= e($row['concepto']) ?></span>
              <span class="font-medium text-gray-800">S/ <?= number_format((float)$row['total'], 2) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

  </main>
</div>

<style>
@media print {
  aside, header, .print\:hidden { display: none !important; }
  main { padding: 0 !important; }
  body { background: white !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
