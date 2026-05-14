<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Pagos';
$activePage = 'pagos';
$pdo        = getDB();

// Filtros
$buscar  = trim($_GET['buscar'] ?? '');
$zona    = $_GET['zona']    ?? '';
$anio    = (int)($_GET['anio']   ?? date('Y'));
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = 15;
$offset  = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
if ($buscar !== '') {
    $where[]  = "(a.dni LIKE ? OR a.nombres LIKE ? OR a.apellidos LIKE ? OR p.numero_recibo LIKE ?)";
    $like     = "%$buscar%";
    $params   = array_merge($params, [$like,$like,$like,$like]);
}
if ($zona !== '') { $where[] = "a.zona = ?"; $params[] = $zona; }
if ($anio > 0)    { $where[] = "YEAR(p.fecha_pago) = ?"; $params[] = $anio; }
$whereStr = implode(' AND ', $where);

$totalStmt = $pdo->prepare("
    SELECT COUNT(*) FROM pagos p
    JOIN abonados a ON a.id = p.abonado_id
    WHERE $whereStr
");
$totalStmt->execute($params);
$totalRows  = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $limit));

$stmt = $pdo->prepare("
    SELECT p.*, CONCAT(a.apellidos,' ',a.nombres) AS abonado,
           a.codigo, a.zona, a.id AS aid,
           c.nombre AS concepto, pc.nombre AS periodo
    FROM pagos p
    JOIN abonados a   ON a.id  = p.abonado_id
    JOIN conceptos c  ON c.id  = p.concepto_id
    LEFT JOIN periodos_cobro pc ON pc.id = p.periodo_id
    WHERE $whereStr
    ORDER BY p.fecha_pago DESC, p.id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$pagos = $stmt->fetchAll();

// Total recaudado en filtro
$sumStmt = $pdo->prepare("
    SELECT COALESCE(SUM(p.monto_total),0) FROM pagos p
    JOIN abonados a ON a.id = p.abonado_id
    WHERE $whereStr
");
$sumStmt->execute($params);
$totalMonto = (float)$sumStmt->fetchColumn();

// Años disponibles
$anios = $pdo->query("SELECT DISTINCT YEAR(fecha_pago) AS y FROM pagos ORDER BY y DESC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Historial de Pagos</h1>
        <p class="text-sm text-gray-400"><?= number_format($totalRows) ?> registros &nbsp;·&nbsp;
          Total: <strong class="text-green-600">S/ <?= number_format($totalMonto, 2) ?></strong></p>
      </div>
      <a href="<?= APP_URL ?>/pagos/registrar.php"
         class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium
                px-4 py-2.5 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Registrar Pago
      </a>
    </div>

    <?php $msg = flash('success'); if ($msg): ?>
      <div class="px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <input name="buscar" value="<?= e($buscar) ?>" placeholder="DNI, nombres, apellidos, recibo…"
               class="flex-1 px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        <select name="zona" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <option value="">Todas las zonas</option>
          <?php foreach (ZONAS as $k => $v): ?>
            <option value="<?= e($k) ?>" <?= $zona === $k ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="anio" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <option value="">Todos los años</option>
          <?php foreach ($anios as $y): ?>
            <option value="<?= e((string)$y) ?>" <?= $anio === (int)$y ? 'selected' : '' ?>><?= e((string)$y) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
          Filtrar
        </button>
        <a href="<?= APP_URL ?>/pagos/index.php"
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
              <th class="px-5 py-3 text-left">Recibo</th>
              <th class="px-5 py-3 text-left">Abonado</th>
              <th class="px-5 py-3 text-left">Concepto</th>
              <th class="px-5 py-3 text-left">Período</th>
              <th class="px-5 py-3 text-left">Fecha</th>
              <th class="px-5 py-3 text-right">Monto</th>
              <th class="px-5 py-3 text-left">Método</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($pagos as $p): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 font-mono text-xs text-gray-500"><?= e($p['numero_recibo'] ?? '—') ?></td>
              <td class="px-5 py-3">
                <a href="<?= APP_URL ?>/abonados/ver.php?id=<?= $p['aid'] ?>"
                   class="font-medium text-gray-800 hover:text-brand-600"><?= e($p['abonado']) ?></a>
                <div class="text-xs text-gray-400"><?= e($p['codigo']) ?> &nbsp;·&nbsp; <?= e(ZONAS[$p['zona']] ?? $p['zona']) ?></div>
              </td>
              <td class="px-5 py-3 text-gray-600"><?= e($p['concepto']) ?></td>
              <td class="px-5 py-3 text-gray-500 text-xs"><?= e($p['periodo'] ?? '—') ?></td>
              <td class="px-5 py-3 text-gray-500"><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
              <td class="px-5 py-3 text-right font-semibold text-gray-800">S/ <?= number_format((float)$p['monto_total'], 2) ?></td>
              <td class="px-5 py-3">
                <span class="px-2 py-0.5 text-xs rounded-full
                  <?= $p['metodo_pago'] === 'efectivo' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' ?>">
                  <?= e(METODOS_PAGO[$p['metodo_pago']] ?? $p['metodo_pago']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($pagos)): ?>
              <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No hay pagos registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
      <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50 text-xs text-gray-500">
        <span>Página <?= $page ?> de <?= $totalPages ?></span>
        <div class="flex gap-1">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?buscar=<?= urlencode($buscar) ?>&zona=<?= urlencode($zona) ?>&anio=<?= $anio ?>&page=<?= $i ?>"
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
