<?php
// Inclui a verificação de permissão para funcionários
include 'verificar_permissao_funcionario.php';

// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Inclui a configuração do banco de dados
include '../config/database.php';

// Log para verificar se o banco está conectado
error_log("Tentando conectar ao banco de dados...");

// Processa a venda se for uma requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['finalizar_venda'])) {
        try {
            $pdo->beginTransaction();

            // Busca o caixa aberto
            $sql = "SELECT id, valor_inicial FROM caixa WHERE status = 'aberto' AND usuario_id = :usuario_id ORDER BY id DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':usuario_id' => $_SESSION['usuario_id']]);
            $caixa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$caixa) {
                echo json_encode(['success' => false, 'message' => 'Nenhum caixa aberto encontrado!']);
                exit;
            }

            // Calcula o valor total
            $valor_total = 0;
            foreach ($_POST['itens'] as $item) {
                $valor_total += floatval($item['preco_unitario']) * intval($item['quantidade']);
            }

            // Insere a venda
            $sql = "INSERT INTO vendas (valor_total, forma_pagamento, data_venda, caixa_id) 
                    VALUES (:valor_total, :forma_pagamento, :data_venda, :caixa_id)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':valor_total' => $valor_total,
                ':forma_pagamento' => $_POST['forma_pagamento'],
                ':data_venda' => date('Y-m-d H:i:s'),
                ':caixa_id' => $caixa['id']
            ]);

            $venda_id = $pdo->lastInsertId();

            // Insere os itens da venda
            $sql = "INSERT INTO itens_venda (venda_id, produto_id, quantidade, valor_unitario) 
                    VALUES (:venda_id, :produto_id, :quantidade, :valor_unitario)";
            
            $stmt = $pdo->prepare($sql);

            foreach ($_POST['itens'] as $item) {
                $stmt->execute([
                    ':venda_id' => $venda_id,
                    ':produto_id' => $item['produto_id'],
                    ':quantidade' => $item['quantidade'],
                    ':valor_unitario' => $item['preco_unitario']
                ]);

                // Atualiza o estoque
                $sql = "UPDATE produtos SET estoque = estoque - :quantidade WHERE id = :produto_id";
                $stmt2 = $pdo->prepare($sql);
                $stmt2->execute([
                    ':quantidade' => $item['quantidade'],
                    ':produto_id' => $item['produto_id']
                ]);
            }

            // Atualiza o valor do caixa
            $sql = "UPDATE caixa SET valor_inicial = valor_inicial + :valor_total WHERE id = :caixa_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':valor_total' => $valor_total,
                ':caixa_id' => $caixa['id']
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Venda realizada com sucesso!']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erro ao realizar venda: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao realizar venda: ' . $e->getMessage()]);
            exit;
        }
    }
}

try {
    // Busca produtos ativos
    $sql = "SELECT id, nome, codigo_barras, preco_venda, estoque FROM produtos WHERE status = 'ativo' ORDER BY nome";
    $stmt = $pdo->query($sql);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Produtos encontrados: " . count($produtos));

    // Busca as últimas 10 vendas
    $sql_vendas = "SELECT v.id, v.valor_total, v.forma_pagamento, v.data_venda, v.status, c.valor_inicial as caixa_valor
                   FROM vendas v 
                   JOIN caixa c ON v.caixa_id = c.id 
                   ORDER BY v.data_venda DESC 
                   LIMIT 10";
    $stmt_vendas = $pdo->query($sql_vendas);
    $ultimas_vendas = $stmt_vendas->fetchAll(PDO::FETCH_ASSOC);
    error_log("Vendas encontradas: " . count($ultimas_vendas));

    // Inclui o layout
    error_log("Tentando incluir o layout...");
    include '../includes/layout.php';
    error_log("Layout incluído com sucesso");

} catch (Exception $e) {
    error_log("Erro ao carregar dados: " . $e->getMessage());
    die("Erro ao carregar a página: " . $e->getMessage());
}
?>

