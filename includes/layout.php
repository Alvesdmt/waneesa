<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: login');
    exit();
}

// Inclui a configuração do banco de dados
include '../config/database.php';

// Verifica o tipo de usuário
$tipo_usuario = 'funcionario'; // padrão
if (isset($_SESSION['usuario_id'])) {
    $sql = "SELECT tipo FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usuario) {
        $tipo_usuario = $usuario['tipo'];
    }
}

// Páginas permitidas para funcionários
$paginas_funcionario = [
    'produtos.php',
    'vendas.php',
    'carnes.php',
    'caixa.php',
    'dashboard.php'
];

// Função para verificar se uma página é permitida
function paginaPermitida($pagina, $tipo_usuario, $paginas_funcionario) {
    if ($tipo_usuario === 'admin') {
        return true;
    }
    return in_array($pagina, $paginas_funcionario);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waneesa - Sistema de Gestão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        /* Variáveis */
:root {
    --primary-color: #4e73df;
    --secondary-color: #858796;
    --success-color: #1cc88a;
    --info-color: #36b9cc;
    --warning-color: #f6c23e;
    --danger-color: #e74a3b;
    --light-color: #f8f9fc;
    --dark-color: #5a5c69;
    --sidebar-bg: #1a1a1a;
    --sidebar-hover: #2a2a2a;
    --sidebar-text: #ffffff;
    --sidebar-icon: #4e73df;
    --navbar-bg: #ffffff;
    --navbar-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    --navbar-text: #2d3748;
    --navbar-hover: #f7fafc;
    --navbar-border: #e2e8f0;
    --content-bg: #f8f9fc;
    --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    --transition-speed: 0.3s;
}

/* Layout Base */
body {
    font-family: 'Nunito', sans-serif;
    background-color: var(--content-bg);
    color: var(--navbar-text);
    line-height: 1.6;
}

.wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

/* Sidebar Moderna */
#sidebar {
    min-width: 250px;
    max-width: 250px;
    min-height: 100vh;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--navbar-shadow);
    position: fixed;
    z-index: 1000;
}

#sidebar .sidebar-header {
    padding: 1.5rem;
    background: transparent;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

#sidebar .sidebar-header h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--sidebar-text);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 1px;
}

#sidebar ul.components {
    padding: 1.5rem 0;
}

#sidebar ul li {
    margin: 0.5rem 1rem;
}

#sidebar ul li a {
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    color: var(--sidebar-text);
    text-decoration: none;
    transition: all var(--transition-speed) ease;
    border-radius: 0.5rem;
}

#sidebar ul li a i {
    margin-right: 1rem;
    color: var(--sidebar-icon);
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

#sidebar ul li a:hover {
    background: var(--sidebar-hover);
    transform: translateX(5px);
}

#sidebar ul li.active > a {
    background: var(--sidebar-hover);
    border-left: 3px solid var(--sidebar-icon);
}

/* Navbar Moderna */
.navbar {
    padding: 0.75rem 1.5rem;
    background: var(--navbar-bg);
    border: none;
    box-shadow: var(--navbar-shadow);
    transition: all var(--transition-speed) ease;
    position: sticky;
    top: 0;
    z-index: 1030;
}

.navbar .container-fluid {
    padding: 0;
}

.navbar .navbar-toggler {
    border: none;
    padding: 0.5rem;
    margin-right: 1rem;
    transition: all var(--transition-speed) ease;
}

.navbar .navbar-toggler:focus {
    box-shadow: none;
    outline: none;
}

.navbar .navbar-toggler i {
    color: var(--navbar-text);
    font-size: 1.25rem;
}

.navbar .navbar-nav {
    margin-left: auto;
}

.navbar .nav-item {
    margin: 0 0.5rem;
}

.navbar .nav-link {
    color: var(--navbar-text);
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: all var(--transition-speed) ease;
    display: flex;
    align-items: center;
}

.navbar .nav-link i {
    margin-right: 0.5rem;
    font-size: 1.1rem;
}

