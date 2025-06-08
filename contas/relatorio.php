<?php
// contas/relatorio.php
// Relatórios de contas - Sistema Domaria Café

require_once 'config.php';

// Capturar filtros da URL
$filtros = [
    'tipo' => $_GET['tipo'] ?? '',
    'status' => $_GET['status'] ?? '',
    'categoria_id' => $_GET['categoria_id'] ?? '',
    'vencimento_inicio' => $_GET['vencimento_inicio'] ?? '',
    'vencimento_fim' => $_GET['vencimento_fim'] ?? '',
    'formato' => $_GET['formato'] ?? 'html'
];

// Remover filtros vazios
$filtros = array_filter($filtros, function($value, $key) {
    return $value !== '' && $key !== 'formato';
}, ARRAY_FILTER_USE_BOTH);

// Buscar dados para relatório
$contas = $contasManager->buscarContas($filtros, 1, 1000); // Limite maior para relatórios
$categorias = $contasManager->buscarCategorias();

// Calcular totais
$totais = [
    'pagar' => ['total' => 0, 'pago' => 0, 'pendente' => 0, 'vencido' => 0, 'quantidade' => 0],
    'receber' => ['total' => 0, 'pago' => 0, 'pendente' => 0, 'vencido' => 0, 'quantidade' => 0],
    'geral' => ['total' => 0, 'pago' => 0, 'pendente' => 0, 'vencido' => 0, 'quantidade' => 0]
];

foreach ($contas as $conta) {
    $tipo = $conta['tipo'];
    
    $totais[$tipo]['total'] += $conta['valor_original'];
    $totais[$tipo]['pago'] += $conta['valor_pago'];
    $totais[$tipo]['pendente'] += $conta['valor_pendente'];
    $totais[$tipo]['quantidade']++;
    
    if ($conta['dias_vencido'] > 0) {
        $totais[$tipo]['vencido'] += $conta['valor_pendente'];
    }
    
    // Totais gerais
    $totais['geral']['total'] += $conta['valor_original'];
    $totais['geral']['pago'] += $conta['valor_pago'];
    $totais['geral']['pendente'] += $conta['valor_pendente'];
    $totais['geral']['quantidade']++;
    
    if ($conta['dias_vencido'] > 0) {
        $totais['geral']['vencido'] += $conta['valor_pendente'];
    }
}

// Verificar formato de saída
if ($_GET['formato'] === 'pdf') {
    gerarRelatorioPDF($contas, $totais, $filtros);
    exit;
} elseif ($_GET['formato'] === 'excel') {
    gerarRelatorioExcel($contas, $totais, $filtros);
    exit;
}

$page_title = "Relatório de Contas";
$page_description = "Relatório financeiro detalhado";

/**
 * Função para gerar relatório em PDF
 */