<style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --success-color: #4cc9f0;
        --danger-color: #f72585;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .btn-primary {
        background-color: var(--primary-color);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
    }

    .btn-primary:hover {
        background-color: var(--secondary-color);
    }

    .search-box {
        border-radius: 20px;
        padding: 10px 20px;
        border: 2px solid #e9ecef;
    }

    .search-box:focus {
        border-color: var(--primary-color);
        box-shadow: none;
    }

    .cart-item {
        background-color: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
    }

    .product-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .product-card:hover {
        background-color: #f8f9fa;
    }

    .quantity-control {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    #cart-items {
        max-height: 400px;
        overflow-y: auto;
    }

    .payment-methods {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .payment-method-btn {
        flex: 1;
        padding: 10px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .payment-method-btn.active {
        border-color: var(--primary-color);
        background-color: rgba(67, 97, 238, 0.1);
    }

    /* Estilos para a tabela de últimas vendas */
    .sales-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 1rem;
    }

    .sales-table thead th {
        background-color: var(--primary-color);
        color: white;
        padding: 12px;
        font-weight: 500;
        text-align: left;
    }

    .sales-table tbody tr {
        background-color: white;
        transition: all 0.3s ease;
    }

    .sales-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .sales-table td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
    }

    .sales-table tr:last-child td {
        border-bottom: none;
    }

    .sales-card {
        margin-top: 2rem;
    }

    .sales-card .card-header {
        background-color: white;
        border-bottom: 2px solid var(--primary-color);
    }

    .sales-card .card-title {
        color: var(--primary-color);
        font-weight: 600;
    }

    .payment-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .payment-badge.dinheiro {
        background-color: #e3f2fd;
        color: #1976d2;
    }

    .payment-badge.cartao {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .payment-badge.pix {
        background-color: #fff3e0;
        color: #f57c00;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Área de busca e produtos -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Buscar Produtos</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" id="search-input" class="form-control search-box" placeholder="Digite o nome ou código do produto...">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="row" id="products-grid">
                        <?php foreach ($produtos as $produto): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card product-card" data-product-id="<?= $produto['id'] ?>">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($produto['nome']) ?></h6>
                                        <p class="card-text">
                                            Código: <?= htmlspecialchars($produto['codigo_barras']) ?><br>
                                            Preço: R$ <?= number_format($produto['preco_venda'], 2, ',', '.') ?><br>
                                            Estoque: <?= $produto['estoque'] ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carrinho de compras -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Carrinho de Compras</h5>
                </div>
                <div class="card-body">
                    <div id="cart-items">
                        <!-- Itens do carrinho serão inseridos aqui via JavaScript -->
                    </div>
                    <div class="mt-3">
                        <h6>Total: R$ <span id="total-value">0,00</span></h6>
                    </div>
                    <div class="payment-methods">
                        <div class="payment-method-btn" data-method="dinheiro">
                            <i class="fas fa-money-bill-wave"></i>
                            <div>Dinheiro</div>
                        </div>
                        <div class="payment-method-btn" data-method="cartao">
                            <i class="fas fa-credit-card"></i>
                            <div>Cartão</div>
                        </div>
                        <div class="payment-method-btn" data-method="pix">
                            <i class="fas fa-qrcode"></i>
                            <div>PIX</div>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100" id="finalizar-venda">
                        Finalizar Venda
                    </button>
                </div>
            </div>

            <!-- Tabela de últimas vendas -->
            <div class="card shadow-sm sales-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Últimas Vendas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="sales-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Pagamento</th>
                                    <th>Status</th>
                                    <th>Caixa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimas_vendas as $venda): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                                        <td>R$ <?= number_format($venda['valor_total'], 2, ',', '.') ?></td>
                                        <td>
                                            <span class="payment-badge <?= $venda['forma_pagamento'] ?>">
                                                <?= ucfirst($venda['forma_pagamento']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $venda['status'] == 'concluido' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($venda['status']) ?>
                                            </span>
                                        </td>
                                        <td>R$ <?= number_format($venda['caixa_valor'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        let cart = [];
        let selectedPaymentMethod = null;

        // Função para atualizar o carrinho
        function updateCart() {
            $('#cart-items').empty();
            let total = 0;

            cart.forEach((item, index) => {
                total += item.subtotal;
                $('#cart-items').append(`
                    <div class="cart-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">${item.nome}</h6>
                                <small>R$ ${item.preco_unitario.toFixed(2)} x ${item.quantidade}</small>
                            </div>
                            <div class="quantity-control">
                                <button class="btn btn-sm btn-outline-primary quantity-btn" onclick="updateQuantity(${index}, -1)">-</button>
                                <span>${item.quantidade}</span>
                                <button class="btn btn-sm btn-outline-primary quantity-btn" onclick="updateQuantity(${index}, 1)">+</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="text-end mt-2">
                            <strong>R$ ${item.subtotal.toFixed(2)}</strong>
                        </div>
                    </div>
                `);
            });

            $('#total-value').text(total.toFixed(2));
        }

        // Adicionar produto ao carrinho
        $('.product-card').click(function() {
            const productId = $(this).data('product-id');
            const productName = $(this).find('.card-title').text();
            const price = parseFloat($(this).find('.card-text').text().match(/R\$\s*(\d+,\d+)/)[1].replace(',', '.'));
            
            const existingItem = cart.find(item => item.produto_id === productId);
            
            if (existingItem) {
                existingItem.quantidade++;
                existingItem.subtotal = existingItem.quantidade * existingItem.preco_unitario;
            } else {
                cart.push({
                    produto_id: productId,
                    nome: productName,
                    preco_unitario: price,
                    quantidade: 1,
                    subtotal: price
                });
            }
            
            updateCart();
        });

        // Atualizar quantidade
        window.updateQuantity = function(index, change) {
            cart[index].quantidade += change;
            if (cart[index].quantidade <= 0) {
                cart.splice(index, 1);
            } else {
                cart[index].subtotal = cart[index].quantidade * cart[index].preco_unitario;
            }
            updateCart();
        };

        // Remover item
        window.removeItem = function(index) {
            cart.splice(index, 1);
            updateCart();
        };

        // Selecionar método de pagamento
        $('.payment-method-btn').click(function() {
            $('.payment-method-btn').removeClass('active');
            $(this).addClass('active');
            selectedPaymentMethod = $(this).data('method');
        });

        // Finalizar venda
        $('#finalizar-venda').click(function() {
            if (cart.length === 0) {
                alert('Adicione produtos ao carrinho!');
                return;
            }

            if (!selectedPaymentMethod) {
                alert('Selecione um método de pagamento!');
                return;
            }

            const total = parseFloat($('#total-value').text()) || 0;

            const data = {
                finalizar_venda: true,
                valor_total: total,
                forma_pagamento: selectedPaymentMethod,
                itens: cart.map(item => ({
                    produto_id: item.produto_id,
                    quantidade: item.quantidade,
                    preco_unitario: item.preco_unitario
                }))
            };

            $.ajax({
                url: 'vendas.php',
                method: 'POST',
                data: data,
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert('Venda realizada com sucesso!');
                        cart = [];
                        updateCart();
                        $('.payment-method-btn').removeClass('active');
                        selectedPaymentMethod = null;
                    } else {
                        alert('Erro ao realizar venda: ' + result.message);
                    }
                },
                error: function() {
                    alert('Erro ao processar a venda. Tente novamente.');
                }
            });
        });

        // Busca de produtos
        $('#search-input').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('.product-card').each(function() {
                const productName = $(this).find('.card-title').text().toLowerCase();
                const productCode = $(this).find('.card-text').text().toLowerCase();
                if (productName.includes(searchTerm) || productCode.includes(searchTerm)) {
                    $(this).parent().show();
                } else {
                    $(this).parent().hide();
                }
            });
        });
    });
</script>

                