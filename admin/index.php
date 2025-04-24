<?php
// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar log de erros
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

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

try {
    // Inclui a configuração do banco de dados
    if (!file_exists('../config/database.php')) {
        throw new Exception("Arquivo de configuração do banco de dados não encontrado");
    }
    include '../config/database.php';

    // Verifica se a conexão com o banco foi estabelecida
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Falha na conexão com o banco de dados");
    }

    // Função para buscar vendas do mês selecionado
    function getVendasMes($pdo, $mes, $ano) {
        try {
            $query = "SELECT COUNT(*) as total, COALESCE(SUM(valor_total), 0) as valor_total 
                     FROM vendas 
                     WHERE MONTH(data_venda) = :mes 
                     AND YEAR(data_venda) = :ano";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['mes' => $mes, 'ano' => $ano]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar vendas do mês: " . $e->getMessage());
            return ['total' => 0, 'valor_total' => 0];
        }
    }

    // Função para buscar compras do mês selecionado
    function getComprasMes($pdo, $mes, $ano) {
        try {
            $query = "SELECT COUNT(*) as total, COALESCE(SUM(valor_total), 0) as valor_total 
                     FROM compras 
                     WHERE MONTH(data_compra) = :mes 
                     AND YEAR(data_compra) = :ano";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['mes' => $mes, 'ano' => $ano]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar compras do mês: " . $e->getMessage());
            return ['total' => 0, 'valor_total' => 0];
        }
    }

    // Função para buscar total de caixas do mês
    function getTotalCaixasMes($pdo, $mes, $ano) {
        try {
            $query = "SELECT 
                        COUNT(*) as total_caixas,
                        COALESCE(SUM(valor_inicial), 0) as total_inicial,
                        COALESCE(SUM(valor_final), 0) as total_final
                     FROM caixa 
                     WHERE MONTH(data_abertura) = :mes 
                     AND YEAR(data_abertura) = :ano
                     AND status = 'fechado'";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['mes' => $mes, 'ano' => $ano]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar total de caixas: " . $e->getMessage());
            return ['total_caixas' => 0, 'total_inicial' => 0, 'total_final' => 0];
        }
    }

    // Função para buscar parcelas em atraso
    function getParcelasAtraso($pdo) {
        try {
            $query = "SELECT p.*, c.nome as cliente_nome 
                     FROM parcelas p 
                     JOIN carnes cr ON p.carne_id = cr.id 
                     JOIN clientes c ON cr.cliente_id = c.id 
                     WHERE p.status = 'Pendente' 
                     AND p.data_vencimento < CURDATE()
                     ORDER BY p.data_vencimento ASC LIMIT 5";
            $stmt = $pdo->query($query);
            if ($stmt === false) {
                throw new PDOException("Erro na consulta de parcelas em atraso");
            }
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro ao buscar parcelas em atraso: " . $e->getMessage());
            return null;
        }
    }

    // Funções para buscar estatísticas
    function getTotalVendas($pdo, $mes, $ano) {
        try {
            $query = "SELECT COUNT(*) as total, COALESCE(SUM(valor_total), 0) as valor_total 
                     FROM vendas 
                     WHERE MONTH(data_venda) = :mes 
                     AND YEAR(data_venda) = :ano";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['mes' => $mes, 'ano' => $ano]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar total de vendas: " . $e->getMessage());
            return ['total' => 0, 'valor_total' => 0];
        }
    }

    function getTotalClientes($pdo) {
        try {
            $query = "SELECT COUNT(*) as total FROM clientes";
            $stmt = $pdo->query($query);
            if ($stmt === false) {
                throw new PDOException("Erro na consulta de clientes");
            }
            $result = $stmt->fetch();
            return $result ? $result['total'] : 0;
        } catch (PDOException $e) {
            error_log("Erro ao buscar total de clientes: " . $e->getMessage());
            return 0;
        }
    }

    function getTotalProdutos($pdo) {
        try {
            $query = "SELECT COUNT(*) as total, COALESCE(SUM(estoque), 0) as estoque_total 
                     FROM produtos 
                     WHERE status = 'ativo'";
            $stmt = $pdo->query($query);
            if ($stmt === false) {
                throw new PDOException("Erro na consulta de produtos");
            }
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar total de produtos: " . $e->getMessage());
            return ['total' => 0, 'estoque_total' => 0];
        }
    }

    function getTotalCompras($pdo, $mes, $ano) {
        try {
            $query = "SELECT COUNT(*) as total, COALESCE(SUM(valor_total), 0) as valor_total 
                     FROM compras 
                     WHERE MONTH(data_compra) = :mes 
                     AND YEAR(data_compra) = :ano";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['mes' => $mes, 'ano' => $ano]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar total de compras: " . $e->getMessage());
            return ['total' => 0, 'valor_total' => 0];
        }
    }

    function getCaixaAtual($pdo, $mes, $ano) {
        try {
            $query = "SELECT * FROM caixa 
                     WHERE status = 'aberto' 
                     AND MONTH(data_abertura) = :mes 
                     AND YEAR(data_abertura) = :ano
                     ORDER BY id DESC LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['mes' => $mes, 'ano' => $ano]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar status do caixa: " . $e->getMessage());
            return null;
        }
    }

    function getUltimasVendas($pdo) {
        try {
            $query = "SELECT 
                        v.id,
                        v.valor_total,
                        v.forma_pagamento,
                        v.data_venda,
                        v.status,
                        v.caixa_id,
                        c.nome as cliente_nome 
                     FROM vendas v 
                     LEFT JOIN clientes c ON v.cliente_id = c.id 
                     ORDER BY v.data_venda DESC LIMIT 5";
            $stmt = $pdo->query($query);
            if ($stmt === false) {
                throw new PDOException("Erro na consulta de últimas vendas");
            }
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro ao buscar últimas vendas: " . $e->getMessage());
            return null;
        }
    }

    function getProdutosBaixoEstoque($pdo) {
        try {
            $query = "SELECT * FROM produtos WHERE estoque <= 5 AND status = 'ativo' ORDER BY estoque ASC LIMIT 5";
            $stmt = $pdo->query($query);
            if ($stmt === false) {
                throw new PDOException("Erro na consulta de produtos com baixo estoque");
            }
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro ao buscar produtos com baixo estoque: " . $e->getMessage());
            return null;
        }
    }

    function getParcelasPendentes($pdo) {
        try {
            $query = "SELECT p.*, c.nome as cliente_nome 
                     FROM parcelas p 
                     JOIN carnes cr ON p.carne_id = cr.id 
                     JOIN clientes c ON cr.cliente_id = c.id 
                     WHERE p.status = 'Pendente' 
                     ORDER BY p.data_vencimento ASC LIMIT 5";
            $stmt = $pdo->query($query);
            if ($stmt === false) {
                throw new PDOException("Erro na consulta de parcelas pendentes");
            }
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro ao buscar parcelas pendentes: " . $e->getMessage());
            return null;
        }
    }

    // Função para buscar todos os caixas
    function getAllCaixas($pdo, $mes, $ano) {
        try {
            $query = "SELECT c.*, u.nome as usuario_nome 
                     FROM caixa c 
                     LEFT JOIN usuarios u ON c.usuario_id = u.id 
                     WHERE MONTH(c.data_abertura) = :mes 
                     AND YEAR(c.data_abertura) = :ano
                     ORDER BY c.data_abertura DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['mes' => $mes, 'ano' => $ano]);
            if ($stmt === false) {
                throw new PDOException("Erro na consulta de caixas");
            }
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro ao buscar caixas: " . $e->getMessage());
            return null;
        }
    }

    // Obter mês e ano dos filtros
    $mesSelecionado = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
    $anoSelecionado = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');

    // Buscar estatísticas do mês selecionado
    $vendasMes = getVendasMes($pdo, $mesSelecionado, $anoSelecionado);
    $comprasMes = getComprasMes($pdo, $mesSelecionado, $anoSelecionado);
    $totalCaixas = getTotalCaixasMes($pdo, $mesSelecionado, $anoSelecionado);
    $parcelasAtraso = getParcelasAtraso($pdo);
    $ultimasVendas = getUltimasVendas($pdo);
    $parcelasPendentes = getParcelasPendentes($pdo);

    // Buscar estatísticas gerais
    $totalVendas = getTotalVendas($pdo, $mesSelecionado, $anoSelecionado);
    $totalClientes = getTotalClientes($pdo);
    $totalProdutos = getTotalProdutos($pdo);
    $totalCompras = getTotalCompras($pdo, $mesSelecionado, $anoSelecionado);
    $caixaAtual = getCaixaAtual($pdo, $mesSelecionado, $anoSelecionado);
    $produtosBaixoEstoque = getProdutosBaixoEstoque($pdo);
    $todosCaixas = getAllCaixas($pdo, $mesSelecionado, $anoSelecionado);

    // Inclui o layout
    if (!file_exists('../includes/layout.php')) {
        throw new Exception("Arquivo de layout não encontrado");
    }
    include '../includes/layout.php';

} catch (Exception $e) {
    error_log($e->getMessage());
    echo "<div class='alert alert-danger'>";
    echo "<h4>Erro no Sistema</h4>";
    echo "<p>Ocorreu um erro ao carregar o dashboard. Por favor, tente novamente mais tarde.</p>";
    echo "<p>Detalhes do erro: " . $e->getMessage() . "</p>";
    echo "</div>";
    exit();
}
?>

