<?php
session_start();
require_once '../config/database.php';

function login($email, $senha) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            return true;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

function registrar($nome, $email, $senha) {
    global $pdo;
    
    try {
        // Verifica se o email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return false; // Email já existe
        }
        
        // Cria o novo usuário
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $senha_hash]);
        
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function verificarAutenticacao() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../index.php");
        exit();
    }
}
?> 