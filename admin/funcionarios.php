<?php
// Inclui a verificação de permissão
include 'verificar_permissao.php';

session_start();

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

// Processa o formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cadastrar_funcionario'])) {
        try {
            $nome = $_POST['nome'];
            $email = $_POST['email'];
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $tipo = $_POST['tipo'];

            $sql = "INSERT INTO usuarios (nome, email, senha, tipo, created_at) 
                    VALUES (:nome, :email, :senha, :tipo, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senha,
                ':tipo' => $tipo
            ]);

            $_SESSION['mensagem'] = "Funcionário cadastrado com sucesso!";
            header("Location: funcionarios.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao cadastrar funcionário: " . $e->getMessage();
        }
    }

    // Processa a exclusão de funcionário
    if (isset($_POST['excluir_funcionario'])) {
        try {
            $id = $_POST['id'];
            $sql = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $_SESSION['mensagem'] = "Funcionário excluído com sucesso!";
            header("Location: funcionarios.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao excluir funcionário: " . $e->getMessage();
        }
    }

    // Processa a compra se for uma requisição POST
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

    // Processa a edição de funcionário
    if (isset($_POST['editar_funcionario'])) {
        try {
            $id = $_POST['id'];
            $nome = $_POST['nome'];
            $email = $_POST['email'];
            $tipo = $_POST['tipo'];

            $sql = "UPDATE usuarios SET 
                    nome = :nome,
                    email = :email,
                    tipo = :tipo
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':tipo' => $tipo,
                ':id' => $id
            ]);

            $_SESSION['mensagem'] = "Funcionário atualizado com sucesso!";
            header("Location: funcionarios.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao atualizar funcionário: " . $e->getMessage();
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

// Busca todos os funcionários
$sql = "SELECT id, nome, email, tipo, created_at FROM usuarios ORDER BY nome";
$stmt = $pdo->query($sql);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca funcionário para edição
$funcionario_edicao = null;
if (isset($_GET['editar'])) {
    $sql = "SELECT * FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_GET['editar']]);
    $funcionario_edicao = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Inclui o layout
include '../includes/layout.php';

// Adicione isso antes do fechamento do PHP inicial
$data_inicial = $_GET['data_inicial'] ?? '';
$data_final = $_GET['data_final'] ?? '';
$tipo_custo_filtro = $_GET['tipo_custo'] ?? '';
?>

<!-- HTML da página -->
<div class="container mt-4">
    <h2>Gerenciamento de Funcionários</h2>

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['mensagem'];
            unset($_SESSION['mensagem']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['erro'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['erro'];
            unset($_SESSION['erro']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Formulário de Cadastro/Edição -->
    <div class="card mb-4">
        <div class="card-header">
            <h4><?php echo $funcionario_edicao ? 'Editar Funcionário' : 'Cadastrar Novo Funcionário'; ?></h4>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?php if ($funcionario_edicao): ?>
                    <input type="hidden" name="id" value="<?php echo $funcionario_edicao['id']; ?>">
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo $funcionario_edicao ? htmlspecialchars($funcionario_edicao['nome']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $funcionario_edicao ? htmlspecialchars($funcionario_edicao['email']) : ''; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <?php if (!$funcionario_edicao): ?>
                    <div class="col-md-6 mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha" required>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6 mb-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="funcionario" <?php echo ($funcionario_edicao && $funcionario_edicao['tipo'] == 'funcionario') ? 'selected' : ''; ?>>Funcionário</option>
                            <option value="admin" <?php echo ($funcionario_edicao && $funcionario_edicao['tipo'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="<?php echo $funcionario_edicao ? 'editar_funcionario' : 'cadastrar_funcionario'; ?>" 
                        class="btn btn-primary">
                    <?php echo $funcionario_edicao ? 'Atualizar' : 'Cadastrar'; ?>
                </button>
                <?php if ($funcionario_edicao): ?>
                    <a href="funcionarios.php" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Lista de Funcionários -->
    <div class="card">
        <div class="card-header">
            <h4>Lista de Funcionários</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Data de Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($funcionarios as $funcionario): ?>
                            <tr>
                                <td><?php echo $funcionario['id']; ?></td>
                                <td><?php echo htmlspecialchars($funcionario['nome']); ?></td>
                                <td><?php echo htmlspecialchars($funcionario['email']); ?></td>
                                <td><?php echo ucfirst($funcionario['tipo']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($funcionario['created_at'])); ?></td>
                                <td>
                                    <a href="funcionarios.php?editar=<?php echo $funcionario['id']; ?>" 
                                       class="btn btn-primary btn-sm">Editar</a>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $funcionario['id']; ?>">
                                        <button type="submit" name="excluir_funcionario" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Tem certeza que deseja excluir este funcionário?')">
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- HTML da página -->

    
  

    

