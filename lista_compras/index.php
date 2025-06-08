<?php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit();
}

// Processar filtros
$filtro_status = $_GET['status'] ?? '';
$filtro_prioridade = $_GET['prioridade'] ?? '';
$filtro_periodo = $_GET['periodo'] ?? '';
$busca = $_GET['busca'] ?? '';
$ordenar = $_GET['ordenar'] ?? 'data_criacao';
$direcao = $_GET['direcao'] ?? 'DESC';

// Paginação
$pagina = (int)($_GET['pagina'] ?? 1);
$por_pagina = 12;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta SQL
$where_conditions = [];
$params = [];

if ($filtro_status) {
    $where_conditions[] = "l.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_prioridade) {
    $where_conditions[] = "l.prioridade = ?";
    $params[] = $filtro_prioridade;
}

if ($busca) {
    $where_conditions[] = "(l.nome LIKE ? OR l.descricao LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

if ($filtro_periodo) {
    switch ($filtro_periodo) {
        case 'hoje':
            $where_conditions[] = "DATE(l.data_criacao) = CURDATE()";
            break;
        case 'semana':
            $where_conditions[] = "l.data_criacao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'mes':
            $where_conditions[] = "l.data_criacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
    }
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Consultar listas com resumo
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
        $where_clause
        GROUP BY l.id
        ORDER BY l.$ordenar $direcao
        LIMIT $por_pagina OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listas = $stmt->fetchAll();

// Contar total para paginação
$sql_count = "SELECT COUNT(DISTINCT l.id) 
              FROM listas_compras l 
              LEFT JOIN itens_lista_compras i ON l.id = i.lista_id
              LEFT JOIN envios_lista_fornecedores e ON l.id = e.lista_id
              $where_clause";

$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// Buscar estatísticas gerais
$sql_stats = "SELECT COUNT(*) as total_listas,
                     COUNT(CASE WHEN status = 'rascunho' THEN 1 END) as rascunhos,
                     COUNT(CASE WHEN status = 'enviada' THEN 1 END) as enviadas,
                     COUNT(CASE WHEN status = 'em_cotacao' THEN 1 END) as em_cotacao,
                     COUNT(CASE WHEN status = 'finalizada' THEN 1 END) as finalizadas,
                     COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as canceladas,
                     COALESCE(SUM(valor_estimado), 0) as valor_total_estimado,
                     COALESCE(SUM(valor_final), 0) as valor_total_final
              FROM listas_compras";
$stmt_stats = $pdo->query($sql_stats);
$stats = $stmt_stats->fetch();

// Função para formatar status
function formatarStatus($status) {
    $badges = [
        'rascunho' => '<span class="badge badge-secondary">Rascunho</span>',
        'enviada' => '<span class="badge badge-info">Enviada</span>',
        'em_cotacao' => '<span class="badge badge-warning">Em Cotação</span>',
        'finalizada' => '<span class="badge badge-success">Finalizada</span>',
        'cancelada' => '<span class="badge badge-danger">Cancelada</span>'
    ];
    return $badges[$status] ?? $status;
}

function formatarPrioridade($prioridade) {
    $badges = [
        'baixa' => '<span class="badge badge-light">Baixa</span>',
        'media' => '<span class="badge badge-primary">Média</span>',
        'alta' => '<span class="badge badge-warning">Alta</span>',
        'urgente' => '<span class="badge badge-danger">Urgente</span>'
    ];
    return $badges[$prioridade] ?? $prioridade;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Compras - Sistema Domaria Café</title>
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

        .card-lista {
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            animation: slideInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .card-lista:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .card-lista:nth-child(1) { animation-delay: 0.1s; }
        .card-lista:nth-child(2) { animation-delay: 0.2s; }
        .card-lista:nth-child(3) { animation-delay: 0.3s; }
        .card-lista:nth-child(4) { animation-delay: 0.4s; }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-stats {
            background: var(--gradient-info);
            color: white;
            border-radius: 15px;
            border: none;
            margin-bottom: 1.5rem;
        }

        .stat-item {
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
            opacity: 0.9;
        }

        .btn-novo {
            background: var(--gradient-compras);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-novo:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }

        .filter-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .progress {
            height: 8px;
            border-radius: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Header da Seção -->
    <div class="header-compras">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-clipboard-list me-3"></i>
                        Listas de Compras
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">
                        Gerencie suas listas de compras e cotações
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="criar.php" class="btn btn-novo">
                        <i class="fas fa-plus me-2"></i>
                        Nova Lista
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $stats['total_listas'] ?></div>
                                    <div class="stat-label">Total de Listas</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $stats['rascunhos'] ?></div>
                                    <div class="stat-label">Rascunhos</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $stats['em_cotacao'] ?></div>
                                    <div class="stat-label">Em Cotação</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $stats['finalizadas'] ?></div>
                                    <div class="stat-label">Finalizadas</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-item">
                                    <div class="stat-number">R$ <?= number_format($stats['valor_total_estimado'], 2, ',', '.') ?></div>
                                    <div class="stat-label">Valor Estimado</div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-item">
                                    <div class="stat-number">R$ <?= number_format($stats['valor_total_final'], 2, ',', '.') ?></div>
                                    <div class="stat-label">Valor Final</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filter-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="busca" value="<?= htmlspecialchars($busca) ?>" 
                           placeholder="Nome ou descrição...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Todos</option>
                        <option value="rascunho" <?= $filtro_status == 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                        <option value="enviada" <?= $filtro_status == 'enviada' ? 'selected' : '' ?>>Enviada</option>
                        <option value="em_cotacao" <?= $filtro_status == 'em_cotacao' ? 'selected' : '' ?>>Em Cotação</option>
                        <option value="finalizada" <?= $filtro_status == 'finalizada' ? 'selected' : '' ?>>Finalizada</option>
                        <option value="cancelada" <?= $filtro_status == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Prioridade</label>
                    <select class="form-select" name="prioridade">
                        <option value="">Todas</option>
                        <option value="baixa" <?= $filtro_prioridade == 'baixa' ? 'selected' : '' ?>>Baixa</option>
                        <option value="media" <?= $filtro_prioridade == 'media' ? 'selected' : '' ?>>Média</option>
                        <option value="alta" <?= $filtro_prioridade == 'alta' ? 'selected' : '' ?>>Alta</option>
                        <option value="urgente" <?= $filtro_prioridade == 'urgente' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Período</label>
                    <select class="form-select" name="periodo">
                        <option value="">Todos</option>
                        <option value="hoje" <?= $filtro_periodo == 'hoje' ? 'selected' : '' ?>>Hoje</option>
                        <option value="semana" <?= $filtro_periodo == 'semana' ? 'selected' : '' ?>>Esta Semana</option>
                        <option value="mes" <?= $filtro_periodo == 'mes' ? 'selected' : '' ?>>Este Mês</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ordenar por</label>
                    <select class="form-select" name="ordenar">
                        <option value="data_criacao" <?= $ordenar == 'data_criacao' ? 'selected' : '' ?>>Data de Criação</option>
                        <option value="nome" <?= $ordenar == 'nome' ? 'selected' : '' ?>>Nome</option>
                        <option value="status" <?= $ordenar == 'status' ? 'selected' : '' ?>>Status</option>
                        <option value="prioridade" <?= $ordenar == 'prioridade' ? 'selected' : '' ?>>Prioridade</option>
                        <option value="valor_estimado" <?= $ordenar == 'valor_estimado' ? 'selected' : '' ?>>Valor Estimado</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Ordem</label>
                    <select class="form-select" name="direcao">
                        <option value="DESC" <?= $direcao == 'DESC' ? 'selected' : '' ?>>↓</option>
                        <option value="ASC" <?= $direcao == 'ASC' ? 'selected' : '' ?>>↑</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Filtrar
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-times me-2"></i>Limpar
                    </a>
                </div>
            </form>
        </div>

        <!-- Lista de Compras -->
        <?php if (empty($listas)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Nenhuma lista encontrada</h3>
                <p>Não há listas de compras que correspondam aos filtros aplicados.</p>
                <a href="criar.php" class="btn btn-novo mt-3">
                    <i class="fas fa-plus me-2"></i>Criar Primeira Lista
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($listas as $index => $lista): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="card card-lista" style="animation-delay: <?= ($index % 12) * 0.1 ?>s">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-truncate me-2">
                                    <i class="fas fa-clipboard-list me-2 text-primary"></i>
                                    <?= htmlspecialchars($lista['nome']) ?>
                                </h6>
                                <?= formatarPrioridade($lista['prioridade']) ?>
                            </div>
                            <div class="card-body">
                                <!-- Status e Data -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <?= formatarStatus($lista['status']) ?>
                                    <small class="text-muted">
                                        <?= date('d/m/Y', strtotime($lista['data_criacao'])) ?>
                                    </small>
                                </div>

                                <!-- Descrição -->
                                <?php if ($lista['descricao']): ?>
                                    <p class="text-muted small mb-3">
                                        <?= htmlspecialchars(substr($lista['descricao'], 0, 100)) ?>
                                        <?= strlen($lista['descricao']) > 100 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Estatísticas dos Itens -->
                                <div class="row text-center mb-3">
                                    <div class="col-3">
                                        <div class="stat-number h6 mb-1 text-primary"><?= $lista['total_itens'] ?></div>
                                        <div class="stat-label small">Itens</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="stat-number h6 mb-1 text-warning"><?= $lista['itens_pendentes'] ?></div>
                                        <div class="stat-label small">Pendentes</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="stat-number h6 mb-1 text-info"><?= $lista['cotacoes_recebidas'] ?></div>
                                        <div class="stat-label small">Cotações</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="stat-number h6 mb-1 text-success"><?= $lista['fornecedores_contatados'] ?></div>
                                        <div class="stat-label small">Fornecedores</div>
                                    </div>
                                </div>

                                <!-- Progresso dos Itens -->
                                <?php if ($lista['total_itens'] > 0): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>Progresso dos Itens</small>
                                            <small><?= round(($lista['itens_comprados'] / $lista['total_itens']) * 100, 1) ?>%</small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: <?= ($lista['itens_comprados'] / $lista['total_itens']) * 100 ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Valores -->
                                <div class="row text-center mt-3">
                                    <div class="col-6">
                                        <strong class="text-primary">R$ <?= number_format($lista['valor_estimado'], 2, ',', '.') ?></strong>
                                        <div class="small text-muted">Estimado</div>
                                    </div>
                                    <div class="col-6">
                                        <strong class="text-success">R$ <?= number_format($lista['valor_final'], 2, ',', '.') ?></strong>
                                        <div class="small text-muted">Final</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="detalhes.php?id=<?= $lista['id'] ?>" class="btn btn-outline-primary btn-sm" title="Ver Detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar.php?id=<?= $lista['id'] ?>" class="btn btn-outline-warning btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="relatorio.php?id=<?= $lista['id'] ?>" class="btn btn-outline-secondary btn-sm" title="Relatório">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <?php if ($lista['status'] == 'rascunho'): ?>
                                        <button class="btn btn-outline-danger btn-sm" onclick="confirmarExclusao(<?= $lista['id'] ?>)" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Paginação" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&<?= http_build_query(array_filter($_GET)) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $inicio = max(1, $pagina - 2);
                        $fim = min($total_paginas, $pagina + 2);
                        
                        for ($i = $inicio; $i <= $fim; $i++): ?>
                            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $i ?>&<?= http_build_query(array_filter($_GET)) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagina < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&<?= http_build_query(array_filter($_GET)) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="text-center text-muted mt-2">
                    Mostrando <?= ($offset + 1) ?> a <?= min($offset + $por_pagina, $total_registros) ?> de <?= $total_registros ?> listas
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalExcluir" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir esta lista de compras?</p>
                    <p class="text-danger"><strong>Esta ação não pode ser desfeita.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnConfirmarExclusao" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Excluir
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para confirmar exclusão
        function confirmarExclusao(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalExcluir'));
            const btnConfirmar = document.getElementById('btnConfirmarExclusao');
            btnConfirmar.href = `excluir.php?id=${id}`;
            modal.show();
        }

        // Mensagens de sessão com auto-dismiss
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