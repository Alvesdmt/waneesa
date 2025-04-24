<?php
require_once '../functions/funcionarios.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

    if (cadastrarFuncionario($dados)) {
        $_SESSION['mensagem'] = 'Funcionário cadastrado com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao cadastrar funcionário!';
        $_SESSION['tipo_mensagem'] = 'danger';
    }
}

header('Location: ../../funcionarios');
exit(); 