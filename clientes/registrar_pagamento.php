<?php
// clientes/registrar_pagamento.php - Versão Corrigida
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

// Verificar se o ID do cliente foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Cliente não especificado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$cliente_id = intval($_GET['id']);

// Buscar informações do cliente
$sql_cliente = "SELECT * FROM clientes WHERE id = $cliente_id";
$result_cliente = $conn->query($sql_cliente);

if (!$result_cliente || $result_cliente->num_rows == 0) {
    $_SESSION['msg'] = "Cliente não encontrado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$cliente = $result_cliente->fetch_assoc();

// 🔧 FUNÇÃO PARA CORRIGIR PRECISÃO DECIMAL
function formatarValor($valor_string) {
    // Remove espaços e substitui vírgula por ponto
    $valor = str_replace([' ', ','], ['', '.'], trim($valor_string));
    
    // Converte para float e depois para string com 2 casas decimais
    $valor_float = floatval($valor);
    
    // Arredonda para 2 casas decimais para evitar problemas de precisão
    return round($valor_float, 2);
}

// 🔧 CÁLCULO CORRIGIDO DO SALDO DEVEDOR
$sql_saldo = "SELECT 
              ROUND(COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0), 2) as total_compras,
              ROUND(COALESCE((SELECT SUM(p.valor) FROM pagamentos p 
                        JOIN vendas v2 ON p.venda_id = v2.id 
                        WHERE v2.cliente_id = $cliente_id), 0), 2) as total_pago
              FROM vendas v
              LEFT JOIN itens_venda iv ON v.id = iv.venda_id
              WHERE v.cliente_id = $cliente_id";
$result_saldo = $conn->query($sql_saldo);
$saldo_info = $result_saldo->fetch_assoc();
$total_compras = $saldo_info['total_compras'];
$total_pago = $saldo_info['total_pago'];
$saldo_devedor = round($total_compras - $total_pago, 2); // 🔧 ARREDONDAMENTO FORÇADO

// 🔧 BUSCAR VENDAS EM ABERTO COM VALORES ARREDONDADOS
$sql_vendas = "SELECT v.id, v.data_venda, 
               ROUND(COALESCE(SUM(iv.quantidade * iv.valor_unitario), 0), 2) as valor_total,
               ROUND(COALESCE((SELECT SUM(p.valor) FROM pagamentos p WHERE p.venda_id = v.id), 0), 2) as valor_pago
               FROM vendas v
               LEFT JOIN itens_venda iv ON v.id = iv.venda_id
               WHERE v.cliente_id = $cliente_id AND v.status = 'aberto'
               GROUP BY v.id
               ORDER BY v.data_venda ASC";
$result_vendas = $conn->query($sql_vendas);

