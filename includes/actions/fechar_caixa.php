<?php
require_once '../functions/caixa.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $valorFechamento = $_POST['valorFechamento'];
    $usuarioId = $_SESSION['usuario_id'];
    $dataHoraFechamento = date('Y-m-d H:i:s');

    if (fecharCaixa($usuarioId, $dataHoraFechamento)) {
        $_SESSION['mensagem'] = 'Caixa fechado com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao fechar o caixa!';
        $_SESSION['tipo_mensagem'] = 'danger';
    }
}

header('Location: ../../caixa');
exit(); 