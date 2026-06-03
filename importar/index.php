<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'cajero');

$pageTitle  = 'Importar Abonados';
$activePage = 'importar';

$errors   = [];
$warnings = [];
$imported = 0;
$skipped  = 0;

// Default zone for import
$defaultZona              = 'porvenir';
$defaultGradoInstruccion  = 'sin_instruccion';
$defaultEstadoCivil       = 'soltero';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $file = $_FILES['archivo'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error al subir el archivo. Código: ' . $file['error'];
    } elseif (!in_array($ext, ['csv', 'txt'])) {
        $errors[] = 'Solo se aceptan archivos CSV o TXT.';
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'El archivo no debe superar los 5 MB.';
    } else {
        $pdo          = getDB();
        $zona         = trim($_POST['zona'] ?? $defaultZona);
        $skipHeader   = isset($_POST['skip_header']);
        $separator    = $_POST['separator'] ?? ',';
        $consultarDNI = isset($_POST['consultar_dni']);

        $handle = fopen($file['tmp_name'], 'r');
        $row    = 0;
        $pdo->beginTransaction();

        try {
            while (($cols = fgetcsv($handle, 1000, $separator)) !== false) {
                $row++;
                if ($row === 1 && $skipHeader) continue;
                if (empty(array_filter($cols))) continue;

                // Esperado: DNI, Apellidos, Nombres, [dirección], [teléfono]
                $dni       = trim($cols[0] ?? '');
                $apellidos = trim($cols[1] ?? '');
                $nombres   = trim($cols[2] ?? '');
                $direccion = trim($cols[3] ?? '');
                $telefono  = trim($cols[4] ?? '');

                if (!preg_match('/^\d{8}$/', $dni)) {
                    $warnings[] = "Fila $row: DNI \"$dni\" inválido, omitido.";
                    $skipped++;
                    continue;
                }
                if ($nombres === '' && $apellidos === '') {
                    // Intentar consultar API si está activado
                    if ($consultarDNI) {
                        $apiResp = consultarDniMigo($dni);
                        if ($apiResp['success']) {
                            $apellidos = $apiResp['apellidos'] ?? '';
                            $nombres   = $apiResp['nombres']   ?? '';
                        }
                    }
                    if ($nombres === '' && $apellidos === '') {
                        $warnings[] = "Fila $row: DNI $dni sin nombre, omitido.";
                        $skipped++;
                        continue;
                    }
                }

                // Verificar si ya existe
                $check = $pdo->prepare("SELECT id FROM abonados WHERE dni = ?");
                $check->execute([$dni]);
                if ($check->fetch()) {
                    $warnings[] = "Fila $row: DNI $dni ya existe, omitido.";
                    $skipped++;
                    continue;
                }

                $codigo = generarCodigo();
                $pdo->prepare("
                    INSERT INTO abonados
                      (codigo, dni, nombres, apellidos, zona, direccion, telefono,
                       grado_instruccion, estado_civil, estado, creado_por)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ")->execute([
                    $codigo, $dni,
                    mb_strtoupper(trim($nombres)),
                    mb_strtoupper(trim($apellidos)),
                    $zona, $direccion, $telefono,
                    $defaultGradoInstruccion, $defaultEstadoCivil,
                    'activo', currentUser()['id'],
                ]);
                $imported++;
            }
            fclose($handle);
            $pdo->commit();
        } catch (PDOException $ex) {
            fclose($handle);
            $pdo->rollBack();
            $errors[] = 'Error de base de datos: ' . $ex->getMessage();
        }
    }
}

