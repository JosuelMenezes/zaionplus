<?php
// contas/index.php
// Lista principal de contas - Sistema Domaria Café

require_once 'config.php';

$page_title = "Contas";
$page_description = "Gestão de contas a pagar e receber";

// Capturar filtros
$filtros = [
    'tipo' => $_GET['tipo'] ?? '',
    'status' => $_GET['status'] ?? '',
    'categoria_id' => $_GET['categoria_id'] ?? '',
    'vencimento_inicio' => $_GET['vencimento_inicio'] ?? '',
    'vencimento_fim' => $_GET['vencimento_fim'] ?? '',
    'busca' => $_GET['busca'] ?? ''
];

// Remover filtros vazios
$filtros = array_filter($filtros, function($value) {
    return $value !== '';
});

// Paginação
$pagina_atual = max(1, intval($_GET['pagina'] ?? 1));

// Buscar categorias para filtros
$categorias = $contasManager->buscarCategorias();

// Obter resumo
$resumo = $contasManager->obterResumo();

// Processar resumo para exibição
$resumo_processado = [
    'pagar' => ['total' => 0, 'pago' => 0, 'pendente' => 0, 'vencido' => 0],
    'receber' => ['total' => 0, 'pago' => 0, 'pendente' => 0, 'vencido' => 0]
];

foreach ($resumo as $item) {
    $tipo = $item['tipo'];
    if (!isset($resumo_processado[$tipo])) continue;
    
    $resumo_processado[$tipo]['total'] += $item['valor_total'];
    
    if ($item['status'] == 'pago') {
        $resumo_processado[$tipo]['pago'] += $item['valor_total'];
    } elseif (in_array($item['status'], ['pendente', 'pago_parcial'])) {
        $resumo_processado[$tipo]['pendente'] += $item['valor_pendente_total'];
    } elseif ($item['status'] == 'vencido') {
        $resumo_processado[$tipo]['vencido'] += $item['valor_pendente_total'];
    }
}

// =================================================================================
// <<< CORREÇÃO CRÍTICA AQUI >>>
// Este bloco busca as contas e define as variáveis de paginação ANTES do HTML.
// Isso garante que $total_paginas exista quando a paginação for renderizada.
// =================================================================================
$contas = $contasManager->buscarContas($filtros, $pagina_atual);
$total_contas = $contasManager->contarContas($filtros);
$total_paginas = ceil($total_contas / CONTAS_ITEMS_PER_PAGE);


require_once('../includes/header.php');
?>

