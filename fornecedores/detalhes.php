<?php
// fornecedores/detalhes.php - VERSÃO COMPLETA CORRIGIDA
require_once '../config/database.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Verificar se o ID do fornecedor foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Fornecedor não especificado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$fornecedor_id = intval($_GET['id']);

// Buscar informações do fornecedor
$sql_fornecedor = "SELECT f.*,
                    GROUP_CONCAT(DISTINCT cat.nome ORDER BY cat.nome ASC SEPARATOR ', ') as categorias,
                    GROUP_CONCAT(DISTINCT cat.cor ORDER BY cat.nome ASC SEPARATOR ',') as cores_categorias,
                    GROUP_CONCAT(DISTINCT cat.icone ORDER BY cat.nome ASC SEPARATOR ',') as icones_categorias
                   FROM fornecedores f
                   LEFT JOIN fornecedor_categorias fc ON f.id = fc.fornecedor_id
                   LEFT JOIN categorias_fornecedores cat ON fc.categoria_id = cat.id
                   WHERE f.id = $fornecedor_id
                   GROUP BY f.id";
$result_fornecedor = $conn->query($sql_fornecedor);

if (!$result_fornecedor || $result_fornecedor->num_rows == 0) {
    $_SESSION['msg'] = "Fornecedor não encontrado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}
$fornecedor = $result_fornecedor->fetch_assoc();

// Buscar estatísticas do fornecedor
$sql_stats = "SELECT
              COUNT(DISTINCT pf.id) as total_pedidos,
              COALESCE(SUM(CASE WHEN pf.status = 'entregue' THEN pf.valor_total END), 0) as valor_total_comprado,
              COALESCE(SUM(CASE WHEN pf.status IN ('pendente', 'confirmado', 'em_transito') THEN pf.valor_total END), 0) as valor_pedidos_abertos,
              MAX(pf.data_pedido) as ultimo_pedido,
              MIN(pf.data_pedido) as primeiro_pedido,
              AVG(CASE WHEN pf.data_entrega_realizada IS NOT NULL AND pf.data_entrega_prevista IS NOT NULL
                   THEN DATEDIFF(pf.data_entrega_realizada, pf.data_entrega_prevista) END) as media_atraso_dias,
              COUNT(CASE WHEN pf.status = 'entregue' AND pf.data_entrega_realizada <= pf.data_entrega_prevista THEN 1 END) as entregas_no_prazo,
              COUNT(CASE WHEN pf.status = 'entregue' THEN 1 END) as total_entregas,
              COUNT(CASE WHEN pf.status = 'cancelado' THEN 1 END) as pedidos_cancelados
              FROM pedidos_fornecedores pf
              WHERE pf.fornecedor_id = $fornecedor_id";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

// Buscar últimos pedidos
$sql_pedidos = "SELECT pf.id, pf.numero_pedido, pf.data_pedido, pf.data_entrega_prevista,
                 pf.data_entrega_realizada, pf.valor_total, pf.status, pf.observacoes,
                COUNT(ipf.id) as total_itens
                FROM pedidos_fornecedores pf
                LEFT JOIN itens_pedido_fornecedor ipf ON pf.id = ipf.pedido_id
                WHERE pf.fornecedor_id = $fornecedor_id
                GROUP BY pf.id
                ORDER BY pf.data_pedido DESC
                LIMIT 10";
$result_pedidos = $conn->query($sql_pedidos);

// Buscar contatos do fornecedor
$sql_contatos = "SELECT * FROM contatos_fornecedores
                  WHERE fornecedor_id = $fornecedor_id
                  ORDER BY eh_principal DESC, nome ASC";
$result_contatos = $conn->query($sql_contatos);

// Buscar últimas comunicações - CONSULTA CORRIGIDA
$sql_comunicacoes = "SELECT cf.*, u.nome as usuario_nome
                     FROM comunicacoes_fornecedores cf
                     LEFT JOIN usuarios u ON cf.usuario_id = u.id
                     WHERE cf.fornecedor_id = $fornecedor_id
                     ORDER BY cf.data_comunicacao DESC
                     LIMIT 10";
$result_comunicacoes = $conn->query($sql_comunicacoes);

// Verificar se há mensagens na sessão
$msg = isset($_SESSION['msg']) ? $_SESSION['msg'] : '';
$msg_type = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : '';
unset($_SESSION['msg']);
unset($_SESSION['msg_type']);

// Preparar mensagem para WhatsApp
$mensagem_whatsapp = "Olá " . $fornecedor['nome'] . "! ";
if ($stats['valor_pedidos_abertos'] > 0) {
    $mensagem_whatsapp .= "Gostaríamos de verificar o status dos nossos pedidos em aberto. Poderia nos atualizar?";
} else {
    $mensagem_whatsapp .= "Gostaríamos de fazer um novo pedido. Poderia nos ajudar com o orçamento?";
}
// Codificar a mensagem para URL
$mensagem_whatsapp_codificada = urlencode($mensagem_whatsapp);

// Função para gerar cor baseada em string (versão PHP)
function stringToColor($str) {
    $hash = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $hash = ord($str[$i]) + (($hash << 5) - $hash);
    }
    $color = '#';
    for ($i = 0; $i < 3; $i++) {
        $value = ($hash >> ($i * 8)) & 0xFF;
        $color .= str_pad(dechex($value), 2, '0', STR_PAD_LEFT);
    }
    return $color;
}

