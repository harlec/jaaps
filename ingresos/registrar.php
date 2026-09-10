<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Registrar Ingreso';
$activePage = 'ingresos';
$pdo        = getDB();
$errors     = [];

$conceptos = $pdo->query("SELECT * FROM ingreso_conceptos WHERE activo = 1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conceptoId  = (int)($_POST['concepto_id'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $monto       = (float)str_replace(',', '.', $_POST['monto'] ?? '0');
    $fechaIngreso= trim($_POST['fecha_ingreso'] ?? date('Y-m-d'));
    $metodo      = trim($_POST['metodo_pago'] ?? 'efectivo');
    $origen      = trim($_POST['origen'] ?? '');
    $comprobante = trim($_POST['numero_comprobante'] ?? '');
    $observacion = trim($_POST['observacion'] ?? '');

    if ($conceptoId <= 0)   $errors[] = 'Seleccione un concepto de ingreso.';
    if ($monto <= 0)        $errors[] = 'El monto debe ser mayor a cero.';
    if (!array_key_exists($metodo, METODOS_PAGO)) $errors[] = 'Método de pago inválido.';
    if (!strtotime($fechaIngreso)) $errors[] = 'Fecha de ingreso inválida.';

    if (empty($errors)) {
        try {
            $ins = $pdo->prepare("
                INSERT INTO ingresos_otros
                  (concepto_id, descripcion, monto, fecha_ingreso, metodo_pago, origen, numero_comprobante, observacion, registrado_por)
                VALUES
                  (:concepto_id, :descripcion, :monto, :fecha_ingreso, :metodo_pago, :origen, :numero_comprobante, :observacion, :registrado_por)
            ");
            $ins->execute([
                ':concepto_id'        => $conceptoId,
                ':descripcion'        => $descripcion,
                ':monto'              => $monto,
                ':fecha_ingreso'      => $fechaIngreso,
                ':metodo_pago'        => $metodo,
                ':origen'             => $origen,
                ':numero_comprobante' => $comprobante,
                ':observacion'        => $observacion,
                ':registrado_por'     => currentUser()['id'],
            ]);

            flash('success', 'Ingreso registrado correctamente por S/ ' . number_format($monto, 2));
            redirect(APP_URL . '/ingresos/index.php');
        } catch (PDOException $ex) {
            $errors[] = 'Error al registrar ingreso: ' . $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
      <a href="<?= APP_URL ?>/ingresos/index.php" class="hover:text-brand-600">Otros Ingresos</a>
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
      <span class="text-gray-600 font-medium">Registrar Ingreso</span>
    </div>

    <?php if ($errors): ?>
      <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        <strong>Errores:</strong>
        <ul class="mt-1 list-disc list-inside">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (empty($conceptos)): ?>
      <div class="mb-5 px-4 py-3 rounded-lg bg-orange-50 border border-orange-200 text-sm text-orange-700">
        No hay conceptos de ingreso activos. <a href="<?= APP_URL ?>/ingresos/conceptos.php" class="underline font-medium">Crea uno primero</a>.
      </div>
    <?php endif; ?>

    <form method="POST" action="" class="max-w-3xl space-y-5">

      <!-- Concepto -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Concepto de Ingreso</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Concepto <span class="text-red-500">*</span></label>
            <select name="concepto_id" required
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <option value="">Seleccionar…</option>
              <?php foreach ($conceptos as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int)($_POST['concepto_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= e($c['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
            <input name="descripcion" type="text" value="<?= e($_POST['descripcion'] ?? '') ?>"
                   placeholder="Detalle breve (opcional)"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
        </div>
      </div>

      <!-- Monto y fecha -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Monto y Fecha</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Monto (S/.) <span class="text-red-500">*</span></label>
            <input name="monto" type="number" step="0.01" min="0.01" required
                   value="<?= e($_POST['monto'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha del ingreso <span class="text-red-500">*</span></label>
            <input name="fecha_ingreso" type="date" value="<?= e($_POST['fecha_ingreso'] ?? date('Y-m-d')) ?>" required
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Método de pago</label>
            <select name="metodo_pago"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (METODOS_PAGO as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= ($_POST['metodo_pago'] ?? 'efectivo') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Detalle -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Detalle</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Origen (quién paga)</label>
            <input name="origen" type="text" value="<?= e($_POST['origen'] ?? '') ?>"
                   placeholder="Ej: Inquilino local, municipalidad…"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">N° comprobante / recibo</label>
            <input name="numero_comprobante" type="text" value="<?= e($_POST['numero_comprobante'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
            <textarea name="observacion" rows="2"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"><?= e($_POST['observacion'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 justify-end">
        <a href="<?= APP_URL ?>/ingresos/index.php"
           class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
          Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors">
          Guardar Ingreso
        </button>
      </div>
    </form>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
