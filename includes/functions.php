<?php
function formatarMoeda($valor) {
    return number_format($valor, 2, ',', '.');
}

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

function formatarDataHora($data) {
    return date('d/m/Y H:i', strtotime($data));
}

function validarPermissao($permissao) {
    if (!isset($_SESSION['usuario_permissoes']) || 
        !in_array($permissao, $_SESSION['usuario_permissoes'])) {
        header('Location: index.php');
        exit();
    }
}

function gerarSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', "-", $string);
    return $string;
}

function uploadImagem($arquivo, $pasta = 'uploads') {
    if (!isset($arquivo['error']) || $arquivo['error'] !== 0) {
        return false;
    }

    $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
    
    if (!in_array(strtolower($extensao), $extensoes_permitidas)) {
        return false;
    }

    $nome_arquivo = uniqid() . '.' . $extensao;
    $caminho = $pasta . '/' . $nome_arquivo;

    if (move_uploaded_file($arquivo['tmp_name'], $caminho)) {
        return $nome_arquivo;
    }

    return false;
} 