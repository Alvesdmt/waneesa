<?php
// Verifica se a sessão já está ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Inclui a configuração do banco de dados
include '../config/database.php';

// Lista de páginas permitidas para funcionários
$paginas_permitidas = [
    'produtos.php',
    'vendas.php',
    'carnes.php',
    'caixa.php',
    'dashboard.php'
];

// Função para verificar se o usuário tem permissão de funcionário
function verificarPermissaoFuncionario() {
    global $pdo, $paginas_permitidas;
    
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    $sql = "SELECT tipo FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Se for administrador, permite acesso a todas as páginas
    if ($usuario && $usuario['tipo'] === 'admin') {
        return true;
    }
    
    // Se for funcionário, verifica se a página é permitida
    if ($usuario && $usuario['tipo'] === 'funcionario') {
        $pagina_atual = basename($_SERVER['PHP_SELF']);
        return in_array($pagina_atual, $paginas_permitidas);
    }
    
    return false;
}

// Verifica se o usuário tem permissão
if (!verificarPermissaoFuncionario()) {
    $_SESSION['erro'] = "Você não tem permissão para acessar esta página.";
    header("Location: vendas.php");
    exit();
}
?> 