<style>
/* Gradiente específico para o módulo Contas */
:root {
    --gradient-contas: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-pagar: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
    --gradient-receber: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.card-contas {
    background: var(--gradient-contas);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
}

.card-contas:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.card-pagar {
    background: var(--gradient-pagar);
}

.card-receber {
    background: var(--gradient-receber);
}

.conta-item {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.conta-item:hover {
    transform: translateX(5px);
    box-shadow: var(--shadow-hover);
}

.conta-item.vencida {
    border-left-color: #dc3545;
    background: linear-gradient(90deg, rgba(220, 53, 69, 0.05) 0%, white 10%);
}

.conta-item.pago {
    border-left-color: #28a745;
    background: linear-gradient(90deg, rgba(40, 167, 69, 0.05) 0%, white 10%);
}

.conta-item.pendente {
    border-left-color: #ffc107;
    background: linear-gradient(90deg, rgba(255, 193, 7, 0.05) 0%, white 10%);
}

.filtros-avancados {
    background: rgba(102, 126, 234, 0.05);
    border-radius: var(--border-radius);
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.btn-filtro {
    background: var(--gradient-contas);
    border: none;
    border-radius: 8px;
    color: white;
    transition: all 0.3s ease;
}

.btn-filtro:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.valor-destaque {
    font-size: 1.25rem;
    font-weight: 700;
}

.categoria-badge {
    border-radius: 15px;
    padding: 4px 12px;
    font-size: 0.8rem;
    color: white;
    font-weight: 500;
}

.fade-in {
    animation: fadeIn 0.5s ease-in;
}

.fade-in-delay {
    animation: fadeIn 0.5s ease-in 0.2s both;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.actions-float {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
}

.actions-float .btn {
    border-radius: 50%;
    width: 60px;
    height: 60px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.actions-float .btn:hover {
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .actions-float {
        bottom: 20px;
        right: 20px;
    }
    
    .actions-float .btn {
        width: 50px;
        height: 50px;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <h1 class="h3 mb-1">
            <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
            Contas a Pagar e Receber
        </h1>
        <p class="text-muted mb-0">Gestão completa do financeiro</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#filtrosAvancados">
            <i class="fas fa-filter me-2"></i>Filtros
        </button>
        <a href="criar.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nova Conta
        </a>
    </div>
</div>

<div class="row mb-4 fade-in">
    <div class="col-md-6 mb-3">
        <div class="card card-contas card-pagar h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="card-title mb-1">
                            <i class="fas fa-credit-card me-2"></i>Contas a Pagar
                        </h5>
                        <small class="opacity-75">Despesas e obrigações</small>
                    </div>
                    <i class="fas fa-arrow-down fs-2 opacity-50"></i>
                </div>
                
                <div class="row text-center">
                    <div class="col-4">
                        <div class="valor-destaque"><?php echo formatarMoeda($resumo_processado['pagar']['total']); ?></div>
                        <small class="opacity-75">Total</small>
                    </div>
                    <div class="col-4">
                        <div class="valor-destaque"><?php echo formatarMoeda($resumo_processado['pagar']['pendente']); ?></div>
                        <small class="opacity-75">Pendente</small>
                    </div>
                    <div class="col-4">
                        <div class="valor-destaque"><?php echo formatarMoeda($resumo_processado['pagar']['vencido']); ?></div>
                        <small class="opacity-75">Vencido</small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="?tipo=pagar" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-eye me-1"></i>Ver Todas
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card card-contas card-receber h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="card-title mb-1">
                            <i class="fas fa-money-bill-wave me-2"></i>Contas a Receber
                        </h5>
                        <small class="opacity-75">Receitas e direitos</small>
                    </div>
                    <i class="fas fa-arrow-up fs-2 opacity-50"></i>
                </div>
                
                <div class="row text-center">
                    <div class="col-4">
                        <div class="valor-destaque"><?php echo formatarMoeda($resumo_processado['receber']['total']); ?></div>
                        <small class="opacity-75">Total</small>
                    </div>
                    <div class="col-4">
                        <div class="valor-destaque"><?php echo formatarMoeda($resumo_processado['receber']['pendente']); ?></div>
                        <small class="opacity-75">Pendente</small>
                    </div>
                    <div class="col-4">
                        <div class="valor-destaque"><?php echo formatarMoeda($resumo_processado['receber']['vencido']); ?></div>
                        <small class="opacity-75">Vencido</small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="?tipo=receber" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-eye me-1"></i>Ver Todas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="collapse fade-in-delay" id="filtrosAvancados">
    <div class="filtros-avancados p-4 mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="pagar" <?php echo ($filtros['tipo'] ?? '') == 'pagar' ? 'selected' : ''; ?>>A Pagar</option>
                    <option value="receber" <?php echo ($filtros['tipo'] ?? '') == 'receber' ? 'selected' : ''; ?>>A Receber</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (CONTAS_STATUS as $key => $config): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($filtros['status'] ?? '') == $key ? 'selected' : ''; ?>>
                            <?php echo $config['label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Categoria</label>
                <select name="categoria_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>" <?php echo ($filtros['categoria_id'] ?? '') == $categoria['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Vencimento (De)</label>
                <input type="date" name="vencimento_inicio" class="form-control" 
                       value="<?php echo htmlspecialchars($filtros['vencimento_inicio'] ?? ''); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Vencimento (Até)</label>
                <input type="date" name="vencimento_fim" class="form-control" 
                       value="<?php echo htmlspecialchars($filtros['vencimento_fim'] ?? ''); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Buscar</label>
                <input type="text" name="busca" class="form-control" 
                       placeholder="Descrição, documento..." 
                       value="<?php echo htmlspecialchars($filtros['busca'] ?? ''); ?>">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-filtro me-2">
                    <i class="fas fa-search me-2"></i>Filtrar
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card fade-in-delay">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2 text-primary"></i>
                Lista de Contas
                <?php if (!empty($filtros)): ?>
                    <span class="badge bg-primary ms-2"><?php echo $total_contas; ?> encontrada(s)</span>
                <?php endif; ?>
            </h5>
            
            <div class="d-flex gap-2">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm active" id="btnVisaoLista">
                        <i class="fas fa-list"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnVisaoCards">
                        <i class="fas fa-th"></i>
                    </button>
                </div>
                
                <a href="relatorio.php<?php echo !empty($filtros) ? '?' . http_build_query($filtros) : ''; ?>" 
                   class="btn btn-outline-success btn-sm" target="_blank">
                    <i class="fas fa-file-pdf me-1"></i>Relatório
                </a>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($contas)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">Nenhuma conta encontrada</h5>
                <p class="text-muted">
                    <?php if (!empty($filtros)): ?>
                        Tente ajustar os filtros ou <a href="index.php">limpar a busca</a>.
                    <?php else: ?>
                        Comece <a href="criar.php">cadastrando uma nova conta</a>.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div id="visaoLista">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">Tipo</th>
                                <th width="25%">Descrição</th>
                                <th width="15%">Categoria</th>
                                <th width="10%">Valor</th>
                                <th width="10%">Vencimento</th>
                                <th width="10%">Status</th>
                                <th width="15%">Cliente/Fornecedor</th>
                                <th width="10%">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contas as $conta): ?>
                                <?php 
                                $statusBadge = getBadgeStatus($conta['status']);
                                $dias_vencido = $conta['dias_vencido'];
                                ?>
                                <tr class="conta-row" data-tipo="<?php echo $conta['tipo']; ?>" data-status="<?php echo $conta['status']; ?>">
                                    <td>
                                        <?php if ($conta['tipo'] == 'pagar'): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-arrow-down me-1"></i>Pagar
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-arrow-up me-1"></i>Receber
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($conta['descricao']); ?></strong>
                                            <?php if ($conta['documento']): ?>
                                                <br><small class="text-muted">Doc: <?php echo htmlspecialchars($conta['documento']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($dias_vencido > 0): ?>
                                                <br><small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    <?php echo $dias_vencido; ?> dia(s) em atraso
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php if ($conta['categoria_nome']): ?>
                                            <span class="categoria-badge" style="background-color: <?php echo $conta['categoria_cor']; ?>">
                                                <i class="<?php echo $conta['categoria_icone']; ?> me-1"></i>
                                                <?php echo htmlspecialchars($conta['categoria_nome']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sem categoria</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div>
                                            <strong><?php echo formatarMoeda($conta['valor_original']); ?></strong>
                                            <?php if ($conta['valor_pago'] > 0): ?>
                                                <br><small class="text-success">
                                                    Pago: <?php echo formatarMoeda($conta['valor_pago']); ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if ($conta['valor_pendente'] > 0): ?>
                                                <br><small class="text-warning">
                                                    Pendente: <?php echo formatarMoeda($conta['valor_pendente']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="<?php echo $dias_vencido > 0 ? 'text-danger fw-bold' : ''; ?>">
                                            <?php echo formatarData($conta['data_vencimento']); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <span class="<?php echo $statusBadge['class']; ?>">
                                            <i class="fas fa-<?php echo $statusBadge['icon']; ?> me-1"></i>
                                            <?php echo $statusBadge['label']; ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <?php if ($conta['cliente_nome']): ?>
                                            <small class="text-muted">Cliente:</small><br>
                                            <?php echo htmlspecialchars($conta['cliente_nome']); ?>
                                        <?php elseif ($conta['fornecedor_nome']): ?>
                                            <small class="text-muted">Fornecedor:</small><br>
                                            <?php echo htmlspecialchars($conta['fornecedor_nome']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="detalhes.php?id=<?php echo $conta['id']; ?>">
                                                        <i class="fas fa-eye me-2"></i>Detalhes
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="editar.php?id=<?php echo $conta['id']; ?>">
                                                        <i class="fas fa-edit me-2"></i>Editar
                                                    </a>
                                                </li>
                                                <?php if (in_array($conta['status'], ['pendente', 'pago_parcial'])): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="registrar_pagamento.php?id=<?php echo $conta['id']; ?>">
                                                            <i class="fas fa-credit-card me-2"></i>Registrar Pagamento
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" 
                                                       onclick="confirmarExclusao(<?php echo $conta['id']; ?>, '<?php echo addslashes($conta['descricao']); ?>')">
                                                        <i class="fas fa-trash me-2"></i>Excluir
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="visaoCards" style="display: none;">
                <div class="row p-3">
                    <?php foreach ($contas as $index => $conta): ?>
                        <?php 
                        $statusBadge = getBadgeStatus($conta['status']);
                        $dias_vencido = $conta['dias_vencido'];
                        $delay = $index * 0.1;
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="conta-item p-3 h-100 <?php echo $conta['status']; ?>" 
                                 style="animation-delay: <?php echo $delay; ?>s">
                                
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($conta['descricao']); ?></h6>
                                        <?php if ($conta['categoria_nome']): ?>
                                            <span class="categoria-badge" style="background-color: <?php echo $conta['categoria_cor']; ?>">
                                                <i class="<?php echo $conta['categoria_icone']; ?> me-1"></i>
                                                <?php echo htmlspecialchars($conta['categoria_nome']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($conta['tipo'] == 'pagar'): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-arrow-down me-1"></i>Pagar
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-arrow-up me-1"></i>Receber
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted">Valor:</small>
                                        <div class="fw-bold"><?php echo formatarMoeda($conta['valor_original']); ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Vencimento:</small>
                                        <div class="<?php echo $dias_vencido > 0 ? 'text-danger fw-bold' : ''; ?>">
                                            <?php echo formatarData($conta['data_vencimento']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($dias_vencido > 0): ?>
                                    <div class="alert alert-danger py-1 px-2 mb-2">
                                        <small>
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?php echo $dias_vencido; ?> dia(s) em atraso
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="<?php echo $statusBadge['class']; ?>">
                                        <i class="fas fa-<?php echo $statusBadge['icon']; ?> me-1"></i>
                                        <?php echo $statusBadge['label']; ?>
                                    </span>
                                    
                                    <a href="detalhes.php?id=<?php echo $conta['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_paginas > 1): ?>
        <div class="card-footer bg-light">
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php if ($pagina_atual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $pagina_atual - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $pagina_atual - 2); $i <= min($total_paginas, $pagina_atual + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagina_atual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $pagina_atual + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="text-center mt-2">
                <small class="text-muted">
                    Página <?php echo $pagina_atual; ?> de <?php echo $total_paginas; ?> 
                    (<?php echo $total_contas; ?> conta(s) no total)
                </small>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="actions-float">
    <a href="relatorio.php<?php echo !empty($filtros) ? '?' . http_build_query($filtros) : ''; ?>" 
       class="btn btn-success" title="Gerar Relatório" target="_blank">
        <i class="fas fa-file-pdf"></i>
    </a>
    <a href="criar.php" class="btn btn-primary" title="Nova Conta">
        <i class="fas fa-plus"></i>
    </a>
</div>

<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-warning fs-1 mb-3"></i>
                    <h5>Tem certeza que deseja excluir esta conta?</h5>
                    <p class="text-muted mb-0" id="contaDescricao"></p>
                    <small class="text-danger">Esta ação não pode ser desfeita.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formExcluir" method="POST" action="excluir.php" class="d-inline">
                    <input type="hidden" name="id" id="contaIdExcluir">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Alternar entre visão lista e cards
    const btnVisaoLista = document.getElementById('btnVisaoLista');
    const btnVisaoCards = document.getElementById('btnVisaoCards');
    const visaoLista = document.getElementById('visaoLista');
    const visaoCards = document.getElementById('visaoCards');
    
    btnVisaoLista.addEventListener('click', function() {
        visaoLista.style.display = 'block';
        visaoCards.style.display = 'none';
        btnVisaoLista.classList.add('active');
        btnVisaoCards.classList.remove('active');
        localStorage.setItem('contas_visao', 'lista');
    });
    
    btnVisaoCards.addEventListener('click', function() {
        visaoLista.style.display = 'none';
        visaoCards.style.display = 'block';
        btnVisaoCards.classList.add('active');
        btnVisaoLista.classList.remove('active');
        localStorage.setItem('contas_visao', 'cards');
    });
    
    // Restaurar visão preferida
    const visaoPreferida = localStorage.getItem('contas_visao');
    if (visaoPreferida === 'cards') {
        btnVisaoCards.click();
    }
    
    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey) {
            switch(e.key) {
                case 'n':
                case 'N':
                    e.preventDefault();
                    window.location.href = 'criar.php';
                    break;
                case 'f':
                case 'F':
                    e.preventDefault();
                    const filtros = document.getElementById('filtrosAvancados');
                    if (filtros) {
                        new bootstrap.Collapse(filtros).toggle();
                    }
                    break;
                case 'p':
                case 'P':
                    e.preventDefault();
                    window.open('relatorio.php<?php echo !empty($filtros) ? "?" . http_build_query($filtros) : ""; ?>', '_blank');
                    break;
            }
        }
    });
    
    // Atualizar contadores em tempo real
    atualizarContadores();
    
    // Auto-refresh a cada 5 minutos para atualizar status vencidos
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            location.reload();
        }
    }, 300000); // 5 minutos
});

function confirmarExclusao(id, descricao) {
    document.getElementById('contaIdExcluir').value = id;
    document.getElementById('contaDescricao').textContent = descricao;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}

function atualizarContadores() {
    const rows = document.querySelectorAll('.conta-row');
    const contadores = {
        total: rows.length,
        pagar: 0,
        receber: 0,
        pendente: 0,
        vencido: 0,
        pago: 0
    };
    
    rows.forEach(row => {
        const tipo = row.dataset.tipo;
        const status = row.dataset.status;
        
        contadores[tipo]++;
        contadores[status]++;
    });
    
    // Atualizar badges se existirem
    const badges = document.querySelectorAll('[data-contador]');
    badges.forEach(badge => {
        const tipo = badge.dataset.contador;
        if (contadores[tipo] !== undefined) {
            badge.textContent = contadores[tipo];
        }
    });
}

// Funcionalidade de busca rápida em tempo real
const inputBusca = document.querySelector('input[name="busca"]');
if (inputBusca) {
    let timeoutBusca;
    inputBusca.addEventListener('input', function() {
        clearTimeout(timeoutBusca);
        timeoutBusca = setTimeout(() => {
            if (this.value.length >= 3 || this.value.length === 0) {
                filtrarContasRapido(this.value);
            }
        }, 500);
    });
}

function filtrarContasRapido(termo) {
    const rows = document.querySelectorAll('.conta-row');
    let visiveisCount = 0;
    
    rows.forEach(row => {
        const texto = row.textContent.toLowerCase();
        const match = termo === '' || texto.includes(termo.toLowerCase());
        
        row.style.display = match ? '' : 'none';
        if (match) visiveisCount++;
    });
    
    // Mostrar mensagem se nenhuma conta for encontrada
    const tbody = document.querySelector('tbody');
    let msgNaoEncontrado = document.getElementById('msgNaoEncontrado');
    
    if (visiveisCount === 0 && termo !== '') {
        if (!msgNaoEncontrado) {
            msgNaoEncontrado = document.createElement('tr');
            msgNaoEncontrado.id = 'msgNaoEncontrado';
            msgNaoEncontrado.innerHTML = `
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-search text-muted fs-1 mb-2"></i>
                    <div class="text-muted">Nenhuma conta encontrada para "${termo}"</div>
                </td>
            `;
            tbody.appendChild(msgNaoEncontrado);
        }
    } else if (msgNaoEncontrado) {
        msgNaoEncontrado.remove();
    }
}

// Tooltip para informações adicionais
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
});

// Animações de entrada escalonadas
document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.conta-item, .conta-row');
    items.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.05}s`;
        item.classList.add('fade-in');
    });
});

// Marcar contas vencidas visualmente
function marcarContasVencidas() {
    const hoje = new Date();
    const rows = document.querySelectorAll('.conta-row');
    
    rows.forEach(row => {
        const vencimentoCell = row.cells[4]; // Coluna de vencimento
        const vencimentoTexto = vencimentoCell.textContent.trim();
        
        if (vencimentoTexto !== '-') {
            const [dia, mes, ano] = vencimentoTexto.split('/');
            const vencimento = new Date(ano, mes - 1, dia);
            
            if (vencimento < hoje) {
                row.classList.add('table-danger');
                vencimentoCell.innerHTML = `
                    <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                    ${vencimentoTexto}
                `;
            }
        }
    });
}

// Executar ao carregar
document.addEventListener('DOMContentLoaded', marcarContasVencidas);

// Função para exportar dados
function exportarDados(formato) {
    const filtrosAtivos = new URLSearchParams(window.location.search);
    filtrosAtivos.set('formato', formato);
    window.open(`exportar.php?${filtrosAtivos.toString()}`, '_blank');
}

// Adicionar botões de exportação se necessário
document.addEventListener('DOMContentLoaded', function() {
    const headerCard = document.querySelector('.card-header .d-flex');
    if (headerCard && <?php echo empty($contas) ? 'false' : 'true'; ?>) {
        const exportGroup = document.createElement('div');
        exportGroup.className = 'btn-group ms-2';
        exportGroup.innerHTML = `
            <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-download me-1"></i>Exportar
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="exportarDados('excel')">
                    <i class="fas fa-file-excel me-2"></i>Excel
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="exportarDados('csv')">
                    <i class="fas fa-file-csv me-2"></i>CSV
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="exportarDados('json')">
                    <i class="fas fa-file-code me-2"></i>JSON
                </a></li>
            </ul>
        `;
        headerCard.appendChild(exportGroup);
    }
});
</script>

<?php require_once '../includes/footer.php';?>