<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Reporte de Deudores';
$activePage = 'reportes';
$pdo        = getDB();

// Períodos disponibles para filtrar
$periodos = $pdo->query(
    "SELECT id, nombre, anio, semestre, monto_total
     FROM periodos_cobro
     ORDER BY anio DESC, semestre"
)->fetchAll();

// Período seleccionado — por defecto el semestre 1 2026
$periodoId = (int)($_GET['periodo_id'] ?? 0);
if ($periodoId === 0) {
    foreach ($periodos as $p) {
        if ((int)$p['anio'] === 2026 && $p['semestre'] === '1') {
            $periodoId = (int)$p['id'];
            break;
        }
    }
    if ($periodoId === 0 && !empty($periodos)) {
        $periodoId = (int)$periodos[0]['id'];
    }
}

$periodoActual = null;
foreach ($periodos as $p) {
    if ((int)$p['id'] === $periodoId) { $periodoActual = $p; break; }
}

$zona = $_GET['zona'] ?? '';

// Concepto tarifa mensual para el botón de pago rápido
$conceptoTarifa = $pdo->query(
    "SELECT id FROM conceptos WHERE tipo = 'tarifa_mensual' AND activo = 1 LIMIT 1"
)->fetch() ?: null;

// Abonados activos que NO tienen pago en el período seleccionado
$whereZona = $zona !== '' ? "AND a.zona = ?" : '';
$params    = $periodoId ? [$periodoId] : [0];
if ($zona !== '') $params[] = $zona;

$deudores = [];
$totalDeuda = 0.0;
if ($periodoActual) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.codigo, a.dni, a.nombres, a.apellidos, a.zona, a.telefono
        FROM abonados a
        WHERE a.estado = 'activo'
          AND a.id NOT IN (
              SELECT p.abonado_id FROM pagos p WHERE p.periodo_id = ?
          )
          $whereZona
        ORDER BY a.zona, a.apellidos, a.nombres
    ");
    $stmt->execute($params);
    $deudores   = $stmt->fetchAll();
    $totalDeuda = count($deudores) * (float)$periodoActual['monto_total'];
}

