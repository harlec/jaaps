<?php
declare(strict_types=1);

/** Convierte un entero (0–999 999 999) a letras en español. */
function numeroALetras(int $n): string
{
    if ($n === 0) return 'cero';
    $unid = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez',
             'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve',
             'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco',
             'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];
    $dec  = [3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta', 7 => 'setenta', 8 => 'ochenta', 9 => 'noventa'];
    $cent = [1 => 'ciento', 2 => 'doscientos', 3 => 'trescientos', 4 => 'cuatrocientos', 5 => 'quinientos',
             6 => 'seiscientos', 7 => 'setecientos', 8 => 'ochocientos', 9 => 'novecientos'];

    $menorMil = function (int $x) use ($unid, $dec, $cent): string {
        if ($x === 100) return 'cien';
        $out = [];
        if ($x >= 100) { $out[] = $cent[intdiv($x, 100)]; $x %= 100; }
        if ($x >= 30) {
            $t = $dec[intdiv($x, 10)];
            $out[] = $x % 10 ? "$t y " . $unid[$x % 10] : $t;
        } elseif ($x > 0) {
            $out[] = $unid[$x];
        }
        return implode(' ', $out);
    };

    $out  = [];
    $mill = intdiv($n, 1000000);
    $mil  = intdiv($n % 1000000, 1000);
    $res  = $n % 1000;
    if ($mill) $out[] = $mill === 1 ? 'un millón' : $menorMil($mill) . ' millones';
    if ($mil)  $out[] = $mil === 1 ? 'mil' : preg_replace('/uno$/', 'un', $menorMil($mil)) . ' mil';
    if ($res)  $out[] = $menorMil($res);
    return implode(' ', $out);
}

function montoALetras(float $monto): string
{
    $enteros  = (int)floor($monto);
    $centimos = (int)round(($monto - $enteros) * 100);
    $t = numeroALetras($enteros);
    return mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1) . sprintf(' con %02d/100', $centimos);
}

const MESES_ES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto',
                  'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

