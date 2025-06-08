<?php
// funcionarios/extras_detalhes.php - Detalhes dos extras de um funcionário (VERSÃO CORRIGIDA E ATUALIZADA)
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Incluir arquivo de conexão
require_once '../config/database.php';

// FUNÇÕES PARA FORMATAR MÊS EM PORTUGUÊS
function formatarMesPT($data) {
    $meses = [
        'January' => 'Janeiro',
        'February' => 'Fevereiro',
        'March' => 'Março',
        'April' => 'Abril',
        'May' => 'Maio',
        'June' => 'Junho',
        'July' => 'Julho',
        'August' => 'Agosto',
        'September' => 'Setembro',
        'October' => 'Outubro',
        'November' => 'Novembro',
        'December' => 'Dezembro'
    ];

    $mesIngles = date('F', strtotime($data));
    $ano = date('/Y', strtotime($data));

    return $meses[$mesIngles] . $ano;
}

function formatarMesAbrevPT($data) {
    $meses = [
        'Jan' => 'Jan',
        'Feb' => 'Fev',
        'Mar' => 'Mar',
        'Apr' => 'Abr',
        'May' => 'Mai',
        'Jun' => 'Jun',
        'Jul' => 'Jul',
        'Aug' => 'Ago',
        'Sep' => 'Set',
        'Oct' => 'Out',
        'Nov' => 'Nov',
        'Dec' => 'Dez'
    ];

    $mesIngles = date('M', strtotime($data));
    $ano = date('/Y', strtotime($data));

    return $meses[$mesIngles] . $ano;
}


// Obter parâmetros
$funcionario_id = (int)($_GET['funcionario_id'] ?? 0);
$mes_selecionado = $_GET['mes'] ?? date('Y-m');

if ($funcionario_id <= 0) {
    $_SESSION['msg'] = "Funcionário não encontrado.";
    $_SESSION['msg_type'] = "danger";
    header("Location: extras.php");
    exit;
}

// Verificar se é admin
$is_admin = isset($_SESSION['nivel_acesso']) && ($_SESSION['nivel_acesso'] == 'admin');

