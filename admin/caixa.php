<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log inicial
error_log("=== Início do processamento da página caixa.php ===");
error_log("Sessão iniciada - ID da sessão: " . session_id());
error_log("Dados da sessão: " . print_r($_SESSION, true));

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    error_log("Usuário não autenticado - Redirecionando para index.php");
    header("Location: ../index.php");
    exit();
}

error_log("Usuário autenticado - ID: " . $_SESSION['usuario_id']);

// Inclui a configuração do banco de dados
try {
    error_log("Tentando incluir arquivo de configuração do banco de dados");
    include '../config/database.php';
    error_log("Arquivo de configuração do banco incluído com sucesso");
} catch (Exception $e) {
    error_log("Erro ao incluir configuração do banco: " . $e->getMessage());
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Processa a abertura/fechamento do caixa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Requisição POST recebida");
    error_log("Dados POST: " . print_r($_POST, true));

    if (isset($_POST['abrir_caixa'])) {
        try {
            error_log("Tentando abrir caixa");
            // Verifica se já existe caixa aberto
            $sql = "SELECT id FROM caixa WHERE usuario_id = :usuario_id AND status = 'aberto'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':usuario_id' => $_SESSION['usuario_id']]);
            
            if ($stmt->fetch()) {
                error_log("Já existe um caixa aberto para este usuário");
                die(json_encode(['success' => false, 'message' => 'Já existe um caixa aberto.']));
            }

            // Abre o caixa
            $sql = "INSERT INTO caixa (valor_inicial, usuario_id, data_abertura, status) VALUES (:valor_inicial, :usuario_id, NOW(), 'aberto')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':valor_inicial' => $_POST['valor_inicial'],
                ':usuario_id' => $_SESSION['usuario_id']
            ]);
            error_log("Caixa aberto com sucesso");

            echo json_encode(['success' => true, 'message' => 'Caixa aberto com sucesso!']);
            exit;
        } catch (Exception $e) {
            error_log("Erro ao abrir caixa: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao abrir caixa: ' . $e->getMessage()]);
            exit;
        }
    }

    if (isset($_POST['fechar_caixa'])) {
        try {
            error_log("Tentando fechar caixa");
            $pdo->beginTransaction();

            // Busca o caixa aberto
            $sql = "SELECT id, valor_inicial FROM caixa WHERE usuario_id = :usuario_id AND status = 'aberto'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':usuario_id' => $_SESSION['usuario_id']]);
            $caixa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$caixa) {
                error_log("Nenhum caixa aberto encontrado");
                throw new Exception('Não existe caixa aberto.');
            }

            error_log("Caixa encontrado: " . print_r($caixa, true));

            // Calcula o valor final
            $sql = "SELECT 
                    SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas,
                    SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas
                    FROM movimentacoes_caixa 
                    WHERE caixa_id = :caixa_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':caixa_id' => $caixa['id']]);
            $movimentacoes = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Movimentações calculadas: " . print_r($movimentacoes, true));

            $valor_final = $caixa['valor_inicial'] + $movimentacoes['entradas'] - $movimentacoes['saidas'];

            // Fecha o caixa
            $sql = "UPDATE caixa SET 
                    status = 'fechado',
                    data_fechamento = NOW(),
                    valor_final = :valor_final
                    WHERE id = :caixa_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':valor_final' => $valor_final,
                ':caixa_id' => $caixa['id']
            ]);

            $pdo->commit();
            error_log("Caixa fechado com sucesso");
            echo json_encode(['success' => true, 'message' => 'Caixa fechado com sucesso!']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erro ao fechar caixa: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao fechar caixa: ' . $e->getMessage()]);
            exit;
        }
    }
}

try {
    error_log("Buscando status do caixa atual");
    // Busca o status do caixa
    $sql = "SELECT c.*, 
            COALESCE(SUM(CASE WHEN m.tipo = 'entrada' THEN m.valor ELSE 0 END), 0) as entradas,
            COALESCE(SUM(CASE WHEN m.tipo = 'saida' THEN m.valor ELSE 0 END), 0) as saidas
            FROM caixa c
            LEFT JOIN movimentacoes_caixa m ON m.caixa_id = c.id
            WHERE c.usuario_id = :usuario_id AND c.status = 'aberto'
            GROUP BY c.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario_id' => $_SESSION['usuario_id']]);
    $caixa = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Resultado da busca do caixa: " . ($caixa ? "Caixa encontrado" : "Nenhum caixa aberto"));
    if ($caixa) {
        error_log("Dados do caixa: " . print_r($caixa, true));
    }

    // Inclui o layout
    error_log("Tentando incluir o arquivo de layout");
    include '../includes/layout.php';
    error_log("Layout incluído com sucesso");

} catch (Exception $e) {
    error_log("Erro fatal na página: " . $e->getMessage());
    die("Erro ao carregar a página: " . $e->getMessage());
}

