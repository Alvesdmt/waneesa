<?php
function gerarNotaPromissoria($dados) {
    // Converte o mês para português
    $meses = array(
        'January' => 'Jan',
        'February' => 'Fev',
        'March' => 'Mar',
        'April' => 'Abr',
        'May' => 'Mai',
        'June' => 'Jun',
        'July' => 'Jul',
        'August' => 'Ago',
        'September' => 'Set',
        'October' => 'Out',
        'November' => 'Nov',
        'December' => 'Dez'
    );
    
    $data = date('d \d\e F \d\e Y', strtotime($dados['data_vencimento']));
    foreach ($meses as $en => $pt) {
        $data = str_replace($en, $pt, $data);
    }

    $html = '
    <div class="nota-promissoria">
        <div class="nota-conteudo">
            <!-- Cabeçalho -->
            <div class="nota-header">
                <div class="logo-titulo">
                    <div class="logo">IM</div>
                    <div class="titulo-container">
                        <div class="titulo">NOTA PROMISSÓRIA</div>
                        <div class="numero">Nº ' . str_pad($dados['numero_parcela'], 2, '0', STR_PAD_LEFT) . '-' . $dados['carne_id'] . '</div>
                    </div>
                </div>
                <div class="vencimento-valor">
                    <div class="vencimento">Venc: ' . date('d/m/Y', strtotime($dados['data_vencimento'])) . '</div>
                    <div class="valor">R$ ' . number_format($dados['valor'], 2, ',', '.') . '</div>
                </div>
            </div>
            
            <!-- Texto Principal -->
            <div class="texto-principal">
                ' . $data . ' pagarei por esta via de NOTA PROMISSÓRIA a 
                ' . $dados['empresa'] . ' ou à sua ordem, a quantia de ' . $dados['valor_por_extenso'] . ' 
                em moeda corrente deste país.
            </div>
            
            <!-- Local de Pagamento -->
            <div class="local-pagamento">
                Local pgto: ' . $dados['cidade'] . '/' . $dados['estado'] . '
            </div>
            
            <!-- Dados do Emitente -->
            <div class="dados-emitente">
                <div class="titulo-emitente">EMITENTE:</div>
                <div class="info-linha">Nome: ' . $dados['nome_cliente'] . '</div>
                <div class="info-linha">CPF: ' . $dados['cpf_cliente'] . '</div>
                <div class="info-linha">End: ' . $dados['endereco_cliente'] . '</div>
                <div class="info-linha">Cidade/UF: ' . $dados['cidade_cliente'] . '/' . $dados['estado_cliente'] . '</div>
            </div>
            
            <!-- Assinatura -->
            <div class="assinatura">
                <div class="linha-assinatura"></div>
                <div class="texto-assinatura">Assinatura do Emitente</div>
            </div>
        </div>
    </div>';

    return $html;
}

function valorPorExtenso($valor) {
    // Implementação completa da função valorPorExtenso
    $singular = array("centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
    $plural = array("centavos", "reais", "mil", "milhões", "bilhões", "trilhões", "quatrilhões");

    $c = array("", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
    $d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa");
    $d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezessete", "dezoito", "dezenove");
    $u = array("", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove");

    $valor = number_format($valor, 2, ".", ".");
    $inteiro = explode(".", $valor);
    
    $rt = "";
    
    // Processando a parte inteira
    $valor_inteiro = (int)$inteiro[0];
    if ($valor_inteiro > 0) {
        if ($valor_inteiro < 1000) {
            // Processando centenas, dezenas e unidades
            $centena = floor($valor_inteiro / 100);
            $dezena = floor(($valor_inteiro % 100) / 10);
            $unidade = $valor_inteiro % 10;
            
            if ($centena > 0) {
                $rt .= $c[$centena];
                if ($dezena > 0 || $unidade > 0) $rt .= " e ";
            }
            
            if ($dezena > 0) {
                if ($dezena == 1 && $unidade > 0) {
                    $rt .= $d10[$unidade];
                } else {
                    $rt .= $d[$dezena];
                    if ($unidade > 0) $rt .= " e ";
                }
            }
            
            if ($unidade > 0 && $dezena != 1) {
                $rt .= $u[$unidade];
            }
            
            $rt .= " " . ($valor_inteiro > 1 ? $plural[1] : $singular[1]);
        }
    }
    
    // Processando os centavos
    if ((int)$inteiro[1] > 0) {
        if ($rt) $rt .= " e ";
        $rt .= $u[(int)$inteiro[1]] . " " . ($inteiro[1] == "1" ? $singular[0] : $plural[0]);
    }
    
    return $rt ? trim($rt) : "zero reais";
}
?> 