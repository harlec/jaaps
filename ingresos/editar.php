<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Editar Ingreso';
$activePage = 'ingresos';
$pdo        = getDB();
$errors     = [];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) { flash('error', 'Ingreso no válido.'); redirect(APP_URL . '/ingresos/index.php'); }

$stmt = $pdo->prepare("SELECT * FROM ingresos_otros WHERE id = ?");
$stmt->execute([$id]);
$ingreso = $stmt->fetch();
if (!$ingreso) { flash('error', 'Ingreso no encontrado.'); redirect(APP_URL . '/ingresos/index.php'); }

$conceptos = $pdo->query("SELECT * FROM ingreso_conceptos WHERE activo = 1 OR id = " . (int)$ingreso['concepto_id'] . " ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conceptoId   = (int)($_POST['concepto_id'] ?? 0);
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $monto        = (float)str_replace(',', '.', $_POST['monto'] ?? '0');
    $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');
    $metodo       = trim($_POST['metodo_pago'] ?? 'efectivo');
    $origen       = trim($_POST['origen'] ?? '');
    $comprobante  = trim($_POST['numero_comprobante'] ?? '');
    $observacion  = trim($_POST['observacion'] ?? '');

    if ($conceptoId <= 0)   $errors[] = 'Seleccione un concepto de ingreso.';
    if ($monto <= 0)        $errors[] = 'El monto debe ser mayor a cero.';
    if (!array_key_exists($metodo, METODOS_PAGO)) $errors[] = 'Método de pago inválido.';
    if (!strtotime($fechaIngreso)) $errors[] = 'Fecha de ingreso inválida.';

    if (empty($errors)) {
        try {
            $upd = $pdo->prepare("
                UPDATE ingresos_otros SET
                    concepto_id        = :concepto_id,
                    descripcion        = :descripcion,
                    monto              = :monto,
                    fecha_ingreso      = :fecha_ingreso,
                    metodo_pago        = :metodo_pago,
                    origen             = :origen,
                    numero_comprobante = :numero_comprobante,
                    observacion        = :observacion
                WHERE id = :id
            ");
            $upd->execute([
                ':concepto_id'        => $conceptoId,
                ':descripcion'        => $descripcion,
                ':monto'              => $monto,
                ':fecha_ingreso'      => $fechaIngreso,
                ':metodo_pago'        => $metodo,
                ':origen'             => $origen,
                ':numero_comprobante' => $comprobante,
                ':observacion'        => $observacion,
                ':id'                 => $id,
            ]);

            flash('success', 'Ingreso actualizado correctamente.');
            redirect(APP_URL . '/ingresos/index.php');
        } catch (PDOException $ex) {
            $errors[] = 'Error al actualizar: ' . $ex->getMessage();
        }
    }
}

$v = [
    'concepto_id'        => (int)($_POST['concepto_id']        ?? $ingreso['concepto_id']),
    'descripcion'        => $_POST['descripcion']        ?? $ingreso['descripcion'],
    'monto'              => (float)($_POST['monto']       ?? $ingreso['monto']),
    'fecha_ingreso'      => $_POST['fecha_ingreso']      ?? $ingreso['fecha_ingreso'],
    'metodo_pago'        => $_POST['metodo_pago']        ?? $ingreso['metodo_pago'],
    'origen'             => $_POST['origen']             ?? $ingreso['origen'],
    'numero_comprobante' => $_POST['numero_comprobante'] ?? $ingreso['numero_comprobante'],
    'observacion'        => $_POST['observacion']        ?? $ingreso['observacion'],
];

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
      <span class="text-gray-600 font-medium">Editar Ingreso</span>
    </div>

    <?php if ($errors): ?>
      <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        <strong>Errores:</strong>
        <ul class="mt-1 list-disc list-inside">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="max-w-3xl space-y-5">
      <input type="hidden" name="id" value="<?= $id ?>">

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
                <option value="<?= $c['id'] ?>" <?= $v['concepto_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= e($c['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
            <input name="descripcion" type="text" value="<?= e($v['descripcion'] ?? '') ?>"
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
                   value="<?= number_format($v['monto'], 2) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha del ingreso <span class="text-red-500">*</span></label>
            <input name="fecha_ingreso" type="date" value="<?= e($v['fecha_ingreso']) ?>" required
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
        </div>
      </div>

      <!-- Detalle -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Detalle</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Origen (quién paga)</label>
            <input name="origen" type="text" value="<?= e($v['origen'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">N° comprobante / recibo</label>
            <input name="numero_comprobante" type="text" value="<?= e($v['numero_comprobante'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
            <textarea name="observacion" rows="2"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"><?= e($v['observacion'] ?? '') ?></textarea>
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
          Guardar Cambios
        </button>
      </div>
    </form>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
