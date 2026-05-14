<?php
/**
 * verificar_nombres.php
 * Herramienta para validar y corregir nombres de abonados contra la API migo.pe.
 * Requiere inicio de sesión como administrador.
 */

declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
requireLogin();
requireRole('admin');

// ── Manejador de actualización (POST AJAX) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'actualizar') {
    header('Content-Type: application/json');

    $id              = (int)($_POST['id']       ?? 0);
    $nuevosNombres   = trim($_POST['nombres']   ?? '');
    $nuevosApellidos = trim($_POST['apellidos'] ?? '');

    if ($id <= 0 || $nuevosNombres === '' || $nuevosApellidos === '') {
        echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
        exit;
    }

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            "UPDATE abonados
                SET nombres    = ?,
                    apellidos  = ?,
                    observaciones = CONCAT(COALESCE(observaciones,''), ' | Nombre actualizado desde migo.pe el " . date('Y-m-d') . "')
              WHERE id = ?"
        );
        $stmt->execute([$nuevosNombres, $nuevosApellidos, $id]);
        echo json_encode(['ok' => true]);
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => 'Error al actualizar']);
    }
    exit;
}

// ── Cargar abonados con DNI real (excluir provisionales 999xxxxx) ────
$pdo = getDB();
$stmt = $pdo->query(
    "SELECT id, codigo, dni, nombres, apellidos, zona, observaciones
       FROM abonados
      WHERE dni NOT LIKE '999%'
      ORDER BY zona, codigo"
);
$abonados = $stmt->fetchAll();

$totalAbonados = count($abonados);

$zonaLabel = ['tunas' => 'Tunas', 'cerro_de_pasco' => 'Pasco - Carrizales', 'porvenir' => 'Porvenir - Carrizales'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verificar Nombres con migo.pe | JAAP</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { brand: { 600:'#0d9488', 700:'#0f766e' } } } } }</script>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="max-w-7xl mx-auto px-4 py-8">

  <!-- Encabezado -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Verificar Nombres con migo.pe</h1>
      <p class="text-sm text-gray-500 mt-1">
        Compara los nombres del padrón contra el RENIEC a través de la API migo.pe.
        <strong><?= $totalAbonados ?> abonados</strong> con DNI real.
      </p>
    </div>
    <a href="<?= APP_URL ?>/abonados/" class="text-sm text-brand-600 hover:underline">← Volver a Abonados</a>
  </div>

  <!-- Barra de progreso y controles -->
  <div class="bg-white rounded-xl shadow p-5 mb-6">
    <div class="flex flex-wrap items-center gap-4">
      <button id="btnVerificarTodos"
              class="px-5 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
        ▶ Verificar todos (con pausa de 1s entre consultas)
      </button>
      <button id="btnDetener" disabled
              class="px-5 py-2 bg-gray-200 text-gray-500 rounded-lg text-sm font-medium cursor-not-allowed">
        ■ Detener
      </button>
      <button id="btnActualizarDiferentes"  type="button"
              class="px-5 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition hidden">
        ↑ Actualizar todos con nombre diferente
      </button>
      <span id="statsText" class="text-sm text-gray-500 ml-auto"></span>
    </div>

    <!-- Barra de progreso -->
    <div class="mt-4 h-2 bg-gray-100 rounded-full overflow-hidden">
      <div id="progressBar" class="h-full bg-brand-600 transition-all duration-300" style="width:0%"></div>
    </div>
    <div id="progressLabel" class="text-xs text-gray-400 mt-1">Listo para verificar</div>
  </div>

  <!-- Leyenda -->
  <div class="flex gap-4 text-xs mb-4">
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-gray-200 inline-block"></span> Sin verificar</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Nombre correcto</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span> Nombre diferente (verificar)</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span> DNI no encontrado / error</span>
  </div>

  <!-- Tabla -->
  <div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
        <tr>
          <th class="px-4 py-3 text-left w-8">#</th>
          <th class="px-4 py-3 text-left">Código</th>
          <th class="px-4 py-3 text-left">DNI</th>
          <th class="px-4 py-3 text-left">Nombres (sistema)</th>
          <th class="px-4 py-3 text-left">Apellidos (sistema)</th>
          <th class="px-4 py-3 text-left">Zona</th>
          <th class="px-4 py-3 text-left">Nombre RENIEC (migo.pe)</th>
          <th class="px-4 py-3 text-center">Acción</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100" id="tablaBody">
        <?php $n = 0; foreach ($abonados as $ab): $n++; ?>
        <tr id="row-<?= $ab['id'] ?>"
            data-id="<?= $ab['id'] ?>"
            data-dni="<?= e($ab['dni']) ?>"
            data-nombres="<?= e($ab['nombres']) ?>"
            data-apellidos="<?= e($ab['apellidos']) ?>"
            class="hover:bg-gray-50 transition">
          <td class="px-4 py-3 text-gray-400"><?= $n ?></td>
          <td class="px-4 py-3 font-mono font-medium text-brand-600"><?= e($ab['codigo']) ?></td>
          <td class="px-4 py-3 font-mono"><?= e($ab['dni']) ?></td>
          <td class="px-4 py-3"><?= e($ab['nombres']) ?></td>
          <td class="px-4 py-3"><?= e($ab['apellidos']) ?></td>
          <td class="px-4 py-3 text-gray-500"><?= e($zonaLabel[$ab['zona']] ?? $ab['zona']) ?></td>
          <td class="px-4 py-3 text-gray-400 italic" id="api-<?= $ab['id'] ?>">—</td>
          <td class="px-4 py-3 text-center" id="btn-<?= $ab['id'] ?>">
            <button onclick="verificarUno(<?= $ab['id'] ?>)"
                    class="px-3 py-1 text-xs bg-gray-100 hover:bg-brand-600 hover:text-white rounded transition">
              Verificar
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="text-xs text-gray-400 mt-4 text-center">
    ⚠️ Borra este archivo del servidor cuando termines, o restringe el acceso sólo a administradores.
  </p>
