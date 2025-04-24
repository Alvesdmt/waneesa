<?php
require_once '../functions/caixa.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo = $_POST['tipo'];
    $valor = $_POST['valor'];
    $descricao = $_POST['descricao'];

    if (registrarMovimentoCaixa($tipo, $valor, $descricao)) {
        $_SESSION['mensagem'] = 'Movimento registrado com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao registrar o movimento!';
        $_SESSION['tipo_mensagem'] = 'danger';
    }
}

header('Location: ../../caixa');
exit(); 