<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Generar Recibos';
$activePage = 'recibos';
$pdo        = getDB();

$periodos = $pdo->query(
    "SELECT * FROM periodos_cobro ORDER BY anio DESC, semestre"
)->fetchAll();

// Período por defecto: Semestre 2 – 2026 (julio–diciembre)
$defId = 0;
foreach ($periodos as $p) {
    if ((int)$p['anio'] === 2026 && $p['semestre'] === '2') { $defId = (int)$p['id']; break; }
}
if ($defId === 0 && $periodos) $defId = (int)$periodos[0]['id'];

// Datos de cada período para autocompletar el formulario desde JS
$periodosJs = [];
foreach ($periodos as $p) {
    $ini = strtotime($p['fecha_inicio']);
    $fin = strtotime($p['fecha_fin']);
    $periodosJs[$p['id']] = [
        'monto'       => (float)$p['monto_total'],
        'vencimiento' => $p['fecha_vencimiento'],
        'mes'         => MESES_ES[(int)date('n', $ini)] . ' - ' . MESES_ES[(int)date('n', $fin)] . ' ' . date('Y', $fin),
    ];
}

$cantActivos = (int)$pdo->query("SELECT COUNT(*) FROM abonados WHERE estado = 'activo'")->fetchColumn();
$cantExcl    = (int)$pdo->query("SELECT COUNT(*) FROM abonados WHERE estado != 'activo'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6 space-y-5">
    <div>
      <h1 class="text-lg font-bold text-gray-800">Generar recibos por período</h1>
      <p class="text-sm text-gray-400">
        Genera un recibo (A6, 4 por hoja A4) por cada abonado <strong>activo</strong>.
        Se omiten cortados, en suspensión temporal e inactivos
        (<?= $cantExcl ?> abonados excluidos de <?= $cantActivos + $cantExcl ?>).
      </p>
    </div>

    <form method="GET" action="<?= APP_URL ?>/recibos/imprimir.php" target="_blank"
          class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden max-w-3xl">
      <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Período</label>
          <select name="periodo_id" id="periodo"
                  class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
            <?php foreach ($periodos as $p): ?>
              <option value="<?= $p['id'] ?>" <?= (int)$p['id'] === $defId ? 'selected' : '' ?>><?= e($p['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Monto por abonado (S/)</label>
          <input name="monto" id="monto" type="number" step="0.01" min="0" required
                 class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de emisión</label>
          <input name="fecha_emision" type="date" value="<?= date('Y-m-d') ?>" required
                 class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de vencimiento de pago</label>
          <input name="fecha_vencimiento" id="vencimiento" type="date" required
                 class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 mb-1">Por concepto de (editable)</label>
          <input name="concepto" id="concepto" type="text" maxlength="200" value="Cuota de agua potable"
                 class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 mb-1">Mes de / Período (editable)</label>
          <input name="mes" id="mes" type="text" maxlength="100"
                 class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Zona</label>
          <select name="zona" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
            <option value="">Todas</option>
            <?php foreach (ZONAS as $k => $v): ?>
              <option value="<?= e($k) ?>"><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Nº inicial (opcional)</label>
          <input name="numero_inicial" type="number" min="0" placeholder="Vacío = sin número"
                 class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        </div>

        <label class="sm:col-span-2 flex items-center gap-2 text-sm text-gray-600">
          <input type="checkbox" name="solo_deudores" value="1" checked class="rounded border-gray-300">
          Omitir abonados que ya pagaron este período
        </label>
      </div>

      <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
        <button type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
          Generar recibos
        </button>
      </div>
    </form>
  </main>
</div>

<script>
  const periodos = <?= json_encode($periodosJs, JSON_UNESCAPED_UNICODE) ?>;
  const sel = document.getElementById('periodo');
  function aplicarPeriodo() {
    const p = periodos[sel.value]; if (!p) return;
    document.getElementById('monto').value       = p.monto.toFixed(2);
    document.getElementById('vencimiento').value = p.vencimiento;
    document.getElementById('mes').value         = p.mes;
  }
  sel.addEventListener('change', aplicarPeriodo);
  aplicarPeriodo();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