// Resumen por zona
$resumenZona = [];
foreach ($deudores as $d) {
    $z = $d['zona'];
    $resumenZona[$z] = ($resumenZona[$z] ?? 0) + 1;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Reporte de Deudores</h1>
        <p class="text-sm text-gray-400">Abonados activos sin pago registrado en el período seleccionado</p>
      </div>
      <a href="<?= APP_URL ?>/reportes/deudores_print.php?periodo_id=<?= $periodoId ?>&zona=<?= urlencode($zona) ?>"
         target="_blank"
         class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
        </svg>
        Generar PDF / Imprimir
      </a>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4 print:hidden">
      <div class="flex flex-col sm:flex-row gap-3">
        <select name="periodo_id"
                class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <?php foreach ($periodos as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (int)$p['id'] === $periodoId ? 'selected' : '' ?>>
              <?= e($p['nombre']) ?> – S/ <?= number_format((float)$p['monto_total'], 2) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select name="zona"
                class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <option value="">Todas las zonas</option>
          <?php foreach (ZONAS as $k => $v): ?>
            <option value="<?= e($k) ?>" <?= $zona === $k ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
          Filtrar
        </button>
      </div>
    </form>

    <?php if ($periodoActual): ?>

    <!-- Tarjetas resumen -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">Total deudores</p>
        <p class="text-2xl font-bold text-red-600 mt-1"><?= count($deudores) ?></p>
        <p class="text-xs text-gray-400 mt-0.5">abonados sin pago</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">Deuda total</p>
        <p class="text-2xl font-bold text-red-600 mt-1">S/ <?= number_format($totalDeuda, 2) ?></p>
        <p class="text-xs text-gray-400 mt-0.5">por cobrar</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">Monto por abonado</p>
        <p class="text-2xl font-bold text-gray-700 mt-1">S/ <?= number_format((float)$periodoActual['monto_total'], 2) ?></p>
        <p class="text-xs text-gray-400 mt-0.5"><?= e($periodoActual['nombre']) ?></p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">Zonas afectadas</p>
        <p class="text-2xl font-bold text-gray-700 mt-1"><?= count($resumenZona) ?></p>
        <p class="text-xs text-gray-400 mt-0.5">
          <?= implode(', ', array_map(fn($z) => ZONAS[$z] ?? $z, array_keys($resumenZona))) ?>
        </p>
      </div>
    </div>

    <!-- Resumen por zona -->
    <?php if (count($resumenZona) > 1): ?>
    <div class="flex flex-wrap gap-3 print:hidden">
      <?php foreach ($resumenZona as $z => $cnt): ?>
        <div class="flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-100 rounded-xl text-sm">
          <span class="font-semibold text-red-700"><?= e(ZONAS[$z] ?? $z) ?></span>
          <span class="text-red-500"><?= $cnt ?> deudor<?= $cnt !== 1 ? 'es' : '' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Encabezado de tabla imprimible -->
      <div class="hidden print:block px-6 py-4 border-b border-gray-200">
        <p class="text-base font-bold text-gray-800">Reporte de Deudores – <?= e($periodoActual['nombre']) ?></p>
        <p class="text-xs text-gray-400">Generado el <?= date('d/m/Y H:i') ?> | Total: <?= count($deudores) ?> abonados | Deuda: S/ <?= number_format($totalDeuda, 2) ?></p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
            <tr>
              <th class="px-5 py-3 text-left">#</th>
              <th class="px-5 py-3 text-left">Código</th>
              <th class="px-5 py-3 text-left">DNI</th>
              <th class="px-5 py-3 text-left">Abonado</th>
              <th class="px-5 py-3 text-left">Zona</th>
              <th class="px-5 py-3 text-right">Monto</th>
              <th class="px-5 py-3 text-center print:hidden">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50" id="tablaDeudores">
            <?php if (empty($deudores)): ?>
              <tr>
                <td colspan="7" class="px-5 py-12 text-center text-green-600 font-medium">
                  ✓ Todos los abonados han pagado este período.
                </td>
              </tr>
            <?php endif; ?>
            <?php foreach ($deudores as $i => $d): ?>
            <tr id="deudor-<?= $d['id'] ?>" class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-gray-400 text-xs"><?= $i + 1 ?></td>
              <td class="px-5 py-3 font-mono text-xs text-gray-500"><?= e($d['codigo']) ?></td>
              <td class="px-5 py-3 font-mono text-gray-700"><?= e($d['dni']) ?></td>
              <td class="px-5 py-3">
                <span class="font-medium text-gray-800"><?= e($d['apellidos'] . ', ' . $d['nombres']) ?></span>
                <?php if ($d['telefono']): ?>
                  <span class="block text-xs text-gray-400"><?= e($d['telefono']) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3">
                <span class="capitalize text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                  <?= e(ZONAS[$d['zona']] ?? $d['zona']) ?>
                </span>
              </td>
              <td class="px-5 py-3 text-right font-semibold text-red-600">
                S/ <?= number_format((float)$periodoActual['monto_total'], 2) ?>
              </td>
              <td class="px-5 py-3 text-center print:hidden">
                <?php if ($conceptoTarifa): ?>
                <button type="button"
                        data-abonado="<?= $d['id'] ?>"
                        data-concepto="<?= (int)$conceptoTarifa['id'] ?>"
                        data-periodo="<?= $periodoId ?>"
                        data-monto="<?= (float)$periodoActual['monto_total'] ?>"
                        onclick="pagarDeudor(this)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                  </svg>
                  Registrar pago
                </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <?php if (!empty($deudores)): ?>
          <tfoot class="bg-gray-50 border-t border-gray-200">
            <tr>
              <td colspan="5" class="px-5 py-3 text-sm font-semibold text-gray-700">
                Total (<?= count($deudores) ?> deudores)
              </td>
              <td class="px-5 py-3 text-right font-bold text-red-600">
                S/ <?= number_format($totalDeuda, 2) ?>
              </td>
              <td class="print:hidden"></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <?php else: ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-12 text-center text-gray-400">
        No hay períodos de cobro configurados.
      </div>
    <?php endif; ?>

  </main>
</div>

<script>
async function pagarDeudor(btn) {
  btn.disabled = true;
  btn.textContent = 'Registrando…';

  const fd = new FormData();
  fd.append('abonado_id', btn.dataset.abonado);
  fd.append('concepto_id', btn.dataset.concepto);
  fd.append('periodo_id', btn.dataset.periodo);
  fd.append('monto', btn.dataset.monto);

  try {
    const r    = await fetch('<?= APP_URL ?>/api/registrar_pago_rapido.php', { method: 'POST', body: fd });
    const data = await r.json();

    if (data.success) {
      showToast('Pago registrado: ' + data.numero_recibo);
      const row = document.getElementById('deudor-' + btn.dataset.abonado);
      row.style.transition = 'opacity .4s';
      row.style.opacity    = '0';
      setTimeout(() => {
        row.remove();
        actualizarContadores();
      }, 400);
    } else {
      showToast(data.error ?? 'No se pudo registrar el pago', 'error');
      btn.disabled = false;
      btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg> Registrar pago';
    }
  } catch (e) {
    showToast('Error de conexión', 'error');
    btn.disabled = false;
    btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg> Registrar pago';
  }
}

function actualizarContadores() {
  const filas = document.querySelectorAll('#tablaDeudores tr[id^="deudor-"]');
  const monto = <?= (float)($periodoActual['monto_total'] ?? 0) ?>;
  const total = filas.length * monto;
  document.querySelectorAll('.text-red-600.text-2xl')[0]?.textContent && (
    document.querySelectorAll('.text-red-600.text-2xl')[0].textContent = filas.length
  );
  document.querySelectorAll('.text-red-600.text-2xl')[1] && (
    document.querySelectorAll('.text-red-600.text-2xl')[1].textContent = 'S/ ' + total.toFixed(2)
  );
}
</script>

<style>
@media print {
  aside, header, .print\:hidden { display: none !important; }
  main { padding: 0 !important; }
  body { background: white !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
