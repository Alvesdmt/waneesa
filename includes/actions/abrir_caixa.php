<?php
require_once '../functions/caixa.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $valorInicial = $_POST['valorInicial'];
    $usuarioId = $_SESSION['usuario_id'];

    if (abrirCaixa($usuarioId, $valorInicial)) {
        $_SESSION['mensagem'] = 'Caixa aberto com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao abrir o caixa!';
        $_SESSION['tipo_mensagem'] = 'danger';
    }
}

header('Location: ../../caixa');
exit(); 