// Buscar dados do funcionário
try {
    $sql_func = "SELECT * FROM funcionarios WHERE id = ?";
    $stmt_func = $conn->prepare($sql_func);
    $stmt_func->bind_param("i", $funcionario_id);
    $stmt_func->execute();
    $result_func = $stmt_func->get_result();

    if ($result_func->num_rows === 0) {
        $_SESSION['msg'] = "Funcionário não encontrado.";
        $_SESSION['msg_type'] = "danger";
        header("Location: extras.php");
        exit;
    }

    $funcionario = $result_func->fetch_assoc();
    $stmt_func->close();

    // Buscar extras do funcionário no mês
    $sql_extras = "SELECT fe.*, et.nome as tipo_nome, et.cor, et.icone,
                          u.nome as concedido_por_nome
                   FROM funcionarios_extras fe
                   JOIN extras_tipos et ON fe.extra_tipo_id = et.id
                   LEFT JOIN usuarios u ON fe.concedido_por = u.id
                   WHERE fe.funcionario_id = ?
                   AND DATE_FORMAT(fe.mes_referencia, '%Y-%m') = ?
                   ORDER BY fe.concedido_em DESC";

    $stmt_extras = $conn->prepare($sql_extras);
    $stmt_extras->bind_param("is", $funcionario_id, $mes_selecionado);
    $stmt_extras->execute();
    $extras = $stmt_extras->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_extras->close();

    // Calcular total dos extras do mês
    $total_extras_mes = array_sum(array_column($extras, 'valor'));

    // Buscar histórico simplificado (sem GROUP BY problemático)
    $sql_historico_raw = "SELECT mes_referencia, valor
                          FROM funcionarios_extras
                          WHERE funcionario_id = ?
                          AND mes_referencia >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                          ORDER BY mes_referencia DESC";

    $stmt_historico = $conn->prepare($sql_historico_raw);
    $stmt_historico->bind_param("i", $funcionario_id);
    $stmt_historico->execute();
    $result_historico = $stmt_historico->get_result();

    // Processar histórico em PHP
    $historico = [];
    while ($row = $result_historico->fetch_assoc()) {
        $mes_key = date('Y-m', strtotime($row['mes_referencia']));

        if (!isset($historico[$mes_key])) {
            $historico[$mes_key] = [
                'mes' => $mes_key,
                'quantidade' => 0,
                'total' => 0
            ];
        }

        $historico[$mes_key]['quantidade']++;
        $historico[$mes_key]['total'] += $row['valor'];
    }

    // Converter para array indexado e ordenar
    $historico = array_values($historico);
    usort($historico, function($a, $b) {
        return strcmp($b['mes'], $a['mes']);
    });

    $stmt_historico->close();

    // Estatísticas gerais simplificadas
    $sql_stats_count = "SELECT COUNT(*) as total FROM funcionarios_extras WHERE funcionario_id = ?";
    $stmt_count = $conn->prepare($sql_stats_count);
    $stmt_count->bind_param("i", $funcionario_id);
    $stmt_count->execute();
    $count_result = $stmt_count->get_result();
    $total_extras_recebidos = $count_result->fetch_assoc()['total'];
    $stmt_count->close();

    $sql_stats_sum = "SELECT SUM(valor) as total FROM funcionarios_extras WHERE funcionario_id = ?";
    $stmt_sum = $conn->prepare($sql_stats_sum);
    $stmt_sum->bind_param("i", $funcionario_id);
    $stmt_sum->execute();
    $sum_result = $stmt_sum->get_result();
    $total_valor_recebido = $sum_result->fetch_assoc()['total'] ?? 0;
    $stmt_sum->close();

    $sql_stats_max = "SELECT MAX(valor) as maximo FROM funcionarios_extras WHERE funcionario_id = ?";
    $stmt_max = $conn->prepare($sql_stats_max);
    $stmt_max->bind_param("i", $funcionario_id);
    $stmt_max->execute();
    $max_result = $stmt_max->get_result();
    $maior_extra = $max_result->fetch_assoc()['maximo'] ?? 0;
    $stmt_max->close();

    $sql_stats_first = "SELECT MIN(concedido_em) as primeiro FROM funcionarios_extras WHERE funcionario_id = ?";
    $stmt_first = $conn->prepare($sql_stats_first);
    $stmt_first->bind_param("i", $funcionario_id);
    $stmt_first->execute();
    $first_result = $stmt_first->get_result();
    $primeiro_extra = $first_result->fetch_assoc()['primeiro'];
    $stmt_first->close();

    // Montar array de estatísticas
    $stats = [
        'total_extras_recebidos' => $total_extras_recebidos,
        'total_valor_recebido' => $total_valor_recebido,
        'media_por_extra' => $total_extras_recebidos > 0 ? ($total_valor_recebido / $total_extras_recebidos) : 0,
        'maior_extra' => $maior_extra,
        'primeiro_extra' => $primeiro_extra
    ];

} catch (Exception $e) {
    $_SESSION['msg'] = "Erro ao buscar dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
    header("Location: extras.php");
    exit;
}

// Função para gerar cor baseada no nome
function stringToColor($str) {
    $hash = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $hash = ord($str[$i]) + (($hash << 5) - $hash);
    }
    $hue = abs($hash) % 360;
    return "hsl($hue, 70%, 50%)";
}

// Incluir o cabeçalho
include_once '../includes/header.php';
?>

<style>
:root {
    --extras-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gold-gradient: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
    --border-radius: 15px;
}

.profile-header {
    background: var(--extras-gradient);
    color: white;
    padding: 40px 0;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '💎';
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 4rem;
    opacity: 0.3;
    animation: sparkle 3s ease-in-out infinite;
}

@keyframes sparkle {
    0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.3; }
    50% { transform: scale(1.1) rotate(180deg); opacity: 0.6; }
}

.funcionario-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2.5rem;
    margin-right: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    position: relative;
    overflow: hidden;
}

