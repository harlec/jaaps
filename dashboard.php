<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$pdo = getDB();

// ── KPIs ────────────────────────────────────────────────────────────────────
$totalAbonados   = $pdo->query("SELECT COUNT(*) FROM abonados WHERE estado = 'activo'")->fetchColumn();
$totalInactivos  = $pdo->query("SELECT COUNT(*) FROM abonados WHERE estado != 'activo'")->fetchColumn();
$totalRecaudado  = $pdo->query("SELECT COALESCE(SUM(monto_total),0) FROM pagos WHERE YEAR(fecha_pago)=YEAR(CURDATE())")->fetchColumn();
$pagosPendientes = $pdo->query("
    SELECT COUNT(*) FROM abonados a
    WHERE a.estado = 'activo'
      AND NOT EXISTS (
          SELECT 1 FROM pagos p
          JOIN periodos_cobro pc ON pc.id = p.periodo_id
          WHERE p.abonado_id = a.id AND pc.estado = 'activo'
      )
")->fetchColumn();

// ── Pagos por zona ───────────────────────────────────────────────────────────
$byZona = $pdo->query("
    SELECT COALESCE(a.zona,'sin zona') AS zona, COUNT(*) AS total
    FROM abonados a WHERE a.estado = 'activo'
    GROUP BY a.zona
")->fetchAll();

// ── Últimos pagos ────────────────────────────────────────────────────────────
$ultimosPagos = $pdo->query("
    SELECT p.*, CONCAT(a.nombres,' ',a.apellidos) AS abonado, a.codigo, c.nombre AS concepto
    FROM pagos p
    JOIN abonados a  ON a.id = p.abonado_id
    JOIN conceptos c ON c.id = p.concepto_id
    ORDER BY p.created_at DESC LIMIT 8
")->fetchAll();

// ── Abonados por mes (inscripciones) – gráfico ───────────────────────────────
$porMes = $pdo->query("
    SELECT DATE_FORMAT(fecha_inscripcion,'%b') AS mes,
           MONTH(fecha_inscripcion) AS num,
           COUNT(*)                AS total
    FROM abonados
    WHERE YEAR(fecha_inscripcion) = YEAR(CURDATE())
    GROUP BY mes, num ORDER BY num
")->fetchAll();
$mesesLabels = array_column($porMes, 'mes');
$mesesData   = array_column($porMes, 'total');

// ── Recaudación mensual ──────────────────────────────────────────────────────
$recMes = $pdo->query("
    SELECT DATE_FORMAT(fecha_pago,'%b') AS mes,
           MONTH(fecha_pago)            AS num,
           SUM(monto_total)             AS total
    FROM pagos
    WHERE YEAR(fecha_pago) = YEAR(CURDATE())
    GROUP BY mes, num ORDER BY num
")->fetchAll();
$recLabels = array_column($recMes, 'mes');
$recData   = array_column($recMes, 'total');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- MAIN -->
<div class="flex flex-col flex-1 overflow-hidden">

  <?php require_once __DIR__ . '/includes/topbar.php'; ?>

  <!-- Contenido scrollable -->
  <main class="flex-1 overflow-y-auto p-6 space-y-6">

    <!-- Flash messages -->
    <?php $msg = flash('success'); if ($msg): ?>
      <div class="px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

      <!-- Total abonados activos -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-start justify-between">
          <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full">Activo</span>
        </div>
        <p class="text-3xl font-bold text-gray-800 mt-3"><?= number_format((int)$totalAbonados) ?></p>
        <p class="text-xs text-gray-400 mt-1">Total Abonados Activos</p>
      </div>

      <!-- Pendientes de pago -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-start justify-between">
          <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full">Alert</span>
        </div>
        <p class="text-3xl font-bold text-gray-800 mt-3"><?= number_format((int)$pagosPendientes) ?></p>
        <p class="text-xs text-gray-400 mt-1">Sin pago en período activo</p>
      </div>

      <!-- Recaudado este año -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-start justify-between">
          <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Activo</span>
        </div>
        <p class="text-3xl font-bold text-gray-800 mt-3">S/ <?= number_format((float)$totalRecaudado, 2) ?></p>
        <p class="text-xs text-gray-400 mt-1">Recaudado <?= date('Y') ?></p>
      </div>

      <!-- Inactivos/Suspendidos -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-start justify-between">
          <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
          </div>
          <span class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">
            <?= number_format(($totalAbonados + $totalInactivos) > 0 ? ($totalInactivos / ($totalAbonados + $totalInactivos)) * 100 : 0) ?>%
          </span>
        </div>
        <p class="text-3xl font-bold text-gray-800 mt-3"><?= number_format((int)$totalInactivos) ?></p>
        <p class="text-xs text-gray-400 mt-1">Inactivos / Suspendidos</p>
      </div>
    </div>

    <!-- Gráficos + Zonas -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

      <!-- Gráfico – Recaudación mensual -->
      <div class="xl:col-span-2 bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-semibold text-gray-700">Recaudación Mensual <?= date('Y') ?></h3>
            <p class="text-xs text-gray-400">Pagos registrados mes a mes (S/.)</p>
          </div>
          <div class="flex items-center gap-3 text-xs text-gray-400">
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-teal-500 inline-block"></span>Recaudación</span>
          </div>
        </div>
        <canvas id="chartRecaudacion" height="90"></canvas>
      </div>

      <!-- Abonados por zona -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Abonados por Zona</h3>
        <?php
        $zonasTotales = array_sum(array_column($byZona, 'total')) ?: 1;
        $zonaColors   = ['bg-teal-400','bg-orange-400','bg-purple-400','bg-blue-400'];
        $zi = 0;
        foreach ($byZona as $z):
          $pct = round($z['total'] / $zonasTotales * 100);
        ?>
        <div class="mb-4">
          <div class="flex justify-between text-xs text-gray-600 mb-1">
            <span class="capitalize font-medium"><?= e(str_replace('_',' ', $z['zona'])) ?></span>
            <span class="font-semibold text-gray-700"><?= $pct ?>%</span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2">
            <div class="<?= $zonaColors[$zi % count($zonaColors)] ?> h-2 rounded-full transition-all"
                 style="width:<?= $pct ?>%"></div>
          </div>
          <p class="text-[10px] text-gray-400 mt-0.5"><?= $z['total'] ?> abonados</p>
        </div>
        <?php $zi++; endforeach; ?>
        <?php if (empty($byZona)): ?>
          <p class="text-xs text-gray-400 text-center py-6">Sin datos de zonas todavía.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Últimos pagos -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Últimos Pagos Registrados</h3>
        <a href="<?= APP_URL ?>/pagos/index.php"
           class="text-xs font-medium text-brand-600 hover:underline">Ver todos</a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
            <tr>
              <th class="px-5 py-3 text-left">Recibo</th>
              <th class="px-5 py-3 text-left">Abonado</th>
              <th class="px-5 py-3 text-left">Concepto</th>
              <th class="px-5 py-3 text-left">Fecha</th>
              <th class="px-5 py-3 text-right">Monto</th>
              <th class="px-5 py-3 text-left">Método</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($ultimosPagos as $p): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 font-mono text-xs text-gray-500"><?= e($p['numero_recibo'] ?? '—') ?></td>
              <td class="px-5 py-3">
                <div class="font-medium text-gray-800"><?= e($p['abonado']) ?></div>
                <div class="text-xs text-gray-400"><?= e($p['codigo']) ?></div>
              </td>
              <td class="px-5 py-3 text-gray-600"><?= e($p['concepto']) ?></td>
              <td class="px-5 py-3 text-gray-500"><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
              <td class="px-5 py-3 text-right font-semibold text-gray-800">S/ <?= number_format((float)$p['monto_total'], 2) ?></td>
              <td class="px-5 py-3">
                <span class="px-2 py-0.5 text-xs rounded-full
                  <?= $p['metodo_pago'] === 'efectivo' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' ?>">
                  <?= METODOS_PAGO[$p['metodo_pago']] ?? e($p['metodo_pago']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ultimosPagos)): ?>
              <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400 text-sm">No hay pagos registrados todavía.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<script>
// Gráfico Recaudación
const ctx = document.getElementById('chartRecaudacion').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($recLabels ?: ['Ene','Feb','Mar','Apr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']) ?>,
    datasets: [{
      label: 'S/.',
      data: <?= json_encode($recData ?: []) ?>,
      borderColor: '#0d9488',
      backgroundColor: 'rgba(13,148,136,0.08)',
      borderWidth: 2,
      pointBackgroundColor: '#0d9488',
      pointRadius: 4,
      tension: 0.4,
      fill: true,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 } } },
      x: { grid: { display: false }, ticks: { font: { size: 11 } } }
    }
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
