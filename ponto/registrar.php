<?php
// ponto/registrar.php - Sistema de Registro de Ponto (ADAPTADO)
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

// Verificar se é admin
$is_admin = isset($_SESSION['nivel_acesso']) && ($_SESSION['nivel_acesso'] == 'admin');

// Obter funcionário atual (se não for admin, só pode ver o próprio ponto)
$funcionario_id = $is_admin && isset($_GET['funcionario_id']) ? 
    (int)$_GET['funcionario_id'] : $_SESSION['funcionario_id'] ?? null;

// Se não há funcionário vinculado ao usuário e não é admin
if (!$funcionario_id && !$is_admin) {
    $_SESSION['msg'] = "Usuário não vinculado a um funcionário. Contate o administrador.";
    $_SESSION['msg_type'] = "warning";
    header("Location: ../dashboard.php");
    exit;
}

// Para admin: se não especificou funcionário, mostrar seletor
if ($is_admin && !$funcionario_id) {
    // Buscar funcionários ativos
    $sql_funcionarios = "SELECT id, nome, codigo, cargo FROM funcionarios WHERE status = 'ativo' ORDER BY nome";
    $funcionarios = $conn->query($sql_funcionarios)->fetch_all(MYSQLI_ASSOC);
} else {
    // Buscar dados do funcionário específico
    try {
        $sql_funcionario = "SELECT * FROM funcionarios WHERE id = ? AND status = 'ativo'";
        $stmt = $conn->prepare($sql_funcionario);
        $stmt->bind_param("i", $funcionario_id);
        $stmt->execute();
        $funcionario = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$funcionario) {
            $_SESSION['msg'] = "Funcionário não encontrado ou inativo.";
            $_SESSION['msg_type'] = "danger";
            header("Location: ../dashboard.php");
            exit;
        }
        
        // Buscar registro de hoje (ADAPTADO para sua estrutura)
        $data_hoje = date('Y-m-d');
        $sql_ponto_hoje = "SELECT * FROM ponto_registros WHERE funcionario_id = ? AND data_registro = ?";
        $stmt = $conn->prepare($sql_ponto_hoje);
        $stmt->bind_param("is", $funcionario_id, $data_hoje);
        $stmt->execute();
        $ponto_hoje = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Buscar horário esperado para hoje
        $dia_semana = date('N'); // 1=Segunda, 7=Domingo
        $sql_horario = "SELECT * FROM horarios_trabalho WHERE funcionario_id = ? AND dia_semana = ? AND ativo = TRUE";
        $stmt = $conn->prepare($sql_horario);
        $stmt->bind_param("ii", $funcionario_id, $dia_semana);
        $stmt->execute();
        $horario_esperado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Buscar configurações do sistema
        $sql_config = "SELECT * FROM configuracoes_ponto WHERE id = 1";
        $config_ponto = $conn->query($sql_config)->fetch_assoc();
        
        // Buscar últimos registros (5 dias) - ADAPTADO
        $sql_historico = "SELECT pr.*, 
                            CASE 
                                WHEN pr.entrada_manha IS NULL THEN 'Falta'
                                WHEN pr.saida_final IS NULL THEN 'Incompleto'
                                ELSE 'Completo'
                            END as status_desc
                          FROM ponto_registros pr 
                          WHERE pr.funcionario_id = ? 
                          AND pr.data_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                          AND pr.data_registro < CURDATE()
                          ORDER BY pr.data_registro DESC 
                          LIMIT 5";
        
        $stmt = $conn->prepare($sql_historico);
        $stmt->bind_param("i", $funcionario_id);
        $stmt->execute();
        $historico = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
    } catch (Exception $e) {
        $_SESSION['msg'] = "Erro ao carregar dados: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
        $funcionario = null;
        $ponto_hoje = null;
        $horario_esperado = null;
        $historico = [];
    }
}