.funcionario-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.total-destaque {
    font-size: 3rem;
    font-weight: bold;
    background: var(--gold-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 10px;
}

.stats-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    text-align: center;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    border-top: 4px solid;
    margin-bottom: 20px;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.stats-card.total-recebidos { border-top-color: #17a2b8; }
.stats-card.valor-total { border-top-color: #28a745; }
.stats-card.media-extra { border-top-color: #ffc107; }
.stats-card.maior-extra { border-top-color: #dc3545; }

.stats-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.extra-item {
    background: white;
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    border-left: 4px solid;
    position: relative;
}

.extra-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.extra-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-right: 15px;
    box-shadow: var(--shadow-soft);
}

.extra-valor {
    font-size: 1.5rem;
    font-weight: bold;
    color: #28a745;
}

.historico-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--shadow-soft);
}

.historico-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.historico-item:last-child {
    border-bottom: none;
}

.historico-mes {
    font-weight: 600;
    color: #333;
}

.historico-valor {
    font-weight: bold;
    color: #28a745;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.3;
}

.month-badge {
    background: var(--info-gradient);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .profile-header {
        padding: 20px 0;
    }

    .funcionario-avatar {
        width: 80px;
        height: 80px;
        font-size: 2rem;
        margin-right: 15px;
    }

    .total-destaque {
        font-size: 2rem;
    }

    .stats-number {
        font-size: 1.5rem;
    }
}
</style>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="listar.php">Funcionários</a></li>
        <li class="breadcrumb-item"><a href="extras.php">Extras</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($funcionario['nome']); ?></li>
    </ol>
</nav>

<div class="profile-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="funcionario-avatar" style="background: <?php echo stringToColor($funcionario['nome']); ?>;">
                        <?php if (!empty($funcionario['foto']) && file_exists('../' . $funcionario['foto'])): ?>
                            <img src="../<?php echo htmlspecialchars($funcionario['foto']); ?>" alt="Foto">
                        <?php else: ?>
                            <?php echo strtoupper(substr($funcionario['nome'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 class="mb-2"><?php echo htmlspecialchars($funcionario['nome']); ?></h1>
                        <p class="mb-1 fs-5"><?php echo htmlspecialchars($funcionario['cargo']); ?></p>
                        <p class="mb-0"><?php echo htmlspecialchars($funcionario['departamento']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <div class="month-badge">
                    <?php echo formatarMesPT($mes_selecionado . '-01'); ?>
                </div>
                <div class="total-destaque mt-2">
                    <?php if ($total_extras_mes > 0): ?>
                        + R$ <?php echo number_format($total_extras_mes, 2, ',', '.'); ?>
                    <?php else: ?>
                        R$ 0,00
                    <?php endif; ?>
                </div>
                <small class="opacity-75">Total de extras do mês</small>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>
                    <i class="fas fa-gift me-2 text-primary"></i>
                    Extras do Mês
                </h3>
                <div>
                    <select class="form-select" onchange="alterarMes(this.value)">
                        <?php
                        for ($i = 0; $i < 12; $i++) {
                            $mes = date('Y-m', strtotime("-$i months"));
                            $selected = ($mes == $mes_selecionado) ? 'selected' : '';
                            echo "<option value='$mes' $selected>" . formatarMesPT($mes . '-01') . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <?php if (!empty($extras)): ?>
                <?php foreach ($extras as $extra): ?>
                    <div class="extra-item" style="border-left-color: <?php echo $extra['cor']; ?>;">
                        <div class="d-flex align-items-center">
                            <div class="extra-icon" style="background-color: <?php echo $extra['cor']; ?>;">
                                <i class="<?php echo $extra['icone']; ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($extra['tipo_nome']); ?></h6>
                                <small class="text-muted">
                                    Concedido por <?php echo htmlspecialchars($extra['concedido_por_nome'] ?? 'Sistema'); ?>
                                    em <?php echo date('d/m/Y \à\s H:i', strtotime($extra['concedido_em'])); ?>
                                </small>
                                <?php if (!empty($extra['observacao'])): ?>
                                    <div class="mt-2">
                                        <small class="text-info">
                                            <i class="fas fa-comment me-1"></i>
                                            <?php echo htmlspecialchars($extra['observacao']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <div class="extra-valor">
                                    + R$ <?php echo number_format($extra['valor'], 2, ',', '.'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-gift"></i>
                    <h5>Nenhum extra concedido</h5>
                    <p>Este funcionário não recebeu extras no mês selecionado.</p>
                    <?php if ($is_admin): ?>
                        <button class="btn btn-primary" onclick="concederExtra()">
                            <i class="fas fa-plus me-2"></i>Conceder Extra
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-chart-bar me-2 text-info"></i>
                    Estatísticas Gerais
                </h5>

                <div class="stats-card total-recebidos">
                    <div class="stats-number text-info"><?php echo $stats['total_extras_recebidos']; ?></div>
                    <small class="text-muted">Extras Recebidos</small>
                </div>

                <div class="stats-card valor-total">
                    <div class="stats-number text-success">
                        R$ <?php echo number_format($stats['total_valor_recebido'], 2, ',', '.'); ?>
                    </div>
                    <small class="text-muted">Valor Total</small>
                </div>

                <div class="stats-card media-extra">
                    <div class="stats-number text-warning">
                        R$ <?php echo number_format($stats['media_por_extra'], 2, ',', '.'); ?>
                    </div>
                    <small class="text-muted">Média por Extra</small>
                </div>

                <div class="stats-card maior-extra">
                    <div class="stats-number text-danger">
                        R$ <?php echo number_format($stats['maior_extra'], 2, ',', '.'); ?>
                    </div>
                    <small class="text-muted">Maior Extra</small>
                </div>
            </div>

            <div class="historico-card">
                <h6 class="mb-3">
                    <i class="fas fa-history me-2 text-secondary"></i>
                    Histórico (12 meses)
                </h6>

                <?php if (!empty($historico)): ?>
                    <?php foreach ($historico as $mes_hist): ?>
                        <div class="historico-item">
                            <div>
                                <div class="historico-mes">
                                    <?php echo formatarMesAbrevPT($mes_hist['mes'] . '-01'); ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo $mes_hist['quantidade']; ?> extra<?php echo $mes_hist['quantidade'] > 1 ? 's' : ''; ?>
                                </small>
                            </div>
                            <div class="historico-valor">
                                R$ <?php echo number_format($mes_hist['total'], 2, ',', '.'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-calendar-times fa-2x mb-2"></i>
                        <p class="mb-0">Nenhum histórico encontrado</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4">
                <div class="d-grid gap-2">
                    <?php if ($is_admin): ?>
                        <button class="btn btn-primary" onclick="concederExtra()">
                            <i class="fas fa-plus me-2"></i>Conceder Novo Extra
                        </button>
                    <?php endif; ?>
                    <a href="visualizar.php?id=<?php echo $funcionario['id']; ?>" class="btn btn-outline-info">
                        <i class="fas fa-user me-2"></i>Ver Perfil Completo
                    </a>
                    <a href="extras.php?mes=<?php echo $mes_selecionado; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar aos Extras
                    </a>
                </div>
            </div>

            <div class="mt-4 p-3" style="background: #f8f9fa; border-radius: 10px;">
                <h6 class="mb-2">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações do Funcionário
                </h6>
                <small class="text-muted d-block">
                    <strong>Código:</strong> <?php echo htmlspecialchars($funcionario['codigo']); ?>
                </small>
                <small class="text-muted d-block">
                    <strong>Status:</strong>
                    <span class="badge bg-<?php echo $funcionario['status'] == 'ativo' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($funcionario['status']); ?>
                    </span>
                </small>
                <?php if (!empty($funcionario['data_admissao'])): ?>
                    <small class="text-muted d-block">
                        <strong>Admissão:</strong> <?php echo date('d/m/Y', strtotime($funcionario['data_admissao'])); ?>
                    </small>
                <?php endif; ?>
                <?php if ($stats['primeiro_extra']): ?>
                    <small class="text-muted d-block">
                        <strong>Primeiro Extra:</strong> <?php echo date('d/m/Y', strtotime($stats['primeiro_extra'])); ?>
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function alterarMes(mes) {
    window.location.href = `extras_detalhes.php?funcionario_id=<?php echo $funcionario_id; ?>&mes=${mes}`;
}

function concederExtra() {
    // Redirecionar para a página de extras com o funcionário pré-selecionado
    window.location.href = `extras.php?mes=<?php echo $mes_selecionado; ?>&funcionario_id=<?php echo $funcionario_id; ?>`;
}

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    if (e.altKey) {
        switch(e.key) {
            case 'n':
                e.preventDefault();
                <?php if ($is_admin): ?>
                concederExtra();
                <?php endif; ?>
                break;
            case 'p':
                e.preventDefault();
                window.location.href = 'visualizar.php?id=<?php echo $funcionario_id; ?>';
                break;
            case 'e':
                e.preventDefault();
                window.location.href = 'extras.php?mes=<?php echo $mes_selecionado; ?>';
                break;
            case 'l':
                e.preventDefault();
                window.location.href = 'listar.php';
                break;
        }
    }
});

// Animações ao carregar
document.addEventListener('DOMContentLoaded', function() {
    // Animar cards com delay
    const items = document.querySelectorAll('.extra-item, .stats-card');
    items.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';

        setTimeout(() => {
            item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Highlight do mês atual se estiver selecionado
    const mesAtual = '<?php echo date('Y-m'); ?>';
    const mesSelecionado = '<?php echo $mes_selecionado; ?>';

    if (mesAtual === mesSelecionado) {
        document.querySelector('.month-badge').style.background = 'var(--success-gradient)';
    }
});

// Auto-refresh a cada 5 minutos para verificar novos extras
setInterval(function() {
    // Verificar se há novos extras sem recarregar completamente
    fetch(`extras_check_updates.php?funcionario_id=<?php echo $funcionario_id; ?>&mes=<?php echo $mes_selecionado; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.hasUpdates) {
                // Mostrar notificação discreta
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed';
                toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-gift me-2"></i>
                            Novos extras foram adicionados! Clique para atualizar.
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="location.reload()"></button>
                    </div>
                `;

                document.body.appendChild(toast);

                const bsToast = new bootstrap.Toast(toast); // Supondo que Bootstrap 5 Toast está disponível
                bsToast.show();
            }
        })
        .catch(error => {
            console.log('Erro ao verificar atualizações:', error);
        });
}, 300000); // 5 minutos

// Função para imprimir relatório do funcionário
function imprimirRelatorio() {
    window.print();
}

// Adicionar ctrl+p para imprimir
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        imprimirRelatorio();
    }
});
</script>

<style media="print">
    .btn, .breadcrumb, .sidebar, .form-select,
    div[onclick^="alterarMes"],
    button[onclick^="concederExtra"] {
        display: none !important;
    }

    .col-lg-4 { /* Sidebar */
        display: none !important;
    }
    .col-lg-8 { /* Conteúdo principal */
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }

    .profile-header {
        background: #f093fb !important; /* Mantém a cor de fundo, mas pode não ser ideal para impressão */
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
        padding: 20px 0 !important;
    }

    .profile-header .month-badge {
        display: inline-block !important; /* Garantir que o badge do mês seja impresso */
    }


    .container {
        max-width: 100% !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .extra-item {
        page-break-inside: avoid;
        border: 1px solid #ddd !important;
        margin-bottom: 10px !important;
    }

    .stats-card { /* As estatísticas gerais estarão na sidebar que é oculta, então não precisa disso */
        /* display: none !important; */
    }

    .historico-card { /* O histórico estará na sidebar que é oculta, então não precisa disso */
       /* display: none !important; */
    }

    .d-flex.justify-content-between.align-items-center.mb-4 h3 {
        width: 100%; /* Ajusta o título "Extras do Mês" quando o select é removido */
    }


    @page {
        margin: 1cm;
        size: A4;
    }

    body {
        font-size: 10pt; /* Reduzir tamanho da fonte para melhor encaixe */
    }

    .extra-valor {
        font-size: 1.2rem;
    }
    .total-destaque {
        font-size: 2rem;
    }
    h1 {
        font-size: 1.5rem;
    }
</style>

<?php
// Incluir o rodapé
include_once '../includes/footer.php';
?>