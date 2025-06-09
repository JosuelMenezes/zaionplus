<?php
/**
 * header.php - Cabeçalho Global do Sistema
 * Versão aprimorada utilizando caminhos absolutos (BASE_URL) para navegação robusta.
 */

// Inicia a sessão se ainda não houver uma ativa.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se a BASE_URL não foi definida em um arquivo de configuração central, 
// o código tenta detectá-la. É ALTAMENTE RECOMENDADO definir esta constante manualmente.
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    // Remove subdiretórios do módulo (ex: /contas/, /clientes/) para chegar na raiz
    $base_path = preg_replace('/\/[a-zA-Z0-9_-]+\/?$/', '/', $script_path);
    define('BASE_URL', rtrim($protocol . $host . $base_path, '/'));
}

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['usuario_id']) && basename($_SERVER['PHP_SELF']) != 'index.php') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Determina qual página está ativa para destacar o menu correto.
$current_uri = $_SERVER['REQUEST_URI'];

$is_inicio = (strpos($current_uri, 'inicio.php') !== false);
$is_dashboard = (strpos($current_uri, 'dashboard.php') !== false);
$is_clientes = (strpos($current_uri, '/clientes/') !== false);
$is_produtos = (strpos($current_uri, '/produtos/') !== false);
$is_vendas = (strpos($current_uri, '/vendas/') !== false);
$is_fornecedores = (strpos($current_uri, '/fornecedores/') !== false);
$is_lista_compras = (strpos($current_uri, '/lista_compras/') !== false);
$is_contas = (strpos($current_uri, '/contas/') !== false);
$is_funcionarios = (strpos($current_uri, '/funcionarios/') !== false);
$is_ponto = (strpos($current_uri, '/ponto/') !== false);
$is_usuarios = (strpos($current_uri, '/usuarios/') !== false);
$is_config = (strpos($current_uri, '/config/') !== false);
$is_perfil = (strpos($current_uri, 'perfil.php') !== false);

// Detecção aprimorada para a sub-seção 'Extras' de funcionários
$is_extras = ($is_funcionarios && strpos($current_uri, '/extras') !== false);

