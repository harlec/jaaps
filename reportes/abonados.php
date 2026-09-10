<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Reporte de Abonados';
$activePage = 'reporte_abonados';
$pdo        = getDB();

// Períodos disponibles para filtrar el estado de pago
$periodos = $pdo->query(
    "SELECT id, nombre, anio, semestre, monto_total
     FROM periodos_cobro
     ORDER BY anio DESC, semestre"
)->fetchAll();

$periodoId = (int)($_GET['periodo_id'] ?? 0);
if ($periodoId === 0) {
    foreach ($periodos as $p) {
        if ((int)$p['anio'] === 2026 && $p['semestre'] === '1') {
            $periodoId = (int)$p['id'];
            break;
        }
    }
    if ($periodoId === 0 && !empty($periodos)) {
        $periodoId = (int)$periodos[0]['id'];
    }
}

$periodoActual = null;
foreach ($periodos as $p) {
    if ((int)$p['id'] === $periodoId) { $periodoActual = $p; break; }
}

// ── Filtros ────────────────────────────────────────────────────────────────
$estado = $_GET['estado'] ?? '';
$pagado = $_GET['pagado'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where       = ['1=1'];
$whereParams = [];
if ($estado !== '') {
    $where[]       = 'a.estado = ?';
    $whereParams[] = $estado;
}
if ($pagado === 'si') {
    $where[]       = 'a.id IN (SELECT p.abonado_id FROM pagos p WHERE p.periodo_id = ?)';
    $whereParams[] = $periodoId;
} elseif ($pagado === 'no') {
    $where[]       = 'a.id NOT IN (SELECT p.abonado_id FROM pagos p WHERE p.periodo_id = ?)';
    $whereParams[] = $periodoId;
}
$whereStr = implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM abonados a WHERE $whereStr");
$totalStmt->execute($whereParams);
$totalRows  = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $limit));

