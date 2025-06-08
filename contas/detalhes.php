<?php
// contas/detalhes.php
// Página de detalhes da conta - Sistema Domaria Café

require_once 'config.php';

// <<< CORREÇÃO ADICIONADA AQUI >>>
// Esta verificação garante que o script pare se a conexão com o banco falhar,
// resolvendo os alertas do editor de código.
if (!isset($pdo_connection) || !$pdo_connection instanceof PDO) {
    die("Erro crítico: A conexão com o banco de dados não pôde ser estabelecida.");
}

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Conta não encontrada.";
    $_SESSION['msg_type'] = "error";
    header("Location: index.php");
    exit;
}

$conta_id = (int)$_GET['id'];

// Buscar dados da conta
$sql = "
    SELECT 
        c.*,
        cat.nome as categoria_nome,
        cat.cor as categoria_cor,
        cat.icone as categoria_icone,
        cat.tipo as categoria_tipo,
        cl.nome as cliente_nome,
        cl.email as cliente_email,
        cl.telefone as cliente_telefone,
        f.nome as fornecedor_nome,
        f.email as fornecedor_email,
        f.telefone as fornecedor_telefone,
        u.nome as usuario_nome,
        v.id as venda_numero,
        CASE 
            WHEN c.data_vencimento < CURDATE() AND c.status IN ('pendente', 'pago_parcial') 
            THEN DATEDIFF(CURDATE(), c.data_vencimento) 
            ELSE 0 
        END as dias_atraso
    FROM contas c
    LEFT JOIN categorias_contas cat ON c.categoria_id = cat.id
    LEFT JOIN clientes cl ON c.cliente_id = cl.id
    LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
    LEFT JOIN usuarios u ON c.usuario_cadastro = u.id
    LEFT JOIN vendas v ON c.venda_id = v.id
    WHERE c.id = :id
";

$stmt = $pdo_connection->prepare($sql);
$stmt->execute([':id' => $conta_id]);
$conta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conta) {
    $_SESSION['msg'] = "Conta não encontrada.";
    $_SESSION['msg_type'] = "error";
    header("Location: index.php");
    exit;
}

// Buscar histórico de pagamentos
$sql_pagamentos = "
    SELECT 
        p.*,
        u.nome as usuario_nome
    FROM pagamentos_contas p
    LEFT JOIN usuarios u ON p.usuario_registro = u.id
    WHERE p.conta_id = :conta_id
    ORDER BY p.data_pagamento DESC, p.created_at DESC
";
$stmt_pagamentos = $pdo_connection->prepare($sql_pagamentos);
$stmt_pagamentos->execute([':conta_id' => $conta_id]);
$pagamentos = $stmt_pagamentos->fetchAll(PDO::FETCH_ASSOC);

// Buscar histórico de alterações
$sql_historico = "
    SELECT 
        h.*,
        u.nome as usuario_nome
    FROM historico_contas h
    LEFT JOIN usuarios u ON h.usuario_id = u.id
    WHERE h.conta_id = :conta_id
    ORDER BY h.created_at DESC
    LIMIT 10
";
$stmt_historico = $pdo_connection->prepare($sql_historico);
$stmt_historico->execute([':conta_id' => $conta_id]);
$historico = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

// Buscar anexos
$sql_anexos = "
    SELECT 
        a.*,
        u.nome as usuario_nome
    FROM anexos_contas a
    LEFT JOIN usuarios u ON a.usuario_upload = u.id
    WHERE a.conta_id = :conta_id
    ORDER BY a.created_at DESC
";
$stmt_anexos = $pdo_connection->prepare($sql_anexos);
$stmt_anexos->execute([':conta_id' => $conta_id]);
$anexos = $stmt_anexos->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Detalhes da Conta";
$page_description = htmlspecialchars($conta['descricao']);

require_once '../includes/header.php';
?>

