<?php
function gerarContrato($dados) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Contrato de Parcelamento</title>
        <style>
            @page {
                margin: 20mm;
                size: A4 portrait;
            }
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #000;
                margin: 0;
                padding: 0;
                background: #fff;
            }
            .container {
                max-width: 170mm;
                margin: 0 auto;
                padding: 5mm;
            }
            .header {
                text-align: center;
                margin-bottom: 10mm;
                border-bottom: 2px solid #000;
                padding-bottom: 5mm;
            }
            .titulo {
                font-size: 16pt;
                font-weight: bold;
                margin-bottom: 5mm;
            }
            .subtitulo {
                font-size: 10pt;
                margin-bottom: 3mm;
            }
            .texto-principal {
                text-align: justify;
                margin-bottom: 8mm;
                font-size: 10pt;
            }
            .tabela-parcelas {
                width: 100%;
                border-collapse: collapse;
                margin: 5mm 0 8mm;
            }
            .tabela-parcelas th,
            .tabela-parcelas td {
                border: 1px solid #000;
                padding: 2mm;
                text-align: center;
                font-size: 9pt;
            }
            .tabela-parcelas th {
                background: #f0f0f0;
                font-weight: bold;
            }
            .tabela-parcelas tr:nth-child(even) {
                background: #f9f9f9;
            }
            .clausulas {
                margin: 5mm 0;
                font-size: 9pt;
            }
            .assinatura {
                margin-top: 15mm;
                text-align: center;
            }
            .linha-assinatura {
                border-top: 1px solid #000;
                width: 60mm;
                margin: 2mm auto;
            }
            .nome-assinatura {
                font-size: 9pt;
                margin-bottom: 1mm;
            }
            .cpf-assinatura {
                font-size: 8pt;
                color: #333;
            }
            .local-data {
                margin: 10mm 0;
                font-size: 9pt;
                text-align: right;
            }
            .rodape {
                margin-top: 15mm;
                font-size: 8pt;
                color: #666;
                text-align: center;
                border-top: 1px solid #ccc;
                padding-top: 3mm;
            }
            .status-pago {
                color: #006400;
                font-weight: bold;
            }
            @media print {
                body {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .tabela-parcelas th {
                    background: #f0f0f0 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .tabela-parcelas tr:nth-child(even) {
                    background: #f9f9f9 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .status-pago {
                    color: #006400 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="titulo">NOTA PROMISSÓRIA</div>
                <div class="subtitulo">Contrato de Parcelamento</div>
            </div>

            <div class="texto-principal">
                Eu, <strong>' . $dados['nome'] . '</strong>, portador do CPF nº <strong>' . $dados['cpf'] . '</strong>, 
                residente e domiciliado em <strong>' . $dados['endereco'] . ', ' . $dados['cidade'] . ' - ' . $dados['estado'] . '</strong>, 
                comprometo-me a pagar a quantia de <strong>R$ ' . number_format($dados['valor_total'], 2, ',', '.') . ' 
                (' . $dados['valor_total_extenso'] . ')</strong>, em ' . $dados['num_parcelas'] . ' parcelas mensais, 
                conforme tabela abaixo:
            </div>

            <table class="tabela-parcelas">
                <thead>
                    <tr>
                        <th>Parcela</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $dados['tabela_parcelas'] . '
                </tbody>
            </table>

            <div class="clausulas">
                <p>O pagamento deverá ser efetuado até o dia do vencimento de cada parcela, sob pena de incidência de multa e juros conforme legislação vigente.</p>
                <p>Declaro estar ciente de que o não pagamento no prazo estipulado implicará na inclusão do meu nome nos órgãos de proteção ao crédito.</p>
            </div>

            <div class="local-data">
                ' . $dados['cidade'] . ', ' . $dados['data_atual'] . '
            </div>

            <div class="assinatura">
                <div class="linha-assinatura"></div>
                <div class="nome-assinatura">' . $dados['nome'] . '</div>
                <div class="cpf-assinatura">CPF: ' . $dados['cpf'] . '</div>
            </div>

            <div class="rodape">
                Documento gerado em: ' . $dados['data_geracao'] . '
            </div>
        </div>
    </body>
    </html>';

    return $html;
}

function formatarParcela($numero, $valor, $vencimento, $status) {
    $class = $status == 'Pago' ? 'status-pago' : '';
    return '
    <tr>
        <td>' . $numero . 'ª</td>
        <td>R$ ' . number_format($valor, 2, ',', '.') . '</td>
        <td>' . date('d/m/Y', strtotime($vencimento)) . '</td>
        <td class="' . $class . '">' . $status . '</td>
    </tr>';
}
?> 