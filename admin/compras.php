<?php
// Inicia a sessão se ainda não estiver iniciada
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

// Verifica se o usuário tem permissão de funcionário
$sql = "SELECT tipo FROM usuarios WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Lista de páginas permitidas para funcionários
$paginas_permitidas = [
    'produtos.php',
    'vendas.php',
    'carnes.php',
    'caixa.php',
    'dashboard.php'
];

// Se não for admin e não for funcionário com acesso à página atual, redireciona
if ($usuario['tipo'] !== 'admin' && 
    ($usuario['tipo'] !== 'funcionario' || !in_array(basename($_SERVER['PHP_SELF']), $paginas_permitidas))) {
    $_SESSION['erro'] = "Você não tem permissão para acessar esta página.";
    header("Location: vendas.php");
    exit();
}

// Inclui a configuração do banco de dados
include '../config/database.php';

// Adicione isso no início do arquivo PHP
error_log('POST data: ' . print_r($_POST, true));

// Adicione temporariamente para debug
$sql_debug = "SELECT tipo_custo, COUNT(*) as total, SUM(valor_total) as soma 
              FROM compras 
              GROUP BY tipo_custo";
$result_debug = $pdo->query($sql_debug)->fetchAll(PDO::FETCH_ASSOC);
error_log('Debug tipos de custo: ' . print_r($result_debug, true));

// Adicione no início do arquivo, após a conexão com o banco
$sql_check = "SHOW CREATE TABLE compras";
$stmt_check = $pdo->query($sql_check);
$table_structure = $stmt_check->fetch(PDO::FETCH_ASSOC);
error_log('Estrutura da tabela compras: ' . print_r($table_structure, true));

// Processa a compra se for uma requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['finalizar_compra'])) {
        try {
            $pdo->beginTransaction();

            // Validação apenas dos campos obrigatórios
            if (empty($_POST['fornecedor']) || empty($_POST['valor_total'])) {
                throw new Exception("Fornecedor e Valor são campos obrigatórios");
            }

            // Validar o tipo_custo
            $tipo_custo = $_POST['tipo_custo'];
            if (!in_array($tipo_custo, ['fixo', 'variavel', 'operacional'])) {
                $tipo_custo = 'operacional'; // valor padrão se não for válido
            }

            // Insere a compra com todos os campos
            $sql = "INSERT INTO compras (fornecedor, data_compra, valor_total, status, tipo_custo) 
                    VALUES (:fornecedor, :data_compra, :valor_total, 'Pendente', :tipo_custo)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':fornecedor' => $_POST['fornecedor'],
                ':data_compra' => $_POST['data_compra'] ?? date('Y-m-d'),
                ':valor_total' => $_POST['valor_total'],
                ':tipo_custo' => $tipo_custo
            ]);

            $compra_id = $pdo->lastInsertId();

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Compra registrada com sucesso!']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar compra: ' . $e->getMessage()]);
            exit;
        }
    }

    // Processar alteração de status da compra
    if (isset($_POST['alterar_status'])) {
        try {
            // Log para debug
            error_log('Dados recebidos para alteração de status: ' . print_r($_POST, true));
            
            // Garante que o status não seja nulo
            $status = $_POST['status'] ?? 'Concluída';
            $compra_id = $_POST['compra_id'] ?? null;
            
            if (!$compra_id) {
                throw new Exception('ID da compra não fornecido');
            }
            
            $sql = "UPDATE compras SET status = :status WHERE id = :id";
            $stmt = $pdo->prepare($sql);
                $stmt->execute([
                ':status' => $status,
                ':id' => $compra_id
            ]);
            
            // Log do resultado
            error_log('Linhas afetadas: ' . $stmt->rowCount());

            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
            exit;
        } catch (Exception $e) {
            error_log('Erro ao atualizar status: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status: ' . $e->getMessage()]);
            exit;
        }
    }

    if (isset($_POST['excluir_compra'])) {
        try {
            $sql = "DELETE FROM compras WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $_POST['compra_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Compra excluída com sucesso!']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir compra: ' . $e->getMessage()]);
            exit;
        }
    }

    if (isset($_POST['ver_detalhes'])) {
        try {
            $sql = "SELECT * FROM compras WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $_POST['compra_id']]);
            $compra = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $compra]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar detalhes: ' . $e->getMessage()]);
            exit;
        }
    }
}