function gerarRelatorioPDF($contas, $totais, $filtros) {
    // Implementação básica - pode ser expandida com bibliotecas como TCPDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="relatorio_contas_' . date('Y-m-d') . '.pdf"');
    
    // Por simplicidade, vamos gerar HTML e deixar o navegador converter
    header('Content-Type: text/html');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Contas</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f5f5f5; font-weight: bold; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .total { background-color: #e9ecef; font-weight: bold; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>';
    
    echo '<h1 class="text-center">Relatório de Contas</h1>';
    echo '<p class="text-center">Gerado em: ' . date('d/m/Y H:i') . '</p>';
    
    if (!empty($filtros)) {
        echo '<h3>Filtros Aplicados:</h3><ul>';
        foreach ($filtros as $key => $value) {
            echo '<li>' . ucfirst(str_replace('_', ' ', $key)) . ': ' . htmlspecialchars($value) . '</li>';
        }
        echo '</ul>';
    }
    
    // Resumo
    echo '<h3>Resumo Financeiro</h3>';
    echo '<table>';
    echo '<tr><th>Tipo</th><th>Quantidade</th><th>Total</th><th>Pago</th><th>Pendente</th><th>Vencido</th></tr>';
    
    foreach (['pagar', 'receber'] as $tipo) {
        $label = $tipo === 'pagar' ? 'A Pagar' : 'A Receber';
        echo '<tr>';
        echo '<td>' . $label . '</td>';
        echo '<td class="text-center">' . $totais[$tipo]['quantidade'] . '</td>';
        echo '<td class="text-right">R$ ' . number_format($totais[$tipo]['total'], 2, ',', '.') . '</td>';
        echo '<td class="text-right">R$ ' . number_format($totais[$tipo]['pago'], 2, ',', '.') . '</td>';
        echo '<td class="text-right">R$ ' . number_format($totais[$tipo]['pendente'], 2, ',', '.') . '</td>';
        echo '<td class="text-right">R$ ' . number_format($totais[$tipo]['vencido'], 2, ',', '.') . '</td>';
        echo '</tr>';
    }
    
    echo '<tr class="total">';
    echo '<td><strong>TOTAL GERAL</strong></td>';
    echo '<td class="text-center"><strong>' . $totais['geral']['quantidade'] . '</strong></td>';
    echo '<td class="text-right"><strong>R$ ' . number_format($totais['geral']['total'], 2, ',', '.') . '</strong></td>';
    echo '<td class="text-right"><strong>R$ ' . number_format($totais['geral']['pago'], 2, ',', '.') . '</strong></td>';
    echo '<td class="text-right"><strong>R$ ' . number_format($totais['geral']['pendente'], 2, ',', '.') . '</strong></td>';
    echo '<td class="text-right"><strong>R$ ' . number_format($totais['geral']['vencido'], 2, ',', '.') . '</strong></td>';
    echo '</tr>';
    echo '</table>';
    
    // Detalhamento
    if (!empty($contas)) {
        echo '<h3>Detalhamento das Contas</h3>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Tipo</th><th>Descrição</th><th>Categoria</th><th>Valor</th>';
        echo '<th>Vencimento</th><th>Status</th><th>Cliente/Fornecedor</th>';
        echo '</tr>';
        
        foreach ($contas as $conta) {
            echo '<tr>';
            echo '<td>' . ($conta['tipo'] === 'pagar' ? 'A Pagar' : 'A Receber') . '</td>';
            echo '<td>' . htmlspecialchars($conta['descricao']) . '</td>';
            echo '<td>' . htmlspecialchars($conta['categoria_nome'] ?? 'Sem categoria') . '</td>';
            echo '<td class="text-right">R$ ' . number_format($conta['valor_original'], 2, ',', '.') . '</td>';
            echo '<td class="text-center">' . date('d/m/Y', strtotime($conta['data_vencimento'])) . '</td>';
            echo '<td>' . ucfirst($conta['status']) . '</td>';
            echo '<td>' . htmlspecialchars($conta['cliente_nome'] ?? $conta['fornecedor_nome'] ?? '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    echo '</body></html>';
}

/**
 * Função para gerar relatório em Excel
 */
function gerarRelatorioExcel($contas, $totais, $filtros) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="relatorio_contas_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    echo '<tr><td colspan="7" style="font-size: 16px; font-weight: bold; text-align: center;">Relatório de Contas - ' . date('d/m/Y') . '</td></tr>';
    echo '<tr><td colspan="7"></td></tr>';
    
    // Cabeçalho
    echo '<tr style="background-color: #f0f0f0; font-weight: bold;">';
    echo '<td>Tipo</td><td>Descrição</td><td>Categoria</td><td>Valor</td>';
    echo '<td>Vencimento</td><td>Status</td><td>Cliente/Fornecedor</td>';
    echo '</tr>';
    
    // Dados
    foreach ($contas as $conta) {
        echo '<tr>';
        echo '<td>' . ($conta['tipo'] === 'pagar' ? 'A Pagar' : 'A Receber') . '</td>';
        echo '<td>' . htmlspecialchars($conta['descricao']) . '</td>';
        echo '<td>' . htmlspecialchars($conta['categoria_nome'] ?? 'Sem categoria') . '</td>';
        echo '<td>' . number_format($conta['valor_original'], 2, ',', '.') . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($conta['data_vencimento'])) . '</td>';
        echo '<td>' . ucfirst($conta['status']) . '</td>';
        echo '<td>' . htmlspecialchars($conta['cliente_nome'] ?? $conta['fornecedor_nome'] ?? '-') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
}

require_once '../header.php';
?>

<style>
.relatorio-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    overflow: hidden;
}

.relatorio-header {
    background: var(--gradient-contas);
    color: white;
    padding: 2rem;
    text-align: center;
}

.resumo-card {
    background: rgba(102, 126, 234, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.1);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.estatistica {
    text-align: center;
    padding: 1rem;
}

.estatistica-valor {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.estatistica-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.tabela-relatorio {
    font-size: 0.9rem;
}

.tabela-relatorio th {
    background: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.total-row {
    background: rgba(102, 126, 234, 0.1);
    font-weight: 600;
}

.botoes-export {
    position: sticky;
    top: 20px;
    z-index: 100;
}

@media print {
    .botoes-export,
    .btn,
    .fade-in {
        display: none !important;
    }
    
    .relatorio-container {
        box-shadow: none;
        border: none;
    }
}

.fade-in {
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="container-fluid">
    <!-- Header do Relatório -->
    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-chart-bar text-primary me-2"></i>
                Relatório de Contas
            </h1>
            <p class="text-muted mb-0">Análise financeira detalhada</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Voltar
            </a>
        </div>
    </div>

    <!-- Botões de Exportação -->
    <div class="botoes-export mb-4 fade-in">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-download text-success me-2"></i>
                    Exportar Relatório
                </h5>
                
                <div class="d-flex gap-2 flex-wrap">
                    <button onclick="window.print()" class="btn btn-outline-primary">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                    
                    <a href="?<?php echo http_build_query(array_merge($filtros, ['formato' => 'pdf'])); ?>" 
                       class="btn btn-outline-danger" target="_blank">
                        <i class="fas fa-file-pdf me-2"></i>PDF
                    </a>
                    
                    <a href="?<?php echo http_build_query(array_merge($filtros, ['formato' => 'excel'])); ?>" 
                       class="btn btn-outline-success">
                        <i class="fas fa-file-excel me-2"></i>Excel
                    </a>
                    
                    <button onclick="compartilharRelatorio()" class="btn btn-outline-info">
                        <i class="fas fa-share-alt me-2"></i>Compartilhar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Container do Relatório -->
    <div class="relatorio-container fade-in" style="animation-delay: 0.2s">
        
        <!-- Header do Relatório -->
        <div class="relatorio-header">
            <h2 class="mb-1">Relatório Financeiro - Contas</h2>
            <p class="mb-0 opacity-75">
                Gerado em <?php echo date('d/m/Y H:i'); ?> | 
                Domaria Café - Sistema de Gestão
            </p>
        </div>

        <div class="p-4">
            
            <!-- Filtros Aplicados -->
            <?php if (!empty($filtros)): ?>
            <div class="alert alert-info mb-4">
                <h6><i class="fas fa-filter me-2"></i>Filtros Aplicados:</h6>
                <ul class="mb-0">
                    <?php 
                    $filtro_labels = [
                        'tipo' => 'Tipo',
                        'status' => 'Status',
                        'categoria_id' => 'Categoria',
                        'vencimento_inicio' => 'Vencimento (De)',
                        'vencimento_fim' => 'Vencimento (Até)'
                    ];
                    
                    foreach ($filtros as $key => $value): 
                        if ($key === 'formato') continue;
                        $label = $filtro_labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                        
                        // Formatar valor para exibição
                        $valor_display = $value;
                        if (in_array($key, ['vencimento_inicio', 'vencimento_fim'])) {
                            $valor_display = date('d/m/Y', strtotime($value));
                        } elseif ($key === 'categoria_id') {
                            $categoria = array_filter($categorias, function($cat) use ($value) {
                                return $cat['id'] == $value;
                            });
                            $valor_display = !empty($categoria) ? reset($categoria)['nome'] : $value;
                        }
                    ?>
                    <li><strong><?php echo $label; ?>:</strong> <?php echo htmlspecialchars($valor_display); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Resumo Financeiro -->
            <div class="resumo-card">
                <h4 class="mb-4 text-center">
                    <i class="fas fa-calculator text-primary me-2"></i>
                    Resumo Financeiro
                </h4>
                
                <div class="row">
                    <!-- Contas a Pagar -->
                    <div class="col-md-4 mb-3">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white text-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-arrow-down me-2"></i>
                                    Contas a Pagar
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="estatistica">
                                    <div class="estatistica-valor text-danger">
                                        <?php echo formatarMoeda($totais['pagar']['total']); ?>
                                    </div>
                                    <div class="estatistica-label">Total</div>
                                </div>
                                
                                <hr>
                                
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="fw-bold"><?php echo formatarMoeda($totais['pagar']['pago']); ?></div>
                                        <small class="text-muted">Pago</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold"><?php echo formatarMoeda($totais['pagar']['pendente']); ?></div>
                                        <small class="text-muted">Pendente</small>
                                    </div>
                                </div>
                                
                                <?php if ($totais['pagar']['vencido'] > 0): ?>
                                <div class="mt-2 text-center">
                                    <div class="fw-bold text-danger"><?php echo formatarMoeda($totais['pagar']['vencido']); ?></div>
                                    <small class="text-danger">Vencido</small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-3 text-center">
                                    <span class="badge bg-secondary"><?php echo $totais['pagar']['quantidade']; ?> conta(s)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contas a Receber -->
                    <div class="col-md-4 mb-3">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white text-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-arrow-up me-2"></i>
                                    Contas a Receber
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="estatistica">
                                    <div class="estatistica-valor text-success">
                                        <?php echo formatarMoeda($totais['receber']['total']); ?>
                                    </div>
                                    <div class="estatistica-label">Total</div>
                                </div>
                                
                                <hr>
                                
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="fw-bold"><?php echo formatarMoeda($totais['receber']['pago']); ?></div>
                                        <small class="text-muted">Recebido</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold"><?php echo formatarMoeda($totais['receber']['pendente']); ?></div>
                                        <small class="text-muted">Pendente</small>
                                    </div>
                                </div>
                                
                                <?php if ($totais['receber']['vencido'] > 0): ?>
                                <div class="mt-2 text-center">
                                    <div class="fw-bold text-danger"><?php echo formatarMoeda($totais['receber']['vencido']); ?></div>
                                    <small class="text-danger">Vencido</small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-3 text-center">
                                    <span class="badge bg-secondary"><?php echo $totais['receber']['quantidade']; ?> conta(s)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Saldo Geral -->
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white text-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-balance-scale me-2"></i>
                                    Saldo Geral
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                $saldo_total = $totais['receber']['total'] - $totais['pagar']['total'];
                                $saldo_pendente = $totais['receber']['pendente'] - $totais['pagar']['pendente'];
                                ?>
                                
                                <div class="estatistica">
                                    <div class="estatistica-valor <?php echo $saldo_total >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatarMoeda(abs($saldo_total)); ?>
                                    </div>
                                    <div class="estatistica-label">
                                        <?php echo $saldo_total >= 0 ? 'Superávit' : 'Déficit'; ?> Total
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="text-center">
                                    <div class="fw-bold <?php echo $saldo_pendente >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatarMoeda(abs($saldo_pendente)); ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $saldo_pendente >= 0 ? 'Superávit' : 'Déficit'; ?> Pendente
                                    </small>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <span class="badge bg-secondary"><?php echo $totais['geral']['quantidade']; ?> conta(s) total</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalhamento das Contas -->
            <?php if (!empty($contas)): ?>
            <div class="mb-4">
                <h4 class="mb-3">
                    <i class="fas fa-list text-primary me-2"></i>
                    Detalhamento das Contas
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover tabela-relatorio">
                        <thead>
                            <tr>
                                <th width="8%">Tipo</th>
                                <th width="25%">Descrição</th>
                                <th width="15%">Categoria</th>
                                <th width="12%">Valor</th>
                                <th width="10%">Vencimento</th>
                                <th width="10%">Status</th>
                                <th width="20%">Cliente/Fornecedor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contas as $conta): ?>
                            <tr class="<?php echo $conta['dias_vencido'] > 0 ? 'table-warning' : ''; ?>">
                                <td>
                                    <span class="badge bg-<?php echo $conta['tipo'] === 'pagar' ? 'danger' : 'success'; ?>">
                                        <?php echo $conta['tipo'] === 'pagar' ? 'Pagar' : 'Receber'; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($conta['descricao']); ?></strong>
                                    <?php if ($conta['documento']): ?>
                                        <br><small class="text-muted">Doc: <?php echo htmlspecialchars($conta['documento']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($conta['categoria_nome']): ?>
                                        <span class="badge" style="background-color: <?php echo $conta['categoria_cor']; ?>; color: white;">
                                            <?php echo htmlspecialchars($conta['categoria_nome']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Sem categoria</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo formatarMoeda($conta['valor_original']); ?></strong>
                                    <?php if ($conta['valor_pago'] > 0): ?>
                                        <br><small class="text-success">Pago: <?php echo formatarMoeda($conta['valor_pago']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="<?php echo $conta['dias_vencido'] > 0 ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo formatarData($conta['data_vencimento']); ?>
                                    </span>
                                    <?php if ($conta['dias_vencido'] > 0): ?>
                                        <br><small class="text-danger"><?php echo $conta['dias_vencido']; ?> dias</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $statusBadge = getBadgeStatus($conta['status']);
                                    ?>
                                    <span class="<?php echo $statusBadge['class']; ?>">
                                        <?php echo $statusBadge['label']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($conta['cliente_nome'] ?? $conta['fornecedor_nome'] ?? '-'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3"><strong>TOTAIS</strong></td>
                                <td class="text-end"><strong><?php echo formatarMoeda($totais['geral']['total']); ?></strong></td>
                                <td colspan="3" class="text-center">
                                    <strong><?php echo $totais['geral']['quantidade']; ?> conta(s)</strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">Nenhuma conta encontrada</h5>
                <p class="text-muted">Ajuste os filtros para gerar o relatório.</p>
            </div>
            <?php endif; ?>

            <!-- Rodapé do Relatório -->
            <div class="border-top pt-3 mt-4 text-center text-muted">
                <small>
                    Relatório gerado pelo Sistema Domaria Café v<?php echo MODULO_CONTAS_VERSION; ?> em <?php echo date('d/m/Y H:i:s'); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<script>
function compartilharRelatorio() {
    const url = window.location.href;
    
    if (navigator.share) {
        navigator.share({
            title: 'Relatório de Contas - Domaria Café',
            text: 'Relatório financeiro gerado pelo sistema',
            url: url
        });
    } else {
        navigator.clipboard.writeText(url).then(function() {
            alert('Link do relatório copiado para a área de transferência!');
        });
    }
}

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey) {
        switch(e.key) {
            case 'p':
            case 'P':
                e.preventDefault();
                window.print();
                break;
        }
    }
});
</script>

<?php require_once '../footer.php'; ?>