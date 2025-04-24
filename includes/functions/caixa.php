<?php
require_once '../config/database.php';

function abrirCaixa($usuarioId, $valorInicial) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO caixa (usuario_id, valor_inicial, data_abertura, status) VALUES (?, ?, NOW(), 'aberto')");
        $stmt->execute([$usuarioId, $valorInicial]);
        return $conn->lastInsertId();
    } catch(PDOException $e) {
        return false;
    }
}

function fecharCaixa($usuarioId, $dataHoraFechamento) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE caixa SET status = 'fechado', data_fechamento = ? WHERE usuario_id = ? AND status = 'aberto'");
        return $stmt->execute([$dataHoraFechamento, $usuarioId]);
    } catch(PDOException $e) {
        return false;
    }
}

function obterStatusCaixaAtual() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT status FROM caixa WHERE status = 'aberto' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $resultado = $stmt->fetch();
        
        return $resultado ? $resultado['status'] : 'fechado';
    } catch(PDOException $e) {
        return 'erro';
    }
}

function registrarMovimentoCaixa($tipo, $valor, $descricao) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO movimentos_caixa (tipo, valor, descricao, data_movimento) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$tipo, $valor, $descricao]);
    } catch(PDOException $e) {
        return false;
    }
}

function obterSaldoAtual() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT 
            (SELECT COALESCE(SUM(valor), 0) FROM movimentos_caixa WHERE tipo = 'entrada') -
            (SELECT COALESCE(SUM(valor), 0) FROM movimentos_caixa WHERE tipo = 'saida') as saldo");
        $stmt->execute();
        $resultado = $stmt->fetch();
        
        return $resultado['saldo'];
    } catch(PDOException $e) {
        return 0;
    }
}

function listarMovimentosCaixa($dataInicio = null, $dataFim = null) {
    global $conn;
    
    try {
        $sql = "SELECT * FROM movimentos_caixa";
        $params = [];
        
        if ($dataInicio && $dataFim) {
            $sql .= " WHERE data_movimento BETWEEN ? AND ?";
            $params[] = $dataInicio;
            $params[] = $dataFim;
        }
        
        $sql .= " ORDER BY data_movimento DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
    }
}
?> 