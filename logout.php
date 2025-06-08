<?php
// Iniciar sessão
session_start();

// Obter informações do usuário antes de destruir a sessão
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$nivel_acesso = $_SESSION['nivel_acesso'] ?? 'usuário';

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se está sendo usado cookies de sessão, apagar o cookie também
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saindo... - Domaria Café</title>
    
    <!-- Cache Busting -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/icons/favicon.ico?v=<?php echo time(); ?>" type="image/x-icon">
    
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --gradient-danger: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
            --shadow-soft: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-hover: 0 5px 20px rgba(0,0,0,0.15);
            --border-radius: 15px;
        }
        
        body {
            background: var(--gradient-primary);
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
        
        .logout-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .logout-card {
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
        
        .logout-header {
            background: var(--gradient-danger);
            color: white;
            text-align: center;
            padding: 2.5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .logout-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
        }
        
        .logout-icon {
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
        
        .logout-body {
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .goodbye-message {
            background: linear-gradient(135deg, rgba(17, 153, 142, 0.1), rgba(56, 239, 125, 0.1));
            border-left: 4px solid var(--gradient-success);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            animation: slideIn 0.6s ease 0.5s both;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .user-info {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            animation: slideIn 0.6s ease 0.7s both;
        }
        
        .btn-back {
            background: var(--gradient-primary);
            border: none;
            border-radius: 10px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-soft);
            position: relative;
            overflow: hidden;
            animation: slideIn 0.6s ease 0.9s both;
        }
        
        .btn-back::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            color: white;
            text-decoration: none;
        }
        
        .btn-back:hover::before {
            left: 100%;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .countdown {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
            margin: 1rem 0;
            animation: slideIn 0.6s ease 1.1s both;
        }
        
        .progress-ring {
            width: 60px;
            height: 60px;
            margin: 1rem auto;
            animation: slideIn 0.6s ease 1.3s both;
        }
        
        .progress-ring circle {
            fill: none;
            stroke: #e9ecef;
            stroke-width: 4;
            r: 26;
            cx: 30;
            cy: 30;
            stroke-dasharray: 163.36;
            stroke-dashoffset: 163.36;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            animation: countdown-ring 5s linear forwards;
        }
        
        @keyframes countdown-ring {
            to {
                stroke-dashoffset: 0;
            }
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
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 25px;
            height: 25px;
            left: 40%;
            animation-delay: 4s;
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
            .logout-container {
                max-width: 95%;
                padding: 15px;
            }
            
            .logout-header {
                padding: 2rem 1.5rem;
            }
            
            .logout-body {
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
    
    <!-- Meta refresh como fallback -->
    <meta http-equiv="refresh" content="5;url=index.php">
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <div class="logout-container">
        <div class="card logout-card">
            <div class="logout-header">
                <div class="logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h4>Saindo do Sistema...</h4>
                <p>Aguarde enquanto encerramos sua sessão</p>
            </div>
            
            <div class="logout-body">
                <div class="goodbye-message">
                    <h5><i class="fas fa-heart text-danger me-2"></i>Obrigado por usar nosso sistema!</h5>
                    <p class="mb-0">Sua sessão foi encerrada com segurança.</p>
                </div>
                
                <div class="user-info">
                    <p class="mb-1"><strong>Usuário:</strong> <?php echo htmlspecialchars($usuario_nome); ?></p>
                    <p class="mb-0"><small class="text-muted">Nível: <?php echo ucfirst($nivel_acesso); ?></small></p>
                </div>
                
                <div class="progress-ring">
                    <svg>
                        <circle stroke="#667eea"></circle>
                    </svg>
                </div>
                
                <div class="countdown">
                    Redirecionando em <span id="countdown">5</span> segundos...
                </div>
                
                <a href="index.php" class="btn-back" id="backButton">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar ao Login
                </a>
            </div>
        </div>
    </div>
    
    <div class="version-info">
        <i class="fas fa-code-branch me-1"></i>
        Versão 1.3
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let timeLeft = 5;
        const countdownElement = document.getElementById('countdown');
        const backButton = document.getElementById('backButton');
        
        // Countdown timer
        const countdownTimer = setInterval(() => {
            timeLeft--;
            countdownElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdownTimer);
                
                // Mostrar loading no botão
                backButton.innerHTML = '<div class="loading-spinner"></div>Redirecionando...';
                backButton.style.pointerEvents = 'none';
                
                // Redirecionar
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 500);
            }
        }, 1000);
        
        // Limpar qualquer dado sensível do localStorage/sessionStorage
        try {
            localStorage.clear();
            sessionStorage.clear();
        } catch (e) {
            console.log('Não foi possível limpar o storage local');
        }
        
        // Limpar cache da página
        if ('caches' in window) {
            caches.keys().then(names => {
                names.forEach(name => {
                    caches.delete(name);
                });
            });
        }
        
        // Impedir voltar com botão do navegador
        window.history.pushState(null, '', window.location.href);
        window.addEventListener('popstate', function() {
            window.history.pushState(null, '', window.location.href);
        });
        
        // Efeito de saída quando clicar no botão
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            clearInterval(countdownTimer);
            
            this.innerHTML = '<div class="loading-spinner"></div>Redirecionando...';
            this.style.pointerEvents = 'none';
            
            // Animação de saída
            document.querySelector('.logout-card').style.transform = 'translateY(-50px)';
            document.querySelector('.logout-card').style.opacity = '0';
            
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 500);
        });
        
        // Efeitos visuais adicionais
        setTimeout(() => {
            document.querySelector('.logout-icon').style.transform = 'rotate(360deg)';
        }, 1000);
        
        // Log de segurança (opcional)
        console.log('%cSessão encerrada com segurança', 'color: #28a745; font-weight: bold; font-size: 14px;');
        console.log('%cSe você não fez logout, entre em contato com o administrador.', 'color: #dc3545; font-size: 12px;');
    });
    </script>
</body>
</html>