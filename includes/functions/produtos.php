<?php
require_once 'database.php';

function listarProdutos() {
    global $conn;
    $sql = "SELECT * FROM produtos ORDER BY nome";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function buscarProduto($id) {
    global $conn;
    $sql = "SELECT * FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function adicionarProduto($nome, $descricao, $codigo_barras, $preco_custo, $preco_venda, $estoque, $foto = null) {
    global $conn;
    $sql = "INSERT INTO produtos (nome, descricao, codigo_barras, preco_custo, preco_venda, estoque, foto) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssddds", $nome, $descricao, $codigo_barras, $preco_custo, $preco_venda, $estoque, $foto);
    return $stmt->execute();
}

function editarProduto($id, $nome, $descricao, $codigo_barras, $preco_custo, $preco_venda, $estoque, $foto = null) {
    global $conn;
    $sql = "UPDATE produtos SET nome = ?, descricao = ?, codigo_barras = ?, preco_custo = ?, preco_venda = ?, estoque = ?";
    if ($foto) {
        $sql .= ", foto = ?";
    }
    $sql .= " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($foto) {
        $stmt->bind_param("sssdddsi", $nome, $descricao, $codigo_barras, $preco_custo, $preco_venda, $estoque, $foto, $id);
    } else {
        $stmt->bind_param("sssdddi", $nome, $descricao, $codigo_barras, $preco_custo, $preco_venda, $estoque, $id);
    }
    return $stmt->execute();
}

function excluirProduto($id) {
    global $conn;
    $sql = "DELETE FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function buscarProdutoPorNome($nome) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM produtos WHERE nome LIKE ? AND status = 'ativo'");
        $stmt->execute(["%$nome%"]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}

function buscarProdutoPorCodigo($codigo) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM produtos WHERE codigo_barras = ? AND status = 'ativo'");
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

function listarProdutos($filtro = null) {
    global $conn;
    
    try {
        $sql = "SELECT * FROM produtos WHERE status = 'ativo'";
        $params = [];
        
        if ($filtro) {
            $sql .= " AND (nome LIKE ? OR codigo_barras LIKE ?)";
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

function obterProduto($id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

function atualizarEstoque($id, $quantidade) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id = ?");
        return $stmt->execute([$quantidade, $id]);
    } catch(PDOException $e) {
        return false;
    }
}

function gerarEtiquetaProduto($idProduto, $quantidade) {
    global $conn;
    
    try {
        $produto = obterProduto($idProduto);
        if (!$produto) return false;
        
        // Aqui você pode implementar a geração da etiqueta
        // Por exemplo, usando uma biblioteca de geração de PDF ou HTML
        
        return true;
    } catch(PDOException $e) {
        return false;
    }
}
?> 