</div>

<script>
const API_URL  = '<?= APP_URL ?>/api/dni.php';
const UPDATE_URL = window.location.pathname;

let stopRequested = false;
let diferentesIds = [];

// ── Verifica un abonado individual ───────────────────────────────────
async function verificarUno(id) {
    const row      = document.getElementById('row-' + id);
    const apiCell  = document.getElementById('api-' + id);
    const btnCell  = document.getElementById('btn-' + id);
    const dni      = row.dataset.dni;
    const nombres  = row.dataset.nombres;
    const apellidos= row.dataset.apellidos;

    apiCell.innerHTML = '<span class="text-gray-400 animate-pulse">Consultando...</span>';
    btnCell.innerHTML = '';

    try {
        const res  = await fetch(`${API_URL}?dni=${encodeURIComponent(dni)}`);
        const data = await res.json();

        if (!data.success) {
            apiCell.innerHTML = `<span class="text-red-500">❌ ${data.error ?? 'No encontrado'}</span>`;
            row.classList.add('bg-red-50');
            return;
        }

        // migo.pe devuelve: "APELLIDO1 APELLIDO2 NOMBRE1 NOMBRE2"
        const partes        = data.nombre.trim().split(/\s+/);
        const apiApellidos  = partes.slice(0, 2).join(' ');
        const apiNombres    = partes.slice(2).join(' ');

        const nombreCompleto = data.nombre;
        const sistemaCompleto= (apellidos + ' ' + nombres).toUpperCase();
        const apiCompleto    = nombreCompleto.toUpperCase();

        const iguales = sistemaCompleto.trim() === apiCompleto.trim();

        if (iguales) {
            apiCell.innerHTML = `<span class="text-green-600 font-medium">✓ ${nombreCompleto}</span>`;
            row.classList.add('bg-green-50');
        } else {
            apiCell.innerHTML =
                `<span class="text-amber-700 font-medium">⚠ ${nombreCompleto}</span>` +
                `<div class="text-xs text-gray-400 mt-0.5">
                    Nombres: <em>${apiNombres}</em> | Apellidos: <em>${apiApellidos}</em>
                 </div>`;
            row.classList.add('bg-amber-50');
            diferentesIds.push({id, apiNombres, apiApellidos, nombreCompleto});

            btnCell.innerHTML =
                `<button onclick="actualizarUno(${id}, '${apiNombres.replace(/'/g,"\\'")}', '${apiApellidos.replace(/'/g,"\\'")}', this)"
                         class="px-3 py-1 text-xs bg-amber-500 text-white rounded hover:bg-amber-600 transition">
                    Actualizar
                 </button>`;
        }
    } catch (e) {
        apiCell.innerHTML = '<span class="text-red-400">Error de red</span>';
    }
}