.navbar .nav-link:hover {
    background: var(--navbar-hover);
    color: var(--primary-color);
}

.navbar .dropdown-menu {
    border: none;
    box-shadow: var(--card-shadow);
    border-radius: 0.75rem;
    padding: 0.5rem;
    margin-top: 0.5rem;
    min-width: 200px;
    border: 1px solid var(--navbar-border);
}

.navbar .dropdown-item {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    color: var(--navbar-text);
    font-weight: 500;
    transition: all var(--transition-speed) ease;
}

.navbar .dropdown-item:hover {
    background: var(--navbar-hover);
    color: var(--primary-color);
}

.navbar .dropdown-item i {
    margin-right: 0.75rem;
    width: 20px;
    text-align: center;
}

.navbar .dropdown-divider {
    margin: 0.5rem 0;
    border-color: var(--navbar-border);
}

/* Conteúdo Principal */
#content {
    width: calc(100% - 250px);
    min-height: 100vh;
    margin-left: 250px;
    transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
    background: var(--content-bg);
    padding: 2rem;
}

/* Cards */
.card {
    border: none;
    border-radius: 0.75rem;
    box-shadow: var(--card-shadow);
    transition: all var(--transition-speed) ease;
    background: var(--navbar-bg);
    margin-bottom: 1.5rem;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.card-header {
    background-color: transparent;
    border-bottom: 1px solid var(--navbar-border);
    padding: 1.25rem;
}

.card-body {
    padding: 1.25rem;
}

/* Alertas */
.alert {
    border: none;
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--card-shadow);
}

.alert-success {
    background-color: var(--success-color);
    color: white;
}

.alert-danger {
    background-color: var(--danger-color);
    color: white;
}

.alert-warning {
    background-color: var(--warning-color);
    color: white;
}

.alert-info {
    background-color: var(--info-color);
    color: white;
}

/* Responsividade */
@media (max-width: 768px) {
    #sidebar {
        margin-left: -250px;
    }
    #sidebar.active {
        margin-left: 0;
    }
    #content {
        width: 100%;
        margin-left: 0;
    }
    #content.active {
        margin-left: 250px;
    }
    .navbar {
        padding: 0.5rem 1rem;
    }
}

/* Animações */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.5s ease-out;
}

/* Efeito de scroll na navbar */
.navbar.scrolled {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: var(--navbar-shadow);
}
/* Sidebar Moderna */
#sidebar {
    min-width: 250px;
    max-width: 250px;
    min-height: 100vh;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--navbar-shadow);
    position: fixed;
    z-index: 1030;
    left: 0;
    transform: translateX(0);
}

#sidebar.active {
    transform: translateX(-250px);
}

/* Conteúdo Principal */
#content {
    width: calc(100% - 250px);
    min-height: 100vh;
    margin-left: 250px;
    transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
    background: var(--content-bg);
    padding: 2rem;
    position: relative;
}

#content.active {
    width: 100%;
    margin-left: 0;
}

/* Responsividade */
@media (max-width: 768px) {
    #sidebar {
        transform: translateX(-250px);
    }
    
    #sidebar.active {
        transform: translateX(0);
    }
    
    #content {
        width: 100%;
        margin-left: 0;
        padding: 1rem;
    }
    
    #content.active {
        margin-left: 250px;
        width: calc(100% - 250px);
    }
    
    .navbar {
        padding: 0.5rem 1rem;
    }
    
    /* Overlay para quando a sidebar estiver aberta */
    #content.active::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1020;
        pointer-events: none;
        opacity: 0;
        transition: opacity var(--transition-speed) ease;
    }
    
    #content.active::before {
        opacity: 1;
        pointer-events: auto;
    }
}

// ... existing code ...
        </style>

