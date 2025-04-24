<?php
require_once '../functions/funcionarios.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $funcionario = obterFuncionario($id);
    
    if ($funcionario) {
        echo json_encode($funcionario);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Funcionário não encontrado']);
    }
}
exit(); 