$selectParams = array_merge([$periodoId], $whereParams);
$stmt = $pdo->prepare("
    SELECT a.id, a.codigo, a.dni, a.nombres, a.apellidos, a.zona, a.telefono,
           a.estado, a.fecha_inscripcion,
           EXISTS (SELECT 1 FROM pagos p2 WHERE p2.abonado_id = a.id AND p2.periodo_id = ?) AS pagado
    FROM abonados a
    WHERE $whereStr
    ORDER BY a.zona, a.apellidos, a.nombres
    LIMIT $limit OFFSET $offset
");
$stmt->execute($selectParams);
$abonados = $stmt->fetchAll();

// Resumen de pago (según estado filtrado, independiente del filtro "pagado")
$whereEstado       = ['1=1'];
$whereEstadoParams = [];
if ($estado !== '') {
    $whereEstado[]       = 'a.estado = ?';
    $whereEstadoParams[] = $estado;
}
$whereEstadoStr = implode(' AND ', $whereEstado);

$stmtPagados = $pdo->prepare("
    SELECT COUNT(*) FROM abonados a WHERE $whereEstadoStr
    AND a.id IN (SELECT abonado_id FROM pagos WHERE periodo_id = ?)
");
$stmtPagados->execute(array_merge($whereEstadoParams, [$periodoId]));
$totalPagados = (int)$stmtPagados->fetchColumn();

$stmtNoPagados = $pdo->prepare("
    SELECT COUNT(*) FROM abonados a WHERE $whereEstadoStr
    AND a.id NOT IN (SELECT abonado_id FROM pagos WHERE periodo_id = ?)
");
$stmtNoPagados->execute(array_merge($whereEstadoParams, [$periodoId]));
$totalNoPagados = (int)$stmtNoPagados->fetchColumn();

// Query string común para propagar los filtros a exportaciones y paginación
$qs = http_build_query(['periodo_id' => $periodoId, 'estado' => $estado, 'pagado' => $pagado]);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Reporte de Abonados</h1>
        <p class="text-sm text-gray-400">Filtra por estado del pago y situación del abonado</p>
      </div>
      <div class="flex items-center gap-2">
        <a href="<?= APP_URL ?>/reportes/abonados_excel.php?<?= $qs ?>"
           class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
          </svg>
          Exportar Excel
        </a>
        <a href="<?= APP_URL ?>/reportes/abonados_print.php?<?= $qs ?>"
           target="_blank"
           class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
          </svg>
          Generar PDF / Imprimir
        </a>
      </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <select name="periodo_id"
                class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <?php foreach ($periodos as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (int)$p['id'] === $periodoId ? 'selected' : '' ?>>
              <?= e($p['nombre']) ?> – S/ <?= number_format((float)$p['monto_total'], 2) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select name="estado"
                class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <option value="">Todos los estados</option>
          <?php foreach (ESTADOS_ABONADO as $k => $v): ?>
            <option value="<?= e($k) ?>" <?= $estado === $k ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="pagado"
                class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <option value="">Pagó o no (todos)</option>
          <option value="si" <?= $pagado === 'si' ? 'selected' : '' ?>>Pagaron</option>
          <option value="no" <?= $pagado === 'no' ? 'selected' : '' ?>>No pagaron</option>
        </select>
        <button type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
          Filtrar
        </button>
        <a href="<?= APP_URL ?>/reportes/abonados.php"
           class="border border-gray-200 text-gray-500 hover:text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
          Limpiar
        </a>
      </div>
    </form>

    <?php if ($periodoActual): ?>

    <!-- Tarjetas resumen -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">Resultados del filtro</p>
        <p class="text-2xl font-bold text-gray-700 mt-1"><?= number_format($totalRows) ?></p>
        <p class="text-xs text-gray-400 mt-0.5">abonados</p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">Pagaron</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1"><?= number_format($totalPagados) ?></p>
        <p class="text-xs text-gray-400 mt-0.5"><?= e($periodoActual['nombre']) ?></p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">No pagaron</p>
        <p class="text-2xl font-bold text-red-600 mt-1"><?= number_format($totalNoPagados) ?></p>
        <p class="text-xs text-gray-400 mt-0.5"><?= e($periodoActual['nombre']) ?></p>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <p class="text-xs text-gray-400 font-medium">Período</p>
        <p class="text-2xl font-bold text-gray-700 mt-1">S/ <?= number_format((float)$periodoActual['monto_total'], 2) ?></p>
        <p class="text-xs text-gray-400 mt-0.5"><?= e($periodoActual['nombre']) ?></p>
      </div>
    </div>

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
              <th class="px-5 py-3 text-center">Pagó</th>
              <th class="px-5 py-3 text-left">Inscripción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php if (empty($abonados)): ?>
              <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No se encontraron abonados con estos filtros.</td></tr>
            <?php endif; ?>
            <?php
              $estadoClases = [
                'activo'     => 'bg-green-50 text-green-700',
                'inactivo'   => 'bg-gray-100 text-gray-500',
                'suspendido' => 'bg-red-50 text-red-600',
              ];
            ?>
            <?php foreach ($abonados as $a): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 font-mono text-xs text-gray-500"><?= e($a['codigo']) ?></td>
              <td class="px-5 py-3 font-mono text-gray-700"><?= e($a['dni']) ?></td>
              <td class="px-5 py-3">
                <span class="font-medium text-gray-800"><?= e($a['apellidos'] . ', ' . $a['nombres']) ?></span>
                <?php if ($a['telefono']): ?>
                  <span class="block text-xs text-gray-400"><?= e($a['telefono']) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3">
                <span class="capitalize text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                  <?= e(ZONAS[$a['zona']] ?? $a['zona']) ?>
                </span>
              </td>
              <td class="px-5 py-3">
                <span class="<?= $estadoClases[$a['estado']] ?? 'bg-gray-100 text-gray-500' ?> text-xs font-medium px-2 py-0.5 rounded-full capitalize">
                  <?= e(ESTADOS_ABONADO[$a['estado']] ?? $a['estado']) ?>
                </span>
              </td>
              <td class="px-5 py-3 text-center">
                <?php if ((bool)$a['pagado']): ?>
                  <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Sí</span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-red-50 text-red-600">No</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-xs text-gray-400">
                <?= $a['fecha_inscripcion'] ? date('d/m/Y', strtotime($a['fecha_inscripcion'])) : '—' ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <?php if ($totalPages > 1): ?>
      <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50 text-xs text-gray-500">
        <span>Página <?= $page ?> de <?= $totalPages ?></span>
        <div class="flex gap-1">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?= $qs ?>&page=<?= $i ?>"
               class="px-3 py-1 rounded-lg <?= $i === $page ? 'bg-brand-600 text-white' : 'hover:bg-gray-200' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php else: ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-12 text-center text-gray-400">
        No hay períodos de cobro configurados.
      </div>
    <?php endif; ?>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
