<?php
require_once '../vendor/autoload.php';
require_once 'gerar_contrato.php';
require_once '../config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Dados do exemplo
$dados = [
    'nome' => 'Cleia marcia',
    'cpf' => '925.418.601-57',
    'endereco' => 'Quadra QNB 13',
    'cidade' => 'Brasília',
    'estado' => 'DF',
    'valor_total' => 212.00,
    'valor_total_extenso' => 'duzentos e doze reais',
    'num_parcelas' => 2,
    'data_atual' => '24/04/2025',
    'data_geracao' => '24/04/2025, 08:58:38'
];

// Gerar tabela de parcelas
$parcelas = '';
$parcelas .= formatarParcela(1, 106.00, '2025-06-22', 'Pago');
$parcelas .= formatarParcela(2, 106.00, '2025-07-22', 'Pago');
$dados['tabela_parcelas'] = $parcelas;

// Configurações do DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultPaperSize', 'A4');
$options->set('defaultFont', 'Arial');
$options->set('dpi', 96);
$options->set('defaultMediaType', 'print');
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);

// Gera o HTML do contrato
$html = gerarContrato($dados);

// Configura o PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Gera o PDF
$dompdf->stream("contrato_parcelamento.pdf", [
    "Attachment" => true
]);
?> 