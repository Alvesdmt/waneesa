<?php
require_once '../config/database.php';

function cadastrarFuncionario($dados) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO funcionarios (nome, cpf, telefone, email, endereco, cargo, salario, data_admissao) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $dados['nome'],
            $dados['cpf'],
            $dados['telefone'],
            $dados['email'],
            $dados['endereco'],
            $dados['cargo'],
            $dados['salario'],
            $dados['data_admissao']
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

function listarFuncionarios($filtro = null) {
    global $conn;
    
    try {
        $sql = "SELECT * FROM funcionarios";
        $params = [];
        
        if ($filtro) {
            $sql .= " WHERE nome LIKE ? OR cpf LIKE ?";
            $params[] = "%$filtro%";
            $params[] = "%$filtro%";
        }
        
        $sql .= " ORDER BY nome";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function editarFuncionario($id, $dados) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE funcionarios SET 
            nome = ?, 
            cpf = ?, 
            telefone = ?, 
            email = ?, 
            endereco = ?, 
            cargo = ?, 
            salario = ?, 
            data_admissao = ?
            WHERE id = ?");
            
        return $stmt->execute([
            $dados['nome'],
            $dados['cpf'],
            $dados['telefone'],
            $dados['email'],
            $dados['endereco'],
            $dados['cargo'],
            $dados['salario'],
            $dados['data_admissao'],
            $id
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

function excluirFuncionario($id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE funcionarios SET status = 'inativo' WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
}

function obterFuncionario($id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM funcionarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}
?> 