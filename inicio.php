<?php
session_start();
require_once 'config/database.php';

// Definir o fuso horário para Brasília/São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Buscar estatísticas rápidas
$stats = [
    'vendas_hoje' => 0,
    'funcionarios_presente' => 0,
    'vendas_mes' => 0,
    'clientes_total' => 0
];

try {
    // Vendas de hoje
    $data_hoje = date('Y-m-d');
    $sql_vendas_hoje = "SELECT COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as total
                       FROM vendas v 
                       JOIN itens_venda iv ON v.id = iv.venda_id 
                       WHERE DATE(v.data_venda) = '$data_hoje'";
    $result = $conn->query($sql_vendas_hoje);
    if ($result) {
        $stats['vendas_hoje'] = $result->fetch_assoc()['total'];
    }

    // Funcionários presentes hoje (com entrada registrada)
    $sql_funcionarios_presente = "SELECT COUNT(DISTINCT funcionario_id) as total
                                 FROM ponto_registros 
                                 WHERE data_registro = '$data_hoje' 
                                 AND entrada_manha IS NOT NULL";
    $result = $conn->query($sql_funcionarios_presente);
    if ($result) {
        $stats['funcionarios_presente'] = $result->fetch_assoc()['total'];
    }

    // Vendas do mês atual
    $mes_atual = date('m');
    $ano_atual = date('Y');
    $sql_vendas_mes = "SELECT COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as total
                      FROM vendas v 
                      JOIN itens_venda iv ON v.id = iv.venda_id 
                      WHERE MONTH(v.data_venda) = $mes_atual 
                      AND YEAR(v.data_venda) = $ano_atual";
    $result = $conn->query($sql_vendas_mes);
    if ($result) {
        $stats['vendas_mes'] = $result->fetch_assoc()['total'];
    }

    // Total de clientes
    $sql_clientes = "SELECT COUNT(*) as total FROM clientes";
    $result = $conn->query($sql_clientes);
    if ($result) {
        $stats['clientes_total'] = $result->fetch_assoc()['total'];
    }

} catch (Exception $e) {
    // Em caso de erro, manter valores padrão
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
}

// Determinar o caminho base
$base_path = '';
$current_dir = dirname($_SERVER['PHP_SELF']);
if ($current_dir != '/' && $current_dir != '\\') {
    $dirs_up = substr_count($current_dir, '/');
    if ($dirs_up > 0) {
        $base_path = str_repeat('../', $dirs_up);
    }
}

// Incluir o header padrão do sistema
include 'includes/header.php';
?>

<style>
:root {
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-danger: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
    --gradient-ponto: linear-gradient(135deg, #FF6B6B 0%, #FFD93D 100%);
    --gradient-extras: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    --gradient-compras: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%);
    --shadow-soft: 0 8px 32px rgba(0,0,0,0.1);
    --shadow-hover: 0 16px 48px rgba(0,0,0,0.2);
    --border-radius: 20px;
}

/* Header de Boas-vindas */
.welcome-header {
    background: var(--gradient-primary);
    color: white;
    padding: 2.5rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
}

.welcome-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.welcome-content {
    position: relative;
    z-index: 2;
}

.welcome-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.welcome-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 1.5rem;
}

.time-info {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border-radius: 50px;
    padding: 0.8rem 1.5rem;
    display: inline-block;
    border: 1px solid rgba(255,255,255,0.3);
}

/* Cards de Atalhos */
.shortcuts-section {
    padding: 1rem 0 2rem 0;
}

.section-title {
    text-align: center;
    margin-bottom: 2.5rem;
    color: #2c3e50;
}

.section-title h2 {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.section-subtitle {
    font-size: 1rem;
    color: #7f8c8d;
}

.shortcut-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem 1.5rem;
    text-align: center;
    box-shadow: var(--shadow-soft);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: none;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    height: 260px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.shortcut-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    transition: all 0.3s ease;
}

.shortcut-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: var(--shadow-hover);
}

.shortcut-card:active {
    transform: translateY(-5px) scale(1.01);
}

/* Cores específicas dos cards */
.card-nova-venda::before { background: var(--gradient-success); }
.card-nova-venda:hover { background: linear-gradient(135deg, rgba(17, 153, 142, 0.05), rgba(56, 239, 125, 0.05)); }

.card-ponto::before { background: var(--gradient-ponto); }
.card-ponto:hover { background: linear-gradient(135deg, rgba(255, 107, 107, 0.05), rgba(255, 217, 61, 0.05)); }