// ── Actualiza nombre de un abonado ───────────────────────────────────
async function actualizarUno(id, nombres, apellidos, btn) {
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const fd = new FormData();
    fd.append('action', 'actualizar');
    fd.append('id', id);
    fd.append('nombres', nombres);
    fd.append('apellidos', apellidos);

    try {
        const res  = await fetch(UPDATE_URL, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.ok) {
            btn.parentElement.innerHTML = '<span class="text-green-600 text-xs font-medium">✓ Actualizado</span>';
            // Actualizar celdas de la fila
            const row = document.getElementById('row-' + id);
            row.cells[3].textContent = nombres;
            row.cells[4].textContent = apellidos;
            row.dataset.nombres  = nombres;
            row.dataset.apellidos = apellidos;
        } else {
            btn.disabled = false;
            btn.textContent = 'Error – reintentar';
        }
    } catch(e) {
        btn.disabled = false;
        btn.textContent = 'Error – reintentar';
    }
}

// ── Verifica todos de forma secuencial con pausa de 1s ───────────────
document.getElementById('btnVerificarTodos').addEventListener('click', async () => {
    stopRequested = false;
    diferentesIds = [];

    document.getElementById('btnVerificarTodos').disabled = true;
    document.getElementById('btnDetener').disabled = false;
    document.getElementById('btnDetener').classList.remove('cursor-not-allowed','bg-gray-200','text-gray-500');
    document.getElementById('btnDetener').classList.add('bg-red-500','text-white','hover:bg-red-600');

    const filas = [...document.querySelectorAll('#tablaBody tr')];
    let procesados = 0;

    for (const fila of filas) {
        if (stopRequested) break;

        const id = parseInt(fila.dataset.id);
        await verificarUno(id);
        procesados++;

        const pct = Math.round((procesados / filas.length) * 100);
        document.getElementById('progressBar').style.width = pct + '%';
        document.getElementById('progressLabel').textContent =
            `Verificando ${procesados} de ${filas.length}...`;
        document.getElementById('statsText').textContent =
            `${procesados}/${filas.length} verificados`;

        await new Promise(r => setTimeout(r, 1000)); // 1 segundo entre consultas
    }

    document.getElementById('progressLabel').textContent = stopRequested
        ? `Detenido en ${procesados} de ${filas.length}`
        : `✓ Verificación completa – ${procesados} abonados revisados`;

    document.getElementById('btnVerificarTodos').disabled = false;
    document.getElementById('btnDetener').disabled = true;
    document.getElementById('btnDetener').classList.add('cursor-not-allowed','bg-gray-200','text-gray-500');
    document.getElementById('btnDetener').classList.remove('bg-red-500','text-white','hover:bg-red-600');

    if (diferentesIds.length > 0) {
        document.getElementById('btnActualizarDiferentes').classList.remove('hidden');
        document.getElementById('btnActualizarDiferentes').textContent =
            `↑ Actualizar los ${diferentesIds.length} con nombre diferente`;
    }
});

document.getElementById('btnDetener').addEventListener('click', () => {
    stopRequested = true;
});

// ── Actualizar todos los diferentes de golpe ─────────────────────────
document.getElementById('btnActualizarDiferentes').addEventListener('click', async function() {
    this.disabled = true;
    this.textContent = 'Actualizando...';

    for (const {id, apiNombres, apiApellidos} of diferentesIds) {
        const btnCell = document.getElementById('btn-' + id);
        const btn = btnCell.querySelector('button');
        if (btn) await actualizarUno(id, apiNombres, apiApellidos, btn);
        await new Promise(r => setTimeout(r, 300));
    }

    this.textContent = '✓ Todos actualizados';
});
</script>
</body>
</html>
