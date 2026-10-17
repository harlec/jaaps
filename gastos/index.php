<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Gastos';
$activePage = 'gastos';
$pdo        = getDB();

// Filtros
$buscar       = trim($_GET['buscar'] ?? '');
$conceptoId   = (int)($_GET['concepto_id'] ?? 0);
$fechaDesde   = trim($_GET['fecha_desde'] ?? '');
$fechaHasta   = trim($_GET['fecha_hasta'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = 15;
$offset       = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
if ($buscar !== '') {
    $where[]  = "(g.proveedor LIKE ? OR g.numero_comprobante LIKE ? OR g.descripcion LIKE ?)";
    $like     = "%$buscar%";
    $params   = array_merge($params, [$like, $like, $like]);
}
if ($conceptoId > 0) { $where[] = "g.concepto_id = ?"; $params[] = $conceptoId; }
if ($fechaDesde !== '' && strtotime($fechaDesde)) { $where[] = "g.fecha_gasto >= ?"; $params[] = $fechaDesde; }
if ($fechaHasta !== '' && strtotime($fechaHasta)) { $where[] = "g.fecha_gasto <= ?"; $params[] = $fechaHasta; }
$whereStr = implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM gastos g WHERE $whereStr");
$totalStmt->execute($params);
$totalRows  = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $limit));

$stmt = $pdo->prepare("
    SELECT g.*, gc.nombre AS concepto
    FROM gastos g
    JOIN gasto_conceptos gc ON gc.id = g.concepto_id
    WHERE $whereStr
    ORDER BY g.fecha_gasto DESC, g.id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$gastos = $stmt->fetchAll();

$sumStmt = $pdo->prepare("SELECT COALESCE(SUM(g.monto),0) FROM gastos g WHERE $whereStr");
$sumStmt->execute($params);
$totalMonto = (float)$sumStmt->fetchColumn();

$conceptosFiltro = $pdo->query("SELECT id, nombre FROM gasto_conceptos ORDER BY nombre")->fetchAll();

$qs = http_build_query(['buscar' => $buscar, 'concepto_id' => $conceptoId, 'fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Gastos</h1>
        <p class="text-sm text-gray-400"><?= number_format($totalRows) ?> registros &nbsp;·&nbsp;
          Total: <strong class="text-red-600">S/ <?= number_format($totalMonto, 2) ?></strong></p>
      </div>
      <div class="flex items-center gap-2">
        <a href="<?= APP_URL ?>/gastos/conceptos.php"
           class="inline-flex items-center gap-2 border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
          </svg>
          Conceptos
        </a>
        <a href="<?= APP_URL ?>/gastos/registrar.php"
           class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          Nuevo Gasto
        </a>
      </div>
    </div>

    <?php $msg = flash('success'); if ($msg): ?>
      <div class="px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php $msg = flash('error'); if ($msg): ?>
      <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
      <div class="flex flex-col sm:flex-row gap-3 flex-wrap">
        <input name="buscar" value="<?= e($buscar) ?>" placeholder="Proveedor, comprobante, descripción…"
               class="flex-1 min-w-[180px] px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        <select name="concepto_id" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <option value="0">Todos los conceptos</option>
          <?php foreach ($conceptosFiltro as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $conceptoId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" name="fecha_desde" value="<?= e($fechaDesde) ?>"
               class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        <input type="date" name="fecha_hasta" value="<?= e($fechaHasta) ?>"
               class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        <button type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
          Filtrar
        </button>
        <a href="<?= APP_URL ?>/gastos/index.php"
           class="border border-gray-200 text-sm text-gray-500 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
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
              <th class="px-5 py-3 text-left">Fecha</th>
              <th class="px-5 py-3 text-left">Concepto</th>
              <th class="px-5 py-3 text-left">Proveedor</th>
              <th class="px-5 py-3 text-left">Comprobante</th>
              <th class="px-5 py-3 text-right">Monto</th>
              <th class="px-5 py-3 text-left">Método</th>
              <th class="px-5 py-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($gastos as $g): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 text-gray-500"><?= date('d/m/Y', strtotime($g['fecha_gasto'])) ?></td>
              <td class="px-5 py-3">
                <span class="font-medium text-gray-800"><?= e($g['concepto']) ?></span>
                <?php if ($g['descripcion']): ?>
                  <div class="text-xs text-gray-400"><?= e($g['descripcion']) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-gray-600"><?= e($g['proveedor'] ?? '—') ?></td>
              <td class="px-5 py-3 font-mono text-xs text-gray-500"><?= e($g['numero_comprobante'] ?? '—') ?></td>
              <td class="px-5 py-3 text-right font-semibold text-red-600">S/ <?= number_format((float)$g['monto'], 2) ?></td>
              <td class="px-5 py-3">
                <span class="px-2 py-0.5 text-xs rounded-full bg-slate-100 text-slate-600">
                  <?= e(METODOS_PAGO[$g['metodo_pago']] ?? $g['metodo_pago']) ?>
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-center gap-2">
                  <a href="<?= APP_URL ?>/gastos/recibo.php?id=<?= $g['id'] ?>" target="_blank"
                     title="Imprimir recibo"
                     class="p-1.5 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                    </svg>
                  </a>
                  <a href="<?= APP_URL ?>/gastos/editar.php?id=<?= $g['id'] ?>"
                     title="Editar gasto"
                     class="p-1.5 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                    </svg>
                  </a>
                  <?php if (currentUser()['rol'] === 'admin'): ?>
                  <a href="<?= APP_URL ?>/gastos/eliminar.php?id=<?= $g['id'] ?>"
                     title="Eliminar gasto"
                     onclick="return confirm('¿Eliminar este gasto de S/ <?= number_format((float)$g['monto'], 2) ?>? Esta acción no se puede deshacer.')"
                     class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($gastos)): ?>
              <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No hay gastos registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

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

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
