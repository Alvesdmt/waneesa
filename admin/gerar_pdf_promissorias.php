<?php
require_once '../vendor/autoload.php';
require_once 'gerar_promissoria.php';
require_once '../config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['carne_id'])) {
    die('ID do carnê não fornecido');
}

$carne_id = $_GET['carne_id'];
$parcela_id = isset($_GET['parcela_id']) ? $_GET['parcela_id'] : null;

// Busca informações do carnê e do cliente
$sql = "SELECT c.*, cl.* 
        FROM carnes c 
        JOIN clientes cl ON c.cliente_id = cl.id 
        WHERE c.id = :carne_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':carne_id' => $carne_id]);
$dados_carne = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados_carne) {
    die('Carnê não encontrado');
}

// Busca as parcelas
$sql = "SELECT * FROM parcelas WHERE carne_id = :carne_id";
if ($parcela_id) {
    $sql .= " AND id = :parcela_id";
}
$sql .= " ORDER BY numero_parcela";

$stmt = $pdo->prepare($sql);
$params = [':carne_id' => $carne_id];
if ($parcela_id) {
    $params[':parcela_id'] = $parcela_id;
}
$stmt->execute($params);
$parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($parcelas)) {
    die('Nenhuma parcela encontrada');
}

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

// Gera o HTML com as notas promissórias
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nota Promissória</title>
    <style>
        @page {
            margin: 2mm;
            size: A4 portrait;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        .page-break {
            page-break-after: always;
        }
        .notas-container {
            display: flex;
            flex-direction: column;
            gap: 1mm;
            padding: 0;
            height: auto;
        }
        .nota-promissoria {
            width: 100%;
            height: 95mm;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .nota-conteudo {
            border: 1px solid #000;
            padding: 2mm;
            height: 100%;
            position: relative;
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 1mm;
        }
        .nota-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1mm;
            border-bottom: 1px solid #000;
            padding-bottom: 0.5mm;
            flex-shrink: 0;
        }
        .logo-titulo {
            display: flex;
            align-items: center;
            gap: 0.5mm;
        }
        .logo {
            width: 8mm;
            height: 8mm;
        }
        .titulo-container {
            border: 1px solid #000;
            padding: 0.5mm;
        }
        .titulo {
            font-size: 8pt;
            font-weight: bold;
            margin: 0;
        }
        .numero {
            font-size: 7pt;
            margin: 0;
        }
        .vencimento-valor {
            text-align: right;
            font-size: 7pt;
            margin: 0;
        }
        .valor {
            font-weight: bold;
            font-size: 8pt;
            margin: 0;
        }
        .texto-principal {
            margin: 0;
            text-align: justify;
            line-height: 1.1;
            font-size: 7pt;
            flex-shrink: 0;
        }
        .local-pagamento {
            margin: 0;
            font-size: 7pt;
            line-height: 1;
            flex-shrink: 0;
        }
        .dados-emitente {
            margin: 0;
            font-size: 7pt;
            background: #f9f9f9;
            padding: 1mm;
            border: 1px solid #ddd;
            line-height: 1.1;
            flex-shrink: 0;
        }
        .titulo-emitente {
            font-weight: bold;
            margin: 0 0 0.2mm 0;
        }
        .info-linha {
            margin: 0;
        }
        .assinatura {
            margin-top: 2mm;
            text-align: center;
            padding: 0;
            flex-shrink: 0;
        }
        .linha-assinatura {
            border-top: 1px solid #000;
            width: 80%;
            margin: 1mm auto 0;
        }
        .texto-assinatura {
            font-size: 6pt;
            margin: 0.2mm 0 0 0;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .dados-emitente {
                background: #f9f9f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .logo-img {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>';

// Processa as notas em grupos de 3
$total_notas = count($parcelas);
$notas_por_pagina = 3;

for ($i = 0; $i < $total_notas; $i += $notas_por_pagina) {
    $html .= '<div class="notas-container">';
    
    // Gera exatamente 3 notas
    for ($j = 0; $j < $notas_por_pagina && ($i + $j) < $total_notas; $j++) {
        $dados = [
            'numero_parcela' => $parcelas[$i + $j]['numero_parcela'],
            'carne_id' => $carne_id,
            'data_vencimento' => $parcelas[$i + $j]['data_vencimento'],
            'valor' => $parcelas[$i + $j]['valor'],
            'empresa' => 'Armarinho Central',
            'valor_por_extenso' => valorPorExtenso($parcelas[$i + $j]['valor']),
            'cidade' => $dados_carne['cidade'],
            'estado' => $dados_carne['estado'],
            'nome_cliente' => $dados_carne['nome'],
            'cpf_cliente' => $dados_carne['cpf'],
            'endereco_cliente' => $dados_carne['endereco'],
            'cidade_cliente' => $dados_carne['cidade'],
            'estado_cliente' => $dados_carne['estado']
        ];
        $html .= gerarNotaPromissoria($dados);
    }
    
    $html .= '</div>';
    
    // Adiciona quebra de página se não for a última página
    if ($i + $notas_por_pagina < $total_notas) {
        $html .= '<div class="page-break"></div>';
    }
}

$html .= '</body></html>';

// Configura o PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

// Define algumas opções adicionais para otimizar o PDF
$dompdf->set_option('isRemoteEnabled', true);
$dompdf->set_option('isHtml5ParserEnabled', true);
$dompdf->set_option('isPhpEnabled', true);

$dompdf->render();

// Gera o PDF
$nome_arquivo = $parcela_id ? 
    "nota_promissoria_parcela_{$parcela_id}.pdf" : 
    "notas_promissorias_carne_{$carne_id}.pdf";

$dompdf->stream($nome_arquivo, [
    "Attachment" => true
]);
?> 