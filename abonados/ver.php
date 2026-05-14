<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect(APP_URL . '/abonados/index.php'); }

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM abonados WHERE id = ?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) { redirect(APP_URL . '/abonados/index.php'); }

// Hijos
$hijos = $pdo->prepare("SELECT * FROM hijos WHERE abonado_id = ? ORDER BY id");
$hijos->execute([$id]);
$hijosRows = $hijos->fetchAll();

// Pagos
$pagosStmt = $pdo->prepare("
    SELECT p.*, c.nombre AS concepto, pc.nombre AS periodo
    FROM pagos p
    JOIN conceptos c ON c.id = p.concepto_id
    LEFT JOIN periodos_cobro pc ON pc.id = p.periodo_id
    WHERE p.abonado_id = ?
    ORDER BY p.fecha_pago DESC
");
$pagosStmt->execute([$id]);
$pagosRows = $pagosStmt->fetchAll();

// Inscripción
$inscStmt = $pdo->prepare("SELECT * FROM inscripciones WHERE abonado_id = ? ORDER BY id DESC LIMIT 1");
$inscStmt->execute([$id]);
$inscripcion = $inscStmt->fetch();

$pageTitle  = "Abonado – {$a['apellidos']}, {$a['nombres']}";
$activePage = 'abonados';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
      <a href="<?= APP_URL ?>/abonados/index.php" class="hover:text-brand-600">Abonados</a>
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
      <span class="text-gray-600 font-medium"><?= e($a['codigo']) ?></span>
    </div>

    <!-- Encabezado del perfil -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col sm:flex-row gap-4 items-start">
      <div class="w-16 h-16 rounded-2xl bg-brand-600 flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
        <?= e(mb_strtoupper(mb_substr($a['nombres'], 0, 1) . mb_substr($a['apellidos'], 0, 1))) ?>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-3 flex-wrap">
          <h1 class="text-lg font-bold text-gray-800"><?= e($a['apellidos'] . ', ' . $a['nombres']) ?></h1>
          <?php
            $cls = ['activo'=>'bg-green-50 text-green-700','inactivo'=>'bg-gray-100 text-gray-500','suspendido'=>'bg-red-50 text-red-600'];
          ?>
          <span class="<?= $cls[$a['estado']] ?? '' ?> text-xs font-medium px-2 py-0.5 rounded-full capitalize">
            <?= e(ESTADOS_ABONADO[$a['estado']] ?? $a['estado']) ?>
          </span>
        </div>
        <p class="text-sm text-gray-400 mt-0.5">
          DNI: <strong class="text-gray-600 font-mono"><?= e($a['dni']) ?></strong> &nbsp;·&nbsp;
          Código: <strong class="text-gray-600"><?= e($a['codigo']) ?></strong>
        </p>
        <p class="text-xs text-gray-400 mt-1">
          <?= e($a['distrito'] . ', ' . $a['provincia'] . ', ' . $a['departamento']) ?>
          &nbsp;·&nbsp; Zona: <strong><?= e(ZONAS[$a['zona']] ?? $a['zona']) ?></strong>
        </p>
      </div>
      <div class="flex gap-2 flex-shrink-0">
        <a href="<?= APP_URL ?>/abonados/editar.php?id=<?= $id ?>"
           class="px-4 py-2 text-xs font-medium border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-gray-600">
          Editar
        </a>
        <a href="<?= APP_URL ?>/pagos/registrar.php?abonado_id=<?= $id ?>"
           class="px-4 py-2 text-xs font-medium bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors">
          Registrar pago
        </a>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

      <!-- Datos personales -->
      <div class="xl:col-span-2 space-y-5">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Datos Personales</h2></div>
          <div class="p-5 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
            <?php
            $campos = [
              ['Fecha de nacimiento', $a['fecha_nacimiento'] ? date('d/m/Y', strtotime($a['fecha_nacimiento'])) : '—'],
              ['Estado civil',        ESTADOS_CIVILES[$a['estado_civil']] ?? $a['estado_civil']],
              ['Grado de instrucción',GRADOS_INSTRUCCION[$a['grado_instruccion']] ?? $a['grado_instruccion']],
              ['Profesión',           $a['profesion'] ?: '—'],
              ['Actividad',           $a['actividad'] ?: '—'],
              ['Número de hijos',     $a['numero_hijos']],
              ['Teléfono',            $a['telefono'] ?: '—'],
              ['Correo',              $a['email'] ?: '—'],
              ['Dirección',           $a['direccion'] ?: '—'],
              ['Inscripción',         $a['fecha_inscripcion'] ? date('d/m/Y', strtotime($a['fecha_inscripcion'])) : '—'],
            ];
            foreach ($campos as [$label, $val]):
            ?>
              <div>
                <p class="text-xs text-gray-400"><?= e($label) ?></p>
                <p class="font-medium text-gray-700 mt-0.5"><?= e((string)$val) ?></p>
              </div>
            <?php endforeach; ?>
            <?php if ($a['observaciones']): ?>
            <div class="col-span-full">
              <p class="text-xs text-gray-400">Observaciones</p>
              <p class="font-medium text-gray-700 mt-0.5"><?= e($a['observaciones']) ?></p>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Hijos -->
        <?php if (!empty($hijosRows)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Hijos</h2></div>
          <div class="divide-y divide-gray-50">
            <?php foreach ($hijosRows as $h): ?>
            <div class="px-5 py-3 flex justify-between text-sm">
              <span class="font-medium text-gray-700"><?= e($h['nombres']) ?></span>
              <span class="text-gray-400 text-xs">
                <?= $h['fecha_nacimiento'] ? date('d/m/Y', strtotime($h['fecha_nacimiento'])) : '—' ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Historial de pagos -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Historial de Pagos</h2>
            <a href="<?= APP_URL ?>/pagos/registrar.php?abonado_id=<?= $id ?>"
               class="text-xs font-medium text-brand-600 hover:underline">+ Nuevo pago</a>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                  <th class="px-5 py-3 text-left">Recibo</th>
                  <th class="px-5 py-3 text-left">Concepto</th>
                  <th class="px-5 py-3 text-left">Período</th>
                  <th class="px-5 py-3 text-left">Fecha</th>
                  <th class="px-5 py-3 text-right">Monto</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <?php foreach ($pagosRows as $p): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-5 py-3 font-mono text-xs text-gray-500"><?= e($p['numero_recibo'] ?? '—') ?></td>
                  <td class="px-5 py-3 text-gray-700"><?= e($p['concepto']) ?></td>
                  <td class="px-5 py-3 text-gray-500"><?= e($p['periodo'] ?? '—') ?></td>
                  <td class="px-5 py-3 text-gray-500"><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                  <td class="px-5 py-3 text-right font-semibold">S/ <?= number_format((float)$p['monto_total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pagosRows)): ?>
                  <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">Sin pagos registrados.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /col-span-2 -->

      <!-- Sidebar derecho: resumen -->
      <div class="space-y-5">

        <!-- Totales -->
        <?php
          $totalPagado  = array_sum(array_column($pagosRows, 'monto_total'));
          $tarifa       = TARIFA_MENSUAL * 12;
          $deuda        = max(0, $tarifa - $totalPagado);
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
          <h3 class="text-sm font-semibold text-gray-700">Resumen Financiero <?= date('Y') ?></h3>
          <div>
            <p class="text-xs text-gray-400">Total pagado</p>
            <p class="text-2xl font-bold text-green-600">S/ <?= number_format($totalPagado, 2) ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400">Tarifa anual (S/.12 × 12 meses)</p>
            <p class="text-lg font-semibold text-gray-700">S/ <?= number_format($tarifa, 2) ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400">Saldo pendiente estimado</p>
            <p class="text-lg font-bold <?= $deuda > 0 ? 'text-red-500' : 'text-green-600' ?>">
              S/ <?= number_format($deuda, 2) ?>
            </p>
          </div>
        </div>

        <!-- Inscripción -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Inscripción</h3>
          <?php if ($inscripcion): ?>
            <p class="text-xs text-gray-400">Fecha</p>
            <p class="font-medium text-gray-700"><?= date('d/m/Y', strtotime($inscripcion['fecha_inscripcion'])) ?></p>
            <p class="text-xs text-gray-400 mt-2">Monto</p>
            <p class="font-medium text-gray-700">S/ <?= number_format((float)$inscripcion['monto'], 2) ?></p>
            <p class="text-xs text-gray-400 mt-2">Estado</p>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full capitalize
              <?= ['pendiente'=>'bg-yellow-50 text-yellow-700','aprobada'=>'bg-green-50 text-green-700','rechazada'=>'bg-red-50 text-red-600','cancelada'=>'bg-gray-100 text-gray-500'][$inscripcion['estado']] ?? '' ?>">
              <?= e($inscripcion['estado']) ?>
            </span>
          <?php else: ?>
            <p class="text-xs text-gray-400">Sin inscripción registrada.</p>
            <a href="<?= APP_URL ?>/inscripciones/registrar.php?abonado_id=<?= $id ?>"
               class="mt-3 inline-flex text-xs text-brand-600 hover:underline font-medium">
              Registrar inscripción →
            </a>
          <?php endif; ?>
        </div>

      </div>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
