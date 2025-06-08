<?php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit();
}

$lista_id = (int)($_GET['id'] ?? 0);

if (!$lista_id) {
    header('Location: index.php');
    exit();
}

// Buscar dados da lista
$sql = "SELECT l.*, 
               COUNT(i.id) as total_itens,
               COUNT(CASE WHEN i.status_item = 'pendente' THEN 1 END) as itens_pendentes,
               COUNT(CASE WHEN i.status_item = 'cotado' THEN 1 END) as itens_cotados,
               COUNT(CASE WHEN i.status_item = 'aprovado' THEN 1 END) as itens_aprovados,
               COUNT(CASE WHEN i.status_item = 'comprado' THEN 1 END) as itens_comprados,
               COUNT(DISTINCT e.fornecedor_id) as fornecedores_contatados,
               COUNT(CASE WHEN e.status_resposta = 'cotacao_recebida' THEN 1 END) as cotacoes_recebidas
        FROM listas_compras l
        LEFT JOIN itens_lista_compras i ON l.id = i.lista_id
        LEFT JOIN envios_lista_fornecedores e ON l.id = e.lista_id
        WHERE l.id = ?
        GROUP BY l.id";

$stmt = $pdo->prepare($sql);
$stmt->execute([$lista_id]);
$lista = $stmt->fetch();

if (!$lista) {
    $_SESSION['mensagem'] = 'Lista de compras não encontrada.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: index.php');
    exit();
}

// Buscar itens da lista
$sql_itens = "SELECT i.*,
                     fs.nome as fornecedor_sugerido_nome,
                     fe.nome as fornecedor_escolhido_nome
              FROM itens_lista_compras i
              LEFT JOIN fornecedores fs ON i.fornecedor_sugerido_id = fs.id
              LEFT JOIN fornecedores fe ON i.fornecedor_escolhido_id = fe.id
              WHERE i.lista_id = ?
              ORDER BY i.ordem, i.id";

$stmt_itens = $pdo->prepare($sql_itens);
$stmt_itens->execute([$lista_id]);
$itens = $stmt_itens->fetchAll();

// Buscar envios para fornecedores
$sql_envios = "SELECT e.*, f.nome as fornecedor_nome, f.telefone, f.whatsapp, f.email
               FROM envios_lista_fornecedores e
               JOIN fornecedores f ON e.fornecedor_id = f.id
               WHERE e.lista_id = ?
               ORDER BY e.data_envio DESC";

$stmt_envios = $pdo->prepare($sql_envios);
$stmt_envios->execute([$lista_id]);
$envios = $stmt_envios->fetchAll();

// Buscar histórico
$sql_historico = "SELECT h.*
                  FROM historico_listas_compras h
                  WHERE h.lista_id = ?
                  ORDER BY h.data_acao DESC
                  LIMIT 10";

$stmt_historico = $pdo->prepare($sql_historico);
$stmt_historico->execute([$lista_id]);
$historico = $stmt_historico->fetchAll();

// Buscar fornecedores ativos para envio
$sql_fornecedores = "SELECT id, nome, whatsapp, email, telefone 
                     FROM fornecedores 
                     WHERE status = 'ativo' 
                     ORDER BY nome";
$stmt_fornecedores = $pdo->query($sql_fornecedores);
$fornecedores = $stmt_fornecedores->fetchAll();

// Função para formatar status
function formatarStatus($status) {
    $badges = [
        'rascunho' => '<span class="badge bg-secondary">Rascunho</span>',
        'enviada' => '<span class="badge bg-info">Enviada</span>',
        'em_cotacao' => '<span class="badge bg-warning">Em Cotação</span>',
        'finalizada' => '<span class="badge bg-success">Finalizada</span>',
        'cancelada' => '<span class="badge bg-danger">Cancelada</span>'
    ];
    return $badges[$status] ?? $status;
}

function formatarPrioridade($prioridade) {
    $badges = [
        'baixa' => '<span class="badge bg-light text-dark">Baixa</span>',
        'media' => '<span class="badge bg-primary">Média</span>',
        'alta' => '<span class="badge bg-warning">Alta</span>',
        'urgente' => '<span class="badge bg-danger">Urgente</span>'
    ];
    return $badges[$prioridade] ?? $prioridade;
}

