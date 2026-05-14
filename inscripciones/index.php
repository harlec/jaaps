<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Inscripciones';
$activePage = 'inscripciones';
$pdo        = getDB();

$buscar = trim($_GET['buscar'] ?? '');
$estado = $_GET['estado'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
if ($buscar !== '') {
    $where[]  = "(a.dni LIKE ? OR a.nombres LIKE ? OR a.apellidos LIKE ?)";
    $like     = "%$buscar%";
    $params   = array_merge($params, [$like,$like,$like]);
}
if ($estado !== '') { $where[] = "i.estado = ?"; $params[] = $estado; }
$whereStr = implode(' AND ', $where);

$totalRows = (int)$pdo->prepare("SELECT COUNT(*) FROM inscripciones i JOIN abonados a ON a.id=i.abonado_id WHERE $whereStr")->execute($params) ? (function() use ($pdo,$whereStr,$params){ $s = $pdo->prepare("SELECT COUNT(*) FROM inscripciones i JOIN abonados a ON a.id=i.abonado_id WHERE $whereStr"); $s->execute($params); return (int)$s->fetchColumn(); })() : 0;

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM inscripciones i JOIN abonados a ON a.id=i.abonado_id WHERE $whereStr");
$stmtTotal->execute($params);
$totalRows  = (int)$stmtTotal->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $limit));

$stmt = $pdo->prepare("
    SELECT i.*, a.dni, a.nombres, a.apellidos, a.codigo, a.zona
    FROM inscripciones i
    JOIN abonados a ON a.id = i.abonado_id
    WHERE $whereStr
    ORDER BY i.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$inscripciones = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>
  <main class="flex-1 overflow-y-auto p-6 space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Inscripciones</h1>
        <p class="text-sm text-gray-400"><?= number_format($totalRows) ?> registros</p>
      </div>
      <a href="<?= APP_URL ?>/inscripciones/registrar.php"
         class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Nueva Inscripción
      </a>
    </div>

    <?php $msg = flash('success'); if ($msg): ?>
      <div class="px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <input name="buscar" value="<?= e($buscar) ?>" placeholder="DNI, nombres, apellidos…"
               class="flex-1 px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
        <select name="estado" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          <option value="">Todos los estados</option>
          <?php foreach (['pendiente'=>'Pendiente','aprobada'=>'Aprobada','rechazada'=>'Rechazada','cancelada'=>'Cancelada'] as $k=>$v): ?>
            <option value="<?= e($k) ?>" <?= $estado === $k ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Filtrar</button>
        <a href="<?= APP_URL ?>/inscripciones/index.php" class="border border-gray-200 text-sm text-gray-500 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">Limpiar</a>
      </div>
    </form>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
            <tr>
              <th class="px-5 py-3 text-left">N° Solicitud</th>
              <th class="px-5 py-3 text-left">Abonado</th>
              <th class="px-5 py-3 text-left">Zona</th>
              <th class="px-5 py-3 text-left">Fecha</th>
              <th class="px-5 py-3 text-right">Monto</th>
              <th class="px-5 py-3 text-left">Estado</th>
              <th class="px-5 py-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($inscripciones as $ins): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3 font-mono text-xs text-gray-500"><?= e($ins['numero_solicitud'] ?? '—') ?></td>
              <td class="px-5 py-3">
                <a href="<?= APP_URL ?>/abonados/ver.php?id=<?= $ins['abonado_id'] ?>"
                   class="font-medium text-gray-800 hover:text-brand-600">
                  <?= e($ins['apellidos'] . ', ' . $ins['nombres']) ?>
                </a>
                <div class="text-xs text-gray-400"><?= e($ins['codigo']) ?> · DNI <?= e($ins['dni']) ?></div>
              </td>
              <td class="px-5 py-3 text-xs text-gray-500 capitalize"><?= e(ZONAS[$ins['zona']] ?? $ins['zona']) ?></td>
              <td class="px-5 py-3 text-gray-500"><?= date('d/m/Y', strtotime($ins['fecha_inscripcion'])) ?></td>
              <td class="px-5 py-3 text-right font-semibold text-gray-800">S/ <?= number_format((float)$ins['monto'], 2) ?></td>
              <td class="px-5 py-3">
                <?php $statusCls = ['pendiente'=>'bg-yellow-50 text-yellow-700','aprobada'=>'bg-green-50 text-green-700','rechazada'=>'bg-red-50 text-red-600','cancelada'=>'bg-gray-100 text-gray-500']; ?>
                <span class="<?= $statusCls[$ins['estado']] ?? '' ?> text-xs font-medium px-2 py-0.5 rounded-full capitalize">
                  <?= e($ins['estado']) ?>
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex justify-center gap-2">
                  <a href="<?= APP_URL ?>/inscripciones/registrar.php?id=<?= $ins['id'] ?>"
                     class="p-1.5 rounded-lg hover:bg-blue-50 text-gray-400 hover:text-blue-600 transition" title="Editar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($inscripciones)): ?>
              <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No hay inscripciones registradas.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