// Lista todas as compras
$where_conditions = [];
$params = [];

if (!empty($_GET['data_inicial'])) {
    $where_conditions[] = "data_compra >= :data_inicial";
    $params[':data_inicial'] = $_GET['data_inicial'];
}

if (!empty($_GET['data_final'])) {
    $where_conditions[] = "data_compra <= :data_final";
    $params[':data_final'] = $_GET['data_final'];
}

if (!empty($_GET['tipo_custo'])) {
    $where_conditions[] = "tipo_custo = :tipo_custo";
    $params[':tipo_custo'] = $_GET['tipo_custo'];
}

$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT id, fornecedor, data_compra, valor_total, status, tipo_custo 
        FROM compras" . $where_clause . " 
        ORDER BY data_compra DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca produtos/serviços ativos
$sql = "SELECT id, nome, codigo_barras, preco_custo, estoque 
        FROM produtos 
        WHERE status = 'ativo' 
        ORDER BY nome";
$stmt = $pdo->query($sql);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cálculo dos totais para o dashboard
$sql_totais = "SELECT 
    SUM(CASE WHEN tipo_custo = 'fixo' THEN valor_total ELSE 0 END) as total_fixo,
    SUM(CASE WHEN tipo_custo = 'variavel' THEN valor_total ELSE 0 END) as total_variavel,
    SUM(CASE WHEN tipo_custo = 'operacional' THEN valor_total ELSE 0 END) as total_operacional,
    SUM(valor_total) as total_geral
    FROM compras 
    WHERE status = 'Concluída'";

