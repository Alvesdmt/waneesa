<?php
// Inclui a verificação de permissão para funcionários
include 'verificar_permissao_funcionario.php';

session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Inclui a configuração do banco de dados
include '../config/database.php';

// Define a imagem padrão para produtos sem foto
define('PRODUTO_SEM_FOTO', '../includes/img/produto.jpg');

// Verifica se o diretório de uploads existe
$diretorio_uploads = 'uploads/';
if (!is_dir($diretorio_uploads)) {
    if (!mkdir($diretorio_uploads, 0777, true)) {
        throw new Exception('Não foi possível criar o diretório de uploads. Verifique as permissões da pasta.');
    }
    // Garante que as permissões estão corretas
    chmod($diretorio_uploads, 0777);
}

// Verifica se o diretório tem permissão de escrita
if (!is_writable($diretorio_uploads)) {
    // Tenta ajustar as permissões
    if (!chmod($diretorio_uploads, 0777)) {
        throw new Exception('O diretório de uploads não tem permissão de escrita e não foi possível ajustar as permissões.');
    }
}

// Verifica se o diretório da imagem padrão existe
$diretorio_padrao = 'uploads/';
if (!is_dir($diretorio_padrao)) {
    mkdir($diretorio_padrao, 0777, true);
}

// Se a imagem padrão não existir, vamos criar uma
if (!file_exists(PRODUTO_SEM_FOTO)) {
    // Cria uma imagem padrão simples
    $imagem = imagecreatetruecolor(200, 200);
    $cor_fundo = imagecolorallocate($imagem, 240, 240, 240); // Cinza claro
    $cor_texto = imagecolorallocate($imagem, 100, 100, 100); // Cinza escuro
    
    // Preenche o fundo
    imagefill($imagem, 0, 0, $cor_fundo);
    
    // Adiciona texto
    imagestring($imagem, 5, 40, 90, "Sem Imagem", $cor_texto);
    
    // Salva a imagem
    imagejpeg($imagem, PRODUTO_SEM_FOTO);
    imagedestroy($imagem);
}

// Função para gerar código de barras automático
function gerarCodigoBarras() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'] ?? null;
        
        // Formata os valores monetários
        $preco_custo = str_replace(',', '.', $_POST['preco_custo']);
        $preco_venda = str_replace(',', '.', $_POST['preco_venda']);
        
        // Garante que os valores tenham 2 casas decimais
        $preco_custo = number_format((float)$preco_custo, 2, '.', '');
        $preco_venda = number_format((float)$preco_venda, 2, '.', '');
        
        $dados = [
            'nome' => $_POST['nome'],
            'descricao' => $_POST['descricao'],
            'codigo_barras' => !empty($_POST['codigo_barras']) ? $_POST['codigo_barras'] : gerarCodigoBarras(),
            'preco_custo' => $preco_custo,
            'preco_venda' => $preco_venda,
            'estoque' => $_POST['estoque'],
            'status' => $_POST['status']
        ];

        // Processar upload da foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $foto = $_FILES['foto'];
            $ext = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
            
            // Validar extensão
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            // Verifica o tipo MIME do arquivo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $foto['tmp_name']);
            finfo_close($finfo);
            
            $mime_types_permitidos = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ];
            
            if (in_array($ext, $extensoes_permitidas) && in_array($mime_type, $mime_types_permitidos)) {
                $novo_nome = uniqid() . '.' . $ext;
                $diretorio = 'uploads/';
                
                // Verifica se o diretório existe e tem permissão de escrita
                if (!is_dir($diretorio)) {
                    if (!mkdir($diretorio, 0777, true)) {
                        throw new Exception('Não foi possível criar o diretório de uploads');
                    }
                }
                
                if (!is_writable($diretorio)) {
                    throw new Exception('O diretório de uploads não tem permissão de escrita');
                }
                
                // Se for uma atualização, remove a foto antiga
                if ($id) {
                    $stmt = $pdo->prepare("SELECT foto FROM produtos WHERE id = ?");
                    $stmt->execute([$id]);
                    $foto_antiga = $stmt->fetchColumn();
                    
                    if ($foto_antiga && file_exists($diretorio . $foto_antiga)) {
                        unlink($diretorio . $foto_antiga);
                    }
                }
                
                // Upload da nova foto
                $caminho_completo = $diretorio . $novo_nome;
                if (move_uploaded_file($foto['tmp_name'], $caminho_completo)) {
                    $dados['foto'] = $novo_nome;
                } else {
                    throw new Exception('Erro ao mover o arquivo para o diretório de uploads');
                }
            } else {
                throw new Exception('Formato de imagem não permitido. Use: ' . implode(', ', $extensoes_permitidas) . '. Tipo MIME detectado: ' . $mime_type);
            }
        }

        if ($id) {
            // Atualização
            $campos = [];
            foreach ($dados as $campo => $valor) {
                $campos[] = "$campo = :$campo";
            }
            $sql = "UPDATE produtos SET " . implode(', ', $campos) . " WHERE id = :id";
            $dados['id'] = $id;
        } else {
            // Inserção
            $campos = implode(', ', array_keys($dados));
            $valores = ':' . implode(', :', array_keys($dados));
            $sql = "INSERT INTO produtos ($campos) VALUES ($valores)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($dados);

        $_SESSION['mensagem'] = 'Produto salvo com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = 'Erro ao salvar produto: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
    } catch (Exception $e) {
        $_SESSION['mensagem'] = 'Erro: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
    }
    
    // Log de erros
    if (isset($_SESSION['tipo_mensagem']) && $_SESSION['tipo_mensagem'] === 'danger') {
        error_log('Erro no cadastro de produto: ' . $_SESSION['mensagem']);
    }
    
    header('Location: produtos.php');
    exit();
}

