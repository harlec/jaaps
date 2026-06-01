<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Registrar Pago';
$activePage = 'pagos';
$pdo        = getDB();
$errors     = [];

// Pre-cargar abonado si viene de ?abonado_id
$preAbonadoId  = (int)($_GET['abonado_id']  ?? $_POST['abonado_id']  ?? 0);
$preConceptoId = (int)($_GET['concepto_id'] ?? $_POST['concepto_id'] ?? 0);
$prePeriodoId  = (int)($_GET['periodo_id']  ?? $_POST['periodo_id']  ?? 0);
$preMonto      = (float)($_GET['monto']     ?? $_POST['monto']       ?? 0);
$preAbonado   = null;
if ($preAbonadoId > 0) {
    $s = $pdo->prepare("SELECT id, codigo, dni, nombres, apellidos, zona FROM abonados WHERE id = ? AND estado = 'activo'");
    $s->execute([$preAbonadoId]);
    $preAbonado = $s->fetch() ?: null;
}

// Datos para selects
$conceptos = $pdo->query("SELECT * FROM conceptos WHERE activo = 1 ORDER BY tipo, nombre")->fetchAll();
$periodos  = $pdo->query("SELECT * FROM periodos_cobro WHERE estado IN ('activo','pendiente') ORDER BY anio DESC, semestre")->fetchAll();

