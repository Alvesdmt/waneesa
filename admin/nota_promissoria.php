<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Inclui a configuração do banco de dados
include '../config/database.php';

// Verifica se foi fornecido o ID da venda
if (!isset($_GET['venda_id'])) {
    header("Location: carnes.php");
    exit();
}

$venda_id = $_GET['venda_id'];

// Busca informações da venda e do cliente
$sql = "SELECT v.*, c.nome as cliente_nome, c.cpf, c.endereco, c.telefone 
        FROM vendas_prazo v 
        JOIN clientes c ON v.cliente_id = c.id 
        WHERE v.id = :venda_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':venda_id' => $venda_id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venda) {
    header("Location: carnes.php");
    exit();
}

// Busca as parcelas da venda
$sql = "SELECT * FROM parcelas WHERE venda_id = :venda_id ORDER BY numero_parcela";
$stmt = $pdo->prepare($sql);
$stmt->execute([':venda_id' => $venda_id]);
$parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuração do PDF
require_once('../vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);

// HTML da nota promissória
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .signature {
            margin-top: 50px;
            text-align: center;
        }
        .line {
            border-top: 1px solid #000;
            margin-top: 5px;
            width: 200px;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">NOTA PROMISSÓRIA</div>
        </div>
        
        <div class="content">
            <p>Pelo presente instrumento, eu, <strong>' . htmlspecialchars($venda['cliente_nome']) . '</strong>, 
            portador do CPF nº ' . $venda['cpf'] . ', residente e domiciliado em ' . htmlspecialchars($venda['endereco']) . ', 
            prometo pagar a quantia de R$ ' . number_format($venda['valor_total'], 2, ',', '.') . ' 
            (' . numberToWords($venda['valor_total']) . ' reais) em ' . $venda['parcelas'] . ' parcelas, 
            conforme discriminado abaixo:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Parcela</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                    </tr>
                </thead>
                <tbody>';

foreach ($parcelas as $parcela) {
    $html .= '<tr>
                <td>' . $parcela['numero_parcela'] . '</td>
                <td>R$ ' . number_format($parcela['valor'], 2, ',', '.') . '</td>
                <td>' . date('d/m/Y', strtotime($parcela['data_vencimento'])) . '</td>
              </tr>';
}

$html .= '</tbody>
            </table>
            
            <p>Em caso de atraso no pagamento, será cobrado juros de ' . $venda['juros'] . '% ao mês.</p>
            
            <p>Local e Data: ____________________, ' . date('d/m/Y') . '</p>
        </div>
        
        <div class="signature">
            <p>Assinatura do Devedor</p>
            <div class="line"></div>
        </div>
    </div>
</body>
</html>';

// Função para converter número em palavras
function numberToWords($number) {
    $unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
    $dezenas = ['', 'dez', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    $centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
    $milhares = ['', 'mil', 'milhão', 'bilhão', 'trilhão'];
    
    $number = number_format($number, 2, '.', '');
    $parts = explode('.', $number);
    $inteiro = $parts[0];
    $decimal = $parts[1];
    
    $result = '';
    
    if ($inteiro > 0) {
        $result .= convertNumberToWords($inteiro) . ' reais';
    }
    
    if ($decimal > 0) {
        if ($result != '') {
            $result .= ' e ';
        }
        $result .= convertNumberToWords($decimal) . ' centavos';
    }
    
    return $result;
}

function convertNumberToWords($number) {
    if ($number < 10) {
        return $unidades[$number];
    } elseif ($number < 20) {
        return $dezenas[$number - 10];
    } elseif ($number < 100) {
        return $dezenas[floor($number / 10)] . ' e ' . $unidades[$number % 10];
    } elseif ($number < 1000) {
        return $centenas[floor($number / 100)] . ' e ' . convertNumberToWords($number % 100);
    } else {
        return 'mil e ' . convertNumberToWords($number % 1000);
    }
}

// Gera o PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Envia o PDF para o navegador
$dompdf->stream('nota_promissoria_' . $venda_id . '.pdf', ['Attachment' => true]); 