// Função para mapear status para classes de cor
function getStatusClass($status) {
    switch ($status) {
        case 'entregue': return 'success';
        case 'cancelado': return 'danger';
        case 'em_transito': return 'info';
        case 'confirmado': return 'primary';
        case 'pendente': default: return 'warning';
    }
}

include '../includes/header.php';
?>
<style>
    :root {
        --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --gradient-danger: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        --gradient-fornecedores: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);
        --shadow-soft: 0 2px 15px rgba(0, 0, 0, 0.1);
        --shadow-hover: 0 5px 25px rgba(0, 0, 0, 0.15);
    }
    .profile-header { background: var(--gradient-fornecedores); color: white; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; position: relative; overflow: hidden; }
    .profile-header::before { content: ''; position: absolute; top: -50%; right: -20%; width: 200px; height: 200px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; animation: pulse 4s ease-in-out infinite; }
    @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 0.1; } 50% { transform: scale(1.1); opacity: 0.2; } }
    .fornecedor-avatar-xl { width: 120px; height: 120px; border-radius: 20px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold; margin-bottom: 1rem; backdrop-filter: blur(10px); border: 3px solid rgba(255, 255, 255, 0.3); }
    .stats-card { background: white; border: none; border-radius: 15px; box-shadow: var(--shadow-soft); transition: all 0.3s ease; overflow: hidden; position: relative; }
    .stats-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
    .stats-card .card-header { border: none; padding: 1.5rem; background: var(--gradient-info); color: white; position: relative; }
    .stats-card.danger .card-header { background: var(--gradient-danger); }
    .stats-card.success .card-header { background: var(--gradient-success); }
    .stats-card.warning .card-header { background: var(--gradient-warning); }
    .stats-card.fornecedores .card-header { background: var(--gradient-fornecedores); }
    .metric-card { background: white; border: none; border-radius: 15px; padding: 1.5rem; text-align: center; box-shadow: var(--shadow-soft); transition: all 0.3s ease; margin-bottom: 1rem; }
    .metric-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
    .metric-value { font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem; }
    .metric-label { color: #6c757d; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem; }
    .status-badge.ativo { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
    .status-badge.inativo { background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; }
    .action-btn { border-radius: 12px; padding: 0.75rem 1.5rem; font-weight: 500; transition: all 0.3s ease; border: none; position: relative; overflow: hidden; }
    .action-btn::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent); transition: left 0.5s; }
    .action-btn:hover::before { left: 100%; }
    .action-btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }
    .action-btn.primary { background: var(--gradient-primary); color: white; }
    .action-btn.success { background: var(--gradient-success); color: white; }
    .action-btn.warning { background: var(--gradient-warning); color: white; }
    .action-btn.whatsapp { background: linear-gradient(135deg, #25d366, #128c7e); color: white; }
    .action-btn.fornecedores { background: var(--gradient-fornecedores); color: white; }
    .data-table { border-radius: 15px; overflow: hidden; box-shadow: var(--shadow-soft); }
    .data-table .table { margin: 0; }
    .data-table .table thead { background: var(--gradient-fornecedores); color: white; }
    .data-table .table tbody tr { border: none; transition: all 0.3s ease; }
    .data-table .table tbody tr:hover { background: rgba(253, 126, 20, 0.1); transform: scale(1.01); }
    .badge-categoria { border-radius: 20px; padding: 0.3rem 0.8rem; font-weight: 500; font-size: 0.75rem; margin: 2px; display: inline-block; }
    .avaliacao-stars { color: #ffc107; font-size: 1.2rem; }
    .info-item { background: white; padding: 1rem; border-radius: 12px; box-shadow: var(--shadow-soft); border-left: 4px solid #fd7e14; margin-bottom: 1rem; }
    .info-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; }
    .info-value { font-size: 1.1rem; font-weight: 600; color: #495057; }
    .timeline { list-style: none; padding: 0; position: relative; }
    .timeline-item { border-left: 3px solid #e9ecef; padding: 0.5rem 0 1.5rem 1.5rem; position: relative; }
    .timeline-item:last-child { border-left: 3px solid transparent; }
    .timeline-item::before { content: ''; width: 15px; height: 15px; border-radius: 50%; background: #fd7e14; border: 3px solid white; position: absolute; left: -9px; top: 0.5rem; }
    .timeline-item.whatsapp::before { background: #25d366; }
    .timeline-item.email::before { background: #007bff; }
    .timeline-item.telefone::before { background: #6c757d; }
    .empty-state { text-align: center; padding: 3rem 1rem; color: #6c757d; }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }
    .fornecedor-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .contato-card { display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid #e9ecef; transition: background 0.3s; }
    .contato-card:last-child { border-bottom: none; }
    .contato-card:hover { background: #f8f9fa; }
    .contato-avatar { width: 45px; height: 45px; border-radius: 50%; background: var(--gradient-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; margin-right: 1rem; }
    .contato-info { flex-grow: 1; }
    .contato-actions a { color: #6c757d; margin-left: 0.75rem; font-size: 1.1rem; transition: color 0.3s; }
    .contato-actions a:hover { color: var(--bs-primary); }
    @keyframes slideInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .slide-in { animation: slideInUp 0.6s ease-out forwards; }
</style>

<div class="profile-header slide-in">
    <div class="row align-items-center">
        <div class="col-auto">
            <div class="fornecedor-avatar-xl" style="background: linear-gradient(135deg, <?php echo stringToColor($fornecedor['nome']); ?>, <?php echo stringToColor($fornecedor['nome'] . 'x'); ?>);">
                <?php echo strtoupper(substr($fornecedor['nome'], 0, 1)); ?>
            </div>
        </div>
        <div class="col">
            <h1 class="mb-2"><?php echo htmlspecialchars($fornecedor['nome']); ?></h1>
            <?php if (!empty($fornecedor['empresa'])) : ?>
                <h5 class="mb-3 opacity-75"><?php echo htmlspecialchars($fornecedor['empresa']); ?></h5>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="status-badge <?php echo $fornecedor['status']; ?>">
                    <i class="fas fa-<?php echo $fornecedor['status'] == 'ativo' ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo ucfirst($fornecedor['status']); ?>
                </span>
                <span class="badge bg-light text-dark">
                    <i class="fas fa-<?php echo $fornecedor['tipo_fornecedor'] == 'produtos' ? 'box' : ($fornecedor['tipo_fornecedor'] == 'servicos' ? 'handshake' : 'layer-group'); ?>"></i>
                    <?php echo ucfirst($fornecedor['tipo_fornecedor']); ?>
                </span>
                <?php if ($stats['ultimo_pedido']) : ?>
                    <?php
                    $dias_ultimo_pedido = floor((time() - strtotime($stats['ultimo_pedido'])) / (60 * 60 * 24));
                    if ($dias_ultimo_pedido <= 30) :
                    ?>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-fire"></i> Fornecedor Ativo
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="avaliacao-stars">
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <i class="fa<?php echo $i <= $fornecedor['avaliacao'] ? 's' : 'r'; ?> fa-star"></i>
                    <?php endfor; ?>
                    <small class="text-white ms-1">(<?php echo number_format($fornecedor['avaliacao'], 1); ?>)</small>
                </div>
            </div>

            <?php if (!empty($fornecedor['categorias'])) : ?>
                <div class="mt-3">
                    <?php
                    $cats = explode(', ', $fornecedor['categorias']);
                    $cores = explode(',', $fornecedor['cores_categorias']);
                    $icones = explode(',', $fornecedor['icones_categorias']);
                    foreach ($cats as $index => $cat) :
                        $cor = isset($cores[$index]) ? $cores[$index] : '#6c757d';
                        $icone = isset($icones[$index]) ? $icones[$index] : 'fas fa-tag';
                    ?>
                        <span class="badge-categoria" style="background: <?php echo $cor; ?>; color: white;">
                            <i class="<?php echo $icone; ?> me-1"></i>
                            <?php echo htmlspecialchars($cat); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-auto">
            <div class="d-flex flex-column gap-2">
                <a href="listar.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i> Voltar
                </a>
                <a href="editar.php?id=<?php echo $fornecedor_id; ?>" class="btn btn-light">
                    <i class="fas fa-edit me-2"></i> Editar
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($msg) : ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show slide-in" role="alert">
        <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Métricas Principais -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-card slide-in" style="animation-delay: 0.1s">
            <div class="metric-value text-primary"><?php echo $stats['total_pedidos']; ?></div>
            <div class="metric-label">Total de Pedidos</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-card slide-in" style="animation-delay: 0.2s">
            <div class="metric-value text-success">R$ <?php echo number_format($stats['valor_total_comprado'], 0, ',', '.'); ?></div>
            <div class="metric-label">Total Comprado</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-card slide-in" style="animation-delay: 0.3s">
            <div class="metric-value text-warning">R$ <?php echo number_format($stats['valor_pedidos_abertos'], 0, ',', '.'); ?></div>
            <div class="metric-label">Pedidos Abertos</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-card slide-in" style="animation-delay: 0.4s">
            <div class="metric-value text-info">
                <?php
                if ($stats['total_entregas'] > 0) {
                    echo round(($stats['entregas_no_prazo'] / $stats['total_entregas']) * 100) . '%';
                } else {
                    echo 'N/A';
                }
                ?>
            </div>
            <div class="metric-label">Entregas no Prazo</div>
        </div>
    </div>
</div>

<!-- Informações Detalhadas -->
<div class="fornecedor-info-grid slide-in" style="animation-delay: 0.5s">
    <?php if (!empty($fornecedor['cnpj'])) : ?>
        <div class="info-item">
            <div class="info-label">CNPJ</div>
            <div class="info-value"><?php echo htmlspecialchars($fornecedor['cnpj']); ?></div>
        </div>
    <?php endif; ?>
    <?php if (!empty($fornecedor['telefone'])) : ?>
        <div class="info-item">
            <div class="info-label">Telefone</div>
            <div class="info-value">
                <i class="fas fa-phone me-1"></i>
                <?php echo htmlspecialchars($fornecedor['telefone']); ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($fornecedor['whatsapp'])) : ?>
        <div class="info-item">
            <div class="info-label">WhatsApp</div>
            <div class="info-value">
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $fornecedor['whatsapp']); ?>" class="text-success text-decoration-none" target="_blank">
                    <i class="fab fa-whatsapp me-1"></i>
                    <?php echo htmlspecialchars($fornecedor['whatsapp']); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($fornecedor['email'])) : ?>
        <div class="info-item">
            <div class="info-label">E-mail</div>
            <div class="info-value">
                <a href="mailto:<?php echo $fornecedor['email']; ?>" class="text-decoration-none">
                    <i class="fas fa-envelope me-1"></i>
                    <?php echo htmlspecialchars($fornecedor['email']); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
    <div class="info-item">
        <div class="info-label">Prazo de Entrega Padrão</div>
        <div class="info-value"><?php echo $fornecedor['prazo_entrega_padrao']; ?> dias</div>
    </div>
    <?php if (!empty($fornecedor['forma_pagamento_preferida'])) : ?>
        <div class="info-item">
            <div class="info-label">Forma de Pagamento</div>
            <div class="info-value"><?php echo htmlspecialchars($fornecedor['forma_pagamento_preferida']); ?></div>
        </div>
    <?php endif; ?>
    <?php if ($fornecedor['limite_credito'] > 0) : ?>
        <div class="info-item">
            <div class="info-label">Limite de Crédito</div>
            <div class="info-value text-success">R$ <?php echo number_format($fornecedor['limite_credito'], 2, ',', '.'); ?></div>
        </div>
    <?php endif; ?>
    <div class="info-item">
        <div class="info-label">Fornecedor Desde</div>
        <div class="info-value">
            <?php
            echo date('d/m/Y', strtotime($fornecedor['data_cadastro']));
            $dias_fornecedor = floor((time() - strtotime($fornecedor['data_cadastro'])) / (60 * 60 * 24));
            echo " <small class='text-muted'>($dias_fornecedor dias)</small>";
            ?>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card slide-in" style="animation-delay: 0.6s;">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="fas fa-bolt text-warning me-2"></i>
                    Ações Rápidas
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="novo_pedido.php?fornecedor_id=<?php echo $fornecedor_id; ?>" class="action-btn fornecedores">
                        <i class="fas fa-cart-plus me-2"></i> Novo Pedido
                    </a>
                    <?php if (!empty($fornecedor['whatsapp'])) : ?>
                        <a href="https://api.whatsapp.com/send?phone=<?php echo preg_replace('/[^0-9]/', '', $fornecedor['whatsapp']); ?>&text=<?php echo $mensagem_whatsapp_codificada; ?>" class="action-btn whatsapp" target="_blank">
                            <i class="fab fa-whatsapp me-2"></i> Enviar WhatsApp
                        </a>
                    <?php endif; ?>
                    <a href="pedidos.php?fornecedor_id=<?php echo $fornecedor_id; ?>" class="action-btn primary">
                        <i class="fas fa-shopping-cart me-2"></i> Ver Todos os Pedidos
                    </a>
                    <button type="button" class="action-btn success" data-bs-toggle="modal" data-bs-target="#modalComunicacao">
                        <i class="fas fa-comment me-2"></i> Registrar Comunicação
                    </button>
                    <button type="button" class="action-btn warning" data-bs-toggle="modal" data-bs-target="#modalAvaliacao">
                        <i class="fas fa-star me-2"></i> Avaliar Fornecedor
                    </button>
                    <button onclick="gerarRelatorio()" class="action-btn primary">
                        <i class="fas fa-file-pdf me-2"></i> Gerar Relatório
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Últimos Pedidos -->
    <div class="col-lg-8 mb-4">
        <div class="card stats-card slide-in" style="animation-delay: 0.7s;">
            <div class="card-header fornecedores">
                <h5 class="mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Últimos Pedidos
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if ($result_pedidos && $result_pedidos->num_rows > 0) : ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="d-none d-md-table-header-group" style="background: rgba(0,0,0,0.03);">
                                <tr>
                                    <th>Pedido</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th class="text-center">Itens</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pedido = $result_pedidos->fetch_assoc()) : ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold">#<?php echo htmlspecialchars($pedido['numero_pedido'] ?: 'PED-' . str_pad($pedido['id'], 4, '0', STR_PAD_LEFT)); ?></div>
                                            <div class="small text-muted d-md-none"><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></div>
                                        </td>
                                        <td class="d-none d-md-table-cell"><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></td>
                                        <td>
                                            R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
                                            <div class="small text-muted d-md-none"><span class="badge bg-secondary"><?php echo $pedido['total_itens']; ?> Itens</span></div>
                                        </td>
                                        <td class="text-center d-none d-md-table-cell"><span class="badge bg-secondary"><?php echo $pedido['total_itens']; ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusClass($pedido['status']); ?>"><?php echo ucfirst($pedido['status']); ?></span>
                                        </td>
                                        <td>
                                            <a href="pedido_detalhes.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>Nenhum pedido encontrado para este fornecedor.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Informações Laterais -->
    <div class="col-lg-4 mb-4">
        <!-- Contatos -->
        <div class="card stats-card mb-4 slide-in" style="animation-delay: 0.8s;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Contatos
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if ($result_contatos && $result_contatos->num_rows > 0) : ?>
                    <?php while ($contato = $result_contatos->fetch_assoc()) : ?>
                        <div class="contato-card">
                            <div class="contato-avatar" style="background: linear-gradient(135deg, <?php echo stringToColor($contato['nome']); ?>, <?php echo stringToColor($contato['nome'] . 'y'); ?>);">
                                <?php echo strtoupper(substr($contato['nome'], 0, 1)); ?>
                            </div>
                            <div class="contato-info">
                                <div class="fw-bold"><?php echo htmlspecialchars($contato['nome']); ?>
                                    <?php if ($contato['eh_principal']) : ?><span class="badge bg-warning ms-2 small">Principal</span><?php endif; ?>
                                </div>
                                <div class="small text-muted"><?php echo htmlspecialchars($contato['cargo'] ?: ''); ?></div>
                            </div>
                            <div class="contato-actions">
                                <?php if (!empty($contato['telefone'])) : ?><a href="tel:<?php echo preg_replace('/[^0-9]/', '', $contato['telefone']); ?>"><i class="fas fa-phone"></i></a><?php endif; ?>
                                <?php if (!empty($contato['email'])) : ?><a href="mailto:<?php echo $contato['email']; ?>"><i class="fas fa-envelope"></i></a><?php endif; ?>
                                <?php if (!empty($contato['whatsapp'])) : ?><a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contato['whatsapp']); ?>" target="_blank"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>Nenhum contato cadastrado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Últimas Comunicações -->
        <div class="card stats-card slide-in" style="animation-delay: 0.9s;">
            <div class="card-header success">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Últimas Comunicações
                </h5>
            </div>
            <div class="card-body">
                <?php if ($result_comunicacoes && $result_comunicacoes->num_rows > 0) : ?>
                    <ul class="timeline">
                        <?php while ($com = $result_comunicacoes->fetch_assoc()) : ?>
                            <li class="timeline-item <?php echo htmlspecialchars($com['tipo']); ?>">
                                <div class="small text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($com['data_comunicacao'])); ?> 
                                    por <?php echo htmlspecialchars($com['usuario_nome'] ?: 'Sistema'); ?>
                                </div>
                                <p class="mb-0">
                                    <strong><?php echo htmlspecialchars($com['assunto'] ?: 'Comunicação'); ?>:</strong>
                                    <?php 
                                    // Usar mensagem em vez de resumo
                                    $mensagem = $com['mensagem'] ?: '';
                                    // Limitar a 150 caracteres para exibição
                                    if (strlen($mensagem) > 150) {
                                        echo htmlspecialchars(substr($mensagem, 0, 150)) . '...';
                                    } else {
                                        echo htmlspecialchars($mensagem);
                                    }
                                    ?>
                                </p>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else : ?>
                    <div class="empty-state py-2">
                        <i class="fas fa-comments"></i>
                        <p>Nenhum registro de comunicação.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Endereço e Observações -->
<?php if (!empty($fornecedor['endereco']) || !empty($fornecedor['observacoes'])): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card stats-card slide-in" style="animation-delay: 1s;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações Adicionais
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($fornecedor['endereco'])): ?>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            Endereço Completo
                        </h6>
                        <p class="mb-3">
                            <?php echo htmlspecialchars($fornecedor['endereco']); ?>
                            <?php if (!empty($fornecedor['cidade']) || !empty($fornecedor['estado'])): ?>
                                <br>
                                <?php if (!empty($fornecedor['cidade'])): ?>
                                    <?php echo htmlspecialchars($fornecedor['cidade']); ?>
                                <?php endif; ?>
                                <?php if (!empty($fornecedor['estado'])): ?>
                                    - <?php echo htmlspecialchars($fornecedor['estado']); ?>
                                <?php endif; ?>
                                <?php if (!empty($fornecedor['cep'])): ?>
                                    <br>CEP: <?php echo htmlspecialchars($fornecedor['cep']); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($fornecedor['observacoes'])): ?>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">
                            <i class="fas fa-sticky-note me-1"></i>
                            Observações
                        </h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($fornecedor['observacoes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal de Comunicação -->
<div class="modal fade" id="modalComunicacao" tabindex="-1" aria-labelledby="modalComunicacaoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalComunicacaoLabel">Registrar Nova Comunicação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="_registrar_comunicacao.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="fornecedor_id" value="<?php echo $fornecedor_id; ?>">
            <input type="hidden" name="usuario_id" value="<?php echo $_SESSION['usuario_id']; ?>">
            <div class="mb-3">
                <label for="data_comunicacao" class="form-label">Data e Hora</label>
                <input type="datetime-local" class="form-control" id="data_comunicacao" name="data_comunicacao" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
            </div>
            <div class="mb-3">
                <label for="tipo_comunicacao" class="form-label">Tipo</label>
                <select class="form-select" id="tipo_comunicacao" name="tipo_comunicacao" required>
                    <option value="telefone">Telefone</option>
                    <option value="email">E-mail</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="reuniao">Reunião</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="resumo" class="form-label">Resumo da Comunicação</label>
                <textarea class="form-control" id="resumo" name="resumo" rows="4" required></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar Registro</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal de Avaliação -->
<div class="modal fade" id="modalAvaliacao" tabindex="-1" aria-labelledby="modalAvaliacaoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAvaliacaoLabel">Avaliar Fornecedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
       <form action="_avaliar_fornecedor.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="fornecedor_id" value="<?php echo $fornecedor_id; ?>">
            <p>Dê uma nota de 1 a 5 para <strong><?php echo htmlspecialchars($fornecedor['nome']); ?></strong>.</p>
            <div class="mb-3 text-center fs-1" id="ratingStars">
                <i class="far fa-star rating-star" data-value="1"></i>
                <i class="far fa-star rating-star" data-value="2"></i>
                <i class="far fa-star rating-star" data-value="3"></i>
                <i class="far fa-star rating-star" data-value="4"></i>
                <i class="far fa-star rating-star" data-value="5"></i>
            </div>
            <input type="hidden" name="avaliacao" id="avaliacaoInput" required>
            <div class="mb-3">
                <label for="comentario_avaliacao" class="form-label">Comentário (Opcional)</label>
                <textarea class="form-control" name="comentario" id="comentario_avaliacao" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Enviar Avaliação</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    // Script para modal de avaliação
    const stars = document.querySelectorAll('.rating-star');
    const ratingInput = document.getElementById('avaliacaoInput');

    stars.forEach(star => {
        star.addEventListener('mouseover', function() {
            resetStars();
            const value = this.dataset.value;
            for(let i=0; i < value; i++) {
                stars[i].classList.remove('far');
                stars[i].classList.add('fas', 'text-warning');
            }
        });

        star.addEventListener('click', function() {
            const value = this.dataset.value;
            ratingInput.value = value;
        });

        document.getElementById('ratingStars').addEventListener('mouseout', function(){
            resetStars();
            const selectedValue = ratingInput.value;
            if(selectedValue) {
                 for(let i=0; i < selectedValue; i++) {
                    stars[i].classList.remove('far');
                    stars[i].classList.add('fas', 'text-warning');
                }
            }
        });
    });

    function resetStars() {
        stars.forEach(s => {
            s.classList.remove('fas', 'text-warning');
            s.classList.add('far');
        });
    }

    // Função para gerar relatório
    function gerarRelatorio() {
        window.open('gerar_relatorio_fornecedor.php?id=<?php echo $fornecedor_id; ?>', '_blank');
    }

    // Animações de entrada
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.slide-in');
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>

<?php include '../includes/footer.php'; ?>