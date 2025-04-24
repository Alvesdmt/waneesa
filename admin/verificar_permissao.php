<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Inclui a configuração do banco de dados
include '../config/database.php';

// Função para verificar se o usuário tem permissão de administrador
function verificarPermissaoAdmin() {
    global $pdo;
    
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    $sql = "SELECT tipo FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ($usuario && $usuario['tipo'] === 'admin');
}

// Verifica se o usuário é administrador
if (!verificarPermissaoAdmin()) {
    $_SESSION['erro'] = "Você não tem permissão para acessar esta página.";
    header("Location: vendas");
    exit();
}
?> 