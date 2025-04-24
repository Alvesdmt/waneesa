<?php
require_once '../functions/funcionarios.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $dados = [
        'nome' => $_POST['nome'],
        'cpf' => $_POST['cpf'],
        'telefone' => $_POST['telefone'],
        'email' => $_POST['email'],
        'endereco' => $_POST['endereco'],
        'cargo' => $_POST['cargo'],
        'salario' => $_POST['salario'],
        'data_admissao' => $_POST['data_admissao']
    ];

    if (editarFuncionario($id, $dados)) {
        $_SESSION['mensagem'] = 'Funcionário atualizado com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao atualizar funcionário!';
        $_SESSION['tipo_mensagem'] = 'danger';
    }
}

header('Location: ../../funcionarios');
exit(); 