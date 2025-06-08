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

// Vendas do dia atual (sempre mostra o dia atual)
$data_hoje = date('Y-m-d');
$data_formatada = date('d/m/Y');
$label_vendas = "Vendas do Dia ($data_formatada)";
$vendas_dia = 0;
$data_vendas = $data_hoje;

$sql_vendas_dia = "SELECT COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0) as total
                  FROM vendas v 
                  JOIN itens_venda iv ON v.id = iv.venda_id 
                  WHERE DATE(v.data_venda) = '$data_hoje'";
$result_vendas_dia = $conn->query($sql_vendas_dia);

if ($result_vendas_dia && $result_vendas_dia->num_rows > 0) {
    $row = $result_vendas_dia->fetch_assoc();
    $vendas_dia = $row['total'];
}

// Informação adicional sobre o último dia com vendas
$ultimo_dia_com_vendas = "";
$valor_ultimo_dia = 0;

if ($vendas_dia == 0) {
    $sql_ultimo_dia = "SELECT DATE(v.data_venda) as ultimo_dia,
                      SUM(iv.quantidade * iv.valor_unitario) as total
                      FROM vendas v 
                      JOIN itens_venda iv ON v.id = iv.venda_id 
                      GROUP BY DATE(v.data_venda)
                      ORDER BY ultimo_dia DESC
                      LIMIT 1";
    $result_ultimo_dia = $conn->query($sql_ultimo_dia);
    
    if ($result_ultimo_dia && $result_ultimo_dia->num_rows > 0) {
        $row = $result_ultimo_dia->fetch_assoc();
        $valor_ultimo_dia = $row['total'];
        $ultimo_dia_com_vendas = date('d/m/Y', strtotime($row['ultimo_dia']));
    }
}

// Total de produtos (mantido para referência, mas não usado no card)
$sql_produtos = "SELECT COUNT(*) as total FROM produtos";
$result_produtos = $conn->query($sql_produtos);
$total_produtos = $result_produtos->fetch_assoc()['total'];

// Pagamentos recebidos no mês atual (NOVO)
$mes_atual = date('m');
$ano_atual = date('Y');
$sql_pagamentos_mes = "SELECT COALESCE(SUM(valor), 0) as total 
                      FROM pagamentos 
                      WHERE MONTH(data_pagamento) = $mes_atual 
                      AND YEAR(data_pagamento) = $ano_atual";
$result_pagamentos_mes = $conn->query($sql_pagamentos_mes);
$pagamentos_mes = 0;

if ($result_pagamentos_mes && $result_pagamentos_mes->num_rows > 0) {
    $pagamentos_mes = $result_pagamentos_mes->fetch_assoc()['total'];
}

// Vendas do mês atual
$sql_vendas_mes = "SELECT SUM(iv.quantidade * iv.valor_unitario) as total 
                  FROM vendas v 
                  JOIN itens_venda iv ON v.id = iv.venda_id 
                  WHERE MONTH(v.data_venda) = $mes_atual 
                  AND YEAR(v.data_venda) = $ano_atual";
$result_vendas_mes = $conn->query($sql_vendas_mes);
$vendas_mes = $result_vendas_mes->fetch_assoc()['total'] ?? 0;

// Saldo a receber (vendas em aberto) - VERSÃO CORRIGIDA
$saldo_receber = 0;

// Método direto para calcular o saldo a receber
$sql_vendas = "SELECT id FROM vendas WHERE status = 'aberto'";
$result_vendas = $conn->query($sql_vendas);

if ($result_vendas && $result_vendas->num_rows > 0) {
    while ($venda = $result_vendas->fetch_assoc()) {
        $venda_id = $venda['id'];
        
        // Calcular valor total da venda
        $sql_total = "SELECT SUM(quantidade * valor_unitario) as total FROM itens_venda WHERE venda_id = $venda_id";
        $result_total = $conn->query($sql_total);
        $total = $result_total->fetch_assoc()['total'] ?? 0;
        
        // Calcular pagamentos da venda
        $sql_pagos = "SELECT SUM(valor) as pago FROM pagamentos WHERE venda_id = $venda_id";
        $result_pagos = $conn->query($sql_pagos);
        $pago = $result_pagos->fetch_assoc()['pago'] ?? 0;
        
        // Adicionar ao saldo a receber
        $saldo_venda = max(0, $total - $pago);
        $saldo_receber += $saldo_venda;
    }
}

