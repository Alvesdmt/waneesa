<?php
require_once '../functions/funcionarios.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];

    if (excluirFuncionario($id)) {
        $_SESSION['mensagem'] = 'Funcionário excluído com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao excluir funcionário!';
        $_SESSION['tipo_mensagem'] = 'danger';
    }
}

header('Location: ../../funcionarios');
exit(); 