// Carrega configurações da sessão ou usa valores padrão.
$empresa_nome = $_SESSION['config']['nome_empresa'] ?? 'Domaria Cafe';
$logo_session = $_SESSION['config']['logo_url'] ?? '';
$logo_url = !empty($logo_session) ? $logo_session : 'assets/images/logo.png';
$versao_sistema = $_SESSION['config']['versao_sistema'] ?? '1.4';
$cor_primaria = $_SESSION['config']['cor_primaria'] ?? '#343a40';
$cor_secundaria = $_SESSION['config']['cor_secundaria'] ?? '#6c757d';
$cor_botoes = $_SESSION['config']['cor_botoes'] ?? '#0d6efd';
$cor_texto = $_SESSION['config']['cor_texto'] ?? '#212529';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($empresa_nome); ?> - Sistema de Gerenciamento</title>

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/icons/favicon2.png" sizes="any">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="<?php echo $cor_primaria; ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --cor-primaria: <?php echo $cor_primaria; ?>;
            --cor-secundaria: <?php echo $cor_secundaria; ?>;
            --cor-botoes: <?php echo $cor_botoes; ?>;
            --cor-texto: <?php echo $cor_texto; ?>;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-danger: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
            --gradient-fornecedores: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);
            --gradient-funcionarios: linear-gradient(135deg, #7B68EE 0%, #9370DB 100%);
            --gradient-ponto: linear-gradient(135deg, #FF6B6B 0%, #FFD93D 100%);
            --gradient-extras: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-inicio: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --shadow-soft: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-hover: 0 5px 20px rgba(0,0,0,0.15);
            --border-radius: 15px;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-hover);
            transition: all 0.3s ease;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
            pointer-events: none;
        }
        
        .nav-link {
            color: rgba(255,255,255,.9) !important;
            transition: all 0.3s ease;
            padding: 10px 18px;
            border-radius: 8px;
            margin: 2px 12px;
            display: flex;
            align-items: center;
            position: relative;
            backdrop-filter: blur(10px);
            font-size: 0.9rem;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--gradient-success);
            border-radius: 2px;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .nav-link:hover {
            color: white !important;
            background: rgba(255,255,255,.15);
            transform: translateX(3px);
            box-shadow: 0 3px 12px rgba(0,0,0,0.2);
        }
        
        .nav-link:hover::before {
            transform: scaleY(1);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,.2);
            color: white !important;
            font-weight: 600;
            box-shadow: 0 3px 12px rgba(0,0,0,0.3);
            transform: translateX(3px);
        }
        
        .nav-link.active::before {
            transform: scaleY(1);
        }

        .nav-link.nav-fornecedores::before {
            background: var(--gradient-fornecedores);
        }
        
        .nav-link.nav-fornecedores:hover {
            background: linear-gradient(135deg, rgba(253, 126, 20, 0.2), rgba(232, 62, 140, 0.2));
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
            font-size: 1.1em;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));
        }

        .user-info { padding: 18px; border-top: 1px solid rgba(255,255,255,.2); margin-top: auto; background: rgba(0,0,0,.15); backdrop-filter: blur(10px); border-radius: var(--border-radius) var(--border-radius) 0 0; }
        .sidebar-container { display: flex; flex-direction: column; min-height: 100vh; position: relative; z-index: 1; }
        .dropdown-menu { min-width: 200px; border-radius: 10px; box-shadow: var(--shadow-hover); border: none; backdrop-filter: blur(10px); background: rgba(255,255,255,0.95); }
        .logo-container { text-align: center; padding: 20px 18px; border-bottom: 1px solid rgba(255,255,255,.2); margin-bottom: 15px; background: rgba(0,0,0,.1); backdrop-filter: blur(10px); position: relative; }
        .logo-container::after { content: ''; position: absolute; bottom: 0; left: 20%; right: 20%; height: 2px; background: var(--gradient-success); border-radius: 1px; }
        .logo-wrapper { display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .logo-image { max-height: 70px; max-width: 100%; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); transition: all 0.3s ease; }
        .logo-image:hover { transform: scale(1.05); }
        .logo-placeholder { display: flex; flex-direction: column; align-items: center; }
        .logo-icon { width: 70px; height: 70px; background: var(--gradient-success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.3); transition: all 0.3s ease; }
        .logo-icon:hover { transform: rotate(15deg) scale(1.1); }
        .logo-container h4 { text-shadow: 0 2px 4px rgba(0,0,0,0.3); font-weight: 700; letter-spacing: 1px; font-size: 1.1rem; margin-top: 8px; }
        .nav-section-title { color: rgba(255,255,255,0.7); font-size: 0.75rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; margin: 12px 18px 5px 18px; padding: 4px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .version-info { font-size: 0.75rem; color: rgba(255,255,255,.8); text-align: center; padding: 8px 0; background: rgba(0,0,0,.2); backdrop-filter: blur(10px); border-radius: 0 0 var(--border-radius) var(--border-radius); font-weight: 500; text-shadow: 0 1px 2px rgba(0,0,0,0.3); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--gradient-success); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: bold; box-shadow: 0 3px 10px rgba(0,0,0,0.3); margin-right: 12px; }
        @media (max-width: 768px) { .sidebar { min-height: auto; position: fixed; top: 0; left: -100%; width: 85%; z-index: 1050; height: 100%; overflow-y: auto; border-radius: 0 var(--border-radius) var(--border-radius) 0; } .sidebar.show { left: 0; animation: slideInLeft 0.3s ease; } @keyframes slideInLeft { from { left: -100%; } to { left: 0; } } .sidebar-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1040; display: none; backdrop-filter: blur(5px); } .sidebar-backdrop.show { display: block; animation: fadeIn 0.3s ease; } @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } } .main-content { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

            <div class="col-md-2 sidebar p-0" id="sidebar">
                <div class="sidebar-container">
                    <div>
                        <div class="logo-container">
                            <div class="logo-wrapper">
                                <?php if (!empty($logo_url) && file_exists($_SERVER['DOCUMENT_ROOT'] . parse_url(BASE_URL, PHP_URL_PATH) . '/' . $logo_url)): ?>
                                    <img src="<?php echo BASE_URL . '/' . $logo_url; ?>" alt="<?php echo htmlspecialchars($empresa_nome); ?>" class="img-fluid logo-image">
                                <?php else: ?>
                                    <div class="logo-placeholder">
                                        <div class="logo-icon">
                                            <i class="fas fa-coffee"></i>
                                        </div>
                                        <h4 class="text-white mb-0 mt-2"><?php echo htmlspecialchars($empresa_nome); ?></h4>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/inicio.php" class="nav-link <?php echo $is_inicio ? 'active' : ''; ?>">
                                    <i class="fas fa-home"></i> Início
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="nav-link <?php echo $is_dashboard ? 'active' : ''; ?>">
                                    <i class="fas fa-chart-line"></i> Dashboard
                                </a>
                            </li>
                            
                            <div class="nav-section-title">Vendas & Clientes</div>
                            
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/clientes/listar.php" class="nav-link <?php echo $is_clientes ? 'active' : ''; ?>">
                                    <i class="fas fa-users"></i> Clientes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/produtos/listar.php" class="nav-link <?php echo $is_produtos ? 'active' : ''; ?>">
                                    <i class="fas fa-box"></i> Produtos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/vendas/listar.php" class="nav-link <?php echo $is_vendas ? 'active' : ''; ?>">
                                    <i class="fas fa-shopping-cart"></i> Vendas
                                </a>
                            </li>
                            
                            <div class="nav-section-title">Compras & Fornecedores</div>
                            
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/fornecedores/listar.php" class="nav-link nav-fornecedores <?php echo $is_fornecedores ? 'active' : ''; ?>">
                                    <i class="fas fa-truck"></i> Fornecedores
                                </a>
                            </li>
                           <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo $is_lista_compras ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/lista_compras/index.php" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-clipboard-list me-1"></i>Lista de Compras
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/lista_compras/index.php">Todas as Listas</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/lista_compras/criar.php">Nova Lista</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/lista_compras/index.php?status=rascunho">Rascunhos</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/lista_compras/index.php?status=em_cotacao">Em Cotação</a></li>
                                </ul>
                            </li>
                            
                            <div class="nav-section-title">Financeiro</div>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo $is_contas ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/contas/index.php" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-file-invoice-dollar me-1"></i>Contas
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/contas/index.php">Todas as Contas</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/contas/criar.php">Nova Conta</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/contas/index.php?tipo=pagar">A Pagar</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/contas/index.php?tipo=receber">A Receber</a></li>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <a href="#" class="nav-link text-muted" onclick="return false;" style="opacity: 0.6;">
                                    <i class="fas fa-chart-pie"></i> Fluxo de Caixa
                                    <small class="ms-1" style="font-size: 0.6rem;">Em Breve</small>
                                </a>
                            </li>

                            <div class="nav-section-title">Recursos Humanos</div>
                            
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/funcionarios/listar.php" class="nav-link nav-funcionarios <?php echo ($is_funcionarios && !$is_extras) ? 'active' : ''; ?>">
                                    <i class="fas fa-id-badge"></i> Funcionários
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/funcionarios/extras.php" class="nav-link nav-extras <?php echo $is_extras ? 'active' : ''; ?>">
                                    <i class="fas fa-gift"></i> Extras
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/ponto/registrar.php" class="nav-link nav-ponto <?php echo $is_ponto ? 'active' : ''; ?>">
                                    <i class="fas fa-clock"></i> Ponto
                                </a>
                            </li>

                            <?php if(isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin'): ?>
                            <div class="nav-section-title admin-section">Administração</div>
                            
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/usuarios/listar.php" class="nav-link <?php echo $is_usuarios ? 'active' : ''; ?>">
                                    <i class="fas fa-user-cog"></i> Usuários
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo BASE_URL; ?>/config/configuracoes.php" class="nav-link <?php echo $is_config ? 'active' : ''; ?>">
                                    <i class="fas fa-cog"></i> Config
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                    <div class="user-info">
                        <div class="d-flex align-items-center mb-3">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['usuario_nome'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário'); ?></div>
                                <small class="opacity-75"><?php echo ucfirst($_SESSION['nivel_acesso'] ?? 'Usuário'); ?></small>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="<?php echo BASE_URL; ?>/perfil.php" class="btn btn-outline-light btn-sm <?php echo $is_perfil ? 'active' : ''; ?>">
                                <i class="fas fa-id-card me-2"></i> Perfil
                            </a>
                            <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                                <i class="fas fa-sign-out-alt me-2"></i> Sair
                            </a>
                        </div>
                    </div>
                    <div class="version-info">
                        <i class="fas fa-code-branch me-1"></i>
                        v<?php echo htmlspecialchars($versao_sistema); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-10 p-4 main-content">
                <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 d-md-none">
                </nav>

                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['msg_type'] ?? 'info'; ?> alert-dismissible fade show fade-in" role="alert">
                        <i class="fas fa-<?php echo $_SESSION['msg_type'] == 'success' ? 'check-circle' : ($_SESSION['msg_type'] == 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                        <?php echo $_SESSION['msg']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
                <?php endif; ?>