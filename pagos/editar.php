<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Editar Pago';
$activePage = 'pagos';
$pdo        = getDB();
$errors     = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Pago no válido.'); redirect(APP_URL . '/pagos/index.php'); }

// Cargar pago actual
$stmt = $pdo->prepare("
    SELECT p.*, a.nombres, a.apellidos, a.codigo, a.zona, a.dni
    FROM pagos p
    JOIN abonados a ON a.id = p.abonado_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pago = $stmt->fetch();
if (!$pago) { flash('error', 'Pago no encontrado.'); redirect(APP_URL . '/pagos/index.php'); }

$conceptos = $pdo->query("SELECT * FROM conceptos WHERE activo = 1 ORDER BY tipo, nombre")->fetchAll();
$periodos  = $pdo->query("SELECT * FROM periodos_cobro ORDER BY anio DESC, semestre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conceptoId  = (int)($_POST['concepto_id'] ?? 0);
    $periodoId   = (int)($_POST['periodo_id']  ?? 0) ?: null;
    $monto       = (float)str_replace(',', '.', $_POST['monto']     ?? '0');
    $descuento   = (float)str_replace(',', '.', $_POST['descuento'] ?? '0');
    $interes     = (float)str_replace(',', '.', $_POST['interes']   ?? '0');
    $fechaPago   = trim($_POST['fecha_pago']  ?? '');
    $metodo      = trim($_POST['metodo_pago'] ?? 'efectivo');
    $referencia  = trim($_POST['referencia']  ?? '');
    $observacion = trim($_POST['observacion'] ?? '');

    $total = round($monto - $descuento + $interes, 2);

    if ($conceptoId <= 0)    $errors[] = 'Seleccione un concepto.';
    if ($monto <= 0)         $errors[] = 'El monto debe ser mayor a cero.';
    if (!array_key_exists($metodo, METODOS_PAGO)) $errors[] = 'Método de pago inválido.';
    if (!strtotime($fechaPago))  $errors[] = 'Fecha de pago inválida.';

    if (empty($errors)) {
        try {
            $upd = $pdo->prepare("
                UPDATE pagos SET
                    concepto_id  = :concepto_id,
                    periodo_id   = :periodo_id,
                    monto        = :monto,
                    descuento    = :descuento,
                    interes      = :interes,
                    monto_total  = :monto_total,
                    fecha_pago   = :fecha_pago,
                    metodo_pago  = :metodo_pago,
                    referencia   = :referencia,
                    observacion  = :observacion
                WHERE id = :id
            ");
            $upd->execute([
                ':concepto_id'  => $conceptoId,
                ':periodo_id'   => $periodoId,
                ':monto'        => $monto,
                ':descuento'    => $descuento,
                ':interes'      => $interes,
                ':monto_total'  => $total,
                ':fecha_pago'   => $fechaPago,
                ':metodo_pago'  => $metodo,
                ':referencia'   => $referencia,
                ':observacion'  => $observacion,
                ':id'           => $id,
            ]);

            flash('success', 'Pago ' . e($pago['numero_recibo']) . ' actualizado correctamente.');
            redirect(APP_URL . '/pagos/index.php');
        } catch (PDOException $ex) {
            $errors[] = 'Error al actualizar: ' . $ex->getMessage();
        }
    }
}

// Valores a mostrar en el form (POST o BD)
$v = [
    'concepto_id' => (int)($_POST['concepto_id'] ?? $pago['concepto_id']),
    'periodo_id'  => (int)($_POST['periodo_id']  ?? $pago['periodo_id']),
    'monto'       => (float)($_POST['monto']      ?? $pago['monto']),
    'descuento'   => (float)($_POST['descuento']  ?? $pago['descuento']),
    'interes'     => (float)($_POST['interes']    ?? $pago['interes']),
    'fecha_pago'  => $_POST['fecha_pago']  ?? $pago['fecha_pago'],
    'metodo_pago' => $_POST['metodo_pago'] ?? $pago['metodo_pago'],
    'referencia'  => $_POST['referencia']  ?? $pago['referencia'],
    'observacion' => $_POST['observacion'] ?? $pago['observacion'],
];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
      <a href="<?= APP_URL ?>/pagos/index.php" class="hover:text-brand-600">Pagos</a>
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
      <span class="text-gray-600 font-medium">Editar <?= e($pago['numero_recibo']) ?></span>
    </div>

    <?php if ($errors): ?>
      <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        <strong>Errores:</strong>
        <ul class="mt-1 list-disc list-inside">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST"
          x-data="{
            monto:     <?= $v['monto'] ?>,
            descuento: <?= $v['descuento'] ?>,
            interes:   <?= $v['interes'] ?>,
            get total() { return Math.max(0, parseFloat(this.monto||0) - parseFloat(this.descuento||0) + parseFloat(this.interes||0)).toFixed(2); }
          }"
          class="max-w-3xl space-y-5">
      <input type="hidden" name="id" value="<?= $id ?>">

      <!-- Abonado (solo lectura) -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Abonado</h2></div>
        <div class="p-5">
          <div class="flex items-center gap-3 p-3 rounded-xl bg-brand-50 border border-brand-100">
            <div class="w-9 h-9 rounded-lg bg-brand-600 flex items-center justify-center text-white text-sm font-bold">
              <?= e(mb_strtoupper(mb_substr($pago['nombres'], 0,1) . mb_substr($pago['apellidos'], 0,1))) ?>
            </div>
            <div>
              <p class="font-semibold text-brand-800 text-sm"><?= e($pago['apellidos'] . ', ' . $pago['nombres']) ?></p>
              <p class="text-xs text-brand-600"><?= e($pago['codigo']) ?> &nbsp;·&nbsp; DNI: <?= e($pago['dni']) ?> &nbsp;·&nbsp; <?= e(ZONAS[$pago['zona']] ?? $pago['zona']) ?></p>
            </div>
            <span class="ml-auto text-xs text-gray-400 font-mono"><?= e($pago['numero_recibo']) ?></span>
          </div>
        </div>
      </div>

      <!-- Concepto y período -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Concepto de Pago</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Concepto <span class="text-red-500">*</span></label>
            <select name="concepto_id" required
                    onchange="const c = this.selectedOptions[0]?.dataset.monto; if(c) { document.getElementById('inputMonto')._x_model.set(c); }"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <option value="">Seleccionar…</option>
              <?php foreach ($conceptos as $c): ?>
                <option value="<?= $c['id'] ?>"
                        data-monto="<?= $c['monto'] ?>"
                        <?= $v['concepto_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= e($c['nombre']) ?> (S/ <?= number_format((float)$c['monto'], 2) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Período de cobro</label>
            <select name="periodo_id"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <option value="">Sin período</option>
              <?php foreach ($periodos as $p): ?>
                <option value="<?= $p['id'] ?>"
                        <?= $v['periodo_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                  <?= e($p['nombre']) ?> – S/ <?= number_format((float)$p['monto_total'], 2) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Montos -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Montos</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Monto base (S/.) <span class="text-red-500">*</span></label>
            <input id="inputMonto" name="monto" type="number" step="0.01" min="0.01"
                   x-model="monto" required
                   value="<?= number_format($v['monto'], 2) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Descuento (S/.)</label>
            <input name="descuento" type="number" step="0.01" min="0"
                   x-model="descuento"
                   value="<?= number_format($v['descuento'], 2) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Interés / mora (S/.)</label>
            <input name="interes" type="number" step="0.01" min="0"
                   x-model="interes"
                   value="<?= number_format($v['interes'], 2) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="bg-brand-50 rounded-xl px-4 py-3 border border-brand-100">
            <p class="text-xs text-brand-600 font-medium">Total a pagar</p>
            <p class="text-2xl font-bold text-brand-700" x-text="'S/ ' + total"></p>
          </div>
        </div>
      </div>

      <!-- Datos del pago -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Datos del Pago</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de pago <span class="text-red-500">*</span></label>
            <input name="fecha_pago" type="date" value="<?= e($v['fecha_pago']) ?>" required
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Método de pago</label>
            <select name="metodo_pago"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (METODOS_PAGO as $k => $lbl): ?>
                <option value="<?= e($k) ?>" <?= $v['metodo_pago'] === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">N° operación / voucher</label>
            <input name="referencia" type="text" value="<?= e($v['referencia'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="sm:col-span-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
            <textarea name="observacion" rows="2"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"><?= e($v['observacion'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 justify-end">
        <a href="<?= APP_URL ?>/pagos/index.php"
           class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
          Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors">
          Guardar Cambios
        </button>
      </div>
    </form>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