<div class="container-fluid">
    <h1 class="mb-4">Dashboard Administrativo</h1>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mes">Mês</label>
                                <select name="mes" id="mes" class="form-control">
                                    <?php
                                    $meses = [
                                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
                                        4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
                                        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro',
                                        10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                    ];
                                    foreach ($meses as $numero => $nome) {
                                        $selected = $numero == $mesSelecionado ? 'selected' : '';
                                        echo "<option value='$numero' $selected>$nome</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ano">Ano</label>
                                <select name="ano" id="ano" class="form-control">
                                    <?php
                                    $anoAtual = date('Y');
                                    for ($i = $anoAtual; $i >= $anoAtual - 5; $i--) {
                                        $selected = $i == $anoSelecionado ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary form-control">Filtrar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Estatísticas do Mês -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Vendas do Mês</h5>
                    <h2 class="card-text">R$ <?php echo number_format($vendasMes['valor_total'], 2, ',', '.'); ?></h2>
                    <p class="card-text"><?php echo $vendasMes['total']; ?> vendas realizadas</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Compras do Mês</h5>
                    <h2 class="card-text">R$ <?php echo number_format($comprasMes['valor_total'], 2, ',', '.'); ?></h2>
                    <p class="card-text"><?php echo $comprasMes['total']; ?> compras realizadas</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Caixas</h5>
                    <h2 class="card-text">R$ <?php echo number_format($totalCaixas['total_final'], 2, ',', '.'); ?></h2>
                    <p class="card-text"><?php echo $totalCaixas['total_caixas']; ?> caixas fechados</p>
                    <small>Valor Inicial: R$ <?php echo number_format($totalCaixas['total_inicial'], 2, ',', '.'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Vendas</h5>
                    <h2 class="card-text">R$ <?php echo number_format($totalVendas['valor_total'], 2, ',', '.'); ?></h2>
                    <p class="card-text"><?php echo $totalVendas['total']; ?> vendas realizadas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Clientes</h5>
                    <h2 class="card-text"><?php echo $totalClientes; ?></h2>
                    <p class="card-text">clientes cadastrados</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Produtos</h5>
                    <h2 class="card-text"><?php echo $totalProdutos['total']; ?></h2>
                    <p class="card-text"><?php echo $totalProdutos['estoque_total']; ?> itens em estoque</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Compras</h5>
                    <h2 class="card-text">R$ <?php echo number_format($totalCompras['valor_total'], 2, ',', '.'); ?></h2>
                    <p class="card-text"><?php echo $totalCompras['total']; ?> compras realizadas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status do Caixa -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status do Caixa</h5>
                </div>
                <div class="card-body">
                    <?php if ($caixaAtual): ?>
                        <div class="alert alert-danger">
                            <h6>Caixa Aberto</h6>
                            <p>Valor Inicial: R$ <?php echo number_format($caixaAtual['valor_inicial'], 2, ',', '.'); ?></p>
                            <p>Aberto em: <?php echo date('d/m/Y H:i', strtotime($caixaAtual['data_abertura'])); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <h6>Caixa Fechado</h6>
                            <p>Não há caixa aberto no momento</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

   

        <!-- Produtos com Baixo Estoque -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Produtos com Baixo Estoque</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Estoque</th>
                                    <th>Preço de Venda</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($produtosBaixoEstoque): ?>
                                    <?php while ($produto = $produtosBaixoEstoque->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo $produto['nome']; ?></td>
                                            <td><?php echo $produto['estoque']; ?></td>
                                            <td>R$ <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Nenhum produto com baixo estoque</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Parcelas Pendentes -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Parcelas Pendentes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($parcelasPendentes): ?>
                                    <?php while ($parcela = $parcelasPendentes->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo $parcela['cliente_nome']; ?></td>
                                            <td>R$ <?php echo number_format($parcela['valor'], 2, ',', '.'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($parcela['data_vencimento'])); ?></td>
                                            <td><?php echo $parcela['status']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Nenhuma parcela pendente</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Parcelas em Atraso -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Parcelas em Atraso</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Dias em Atraso</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($parcelasAtraso): ?>
                                    <?php while ($parcela = $parcelasAtraso->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo $parcela['cliente_nome']; ?></td>
                                            <td>R$ <?php echo number_format($parcela['valor'], 2, ',', '.'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($parcela['data_vencimento'])); ?></td>
                                            <td><?php 
                                                $diasAtraso = floor((time() - strtotime($parcela['data_vencimento'])) / (60 * 60 * 24));
                                                echo "<span class='text-danger'>" . $diasAtraso . "</span>";
                                            ?></td>
                                            <td><span class="badge badge-danger text-danger">Em Atraso</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhuma parcela em atraso</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Todos os Caixas -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Histórico de Caixas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuário</th>
                                    <th>Valor Inicial</th>
                                    <th>Data Abertura</th>
                                    <th>Data Fechamento</th>
                                    <th>Status</th>
                                    <th>Valor Final</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($todosCaixas): ?>
                                    <?php while ($caixa = $todosCaixas->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo $caixa['id']; ?></td>
                                            <td><?php echo $caixa['usuario_nome']; ?></td>
                                            <td>R$ <?php echo number_format($caixa['valor_inicial'], 2, ',', '.'); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($caixa['data_abertura'])); ?></td>
                                            <td><?php echo $caixa['data_fechamento'] ? date('d/m/Y H:i', strtotime($caixa['data_fechamento'])) : '-'; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $caixa['status'] == 'aberto' ? 'danger' : 'success'; ?>">
                                                    <span class="text-<?php echo $caixa['status'] == 'aberto' ? 'danger' : 'success'; ?>">
                                                        <?php echo ucfirst($caixa['status']); ?>
                                                    </span>
                                                </span>
                                            </td>
                                            <td>R$ <?php echo $caixa['valor_final'] ? number_format($caixa['valor_final'], 2, ',', '.') : '-'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info">Copiar</button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Nenhum caixa encontrado</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        margin-bottom: 1rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .table th {
        font-weight: 600;
    }
    
    .alert {
        margin-bottom: 0;
    }
</style> 