// Pago rápido: primer período activo/pendiente de semestre 1
$pagoRapido = null;
foreach ($periodos as $p) {
    if ($p['semestre'] === '1') {
        foreach ($conceptos as $c) {
            if ($c['tipo'] === 'tarifa_mensual') {
                $pagoRapido = ['periodo' => $p, 'concepto' => $c, 'monto' => 60.00];
                break 2;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $abonadoId  = (int)($_POST['abonado_id']  ?? 0);
    $conceptoId = (int)($_POST['concepto_id'] ?? 0);
    $periodoId  = (int)($_POST['periodo_id']  ?? 0) ?: null;
    $monto      = (float)str_replace(',', '.', $_POST['monto'] ?? '0');
    $descuento  = (float)str_replace(',', '.', $_POST['descuento'] ?? '0');
    $interes    = (float)str_replace(',', '.', $_POST['interes']   ?? '0');
    $fechaPago  = trim($_POST['fecha_pago']   ?? date('Y-m-d'));
    $metodo     = trim($_POST['metodo_pago']  ?? 'efectivo');
    $referencia = trim($_POST['referencia']   ?? '');
    $observacion= trim($_POST['observacion']  ?? '');

    $total = round($monto - $descuento + $interes, 2);

    if ($abonadoId <= 0)    $errors[] = 'Seleccione un abonado.';
    if ($conceptoId <= 0)   $errors[] = 'Seleccione un concepto de pago.';
    if ($monto <= 0)        $errors[] = 'El monto debe ser mayor a cero.';
    if (!array_key_exists($metodo, METODOS_PAGO)) $errors[] = 'Método de pago inválido.';
    if (!strtotime($fechaPago)) $errors[] = 'Fecha de pago inválida.';

    if (empty($errors)) {
        try {
            $numeroRecibo = generarNumeroRecibo();
            $ins = $pdo->prepare("
                INSERT INTO pagos
                  (abonado_id, concepto_id, periodo_id, numero_recibo,
                   monto, descuento, interes, monto_total,
                   fecha_pago, metodo_pago, referencia, observacion, registrado_por)
                VALUES
                  (:abonado_id, :concepto_id, :periodo_id, :numero_recibo,
                   :monto, :descuento, :interes, :monto_total,
                   :fecha_pago, :metodo_pago, :referencia, :observacion, :registrado_por)
            ");
            $ins->execute([
                ':abonado_id'   => $abonadoId,
                ':concepto_id'  => $conceptoId,
                ':periodo_id'   => $periodoId,
                ':numero_recibo'=> $numeroRecibo,
                ':monto'        => $monto,
                ':descuento'    => $descuento,
                ':interes'      => $interes,
                ':monto_total'  => $total,
                ':fecha_pago'   => $fechaPago,
                ':metodo_pago'  => $metodo,
                ':referencia'   => $referencia,
                ':observacion'  => $observacion,
                ':registrado_por' => currentUser()['id'],
            ]);

            flash('success', "Pago $numeroRecibo registrado correctamente por S/ " . number_format($total, 2));
            redirect(APP_URL . '/pagos/index.php');
        } catch (PDOException $ex) {
            $errors[] = 'Error al registrar pago: ' . $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col flex-1 overflow-hidden">
  <?php require_once __DIR__ . '/../includes/topbar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6">

    <div class="flex items-center gap-2 text-xs text-gray-400 mb-5">
      <a href="<?= APP_URL ?>/pagos/index.php" class="hover:text-brand-600">Pagos</a>
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
      <span class="text-gray-600 font-medium">Registrar Pago</span>
    </div>

    <?php if ($errors): ?>
      <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        <strong>Errores:</strong>
        <ul class="mt-1 list-disc list-inside">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action=""
          x-data="{
            monto: <?= $preMonto > 0 ? $preMonto : (float)(TARIFA_MENSUAL * 6) ?>,
            descuento: <?= (float)($_POST['descuento'] ?? 0) ?>,
            interes: <?= (float)($_POST['interes'] ?? 0) ?>,
            get total() { return Math.max(0, parseFloat(this.monto||0) - parseFloat(this.descuento||0) + parseFloat(this.interes||0)).toFixed(2); }
          }"
          class="max-w-3xl space-y-5">

      <!-- Buscador de abonado -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Abonado</h2></div>
        <div class="p-5">
          <?php if ($preAbonado): ?>
            <input type="hidden" name="abonado_id" value="<?= $preAbonado['id'] ?>">
            <div class="flex items-center gap-3 p-3 rounded-xl bg-brand-50 border border-brand-100">
              <div class="w-9 h-9 rounded-lg bg-brand-600 flex items-center justify-center text-white text-sm font-bold">
                <?= e(mb_strtoupper(mb_substr($preAbonado['nombres'], 0,1) . mb_substr($preAbonado['apellidos'], 0,1))) ?>
              </div>
              <div>
                <p class="font-semibold text-brand-800 text-sm"><?= e($preAbonado['apellidos'] . ', ' . $preAbonado['nombres']) ?></p>
                <p class="text-xs text-brand-600"><?= e($preAbonado['codigo']) ?> &nbsp;·&nbsp; DNI: <?= e($preAbonado['dni']) ?> &nbsp;·&nbsp; <?= e(ZONAS[$preAbonado['zona']] ?? $preAbonado['zona']) ?></p>
              </div>
              <a href="<?= APP_URL ?>/pagos/registrar.php" class="ml-auto text-xs text-gray-400 hover:text-gray-600">Cambiar</a>
            </div>
          <?php else: ?>
            <div id="abonadoSearch">
              <label class="block text-xs font-medium text-gray-600 mb-1">Buscar por DNI, nombre o código</label>
              <div class="flex gap-2">
                <input id="buscarAbonado" type="text" placeholder="Ej: 12345678 o García…"
                       class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              </div>
              <div id="resultados" class="mt-2 space-y-1 hidden"></div>
              <input type="hidden" name="abonado_id" id="abonadoIdInput" value="<?= (int)($_POST['abonado_id'] ?? 0) ?>">
              <p id="abonadoSeleccionado" class="text-xs text-green-600 mt-1 hidden"></p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($pagoRapido): ?>
      <!-- Pago rápido semestral -->
      <div class="flex items-center justify-between gap-4 px-5 py-4 rounded-2xl bg-emerald-50 border border-emerald-200">
        <div>
          <p class="text-sm font-semibold text-emerald-800">Pago Semestre 1 <?= (int)$pagoRapido['periodo']['anio'] ?></p>
          <p class="text-xs text-emerald-600 mt-0.5">
            <?= e($pagoRapido['periodo']['nombre']) ?> &nbsp;·&nbsp; S/ 10.00 × 6 meses = <strong>S/ 60.00</strong>
          </p>
        </div>
        <button type="button"
                data-concepto="<?= (int)$pagoRapido['concepto']['id'] ?>"
                data-periodo="<?= (int)$pagoRapido['periodo']['id'] ?>"
                data-monto="60"
                onclick="aplicarPagoRapido(this)"
                class="shrink-0 px-4 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">
          Registrar Pago Semestre 1 <?= (int)$pagoRapido['periodo']['anio'] ?>
        </button>
      </div>
      <?php endif; ?>

      <!-- Concepto y período -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Concepto de Pago</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Concepto <span class="text-red-500">*</span></label>
            <select name="concepto_id" required
                    onchange="const c = this.selectedOptions[0]?.dataset.monto; if(c) document.getElementById('inputMonto').value = c;"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <option value="">Seleccionar…</option>
              <?php foreach ($conceptos as $c): ?>
                <option value="<?= $c['id'] ?>"
                        data-monto="<?= $c['monto'] ?>"
                        <?= $preConceptoId === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= e($c['nombre']) ?> (S/ <?= number_format((float)$c['monto'], 2) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Período de cobro</label>
            <select name="periodo_id"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <option value="">Sin período</option>
              <?php foreach ($periodos as $p): ?>
                <option value="<?= $p['id'] ?>"
                        <?= $prePeriodoId === (int)$p['id'] ? 'selected' : '' ?>>
                  <?= e($p['nombre']) ?> – S/ <?= number_format((float)$p['monto_total'], 2) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Montos -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Montos</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Monto base (S/.) <span class="text-red-500">*</span></label>
            <input id="inputMonto" name="monto" type="number" step="0.01" min="0.01"
                   x-model="monto" required
                   value="<?= number_format($preMonto > 0 ? $preMonto : TARIFA_MENSUAL * 6, 2) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Descuento (S/.)</label>
            <input name="descuento" type="number" step="0.01" min="0"
                   x-model="descuento"
                   value="<?= number_format((float)($_POST['descuento'] ?? 0), 2) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Interés / mora (S/.)</label>
            <input name="interes" type="number" step="0.01" min="0"
                   x-model="interes"
                   value="<?= number_format((float)($_POST['interes'] ?? 0), 2) ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="bg-brand-50 rounded-xl px-4 py-3 border border-brand-100">
            <p class="text-xs text-brand-600 font-medium">Total a pagar</p>
            <p class="text-2xl font-bold text-brand-700" x-text="'S/ ' + total"></p>
          </div>
        </div>
      </div>

      <!-- Pago -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="text-sm font-semibold text-gray-700">Datos del Pago</h2></div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de pago <span class="text-red-500">*</span></label>
            <input name="fecha_pago" type="date" value="<?= e($_POST['fecha_pago'] ?? date('Y-m-d')) ?>" required
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Método de pago</label>
            <select name="metodo_pago"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
              <?php foreach (METODOS_PAGO as $k => $v): ?>
                <option value="<?= e($k) ?>" <?= ($_POST['metodo_pago'] ?? 'efectivo') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">N° operación / voucher</label>
            <input name="referencia" type="text" value="<?= e($_POST['referencia'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300">
          </div>
          <div class="sm:col-span-3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
            <textarea name="observacion" rows="2"
                      class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"><?= e($_POST['observacion'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3 justify-end">
        <a href="<?= APP_URL ?>/pagos/index.php"
           class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
          Cancelar
        </a>
        <button type="submit"
                class="px-6 py-2.5 text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors">
          Guardar Pago
        </button>
      </div>
    </form>

  </main>
</div>

<script>
function aplicarPagoRapido(btn) {
  const conceptoSel = document.querySelector('select[name="concepto_id"]');
  const periodoSel  = document.querySelector('select[name="periodo_id"]');
  const montoInput  = document.getElementById('inputMonto');

  conceptoSel.value = btn.dataset.concepto;
  periodoSel.value  = btn.dataset.periodo;
  montoInput.value  = btn.dataset.monto;
  montoInput.dispatchEvent(new Event('input'));

  montoInput.closest('form').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Búsqueda de abonado en tiempo real
const inp = document.getElementById('buscarAbonado');
const res = document.getElementById('resultados');
const hid = document.getElementById('abonadoIdInput');
const sel = document.getElementById('abonadoSeleccionado');

if (inp) {
  let timer;
  inp.addEventListener('input', () => {
    clearTimeout(timer);
    const q = inp.value.trim();
    if (q.length < 2) { res.classList.add('hidden'); return; }
    timer = setTimeout(async () => {
      const r = await fetch('<?= APP_URL ?>/api/buscar_abonado.php?q=' + encodeURIComponent(q));
      const rows = await r.json();
      res.innerHTML = '';
      if (!rows.length) {
        res.innerHTML = '<p class="text-xs text-gray-400 px-2">Sin resultados.</p>';
      } else {
        rows.forEach(a => {
          const d = document.createElement('div');
          d.className = 'flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-brand-50 cursor-pointer border border-gray-100';
          d.innerHTML = `<span class="font-medium text-sm text-gray-700">${a.apellidos}, ${a.nombres}</span>
                         <span class="text-xs text-gray-400 ml-1">${a.codigo} · DNI ${a.dni}</span>`;
          d.addEventListener('click', () => {
            hid.value = a.id;
            sel.textContent = '✓ Abonado seleccionado: ' + a.apellidos + ', ' + a.nombres;
            sel.classList.remove('hidden');
            res.classList.add('hidden');
            inp.value = '';
          });
          res.appendChild(d);
        });
      }
      res.classList.remove('hidden');
    }, 300);
  });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
