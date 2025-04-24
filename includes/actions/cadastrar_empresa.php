<?php
require_once '../functions/empresas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'razao_social' => $_POST['razao_social'],
        'nome_fantasia' => $_POST['nome_fantasia'],
        'cnpj' => $_POST['cnpj'],
        'ie' => $_POST['ie'],
        'telefone' => $_POST['telefone'],
        'email' => $_POST['email'],
        'endereco' => $_POST['endereco'],
        'numero' => $_POST['numero'],
        'complemento' => $_POST['complemento'],
        'bairro' => $_POST['bairro'],
        'cidade' => $_POST['cidade'],
        'estado' => $_POST['estado'],
        'cep' => $_POST['cep'],
        'logo' => null
    ];

    // Upload da logo se existir
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/empresas/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extensao = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid() . '.' . $extensao;
        $caminhoArquivo = $uploadDir . $nomeArquivo;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $caminhoArquivo)) {
            $dados['logo'] = $nomeArquivo;
        }
    }

    if (cadastrarEmpresa($dados)) {
        $_SESSION['mensagem'] = 'Empresa cadastrada com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao cadastrar empresa.';
        $_SESSION['tipo_mensagem'] = 'danger';
    }

    header('Location: ../../empresas');
    exit;
} 