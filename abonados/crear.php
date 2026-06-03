<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Nuevo Abonado';
$activePage = 'abonados';
$errors     = [];
$data       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
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
        'estado'            => 'activo',
        'observaciones'     => trim($_POST['observaciones'] ?? ''),
    ];

    // Hijos (array)
    $hijosNombres = $_POST['hijo_nombre'] ?? [];
    $hijosFechas  = $_POST['hijo_fecha']  ?? [];

    // Validaciones
    if (!preg_match('/^\d{8}$/', $data['dni']))          $errors[] = 'El DNI debe tener exactamente 8 dígitos.';
    if ($data['nombres'] === '')                          $errors[] = 'El campo Nombres es obligatorio.';
    if ($data['apellidos'] === '')                        $errors[] = 'El campo Apellidos es obligatorio.';
    if (!array_key_exists($data['zona'], ZONAS))         $errors[] = 'La zona seleccionada no es válida.';
    if (!array_key_exists($data['grado_instruccion'], GRADOS_INSTRUCCION)) $errors[] = 'Grado de instrucción inválido.';
    if (!array_key_exists($data['estado_civil'], ESTADOS_CIVILES))         $errors[] = 'Estado civil inválido.';

    if (empty($errors)) {
        try {
            $pdo  = getDB();
            // Verificar DNI único
            $check = $pdo->prepare("SELECT id FROM abonados WHERE dni = ?");
            $check->execute([$data['dni']]);
            if ($check->fetch()) {
                $errors[] = "Ya existe un abonado registrado con el DNI {$data['dni']}.";
            } else {
                $codigo = generarCodigo();
                $pdo->beginTransaction();

                $ins = $pdo->prepare("
                    INSERT INTO abonados
                      (codigo, dni, nombres, apellidos, fecha_nacimiento,
                       departamento, provincia, distrito, direccion, zona,
                       profesion, actividad, grado_instruccion, estado_civil,
                       telefono, email, numero_hijos, fecha_inscripcion,
                       estado, observaciones, creado_por)
                    VALUES
                      (:codigo, :dni, :nombres, :apellidos, :fecha_nacimiento,
                       :departamento, :provincia, :distrito, :direccion, :zona,
                       :profesion, :actividad, :grado_instruccion, :estado_civil,
                       :telefono, :email, :numero_hijos, :fecha_inscripcion,
                       :estado, :observaciones, :creado_por)
                ");
                $ins->execute([
                    ':codigo'           => $codigo,
                    ':creado_por'       => currentUser()['id'],
                    ...$data,
                ]);
                $abonadoId = (int)$pdo->lastInsertId();

                // Guardar hijos
                $insHijo = $pdo->prepare(
                    "INSERT INTO hijos (abonado_id, nombres, fecha_nacimiento) VALUES (?,?,?)"
                );
                foreach ($hijosNombres as $i => $hn) {
                    $hn = trim($hn);
                    if ($hn !== '') {
                        $insHijo->execute([
                            $abonadoId,
                            $hn,
                            trim($hijosFechas[$i] ?? '') ?: null,
                        ]);
                    }
                }

                $pdo->commit();
                flash('success', "Abonado $codigo – {$data['apellidos']}, {$data['nombres']} registrado correctamente.");
                redirect(APP_URL . '/abonados/index.php');
            }
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
      <a href="<?= APP_URL ?>/abonados/index.php" class="hover:text-brand-600">Abonados</a>
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
      <span class="text-gray-600 font-medium">Nuevo Abonado</span>
    </div>

    <?php if ($errors): ?>
      <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        <strong>Corrige los siguientes errores:</strong>
        <ul class="mt-1 list-disc list-inside space-y-0.5">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="" x-data="{ nHijos: <?= max(0, (int)($_POST['numero_hijos'] ?? 0)) ?> }" class="space-y-5">

      <!-- ── Datos del DNI ─────────────────────────────────── -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-brand-50 flex items-center justify-center">
            <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
            </svg>
          </div>
          <h2 class="text-sm font-semibold text-gray-700">Identificación</h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <!-- DNI + botón API -->
          <div class="sm:col-span-1">
            <label class="block text-xs font-medium text-gray-600 mb-1">DNI <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
              <input id="dni" name="dni" type="text" maxlength="8" pattern="\d{8}"
                     value="<?= e($data['dni'] ?? '') ?>" required
                     class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"
                     placeholder="12345678">
              <button type="button" id="btnBuscarDni"
                      class="px-3 py-2 text-xs bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium transition-colors whitespace-nowrap">
                Consultar
              </button>
            </div>
            <p id="dniMsg" class="text-xs mt-1 text-gray-400 hidden"></p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Nombres <span class="text-red-500">*</span></label>
            <input id="nombres" name="nombres" type="text" value="<?= e($data['nombres'] ?? '') ?>" required
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Apellidos <span class="text-red-500">*</span></label>
            <input id="apellidos" name="apellidos" type="text" value="<?= e($data['apellidos'] ?? '') ?>" required
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de nacimiento</label>
            <input name="fecha_nacimiento" type="date" value="<?= e($data['fecha_nacimiento'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono</label>
            <input name="telefono" type="tel" value="<?= e($data['telefono'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Correo electrónico</label>
            <input name="email" type="email" value="<?= e($data['email'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
        </div>
      </div>

      <!-- ── Ubicación ───────────────────────────────────────── -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="text-sm font-semibold text-gray-700">Ubicación</h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Departamento</label>
            <input name="departamento" type="text" value="<?= e($data['departamento'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Provincia</label>
            <input name="provincia" type="text" value="<?= e($data['provincia'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Distrito</label>
            <input name="distrito" type="text" value="<?= e($data['distrito'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Dirección</label>
            <input name="direccion" type="text" value="<?= e($data['direccion'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Zona <span class="text-red-500">*</span></label>
            <select name="zona" required
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <option value="">Seleccionar…</option>
              <?php foreach (ZONAS as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= ($data['zona'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- ── Datos personales adicionales ─────────────────────── -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="text-sm font-semibold text-gray-700">Datos Personales</h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Profesión</label>
            <input name="profesion" type="text" value="<?= e($data['profesion'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Actividad</label>
            <input name="actividad" type="text" value="<?= e($data['actividad'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Grado de instrucción <span class="text-red-500">*</span></label>
            <select name="grado_instruccion" required
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (GRADOS_INSTRUCCION as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= ($data['grado_instruccion'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Estado civil <span class="text-red-500">*</span></label>
            <select name="estado_civil" required
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (ESTADOS_CIVILES as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= ($data['estado_civil'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Número de hijos</label>
            <input name="numero_hijos" type="number" min="0" max="20"
                   x-model="nHijos"
                   value="<?= (int)($data['numero_hijos'] ?? 0) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de inscripción</label>
            <input name="fecha_inscripcion" type="date" value="<?= e($data['fecha_inscripcion'] ?? date('Y-m-d')) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="sm:col-span-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones</label>
            <textarea name="observaciones" rows="2"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"><?= e($data['observaciones'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- ── Hijos ──────────────────────────────────────────── -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-show="nHijos > 0">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="text-sm font-semibold text-gray-700">Datos de Hijos</h2>
        </div>
        <div class="p-5 space-y-3">
          <template x-for="i in parseInt(nHijos)" :key="i">
            <div class="flex gap-3 items-end">
              <div class="flex-1">
                <label class="block text-xs font-medium text-gray-600 mb-1" x-text="'Hijo ' + i + ' – Nombres'"></label>
                <input :name="'hijo_nombre[]'" type="text"
                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha nac.</label>
                <input :name="'hijo_fecha[]'" type="date"
                       class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Botones -->
      <div class="flex items-center gap-3 justify-end">
        <a href="<?= APP_URL ?>/abonados/index.php"
           class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
          Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors">
          Guardar Abonado
        </button>
      </div>
    </form>
  </main>
</div>

<script>
// ── Consulta DNI via API migo.pe ────────────────────────────────────
document.getElementById('btnBuscarDni').addEventListener('click', async () => {
    const dni = document.getElementById('dni').value.trim();
    const msg = document.getElementById('dniMsg');

    if (!/^\d{8}$/.test(dni)) {
        msg.textContent = 'Ingrese 8 dígitos para consultar.';
        msg.className   = 'text-xs mt-1 text-red-500';
        msg.classList.remove('hidden');
        return;
    }

    msg.textContent = 'Consultando…';
    msg.className   = 'text-xs mt-1 text-gray-400';
    msg.classList.remove('hidden');

    try {
        const res  = await fetch('<?= APP_URL ?>/api/dni.php?dni=' + encodeURIComponent(dni));
        const json = await res.json();

        if (json.success) {
            document.getElementById('apellidos').value = json.apellidos;
            document.getElementById('nombres').value   = json.nombres;
            msg.textContent = '✓ Datos cargados desde RENIEC';
            msg.className   = 'text-xs mt-1 text-green-600';
        } else {
            msg.textContent = json.message ?? 'No se encontró información para ese DNI.';
            msg.className   = 'text-xs mt-1 text-orange-500';
        }
    } catch {
        msg.textContent = 'Error al conectar con el servicio de DNI.';
        msg.className   = 'text-xs mt-1 text-red-500';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
