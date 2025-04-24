<?php
require_once 'database.php';

function cadastrarEmpresa($dados) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO empresas (
            razao_social, nome_fantasia, cnpj, ie, telefone, email, 
            endereco, numero, complemento, bairro, cidade, estado, cep, logo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        return $stmt->execute([
            $dados['razao_social'],
            $dados['nome_fantasia'],
            $dados['cnpj'],
            $dados['ie'],
            $dados['telefone'],
            $dados['email'],
            $dados['endereco'],
            $dados['numero'],
            $dados['complemento'],
            $dados['bairro'],
            $dados['cidade'],
            $dados['estado'],
            $dados['cep'],
            $dados['logo']
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

function listarEmpresas() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM empresas ORDER BY razao_social");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function buscarEmpresa($id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

function editarEmpresa($id, $dados) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE empresas SET 
            razao_social = ?, nome_fantasia = ?, cnpj = ?, ie = ?, 
            telefone = ?, email = ?, endereco = ?, numero = ?, 
            complemento = ?, bairro = ?, cidade = ?, estado = ?, 
            cep = ?, logo = ? WHERE id = ?");
            
        return $stmt->execute([
            $dados['razao_social'],
            $dados['nome_fantasia'],
            $dados['cnpj'],
            $dados['ie'],
            $dados['telefone'],
            $dados['email'],
            $dados['endereco'],
            $dados['numero'],
            $dados['complemento'],
            $dados['bairro'],
            $dados['cidade'],
            $dados['estado'],
            $dados['cep'],
            $dados['logo'],
            $id
        ]);
    } catch(PDOException $e) {
        return false;
    }
}

function excluirEmpresa($id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM empresas WHERE id = ?");
        return $stmt->execute([$id]);
    } catch(PDOException $e) {
        return false;
    }
} 