<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$pdo = getDB();

// Períodos
$periodos = $pdo->query(
    "SELECT id, nombre, anio, semestre, monto_total FROM periodos_cobro ORDER BY anio DESC, semestre"
)->fetchAll();

$periodoId = (int)($_GET['periodo_id'] ?? 0);
if ($periodoId === 0) {
    foreach ($periodos as $p) {
        if ((int)$p['anio'] === 2026 && $p['semestre'] === '1') { $periodoId = (int)$p['id']; break; }
    }
    if ($periodoId === 0 && !empty($periodos)) $periodoId = (int)$periodos[0]['id'];
}

$periodoActual = null;
foreach ($periodos as $p) { if ((int)$p['id'] === $periodoId) { $periodoActual = $p; break; } }

$estado = $_GET['estado'] ?? '';
$pagado = $_GET['pagado'] ?? '';

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

$selectParams = array_merge([$periodoId], $whereParams);
$stmt = $pdo->prepare("
    SELECT a.codigo, a.dni, a.nombres, a.apellidos, a.zona, a.telefono, a.email,
           a.estado, a.fecha_inscripcion,
           EXISTS (SELECT 1 FROM pagos p2 WHERE p2.abonado_id = a.id AND p2.periodo_id = ?) AS pagado
    FROM abonados a
    WHERE $whereStr
    ORDER BY a.zona, a.apellidos, a.nombres
");
$stmt->execute($selectParams);
$abonados = $stmt->fetchAll();

// ── Construcción del libro ──────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Abonados');

$sheet->setCellValue('A1', APP_NAME);
$sheet->mergeCells('A1:I1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0D9488');

$filtroEstadoLabel = $estado !== '' ? (ESTADOS_ABONADO[$estado] ?? $estado) : 'Todos';
$filtroPagadoLabel = $pagado === 'si' ? 'Pagaron' : ($pagado === 'no' ? 'No pagaron' : 'Todos');

$sheet->setCellValue('A2', 'Reporte de Abonados — Período: ' . ($periodoActual['nombre'] ?? '—')
    . ' | Estado: ' . $filtroEstadoLabel . ' | Pago: ' . $filtroPagadoLabel);
$sheet->mergeCells('A2:I2');
$sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('6B7280');

$sheet->setCellValue('A3', 'Generado el ' . date('d/m/Y H:i'));
$sheet->mergeCells('A3:I3');
$sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('9CA3AF');

$headerRow = 5;
$headers = ['#', 'Código', 'DNI', 'Apellidos', 'Nombres', 'Zona', 'Estado', 'Pagó', 'Teléfono', 'Email', 'Fecha inscripción'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . $headerRow, $h);
    $col++;
}
$sheet->getStyle('A' . $headerRow . ':K' . $headerRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$sheet->getStyle('A' . $headerRow . ':K' . $headerRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0D9488');
$sheet->getStyle('A' . $headerRow . ':K' . $headerRow)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$row = $headerRow + 1;
foreach ($abonados as $i => $a) {
    $sheet->setCellValue('A' . $row, $i + 1);
    $sheet->setCellValueExplicit('B' . $row, $a['codigo'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('C' . $row, $a['dni'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('D' . $row, $a['apellidos']);
    $sheet->setCellValue('E' . $row, $a['nombres']);
    $sheet->setCellValue('F' . $row, ZONAS[$a['zona']] ?? $a['zona']);
    $sheet->setCellValue('G' . $row, ESTADOS_ABONADO[$a['estado']] ?? $a['estado']);
    $sheet->setCellValue('H' . $row, (bool)$a['pagado'] ? 'Sí' : 'No');
    $sheet->setCellValueExplicit('I' . $row, $a['telefono'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('J' . $row, $a['email'] ?? '');
    $sheet->setCellValue('K' . $row, $a['fecha_inscripcion'] ? date('d/m/Y', strtotime($a['fecha_inscripcion'])) : '');

    $pagadoCell = $sheet->getCell('H' . $row);
    $pagadoCell->getStyle()->getFont()->getColor()->setRGB((bool)$a['pagado'] ? '059669' : 'DC2626');
    $pagadoCell->getStyle()->getFont()->setBold(true);

    if ($i % 2 === 1) {
        $sheet->getStyle('A' . $row . ':K' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
    }
    $row++;
}

$lastRow = $row - 1;
if ($lastRow >= $headerRow + 1) {
    $sheet->getStyle('A' . $headerRow . ':K' . $lastRow)
        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('E5E7EB'));
}

foreach (range('A', 'K') as $c) {
    $sheet->getColumnDimension($c)->setAutoSize(true);
}

$sheet->freezePane('A' . ($headerRow + 1));

// ── Salida del archivo ──────────────────────────────────────────────────
$filename = 'abonados_' . date('Ymd_His') . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