function consultarDniMigo(string $dni): array
{
    $ch = curl_init(SUNAT_API_URL . '/' . $dni);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET        => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . SUNAT_API_TOKEN,
            'Accept: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$body || $code !== 200) return ['success' => false];
    $data = json_decode($body, true) ?? [];
    $apellPat = strtoupper(trim($data['apellido_pat'] ?? ''));
    $apellMat = strtoupper(trim($data['apellido_mat'] ?? ''));
    $nombres  = strtoupper(trim($data['nombres']      ?? ''));
    if ($nombres === '' && $apellPat === '') return ['success' => false];
    return [
        'success'   => true,
        'nombres'   => $nombres,
        'apellidos' => trim("$apellPat $apellMat"),
    ];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>
  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <h1 class="text-lg font-bold text-gray-800">Importar Abonados</h1>
    <p class="text-sm text-gray-400 -mt-3">Carga masiva desde un archivo CSV o TXT con lista de abonados.</p>

    <!-- Resultado -->
    <?php if ($imported > 0 || !empty($warnings)): ?>
      <div class="px-4 py-4 rounded-2xl <?= $imported > 0 ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' ?>">
        <?php if ($imported > 0): ?>
          <p class="text-sm font-semibold text-green-700">✓ <?= $imported ?> abonado(s) importados correctamente.</p>
        <?php endif; ?>
        <?php if ($skipped > 0): ?>
          <p class="text-sm text-orange-600 mt-1">⚠ <?= $skipped ?> fila(s) omitidas.</p>
        <?php endif; ?>
        <?php if (!empty($warnings)): ?>
          <details class="mt-2">
            <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">Ver advertencias (<?= count($warnings) ?>)</summary>
            <ul class="mt-2 space-y-0.5 text-xs text-orange-700 list-disc list-inside">
              <?php foreach ($warnings as $w): ?><li><?= e($w) ?></li><?php endforeach; ?>
            </ul>
          </details>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

      <!-- Formulario de carga -->
      <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Subir Archivo</h2></div>
        <div class="p-5">
          <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">

            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Archivo CSV / TXT <span class="text-red-500">*</span></label>
              <input type="file" name="archivo" accept=".csv,.txt" required
                     class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                            file:text-xs file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Separador de columnas</label>
              <select name="separator"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
                <option value=","  selected>Coma (,)</option>
                <option value=";">Punto y coma (;)</option>
                <option value="	">Tabulación</option>
                <option value="|">Pipe (|)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Zona predeterminada</label>
              <select name="zona"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
                <?php foreach (ZONAS as $k => $v): ?>
                  <option value="<?= e($k) ?>"><?= e($v) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
              <input type="checkbox" name="skip_header" checked
                     class="rounded border-gray-300 text-brand-600">
              La primera fila es encabezado (omitir)
            </label>

            <label class="flex items-center gap-2 text-sm text-gray-600">
              <input type="checkbox" name="consultar_dni"
                     class="rounded border-gray-300 text-brand-600">
              Consultar migo.pe si nombre está vacío
              <span class="text-xs text-gray-400">(más lento)</span>
            </label>

            <button type="submit"
                    class="w-full px-4 py-2.5 text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors">
              Importar
            </button>
          </form>
        </div>
      </div>

      <!-- Instrucciones -->
      <div class="xl:col-span-3 space-y-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Formato del Archivo</h3>
          <p class="text-xs text-gray-500 mb-3">
            El archivo debe tener las columnas en este orden (separadas por coma u otro separador):
          </p>
          <div class="bg-gray-50 rounded-xl p-4 font-mono text-xs text-gray-700 overflow-x-auto">
            <p class="text-gray-400 mb-1"># Columna 1: DNI (8 dígitos, obligatorio)</p>
            <p class="text-gray-400 mb-1"># Columna 2: Apellidos (obligatorio)</p>
            <p class="text-gray-400 mb-1"># Columna 3: Nombres (obligatorio)</p>
            <p class="text-gray-400 mb-1"># Columna 4: Dirección (opcional)</p>
            <p class="text-gray-400 mb-3"># Columna 5: Teléfono (opcional)</p>
            <p>DNI,Apellidos,Nombres,Dirección,Teléfono</p>
            <p>12345678,GARCIA LOPEZ,JUAN CARLOS,Av. Principal 123,987654321</p>
            <p>87654321,TORRES RIOS,MARIA ELENA,Jr. Las Flores 456,</p>
            <p>11223344,QUISPE MAMANI,PEDRO,,</p>
          </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
          <h3 class="text-sm font-semibold text-amber-800 mb-2">⚠ Consideraciones</h3>
          <ul class="text-xs text-amber-700 space-y-1 list-disc list-inside">
            <li>Solo se aceptan DNIs de 8 dígitos exactos.</li>
            <li>Los abonados con DNI ya registrado serán omitidos.</li>
            <li>El campo Zona se asignará igual para todos los del archivo.</li>
            <li>Campos como profesión, estado civil, grado de instrucción se pueden editar luego individualmente.</li>
            <li>Si activas la consulta a migo.pe, necesitas tener configurado tu token en <code>config/config.php</code>.</li>
            <li>Máximo 5 MB por archivo.</li>
          </ul>
        </div>

        <!-- Descarga plantilla -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-2">Plantilla de ejemplo</h3>
          <p class="text-xs text-gray-400 mb-3">Descarga un CSV de ejemplo para usar como base:</p>
          <a href="<?= APP_URL ?>/importar/plantilla.csv"
             class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-brand-600 border border-brand-200 rounded-lg hover:bg-brand-50 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Descargar plantilla CSV
          </a>
        </div>
      </div>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