// Adiciona filtros se existirem
if (!empty($where_conditions)) {
    $sql_totais .= " AND " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare($sql_totais);
$stmt->execute($params);
$totais = $stmt->fetch(PDO::FETCH_ASSOC);

// Adicione este código logo após a query dos totais
error_log('Totais do dashboard: ' . print_r($totais, true));

// Query de verificação
$sql_verificacao = "SELECT 
    tipo_custo, 
    status, 
    COUNT(*) as quantidade, 
    SUM(valor_total) as total 
    FROM compras 
    GROUP BY tipo_custo, status";
$stmt_verificacao = $pdo->query($sql_verificacao);
$verificacao = $stmt_verificacao->fetchAll(PDO::FETCH_ASSOC);
error_log('Verificação de totais por tipo e status: ' . print_r($verificacao, true));

// Inclui o layout
include '../includes/layout.php';

// Adicione isso antes do fechamento do PHP inicial
$data_inicial = $_GET['data_inicial'] ?? '';
$data_final = $_GET['data_final'] ?? '';
$tipo_custo_filtro = $_GET['tipo_custo'] ?? '';
?>

<!-- HTML da página -->
<div class="container mt-4">
    <h2>Controle de Custos e Despesas</h2>
    
    <!-- Botão para nova compra -->
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalNovaCompra">
        Novo Lançamento
    </button>

    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="dataInicial" value="<?= $data_inicial ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="dataFinal" value="<?= $data_final ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Custo</label>
                    <select class="form-select" id="tipoCusto">
                        <option value="">Todos</option>
                        <option value="fixo" <?= $tipo_custo_filtro === 'fixo' ? 'selected' : '' ?>>Custos Fixos</option>
                        <option value="variavel" <?= $tipo_custo_filtro === 'variavel' ? 'selected' : '' ?>>Custos Variáveis</option>
                        <option value="operacional" <?= $tipo_custo_filtro === 'operacional' ? 'selected' : '' ?>>Custos Operacionais</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-secondary w-100" onclick="filtrarLancamentos()">
                        Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumo -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Custos Fixos</h6>
                    <h4 class="mb-0">R$ <?= number_format($totais['total_fixo'] ?? 0, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Custos Variáveis</h6>
                    <h4 class="mb-0">R$ <?= number_format($totais['total_variavel'] ?? 0, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Operacional</h6>
                    <h4 class="mb-0">R$ <?= number_format($totais['total_operacional'] ?? 0, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Geral</h6>
                    <h4 class="mb-0">R$ <?= number_format($totais['total_geral'] ?? 0, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de lançamentos -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Fornecedor/Descrição</th>
                    <th>Tipo</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compras as $compra): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($compra['data_compra'])) ?></td>
                    <td><?= htmlspecialchars($compra['fornecedor']) ?></td>
                    <td>
                        <span class="badge bg-<?= 
                            $compra['tipo_custo'] === 'fixo' ? 'primary' : 
                            ($compra['tipo_custo'] === 'variavel' ? 'success' : 'info') 
                        ?>">
                            <?= ucfirst($compra['tipo_custo']) ?>
                        </span>
                    </td>
                    <td>Operacional</td>
                    <td>R$ <?= number_format($compra['valor_total'], 2, ',', '.') ?></td>
                    <td>
                        <span class="badge bg-<?= $compra['status'] === 'Concluída' ? 'success' : 
                            ($compra['status'] === 'Pendente' ? 'warning' : 'danger') ?>">
                            <?= $compra['status'] ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="verDetalhes(<?= $compra['id'] ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($compra['status'] === 'Pendente'): ?>
                            <button class="btn btn-sm btn-success" onclick="alterarStatus(<?= $compra['id'] ?>, 'Concluída')">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-danger" onclick="excluirCompra(<?= $compra['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Novo Lançamento -->
<div class="modal fade" id="modalNovaCompra" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Lançamento de Custo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCompra" onsubmit="return false;">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Fornecedor* <small class="text-danger">(Obrigatório)</small></label>
                            <input type="text" class="form-control" name="fornecedor" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo de Custo</label>
                            <select class="form-select" name="tipo_custo" required>
                                <option value="fixo">Fixo</option>
                                <option value="variavel">Variável</option>
                                <option value="operacional">Operacional</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data</label>
                            <input type="date" class="form-control" name="data_compra">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="categoria">
                            <option value="">Selecione...</option>
                            <option value="aluguel">Aluguel</option>
                            <option value="energia">Energia Elétrica</option>
                            <option value="agua">Água</option>
                            <option value="internet">Internet/Telefone</option>
                            <option value="salarios">Salários</option>
                            <option value="marketing">Marketing</option>
                            <option value="manutencao">Manutenção</option>
                            <option value="insumos">Insumos</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição Detalhada</label>
                        <textarea class="form-control" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor Total* <small class="text-danger">(Obrigatório)</small></label>
                        <input type="number" class="form-control" name="valor_total" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Forma de Pagamento</label>
                        <select class="form-select" name="forma_pagamento">
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="cartao">Cartão</option>
                            <option value="boleto">Boleto</option>
                            <option value="transferencia">Transferência</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarLancamento()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Fornecedor:</strong>
                    <p id="detalhe-fornecedor"></p>
                </div>
                <div class="mb-3">
                    <strong>Data:</strong>
                    <p id="detalhe-data"></p>
                </div>
                <div class="mb-3">
                    <strong>Valor Total:</strong>
                    <p id="detalhe-valor"></p>
                </div>
                <div class="mb-3">
                    <strong>Status:</strong>
                    <div class="d-flex align-items-center gap-2">
                        <p id="detalhe-status" class="mb-0"></p>
                        <select id="alterarStatusSelect" class="form-select" style="width: auto;">
                            <option value="Pendente">Pendente</option>
                            <option value="Concluída">Concluída</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                        <button class="btn btn-primary btn-sm" onclick="salvarNovoStatus()">
                            Alterar Status
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="excluirCompraModal()">Excluir</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Define a data atual no campo de data
    document.querySelector('input[name="data_compra"]').value = new Date().toISOString().split('T')[0];
});

// Função para salvar o lançamento
function salvarLancamento() {
    const form = document.getElementById('formCompra');
    const formData = new FormData(form);
    
    // Validar apenas os campos obrigatórios
    const fornecedor = formData.get('fornecedor');
    const valorTotal = formData.get('valor_total');
    const tipoCusto = formData.get('tipo_custo'); // Garantir que pegamos o tipo de custo

    if (!fornecedor || !valorTotal) {
        alert('Por favor, preencha o fornecedor e o valor total');
        return;
    }

    // Criar objeto com todos os dados do formulário
    const dados = {
        finalizar_compra: true,
        fornecedor: fornecedor,
        valor_total: valorTotal,
        tipo_custo: tipoCusto, // Usar o valor do select diretamente
        categoria: formData.get('categoria') || 'outros',
        descricao: formData.get('descricao') || '',
        forma_pagamento: formData.get('forma_pagamento') || 'dinheiro',
        data_compra: formData.get('data_compra') || new Date().toISOString().split('T')[0]
    };

    console.log('Dados sendo enviados:', dados); // Para debug

    fetch('compras.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Erro ao salvar o lançamento: ' + error);
    });
}

// Função para filtrar lançamentos
function filtrarLancamentos() {
    const dataInicial = document.getElementById('dataInicial').value;
    const dataFinal = document.getElementById('dataFinal').value;
    const tipoCusto = document.getElementById('tipoCusto').value;

    // Construir a URL com os parâmetros de filtro
    const params = new URLSearchParams();
    if (dataInicial) params.append('data_inicial', dataInicial);
    if (dataFinal) params.append('data_final', dataFinal);
    if (tipoCusto) params.append('tipo_custo', tipoCusto);

    // Redirecionar para a mesma página com os filtros
    window.location.href = 'compras.php?' + params.toString();
}

// Adicionar item automaticamente quando o modal é aberto
document.getElementById('modalNovaCompra').addEventListener('shown.bs.modal', function () {
    if (document.querySelectorAll('#tabelaItens tbody tr').length === 0) {
        adicionarItem();
    }
});

// Limpar formulário quando o modal for fechado
document.getElementById('modalNovaCompra').addEventListener('hidden.bs.modal', function () {
    document.querySelector('#tabelaItens tbody').innerHTML = '';
    document.querySelector('input[name="fornecedor"]').value = '';
    document.querySelector('#totalGeral').textContent = '0,00';
    document.querySelector('#valorTotal').textContent = '0,00';
});

function alterarStatus(compraId, novoStatus = 'Concluída') {
    if (!confirm(`Deseja realmente alterar o status para ${novoStatus}?`)) {
        return;
    }

    console.log('Alterando status:', { compraId, novoStatus }); // Debug

    const dados = {
        alterar_status: true,
        compra_id: compraId,
        status: novoStatus
    };

    fetch('compras.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status atualizado com sucesso!');
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error); // Debug
        alert('Erro ao atualizar status: ' + error);
    });
}