error_log("=== Fim do processamento da página caixa.php ===");
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Controle de Caixa</h5>
                </div>
                <div class="card-body">
                    <?php if (!$caixa): ?>
                    <!-- Formulário para abrir caixa -->
                    <form id="formAbrirCaixa">
                        <div class="form-group mb-3">
                            <label for="valor_inicial">Valor Inicial</label>
                            <input type="text" class="form-control money-value" id="valor_inicial" required>
                        </div>
                        <button type="submit" class="btn btn-primary" id="btnAbrirCaixa">
                            <i class="fas fa-lock-open"></i> Abrir Caixa
                        </button>
                    </form>
                    <?php else: ?>
                    <!-- Informações do caixa aberto -->
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Data de Abertura:</strong> <?= date('d/m/Y H:i', strtotime($caixa['data_abertura'])) ?></p>
                            <p><strong>Valor Inicial:</strong> R$ <?= number_format($caixa['valor_inicial'], 2, ',', '.') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Entradas:</strong> R$ <?= number_format($caixa['entradas'], 2, ',', '.') ?></p>
                            <p><strong>Saídas:</strong> R$ <?= number_format($caixa['saidas'], 2, ',', '.') ?></p>
                            <p><strong>Saldo Atual:</strong> R$ <?= number_format($caixa['valor_inicial'] + $caixa['entradas'] - $caixa['saidas'], 2, ',', '.') ?></p>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-danger" id="btnFecharCaixa">
                            <i class="fas fa-lock"></i> Fechar Caixa
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adiciona SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para formatar valores monetários
    function formatarValor(valor) {
        return valor.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    // Formatação do campo de valor
    const valorInicial = document.getElementById('valor_inicial');
    if (valorInicial) {
        valorInicial.addEventListener('input', function() {
            let valor = this.value.replace(/[^\d,]/g, '');
            valor = valor.replace(',', '.');
            valor = parseFloat(valor) || 0;
            this.value = formatarValor(valor);
        });
    }

    // Evento de abrir caixa
    const formAbrirCaixa = document.getElementById('formAbrirCaixa');
    if (formAbrirCaixa) {
        formAbrirCaixa.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const valor = parseFloat(document.getElementById('valor_inicial').value.replace(/[^\d,]/g, '').replace(',', '.')) || 0;
            
            fetch('caixa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    abrir_caixa: true,
                    valor_inicial: valor
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro ao abrir caixa. Tente novamente.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            });
        });
    }

    // Evento de fechar caixa
    const btnFecharCaixa = document.getElementById('btnFecharCaixa');
    if (btnFecharCaixa) {
        btnFecharCaixa.addEventListener('click', function() {
            Swal.fire({
                title: 'Confirmar Fechamento',
                text: 'Deseja realmente fechar o caixa?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, fechar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('caixa.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            fechar_caixa: true
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Sucesso!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Erro ao fechar caixa. Tente novamente.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    });
                }
            });
        });
    }
});
</script>

<style>
/* Variáveis de cores */
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #2ecc71;
    --danger-color: #e74c3c;
    --warning-color: #f1c40f;
    --light-color: #ecf0f1;
    --dark-color: #2c3e50;
    --border-radius: 8px;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

/* Estilos gerais */
body {
    background-color: #f5f6fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.card-header {
    background-color: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

/* Botões */
.btn {
    border-radius: var(--border-radius);
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    transition: var(--transition);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-primary {
    background-color: var(--secondary-color);
    border: none;
}

.btn-primary:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
}

.btn-danger {
    background-color: var(--danger-color);
    border: none;
}

.btn-danger:hover {
    background-color: #c0392b;
    transform: translateY(-2px);
}

/* Cards de resumo */
.card.bg-light {
    background-color: white !important;
    border-left: 4px solid var(--secondary-color);
}

.card.bg-success {
    background-color: var(--success-color) !important;
    border-left: 4px solid #27ae60;
}

.card.bg-danger {
    background-color: var(--danger-color) !important;
    border-left: 4px solid #c0392b;
}

.card.bg-light, .card.bg-success, .card.bg-danger {
    transition: var(--transition);
}

.card.bg-light:hover, .card.bg-success:hover, .card.bg-danger:hover {
    transform: translateY(-2px);
    box-shadow: var(--box-shadow);
}

/* Tabela */
.table {
    background-color: white;
    border-radius: var(--border-radius);
    overflow: hidden;
}

.table th {
    background-color: var(--primary-color);
    color: white;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    padding: 1rem;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: rgba(52, 152, 219, 0.1);
    transition: var(--transition);
}

/* Badges */
.badge {
    padding: 0.5rem 1rem;
    font-weight: 500;
    border-radius: 20px;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.badge.bg-success {
    background-color: var(--success-color) !important;
}

.badge.bg-danger {
    background-color: var(--danger-color) !important;
}

/* Modal */
.modal-content {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-header {
    background-color: var(--primary-color);
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-header .btn-close {
    color: white;
}

.modal-body {
    padding: 2rem;
}

/* Formulários */
.form-control {
    border-radius: var(--border-radius);
    border: 1px solid rgba(0, 0, 0, 0.1);
    padding: 0.75rem 1rem;
    transition: var(--transition);
}

.form-control:focus {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

.form-label {
    font-weight: 500;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

/* Alertas */
.alert {
    border: none;
    border-radius: var(--border-radius);
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
}

.alert-success {
    background-color: var(--success-color);
    color: white;
}

.alert-danger {
    background-color: var(--danger-color);
    color: white;
}

.alert-info {
    background-color: var(--secondary-color);
    color: white;
}

/* Animações */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card, .alert, .modal-content {
    animation: fadeIn 0.5s ease-out;
}

/* Responsividade */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .table-responsive {
        margin: 0 -1.5rem;
    }
}

/* Scrollbar personalizada */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: var(--secondary-color);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #2980b9;
}

/* Estilos adicionais para os popups */
.swal2-popup {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.swal2-title {
    color: var(--dark-color);
    font-weight: 600;
}

.swal2-content {
    color: var(--dark-color);
}

.swal2-confirm {
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}

.swal2-cancel {
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}
</style> 