// Processar registro de ponto (ADAPTADO para sua estrutura)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $funcionario_id) {
    try {
        $acao = $_POST['acao'];
        $data_registro = $_POST['data_registro'] ?? date('Y-m-d');
        $observacao = trim($_POST['observacao'] ?? '');
        $hora_atual = date('H:i:s');
        $ip_usuario = $_SERVER['REMOTE_ADDR'];
        
        // Verificar se já existe registro para a data
        $sql_check = "SELECT * FROM ponto_registros WHERE funcionario_id = ? AND data_registro = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("is", $funcionario_id, $data_registro);
        $stmt->execute();
        $registro_existente = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$registro_existente) {
            // Criar novo registro
            $sql_create = "INSERT INTO ponto_registros (funcionario_id, data_registro, registrado_por, ip_registro) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_create);
            $stmt->bind_param("isis", $funcionario_id, $data_registro, $_SESSION['usuario_id'], $ip_usuario);
            $stmt->execute();
            $stmt->close();
            
            // Buscar o registro criado
            $stmt = $conn->prepare($sql_check);
            $stmt->bind_param("is", $funcionario_id, $data_registro);
            $stmt->execute();
            $registro_existente = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        
        // Atualizar registro baseado na ação (ADAPTADO)
        $sql_update = "";
        $success_msg = "";
        
        switch ($acao) {
            case 'entrada':
                if ($registro_existente['entrada_manha']) {
                    throw new Exception('Entrada já registrada para hoje.');
                }
                $sql_update = "UPDATE ponto_registros SET entrada_manha = ?, observacoes = ? WHERE id = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("ssi", $hora_atual, $observacao, $registro_existente['id']);
                $success_msg = "Entrada registrada às " . date('H:i');
                break;
                
            case 'almoco_saida':
                if (!$registro_existente['entrada_manha']) {
                    throw new Exception('Registre a entrada primeiro.');
                }
                if ($registro_existente['saida_almoco']) {
                    throw new Exception('Saída para almoço já registrada.');
                }
                $sql_update = "UPDATE ponto_registros SET saida_almoco = ? WHERE id = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("si", $hora_atual, $registro_existente['id']);
                $success_msg = "Saída para almoço registrada às " . date('H:i');
                break;
                
            case 'almoco_volta':
                if (!$registro_existente['saida_almoco']) {
                    throw new Exception('Registre a saída para almoço primeiro.');
                }
                if ($registro_existente['entrada_tarde']) {
                    throw new Exception('Volta do almoço já registrada.');
                }
                $sql_update = "UPDATE ponto_registros SET entrada_tarde = ? WHERE id = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("si", $hora_atual, $registro_existente['id']);
                $success_msg = "Volta do almoço registrada às " . date('H:i');
                break;
                
            case 'saida':
                if (!$registro_existente['entrada_manha']) {
                    throw new Exception('Registre a entrada primeiro.');
                }
                if ($registro_existente['saida_final']) {
                    throw new Exception('Saída já registrada para hoje.');
                }
                
                $sql_update = "UPDATE ponto_registros SET saida_final = ? WHERE id = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("si", $hora_atual, $registro_existente['id']);
                $success_msg = "Saída registrada às " . date('H:i');
                break;
                
            default:
                throw new Exception('Ação inválida.');
        }
        
        if ($stmt->execute()) {
            $_SESSION['msg'] = $success_msg;
            $_SESSION['msg_type'] = "success";
        } else {
            throw new Exception('Erro ao registrar ponto: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Redirecionar para evitar resubmissão
        header("Location: registrar.php" . ($is_admin && $funcionario_id ? "?funcionario_id=$funcionario_id" : ""));
        exit;
        
    } catch (Exception $e) {
        $_SESSION['msg'] = $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
}

// Função para formatar tempo
function formatarTempo($time) {
    if (!$time) return '--:--';
    return date('H:i', strtotime($time));
}

// Função para calcular diferença de tempo
function calcularDiferenca($esperado, $real) {
    if (!$esperado || !$real) return null;
    
    $timestamp_esperado = strtotime($esperado);
    $timestamp_real = strtotime($real);
    $diferenca = $timestamp_real - $timestamp_esperado;
    
    if ($diferenca > 0) {
        return '+' . gmdate('H:i', $diferenca);
    } elseif ($diferenca < 0) {
        return '-' . gmdate('H:i', abs($diferenca));
    }
    return '00:00';
}

// Incluir o cabeçalho
include_once '../includes/header.php';
?>

<style>
:root {
    --ponto-gradient: linear-gradient(135deg, #FF6B6B 0%, #FFD93D 100%);
    --entrada-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --almoco-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --saida-gradient: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
    --border-radius: 15px;
}

.ponto-header {
    background: var(--ponto-gradient);
    color: white;
    padding: 40px 0;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.ponto-header::before {
    content: '🕐';
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 4rem;
    opacity: 0.3;
    animation: tick 2s ease-in-out infinite;
}

@keyframes tick {
    0%, 100% { transform: rotate(-5deg); }
    50% { transform: rotate(5deg); }
}

.clock-display {
    background: white;
    border-radius: var(--border-radius);
    padding: 30px;
    text-align: center;
    box-shadow: var(--shadow-soft);
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.clock-display::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,107,107,0.05) 0%, rgba(255,217,61,0.05) 100%);
    pointer-events: none;
}

.time-current {
    font-size: 4rem;
    font-weight: bold;
    color: #FF6B6B;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 10px;
    font-family: 'Courier New', monospace;
}

.date-current {
    font-size: 1.2rem;
    color: #666;
    margin-bottom: 15px;
}

.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.entrada { background: var(--entrada-gradient); color: white; }
.status-badge.almoco { background: var(--almoco-gradient); color: white; }
.status-badge.trabalho { background: var(--ponto-gradient); color: white; }
.status-badge.saida { background: var(--saida-gradient); color: white; }

.registro-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--shadow-soft);
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.registro-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.funcionario-info {
    background: linear-gradient(135deg, rgba(255,107,107,0.1), rgba(255,217,61,0.1));
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid #FF6B6B;
}

.btn-ponto {
    border: none;
    color: white;
    border-radius: 12px;
    padding: 15px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: block;
    text-align: center;
    margin-bottom: 10px;
    position: relative;
    overflow: hidden;
}

.btn-ponto:hover {
    transform: translateY(-2px);
    color: white;
}

.btn-ponto:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-entrada { background: var(--entrada-gradient); }
.btn-entrada:hover { box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4); }

