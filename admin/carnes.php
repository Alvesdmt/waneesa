<?php
// Inclui a verificação de permissão para funcionários
include 'verificar_permissao_funcionario.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
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

// Atualiza a estrutura da tabela parcelas
try {
    // Primeiro, vamos tentar adicionar as colunas diretamente
    $sql_add_status = "ALTER TABLE parcelas ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'Pendente'";
    $pdo->exec($sql_add_status);
    
    $sql_add_data = "ALTER TABLE parcelas ADD COLUMN IF NOT EXISTS data_pagamento DATE DEFAULT NULL";
    $pdo->exec($sql_add_data);
    
    error_log('Estrutura da tabela parcelas atualizada com sucesso');
} catch (Exception $e) {
    error_log('Erro ao atualizar estrutura da tabela: ' . $e->getMessage());
    
    // Se falhar, tenta criar a tabela do zero
    try {
        $sql_create = "CREATE TABLE IF NOT EXISTS parcelas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            carne_id INT NOT NULL,
            numero_parcela INT NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            data_vencimento DATE NOT NULL,
            status VARCHAR(20) DEFAULT 'Pendente',
            data_pagamento DATE DEFAULT NULL,
            FOREIGN KEY (carne_id) REFERENCES carnes(id)
        )";
        $pdo->exec($sql_create);
        error_log('Tabela parcelas criada com sucesso');
    } catch (Exception $e2) {
        error_log('Erro ao criar tabela parcelas: ' . $e2->getMessage());
    }
}

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
            $carne_id = $_POST['compra_id'];
            error_log('Buscando detalhes do carnê ID: ' . $carne_id);
            
            // Busca os dados do carnê com join na tabela de clientes
            $sql = "SELECT c.*, cl.nome as cliente_nome, cl.cpf, cl.telefone, cl.endereco, cl.cidade, cl.estado 
                    FROM carnes c 
                    JOIN clientes cl ON c.cliente_id = cl.id 
                    WHERE c.id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $carne_id]);
            $carne = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($carne) {
                error_log('Carnê encontrado: ' . print_r($carne, true));
                
                // Busca as parcelas do carnê
                $sql_parcelas = "SELECT * FROM parcelas WHERE carne_id = :carne_id ORDER BY numero_parcela";
                $stmt_parcelas = $pdo->prepare($sql_parcelas);
                $stmt_parcelas->execute([':carne_id' => $carne_id]);
                $parcelas = $stmt_parcelas->fetchAll(PDO::FETCH_ASSOC);
                
                error_log('Parcelas encontradas: ' . print_r($parcelas, true));
                
                $carne['parcelas'] = $parcelas;
                
                echo json_encode(['success' => true, 'data' => $carne]);
            } else {
                error_log('Carnê não encontrado para ID: ' . $carne_id);
                echo json_encode(['success' => false, 'message' => 'Carnê não encontrado']);
            }
            exit;
        } catch (Exception $e) {
            error_log('Erro ao buscar detalhes do carnê: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar detalhes do carnê: ' . $e->getMessage()]);
            exit;
        }
    }

    if (isset($_POST['cadastrar_carne'])) {
        try {
            // Validação dos campos obrigatórios
            if (empty($_POST['nome']) || empty($_POST['cpf']) || empty($_POST['telefone']) || 
                empty($_POST['endereco']) || empty($_POST['cidade']) || empty($_POST['estado']) ||
                empty($_POST['valor_total']) || empty($_POST['num_parcelas']) || empty($_POST['data_inicio'])) {
                throw new Exception("Todos os campos marcados com * são obrigatórios");
            }

            $pdo->beginTransaction();

            // Insere o cliente
            $sql_cliente = "INSERT INTO clientes (nome, cpf, telefone, endereco, cidade, estado, cep) 
                           VALUES (:nome, :cpf, :telefone, :endereco, :cidade, :estado, :cep)";
            
            $stmt = $pdo->prepare($sql_cliente);
            $stmt->execute([
                ':nome' => $_POST['nome'],
                ':cpf' => $_POST['cpf'],
                ':telefone' => $_POST['telefone'],
                ':endereco' => $_POST['endereco'],
                ':cidade' => $_POST['cidade'],
                ':estado' => $_POST['estado'],
                ':cep' => $_POST['cep']
            ]);

            $cliente_id = $pdo->lastInsertId();

            // Insere o carnê
            $sql = "INSERT INTO carnes (cliente_id, valor_total, num_parcelas, data_inicio, observacoes) 
                    VALUES (:cliente_id, :valor_total, :num_parcelas, :data_inicio, :observacoes)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':cliente_id' => $cliente_id,
                ':valor_total' => $_POST['valor_total'],
                ':num_parcelas' => $_POST['num_parcelas'],
                ':data_inicio' => $_POST['data_inicio'],
                ':observacoes' => $_POST['observacoes']
            ]);

            $carne_id = $pdo->lastInsertId();

            // Calcular valor da parcela
            $valor_parcela = $_POST['valor_total'] / $_POST['num_parcelas'];

            // Inserir as parcelas
            for ($i = 1; $i <= $_POST['num_parcelas']; $i++) {
                $data_vencimento = date('Y-m-d', strtotime("+$i month", strtotime($_POST['data_inicio'])));
                
                $sql = "INSERT INTO parcelas (carne_id, numero_parcela, valor, data_vencimento) 
                        VALUES (:carne_id, :numero_parcela, :valor, :data_vencimento)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':carne_id' => $carne_id,
                    ':numero_parcela' => $i,
                    ':valor' => $valor_parcela,
                    ':data_vencimento' => $data_vencimento
                ]);
            }

            $pdo->commit();
            $_SESSION['mensagem'] = 'Carnê gerado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: carnes.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = 'Erro ao gerar carnê: ' . $e->getMessage();
            $_SESSION['tipo_mensagem'] = 'danger';
            header('Location: carnes.php');
            exit;
        }
    }

    if (isset($_POST['pagar_parcela'])) {
        try {
            $parcela_id = $_POST['parcela_id'];
            $data_pagamento = date('Y-m-d');
            
            // Primeiro, vamos verificar se a parcela existe
            $sql_check = "SELECT id FROM parcelas WHERE id = :id";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([':id' => $parcela_id]);
            
            if ($stmt_check->rowCount() > 0) {
                // Atualiza o status e a data de pagamento da parcela
                $sql = "UPDATE parcelas SET status = 'Pago', data_pagamento = :data_pagamento WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':data_pagamento', $data_pagamento, PDO::PARAM_STR);
                $stmt->bindValue(':id', $parcela_id, PDO::PARAM_INT);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Parcela paga com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Parcela não encontrada']);
            }
            exit;
        } catch (Exception $e) {
            error_log('Erro ao pagar parcela: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao processar pagamento']);
            exit;
        }
    }

    if (isset($_POST['cadastrar_cliente'])) {
        try {
            // Validação dos campos obrigatórios
            if (empty($_POST['nome']) || empty($_POST['cpf']) || empty($_POST['telefone']) || 
                empty($_POST['endereco']) || empty($_POST['cidade']) || empty($_POST['estado'])) {
                throw new Exception("Todos os campos marcados com * são obrigatórios");
            }

            // Verifica se já existe cliente com mesmo CPF, nome ou telefone
            $sql_check = "SELECT id, nome, cpf, telefone FROM clientes 
                         WHERE cpf = :cpf OR nome = :nome OR telefone = :telefone";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([
                ':cpf' => $_POST['cpf'],
                ':nome' => $_POST['nome'],
                ':telefone' => $_POST['telefone']
            ]);
            
            if ($stmt_check->rowCount() > 0) {
                $cliente_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);
                $mensagem = "Já existe um cliente cadastrado com ";
                $campos = [];
                
                if ($cliente_existente['cpf'] === $_POST['cpf']) {
                    $campos[] = "este CPF";
                }
                if ($cliente_existente['nome'] === $_POST['nome']) {
                    $campos[] = "este nome";
                }
                if ($cliente_existente['telefone'] === $_POST['telefone']) {
                    $campos[] = "este telefone";
                }
                
                throw new Exception($mensagem . implode(" e ", $campos));
            }

            // Insere o cliente
            $sql = "INSERT INTO clientes (nome, cpf, telefone, endereco, cidade, estado, cep) 
                    VALUES (:nome, :cpf, :telefone, :endereco, :cidade, :estado, :cep)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $_POST['nome'],
                ':cpf' => $_POST['cpf'],
                ':telefone' => $_POST['telefone'],
                ':endereco' => $_POST['endereco'],
                ':cidade' => $_POST['cidade'],
                ':estado' => $_POST['estado'],
                ':cep' => $_POST['cep'] ?? null
            ]);

            echo json_encode(['success' => true, 'message' => 'Cliente cadastrado com sucesso!']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar cliente: ' . $e->getMessage()]);
            exit;
        }
    }

    // Processa a busca de carnês
    if (isset($_POST['buscar_carnes'])) {
        $termo = $_POST['termo'] ?? '';
        
        $sql = "SELECT c.*, cl.nome as cliente_nome, cl.cpf,
                (SELECT COUNT(*) FROM parcelas p WHERE p.carne_id = c.id AND (p.status = 'Pendente' OR p.data_pagamento IS NULL)) as total_pendentes,
                (SELECT COUNT(*) FROM parcelas p WHERE p.carne_id = c.id AND (p.status = 'Pendente' OR p.data_pagamento IS NULL) AND p.data_vencimento < CURDATE()) as atrasadas
                FROM carnes c 
                JOIN clientes cl ON c.cliente_id = cl.id";
        
        $params = [];
        
        if (!empty($termo)) {
            $sql .= " WHERE cl.nome LIKE :termo OR cl.cpf LIKE :termo";
            $params[':termo'] = "%$termo%";
        }
        
        $sql .= " ORDER BY c.data_cadastro DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $carnes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'carnes' => $carnes]);
        exit;
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

// Busca todos os carnês
$sql = "SELECT c.*, cl.nome as cliente_nome, cl.cpf 
        FROM carnes c 
        JOIN clientes cl ON c.cliente_id = cl.id 
        ORDER BY c.data_cadastro DESC";
$stmt = $pdo->query($sql);
$carnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inclui o layout
include '../includes/layout.php';

// Adicione isso antes do fechamento do PHP inicial
$data_inicial = $_GET['data_inicial'] ?? '';
$data_final = $_GET['data_final'] ?? '';
$tipo_custo_filtro = $_GET['tipo_custo'] ?? '';
?>

<!-- HTML da página -->
<div class="container mt-4">
    <h2>Geração de Carnês</h2>
    
    <!-- Botões de ação -->
    <div class="mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCarne">
            <i class="fas fa-plus"></i> Novo Carnê
        </button>
       
    </div>

    <!-- Campo de busca -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <input type="text" id="buscaCliente" class="form-control" placeholder="Buscar por nome ou CPF...">
                <button class="btn btn-outline-secondary" type="button" id="btnBuscar">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
        </div>
    </div>

    <!-- Tabela de carnês -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>CPF</th>
                    <th>Valor Total</th>
                    <th>Parcelas</th>
                    <th>Pendentes</th>
                    <th>Status</th>
                    <th>Data Início</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tabelaCarnes">
                <?php foreach ($carnes as $carne): 
                    // Busca informações das parcelas
                    $sql_parcelas = "SELECT 
                        COUNT(*) as total_parcelas,
                        SUM(CASE WHEN status = 'Pendente' OR data_pagamento IS NULL THEN 1 ELSE 0 END) as total_pendentes,
                        SUM(CASE WHEN (status = 'Pendente' OR data_pagamento IS NULL) AND data_vencimento < CURDATE() THEN 1 ELSE 0 END) as atrasadas
                        FROM parcelas 
                        WHERE carne_id = :carne_id";
                    $stmt_parcelas = $pdo->prepare($sql_parcelas);
                    $stmt_parcelas->execute([':carne_id' => $carne['id']]);
                    $info_parcelas = $stmt_parcelas->fetch(PDO::FETCH_ASSOC);
                    
                    $status = 'Em dia';
                    $status_class = 'success';
                    if ($info_parcelas['atrasadas'] > 0) {
                        $status = 'Atrasado';
                        $status_class = 'danger';
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($carne['cliente_nome']) ?></td>
                    <td><?= htmlspecialchars($carne['cpf']) ?></td>
                    <td>R$ <?= number_format($carne['valor_total'], 2, ',', '.') ?></td>
                    <td><?= $carne['num_parcelas'] ?></td>
                    <td><?= $info_parcelas['total_pendentes'] ?></td>
                    <td>
                        <span class="badge bg-<?= $status_class ?>"><?= $status ?></span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($carne['data_inicio'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-info btn-ver-detalhes" data-carne-id="<?= $carne['id'] ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success btn-imprimir" data-carne-id="<?= $carne['id'] ?>">
                            <i class="fas fa-print"></i>
                        </button>
                        <a href="gerar_pdf_promissorias.php?carne_id=<?= $carne['id'] ?>" class="btn btn-sm btn-warning" target="_blank" title="Gerar Notas Promissórias">
                            <i class="fas fa-file-signature"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Novo Carnê -->
<div class="modal fade" id="modalNovoCarne" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Carnê</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCarne" method="POST">
                    <input type="hidden" name="cadastrar_carne" value="1">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo*</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CPF*</label>
                            <input type="text" class="form-control" name="cpf" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Telefone*</label>
                            <input type="text" class="form-control" name="telefone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="cep">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Endereço*</label>
                        <input type="text" class="form-control" name="endereco" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Cidade*</label>
                            <input type="text" class="form-control" name="cidade" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado*</label>
                            <select class="form-select" name="estado" required>
                                <option value="">Selecione...</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="mb-3 mt-4">Dados do Carnê</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Valor Total*</label>
                            <input type="number" class="form-control" name="valor_total" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Número de Parcelas*</label>
                            <input type="number" class="form-control" name="num_parcelas" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data Início*</label>
                            <input type="date" class="form-control" name="data_inicio" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('formCarne').submit()">Gerar Carnê</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes do Carnê -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Carnê</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalhesCarne">
                    <input type="hidden" id="carne_id" value="">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Pagamento -->
<div class="modal fade" id="modalConfirmarPagamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Deseja realmente confirmar o pagamento desta parcela?</p>
                <form id="formPagamento" method="POST">
                    <input type="hidden" name="pagar_parcela" value="1">
                    <input type="hidden" name="parcela_id" id="parcela_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="confirmarPagamento()">Confirmar Pagamento</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Novo Cliente -->
<div class="modal fade" id="modalNovoCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cadastrar Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCliente" method="POST">
                    <input type="hidden" name="cadastrar_cliente" value="1">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo*</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CPF*</label>
                            <input type="text" class="form-control" name="cpf" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Telefone*</label>
                            <input type="text" class="form-control" name="telefone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="cep">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Endereço*</label>
                        <input type="text" class="form-control" name="endereco" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Cidade*</label>
                            <input type="text" class="form-control" name="cidade" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado*</label>
                            <select class="form-select" name="estado" required>
                                <option value="">Selecione...</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="cadastrarCliente()">Cadastrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="js/carnes.js"></script>

<script>
$(document).ready(function() {
    // Máscaras para os campos
    $('input[name="cpf"]').mask('000.000.000-00');
    $('input[name="telefone"]').mask('(00) 00000-0000');
    $('input[name="cep"]').mask('00000-000');
    
    // Define a data atual no campo de data
    document.querySelector('input[name="data_inicio"]').value = new Date().toISOString().split('T')[0];
});

// Busca endereço pelo CEP
document.querySelector('input[name="cep"]').addEventListener('blur', function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length === 8) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (!data.erro) {
                    document.querySelector('input[name="endereco"]').value = data.logradouro;
                    document.querySelector('input[name="cidade"]').value = data.localidade;
                    document.querySelector('select[name="estado"]').value = data.uf;
                }
            });
    }
});
</script>

