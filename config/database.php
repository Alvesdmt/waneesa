<?php
try {
    $host = 'localhost';
    $dbname = 'waneesa_db';
    $username = 'root';
    $password = '';
    $socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';

    // Configurar o fuso horário do PHP para São Paulo
    date_default_timezone_set('America/Sao_Paulo');

    $pdo = new PDO("mysql:unix_socket=$socket;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Configurar o fuso horário para GMT-3 (São Paulo)
    $pdo->exec("SET time_zone = '-03:00'");
    
    // Verificar se o fuso horário foi aplicado
    $stmt = $pdo->query("SELECT NOW() as hora_atual");
    $result = $stmt->fetch();
    error_log("Hora atual no banco: " . $result['hora_atual']);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?> 