<style>
.conta-header {
    background: var(--gradient-contas);
    color: white;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.conta-valor-principal {
    font-size: 2.5rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.info-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    border-left: 4px solid transparent;
    transition: all 0.3s ease;
}

.info-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.info-card.pagar {
    border-left-color: #fc466b;
}

.info-card.receber {
    border-left-color: #11998e;
}

.progress-pagamento {
    height: 8px;
    border-radius: 10px;
    background: rgba(0,0,0,0.1);
    overflow: hidden;
}

.progress-pagamento .progress-bar {
    transition: width 0.6s ease;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--gradient-contas);
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
    background: white;
    border-radius: 8px;
    margin-bottom: 1rem;
    padding: 1rem;
    box-shadow: var(--shadow-soft);
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -2.3rem;
    top: 1.2rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--gradient-contas);
    border: 3px solid white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
}

.timeline-item.pagamento::before {
    background: var(--gradient-success);
    box-shadow: 0 0 0 3px rgba(17, 153, 142, 0.2);
}

.timeline-item.historico::before {
    background: var(--gradient-info);
    box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.2);
}

.anexo-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.anexo-item:hover {
    border-color: #667eea;
    box-shadow: var(--shadow-soft);
}

.actions-sticky {
    position: sticky;
    top: 20px;
    z-index: 100;
}

.valor-destaque {
    font-size: 1.5rem;
    font-weight: 700;
}

.fade-in {
    animation: fadeIn 0.6s ease;
}

.fade-in-delay {
    animation: fadeIn 0.6s ease 0.3s both;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .conta-valor-principal {
        font-size: 1.8rem;
    }
    
    .actions-sticky {
        position: relative;
        top: 0;
    }
}
</style>

