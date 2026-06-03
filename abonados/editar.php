<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect(APP_URL . '/abonados/index.php'); }

$pdo      = getDB();
$abonado  = $pdo->prepare("SELECT * FROM abonados WHERE id = ?");
$abonado->execute([$id]);
$a = $abonado->fetch();
if (!$a) { redirect(APP_URL . '/abonados/index.php'); }

$pageTitle  = 'Editar Abonado';
$activePage = 'abonados';
$errors     = [];

// Cargar hijos
$hStmt = $pdo->prepare("SELECT * FROM hijos WHERE abonado_id = ? ORDER BY id");
$hStmt->execute([$id]);
$hijosDB = $hStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'dni'               => trim($_POST['dni'] ?? ''),
        'nombres'           => trim($_POST['nombres'] ?? ''),
        'apellidos'         => trim($_POST['apellidos'] ?? ''),
        'fecha_nacimiento'  => trim($_POST['fecha_nacimiento'] ?? '') ?: null,
        'departamento'      => trim($_POST['departamento'] ?? ''),
        'provincia'         => trim($_POST['provincia'] ?? ''),
        'distrito'          => trim($_POST['distrito'] ?? ''),
        'direccion'         => trim($_POST['direccion'] ?? ''),
        'zona'              => trim($_POST['zona'] ?? ''),
        'profesion'         => trim($_POST['profesion'] ?? ''),
        'actividad'         => trim($_POST['actividad'] ?? ''),
        'grado_instruccion' => trim($_POST['grado_instruccion'] ?? ''),
        'estado_civil'      => trim($_POST['estado_civil'] ?? ''),
        'telefono'          => trim($_POST['telefono'] ?? ''),
        'email'             => trim($_POST['email'] ?? ''),
        'numero_hijos'      => max(0, (int)($_POST['numero_hijos'] ?? 0)),
        'fecha_inscripcion' => trim($_POST['fecha_inscripcion'] ?? '') ?: null,
        'estado'            => trim($_POST['estado'] ?? 'activo'),
        'observaciones'     => trim($_POST['observaciones'] ?? ''),
    ];

    $hijosNombres = $_POST['hijo_nombre'] ?? [];
    $hijosFechas  = $_POST['hijo_fecha']  ?? [];

    if (!preg_match('/^\d{8}$/', $data['dni']))          $errors[] = 'El DNI debe tener 8 dígitos.';
    if ($data['nombres'] === '')                          $errors[] = 'Nombres es obligatorio.';
    if ($data['apellidos'] === '')                        $errors[] = 'Apellidos es obligatorio.';
    if (!array_key_exists($data['zona'], ZONAS))         $errors[] = 'Zona inválida.';
    if (!array_key_exists($data['estado'], ESTADOS_ABONADO)) $errors[] = 'Estado inválido.';

    if (empty($errors)) {
        try {
            // Verificar DNI único (excluyendo este registro)
            $ck = $pdo->prepare("SELECT id FROM abonados WHERE dni = ? AND id <> ?");
            $ck->execute([$data['dni'], $id]);
            if ($ck->fetch()) {
                $errors[] = "El DNI {$data['dni']} ya está registrado en otro abonado.";
            } else {
                $pdo->beginTransaction();

                $upd = $pdo->prepare("
                    UPDATE abonados SET
                      dni=:dni, nombres=:nombres, apellidos=:apellidos,
                      fecha_nacimiento=:fecha_nacimiento, departamento=:departamento,
                      provincia=:provincia, distrito=:distrito, direccion=:direccion,
                      zona=:zona, profesion=:profesion, actividad=:actividad,
                      grado_instruccion=:grado_instruccion, estado_civil=:estado_civil,
                      telefono=:telefono, email=:email, numero_hijos=:numero_hijos,
                      fecha_inscripcion=:fecha_inscripcion, estado=:estado,
                      observaciones=:observaciones
                    WHERE id=:id
                ");
                $upd->execute([':id' => $id, ...$data]);

                // Reemplazar hijos: borrar y volver a insertar
                $pdo->prepare("DELETE FROM hijos WHERE abonado_id = ?")->execute([$id]);
                $insHijo = $pdo->prepare(
                    "INSERT INTO hijos (abonado_id, nombres, fecha_nacimiento) VALUES (?,?,?)"
                );
                foreach ($hijosNombres as $i => $hn) {
                    $hn = trim($hn);
                    if ($hn !== '') {
                        $insHijo->execute([$id, $hn, trim($hijosFechas[$i] ?? '') ?: null]);
                    }
                }

                $pdo->commit();
                flash('success', "Abonado actualizado correctamente.");
                redirect(APP_URL . '/abonados/ver.php?id=' . $id);
            }
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Error al guardar: ' . $ex->getMessage();
        }
    }

    // Si hay errores, pinta los POST values
    $a = array_merge($a, $data);
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
      <a href="<?= APP_URL ?>/abonados/index.php" class="hover:text-brand-600">Abonados</a>
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
      <span class="text-gray-600 font-medium">Editar – <?= e($a['codigo']) ?></span>
    </div>

    <?php if ($errors): ?>
      <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        <strong>Corrige los siguientes errores:</strong>
        <ul class="mt-1 list-disc list-inside">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="" x-data="{ nHijos: <?= (int)$a['numero_hijos'] ?> }" class="space-y-5">

      <!-- Identificación -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Identificación</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">DNI <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
              <input id="dni" name="dni" type="text" maxlength="8" pattern="\d{8}" value="<?= e($a['dni']) ?>" required
                     class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <button type="button" id="btnBuscarDni"
                      class="px-3 py-2 text-xs bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium transition-colors">
                Consultar
              </button>
            </div>
            <p id="dniMsg" class="text-xs mt-1 text-gray-400 hidden"></p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Nombres <span class="text-red-500">*</span></label>
            <input id="nombres" name="nombres" type="text" value="<?= e($a['nombres']) ?>" required
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Apellidos <span class="text-red-500">*</span></label>
            <input id="apellidos" name="apellidos" type="text" value="<?= e($a['apellidos']) ?>" required
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de nacimiento</label>
            <input name="fecha_nacimiento" type="date" value="<?= e($a['fecha_nacimiento'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono</label>
            <input name="telefono" type="tel" value="<?= e($a['telefono'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Correo electrónico</label>
            <input name="email" type="email" value="<?= e($a['email'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
            <select name="estado"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (ESTADOS_ABONADO as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= $a['estado'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Ubicación -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Ubicación</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Departamento</label>
            <input name="departamento" type="text" value="<?= e($a['departamento']) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Provincia</label>
            <input name="provincia" type="text" value="<?= e($a['provincia']) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Distrito</label>
            <input name="distrito" type="text" value="<?= e($a['distrito']) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Dirección</label>
            <input name="direccion" type="text" value="<?= e($a['direccion'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Zona <span class="text-red-500">*</span></label>
            <select name="zona" required
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (ZONAS as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= $a['zona'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Datos personales -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Datos Personales</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Profesión</label>
            <input name="profesion" type="text" value="<?= e($a['profesion'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Actividad</label>
            <input name="actividad" type="text" value="<?= e($a['actividad'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Grado de instrucción</label>
            <select name="grado_instruccion"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (GRADOS_INSTRUCCION as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= $a['grado_instruccion'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Estado civil</label>
            <select name="estado_civil"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (ESTADOS_CIVILES as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= $a['estado_civil'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Número de hijos</label>
            <input name="numero_hijos" type="number" min="0" max="20" x-model="nHijos"
                   value="<?= (int)$a['numero_hijos'] ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de inscripción</label>
            <input name="fecha_inscripcion" type="date" value="<?= e($a['fecha_inscripcion'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="sm:col-span-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones</label>
            <textarea name="observaciones" rows="2"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"><?= e($a['observaciones'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Hijos existentes + nuevos -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-show="nHijos > 0">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Datos de Hijos</h2></div>
        <div class="p-5 space-y-3">
          <?php foreach ($hijosDB as $hi): ?>
          <div class="flex gap-3 items-end">
            <div class="flex-1">
              <label class="block text-xs font-medium text-gray-600 mb-1">Nombres</label>
              <input name="hijo_nombre[]" type="text" value="<?= e($hi['nombres']) ?>"
                     class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Fecha nac.</label>
              <input name="hijo_fecha[]" type="date" value="<?= e($hi['fecha_nacimiento'] ?? '') ?>"
                     class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>
          </div>
          <?php endforeach; ?>
          <template x-for="i in Math.max(0, parseInt(nHijos) - <?= count($hijosDB) ?>)" :key="i">
            <div class="flex gap-3 items-end">
              <div class="flex-1">
                <label class="block text-xs font-medium text-gray-600 mb-1" x-text="'Nuevo hijo ' + i"></label>
                <input name="hijo_nombre[]" type="text"
                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha nac.</label>
                <input name="hijo_fecha[]" type="date"
                       class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Botones -->
      <div class="flex items-center gap-3 justify-end">
        <a href="<?= APP_URL ?>/abonados/ver.php?id=<?= $id ?>"
           class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
          Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors">
          Actualizar Abonado
        </button>
      </div>
    </form>
  </main>
</div>

<script>
document.getElementById('btnBuscarDni').addEventListener('click', async () => {
    const dni = document.getElementById('dni').value.trim();
    const msg = document.getElementById('dniMsg');
    if (!/^\d{8}$/.test(dni)) {
        msg.textContent = 'Ingrese 8 dígitos.';
        msg.className = 'text-xs mt-1 text-red-500'; msg.classList.remove('hidden'); return;
    }
    msg.textContent = 'Consultando…'; msg.className = 'text-xs mt-1 text-gray-400'; msg.classList.remove('hidden');
    try {
        const res  = await fetch('<?= APP_URL ?>/api/dni.php?dni=' + encodeURIComponent(dni));
        const json = await res.json();
        if (json.success) {
            document.getElementById('apellidos').value = json.apellidos;
            document.getElementById('nombres').value   = json.nombres;
            msg.textContent = '✓ Datos cargados desde RENIEC'; msg.className = 'text-xs mt-1 text-green-600';
        } else {
            msg.textContent = json.message ?? 'DNI no encontrado.'; msg.className = 'text-xs mt-1 text-orange-500';
        }
    } catch { msg.textContent = 'Error de conexión.'; msg.className = 'text-xs mt-1 text-red-500'; }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
