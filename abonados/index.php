<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Abonados';
$activePage = 'abonados';

$pdo = getDB();

// ── Filtros ────────────────────────────────────────────────────────────────
$buscar = trim($_GET['buscar'] ?? '');
$zona   = $_GET['zona']   ?? '';
$estado = $_GET['estado'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
if ($buscar !== '') {
    $where[]  = "(a.dni LIKE ? OR a.nombres LIKE ? OR a.apellidos LIKE ? OR a.codigo LIKE ?)";
    $like     = "%$buscar%";
    $params   = array_merge($params, [$like,$like,$like,$like]);
}
if ($zona !== '') {
    $where[]  = "a.zona = ?";
    $params[] = $zona;
}
if ($estado !== '') {
    $where[]  = "a.estado = ?";
    $params[] = $estado;
}
$whereStr = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM abonados a WHERE $whereStr");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $limit));

$stmt = $pdo->prepare("
    SELECT a.*,
           (SELECT COUNT(*) FROM pagos p WHERE p.abonado_id = a.id) AS total_pagos
    FROM abonados a
    WHERE $whereStr
    ORDER BY a.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$abonados = $stmt->fetchAll();

// Datos para el botón de pago rápido semestre 1
$periodoS1 = $pdo->query(
    "SELECT id, nombre, anio FROM periodos_cobro
     WHERE semestre = '1' AND estado IN ('activo','pendiente')
     ORDER BY anio DESC LIMIT 1"
)->fetch() ?: null;
$conceptoTarifa = $periodoS1
    ? ($pdo->query("SELECT id FROM conceptos WHERE tipo = 'tarifa_mensual' AND activo = 1 LIMIT 1")->fetch() ?: null)
    : null;
$urlPagoS1Base = ($periodoS1 && $conceptoTarifa)
    ? APP_URL . '/pagos/registrar.php?concepto_id=' . $conceptoTarifa['id']
      . '&periodo_id=' . $periodoS1['id'] . '&monto=60&abonado_id='
    : null;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Abonados</h1>
        <p class="text-sm text-gray-400"><?= number_format($totalRows) ?> registros encontrados</p>
      </div>
      <a href="<?= APP_URL ?>/abonados/crear.php"
         class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium
                px-4 py-2.5 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Nuevo Abonado
      </a>
    </div>

    <!-- Flash -->
    <?php $msg = flash('success'); if ($msg): ?>
      <div class="px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php $msg = flash('error'); if ($msg): ?>
      <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <input name="buscar" value="<?= e($buscar) ?>" placeholder="DNI, nombres, apellidos, código…"
               class="flex-1 px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        <select name="zona" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <option value="">Todas las zonas</option>
          <?php foreach (ZONAS as $k => $v): ?>
            <option value="<?= e($k) ?>" <?= $zona === $k ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="estado" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <option value="">Todos los estados</option>
          <?php foreach (ESTADOS_ABONADO as $k => $v): ?>
            <option value="<?= e($k) ?>" <?= $estado === $k ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
          Buscar
        </button>
        <a href="<?= APP_URL ?>/abonados/index.php"
           class="border border-gray-200 text-gray-500 hover:text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
          Limpiar
        </a>
      </div>
    </form>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
            <tr>
              <th class="px-5 py-3 text-left">Código</th>
              <th class="px-5 py-3 text-left">DNI</th>
              <th class="px-5 py-3 text-left">Abonado</th>
              <th class="px-5 py-3 text-left">Zona</th>
              <th class="px-5 py-3 text-left">Estado</th>
              <th class="px-5 py-3 text-right">Pagos</th>
              <th class="px-5 py-3 text-left">Inscripción</th>
              <th class="px-5 py-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($abonados as $a): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 font-mono text-xs text-gray-500"><?= e($a['codigo']) ?></td>
              <td class="px-5 py-3 font-mono text-gray-700"><?= e($a['dni']) ?></td>
              <td class="px-5 py-3">
                <div class="font-medium text-gray-800"><?= e($a['apellidos'] . ', ' . $a['nombres']) ?></div>
                <div class="text-xs text-gray-400"><?= e($a['distrito'] . ' – ' . $a['provincia']) ?></div>
              </td>
              <td class="px-5 py-3">
                <span class="capitalize text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                  <?= e(ZONAS[$a['zona']] ?? $a['zona']) ?>
                </span>
              </td>
              <td class="px-5 py-3">
                <?php
                  $estadoClases = [
                    'activo'     => 'bg-green-50 text-green-700',
                    'inactivo'   => 'bg-gray-100 text-gray-500',
                    'suspendido' => 'bg-red-50 text-red-600',
                  ];
                  $cls = $estadoClases[$a['estado']] ?? 'bg-gray-100 text-gray-500';
                ?>
                <span class="<?= $cls ?> text-xs font-medium px-2 py-0.5 rounded-full capitalize">
                  <?= e(ESTADOS_ABONADO[$a['estado']] ?? $a['estado']) ?>
                </span>
              </td>
              <td class="px-5 py-3 text-right font-semibold text-gray-700"><?= (int)$a['total_pagos'] ?></td>
              <td class="px-5 py-3 text-xs text-gray-400">
                <?= $a['fecha_inscripcion'] ? date('d/m/Y', strtotime($a['fecha_inscripcion'])) : '—' ?>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <a href="<?= APP_URL ?>/abonados/ver.php?id=<?= $a['id'] ?>"
                     title="Ver" class="p-1.5 rounded-lg hover:bg-teal-50 text-gray-400 hover:text-teal-600 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                  </a>
                  <a href="<?= APP_URL ?>/abonados/editar.php?id=<?= $a['id'] ?>"
                     title="Editar" class="p-1.5 rounded-lg hover:bg-blue-50 text-gray-400 hover:text-blue-600 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                    </svg>
                  </a>
                  <a href="<?= APP_URL ?>/pagos/registrar.php?abonado_id=<?= $a['id'] ?>"
                     title="Registrar pago" class="p-1.5 rounded-lg hover:bg-green-50 text-gray-400 hover:text-green-600 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                  </a>
                  <?php if ($urlPagoS1Base): ?>
                  <a href="<?= $urlPagoS1Base . $a['id'] ?>"
                     title="Pago Semestre 1 <?= (int)$periodoS1['anio'] ?>"
                     class="p-1.5 rounded-lg hover:bg-emerald-50 text-gray-400 hover:text-emerald-600 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                    </svg>
                  </a>
                  <?php endif; ?>
                  <a href="<?= APP_URL ?>/abonados/eliminar.php?id=<?= $a['id'] ?>"
                     title="Eliminar"
                     onclick="return confirm('¿Eliminar abonado <?= e(addslashes($a['apellidos'] . ' ' . $a['nombres'])) ?>?\nEsta acción no se puede deshacer.')"
                     class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($abonados)): ?>
              <tr><td colspan="8" class="px-5 py-12 text-center text-gray-400">No se encontraron abonados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <?php if ($totalPages > 1): ?>
      <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50 text-xs text-gray-500">
        <span>Página <?= $page ?> de <?= $totalPages ?></span>
        <div class="flex gap-1">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?buscar=<?= urlencode($buscar) ?>&zona=<?= urlencode($zona) ?>&estado=<?= urlencode($estado) ?>&page=<?= $i ?>"
               class="px-3 py-1 rounded-lg <?= $i === $page ? 'bg-brand-600 text-white' : 'hover:bg-gray-200' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