// Dados para o gráfico de vendas dos últimos 6 meses
$meses = [];
$valores_vendas = [];
$valores_pagamentos = []; // Adicionar para o gráfico duplo

for ($i = 5; $i >= 0; $i--) {
    $mes = date('m', strtotime("-$i months"));
    $ano = date('Y', strtotime("-$i months"));
    $nome_mes = date('M', strtotime("-$i months"));
    
    $sql_vendas = "SELECT SUM(iv.quantidade * iv.valor_unitario) as total 
                  FROM vendas v 
                  JOIN itens_venda iv ON v.id = iv.venda_id 
                  WHERE MONTH(v.data_venda) = $mes 
                  AND YEAR(v.data_venda) = $ano";
    $result_vendas = $conn->query($sql_vendas);
    $total_vendas = $result_vendas->fetch_assoc()['total'] ?? 0;
    
    // Pagamentos do mesmo período
    $sql_pagamentos = "SELECT SUM(valor) as total 
                      FROM pagamentos 
                      WHERE MONTH(data_pagamento) = $mes 
                      AND YEAR(data_pagamento) = $ano";
    $result_pagamentos = $conn->query($sql_pagamentos);
    $total_pagamentos = $result_pagamentos->fetch_assoc()['total'] ?? 0;
    
    $meses[] = $nome_mes;
    $valores_vendas[] = $total_vendas;
    $valores_pagamentos[] = $total_pagamentos;
}

// Produtos mais vendidos
$sql_produtos_vendidos = "SELECT p.nome, SUM(iv.quantidade) as quantidade
                         FROM produtos p
                         JOIN itens_venda iv ON p.id = iv.produto_id
                         GROUP BY p.id
                         ORDER BY quantidade DESC
                         LIMIT 5";
$result_produtos_vendidos = $conn->query($sql_produtos_vendidos);

$produtos_nomes = [];
$produtos_quantidades = [];

while ($row = $result_produtos_vendidos->fetch_assoc()) {
    $produtos_nomes[] = $row['nome'];
    $produtos_quantidades[] = $row['quantidade'];
}

// Últimas vendas (com telefone para WhatsApp)
$sql_ultimas_vendas = "SELECT v.id, v.data_venda, v.status, c.nome as cliente_nome, c.telefone,
                      SUM(iv.quantidade * iv.valor_unitario) as valor_total,
                      COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.venda_id = v.id), 0) as total_pago
                      FROM vendas v
                      JOIN clientes c ON v.cliente_id = c.id
                      JOIN itens_venda iv ON v.id = iv.venda_id
                      GROUP BY v.id
                      ORDER BY v.data_venda DESC
                      LIMIT 8";
$result_ultimas_vendas = $conn->query($sql_ultimas_vendas);
$ultimas_vendas = [];

while ($row = $result_ultimas_vendas->fetch_assoc()) {
    $ultimas_vendas[] = $row;
}

// Maiores devedores (com telefone para WhatsApp)
$maiores_devedores = [];
$sql_clientes_devedores = "SELECT id, nome, empresa, telefone FROM clientes";
$result_clientes_devedores = $conn->query($sql_clientes_devedores);

