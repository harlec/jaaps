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
    .marco-int { height: 100%; border: 1px solid #2b3a9e; border-radius: 6px; padding: 3mm 5mm 3mm; display: flex; flex-direction: column; }

    .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 4mm; }
    .cab { line-height: 1.15; }
    .cab h1 { font-size: 12px; font-weight: 900; letter-spacing: -.3px; }
    .cab p  { font-size: 9px; font-weight: bold; margin-top: 1px; }
    .soles-box { min-width: 30mm; border: 1.5px solid #2b3a9e; border-radius: 5px; padding: 3px 7px; font-size: 14px; font-weight: bold; display: flex; gap: 5px; color: #111; }
    .soles-box b { color: #2b3a9e; }

    .fila-titulo { display: flex; align-items: center; justify-content: center; gap: 22mm; margin: 2mm 0 3mm; }
    .pill { background: #2b3a9e; color: #fff; font-weight: bold; font-size: 14px; letter-spacing: 1px; padding: 3px 20px; border-radius: 11px; }
    .numero { color: #dc2626; font-family: 'Times New Roman', serif; font-size: 14px; font-weight: bold; }

    .linea { display: flex; align-items: flex-end; gap: 4px; margin-bottom: 2.5mm; font-size: 10px; }
    .linea .et { font-weight: 600; white-space: nowrap; }
    .linea .val { flex: 1; border-bottom: 1px solid #2b3a9e; min-height: 14px; padding: 0 2px 1px; color: #111; font-size: 10px; }
    .linea.lineas2 { display: block; }
    .linea.lineas2 .val { display: block; margin-top: 2.5mm; }
    .linea.lineas2 .head { display: flex; align-items: flex-end; gap: 4px; }

    .cantidad { display: flex; align-items: center; gap: 4px; margin-bottom: 2.5mm; font-size: 10px; }
    .cantidad .et { font-weight: 600; white-space: nowrap; }
    .cantidad .val { flex: 1; border: 1px solid #2b3a9e; border-radius: 5px; padding: 3px 6px; color: #111; font-size: 9px; min-height: 18px; }

    .pie { margin-top: auto; display: flex; justify-content: space-between; align-items: flex-end; gap: 5mm; }
    .izq { display: flex; align-items: flex-end; gap: 4mm; }
    .venc { font-size: 8.5px; color: #111; }
    .venc b { color: #2b3a9e; }
    .fecha { border: 1.2px solid #2b3a9e; border-radius: 7px; border-collapse: separate; border-spacing: 0; text-align: center; font-size: 8.5px; }
    .fecha th, .fecha td { padding: 2px 8px; }
    .fecha th { border-bottom: 1.2px solid #2b3a9e; font-weight: 900; }
    .fecha td { color: #111; font-size: 10px; height: 17px; }
    .fecha th + th, .fecha td + td { border-left: 1.2px solid #2b3a9e; }
    .firma { width: 42mm; text-align: center; font-size: 8px; }
    .firma div { border-top: 1px solid #2b3a9e; margin-bottom: 2px; }

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
 *     monto, concepto, mes, fecha (Y-m-d), vencimiento (Y-m-d|null)
 */
function renderRecibo(array $r): void
{
    $ts    = strtotime($r['fecha']);
    $monto = (float)$r['monto'];
    ?>
<div class="recibo"><div class="marco"><div class="marco-int">
  <div class="top">
    <div class="cab">
      <h1>JAAPS JUNTA ADMINISTRADORA DE AGUA POTABLE</h1>
      <p>SECTOR CARRIZALES - LACRAMARCA BAJA</p>
    </div>
    <div class="soles-box"><b>S/</b><span><?= number_format($monto, 2) ?></span></div>
  </div>

  <div class="fila-titulo">
    <span class="pill"><?= e($r['titulo']) ?></span>
    <span class="numero"><?= $r['numero'] !== '' ? 'Nº ' . e($r['numero']) : '' ?></span>
  </div>

  <div class="linea"><span class="et"><?= e($r['etiqueta']) ?></span><span class="val"><?= e($r['nombre']) ?></span></div>

  <div class="cantidad"><span class="et">La Cantidad de :</span><span class="val"><?= e(montoALetras($monto)) ?></span><span class="et">Soles</span></div>

  <div class="linea lineas2">
    <div class="head"><span class="et">Por Concepto de :</span><span class="val"><?= e($r['concepto']) ?></span></div>
    <span class="val">&nbsp;</span>
  </div>

  <div class="linea"><span class="et">Mes de:</span><span class="val"><?= e($r['mes']) ?></span></div>

  <div class="pie">
    <div class="izq">
      <?php if (!empty($r['vencimiento'])): ?>
        <div class="venc"><b>Vence:</b> <?= date('d/m/Y', strtotime($r['vencimiento'])) ?></div>
      <?php endif; ?>
      <table class="fecha">
        <tr><th>DIA</th><th>MES</th><th>AÑO</th></tr>
        <tr><td><?= date('d', $ts) ?></td><td><?= date('m', $ts) ?></td><td><?= date('Y', $ts) ?></td></tr>
      </table>
    </div>
    <div class="firma"><div></div>TESORERIA</div>
  </div>
</div></div></div>
    <?php
}
