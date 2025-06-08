<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sessão
session_start();

// Incluir arquivo de conexão com o banco de dados
require_once 'config/database.php';

// Verificar se o usuário já está logado
if (isset($_SESSION['usuario_id'])) {
    // Redirecionar com base no nível de acesso
    if ($_SESSION['nivel_acesso'] == 'cliente') {
        header("Location: area_cliente/dashboard.php");
    } else {
        // MODIFICAÇÃO: Redirecionar para página inicial em vez do dashboard
        header("Location: inicio.php");
    }
    exit;
}

// Processar o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Log para depuração
    error_log("Tentativa de login: Email: $email");
    
    if (empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos";
    } else {
        // Buscar usuário pelo email
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            
            // Verificar se a senha está correta
            if (password_verify($senha, $usuario['senha'])) {
                // Verificar se o usuário está ativo
                if ($usuario['ativo'] == 1) {
                    // Iniciar sessão
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
                    
                    // Atualizar último acesso
                    $now = date('Y-m-d H:i:s');
                    $update_sql = "UPDATE usuarios SET ultimo_acesso = '$now' WHERE id = " . $usuario['id'];
                    $conn->query($update_sql);
                    
                    // Redirecionar com base no nível de acesso
                    if ($usuario['nivel_acesso'] == 'cliente') {
                        // Se o usuário já tem cliente_id definido, use-o
                        if (!empty($usuario['cliente_id'])) {
                            $_SESSION['cliente_id'] = $usuario['cliente_id'];
                            header("Location: area_cliente/dashboard.php");
                            exit;
                        } else {
                            // Caso contrário, tente encontrar o cliente pelo nome (ou outro campo disponível)
                            // Vamos assumir que estamos buscando por nome, ajuste conforme necessário
                            $nome_cliente = $usuario['nome'];
                            $sql_cliente = "SELECT id FROM clientes WHERE nome LIKE '%$nome_cliente%' LIMIT 1";
                            $result_cliente = $conn->query($sql_cliente);
                            
                            if ($result_cliente && $result_cliente->num_rows > 0) {
                                $cliente = $result_cliente->fetch_assoc();
                                $_SESSION['cliente_id'] = $cliente['id'];
                                
                                // Atualizar o cliente_id no usuário
                                $update_cliente_id = "UPDATE usuarios SET cliente_id = " . $cliente['id'] . " WHERE id = " . $usuario['id'];
                                $conn->query($update_cliente_id);
                                
                                header("Location: area_cliente/dashboard.php");
                                exit;
                            } else {
                                $erro = "Não foi possível encontrar um cliente associado a este usuário. Entre em contato com o administrador.";
                            }
                        }
                    } else {
                        // MODIFICAÇÃO: Redirecionar para página inicial em vez do dashboard
                        header("Location: inicio.php");
                        exit;
                    }
                } else {
                    $erro = "Sua conta está inativa. Entre em contato com o administrador.";
                }
            } else {
                $erro = "Email ou senha incorretos";
            }
        } else {
            $erro = "Email ou senha incorretos";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Domaria Café</title>
    
    <!-- Remover cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/icons/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-danger: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
            --shadow-soft: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-hover: 0 5px 20px rgba(0,0,0,0.15);
            --border-radius: 15px;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="20" cy="80" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="20" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .login-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-hover);
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            overflow: hidden;
            position: relative;
            animation: slideUp 0.8s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: var(--gradient-primary);
            color: white;
            text-align: center;
            padding: 2.5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
        }
        
        .login-header .logo {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            backdrop-filter: blur(10px);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .login-header h4 {
            margin: 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
        }
        
        .form-floating {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 1rem 1rem 1rem 3.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
            transition: color 0.3s ease;
        }
        
        .form-control:focus + .input-icon {
            color: #667eea;
        }
        
        .form-label {
            position: absolute;
            top: 1rem;
            left: 3.5rem;
            color: #6c757d;
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 5;
        }
        
        .form-control:focus + .input-icon + .form-label,
        .form-control:not(:placeholder-shown) + .input-icon + .form-label {
            top: -0.5rem;
            left: 1rem;
            font-size: 0.8rem;
            color: #667eea;
            background: white;
            padding: 0 0.5rem;
            border-radius: 4px;
        }
        
        .btn-login {
            background: var(--gradient-primary);
            border: none;
            border-radius: 10px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-soft);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            color: white;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            z-index: 5;
            padding: 0.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-toggle:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(252, 70, 107, 0.15), rgba(63, 94, 251, 0.15));
            color: #842029;
            border-left: 4px solid var(--gradient-danger);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(17, 153, 142, 0.15), rgba(56, 239, 125, 0.15));
            color: #0d5345;
            border-left: 4px solid var(--gradient-success);
        }
        
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: floatingElements 15s infinite linear;
        }
        
        .floating-element:nth-child(1) {
            width: 20px;
            height: 20px;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 15px;
            height: 15px;
            left: 70%;
            animation-delay: 5s;
        }
        
        .floating-element:nth-child(3) {
            width: 25px;
            height: 25px;
            left: 40%;
            animation-delay: 10s;
        }
        
        @keyframes floatingElements {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
        
        .version-info {
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        @media (max-width: 768px) {
            .login-container {
                max-width: 95%;
                padding: 15px;
            }
            
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-body {
                padding: 2rem 1.5rem;
            }
            
            .version-info {
                position: relative;
                bottom: auto;
                right: auto;
                margin-top: 2rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-coffee"></i>
                </div>
                <h4>Domaria Café</h4>
                <p>Sistema de Gerenciamento</p>
            </div>
            
            <div class="login-body">
                <?php if (isset($_SESSION['msg']) && isset($_SESSION['msg_type'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $_SESSION['msg_type'] == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo $_SESSION['msg']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
                <?php endif; ?>
                
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $erro; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="seu@email.com" 
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        <i class="fas fa-envelope input-icon"></i>
                        <label for="email" class="form-label">Email</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="senha" name="senha" 
                               placeholder="Sua senha" required>
                        <i class="fas fa-lock input-icon"></i>
                        <label for="senha" class="form-label">Senha</label>
                        <button type="button" class="btn-toggle" id="toggleSenha">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <button type="submit" class="btn btn-login" id="btnLogin">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        <span class="btn-text">Entrar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="version-info">
        <i class="fas fa-code-branch me-1"></i>
        Versão 1.3
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleSenha = document.getElementById('toggleSenha');
        const senhaInput = document.getElementById('senha');
        const loginForm = document.getElementById('loginForm');
        const btnLogin = document.getElementById('btnLogin');
        
        // Toggle senha
        toggleSenha.addEventListener('click', function() {
            const type = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
            senhaInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
        
        // Loading no botão de login
        loginForm.addEventListener('submit', function() {
            btnLogin.disabled = true;
            btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><span class="btn-text">Entrando...</span>';
        });
        
        // Auto-fechar alertas após 5 segundos
        const alertas = document.querySelectorAll('.alert');
        alertas.forEach(function(alerta) {
            setTimeout(function() {
                const closeButton = alerta.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            }, 5000);
        });
        
        // Focar no primeiro campo
        const emailInput = document.getElementById('email');
        emailInput.focus();
        
        // Efeitos de entrada nos campos
        const formControls = document.querySelectorAll('.form-control');
        formControls.forEach((control, index) => {
            control.style.opacity = '0';
            control.style.transform = 'translateX(-20px)';
            control.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                control.style.opacity = '1';
                control.style.transform = 'translateX(0)';
            }, 300 + (index * 100));
        });
        
        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Enter para submeter
            if (e.key === 'Enter' && !e.ctrlKey && !e.altKey) {
                if (document.activeElement.tagName !== 'BUTTON') {
                    loginForm.submit();
                }
            }
        });
    });
    </script>
</body>
</html>