function formatarStatusItem($status) {
    $badges = [
        'pendente' => '<span class="badge bg-secondary">Pendente</span>',
        'cotado' => '<span class="badge bg-info">Cotado</span>',
        'aprovado' => '<span class="badge bg-warning">Aprovado</span>',
        'comprado' => '<span class="badge bg-success">Comprado</span>'
    ];
    return $badges[$status] ?? $status;
}

function formatarStatusEnvio($status) {
    $badges = [
        'enviado' => '<span class="badge bg-info">Enviado</span>',
        'visualizado' => '<span class="badge bg-primary">Visualizado</span>',
        'respondido' => '<span class="badge bg-warning">Respondido</span>',
        'cotacao_recebida' => '<span class="badge bg-success">Cotação Recebida</span>',
        'sem_resposta' => '<span class="badge bg-danger">Sem Resposta</span>'
    ];
    return $badges[$status] ?? $status;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lista['nome']) ?> - Lista de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --gradient-compras: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-danger: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
            --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
        }

        .header-compras {
            background: var(--gradient-compras);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .header-compras::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .header-compras .container {
            position: relative;
            z-index: 1;
        }

        .card-section {
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow-soft);
            margin-bottom: 2rem;
            animation: slideInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .card-section:nth-child(1) { animation-delay: 0.1s; }
        .card-section:nth-child(2) { animation-delay: 0.2s; }
        .card-section:nth-child(3) { animation-delay: 0.3s; }
        .card-section:nth-child(4) { animation-delay: 0.4s; }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-section .card-header {
            background: var(--gradient-compras);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }

        .item-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .item-card:hover {
            background: #e3f2fd;
            border-color: #2196F3;
            transform: translateY(-2px);
        }

        .item-status {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.4rem;
            top: 0.2rem;
            width: 0.8rem;
            height: 0.8rem;
            border-radius: 50%;
            background: #764ba2;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #dee2e6;
        }

        .stats-row {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            text-align: center;
            padding: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(#764ba2 var(--progress, 0%), #e9ecef 0%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .progress-circle::before {
            content: '';
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            position: absolute;
        }

        .progress-circle .progress-text {
            position: relative;
            z-index: 1;
            font-weight: bold;
            font-size: 0.8rem;
            color: #764ba2;
        }

        .floating-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            z-index: 1000;
        }

        .floating-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            color: white;
        }

        .floating-btn.btn-primary {
            background: var(--gradient-compras);
        }

        .floating-btn.btn-success {
            background: var(--gradient-success);
        }

        .floating-btn.btn-warning {
            background: var(--gradient-warning);
        }

        .floating-btn.btn-info {
            background: var(--gradient-info);
        }

        .whatsapp-btn {
            background: #25d366;
            color: white;
            border: none;
        }

        .whatsapp-btn:hover {
            background: #20b858;
            color: white;
        }

        .email-btn {
            background: #ea4335;
            color: white;
            border: none;
        }

        .email-btn:hover {
            background: #d33b2c;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Header da Lista -->
    <div class="header-compras">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-2">
                        <h1 class="mb-0 me-3">
                            <i class="fas fa-clipboard-list me-2"></i>
                            <?= htmlspecialchars($lista['nome']) ?>
                        </h1>
                        <?= formatarStatus($lista['status']) ?>
                        <?= formatarPrioridade($lista['prioridade']) ?>
                    </div>
                    
                    <?php if ($lista['descricao']): ?>
                        <p class="mb-2 opacity-75">
                            <?= htmlspecialchars($lista['descricao']) ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="d-flex flex-wrap gap-3 small opacity-75">
                        <span>
                            <i class="fas fa-calendar me-1"></i>
                            Criada em <?= date('d/m/Y', strtotime($lista['data_criacao'])) ?>
                        </span>
                        <?php if ($lista['data_prazo']): ?>
                            <span>
                                <i class="fas fa-clock me-1"></i>
                                Prazo: <?= date('d/m/Y', strtotime($lista['data_prazo'])) ?>
                            </span>
                        <?php endif; ?>
                        <span>
                            <i class="fas fa-list me-1"></i>
                            <?= $lista['total_itens'] ?> itens
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                        <a href="index.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Voltar
                        </a>
                        <?php if ($lista['status'] == 'rascunho'): ?>
                            <button class="btn btn-light btn-sm" onclick="modalEnviarFornecedores()">
                                <i class="fas fa-paper-plane me-1"></i>Enviar
                            </button>
                        <?php endif; ?>
                        <a href="relatorio.php?id=<?= $lista['id'] ?>" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-file-pdf me-1"></i>Relatório
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Estatísticas Gerais -->
        <div class="stats-row">
            <div class="row">
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number text-primary"><?= $lista['total_itens'] ?></div>
                        <div class="stat-label">Total de Itens</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number text-warning"><?= $lista['itens_pendentes'] ?></div>
                        <div class="stat-label">Pendentes</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number text-info"><?= $lista['itens_cotados'] ?></div>
                        <div class="stat-label">Cotados</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number text-success"><?= $lista['itens_comprados'] ?></div>
                        <div class="stat-label">Comprados</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number text-primary">R$ <?= number_format($lista['valor_estimado'], 2, ',', '.') ?></div>
                        <div class="stat-label">Valor Estimado</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="progress-circle" style="--progress: <?= $lista['total_itens'] > 0 ? ($lista['itens_comprados'] / $lista['total_itens']) * 360 : 0 ?>deg;">
                            <div class="progress-text">
                                <?= $lista['total_itens'] > 0 ? round(($lista['itens_comprados'] / $lista['total_itens']) * 100) : 0 ?>%
                            </div>
                        </div>
                        <div class="stat-label mt-2">Progresso</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Itens da Lista -->
        <div class="card card-section">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Itens da Lista
                </h5>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-1"></i>Filtrar
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="filtrarItens('todos')">Todos os Itens</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filtrarItens('pendente')">Pendentes</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filtrarItens('cotado')">Cotados</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filtrarItens('aprovado')">Aprovados</a></li>
                            <li><a class="dropdown-item" href="#" onclick="filtrarItens('comprado')">Comprados</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($itens)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum item adicionado</h5>
                        <p class="text-muted">Comece adicionando itens à sua lista de compras.</p>
                    </div>
                <?php else: ?>
                    <div id="itens-container">
                        <?php foreach ($itens as $item): ?>
                            <div class="item-card" data-status="<?= $item['status_item'] ?>">
                                <div class="item-status">
                                    <?= formatarStatusItem($item['status_item']) ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="mb-2">
                                            <i class="fas fa-cube me-2 text-primary"></i>
                                            <?= htmlspecialchars($item['produto_descricao']) ?>
                                        </h6>
                                        
                                        <?php if ($item['categoria']): ?>
                                            <span class="badge bg-light text-dark me-2">
                                                <?= htmlspecialchars($item['categoria']) ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="text-muted">
                                            <?= number_format($item['quantidade'], 2, ',', '.') ?> <?= htmlspecialchars($item['unidade']) ?>
                                        </span>
                                        
                                        <?php if ($item['observacoes']): ?>
                                            <p class="small text-muted mt-2 mb-0">
                                                <i class="fas fa-comment me-1"></i>
                                                <?= htmlspecialchars($item['observacoes']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="h6 mb-1 text-primary">
                                                R$ <?= number_format($item['preco_estimado'], 2, ',', '.') ?>
                                            </div>
                                            <div class="small text-muted">Preço Estimado</div>
                                            
                                            <div class="h6 mb-1 text-success mt-2">
                                                R$ <?= number_format($item['quantidade'] * $item['preco_estimado'], 2, ',', '.') ?>
                                            </div>
                                            <div class="small text-muted">Total</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="d-flex flex-column gap-1">
                                            <button class="btn btn-outline-warning btn-sm" onclick="alterarStatusItem(<?= $item['id'] ?>)">
                                                <i class="fas fa-edit me-1"></i>Alterar Status
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Envios para Fornecedores -->
        <?php if (!empty($envios)): ?>
            <div class="card card-section">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-paper-plane me-2"></i>
                        Envios para Fornecedores
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fornecedor</th>
                                    <th>Data Envio</th>
                                    <th>Meio</th>
                                    <th>Status</th>
                                    <th>Prazo</th>
                                    <th>Valor Cotação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($envios as $envio): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($envio['fornecedor_nome']) ?></strong>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($envio['data_envio'])) ?></td>
                                        <td>
                                            <?php
                                            $meios = [
                                                'whatsapp' => '<i class="fab fa-whatsapp text-success"></i> WhatsApp',
                                                'email' => '<i class="fas fa-envelope text-primary"></i> Email',
                                                'telefone' => '<i class="fas fa-phone text-info"></i> Telefone',
                                                'presencial' => '<i class="fas fa-user text-warning"></i> Presencial'
                                            ];
                                            echo $meios[$envio['meio_envio']] ?? $envio['meio_envio'];
                                            ?>
                                        </td>
                                        <td><?= formatarStatusEnvio($envio['status_resposta']) ?></td>
                                        <td>
                                            <?= $envio['prazo_resposta'] ? date('d/m/Y', strtotime($envio['prazo_resposta'])) : '-' ?>
                                        </td>
                                        <td>
                                            <?= $envio['valor_cotacao'] > 0 ? 'R$ ' . number_format($envio['valor_cotacao'], 2, ',', '.') : '-' ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <?php if ($envio['whatsapp']): ?>
                                                    <button class="btn btn-success btn-sm whatsapp-btn" onclick="enviarWhatsApp('<?= $envio['whatsapp'] ?>')">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($envio['email']): ?>
                                                    <button class="btn btn-danger btn-sm email-btn" onclick="enviarEmail('<?= $envio['email'] ?>')">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-primary btn-sm" onclick="atualizarEnvio(<?= $envio['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Histórico -->
        <?php if (!empty($historico)): ?>
            <div class="card card-section">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Histórico de Alterações
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($historico as $h): ?>
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= htmlspecialchars($h['acao']) ?></strong>
                                        <?php if ($h['descricao']): ?>
                                            <p class="mb-1 text-muted"><?= htmlspecialchars($h['descricao']) ?></p>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($h['data_acao'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Ações Flutuantes -->
    <div class="floating-actions">
        <?php if ($lista['status'] == 'rascunho'): ?>
            <button class="floating-btn btn-info" onclick="modalEnviarFornecedores()" title="Enviar para Fornecedores">
                <i class="fas fa-paper-plane"></i>
            </button>
        <?php endif; ?>
        <button class="floating-btn btn-primary" onclick="window.location.href='relatorio.php?id=<?= $lista['id'] ?>'" title="Gerar Relatório">
            <i class="fas fa-file-pdf"></i>
        </button>
    </div>

    <!-- Modal Enviar para Fornecedores -->
    <div class="modal fade" id="modalEnviarFornecedores" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane me-2"></i>
                        Enviar Lista para Fornecedores
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEnviarFornecedores">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Selecionar Fornecedores</label>
                                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem;">
                                    <?php foreach ($fornecedores as $fornecedor): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="fornecedores[]" 
                                                   value="<?= $fornecedor['id'] ?>" id="forn_<?= $fornecedor['id'] ?>">
                                            <label class="form-check-label" for="forn_<?= $fornecedor['id'] ?>">
                                                <?= htmlspecialchars($fornecedor['nome']) ?>
                                                <small class="text-muted d-block">
                                                    <?php if ($fornecedor['whatsapp']): ?>
                                                        <i class="fab fa-whatsapp text-success"></i>
                                                    <?php endif; ?>
                                                    <?php if ($fornecedor['email']): ?>
                                                        <i class="fas fa-envelope text-primary"></i>
                                                    <?php endif; ?>
                                                    <?php if ($fornecedor['telefone']): ?>
                                                        <i class="fas fa-phone text-info"></i>
                                                    <?php endif; ?>
                                                </small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Meio de Envio</label>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="meio_envio" value="whatsapp" id="meio_whatsapp" checked>
                                        <label class="form-check-label" for="meio_whatsapp">
                                            <i class="fab fa-whatsapp text-success me-2"></i>WhatsApp
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="meio_envio" value="email" id="meio_email">
                                        <label class="form-check-label" for="meio_email">
                                            <i class="fas fa-envelope text-primary me-2"></i>Email
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="meio_envio" value="telefone" id="meio_telefone">
                                        <label class="form-check-label" for="meio_telefone">
                                            <i class="fas fa-phone text-info me-2"></i>Telefone
                                        </label>
                                    </div>
                                </div>
                                
                                <label class="form-label">Prazo para Resposta</label>
                                <input type="date" class="form-control mb-3" name="prazo_resposta" min="<?= date('Y-m-d') ?>">
                                
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="observacoes_envio" rows="3" 
                                          placeholder="Observações adicionais para os fornecedores..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="enviarParaFornecedores()">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Lista
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal enviar para fornecedores
        function modalEnviarFornecedores() {
            const modal = new bootstrap.Modal(document.getElementById('modalEnviarFornecedores'));
            modal.show();
        }

        // Enviar para fornecedores
        function enviarParaFornecedores() {
            const form = document.getElementById('formEnviarFornecedores');
            const formData = new FormData(form);
            formData.append('lista_id', <?= $lista['id'] ?>);
            
            const fornecedoresSelecionados = formData.getAll('fornecedores[]');
            if (fornecedoresSelecionados.length === 0) {
                alert('Selecione pelo menos um fornecedor.');
                return;
            }
            
            fetch('_enviar_fornecedores.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao enviar lista para fornecedores.');
            });
        }

        // Filtrar itens
        function filtrarItens(status) {
            const itens = document.querySelectorAll('.item-card');
            itens.forEach(item => {
                if (status === 'todos' || item.dataset.status === status) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Enviar WhatsApp
        function enviarWhatsApp(numero) {
            const mensagem = `Olá! Segue nossa lista de compras: ${window.location.href}`;
            const url = `https://wa.me/55${numero.replace(/\D/g, '')}?text=${encodeURIComponent(mensagem)}`;
            window.open(url, '_blank');
        }

        // Enviar Email
        function enviarEmail(email) {
            const assunto = `Lista de Compras - ${<?= json_encode($lista['nome']) ?>}`;
            const corpo = `Olá!\n\nSegue nossa lista de compras para cotação:\n\n${window.location.href}\n\nAguardamos seu retorno.\n\nObrigado!`;
            const url = `mailto:${email}?subject=${encodeURIComponent(assunto)}&body=${encodeURIComponent(corpo)}`;
            window.location.href = url;
        }

        // Alterar status do item
        function alterarStatusItem(itemId) {
            const novoStatus = prompt('Novo status (pendente, cotado, aprovado, comprado):');
            if (!novoStatus) return;
            
            const statusValidos = ['pendente', 'cotado', 'aprovado', 'comprado'];
            if (!statusValidos.includes(novoStatus)) {
                alert('Status inválido. Use: pendente, cotado, aprovado ou comprado');
                return;
            }
            
            fetch('_alterar_status_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId,
                    status: novoStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao alterar status do item.');
            });
        }

        // Atualizar envio
        function atualizarEnvio(envioId) {
            const novoStatus = prompt('Novo status (enviado, visualizado, respondido, cotacao_recebida, sem_resposta):');
            if (!novoStatus) return;
            
            fetch('_atualizar_envio.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    envio_id: envioId,
                    status: novoStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar envio.');
            });
        }

        // Mensagens de sessão
        <?php if (isset($_SESSION['mensagem'])): ?>
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-<?= $_SESSION['tipo_mensagem'] ?? 'info' ?> alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            alertDiv.innerHTML = `
                <?= $_SESSION['mensagem'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
            
            <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
        <?php endif; ?>
    </script>
</body>
</html>