// Processar o formulário de pagamento
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valor'])) {
    // 🔧 USAR FUNÇÃO CORRIGIDA PARA VALOR
    $valor = formatarValor($_POST['valor']);
    $observacao = isset($_POST['observacao']) ? $conn->real_escape_string($_POST['observacao']) : '';
    $data_pagamento = isset($_POST['data_pagamento']) ? $conn->real_escape_string($_POST['data_pagamento']) : date('Y-m-d');
    
    // Verificar se o cliente_id do formulário corresponde ao cliente_id da URL
    $form_cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
    
    // 🔧 DEBUG: Mostrar valores para depuração
    error_log("DEBUG: Valor recebido: " . $_POST['valor']);
    error_log("DEBUG: Valor formatado: " . $valor);
    error_log("DEBUG: Saldo devedor: " . $saldo_devedor);
    error_log("DEBUG: Comparação: " . ($valor > $saldo_devedor ? 'MAIOR' : 'MENOR/IGUAL'));
    
    if ($form_cliente_id != $cliente_id) {
        $msg = "Erro: ID do cliente não corresponde.";
        $msg_type = "danger";
    } elseif ($valor <= 0) {
        $msg = "O valor do pagamento deve ser maior que zero.";
        $msg_type = "danger";
    } elseif ($valor > $saldo_devedor && $saldo_devedor > 0) {
        // 🔧 MELHORIA NA MENSAGEM DE ERRO COM VALORES EXATOS
        $msg = "O valor do pagamento (R$ " . number_format($valor, 2, ',', '.') . 
               ") não pode ser maior que o saldo devedor (R$ " . number_format($saldo_devedor, 2, ',', '.') . ").";
        $msg_type = "danger";
    } else {
        // Reiniciar a consulta de vendas em aberto
        $result_vendas = $conn->query($sql_vendas);
        
        if ($result_vendas && $result_vendas->num_rows > 0) {
            $conn->begin_transaction();
            
            try {
                $valor_restante = $valor;
                $vendas_atualizadas = [];
                
                while ($venda = $result_vendas->fetch_assoc()) {
                    if ($valor_restante <= 0.01) break; // 🔧 TOLERÂNCIA DE 1 CENTAVO
                    
                    $saldo_venda = round($venda['valor_total'] - $venda['valor_pago'], 2); // 🔧 ARREDONDAMENTO
                    
                    if ($saldo_venda > 0.01) { // 🔧 TOLERÂNCIA DE 1 CENTAVO
                        $valor_aplicado = min($valor_restante, $saldo_venda);
                        $valor_aplicado = round($valor_aplicado, 2); // 🔧 ARREDONDAMENTO
                        
                        // 🔧 INSERIR PAGAMENTO COM VALOR ARREDONDADO
                        $sql_insert = "INSERT INTO pagamentos (venda_id, valor, data_pagamento, observacao) 
                                      VALUES ({$venda['id']}, $valor_aplicado, '$data_pagamento', '$observacao')";
                        
                        if ($conn->query($sql_insert)) {
                            $valor_restante = round($valor_restante - $valor_aplicado, 2); // 🔧 ARREDONDAMENTO
                            $vendas_atualizadas[] = $venda['id'];
                            
                            // 🔧 VERIFICAR SE A VENDA FOI TOTALMENTE PAGA COM TOLERÂNCIA
                            $novo_saldo = round($saldo_venda - $valor_aplicado, 2);
                            if ($novo_saldo <= 0.01) { // 🔧 TOLERÂNCIA DE 1 CENTAVO
                                $conn->query("UPDATE vendas SET status = 'pago' WHERE id = {$venda['id']}");
                            }
                        }
                    }
                }
                
                if (count($vendas_atualizadas) > 0) {
                    $conn->commit();
                    $msg = "Pagamento de R$ " . number_format($valor, 2, ',', '.') . " registrado com sucesso!";
                    if ($valor_restante > 0.01) {
                        $msg .= " (Valor restante: R$ " . number_format($valor_restante, 2, ',', '.') . ")";
                    }
                    $msg_type = "success";
                    
                    // Redirecionar para a página de detalhes do cliente
                    $_SESSION['msg'] = $msg;
                    $_SESSION['msg_type'] = $msg_type;
                    header("Location: detalhes.php?id=$cliente_id");
                    exit;
                } else {
                    throw new Exception("Nenhuma venda foi atualizada.");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "Erro ao registrar pagamento: " . $e->getMessage();
                $msg_type = "danger";
            }
        } else {
            $msg = "Não há vendas em aberto para este cliente.";
            $msg_type = "warning";
        }
    }
}

// Buscar histórico de pagamentos do cliente
$sql_historico = "SELECT p.id, p.venda_id, ROUND(p.valor, 2) as valor, p.data_pagamento, p.observacao
                 FROM pagamentos p
                 JOIN vendas v ON p.venda_id = v.id
                 WHERE v.cliente_id = $cliente_id
                 ORDER BY p.data_pagamento DESC, p.id DESC
                 LIMIT 20";
$result_historico = $conn->query($sql_historico);

// Verificar se há mensagens na sessão
if (!$msg && isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msg_type = $_SESSION['msg_type'];
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
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
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
}

.card-modern {
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
}

.card-modern:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.card-header-gradient {
    background: var(--gradient-primary);
    color: white;
    border: none;
    padding: 1.5rem;
}

.card-header-gradient.success {
    background: var(--gradient-success);
}

.card-header-gradient.info {
    background: var(--gradient-info);
}