</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-dark">
            <div class="sidebar-header">
                <h3>Estoque</h3>
            </div>
            <ul class="list-unstyled components">

            <?php if ($tipo_usuario === 'admin'): ?>
                    <li><a href="index"><i class="fas fa-home"></i> Dashboard</a></li>
                <?php endif; ?>

               
                
                <?php if (paginaPermitida('caixa.php', $tipo_usuario, $paginas_funcionario)): ?>
                    <li><a href="caixa"><i class="fas fa-cash-register"></i> Caixa</a></li>
                <?php endif; ?>
                
                <?php if ($tipo_usuario === 'admin'): ?>
                    <li><a href="funcionarios"><i class="fas fa-users"></i> Funcionários</a></li>
                <?php endif; ?>
                
                <?php if (paginaPermitida('produtos.php', $tipo_usuario, $paginas_funcionario)): ?>
                    <li><a href="produtos"><i class="fas fa-box"></i> Produtos</a></li>
                <?php endif; ?>
                
                <?php if (paginaPermitida('vendas.php', $tipo_usuario, $paginas_funcionario)): ?>
                    <li><a href="vendas"><i class="fas fa-shopping-cart"></i> Vendas</a></li>
                <?php endif; ?>
                
                <?php if ($tipo_usuario === 'admin'): ?>
                    <li><a href="compras"><i class="fas fa-shopping-bag"></i> Compras</a></li>
                <?php endif; ?>
                
                <?php if (paginaPermitida('carnes.php', $tipo_usuario, $paginas_funcionario)): ?>
                    <li><a href="carnes"><i class="fas fa-file-invoice"></i> Carnês</a></li>
                <?php endif; ?>
                
                <li><a href="../catalogo" target="_blank"><i class="fas fa-book"></i> Catálogo</a></li>
                <li><a href="logout" target="_blank"><i class="fas fa-sign-out-alt text-danger"></i> Sair</a></li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-dark">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user"></i> <?php echo $_SESSION['usuario_nome'] ?? ''; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="perfil">Perfil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout">Sair</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid">
                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?> alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['mensagem'];
                        unset($_SESSION['mensagem']);
                        unset($_SESSION['tipo_mensagem']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?> 

                
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Controle da Sidebar e Navbar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const navbar = document.querySelector('.navbar');
            let isSidebarOpen = false;
            let lastScroll = 0;

            // Função para alternar a sidebar
            function toggleSidebar() {
                isSidebarOpen = !isSidebarOpen;
                sidebar.classList.toggle('active');
                content.classList.toggle('active');
                
                // Ajusta o conteúdo quando a sidebar está aberta
                if (isSidebarOpen) {
                    content.style.marginLeft = '250px';
                    content.style.width = 'calc(100% - 250px)';
                } else {
                    content.style.marginLeft = '0';
                    content.style.width = '100%';
                }
            }

            // Evento de clique no botão
            sidebarCollapse.addEventListener('click', function(e) {
                e.preventDefault();
                toggleSidebar();
            });

            // Fechar sidebar ao clicar fora em telas menores
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(event.target) && 
                        !sidebarCollapse.contains(event.target) && 
                        isSidebarOpen) {
                        toggleSidebar();
                    }
                }
            });

            // Ajustar layout ao redimensionar a janela
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    content.classList.remove('active');
                    content.style.marginLeft = '250px';
                    content.style.width = 'calc(100% - 250px)';
                    isSidebarOpen = false;
                }
            });

            // Efeito de scroll na navbar
            window.addEventListener('scroll', function() {
                const currentScroll = window.pageYOffset;
                
                if (currentScroll > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
                
                lastScroll = currentScroll;
            });

            // Animar dropdowns
            const dropdowns = document.querySelectorAll('.dropdown');
            
            dropdowns.forEach(dropdown => {
                dropdown.addEventListener('show.bs.dropdown', function() {
                    this.querySelector('.dropdown-menu').classList.add('show');
                });
                
                dropdown.addEventListener('hide.bs.dropdown', function() {
                    this.querySelector('.dropdown-menu').classList.remove('show');
                });
            });

            // Marcar item ativo na sidebar
            const currentPath = window.location.pathname;
            const sidebarLinks = document.querySelectorAll('#sidebar ul li a');
            
            sidebarLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath.split('/').pop()) {
                    link.parentElement.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
                