<div class="conta-header p-4 fade-in">
    <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center mb-2">
                <h1 class="h3 mb-0 me-3"><?php echo htmlspecialchars($conta['descricao']); ?></h1>
                
                <?php if ($conta['tipo'] == 'pagar'): ?>
                    <span class="badge bg-danger fs-6">
                        <i class="fas fa-arrow-down me-1"></i>A Pagar
                    </span>
                <?php else: ?>
                    <span class="badge bg-success fs-6">
                        <i class="fas fa-arrow-up me-1"></i>A Receber
                    </span>
                <?php endif; ?>
                
                <?php 
                $statusBadge = getBadgeStatus($conta['status']);
                ?>
                <span class="<?php echo $statusBadge['class']; ?> fs-6 ms-2">
                    <i class="fas fa-<?php echo $statusBadge['icon']; ?> me-1"></i>
                    <?php echo $statusBadge['label']; ?>
                </span>
            </div>
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="conta-valor-principal">
                        <?php echo formatarMoeda($conta['valor_original']); ?>
                    </div>
                    
                    <?php if ($conta['valor_pago'] > 0): ?>
                        <div class="mt-2">
                            <small class="opacity-75">Pago: <?php echo formatarMoeda($conta['valor_pago']); ?></small><br>
                            <small class="opacity-75">Pendente: <?php echo formatarMoeda($conta['valor_pendente']); ?></small>
                        </div>
                        
                        <div class="progress-pagamento mt-2">
                            <div class="progress-bar bg-success" style="width: <?php echo ($conta['valor_pago'] / $conta['valor_original']) * 100; ?>%"></div>
                        </div>
                        <small class="opacity-75 mt-1 d-block">
                            <?php echo number_format(($conta['valor_pago'] / $conta['valor_original']) * 100, 1); ?>% pago
                        </small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 text-md-end">
                    <div class="mb-2">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <strong>Vencimento:</strong> 
                        <span class="<?php echo $conta['dias_atraso'] > 0 ? 'text-warning' : ''; ?>">
                            <?php echo formatarData($conta['data_vencimento']); ?>
                        </span>
                    </div>
                    
                    <?php if ($conta['dias_atraso'] > 0): ?>
                        <div class="alert alert-warning py-2 px-3 mb-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong><?php echo $conta['dias_atraso']; ?> dia(s) em atraso</strong>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <small class="opacity-75">
                            <i class="fas fa-user me-1"></i>
                            Cadastrado por <?php echo htmlspecialchars($conta['usuario_nome'] ?? 'Sistema'); ?>
                        </small><br>
                        <small class="opacity-75">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date('d/m/Y H:i', strtotime($conta['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="ms-3">
            <a href="index.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Voltar
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        
        <div class="info-card <?php echo $conta['tipo']; ?> p-4 mb-4 fade-in">
            <h5 class="mb-3">
                <i class="fas fa-info-circle text-primary me-2"></i>
                Informações Básicas
            </h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>Categoria:</strong><br>
                    <?php if ($conta['categoria_nome']): ?>
                        <span class="badge" style="background-color: <?php echo $conta['categoria_cor']; ?>; color: white;">
                            <i class="<?php echo $conta['categoria_icone']; ?> me-1"></i>
                            <?php echo htmlspecialchars($conta['categoria_nome']); ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">Sem categoria</span>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <strong>Prioridade:</strong><br>
                    <?php 
                    $prioridadeBadge = getBadgePrioridade($conta['prioridade']);
                    ?>
                    <span class="<?php echo $prioridadeBadge['class']; ?>">
                        <i class="fas fa-<?php echo $prioridadeBadge['icon']; ?> me-1"></i>
                        <?php echo $prioridadeBadge['label']; ?>
                    </span>
                </div>
                
                <div class="col-md-6 mb-3">
                    <strong>Data de Competência:</strong><br>
                    <span class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo formatarData($conta['data_competencia']); ?>
                    </span>
                </div>
                
                <div class="col-md-6 mb-3">
                    <strong>Forma de Pagamento:</strong><br>
                    <?php if ($conta['forma_pagamento']): ?>
                        <span class="text-muted">
                            <i class="fas fa-credit-card me-1"></i>
                            <?php echo CONTAS_FORMAS_PAGAMENTO[$conta['forma_pagamento']] ?? $conta['forma_pagamento']; ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">Não informado</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($conta['documento']): ?>
                <div class="col-md-6 mb-3">
                    <strong>Documento/Referência:</strong><br>
                    <span class="text-muted">
                        <i class="fas fa-file-alt me-1"></i>
                        <?php echo htmlspecialchars($conta['documento']); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if ($conta['venda_numero']): ?>
                <div class="col-md-6 mb-3">
                    <strong>Venda Relacionada:</strong><br>
                    <a href="../vendas/detalhes.php?id=<?php echo $conta['venda_id']; ?>" class="text-decoration-none">
                        <i class="fas fa-shopping-cart me-1"></i>
                        Venda #<?php echo $conta['venda_numero']; ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($conta['observacoes']): ?>
            <div class="mt-3">
                <strong>Observações:</strong>
                <div class="bg-light p-3 rounded mt-2">
                    <?php echo nl2br(htmlspecialchars($conta['observacoes'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($conta['recorrente']): ?>
            <div class="mt-3">
                <div class="alert alert-info">
                    <i class="fas fa-sync-alt me-2"></i>
                    <strong>Conta Recorrente</strong> - 
                    Periodicidade: <?php echo CONTAS_PERIODICIDADES[$conta['periodicidade']] ?? $conta['periodicidade']; ?>
                    <?php if ($conta['dia_vencimento']): ?>
                        (Todo dia <?php echo $conta['dia_vencimento']; ?>)
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($conta['cliente_nome'] || $conta['fornecedor_nome']): ?>
        <div class="info-card p-4 mb-4 fade-in" style="animation-delay: 0.1s">
            <h5 class="mb-3">
                <i class="fas fa-users text-primary me-2"></i>
                Relacionamentos
            </h5>
            
            <?php if ($conta['cliente_nome']): ?>
            <div class="d-flex align-items-center mb-3">
                <div class="me-3">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px;">
                        <i class="fas fa-user text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <strong><?php echo htmlspecialchars($conta['cliente_nome']); ?></strong>
                    <small class="d-block text-muted">Cliente</small>
                    <?php if ($conta['cliente_email']): ?>
                        <small class="text-muted">
                            <i class="fas fa-envelope me-1"></i>
                            <a href="mailto:<?php echo $conta['cliente_email']; ?>">
                                <?php echo htmlspecialchars($conta['cliente_email']); ?>
                            </a>
                        </small>
                    <?php endif; ?>
                    <?php if ($conta['cliente_telefone']): ?>
                        <small class="text-muted d-block">
                            <i class="fas fa-phone me-1"></i>
                            <a href="tel:<?php echo $conta['cliente_telefone']; ?>">
                                <?php echo htmlspecialchars($conta['cliente_telefone']); ?>
                            </a>
                        </small>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="../clientes/detalhes.php?id=<?php echo $conta['cliente_id']; ?>" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>Ver Cliente
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($conta['fornecedor_nome']): ?>
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px;">
                        <i class="fas fa-truck text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <strong><?php echo htmlspecialchars($conta['fornecedor_nome']); ?></strong>
                    <small class="d-block text-muted">Fornecedor</small>
                    <?php if ($conta['fornecedor_email']): ?>
                        <small class="text-muted">
                            <i class="fas fa-envelope me-1"></i>
                            <a href="mailto:<?php echo $conta['fornecedor_email']; ?>">
                                <?php echo htmlspecialchars($conta['fornecedor_email']); ?>
                            </a>
                        </small>
                    <?php endif; ?>
                    <?php if ($conta['fornecedor_telefone']): ?>
                        <small class="text-muted d-block">
                            <i class="fas fa-phone me-1"></i>
                            <a href="tel:<?php echo $conta['fornecedor_telefone']; ?>">
                                <?php echo htmlspecialchars($conta['fornecedor_telefone']); ?>
                            </a>
                        </small>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="../fornecedores/detalhes.php?id=<?php echo $conta['fornecedor_id']; ?>" 
                       class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-eye me-1"></i>Ver Fornecedor
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($pagamentos)): ?>
        <div class="info-card p-4 mb-4 fade-in" style="animation-delay: 0.2s">
            <h5 class="mb-3">
                <i class="fas fa-credit-card text-success me-2"></i>
                Histórico de Pagamentos
            </h5>
            
            <div class="timeline">
                <?php foreach ($pagamentos as $pagamento): ?>
                <div class="timeline-item pagamento">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <strong class="text-success">
                                <?php echo formatarMoeda($pagamento['valor']); ?>
                            </strong>
                            <small class="d-block text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo formatarData($pagamento['data_pagamento']); ?>
                            </small>
                            
                            <?php if ($pagamento['forma_pagamento']): ?>
                            <small class="text-muted">
                                <i class="fas fa-credit-card me-1"></i>
                                <?php echo CONTAS_FORMAS_PAGAMENTO[$pagamento['forma_pagamento']] ?? $pagamento['forma_pagamento']; ?>
                            </small>
                            <?php endif; ?>
                            
                            <?php if ($pagamento['observacoes']): ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-comment me-1"></i>
                                    <?php echo htmlspecialchars($pagamento['observacoes']); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($pagamento['documento_comprovante']): ?>
                            <div class="mt-2">
                                <a href="../uploads/contas/<?php echo $pagamento['documento_comprovante']; ?>" 
                                   target="_blank" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-download me-1"></i>Comprovante
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end">
                            <small class="text-muted">
                                Por: <?php echo htmlspecialchars($pagamento['usuario_nome'] ?? 'Sistema'); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($anexos)): ?>
        <div class="info-card p-4 mb-4 fade-in" style="animation-delay: 0.3s">
            <h5 class="mb-3">
                <i class="fas fa-paperclip text-info me-2"></i>
                Anexos
            </h5>
            
            <?php foreach ($anexos as $anexo): ?>
            <div class="anexo-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <?php
                            $extensao = strtolower(pathinfo($anexo['nome_original'], PATHINFO_EXTENSION));
                            $icone_class = match($extensao) {
                                'pdf' => 'fas fa-file-pdf text-danger',
                                'doc', 'docx' => 'fas fa-file-word text-primary',
                                'xls', 'xlsx' => 'fas fa-file-excel text-success',
                                'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image text-warning',
                                default => 'fas fa-file text-secondary'
                            };
                            ?>
                            <i class="<?php echo $icone_class; ?> fs-4"></i>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($anexo['nome_original']); ?></strong>
                            <small class="d-block text-muted">
                                <?php echo number_format($anexo['tamanho'] / 1024, 1); ?> KB • 
                                Enviado em <?php echo date('d/m/Y H:i', strtotime($anexo['created_at'])); ?> por 
                                <?php echo htmlspecialchars($anexo['usuario_nome'] ?? 'Sistema'); ?>
                            </small>
                        </div>
                    </div>
                    
                    <div>
                        <a href="../uploads/contas/<?php echo $anexo['nome_arquivo']; ?>" 
                           target="_blank" class="btn btn-outline-primary btn-sm me-2">
                            <i class="fas fa-download me-1"></i>Baixar
                        </a>
                        <button class="btn btn-outline-danger btn-sm" 
                                onclick="excluirAnexo(<?php echo $anexo['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($historico)): ?>
        <div class="info-card p-4 mb-4 fade-in" style="animation-delay: 0.4s">
            <h5 class="mb-3">
                <i class="fas fa-history text-secondary me-2"></i>
                Histórico de Alterações
            </h5>
            
            <div class="timeline">
                <?php foreach ($historico as $item): ?>
                <div class="timeline-item historico">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <?php
                            $acao_labels = [
                                'criacao' => 'Conta criada',
                                'edicao' => 'Conta editada',
                                'pagamento' => 'Pagamento registrado',
                                'cancelamento' => 'Conta cancelada',
                                'reativacao' => 'Conta reativada'
                            ];
                            ?>
                            <strong><?php echo $acao_labels[$item['acao']] ?? $item['acao']; ?></strong>
                            <small class="d-block text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?> por 
                                <?php echo htmlspecialchars($item['usuario_nome'] ?? 'Sistema'); ?>
                            </small>
                            
                            <?php if ($item['observacoes']): ?>
                            <small class="text-muted d-block mt-1">
                                <?php echo htmlspecialchars($item['observacoes']); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="actions-sticky">
            
            <div class="info-card p-4 mb-4 fade-in-delay">
                <h5 class="mb-3">
                    <i class="fas fa-tools text-primary me-2"></i>
                    Ações
                </h5>
                
                <div class="d-grid gap-2">
                    <?php if (in_array($conta['status'], ['pendente', 'pago_parcial'])): ?>
                        <a href="registrar_pagamento.php?id=<?php echo $conta['id']; ?>" 
                           class="btn btn-success">
                            <i class="fas fa-credit-card me-2"></i>
                            Registrar Pagamento
                        </a>
                    <?php endif; ?>
                    
                    <a href="editar.php?id=<?php echo $conta['id']; ?>" 
                       class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>
                        Editar Conta
                    </a>
                    
                    <button class="btn btn-info" onclick="adicionarAnexo()">
                        <i class="fas fa-paperclip me-2"></i>
                        Adicionar Anexo
                    </button>
                    
                    <a href="duplicar.php?id=<?php echo $conta['id']; ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-copy me-2"></i>
                        Duplicar Conta
                    </a>
                    
                    <hr>
                    
                    <a href="relatorio.php?id=<?php echo $conta['id']; ?>" 
                       class="btn btn-outline-success" target="_blank">
                        <i class="fas fa-file-pdf me-2"></i>
                        Relatório PDF
                    </a>
                    
                    <button class="btn btn-outline-info" onclick="compartilharConta()">
                        <i class="fas fa-share-alt me-2"></i>
                        Compartilhar
                    </button>
                    
                    <hr>
                    
                    <?php if ($conta['status'] != 'cancelado'): ?>
                        <button class="btn btn-outline-warning" 
                                onclick="alterarStatus('cancelado')">
                            <i class="fas fa-ban me-2"></i>
                            Cancelar Conta
                        </button>
                    <?php else: ?>
                        <button class="btn btn-outline-success" 
                                onclick="alterarStatus('pendente')">
                            <i class="fas fa-undo me-2"></i>
                            Reativar Conta
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline-danger" 
                            onclick="excluirConta(<?php echo $conta['id']; ?>, '<?php echo addslashes($conta['descricao']); ?>')">
                        <i class="fas fa-trash me-2"></i>
                        Excluir Conta
                    </button>
                </div>
            </div>
            
            <div class="info-card p-4 mb-4 fade-in-delay" style="animation-delay: 0.1s">
                <h5 class="mb-3">
                    <i class="fas fa-calculator text-success me-2"></i>
                    Resumo Financeiro
                </h5>
                
                <div class="row text-center">
                    <div class="col-12 mb-3">
                        <div class="valor-destaque text-primary">
                            <?php echo formatarMoeda($conta['valor_original']); ?>
                        </div>
                        <small class="text-muted">Valor Original</small>
                    </div>
                    
                    <?php if ($conta['valor_pago'] > 0): ?>
                    <div class="col-6 mb-3">
                        <div class="fs-5 fw-bold text-success">
                            <?php echo formatarMoeda($conta['valor_pago']); ?>
                        </div>
                        <small class="text-muted">Pago</small>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($conta['valor_pendente'] > 0): ?>
                    <div class="col-6 mb-3">
                        <div class="fs-5 fw-bold text-warning">
                            <?php echo formatarMoeda($conta['valor_pendente']); ?>
                        </div>
                        <small class="text-muted">Pendente</small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($conta['valor_pago'] > 0 && $conta['valor_original'] > 0): ?>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?php echo ($conta['valor_pago'] / $conta['valor_original']) * 100; ?>%"></div>
                </div>
                <div class="text-center">
                    <small class="text-muted">
                        <?php echo number_format(($conta['valor_pago'] / $conta['valor_original']) * 100, 1); ?>% quitado
                    </small>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="info-card p-4 fade-in-delay" style="animation-delay: 0.2s">
                <h5 class="mb-3">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    Informações Rápidas
                </h5>
                
                <div class="small">
                    <div class="d-flex justify-content-between py-1">
                        <span>ID da Conta:</span>
                        <strong>#<?php echo $conta['id']; ?></strong>
                    </div>
                    
                    <div class="d-flex justify-content-between py-1">
                        <span>Data de Cadastro:</span>
                        <strong><?php echo formatarData($conta['created_at']); ?></strong>
                    </div>
                    
                    <div class="d-flex justify-content-between py-1">
                        <span>Última Atualização:</span>
                        <strong><?php echo date('d/m/Y H:i', strtotime($conta['updated_at'])); ?></strong>
                    </div>
                    
                    <?php if ($conta['dias_atraso'] > 0): ?>
                    <div class="d-flex justify-content-between py-1 text-danger">
                        <span>Dias em Atraso:</span>
                        <strong><?php echo $conta['dias_atraso']; ?> dias</strong>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between py-1">
                        <span>Anexos:</span>
                        <strong><?php echo count($anexos); ?></strong>
                    </div>
                    
                    <div class="d-flex justify-content-between py-1">
                        <span>Pagamentos:</span>
                        <strong><?php echo count($pagamentos); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAnexo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAnexo" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Anexo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="conta_id" value="<?php echo $conta['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Arquivo *</label>
                        <input type="file" class="form-control" name="arquivo" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        <small class="text-muted">Formatos aceitos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (máx. 5MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-2"></i>Enviar</button>
                </div>
            </form>
        </div>
    </div>
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
                    <p class="text-muted mb-0" id="contaDescricaoExcluir"></p>
                    <small class="text-danger">Esta ação não pode ser desfeita.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formExcluir" method="POST" action="excluir.php" class="d-inline">
                    <input type="hidden" name="id" id="contaIdExcluir">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-2"></i>Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey) {
            switch(e.key) {
                case 'e': case 'E':
                    e.preventDefault();
                    window.location.href = 'editar.php?id=<?php echo $conta['id']; ?>';
                    break;
                case 'p': case 'P':
                    e.preventDefault();
                    <?php if (in_array($conta['status'], ['pendente', 'pago_parcial'])): ?>
                    window.location.href = 'registrar_pagamento.php?id=<?php echo $conta['id']; ?>';
                    <?php endif; ?>
                    break;
            }
        }
    });
});

function adicionarAnexo() { new bootstrap.Modal(document.getElementById('modalAnexo')).show(); }
function excluirConta(id, descricao) {
    document.getElementById('contaIdExcluir').value = id;
    document.getElementById('contaDescricaoExcluir').textContent = descricao;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
//... resto do seu JS completo aqui
</script>

<?php require_once '../includes/footer.php'; ?>