.card-header-gradient.secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}

.form-control-modern {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-modern {
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.btn-primary-modern {
    background: var(--gradient-primary);
    color: white;
}

.btn-success-modern {
    background: var(--gradient-success);
    color: white;
}

.table-modern {
    border-radius: 10px;
    overflow: hidden;
}

.table-modern thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.table-modern tbody tr {
    transition: all 0.3s ease;
}

.table-modern tbody tr:hover {
    background: rgba(102, 126, 234, 0.05);
    transform: scale(1.01);
}

.saldo-indicator {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: bold;
    display: inline-block;
    margin-top: 0.5rem;
}

.saldo-positivo {
    background: var(--gradient-success);
    color: white;
}

.saldo-negativo {
    background: var(--gradient-danger);
    color: white;
}

.valor-destaque {
    font-size: 1.2rem;
    font-weight: bold;
    padding: 0.5rem;
    border-radius: 8px;
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.info-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #6c757d;
}

.info-value {
    font-weight: 600;
    color: #495057;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.slide-in {
    animation: slideInUp 0.5s ease-out forwards;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 slide-in">
    <div>
        <h1 class="mb-1">
            <i class="fas fa-money-bill-wave text-success me-2"></i>
            Registrar Pagamento
        </h1>
        <p class="text-muted mb-0">Cliente: <strong><?php echo htmlspecialchars($cliente['nome']); ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="detalhes.php?id=<?php echo $cliente_id; ?>" class="btn btn-outline-secondary btn-modern">
            <i class="fas fa-arrow-left me-2"></i> Voltar para Detalhes
        </a>
        <a href="listar.php" class="btn btn-outline-primary btn-modern">
            <i class="fas fa-list me-2"></i> Lista de Clientes
        </a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show slide-in" role="alert">
        <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : ($msg_type == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Informações do Cliente e Formulário -->
    <div class="col-lg-6 mb-4">
        <!-- Informações do Cliente -->
        <div class="card card-modern slide-in" style="animation-delay: 0.1s;">
            <div class="card-header card-header-gradient">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    Informações do Cliente
                </h5>
            </div>
            <div class="card-body">
                <div class="info-item">
                    <span class="info-label">Nome:</span>
                    <span class="info-value"><?php echo htmlspecialchars($cliente['nome']); ?></span>
                </div>
                
                <?php if (!empty($cliente['empresa'])): ?>
                <div class="info-item">
                    <span class="info-label">Empresa:</span>
                    <span class="info-value"><?php echo htmlspecialchars($cliente['empresa']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($cliente['telefone'])): ?>
                <div class="info-item">
                    <span class="info-label">Telefone:</span>
                    <span class="info-value">
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $cliente['telefone']); ?>" 
                           target="_blank" class="text-success text-decoration-none">
                            <i class="fab fa-whatsapp me-1"></i>
                            <?php echo htmlspecialchars($cliente['telefone']); ?>
                        </a>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <span class="info-label">Limite de Compra:</span>
                    <span class="info-value">R$ <?php echo number_format($cliente['limite_compra'], 2, ',', '.'); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Total de Compras:</span>
                    <span class="info-value text-primary">R$ <?php echo number_format($total_compras, 2, ',', '.'); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Total Pago:</span>
                    <span class="info-value text-success">R$ <?php echo number_format($total_pago, 2, ',', '.'); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Saldo Devedor:</span>
                    <span class="saldo-indicator <?php echo $saldo_devedor > 0 ? 'saldo-negativo' : 'saldo-positivo'; ?>">
                        R$ <?php echo number_format($saldo_devedor, 2, ',', '.'); ?>
                    </span>
                </div>
                
                <!-- Status do Cliente -->
                <div class="mt-3">
                    <?php if ($saldo_devedor <= 0): ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Cliente em dia!</strong><br>
                            Não possui débitos pendentes.
                        </div>
                    <?php elseif ($saldo_devedor > $cliente['limite_compra']): ?>
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Limite excedido!</strong><br>
                            O cliente excedeu o limite de compra em R$ <?php echo number_format($saldo_devedor - $cliente['limite_compra'], 2, ',', '.'); ?>.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Débitos pendentes</strong><br>
                            Limite disponível: R$ <?php echo number_format($cliente['limite_compra'] - $saldo_devedor, 2, ',', '.'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Formulário de Pagamento -->
        <?php if ($saldo_devedor > 0): ?>
            <div class="card card-modern slide-in mt-4" style="animation-delay: 0.2s;">
                <div class="card-header card-header-gradient success">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Registrar Novo Pagamento
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="registrar_pagamento.php?id=<?php echo $cliente_id; ?>" id="formPagamento">
                        <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                        
                        <div class="mb-4">
                            <label for="valor" class="form-label fw-bold">Valor do Pagamento (R$)</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control form-control-modern" id="valor" name="valor" required 
                                       placeholder="0,00" autocomplete="off">
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Valor máximo: <strong>R$ <?php echo number_format($saldo_devedor, 2, ',', '.'); ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="data_pagamento" class="form-label fw-bold">Data do Pagamento</label>
                            <input type="date" class="form-control form-control-modern" id="data_pagamento" name="data_pagamento" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="observacao" class="form-label fw-bold">Observação</label>
                            <textarea class="form-control form-control-modern" id="observacao" name="observacao" rows="3"
                                      placeholder="Digite uma observação sobre este pagamento (opcional)"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success-modern btn-modern" id="btnConfirmar">
                                <i class="fas fa-check-circle me-2"></i> Confirmar Pagamento
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="preencherValorTotal()">
                                <i class="fas fa-percentage me-2"></i> Pagar Valor Total (R$ <?php echo number_format($saldo_devedor, 2, ',', '.'); ?>)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Vendas em Aberto e Histórico -->
    <div class="col-lg-6 mb-4">
        <!-- Vendas em Aberto -->
        <div class="card card-modern slide-in" style="animation-delay: 0.3s;">
            <div class="card-header card-header-gradient info">
                <h5 class="mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Vendas em Aberto
                </h5>
            </div>
            <div class="card-body">
                <?php 
                // Reiniciar o resultado das vendas
                $result_vendas = $conn->query($sql_vendas);
                if ($result_vendas && $result_vendas->num_rows > 0): 
                ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Venda</th>
                                    <th>Data</th>
                                    <th class="text-end">Valor Total</th>
                                    <th class="text-end">Pago</th>
                                    <th class="text-end">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_saldo_vendas = 0;
                                while ($venda = $result_vendas->fetch_assoc()): 
                                    $saldo_venda = round($venda['valor_total'] - $venda['valor_pago'], 2);
                                    $total_saldo_vendas += $saldo_venda;
                                ?>
                                    <tr>
                                        <td>
                                            <a href="../vendas/detalhes.php?id=<?php echo $venda['id']; ?>" 
                                               class="text-decoration-none fw-bold">
                                                #<?php echo $venda['id']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($venda['data_venda'])); ?></td>
                                        <td class="text-end">R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></td>
                                        <td class="text-end text-success">R$ <?php echo number_format($venda['valor_pago'], 2, ',', '.'); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-danger">R$ <?php echo number_format($saldo_venda, 2, ',', '.'); ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <td colspan="4" class="fw-bold">Total em Aberto:</td>
                                    <td class="text-end fw-bold text-danger">
                                        R$ <?php echo number_format($total_saldo_vendas, 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h5>Parabéns!</h5>
                        <p class="text-muted mb-0">Não há vendas em aberto para este cliente.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Histórico de Pagamentos -->
        <div class="card card-modern slide-in mt-4" style="animation-delay: 0.4s;">
            <div class="card-header card-header-gradient secondary">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Histórico de Pagamentos (Últimos 20)
                </h5>
            </div>
            <div class="card-body">
                <?php if ($result_historico && $result_historico->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Venda</th>
                                    <th class="text-end">Valor</th>
                                    <th>Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pagamento = $result_historico->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?>
                                            <br><small class="text-muted"><?php echo date('H:i', strtotime($pagamento['data_pagamento'])); ?></small>
                                        </td>
                                        <td>
                                            <a href="../vendas/detalhes.php?id=<?php echo $pagamento['venda_id']; ?>" 
                                               class="text-decoration-none">
                                                #<?php echo $pagamento['venda_id']; ?>
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-success">R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($pagamento['observacao'] ?: 'Sem observações'); ?></small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-money-bill-wave text-muted fa-3x mb-3"></i>
                        <h6>Nenhum pagamento registrado</h6>
                        <p class="text-muted mb-0">Este será o primeiro pagamento deste cliente.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// 🔧 MÁSCARA PARA VALOR MONETÁRIO COM PRECISÃO
document.getElementById('valor').addEventListener('input', function(e) {
    let value = e.target.value;
    
    // Remove caracteres não numéricos exceto vírgula
    value = value.replace(/[^\d,]/g, '');
    
    // Remove vírgulas extras, mantendo apenas a primeira
    const parts = value.split(',');
    if (parts.length > 2) {
        value = parts[0] + ',' + parts.slice(1).join('');
    }
    
    // Limita as casas decimais a 2
    if (parts.length === 2 && parts[1].length > 2) {
        value = parts[0] + ',' + parts[1].substring(0, 2);
    }
    
    e.target.value = value;
    
    // Validação em tempo real
    validarValor();
});

// 🔧 FUNÇÃO DE VALIDAÇÃO EM TEMPO REAL
function validarValor() {
    const valorInput = document.getElementById('valor');
    const btnConfirmar = document.getElementById('btnConfirmar');
    const saldoDevedor = <?php echo $saldo_devedor; ?>;
    
    let valor = valorInput.value.replace(',', '.');
    valor = parseFloat(valor) || 0;
    
    // Arredondar para 2 casas decimais
    valor = Math.round(valor * 100) / 100;
    
    if (valor <= 0) {
        valorInput.classList.add('is-invalid');
        valorInput.classList.remove('is-valid');
        btnConfirmar.disabled = true;
        showTooltip(valorInput, 'O valor deve ser maior que zero');
    } else if (valor > saldoDevedor) {
        valorInput.classList.add('is-invalid');
        valorInput.classList.remove('is-valid');
        btnConfirmar.disabled = true;
        showTooltip(valorInput, `Valor máximo: R$ ${saldoDevedor.toFixed(2).replace('.', ',')}`);
    } else {
        valorInput.classList.remove('is-invalid');
        valorInput.classList.add('is-valid');
        btnConfirmar.disabled = false;
        hideTooltip(valorInput);
    }
}

// Função para mostrar tooltip de erro
function showTooltip(element, message) {
    // Remove tooltip existente
    hideTooltip(element);
    
    const tooltip = document.createElement('div');
    tooltip.className = 'invalid-tooltip';
    tooltip.textContent = message;
    tooltip.style.cssText = 'position: absolute; z-index: 1000; background: #dc3545; color: white; padding: 0.5rem; border-radius: 5px; font-size: 0.875rem; top: 100%; left: 0; margin-top: 0.25rem;';
    
    element.parentNode.style.position = 'relative';
    element.parentNode.appendChild(tooltip);
}

// Função para esconder tooltip
function hideTooltip(element) {
    const tooltip = element.parentNode.querySelector('.invalid-tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// 🔧 FUNÇÃO PARA PREENCHER VALOR TOTAL
function preencherValorTotal() {
    const valorInput = document.getElementById('valor');
    const saldoDevedor = <?php echo $saldo_devedor; ?>;
    const valorFormatado = saldoDevedor.toFixed(2).replace('.', ',');
    
    valorInput.value = valorFormatado;
    valorInput.focus();
    validarValor();
    
    // Animação de destaque
    valorInput.style.background = 'rgba(40, 167, 69, 0.1)';
    setTimeout(() => {
        valorInput.style.background = '';
    }, 1000);
}

// 🔧 VALIDAÇÃO DO FORMULÁRIO ANTES DO ENVIO
document.getElementById('formPagamento').addEventListener('submit', function(e) {
    const valorInput = document.getElementById('valor');
    let valor = valorInput.value.replace(',', '.');
    valor = parseFloat(valor) || 0;
    
    // Arredondar para 2 casas decimais
    valor = Math.round(valor * 100) / 100;
    
    const saldoDevedor = <?php echo $saldo_devedor; ?>;
    
    if (valor <= 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Valor Inválido',
            text: 'O valor do pagamento deve ser maior que zero.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    if (valor > saldoDevedor) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Valor Excede Saldo',
            html: `O valor do pagamento (R$ ${valor.toFixed(2).replace('.', ',')}) não pode ser maior que o saldo devedor (R$ ${saldoDevedor.toFixed(2).replace('.', ',')}).`,
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    // 🔧 CONFIRMAÇÃO COM VALORES PRECISOS
    e.preventDefault();
    Swal.fire({
        title: 'Confirmar Pagamento',
        html: `
            <div class="text-start">
                <p><strong>Cliente:</strong> <?php echo addslashes($cliente['nome']); ?></p>
                <p><strong>Valor do Pagamento:</strong> <span class="text-success">R$ ${valor.toFixed(2).replace('.', ',')}</span></p>
                <p><strong>Saldo Atual:</strong> R$ ${saldoDevedor.toFixed(2).replace('.', ',')}</p>
                <p><strong>Saldo Após Pagamento:</strong> <span class="text-primary">R$ ${(saldoDevedor - valor).toFixed(2).replace('.', ',')}</span></p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check me-2"></i>Confirmar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Processando Pagamento...',
                text: 'Aguarde enquanto o pagamento é registrado.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submeter o formulário
            document.getElementById('formPagamento').submit();
        }
    });
});

// Sistema de notificações
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + Enter para confirmar pagamento
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        const btnConfirmar = document.getElementById('btnConfirmar');
        if (btnConfirmar && !btnConfirmar.disabled) {
            btnConfirmar.click();
        }
    }
    
    // Ctrl/Cmd + T para pagar valor total
    if ((e.ctrlKey || e.metaKey) && e.key === 't') {
        e.preventDefault();
        preencherValorTotal();
    }
    
    // Esc para voltar
    if (e.key === 'Escape') {
        window.location.href = 'detalhes.php?id=<?php echo $cliente_id; ?>';
    }
});

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Focar no campo valor se há saldo devedor
    <?php if ($saldo_devedor > 0): ?>
        setTimeout(() => {
            document.getElementById('valor').focus();
        }, 500);
    <?php endif; ?>
    
    // Animações de entrada escalonadas
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
    
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Mostrar dica de atalhos se há saldo devedor
    <?php if ($saldo_devedor > 0): ?>
        setTimeout(() => {
            showNotification('💡 Dicas: Use Ctrl+T para valor total, Ctrl+Enter para confirmar', 'info');
        }, 2000);
    <?php endif; ?>
    
    // 🔧 DEBUG: Mostrar valores no console
    console.log('Saldo Devedor:', <?php echo $saldo_devedor; ?>);
    console.log('Total Compras:', <?php echo $total_compras; ?>);
    console.log('Total Pago:', <?php echo $total_pago; ?>);
});

// Adicionar SweetAlert2 se não estiver incluído
if (typeof Swal === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}

// 🔧 FUNÇÃO PARA DESTACAR CAMPOS INVÁLIDOS
function destacarCampoInvalido(campo, mensagem) {
    campo.classList.add('is-invalid');
    campo.focus();
    
    setTimeout(() => {
        campo.classList.remove('is-invalid');
    }, 3000);
}

// 🔧 AUTO-SAVE DO FORMULÁRIO (salvar no localStorage como backup)
document.getElementById('observacao').addEventListener('input', function() {
    localStorage.setItem('pagamento_observacao_backup', this.value);
});

// Recuperar observação se existir
document.addEventListener('DOMContentLoaded', function() {
    const observacaoBackup = localStorage.getItem('pagamento_observacao_backup');
    if (observacaoBackup) {
        document.getElementById('observacao').value = observacaoBackup;
    }
});

// Limpar backup após envio bem-sucedido
<?php if ($msg_type === 'success'): ?>
    localStorage.removeItem('pagamento_observacao_backup');
<?php endif; ?>
</script>

<!-- SweetAlert2 para confirmações -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include '../includes/footer.php'; ?>