// Processamento da exclusão
if (isset($_GET['excluir'])) {
    try {
        $id = $_GET['excluir'];
        $sql = "UPDATE produtos SET status = 'inativo' WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $_SESSION['mensagem'] = 'Produto excluído com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = 'Erro ao excluir produto: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
    }
    
    header('Location: produtos.php');
    exit();
}

// Buscar todos os produtos
$sql = "SELECT * FROM produtos WHERE status = 'ativo' ORDER BY id DESC";
$stmt = $pdo->query($sql);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inclui o layout
include '../includes/layout.php';
?>

<!-- Mensagens de feedback -->
<?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?= $_SESSION['tipo_mensagem'] ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['mensagem'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php 
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo_mensagem']);
    ?>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Lista de Produtos</h5>
        <button type="button" class="btn btn-primary" onclick="abrirModalNovoProduto()">
            <i class="fas fa-plus"></i> Novo Produto
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="tabelaProdutos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>Código de Barras</th>
                        <th>Preço Custo</th>
                        <th>Preço Venda</th>
                        <th>Estoque</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                    <tr>
                        <td><?= $produto['id'] ?></td>
                        <td>
                            <?php
                            $foto_url = PRODUTO_SEM_FOTO;
                            if (!empty($produto['foto']) && file_exists("uploads/{$produto['foto']}")) {
                                $foto_url = "uploads/{$produto['foto']}";
                                echo '<img src="' . $foto_url . '" width="50" height="50" class="img-thumbnail">';
                            } else {
                                echo '<div class="icon-container"><i class="fas fa-box fa-2x"></i></div>';
                            }
                            ?>
                        </td>
                        <td><?= $produto['nome'] ?></td>
                        <td><?= $produto['codigo_barras'] ?></td>
                        <td>R$ <?= number_format($produto['preco_custo'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($produto['preco_venda'], 2, ',', '.') ?></td>
                        <td><?= $produto['estoque'] ?></td>
                        <td>
                            <span class="badge <?= $produto['status'] == 'ativo' ? 'bg-success' : 'bg-danger' ?>">
                                <?= $produto['status'] ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="editarProduto(<?= htmlspecialchars(json_encode($produto)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?excluir=<?= $produto['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para adicionar/editar produto -->
<div class="modal fade" id="modalProduto" tabindex="-1" aria-labelledby="modalProdutoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProdutoLabel">Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="formProduto" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do Produto</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="codigo_barras" class="form-label">Código de Barras</label>
                                <input type="text" class="form-control" id="codigo_barras" name="codigo_barras">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="preco_custo" class="form-label">Preço de Custo</label>
                                <input type="text" class="form-control" id="preco_custo" name="preco_custo" pattern="^\d*\.?\d{0,2}$" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="preco_venda" class="form-label">Preço de Venda</label>
                                <input type="text" class="form-control" id="preco_venda" name="preco_venda" pattern="^\d*\.?\d{0,2}$" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="estoque" class="form-label">Estoque</label>
                                <input type="number" class="form-control" id="estoque" name="estoque" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="foto" class="form-label">Foto do Produto</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/jpeg,image/png,image/gif,image/webp">
                                <small class="text-muted">Formatos aceitos: JPG, JPEG, PNG, GIF e WEBP</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Verificação do Bootstrap
console.log('Bootstrap está carregado:', typeof bootstrap !== 'undefined');
console.log('jQuery está carregado:', typeof jQuery !== 'undefined');

// Variável global para o modal
let produtoModal;

$(document).ready(function() {
    // Inicializa DataTable
    $('#tabelaProdutos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        }
    });

    // Formatação dos campos de preço
    $('#preco_custo, #preco_venda').on('input', function() {
        // Remove tudo que não for número ou ponto
        let valor = $(this).val().replace(/[^\d.]/g, '');
        
        // Garante que só tenha um ponto decimal
        let partes = valor.split('.');
        if (partes.length > 2) {
            valor = partes[0] + '.' + partes.slice(1).join('');
        }
        
        // Formata para ter sempre 2 casas decimais
        if (valor.includes('.')) {
            let partes = valor.split('.');
            if (partes[1].length > 2) {
                partes[1] = partes[1].substring(0, 2);
            }
            valor = partes[0] + '.' + partes[1];
        }
        
        $(this).val(valor);
    });
});

function abrirModalNovoProduto() {
    // Limpa o formulário e título
    document.getElementById('formProduto').reset();
    document.getElementById('id').value = '';
    document.getElementById('modalProdutoLabel').textContent = 'Novo Produto';
    
    // Abre o modal usando Bootstrap 5
    const modal = new bootstrap.Modal(document.getElementById('modalProduto'));
    modal.show();
}

function editarProduto(produto) {
    // Atualiza o título do modal
    document.getElementById('modalProdutoLabel').textContent = 'Editar Produto';
    
    // Preenche todos os campos do formulário
    document.getElementById('id').value = produto.id || '';
    document.getElementById('nome').value = produto.nome || '';
    document.getElementById('codigo_barras').value = produto.codigo_barras || '';
    document.getElementById('descricao').value = produto.descricao || '';
    document.getElementById('preco_custo').value = produto.preco_custo || '';
    document.getElementById('preco_venda').value = produto.preco_venda || '';
    document.getElementById('estoque').value = produto.estoque || '';
    document.getElementById('status').value = produto.status || 'ativo';
    
    // Abre o modal usando Bootstrap 5
    const modal = new bootstrap.Modal(document.getElementById('modalProduto'));
    modal.show();
}

// Função para debug
function verificarBootstrap() {
    if (typeof bootstrap !== 'undefined') {
        console.log('Bootstrap JS está carregado');
    } else {
        console.log('Bootstrap JS NÃO está carregado');
    }
}

// Executar verificação quando a página carregar
document.addEventListener('DOMContentLoaded', verificarBootstrap);
</script>

<style>
.table th {
    background-color: var(--light-color);
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.img-thumbnail {
    object-fit: cover;
    width: 50px;
    height: 50px;
    border-radius: 4px;
}

.icon-container {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.icon-container i {
    color: #6c757d;
}

/* Estilo para a prévia da imagem no modal */
#previewFoto {
    max-width: 100px;
    max-height: 100px;
    margin-top: 10px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> 