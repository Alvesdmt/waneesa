<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuração de logs
$logFile = __DIR__ . '/logs/catalogo_errors.log';

// Função para registrar erros
function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    require_once 'config/database.php';
    
    // Usando as variáveis do arquivo database.php
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Erro na conexão: " . $conn->connect_error);
    }
    
    // Processamento da pesquisa
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $whereClause = $search ? "WHERE (nome LIKE '%$search%' OR descricao LIKE '%$search%' OR codigo_barras LIKE '%$search%') AND status = 'ativo'" : "WHERE status = 'ativo'";
    
    // Consulta dos produtos
    $sql = "SELECT id, nome, descricao, codigo_barras, preco_venda, estoque, foto, status 
            FROM produtos 
            $whereClause 
            ORDER BY nome";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Erro na consulta: " . $conn->error);
    }
} catch (Exception $e) {
    logError($e->getMessage());
    $error = "Ocorreu um erro ao carregar os produtos. Por favor, tente novamente mais tarde.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a1a2e;
            --secondary-color: #16213e;
            --accent-color: #0f3460;
            --success-color: #25d366;
            --text-color: #333333;
            --light-bg: #f5f5f5;
            --card-shadow: 0 10px 20px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text-color);
            min-height: 100vh;
            padding-bottom: 2rem;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 1.2rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 2rem;
            color: white !important;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 1px;
        }
        
        .search-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 0 1.5rem;
            position: relative;
        }
        
        .search-input {
            border-radius: 50px;
            padding: 1.2rem 2rem;
            border: 2px solid rgba(255,255,255,0.2);
            font-size: 1.1rem;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            background: rgba(255,255,255,0.9);
            width: 100%;
            backdrop-filter: blur(5px);
        }
        
        .search-input:focus {
            box-shadow: 0 8px 25px rgba(15, 52, 96, 0.2);
            border-color: var(--accent-color);
            outline: none;
            background: white;
        }
        
        .search-input::placeholder {
            color: #666;
            opacity: 0.8;
        }
        
        .product-card {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 30px;
            transition: var(--transition);
            box-shadow: var(--card-shadow);
            border: none;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            backdrop-filter: blur(5px);
        }
        
        .product-image-container {
            position: relative;
            width: 100%;
            padding-top: 75%; /* Proporção 4:3 */
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .product-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: var(--transition);
            filter: brightness(0.95);
            padding: 1rem;
            background: white;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
            filter: brightness(1);
        }
        
        .product-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.2rem;
            margin: 1.2rem 0;
            padding: 0 1.5rem;
            line-height: 1.4;
        }
        
        .product-price {
            color: var(--success-color);
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0.8rem 0;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .product-price::before {
            content: 'R$';
            font-size: 1rem;
            margin-right: 5px;
            opacity: 0.8;
        }
        
        .btn-whatsapp {
            background: var(--success-color);
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
            width: calc(100% - 3rem);
            margin: 1.5rem;
            margin-top: auto;
            text-transform: uppercase;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-whatsapp:hover {
            background: #128C7E;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        }
        
        .btn-whatsapp i {
            font-size: 1.2rem;
        }
        
        .no-image-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: linear-gradient(45deg, #f3f4f6, #ffffff);
            padding: 1rem;
        }
        
        .no-image-placeholder i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }
        
        .no-image-placeholder::after {
            content: 'Imagem não disponível';
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .no-image-placeholder::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.2) 50%, transparent 100%);
            animation: shine 2s infinite linear;
        }
        
        @keyframes shine {
            0% {
                transform: translateX(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) rotate(45deg);
            }
        }
        
        .error-message {
            background: rgba(255, 243, 243, 0.95);
            border-left: 4px solid var(--accent-color);
            padding: 1.5rem;
            border-radius: 15px;
            margin: 2rem 0;
            backdrop-filter: blur(5px);
        }
        
        .loading {
            position: relative;
            overflow: hidden;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        @media (max-width: 768px) {
            .search-container {
                margin: 2rem auto;
            }
            
            .product-card {
                margin-bottom: 20px;
            }
            
            .product-image-container {
                padding-top: 100%; /* Proporção 1:1 para mobile */
            }
            
            .product-image {
                padding: 0.8rem;
            }
            
            .product-title {
                font-size: 1.1rem;
                padding: 0 1rem;
            }
            
            .product-price {
                font-size: 1.4rem;
                padding: 0 1rem;
            }
            
            .btn-whatsapp {
                padding: 0.8rem 1.5rem;
                font-size: 0.8rem;
                margin: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            
            .search-input {
                padding: 1rem 1.5rem;
                font-size: 1rem;
            }
            
            .product-image-container {
                padding-top: 100%;
            }
            
            .product-image {
                padding: 0.5rem;
            }
            
            .product-title {
                font-size: 1rem;
            }
            
            .product-price {
                font-size: 1.2rem;
            }
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        .product-card {
            animation: fadeIn 0.6s ease forwards;
            opacity: 0;
        }
        
        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        .product-card:nth-child(5) { animation-delay: 0.5s; }
        .product-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-store"></i> Armarinho Central
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="search-container">
            <form action="" method="GET" class="d-flex">
                <input type="text" 
                       name="search" 
                       class="form-control search-input" 
                       placeholder="Pesquisar produtos..."
                       value="<?php echo htmlspecialchars($search); ?>">
               
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <?php while ($produto = $result->fetch_assoc()): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card product-card">
                            <?php if (!empty($produto['foto'])): ?>
                                <div class="product-image-container">
                                    <img src="admin/uploads/<?php echo htmlspecialchars($produto['foto']); ?>" 
                                         class="card-img-top product-image" 
                                         alt="<?php echo htmlspecialchars($produto['nome'] ?? ''); ?>">
                                </div>
                            <?php else: ?>
                                <div class="product-image-container">
                                    <div class="no-image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title product-title">
                                    <?php echo htmlspecialchars($produto['nome'] ?? 'Produto sem nome'); ?>
                                </h5>
                                <p class="product-price">
                                    R$ <?php echo number_format(floatval($produto['preco_venda'] ?? 0), 2, ',', '.'); ?>
                                </p>
                                <?php
                                $whatsapp_message = urlencode("Olá! Gostaria de comprar o produto: " . ($produto['nome'] ?? ''));
                                $whatsapp_url = "https://wa.me/?text=$whatsapp_message";
                                ?>
                                <a href="<?php echo $whatsapp_url; ?>" 
                                   class="btn btn-whatsapp" 
                                   target="_blank">
                                    <i class="fab fa-whatsapp"></i> Comprar via WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            const productContainer = document.querySelector('.row');
            let debounceTimer;
            
            // Função para buscar produtos
            async function searchProducts(query) {
                try {
                    const response = await fetch(`index.php?search=${encodeURIComponent(query)}`);
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newProducts = doc.querySelector('.row').innerHTML;
                    productContainer.innerHTML = newProducts;
                    
                    // Reaplica as animações nos novos cards
                    animateCards();
                } catch (error) {
                    console.error('Erro na busca:', error);
                }
            }
            
            // Função para animar os cards
            function animateCards() {
                const cards = document.querySelectorAll('.product-card');
                cards.forEach(card => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.style.opacity = '1';
                                entry.target.style.transform = 'translateY(0)';
                            }
                        });
                    }, { threshold: 0.1 });
                    
                    observer.observe(card);
                });
            }
            
            // Evento de input com debounce
            searchInput.addEventListener('input', function(e) {
                clearTimeout(debounceTimer);
                const query = e.target.value.trim();
                
                // Adiciona classe de loading
                productContainer.classList.add('loading');
                
                debounceTimer = setTimeout(() => {
                    if (query.length > 0) {
                        searchProducts(query);
                    } else {
                        // Se o campo estiver vazio, recarrega a página
                        window.location.href = 'index.php';
                    }
                    productContainer.classList.remove('loading');
                }, 300); // Debounce de 300ms
            });
            
            // Anima os cards iniciais
            animateCards();
        });
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>