.card-extras::before { background: var(--gradient-extras); }
.card-extras:hover { background: linear-gradient(135deg, rgba(168, 237, 234, 0.3), rgba(254, 214, 227, 0.3)); }

.card-compras::before { background: var(--gradient-compras); }
.card-compras:hover { background: linear-gradient(135deg, rgba(210, 153, 194, 0.2), rgba(254, 249, 215, 0.2)); }

.card-dashboard::before { background: var(--gradient-primary); }
.card-dashboard:hover { background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05)); }

.card-relatorios::before { background: var(--gradient-info); }
.card-relatorios:hover { background: linear-gradient(135deg, rgba(79, 172, 254, 0.05), rgba(0, 242, 254, 0.05)); }

.shortcut-icon {
    font-size: 3.5rem;
    margin-bottom: 1.2rem;
    transition: all 0.3s ease;
}

.card-nova-venda .shortcut-icon { color: #11998e; }
.card-ponto .shortcut-icon { color: #FF6B6B; }
.card-extras .shortcut-icon { color: #667eea; }
.card-compras .shortcut-icon { color: #d299c2; }
.card-dashboard .shortcut-icon { color: #667eea; }
.card-relatorios .shortcut-icon { color: #4facfe; }

.shortcut-card:hover .shortcut-icon {
    transform: scale(1.15) rotate(8deg);
}

.shortcut-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.shortcut-description {
    color: #7f8c8d;
    font-size: 0.9rem;
    line-height: 1.4;
}

.shortcut-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--gradient-warning);
    color: white;
    padding: 0.25rem 0.6rem;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.coming-soon {
    opacity: 0.7;
    position: relative;
}

.coming-soon::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 8px,
        rgba(0,0,0,0.05) 8px,
        rgba(0,0,0,0.05) 16px
    );
    border-radius: var(--border-radius);
}

/* Quick Stats */
.quick-stats {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-soft);
    margin-bottom: 2rem;
}

.stat-item {
    text-align: center;
    padding: 0.8rem;
}

.stat-value {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.4rem;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* Atalhos de Teclado */
.keyboard-shortcuts {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.shortcuts-toggle {
    background: var(--gradient-primary);
    border: none;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
}

.shortcuts-toggle:hover {
    transform: scale(1.1);
    box-shadow: var(--shadow-hover);
}

.shortcuts-panel {
    position: absolute;
    bottom: 60px;
    right: 0;
    background: white;
    border-radius: var(--border-radius);
    padding: 1.2rem;
    box-shadow: var(--shadow-hover);
    min-width: 220px;
    display: none;
}

.shortcuts-panel.show {
    display: block;
    animation: slideInUp 0.3s ease;
}

.shortcut-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.4rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.shortcut-item:last-child {
    border-bottom: none;
}

.shortcut-key {
    background: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.75rem;
    color: #495057;
}

/* Animações */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(25px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in {
    animation: fadeInUp 0.6s ease forwards;
}

/* Responsivo */
@media (max-width: 768px) {
    .welcome-title {
        font-size: 1.8rem;
    }
    
    .section-title h2 {
        font-size: 1.8rem;
    }
    
    .shortcut-card {
        height: 200px;
        padding: 1.2rem 1rem;
        margin-bottom: 1rem;
    }
    
    .shortcut-icon {
        font-size: 2.8rem;
        margin-bottom: 0.8rem;
    }
    
    .shortcut-title {
        font-size: 1.1rem;
    }
    
    .stat-value {
        font-size: 1.8rem;
    }
    
    .keyboard-shortcuts {
        bottom: 15px;
        right: 15px;
    }
}

/* Loading state */
.loading-card {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>

<!-- Header de Boas-vindas -->
<div class="welcome-header animate-in">
    <div class="welcome-content">
        <h1 class="welcome-title">
            <i class="fas fa-sun me-3"></i>
            Olá, <?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nome'] ?? 'Usuário')[0]); ?>!
        </h1>
        <p class="welcome-subtitle">
            Gerencie seu negócio de forma eficiente e intuitiva
        </p>
        <div class="time-info">
            <i class="fas fa-clock me-2"></i>
            <span id="current-time"></span>
            <span class="mx-2">•</span>
            <span id="current-date"></span>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="quick-stats animate-in" style="animation-delay: 0.2s">
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="stat-item">
                <div class="stat-value">R$ <?php echo number_format($stats['vendas_hoje'], 2, ',', '.'); ?></div>
                <div class="stat-label">Vendas Hoje</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['funcionarios_presente']; ?></div>
                <div class="stat-label">Funcionários Presentes</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-item">
                <div class="stat-value">R$ <?php echo number_format($stats['vendas_mes'], 2, ',', '.'); ?></div>
                <div class="stat-label">Vendas do Mês</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['clientes_total']; ?></div>
                <div class="stat-label">Total Clientes</div>
            </div>
        </div>
    </div>
</div>

<!-- Seção de Atalhos -->
<div class="shortcuts-section">
    <div class="section-title animate-in" style="animation-delay: 0.3s">
        <h2>
            <i class="fas fa-bolt me-3"></i>
            Ações Rápidas
        </h2>
        <p class="section-subtitle">
            Acesse rapidamente as funcionalidades mais utilizadas
        </p>
    </div>

    <div class="row g-4">
        <!-- Nova Venda -->
        <div class="col-lg-4 col-md-6">
            <div class="shortcut-card card-nova-venda animate-in" 
                 style="animation-delay: 0.4s"
                 onclick="navigateTo('<?php echo $base_path; ?>vendas/nova_venda.php')"
                 data-shortcut="Alt+1">
                <div class="shortcut-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="shortcut-title">Nova Venda</h3>
                <p class="shortcut-description">
                    Registre uma nova venda rapidamente com nosso sistema intuitivo
                </p>
                <div class="shortcut-badge">Alt+1</div>
            </div>
        </div>

        <!-- Controle de Ponto -->
        <div class="col-lg-4 col-md-6">
            <div class="shortcut-card card-ponto animate-in" 
                 style="animation-delay: 0.5s"
                 onclick="navigateTo('<?php echo $base_path; ?>ponto/registrar.php')"
                 data-shortcut="Alt+2">
                <div class="shortcut-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="shortcut-title">Controle de Ponto</h3>
                <p class="shortcut-description">
                    Registre entrada, saída e controle a jornada de trabalho
                </p>
                <div class="shortcut-badge">Alt+2</div>
            </div>
        </div>

        <!-- Extras Salariais -->
        <div class="col-lg-4 col-md-6">
            <div class="shortcut-card card-extras animate-in" 
                 style="animation-delay: 0.6s"
                 onclick="navigateTo('<?php echo $base_path; ?>funcionarios/extras.php')"
                 data-shortcut="Alt+3">
                <div class="shortcut-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h3 class="shortcut-title">Extras Salariais</h3>
                <p class="shortcut-description">
                    Gerencie bonificações, horas extras e benefícios dos funcionários
                </p>
                <div class="shortcut-badge">Alt+3</div>
            </div>
        </div>

        <!-- Lista de Compras -->
        <div class="col-lg-4 col-md-6">
            <div class="shortcut-card card-compras coming-soon animate-in" 
                 style="animation-delay: 0.7s"
                 data-shortcut="Em breve">
                <div class="shortcut-icon">
                    <i class="fas fa-list-ul"></i>
                </div>
                <h3 class="shortcut-title">Lista de Compras</h3>
                <p class="shortcut-description">
                    Organize e gerencie suas listas de compras e fornecedores
                </p>
                <div class="shortcut-badge">Em Breve</div>
            </div>
        </div>

        <!-- Dashboard -->
        <div class="col-lg-4 col-md-6">
            <div class="shortcut-card card-dashboard animate-in" 
                 style="animation-delay: 0.8s"
                 onclick="navigateTo('<?php echo $base_path; ?>dashboard.php')"
                 data-shortcut="Alt+D">
                <div class="shortcut-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="shortcut-title">Dashboard</h3>
                <p class="shortcut-description">
                    Visualize métricas, gráficos e relatórios detalhados do negócio
                </p>
                <div class="shortcut-badge">Alt+D</div>
            </div>
        </div>

        <!-- Relatórios -->
        <div class="col-lg-4 col-md-6">
            <div class="shortcut-card card-relatorios animate-in" 
                 style="animation-delay: 0.9s"
                 onclick="navigateTo('<?php echo $base_path; ?>ponto/relatorios.php')"
                 data-shortcut="Alt+R">
                <div class="shortcut-icon">
                    <i class="fas fa-file-chart-line"></i>
                </div>
                <h3 class="shortcut-title">Relatórios</h3>
                <p class="shortcut-description">
                    Acesse relatórios de ponto, vendas e performance da equipe
                </p>
                <div class="shortcut-badge">Alt+R</div>
            </div>
        </div>
    </div>
</div>

<!-- Atalhos de Teclado -->
<div class="keyboard-shortcuts">
    <button class="shortcuts-toggle" onclick="toggleShortcuts()">
        <i class="fas fa-keyboard"></i>
    </button>
    <div class="shortcuts-panel" id="shortcuts-panel">
        <h6 class="mb-3">
            <i class="fas fa-keyboard me-2"></i>
            Atalhos de Teclado
        </h6>
        <div class="shortcut-item">
            <span>Nova Venda</span>
            <span class="shortcut-key">Alt + 1</span>
        </div>
        <div class="shortcut-item">
            <span>Controle de Ponto</span>
            <span class="shortcut-key">Alt + 2</span>
        </div>
        <div class="shortcut-item">
            <span>Extras Salariais</span>
            <span class="shortcut-key">Alt + 3</span>
        </div>
        <div class="shortcut-item">
            <span>Dashboard</span>
            <span class="shortcut-key">Alt + D</span>
        </div>
        <div class="shortcut-item">
            <span>Relatórios</span>
            <span class="shortcut-key">Alt + R</span>
        </div>
    </div>
</div>

<script>
// Atualizar data e hora
function updateDateTime() {
    const now = new Date();
    const timeElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');
    
    const timeOptions = { 
        hour: '2-digit', 
        minute: '2-digit',
        timeZone: 'America/Sao_Paulo'
    };
    
    const dateOptions = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        timeZone: 'America/Sao_Paulo'
    };
    
    if (timeElement) {
        timeElement.textContent = now.toLocaleTimeString('pt-BR', timeOptions);
    }
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('pt-BR', dateOptions);
    }
}

// Navegação
function navigateTo(url) {
    // Adicionar efeito de loading
    const card = event.target.closest('.shortcut-card');
    if (card) {
        card.classList.add('loading-card');
    }
    
    setTimeout(() => {
        window.location.href = url;
    }, 300);
}

// Toggle dos atalhos de teclado
function toggleShortcuts() {
    const panel = document.getElementById('shortcuts-panel');
    panel.classList.toggle('show');
}

// Fechar atalhos ao clicar fora
document.addEventListener('click', function(e) {
    const panel = document.getElementById('shortcuts-panel');
    const toggle = document.querySelector('.shortcuts-toggle');
    
    if (!panel.contains(e.target) && !toggle.contains(e.target)) {
        panel.classList.remove('show');
    }
});

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    if (e.altKey) {
        switch(e.key) {
            case '1':
                e.preventDefault();
                window.location.href = '<?php echo $base_path; ?>vendas/nova_venda.php';
                break;
            case '2':
                e.preventDefault();
                window.location.href = '<?php echo $base_path; ?>ponto/registrar.php';
                break;
            case '3':
                e.preventDefault();
                window.location.href = '<?php echo $base_path; ?>funcionarios/extras.php';
                break;
            case 'd':
            case 'D':
                e.preventDefault();
                window.location.href = '<?php echo $base_path; ?>dashboard.php';
                break;
            case 'r':
            case 'R':
                e.preventDefault();
                window.location.href = '<?php echo $base_path; ?>ponto/relatorios.php';
                break;
        }
    }
});

// Efeitos visuais nos cards
document.querySelectorAll('.shortcut-card:not(.coming-soon)').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px) scale(1.03)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    setInterval(updateDateTime, 1000);
    
    // Animações escalonadas
    const elements = document.querySelectorAll('.animate-in');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(25px)';
        
        setTimeout(() => {
            el.style.transition = 'all 0.6s ease';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Feedback tátil (vibração em dispositivos móveis)
function hapticFeedback() {
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

// Adicionar feedback tátil aos cliques
document.querySelectorAll('.shortcut-card').forEach(card => {
    card.addEventListener('click', hapticFeedback);
});

// Notificação de boas-vindas (apenas uma vez por sessão)
if (!sessionStorage.getItem('welcomeShown')) {
    setTimeout(() => {
        if (typeof showToast === 'function') {
            showToast('Bem-vindo ao sistema! Use Alt+1, Alt+2, Alt+3 para acesso rápido.', 'info');
        }
        sessionStorage.setItem('welcomeShown', 'true');
    }, 2000);
}
</script>

<?php include 'includes/footer.php'; ?>