/** CSS del recibo (formato talonario JAAPS, A6 horizontal; 4 por hoja A4 horizontal). */
function reciboCss(): string
{
    return <<<'CSS'
    @page { size: A4 landscape; margin: 0; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; color: #2b3a9e; background: #f3f4f6; }
    .toolbar { background: #0d9488; color: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; gap: 12px; }
    .toolbar button { background: #fff; color: #0d9488; border: none; padding: 7px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; }
    .toolbar a { color: rgba(255,255,255,.85); text-decoration: none; font-size: 12px; }

    .hoja { width: 297mm; height: 210mm; background: #fff; margin: 16px auto; display: grid; grid-template-columns: 148.5mm 148.5mm; grid-template-rows: 105mm 105mm; box-shadow: 0 2px 12px rgba(0,0,0,.12); page-break-after: always; break-after: page; overflow: hidden; }
    .hoja:last-child { page-break-after: auto; break-after: auto; }
    .recibo { width: 148.5mm; height: 105mm; padding: 4mm; }
    .marco { height: 100%; border: 1.5px solid #2b3a9e; border-radius: 8px; padding: 2px; }
    .marco-int { height: 100%; border: 1px solid #2b3a9e; border-radius: 6px; padding: 2.5mm 5mm 2.5mm; display: flex; flex-direction: column; }

    .cab { text-align: center; line-height: 1.15; }
    .cab h1 { font-size: 17px; font-weight: 900; letter-spacing: -.4px; }
    .cab p  { font-size: 12px; font-weight: bold; margin-top: 2px; letter-spacing: .3px; }

    .fila-titulo { display: flex; align-items: center; justify-content: space-between; margin: 1.5mm 0 2.5mm; }
    .pill { background: #2b3a9e; color: #fff; font-weight: bold; font-size: 18px; letter-spacing: 1px; padding: 4px 24px; border-radius: 14px; }
    .numero { color: #dc2626; font-family: 'Times New Roman', serif; font-size: 18px; font-weight: bold; }
    .soles-box { min-width: 36mm; border: 1.5px solid #2b3a9e; border-radius: 6px; padding: 3px 9px; font-size: 18px; font-weight: bold; display: flex; gap: 6px; color: #111; }
    .soles-box b { color: #2b3a9e; }

    .linea { display: flex; align-items: flex-end; gap: 5px; margin-bottom: 1.8mm; font-size: 13px; }
    .linea .et { font-weight: 600; white-space: nowrap; }
    .linea .val { flex: 1; border-bottom: 1px solid #2b3a9e; min-height: 18px; padding: 0 3px 1px; color: #111; font-size: 13px; }
    .linea .val.corto { flex: 0 0 auto; min-width: 38mm; }

    .cantidad { display: flex; align-items: center; gap: 5px; margin-bottom: 1.8mm; font-size: 13px; }
    .cantidad .et { font-weight: 600; white-space: nowrap; }
    .cantidad .val { flex: 1; border: 1px solid #2b3a9e; border-radius: 6px; padding: 4px 8px; color: #111; font-size: 11.5px; min-height: 24px; }

    .pie { margin-top: 3mm; display: flex; align-items: flex-start; gap: 4mm; }
    .fechas { border: 1.5px solid #2b3a9e; border-radius: 9px; border-collapse: separate; border-spacing: 0; text-align: center; font-size: 11px; }
    .fechas th, .fechas td { padding: 2px 10px; }
    .fechas thead th { border-bottom: 1.5px solid #2b3a9e; font-weight: 900; }
    .fechas tbody th { text-align: left; font-weight: 900; border-right: 1.5px solid #2b3a9e; padding-right: 12px; }
    .fechas tbody tr + tr > * { border-top: 1px solid #2b3a9e; }
    .fechas td { color: #111; font-size: 14px; font-weight: bold; }
    .fechas td + td, .fechas thead th + th { border-left: 1px solid #2b3a9e; }
    .fechas td.venc { color: #b91c1c; }
    .izq { display: flex; flex-direction: column; gap: 11mm; }
    .firma { width: 100%; text-align: center; font-size: 10px; font-weight: 600; }
    .firma div { border-top: 1px solid #2b3a9e; margin-bottom: 2px; }
    .pago { flex: 1; font-size: 11px; line-height: 1.3; color: #111; align-self: center; }
    .pago p + p { margin-top: 1.5mm; }
    .pago b { color: #2b3a9e; }
    .pago .pago-tit { font-weight: 900; color: #2b3a9e; font-size: 11.5px; letter-spacing: .5px; }
    .qr { width: 26mm; height: 26mm; overflow: hidden; position: relative; flex: none; }
    .qr img { position: absolute; width: 42.6mm; left: -8.2mm; top: -3.9mm; }

    @media print {
      body { background: #fff; }
      .toolbar { display: none; }
      .hoja { margin: 0; box-shadow: none; }
      * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
CSS;
}

/** Imprime los recibos en hojas A4, 4 por hoja (2 × 2). */
function renderRecibos(array $recibos): void
{
    foreach (array_chunk($recibos, 4) as $grupo) {
        echo '<div class="hoja">';
        foreach ($grupo as $r) renderRecibo($r);
        echo '</div>';
    }
}

/**
 * Dibuja un recibo con el formato del talonario.
 * $r: titulo (INGRESO/EGRESO), etiqueta (Recibí del Sr. / Pagado a), numero, nombre,
 *     monto, concepto, mes, sector (opcional), fecha (Y-m-d), vencimiento (Y-m-d|null)
 */
function renderRecibo(array $r): void
{
    $ts    = strtotime($r['fecha']);
    $monto = (float)$r['monto'];
    ?>
<div class="recibo"><div class="marco"><div class="marco-int">
  <div class="cab">
    <h1>JAAPS JUNTA ADMINISTRADORA DE AGUA POTABLE</h1>
    <p>SECTOR CARRIZALES - LACRAMARCA BAJA</p>
  </div>

  <div class="fila-titulo">
    <span class="pill"><?= e($r['titulo']) ?></span>
    <span class="numero"><?= $r['numero'] !== '' ? 'Nº ' . e($r['numero']) : '' ?></span>
    <div class="soles-box"><b>S/</b><span><?= number_format($monto, 2) ?></span></div>
  </div>

  <div class="linea"><span class="et"><?= e($r['etiqueta']) ?></span><span class="val"><?= e($r['nombre']) ?></span></div>

  <div class="cantidad"><span class="et">La Cantidad de :</span><span class="val"><?= e(montoALetras($monto)) ?></span><span class="et">Soles</span></div>

  <div class="linea"><span class="et">Por Concepto de :</span><span class="val"><?= e($r['concepto']) ?></span></div>

  <div class="linea">
    <span class="et">Mes de:</span><span class="val"><?= e($r['mes']) ?></span>
    <?php if (!empty($r['sector'])): ?>
      <span class="et">Sector:</span><span class="val corto"><?= e($r['sector']) ?></span>
    <?php endif; ?>
  </div>

  <div class="pie">
    <div class="izq">
      <table class="fechas">
        <thead><tr><th></th><th>DIA</th><th>MES</th><th>AÑO</th></tr></thead>
        <tbody>
          <tr><th>EMISIÓN</th><td><?= date('d', $ts) ?></td><td><?= date('m', $ts) ?></td><td><?= date('Y', $ts) ?></td></tr>
          <?php if (!empty($r['vencimiento'])): $tv = strtotime($r['vencimiento']); ?>
          <tr><th>VENCE</th><td class="venc"><?= date('d', $tv) ?></td><td class="venc"><?= date('m', $tv) ?></td><td class="venc"><?= date('Y', $tv) ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      <div class="firma"><div></div>TESORERIA</div>
    </div>
    <?php if (!empty($r['vencimiento'])): ?>
    <div class="pago">
      <p class="pago-tit">FORMAS DE PAGO</p>
      <p><b>Yape:</b> escanee el código QR.</p>
      <p><b>Presencial:</b> el día de vencimiento, <?= date('d/m/Y', $tv) ?>, en el local de la JAAPS.</p>
    </div>
    <div class="qr"><img src="<?= APP_URL ?>/img/qr.jpeg" alt="QR Yape"></div>
    <?php endif; ?>
  </div>
</div></div></div>
    <?php
}
