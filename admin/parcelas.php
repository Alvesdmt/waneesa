<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Inclui a configuração do banco de dados
include '../config/database.php';

// Verifica se foi fornecido o ID da venda
if (!isset($_GET['venda_id'])) {
    header("Location: carnes.php");
    exit();
}

$venda_id = $_GET['venda_id'];

// Processa o pagamento de uma parcela
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_pagamento'])) {
    try {
        $pdo->beginTransaction();

        // Atualiza a parcela
        $sql = "UPDATE parcelas 
                SET status = 'pago', 
                    data_pagamento = :data_pagamento,
                    valor_pago = :valor_pago,
                    juros = :juros
                WHERE id = :parcela_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':data_pagamento' => date('Y-m-d'),
            ':valor_pago' => $_POST['valor_pago'],
            ':juros' => $_POST['juros'],
            ':parcela_id' => $_POST['parcela_id']
        ]);

        // Verifica se todas as parcelas foram pagas
        $sql = "SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) as pagas 
                FROM parcelas 
                WHERE venda_id = :venda_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':venda_id' => $venda_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se todas as parcelas foram pagas, atualiza o status da venda
        if ($result['total'] == $result['pagas']) {
            $sql = "UPDATE vendas_prazo SET status = 'pago' WHERE id = :venda_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':venda_id' => $venda_id]);
        }

        $pdo->commit();
        $mensagem = "Pagamento registrado com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = "Erro ao registrar pagamento: " . $e->getMessage();
    }
}

// Busca informações da venda
$sql = "SELECT v.*, c.nome as cliente_nome, c.cpf, c.endereco 
        FROM vendas_prazo v 
        JOIN clientes c ON v.cliente_id = c.id 
        WHERE v.id = :venda_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':venda_id' => $venda_id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venda) {
    header("Location: carnes.php");
    exit();
}

// Busca as parcelas da venda
$sql = "SELECT * FROM parcelas WHERE venda_id = :venda_id ORDER BY numero_parcela";
$stmt = $pdo->prepare($sql);
$stmt->execute([':venda_id' => $venda_id]);
$parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inclui o layout
include '../includes/layout.php';
?>

<div class="container mt-4">
    <h2>Parcelas da Venda</h2>

    <!-- Mensagens de feedback -->
    <?php if (isset($mensagem)): ?>
        <div class="alert alert-success"><?= $mensagem ?></div>
    <?php endif; ?>
    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>

    <!-- Informações do Cliente -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>Informações do Cliente</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nome:</strong> <?= htmlspecialchars($venda['cliente_nome']) ?></p>
                    <p><strong>CPF:</strong> <?= $venda['cpf'] ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Endereço:</strong> <?= htmlspecialchars($venda['endereco']) ?></p>
                    <p><strong>Valor Total:</strong> R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Parcelas -->
    <div class="card">
        <div class="card-header">
            <h4>Parcelas</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Parcela</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Juros</th>
                            <th>Valor Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcelas as $parcela): 
                            $data_vencimento = new DateTime($parcela['data_vencimento']);
                            $hoje = new DateTime();
                            $dias_atraso = $hoje->diff($data_vencimento)->days;
                            $juros = 0;
                            
                            if ($hoje > $data_vencimento && $parcela['status'] != 'pago') {
                                $juros = $parcela['valor'] * ($venda['juros'] / 100) * ($dias_atraso / 30);
                            }
                        ?>
                        <tr>
                            <td><?= $parcela['numero_parcela'] ?></td>
                            <td>R$ <?= number_format($parcela['valor'], 2, ',', '.') ?></td>
                            <td><?= $data_vencimento->format('d/m/Y') ?></td>
                            <td>R$ <?= number_format($juros, 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($parcela['valor'] + $juros, 2, ',', '.') ?></td>
                            <td>
                                <?php if ($parcela['status'] == 'pago'): ?>
                                    <span class="badge bg-success">Pago</span>
                                <?php elseif ($hoje > $data_vencimento): ?>
                                    <span class="badge bg-danger">Atrasado</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($parcela['status'] != 'pago'): ?>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalPagamento<?= $parcela['id'] ?>">
                                        <i class="fas fa-money-bill-wave"></i> Pagar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal de Pagamento -->
                        <div class="modal fade" id="modalPagamento<?= $parcela['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Registrar Pagamento</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="registrar_pagamento" value="1">
                                            <input type="hidden" name="parcela_id" value="<?= $parcela['id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Valor Original</label>
                                                <input type="text" class="form-control" 
                                                       value="R$ <?= number_format($parcela['valor'], 2, ',', '.') ?>" 
                                                       readonly>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Juros</label>
                                                <input type="text" class="form-control" 
                                                       value="R$ <?= number_format($juros, 2, ',', '.') ?>" 
                                                       readonly>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Valor Total a Pagar</label>
                                                <input type="number" class="form-control" name="valor_pago" 
                                                       value="<?= $parcela['valor'] + $juros ?>" step="0.01" required>
                                            </div>
                                            
                                            <input type="hidden" name="juros" value="<?= $juros ?>">
                                            
                                            <button type="submit" class="btn btn-primary">Confirmar Pagamento</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 