if ($result_clientes_devedores && $result_clientes_devedores->num_rows > 0) {
    while ($cliente = $result_clientes_devedores->fetch_assoc()) {
        $cliente_id = $cliente['id'];
        $saldo_devedor_cliente = 0;
        
        // Buscar vendas em aberto do cliente
        $sql_vendas_cliente = "SELECT id FROM vendas WHERE cliente_id = $cliente_id AND status = 'aberto'";
        $result_vendas_cliente = $conn->query($sql_vendas_cliente);
        
        if ($result_vendas_cliente && $result_vendas_cliente->num_rows > 0) {
            while ($venda = $result_vendas_cliente->fetch_assoc()) {
                $venda_id = $venda['id'];
                
                // Calcular valor total da venda
                $sql_total = "SELECT SUM(quantidade * valor_unitario) as total FROM itens_venda WHERE venda_id = $venda_id";
                $result_total = $conn->query($sql_total);
                $total = $result_total->fetch_assoc()['total'] ?? 0;
                
                // Calcular pagamentos da venda
                $sql_pagos = "SELECT SUM(valor) as pago FROM pagamentos WHERE venda_id = $venda_id";
                $result_pagos = $conn->query($sql_pagos);
                $pago = $result_pagos->fetch_assoc()['pago'] ?? 0;
                
                // Adicionar ao saldo devedor do cliente
                $saldo_venda = max(0, $total - $pago);
                $saldo_devedor_cliente += $saldo_venda;
            }
        }
        
        // Adicionar cliente à lista de devedores se tiver saldo devedor
        if ($saldo_devedor_cliente > 0) {
            $cliente['saldo_devedor'] = $saldo_devedor_cliente;
            $maiores_devedores[] = $cliente;
        }
    }
    
    // Ordenar por saldo devedor (maior para menor)
    usort($maiores_devedores, function($a, $b) {
        return $b['saldo_devedor'] <=> $a['saldo_devedor'];
    });
    
    // Limitar a 5 resultados
    $maiores_devedores = array_slice($maiores_devedores, 0, 5);
}

// Definir a variável base_path se não estiver definida
if (!isset($base_path)) {
    $base_path = '';
    
    // Determinar o caminho base
    $current_dir = dirname($_SERVER['PHP_SELF']);
    if ($current_dir != '/' && $current_dir != '\\') {
        // Se estamos em um subdiretório, precisamos voltar para a raiz
        $dirs_up = substr_count($current_dir, '/');
        if ($dirs_up > 0) {
            $base_path = str_repeat('../', $dirs_up);
        }
    }
}

include 'includes/header.php';
?>

<style>
:root {
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-danger: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
    --shadow-soft: 0 2px 10px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 20px rgba(0,0,0,0.15);
    --border-radius: 15px;
}

.dashboard-header {
    background: var(--gradient-primary);
    color: white;
    padding: 2rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
    box-shadow: var(--shadow-soft);
}

.stats-card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    color: white;
    height: 180px;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.stats-card.primary { background: var(--gradient-primary); }
.stats-card.success { background: var(--gradient-success); }
.stats-card.warning { background: var(--gradient-warning); }
.stats-card.danger { background: var(--gradient-danger); }

.stats-icon {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 3rem;
    opacity: 0.3;
}

.stats-value {
    font-size: 2.2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stats-label {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
}

.stats-link {
    color: white;
    text-decoration: none;
    font-size: 0.9rem;
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.stats-link:hover {
    color: white;
    opacity: 1;
}

.chart-card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
}

.chart-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.chart-card .card-header {
    background: var(--gradient-info);
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    border: none;
    padding: 1.5rem;
}

.quick-actions-card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
}

.quick-actions-card .card-header {
    background: var(--gradient-success);
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    border: none;
    padding: 1.5rem;
}

.quick-action-btn {
    border-radius: 10px;
    padding: 1rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.sales-table-card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
}

.sales-table-card .card-header {
    background: var(--gradient-primary);
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    border: none;
    padding: 1.5rem;
}

.table-modern {
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    overflow: hidden;
}

.table-modern tbody tr {
    border: none;
    transition: background-color 0.3s ease;
}

.table-modern tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.1);
}

.client-avatar {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
    margin-right: 0.75rem;
}

.debtors-card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
}

.debtors-card .card-header {
    background: var(--gradient-danger);
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    border: none;
    padding: 1.5rem;
}

.debtor-item {
    padding: 1rem;
    border: none;
    transition: all 0.3s ease;
    border-radius: 10px;
    margin-bottom: 0.5rem;
}

.debtor-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-pago {
    background: var(--gradient-success);
    color: white;
}

.badge-aberto {
    background: var(--gradient-warning);
    color: white;
}

.badge-cancelado {
    background: var(--gradient-danger);
    color: white;
}

