<?php
require_once '../functions/produtos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome' => $_POST['nome'],
        'descricao' => $_POST['descricao'],
        'codigo_barras' => $_POST['codigo_barras'],
        'preco_custo' => $_POST['preco_custo'],
        'preco_venda' => $_POST['preco_venda'],
        'estoque' => $_POST['estoque'],
        'foto' => null
    ];

    // Upload da foto se existir
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid() . '.' . $extensao;
        $caminhoArquivo = $uploadDir . $nomeArquivo;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoArquivo)) {
            $dados['foto'] = $nomeArquivo;
        }
    }

    if (cadastrarProduto($dados)) {
        $_SESSION['mensagem'] = 'Produto cadastrado com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao cadastrar produto.';
        $_SESSION['tipo_mensagem'] = 'danger';
    }

    header('Location: ../../produtos');
    exit;
} 