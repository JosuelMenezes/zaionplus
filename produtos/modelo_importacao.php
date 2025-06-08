<?php
// produtos/modelo_importacao.php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Criar uma nova planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir os cabeçalhos
$sheet->setCellValue('A1', 'Nome do Produto');
$sheet->setCellValue('B1', 'Descrição');
$sheet->setCellValue('C1', 'Valor de Venda');

// Adicionar alguns exemplos
$sheet->setCellValue('A2', 'Produto Exemplo 1');
$sheet->setCellValue('B2', 'Descrição do produto exemplo 1');
$sheet->setCellValue('C2', '19.90');

$sheet->setCellValue('A3', 'Produto Exemplo 2');
$sheet->setCellValue('B3', 'Descrição do produto exemplo 2');
$sheet->setCellValue('C3', '29.90');

// Formatar os cabeçalhos
$sheet->getStyle('A1:C1')->getFont()->setBold(true);

// Ajustar a largura das colunas
$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(40);
$sheet->getColumnDimension('C')->setWidth(15);

// Configurar o formato da coluna de valor
$sheet->getStyle('C2:C3')->getNumberFormat()->setFormatCode('#,##0.00');

// Configurar o cabeçalho HTTP para download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="modelo_importacao_produtos.xlsx"');
header('Cache-Control: max-age=0');

// Salvar o arquivo
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;