.progress-mini {
    height: 4px;
    background: rgba(255,255,255,0.3);
    border-radius: 2px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-mini-bar {
    height: 100%;
    background: rgba(255,255,255,0.8);
    transition: width 0.3s ease;
}

@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .stats-card {
        height: 140px;
        margin-bottom: 1rem;
    }
    
    .stats-value {
        font-size: 1.8rem;
    }
    
    .stats-icon {
        font-size: 2.5rem;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in {
    animation: fadeInUp 0.6s ease forwards;
}
</style>

<!-- Cabeçalho do Dashboard -->
<div class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </h1>
            <p class="mb-0 opacity-75">Visão geral do seu negócio em tempo real</p>
        </div>
        <div class="text-end">
            <div class="h4 mb-1"><?php echo date('d/m/Y'); ?></div>
            <small class="opacity-75"><?php echo date('H:i'); ?> • <?php echo date('l'); ?></small>
        </div>
    </div>
</div>

<!-- Resumo de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card primary animate-in" style="animation-delay: 0.1s">
            <div class="card-body position-relative">
                <div class="stats-label"><?php echo $label_vendas; ?></div>
                <div class="stats-value">R$ <?php echo number_format($vendas_dia, 2, ',', '.'); ?></div>
                <?php if ($vendas_dia == 0 && !empty($ultimo_dia_com_vendas)): ?>
                    <small class="opacity-75">
                        Último: <?php echo $ultimo_dia_com_vendas; ?><br>
                        R$ <?php echo number_format($valor_ultimo_dia, 2, ',', '.'); ?>
                    </small>
                <?php else: ?>
                    <div class="progress-mini">
                        <div class="progress-mini-bar" style="width: 75%"></div>
                    </div>
                <?php endif; ?>
                <div class="mt-2">
                    <a href="vendas/listar.php?data_inicio=<?php echo $data_vendas; ?>&data_fim=<?php echo $data_vendas; ?>" class="stats-link">
                        Ver detalhes <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <i class="fas fa-calendar-day stats-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card success animate-in" style="animation-delay: 0.2s">
            <div class="card-body position-relative">
                <div class="stats-label">Vendas do Mês</div>
                <div class="stats-value">R$ <?php echo number_format($vendas_mes, 2, ',', '.'); ?></div>
                <div class="progress-mini">
                    <div class="progress-mini-bar" style="width: <?php echo min(100, ($vendas_mes / 50000) * 100); ?>%"></div>
                </div>
                <div class="mt-2">
                    <a href="vendas/listar.php" class="stats-link">
                        Ver detalhes <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <i class="fas fa-shopping-cart stats-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card warning animate-in" style="animation-delay: 0.3s">
            <div class="card-body position-relative">
                <div class="stats-label">Recebido no Mês</div>
                <div class="stats-value">R$ <?php echo number_format($pagamentos_mes, 2, ',', '.'); ?></div>
                <?php 
                $taxa_recebimento = $vendas_mes > 0 ? ($pagamentos_mes / $vendas_mes) * 100 : 0;
                ?>
                <div class="progress-mini">
                    <div class="progress-mini-bar" style="width: <?php echo $taxa_recebimento; ?>%"></div>
                </div>
                <div class="mt-2">
                    <?php
                    $primeiro_dia_mes = date('Y-m-01');
                    $ultimo_dia_mes = date('Y-m-t');
                    ?>
                    <a href="pagamentos/listar.php?data_inicio=<?php echo $primeiro_dia_mes; ?>&data_fim=<?php echo $ultimo_dia_mes; ?>" class="stats-link">
                        Ver detalhes <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <i class="fas fa-hand-holding-usd stats-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card danger animate-in" style="animation-delay: 0.4s">
            <div class="card-body position-relative">
                <div class="stats-label">Saldo a Receber</div>
                <div class="stats-value">R$ <?php echo number_format($saldo_receber, 2, ',', '.'); ?></div>
                <div class="progress-mini">
                    <div class="progress-mini-bar" style="width: <?php echo min(100, ($saldo_receber / $vendas_mes) * 100); ?>%"></div>
                </div>
                <div class="mt-2">
                    <a href="vendas/listar.php?status=aberto" class="stats-link">
                        Ver detalhes <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <i class="fas fa-money-bill-wave stats-icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card chart-card animate-in" style="animation-delay: 0.5s">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Vendas vs Recebimentos - Últimos 6 Meses
                </h5>
            </div>
            <div class="card-body">
                <canvas id="vendasChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card chart-card animate-in" style="animation-delay: 0.6s">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2"></i>
                    Produtos Mais Vendidos
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($produtos_nomes)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma venda registrada ainda</p>
                    </div>
                <?php else: ?>
                    <canvas id="produtosChart" height="300"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Últimas Vendas e Ações Rápidas -->
<div class="row">
    <div class="col-md-8">
        <div class="card sales-table-card animate-in" style="animation-delay: 0.7s">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Últimas Vendas
                </h5>
                <a href="vendas/listar.php" class="btn btn-sm btn-light">
                    <i class="fas fa-external-link-alt me-1"></i> Ver Todas
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-modern">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimas_vendas)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Nenhuma venda encontrada</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($ultimas_vendas as $venda): 
                                    $saldo_venda = $venda['valor_total'] - $venda['total_pago'];
                                ?>
                                <tr>
                                    <td>
                                        <strong class="text-primary">#<?php echo $venda['id']; ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="client-avatar" style="background: linear-gradient(135deg, <?php echo sprintf('#%06X', crc32($venda['cliente_nome'])); ?>, <?php echo sprintf('#%06X', crc32($venda['cliente_nome']) + 100000); ?>);">
                                                <?php echo strtoupper(substr($venda['cliente_nome'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($venda['cliente_nome']); ?></strong>
                                                <?php if (!empty($venda['telefone'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($venda['telefone']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?php echo date('d/m/Y', strtotime($venda['data_venda'])); ?>
                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($venda['data_venda'])); ?></small>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <strong>R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></strong>
                                        <?php if ($saldo_venda > 0): ?>
                                            <br><small class="text-danger">Saldo: R$ <?php echo number_format($saldo_venda, 2, ',', '.'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge badge-<?php echo $venda['status']; ?>">
                                            <?php if ($venda['status'] == 'pago'): ?>
                                                <i class="fas fa-check me-1"></i>Pago
                                            <?php elseif ($venda['status'] == 'aberto'): ?>
                                                <i class="fas fa-clock me-1"></i>Em Aberto
                                            <?php elseif ($venda['status'] == 'cancelado'): ?>
                                                <i class="fas fa-ban me-1"></i>Cancelado
                                            <?php else: ?>
                                                <?php echo ucfirst($venda['status']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="vendas/detalhes.php?id=<?php echo $venda['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver Detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($venda['status'] == 'aberto' && $saldo_venda > 0): ?>
                                                <a href="vendas/registrar_pagamento.php?id=<?php echo $venda['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Receber Pagamento">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($venda['telefone'])): ?>
                                                <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', $venda['telefone']); ?>" 
                                                   target="_blank" class="btn btn-sm btn-outline-success" 
                                                   data-bs-toggle="tooltip" title="WhatsApp">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <!-- Ações Rápidas -->
        <div class="card quick-actions-card animate-in mb-4" style="animation-delay: 0.8s">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Ações Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="vendas/nova_venda.php" class="btn btn-primary quick-action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <div>
                            <strong>Nova Venda</strong>
                            <br><small>Registrar nova venda</small>
                        </div>
                    </a>
                    <a href="clientes/cadastrar.php" class="btn btn-success quick-action-btn">
                        <i class="fas fa-user-plus"></i>
                        <div>
                            <strong>Novo Cliente</strong>
                            <br><small>Cadastrar cliente</small>
                        </div>
                    </a>
                    <a href="produtos/cadastrar.php" class="btn btn-warning quick-action-btn text-white">
                        <i class="fas fa-box-open"></i>
                        <div>
                            <strong>Novo Produto</strong>
                            <br><small>Adicionar produto</small>
                        </div>
                    </a>
                    <a href="clientes/listar.php" class="btn btn-info quick-action-btn text-white">
                        <i class="fas fa-search"></i>
                        <div>
                            <strong>Buscar Cliente</strong>
                            <br><small>Localizar cliente</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Maiores Devedores -->
        <div class="card debtors-card animate-in" style="animation-delay: 0.9s">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Maiores Devedores
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($maiores_devedores)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-smile fa-3x text-success mb-3"></i>
                        <h5 class="text-success">Parabéns!</h5>
                        <p class="text-muted mb-0">Não há clientes com saldo devedor</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($maiores_devedores as $devedor): ?>
                        <div class="list-group-item debtor-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="client-avatar me-3" style="background: linear-gradient(135deg, <?php echo sprintf('#%06X', crc32($devedor['nome'])); ?>, <?php echo sprintf('#%06X', crc32($devedor['nome']) + 100000); ?>);">
                                    <?php echo strtoupper(substr($devedor['nome'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <a href="clientes/detalhes.php?id=<?php echo $devedor['id']; ?>" class="text-decoration-none">
                                        <strong><?php echo htmlspecialchars($devedor['nome']); ?></strong>
                                    </a>
                                    <?php if (!empty($devedor['empresa'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($devedor['empresa']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-danger rounded-pill">
                                        R$ <?php echo number_format($devedor['saldo_devedor'], 2, ',', '.'); ?>
                                    </div>
                                    <?php if (!empty($devedor['telefone'])): ?>
                                        <div class="mt-1">
                                            <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', $devedor['telefone']); ?>" 
                                               target="_blank" class="btn btn-sm btn-success">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para os gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Configuração de cores premium
const chartColors = {
    primary: '#667eea',
    success: '#38ef7d',
    warning: '#f5576c',
    info: '#00f2fe',
    danger: '#fc466b'
};

// Aguardar o DOM carregar completamente
document.addEventListener('DOMContentLoaded', function() {
    
    // Verificar se os elementos existem
    const vendasCanvas = document.getElementById('vendasChart');
    const produtosCanvas = document.getElementById('produtosChart');
    
    if (vendasCanvas) {
        // Dados para o gráfico de vendas vs recebimentos
        const vendasCtx = vendasCanvas.getContext('2d');
        const vendasChart = new Chart(vendasCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($meses); ?>,
                datasets: [
                    {
                        label: 'Vendas (R$)',
                        data: <?php echo json_encode($valores_vendas); ?>,
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderColor: chartColors.primary,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: chartColors.primary,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    },
                    {
                        label: 'Recebimentos (R$)',
                        data: <?php echo json_encode($valores_pagamentos); ?>,
                        backgroundColor: 'rgba(56, 239, 125, 0.1)',
                        borderColor: chartColors.success,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: chartColors.success,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        cornerRadius: 10,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.raw.toLocaleString('pt-BR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            },
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Dados para o gráfico de produtos (apenas se houver dados)
    const produtosNomes = <?php echo json_encode($produtos_nomes); ?>;
    const produtosQuantidades = <?php echo json_encode($produtos_quantidades); ?>;
    
    if (produtosCanvas && produtosNomes.length > 0 && produtosQuantidades.length > 0) {
        const produtosCtx = produtosCanvas.getContext('2d');
        const produtosChart = new Chart(produtosCtx, {
            type: 'doughnut',
            data: {
                labels: produtosNomes,
                datasets: [{
                    data: produtosQuantidades,
                    backgroundColor: [
                        chartColors.danger,
                        chartColors.primary,
                        chartColors.warning,
                        chartColors.success,
                        chartColors.info
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverBorderWidth: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} unidades (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Animações escalonadas
    const elements = document.querySelectorAll('.animate-in');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            el.style.transition = 'all 0.6s ease';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + N para nova venda
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = 'vendas/nova_venda.php';
        }
        
        // Ctrl/Cmd + R para atualizar (custom)
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            setTimeout(() => location.reload(), 500);
        }
        
        // F5 personalizado
        if (e.key === 'F5') {
            e.preventDefault();
            setTimeout(() => location.reload(), 500);
        }
    });
});

// Efeitos visuais adicionais
document.querySelectorAll('.stats-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(-5px) scale(1)';
    });
});

// Feedback visual para ações
document.querySelectorAll('.quick-action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        // Pequena animação de feedback
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
    });
});
</script>

<?php include 'includes/footer.php'; ?>