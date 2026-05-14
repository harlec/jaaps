<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Conceptos de Pago';
$activePage = 'conceptos';
$pdo        = getDB();
$errors     = [];
$editData   = null;

$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    $es = $pdo->prepare("SELECT * FROM conceptos WHERE id = ?");
    $es->execute([$editId]);
    $editData = $es->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? 'save';
    $concId    = (int)($_POST['concepto_id'] ?? 0);
    $nombre    = trim($_POST['nombre'] ?? '');
    $desc      = trim($_POST['descripcion'] ?? '');
    $monto     = (float)str_replace(',', '.', $_POST['monto'] ?? '0');
    $tipo      = trim($_POST['tipo'] ?? 'otro');
    $activo    = isset($_POST['activo']) ? 1 : 0;

    if ($action === 'toggle' && $concId > 0) {
        $pdo->prepare("UPDATE conceptos SET activo = !activo WHERE id = ?")->execute([$concId]);
        flash('success', 'Estado actualizado.');
        redirect(APP_URL . '/conceptos/index.php');
    }

    if ($nombre === '') $errors[] = 'El nombre es obligatorio.';
    if (!in_array($tipo, ['tarifa_mensual','inscripcion','multa','reconexion','otro'])) $errors[] = 'Tipo inválido.';

    if (empty($errors)) {
        try {
            if ($concId > 0) {
                $pdo->prepare("UPDATE conceptos SET nombre=?,descripcion=?,monto=?,tipo=?,activo=? WHERE id=?")
                    ->execute([$nombre,$desc,$monto,$tipo,$activo,$concId]);
                flash('success', "Concepto \"$nombre\" actualizado.");
            } else {
                $pdo->prepare("INSERT INTO conceptos (nombre,descripcion,monto,tipo,activo) VALUES (?,?,?,?,?)")
                    ->execute([$nombre,$desc,$monto,$tipo,1]);
                flash('success', "Concepto \"$nombre\" creado.");
            }
            redirect(APP_URL . '/conceptos/index.php');
        } catch (PDOException $ex) {
            $errors[] = 'Error: ' . $ex->getMessage();
        }
    }
}

$conceptos = $pdo->query("SELECT * FROM conceptos ORDER BY tipo, nombre")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>
  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <h1 class="text-lg font-bold text-gray-800">Conceptos de Pago</h1>

    <?php $msg = flash('success'); if ($msg): ?>
      <div class="px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

      <!-- Formulario -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="text-sm font-semibold text-gray-700"><?= $editData ? 'Editar Concepto' : 'Nuevo Concepto' ?></h2>
        </div>
        <div class="p-5">
          <?php if ($errors): ?>
            <div class="mb-4 px-3 py-2 rounded-lg bg-red-50 text-sm text-red-700">
              <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <form method="POST" action="" class="space-y-4">
            <?php if ($editData): ?>
              <input type="hidden" name="concepto_id" value="<?= $editData['id'] ?>">
            <?php endif; ?>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Nombre <span class="text-red-500">*</span></label>
              <input name="nombre" type="text" value="<?= e($editData['nombre'] ?? '') ?>" required
                     class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
              <textarea name="descripcion" rows="2"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"><?= e($editData['descripcion'] ?? '') ?></textarea>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Monto base (S/.)</label>
              <input name="monto" type="number" step="0.01" min="0"
                     value="<?= number_format((float)($editData['monto'] ?? 0), 2) ?>"
                     class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
              <select name="tipo"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
                <?php foreach (['tarifa_mensual'=>'Tarifa Mensual','inscripcion'=>'Inscripción','multa'=>'Multa','reconexion'=>'Reconexión','otro'=>'Otro'] as $k=>$v): ?>
                  <option value="<?= e($k) ?>" <?= ($editData['tipo'] ?? 'otro') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($editData): ?>
            <label class="flex items-center gap-2 text-sm text-gray-600">
              <input type="checkbox" name="activo" <?= ($editData['activo'] ?? 1) ? 'checked' : '' ?>
                     class="rounded border-gray-300 text-brand-600">
              Activo
            </label>
            <?php endif; ?>
            <div class="flex gap-2">
              <?php if ($editData): ?>
                <a href="<?= APP_URL ?>/conceptos/index.php"
                   class="flex-1 text-center px-3 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-gray-600">
                  Cancelar
                </a>
              <?php endif; ?>
              <button type="submit"
                      class="flex-1 px-3 py-2 text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors">
                <?= $editData ? 'Actualizar' : 'Crear Concepto' ?>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Lista -->
      <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Conceptos Registrados</h2></div>
        <div class="divide-y divide-gray-50">
          <?php foreach ($conceptos as $c):
            $typeCls = ['tarifa_mensual'=>'bg-teal-50 text-teal-700','inscripcion'=>'bg-blue-50 text-blue-700',
                        'multa'=>'bg-red-50 text-red-600','reconexion'=>'bg-orange-50 text-orange-600','otro'=>'bg-gray-100 text-gray-600'];
            $typeLabel = ['tarifa_mensual'=>'Tarifa mensual','inscripcion'=>'Inscripción','multa'=>'Multa','reconexion'=>'Reconexión','otro'=>'Otro'];
          ?>
          <div class="px-5 py-4 flex items-center gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="font-medium text-gray-800 text-sm"><?= e($c['nombre']) ?></p>
                <span class="<?= $typeCls[$c['tipo']] ?? '' ?> text-xs px-2 py-0.5 rounded-full font-medium">
                  <?= e($typeLabel[$c['tipo']] ?? $c['tipo']) ?>
                </span>
                <?php if (!$c['activo']): ?>
                  <span class="bg-gray-100 text-gray-400 text-xs px-2 py-0.5 rounded-full">Inactivo</span>
                <?php endif; ?>
              </div>
              <?php if ($c['descripcion']): ?>
                <p class="text-xs text-gray-400 mt-0.5"><?= e($c['descripcion']) ?></p>
              <?php endif; ?>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="font-semibold text-gray-800">S/ <?= number_format((float)$c['monto'], 2) ?></p>
            </div>
            <div class="flex gap-1 flex-shrink-0">
              <a href="?edit=<?= $c['id'] ?>"
                 class="p-1.5 rounded-lg hover:bg-blue-50 text-gray-400 hover:text-blue-600 transition" title="Editar">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                </svg>
              </a>
              <form method="POST" action="" class="inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="concepto_id" value="<?= $c['id'] ?>">
                <button type="submit" title="<?= $c['activo'] ? 'Desactivar' : 'Activar' ?>"
                        class="p-1.5 rounded-lg hover:bg-orange-50 text-gray-400 hover:text-orange-500 transition">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 12.728M5.636 5.636A9 9 0 0 1 18.364 18.364" />
                  </svg>
                </button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($conceptos)): ?>
            <div class="px-5 py-10 text-center text-gray-400 text-sm">No hay conceptos registrados.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
