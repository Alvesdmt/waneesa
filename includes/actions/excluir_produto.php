<?php
require_once '../functions/produtos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    if (excluirProduto($id)) {
        $_SESSION['mensagem'] = 'Produto excluído com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao excluir produto.';
        $_SESSION['tipo_mensagem'] = 'danger';
    }

    header('Location: ../../produtos');
    exit;
} 