.btn-almoco-saida { background: var(--almoco-gradient); }
.btn-almoco-saida:hover { box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); }

.btn-almoco-volta { background: var(--almoco-gradient); }
.btn-almoco-volta:hover { box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4); }

.btn-saida { background: var(--saida-gradient); }
.btn-saida:hover { box-shadow: 0 8px 25px rgba(252, 70, 107, 0.4); }

.horario-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.horario-item:last-child {
    border-bottom: none;
}

.horario-label {
    font-weight: 600;
    color: #333;
}

.horario-valor {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    font-size: 1.1rem;
}

.horario-esperado {
    color: #666;
    font-size: 0.9rem;
}

.horario-real {
    color: #FF6B6B;
}

.horario-diferenca {
    font-size: 0.8rem;
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: 8px;
}

.horario-diferenca.positivo {
    background: #ffe6e6;
    color: #d63384;
}

.horario-diferenca.negativo {
    background: #e6f7ff;
    color: #0969da;
}

.historico-table {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-soft);
}

.table-modern {
    margin-bottom: 0;
}

.table-modern thead th {
    background: var(--ponto-gradient);
    color: white;
    border: none;
    padding: 15px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table-modern tbody td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.table-modern tbody tr:hover {
    background-color: rgba(255, 107, 107, 0.05);
}

.seletor-funcionario {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-soft);
    text-align: center;
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

@media (max-width: 768px) {
    .time-current {
        font-size: 2.5rem;
    }
    
    .ponto-header {
        padding: 20px 0;
    }
    
    .btn-ponto {
        padding: 12px 20px;
    }
    
    .clock-display {
        padding: 20px;
    }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Controle de Ponto</li>
    </ol>
</nav>

<!-- Header -->
<div class="ponto-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fas fa-clock me-3"></i>
                    Controle de Ponto
                </h1>
                <p class="mb-0 fs-5">
                    Sistema de registro de horários de trabalho
                </p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($is_admin): ?>
                    <a href="relatorios.php" class="btn btn-light btn-lg">
                        <i class="fas fa-chart-line me-2"></i>Relatórios
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($is_admin && !$funcionario_id): ?>
    <!-- Seletor de Funcionário para Admin -->
    <div class="seletor-funcionario fade-in">
        <h4 class="mb-3">
            <i class="fas fa-user-clock me-2 text-primary"></i>
            Selecione um Funcionário
        </h4>
        <form method="GET" action="registrar.php">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-select form-select-lg" id="funcionario_id" name="funcionario_id" required>
                            <option value="">Escolha o funcionário</option>
                            <?php foreach ($funcionarios as $func): ?>
                                <option value="<?php echo $func['id']; ?>">
                                    <?php echo htmlspecialchars($func['codigo'] . ' - ' . $func['nome'] . ' (' . $func['cargo'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="funcionario_id">Funcionário</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-search"></i> Ver Ponto
                    </button>
                </div>
            </div>
        </form>
    </div>
    
<?php elseif ($funcionario): ?>
    <!-- Informações do Funcionário -->
    <div class="funcionario-info fade-in">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1">
                    <i class="fas fa-user me-2"></i>
                    <?php echo htmlspecialchars($funcionario['nome']); ?>
                </h5>
                <p class="mb-0 text-muted">
                    <?php echo htmlspecialchars($funcionario['codigo'] . ' - ' . $funcionario['cargo'] . ' - ' . $funcionario['departamento']); ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($horario_esperado): ?>
                    <small class="text-muted">
                        Horário: <?php echo formatarTempo($horario_esperado['hora_entrada']); ?> às <?php echo formatarTempo($horario_esperado['hora_saida']); ?>
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Relógio e Status Atual -->
    <div class="clock-display fade-in">
        <div class="time-current" id="currentTime"><?php echo date('H:i:s'); ?></div>
        <div class="date-current"><?php echo date('l, d \d\e F \d\e Y'); ?></div>
        
        <?php if ($ponto_hoje): ?>
            <?php
            $status_atual = 'entrada';
            if ($ponto_hoje['saida_final']) {
                $status_atual = 'saida';
            } elseif ($ponto_hoje['entrada_tarde']) {
                $status_atual = 'trabalho';
            } elseif ($ponto_hoje['saida_almoco']) {
                $status_atual = 'almoco';
            } elseif ($ponto_hoje['entrada_manha']) {
                $status_atual = 'trabalho';
            }
            ?>
            <div class="status-badge <?php echo $status_atual; ?>">
                <?php
                switch ($status_atual) {
                    case 'saida': echo 'Expediente Finalizado'; break;
                    case 'almoco': echo 'Intervalo de Almoço'; break;
                    case 'trabalho': echo 'Em Trabalho'; break;
                    default: echo 'Aguardando Entrada';
                }
                ?>
            </div>
        <?php else: ?>
            <div class="status-badge entrada">Aguardando Entrada</div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Coluna dos Botões de Registro -->
        <div class="col-lg-4">
            <div class="registro-card fade-in">
                <h5 class="mb-3">
                    <i class="fas fa-hand-pointer me-2 text-primary"></i>
                    Registrar Ponto
                </h5>
                
                <form method="POST" action="registrar.php<?php echo $is_admin ? '?funcionario_id=' . $funcionario_id : ''; ?>">
                    <input type="hidden" name="data_registro" value="<?php echo date('Y-m-d'); ?>">
                    
                    <!-- Entrada -->
                    <button type="submit" name="acao" value="entrada" 
                            class="btn btn-ponto btn-entrada w-100 <?php echo ($ponto_hoje && $ponto_hoje['entrada_manha']) ? 'pulse' : ''; ?>"
                            <?php echo ($ponto_hoje && $ponto_hoje['entrada_manha']) ? 'disabled' : ''; ?>>
                        <i class="fas fa-sign-in-alt me-2"></i>
                        <?php echo ($ponto_hoje && $ponto_hoje['entrada_manha']) ? 'Entrada: ' . formatarTempo($ponto_hoje['entrada_manha']) : 'Registrar Entrada'; ?>
                    </button>
                    
                    <!-- Saída Almoço -->
                    <button type="submit" name="acao" value="almoco_saida" 
                            class="btn btn-ponto btn-almoco-saida w-100"
                            <?php echo (!$ponto_hoje || !$ponto_hoje['entrada_manha'] || $ponto_hoje['saida_almoco']) ? 'disabled' : ''; ?>>
                        <i class="fas fa-utensils me-2"></i>
                        <?php echo ($ponto_hoje && $ponto_hoje['saida_almoco']) ? 'Saída Almoço: ' . formatarTempo($ponto_hoje['saida_almoco']) : 'Saída para Almoço'; ?>
                    </button>
                    
                    <!-- Volta Almoço -->
                    <button type="submit" name="acao" value="almoco_volta" 
                            class="btn btn-ponto btn-almoco-volta w-100"
                            <?php echo (!$ponto_hoje || !$ponto_hoje['saida_almoco'] || $ponto_hoje['entrada_tarde']) ? 'disabled' : ''; ?>>
                        <i class="fas fa-play me-2"></i>
                        <?php echo ($ponto_hoje && $ponto_hoje['entrada_tarde']) ? 'Volta Almoço: ' . formatarTempo($ponto_hoje['entrada_tarde']) : 'Volta do Almoço'; ?>
                    </button>
                    
                    <!-- Saída -->
                    <button type="submit" name="acao" value="saida" 
                            class="btn btn-ponto btn-saida w-100"
                            <?php echo (!$ponto_hoje || !$ponto_hoje['entrada_manha'] || $ponto_hoje['saida_final']) ? 'disabled' : ''; ?>>
                        <i class="fas fa-sign-out-alt me-2"></i>
                        <?php echo ($ponto_hoje && $ponto_hoje['saida_final']) ? 'Saída: ' . formatarTempo($ponto_hoje['saida_final']) : 'Registrar Saída'; ?>
                    </button>
                    
                    <!-- Campo de Observação -->
                    <div class="form-floating mt-3">
                        <textarea class="form-control" id="observacao" name="observacao" 
                                  placeholder="Observações" style="height: 80px"></textarea>
                        <label for="observacao">Observações (opcional)</label>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Coluna do Resumo do Dia -->
        <div class="col-lg-8">
            <div class="registro-card fade-in">
                <h5 class="mb-3">
                    <i class="fas fa-calendar-day me-2 text-info"></i>
                    Resumo de Hoje
                </h5>
                
                <?php if ($ponto_hoje): ?>
                    <div class="horario-item">
                        <span class="horario-label">Entrada:</span>
                        <div>
                            <span class="horario-valor horario-real">
                                <?php echo formatarTempo($ponto_hoje['entrada_manha']); ?>
                            </span>
                            <?php if ($horario_esperado && $ponto_hoje['entrada_manha']): ?>
                                <span class="horario-esperado">(esperado: <?php echo formatarTempo($horario_esperado['hora_entrada']); ?>)</span>
                                <?php
                                $diff = calcularDiferenca($horario_esperado['hora_entrada'], $ponto_hoje['entrada_manha']);
                                if ($diff && $diff !== '00:00'):
                                ?>
                                    <span class="horario-diferenca <?php echo $diff[0] === '+' ? 'positivo' : 'negativo'; ?>">
                                        <?php echo $diff; ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="horario-item">
                        <span class="horario-label">Saída Almoço:</span>
                        <div>
                            <span class="horario-valor horario-real">
                                <?php echo formatarTempo($ponto_hoje['saida_almoco']); ?>
                            </span>
                            <?php if ($horario_esperado): ?>
                                <span class="horario-esperado">(esperado: <?php echo formatarTempo($horario_esperado['hora_almoco_saida']); ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="horario-item">
                        <span class="horario-label">Volta Almoço:</span>
                        <div>
                            <span class="horario-valor horario-real">
                                <?php echo formatarTempo($ponto_hoje['entrada_tarde']); ?>
                            </span>
                            <?php if ($horario_esperado): ?>
                                <span class="horario-esperado">(esperado: <?php echo formatarTempo($horario_esperado['hora_almoco_volta']); ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="horario-item">
                        <span class="horario-label">Saída:</span>
                        <div>
                            <span class="horario-valor horario-real">
                                <?php echo formatarTempo($ponto_hoje['saida_final']); ?>
                            </span>
                            <?php if ($horario_esperado && $ponto_hoje['saida_final']): ?>
                                <span class="horario-esperado">(esperado: <?php echo formatarTempo($horario_esperado['hora_saida']); ?>)</span>
                                <?php
                                $diff = calcularDiferenca($horario_esperado['hora_saida'], $ponto_hoje['saida_final']);
                                if ($diff && $diff !== '00:00'):
                                ?>
                                    <span class="horario-diferenca <?php echo $diff[0] === '+' ? 'positivo' : 'negativo'; ?>">
                                        <?php echo $diff; ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($ponto_hoje['horas_trabalhadas']): ?>
                        <div class="horario-item" style="border-top: 2px solid #FF6B6B; margin-top: 15px; padding-top: 15px;">
                            <span class="horario-label">Horas Trabalhadas:</span>
                            <div>
                                <span class="horario-valor" style="color: #FF6B6B; font-size: 1.3rem;">
                                    <?php echo formatarTempo($ponto_hoje['horas_trabalhadas']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($ponto_hoje['observacoes']): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <small class="text-muted">
                                <i class="fas fa-comment me-1"></i>
                                <strong>Observações:</strong> <?php echo htmlspecialchars($ponto_hoje['observacoes']); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clock"></i>
                        <h6>Nenhum registro hoje</h6>
                        <p>Clique em "Registrar Entrada" para começar.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Histórico Recente (ADAPTADO) -->
    <?php if (!empty($historico)): ?>
        <div class="historico-table fade-in mt-4">
            <h5 class="p-3 mb-0" style="background: var(--ponto-gradient); color: white; border-radius: var(--border-radius) var(--border-radius) 0 0;">
                <i class="fas fa-history me-2"></i>
                Últimos Registros
            </h5>
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Entrada</th>
                            <th>Saída Almoço</th>
                            <th>Volta Almoço</th>
                            <th>Saída</th>
                            <th>Horas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $registro): ?>
                            <tr>
                                <td><strong><?php echo date('d/m/Y', strtotime($registro['data_registro'])); ?></strong></td>
                                <td><?php echo formatarTempo($registro['entrada_manha']); ?></td>
                                <td><?php echo formatarTempo($registro['saida_almoco']); ?></td>
                                <td><?php echo formatarTempo($registro['entrada_tarde']); ?></td>
                                <td><?php echo formatarTempo($registro['saida_final']); ?></td>
                                <td>
                                    <?php if ($registro['horas_trabalhadas']): ?>
                                        <strong style="color: #FF6B6B;"><?php echo formatarTempo($registro['horas_trabalhadas']); ?></strong>
                                    <?php else: ?>
                                        --:--
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $registro['status_desc'] == 'Completo' ? 'success' : 
                                             ($registro['status_desc'] == 'Falta' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo $registro['status_desc']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-user-times"></i>
        <h4>Funcionário não encontrado</h4>
        <p>Não foi possível carregar os dados do funcionário.</p>
        <a href="../dashboard.php" class="btn btn-primary">
            <i class="fas fa-home me-2"></i>Voltar ao Dashboard
        </a>
    </div>
<?php endif; ?>

<!-- Scripts -->
<script>
// Atualizar relógio em tempo real
function atualizarRelogio() {
    const agora = new Date();
    const horas = agora.getHours().toString().padStart(2, '0');
    const minutos = agora.getMinutes().toString().padStart(2, '0');
    const segundos = agora.getSeconds().toString().padStart(2, '0');
    
    document.getElementById('currentTime').textContent = `${horas}:${minutos}:${segundos}`;
}

// Atualizar a cada segundo
setInterval(atualizarRelogio, 1000);

// Animações ao carregar
document.addEventListener('DOMContentLoaded', function() {
    // Animar cards com delay
    const cards = document.querySelectorAll('.fade-in');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Confirmation para registros importantes
    document.querySelectorAll('button[name="acao"]').forEach(button => {
        button.addEventListener('click', function(e) {
            const acao = this.value;
            let mensagem = '';
            
            switch(acao) {
                case 'entrada':
                    mensagem = 'Confirmar registro de entrada?';
                    break;
                case 'saida':
                    mensagem = 'Confirmar registro de saída? Isso finalizará seu expediente.';
                    break;
                default:
                    return; // Não confirmar para almoço
            }
            
            if (!confirm(mensagem)) {
                e.preventDefault();
            }
        });
    });
});

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    if (e.altKey) {
        switch(e.key) {
            case '1':
                e.preventDefault();
                const btnEntrada = document.querySelector('button[value="entrada"]');
                if (btnEntrada && !btnEntrada.disabled) btnEntrada.click();
                break;
            case '2':
                e.preventDefault();
                const btnAlmocoSaida = document.querySelector('button[value="almoco_saida"]');
                if (btnAlmocoSaida && !btnAlmocoSaida.disabled) btnAlmocoSaida.click();
                break;
            case '3':
                e.preventDefault();
                const btnAlmocoVolta = document.querySelector('button[value="almoco_volta"]');
                if (btnAlmocoVolta && !btnAlmocoVolta.disabled) btnAlmocoVolta.click();
                break;
            case '4':
                e.preventDefault();
                const btnSaida = document.querySelector('button[value="saida"]');
                if (btnSaida && !btnSaida.disabled) btnSaida.click();
                break;
            case 'r':
                e.preventDefault();
                <?php if ($is_admin): ?>
                window.location.href = 'relatorios.php';
                <?php endif; ?>
                break;
        }
    }
});

// Atalho F1 para ajuda
document.addEventListener('keydown', function(e) {
    if (e.key === 'F1') {
        e.preventDefault();
        alert(`Atalhos de Teclado:
    
Alt + 1 = Registrar Entrada
Alt + 2 = Saída para Almoço  
Alt + 3 = Volta do Almoço
Alt + 4 = Registrar Saída
<?php if ($is_admin): ?>Alt + R = Relatórios<?php endif; ?>

F5 = Atualizar página`);
    }
});
</script>

<?php
// Incluir o rodapé
include_once '../includes/footer.php';
?>