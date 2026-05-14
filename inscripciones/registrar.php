<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Registrar Inscripción';
$activePage = 'inscripciones';
$pdo        = getDB();
$errors     = [];

$editId = (int)($_GET['id'] ?? 0);
$inscData = null;
if ($editId > 0) {
    $s = $pdo->prepare("SELECT i.*, a.nombres, a.apellidos, a.codigo, a.dni FROM inscripciones i JOIN abonados a ON a.id=i.abonado_id WHERE i.id=?");
    $s->execute([$editId]);
    $inscData = $s->fetch() ?: null;
}

$preAbonadoId = (int)($_GET['abonado_id'] ?? 0);
$preAbonado   = null;
if ($preAbonadoId > 0) {
    $s = $pdo->prepare("SELECT id, codigo, dni, nombres, apellidos FROM abonados WHERE id=?");
    $s->execute([$preAbonadoId]);
    $preAbonado = $s->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $abonadoId    = (int)($_POST['abonado_id'] ?? 0);
    $numSolicitud = trim($_POST['numero_solicitud'] ?? '');
    $fecha        = trim($_POST['fecha_inscripcion'] ?? date('Y-m-d'));
    $monto        = (float)str_replace(',', '.', $_POST['monto'] ?? '0');
    $estado       = trim($_POST['estado'] ?? 'pendiente');
    $observacion  = trim($_POST['observacion'] ?? '');

    if ($abonadoId <= 0)         $errors[] = 'Seleccione un abonado.';
    if (!strtotime($fecha))      $errors[] = 'Fecha inválida.';
    if (!in_array($estado, ['pendiente','aprobada','rechazada','cancelada']))
                                  $errors[] = 'Estado inválido.';

    if (empty($errors)) {
        try {
            if ($editId > 0) {
                $pdo->prepare("
                    UPDATE inscripciones SET
                      numero_solicitud=?, fecha_inscripcion=?, monto=?, estado=?, observacion=?
                    WHERE id=?
                ")->execute([$numSolicitud ?: null, $fecha, $monto, $estado, $observacion, $editId]);
                flash('success', 'Inscripción actualizada.');
            } else {
                $numSol = 'INS-' . date('Y') . str_pad((string)(($pdo->query("SELECT COUNT(*) FROM inscripciones WHERE YEAR(created_at)=YEAR(CURDATE())")->fetchColumn()) + 1), 4,'0',STR_PAD_LEFT);
                $pdo->prepare("
                    INSERT INTO inscripciones (abonado_id, numero_solicitud, fecha_inscripcion, monto, estado, observacion, registrado_por)
                    VALUES (?,?,?,?,?,?,?)
                ")->execute([$abonadoId, $numSol, $fecha, $monto, $estado, $observacion, currentUser()['id']]);
                flash('success', "Inscripción $numSol registrada.");
            }
            redirect(APP_URL . '/inscripciones/index.php');
        } catch (PDOException $ex) {
            $errors[] = 'Error: ' . $ex->getMessage();
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
      <a href="<?= APP_URL ?>/inscripciones/index.php" class="hover:text-brand-600">Inscripciones</a>
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
      <span class="text-gray-600 font-medium"><?= $editId ? 'Editar' : 'Nueva' ?> Inscripción</span>
    </div>

    <?php if ($errors): ?>
      <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        <ul class="list-disc list-inside"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="" class="max-w-2xl space-y-5">
      <?php if ($editId): ?>
        <input type="hidden" name="abonado_id" value="<?= $inscData['abonado_id'] ?? 0 ?>">
      <?php endif; ?>

      <!-- Abonado -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Abonado</h2></div>
        <div class="p-5">
          <?php if ($inscData): ?>
            <p class="font-semibold text-gray-800"><?= e($inscData['apellidos'] . ', ' . $inscData['nombres']) ?></p>
            <p class="text-xs text-gray-400"><?= e($inscData['codigo']) ?> · DNI <?= e($inscData['dni']) ?></p>
          <?php elseif ($preAbonado): ?>
            <input type="hidden" name="abonado_id" value="<?= $preAbonado['id'] ?>">
            <p class="font-semibold text-gray-800"><?= e($preAbonado['apellidos'] . ', ' . $preAbonado['nombres']) ?></p>
            <p class="text-xs text-gray-400"><?= e($preAbonado['codigo']) ?> · DNI <?= e($preAbonado['dni']) ?></p>
          <?php else: ?>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Buscar abonado</label>
              <input id="buscarAbonado" type="text" placeholder="DNI, nombre…"
                     class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <div id="resultados" class="mt-2 space-y-1 hidden"></div>
              <input type="hidden" name="abonado_id" id="abonadoIdInput">
              <p id="abonadoSel" class="text-xs text-green-600 mt-1 hidden"></p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Datos inscripción -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Datos de la Inscripción</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha</label>
            <input name="fecha_inscripcion" type="date"
                   value="<?= e($inscData['fecha_inscripcion'] ?? date('Y-m-d')) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Monto (S/.)</label>
            <input name="monto" type="number" step="0.01" min="0"
                   value="<?= number_format((float)($inscData['monto'] ?? 0), 2) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
            <select name="estado"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (['pendiente'=>'Pendiente','aprobada'=>'Aprobada','rechazada'=>'Rechazada','cancelada'=>'Cancelada'] as $k=>$v): ?>
                <option value="<?= e($k) ?>" <?= ($inscData['estado'] ?? 'pendiente') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
            <textarea name="observacion" rows="2"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"><?= e($inscData['observacion'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 justify-end">
        <a href="<?= APP_URL ?>/inscripciones/index.php"
           class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
          Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors">
          <?= $editId ? 'Actualizar' : 'Registrar' ?> Inscripción
        </button>
      </div>
    </form>
  </main>
</div>

<script>
const inp = document.getElementById('buscarAbonado');
const res = document.getElementById('resultados');
const hid = document.getElementById('abonadoIdInput');
const sel = document.getElementById('abonadoSel');
if (inp) {
  let t;
  inp.addEventListener('input', () => {
    clearTimeout(t);
    const q = inp.value.trim();
    if (q.length < 2) { res?.classList.add('hidden'); return; }
    t = setTimeout(async () => {
      const r = await fetch('<?= APP_URL ?>/api/buscar_abonado.php?q=' + encodeURIComponent(q));
      const rows = await r.json();
      res.innerHTML = '';
      rows.forEach(a => {
        const d = document.createElement('div');
        d.className = 'flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-brand-50 cursor-pointer border border-gray-100';
        d.innerHTML = `<span class="font-medium text-sm text-gray-700">${a.apellidos}, ${a.nombres}</span><span class="text-xs text-gray-400">${a.codigo}</span>`;
        d.onclick = () => { hid.value=a.id; sel.textContent='✓ '+a.apellidos+', '+a.nombres; sel.classList.remove('hidden'); res.classList.add('hidden'); inp.value=''; };
        res.appendChild(d);
      });
      res.classList.remove('hidden');
    }, 300);
  });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