let compraIdAtual = null; // Variável global para armazenar o ID da compra atual

function verDetalhes(compraId) {
    compraIdAtual = compraId; // Armazena o ID da compra atual
    
    fetch('compras.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            ver_detalhes: true,
            compra_id: compraId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const compra = data.data;
            document.getElementById('detalhe-fornecedor').textContent = compra.fornecedor;
            document.getElementById('detalhe-data').textContent = new Date(compra.data_compra).toLocaleDateString('pt-BR');
            document.getElementById('detalhe-valor').textContent = 'R$ ' + parseFloat(compra.valor_total).toFixed(2).replace('.', ',');
            document.getElementById('detalhe-status').textContent = compra.status;
            
            // Atualiza o select com o status atual
            const statusSelect = document.getElementById('alterarStatusSelect');
            statusSelect.value = compra.status;
            
            const modalDetalhes = new bootstrap.Modal(document.getElementById('modalDetalhes'));
            modalDetalhes.show();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Erro ao buscar detalhes: ' + error);
    });
}

function salvarNovoStatus() {
    if (!compraIdAtual) return;
    
    const novoStatus = document.getElementById('alterarStatusSelect').value;
    
    if (!confirm(`Deseja realmente alterar o status para ${novoStatus}?`)) {
        return;
    }

    const dados = {
        alterar_status: true,
        compra_id: compraIdAtual,
        status: novoStatus
    };

    fetch('compras.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status atualizado com sucesso!');
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Erro ao atualizar status: ' + error);
    });
}

function excluirCompraModal() {
    if (!compraIdAtual) return;
    
    if (!confirm('Tem certeza que deseja excluir esta compra?')) {
        return;
    }

    fetch('compras.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            excluir_compra: true,
            compra_id: compraIdAtual
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Compra excluída com sucesso!');
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Erro ao excluir compra: ' + error);
    });
}
</script>

