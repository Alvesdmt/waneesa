<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo'];

    try {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$nome, $email, $senha, $tipo])) {
            $_SESSION['mensagem'] = 'Usuário cadastrado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: login');
            exit();
        }
    } catch(PDOException $e) {
        $erro = "Erro ao cadastrar usuário. Email já existe.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Waneesa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .cadastro-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #343a40;
            color: white;
            text-align: center;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .btn-primary {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            width: 100%;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
        }
        .link-login {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="cadastro-container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Criar Conta</h3>
            </div>
            <div class="card-body">
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="senha" name="senha" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleSenha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de Usuário</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="">Selecione...</option>
                            <option value="admin">Administrador</option>
                            <option value="funcionario">Funcionário</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Cadastrar
                    </button>
                </form>
                
                <div class="link-login">
                    <p class="mb-0">Já tem uma conta? <a href="login">Faça login</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#toggleSenha').click(function() {
            var senhaInput = $('#senha');
            var icon = $(this).find('i');
            
            if (senhaInput.attr('type') === 'password') {
                senhaInput.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                senhaInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
    </script>
</body>
</html> 