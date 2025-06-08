<?php
// funcionarios/visualizar.php - Sistema Premium de Funcionários
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../config/database.php';

// Verificar se foi passado um ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['msg'] = "Funcionário não encontrado.";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$funcionario_id = (int)$_GET['id'];

// Buscar dados do funcionário
try {
    $sql = "SELECT * FROM funcionarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $funcionario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['msg'] = "Funcionário não encontrado.";
        $_SESSION['msg_type'] = "danger";
        header("Location: listar.php");
        exit;
    }
    
    $funcionario = $result->fetch_assoc();
    $stmt->close();
    
} catch (Exception $e) {
    $_SESSION['msg'] = "Erro ao buscar funcionário: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

// Função para gerar uma cor baseada no nome do funcionário
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
    --funcionarios-gradient: linear-gradient(135deg, #7B68EE 0%, #9370DB 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
    --border-radius: 15px;
}

.profile-header {
    background: var(--funcionarios-gradient);
    color: white;
    padding: 40px 0;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: scale(0);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(0); opacity: 0.3; }
    50% { transform: scale(1); opacity: 0.1; }
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: bold;
    margin: 0 auto 20px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.info-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 30px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    border-left: 4px solid var(--funcionarios-gradient);
}

.info-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.info-card h5 {
    color: #7B68EE;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.info-card h5 i {
    margin-right: 10px;
    width: 24px;
    text-align: center;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #6c757d;
    flex: 0 0 30%;
}

.info-value {
    flex: 1;
    color: #333;
    font-weight: 500;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
}

.status-badge i {
    margin-right: 6px;
}

.status-badge.ativo { background: var(--success-gradient); }
.status-badge.inativo { background: var(--danger-gradient); }
.status-badge.ferias { background: var(--warning-gradient); }
.status-badge.licenca { background: var(--info-gradient); }

.department-tag {
    background: var(--info-gradient);
    color: white;
    border-radius: 20px;
    padding: 8px 16px;
    font-weight: 600;
    font-size: 0.875rem;
    display: inline-block;
}

.salary-display {
    background: var(--success-gradient);
    color: white;
    padding: 20px;
    border-radius: var(--border-radius);
    text-align: center;
    margin: 20px 0;
}

.salary-main {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.salary-info {
    font-size: 0.9rem;
    opacity: 0.9;
}

.schedule-card {
    background: linear-gradient(135deg, rgba(123, 104, 238, 0.1), rgba(147, 112, 219, 0.1));
    border: 2px solid rgba(123, 104, 238, 0.2);
    border-radius: var(--border-radius);
    padding: 20px;
    margin: 20px 0;
}

.time-display {
    font-size: 1.5rem;
    font-weight: bold;
    color: #7B68EE;
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.btn-action {
    border-radius: 10px;
    padding: 12px 24px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-action::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.btn-action:hover::before {
    left: 100%;
}

.btn-edit {
    background: var(--funcionarios-gradient);
    color: white;
}

.btn-edit:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(123, 104, 238, 0.4);
}

.btn-ponto {
    background: var(--success-gradient);
    color: white;
}

.btn-ponto:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4);
}

.btn-inativar {
    background: var(--danger-gradient);
    color: white;
}

.btn-inativar:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    border-top: 4px solid var(--funcionarios-gradient);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #7B68EE;
    margin-bottom: 5px;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

.timeline-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.timeline-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--funcionarios-gradient);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.timeline-content {
    flex: 1;
}

.timeline-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.timeline-date {
    color: #6c757d;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .profile-header {
        padding: 20px 0;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 2rem;
    }
    
    .info-card {
        padding: 20px;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .info-label {
        flex: none;
    }
    
    .action-buttons {
        gap: 10px;
    }
    
    .btn-action {
        flex: 1;
        min-width: 0;
        text-align: center;
    }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="listar.php">Funcionários</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($funcionario['nome']); ?></li>
    </ol>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Perfil Principal -->
        <div class="col-lg-8">
            <div class="card">
                <div class="profile-header">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <div class="profile-avatar" style="background: <?php echo stringToColor($funcionario['nome']); ?>;">
                                    <?php if (!empty($funcionario['foto']) && file_exists('../' . $funcionario['foto'])): ?>
                                        <img src="../<?php echo htmlspecialchars($funcionario['foto']); ?>" alt="Foto do funcionário">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($funcionario['nome'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <h2 class="mb-2"><?php echo htmlspecialchars($funcionario['nome']); ?></h2>
                                <p class="mb-3">
                                    <span class="department-tag"><?php echo htmlspecialchars($funcionario['departamento']); ?></span>
                                </p>
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <span><i class="fas fa-id-badge me-2"></i><?php echo htmlspecialchars($funcionario['codigo']); ?></span>
                                    <span><i class="fas fa-briefcase me-2"></i><?php echo htmlspecialchars($funcionario['cargo']); ?></span>
                                    <span class="status-badge <?php echo $funcionario['status']; ?>">
                                        <i class="fas fa-<?php echo $funcionario['status'] == 'ativo' ? 'check-circle' : ($funcionario['status'] == 'ferias' ? 'umbrella-beach' : 'times-circle'); ?>"></i>
                                        <?php echo ucfirst($funcionario['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Informações Pessoais -->
                    <div class="info-card fade-in">
                        <h5><i class="fas fa-user"></i>Informações Pessoais</h5>
                        <div class="info-row">
                            <span class="info-label">Nome Completo:</span>
                            <span class="info-value"><?php echo htmlspecialchars($funcionario['nome']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">CPF:</span>
                            <span class="info-value"><?php echo !empty($funcionario['cpf']) ? htmlspecialchars($funcionario['cpf']) : 'Não informado'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">RG:</span>
                            <span class="info-value"><?php echo !empty($funcionario['rg']) ? htmlspecialchars($funcionario['rg']) : 'Não informado'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Telefone:</span>
                            <span class="info-value">
                                <?php if (!empty($funcionario['telefone'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($funcionario['telefone']); ?>" class="text-decoration-none">
                                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($funcionario['telefone']); ?>
                                    </a>
                                <?php else: ?>
                                    Não informado
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">E-mail:</span>
                            <span class="info-value">
                                <?php if (!empty($funcionario['email'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($funcionario['email']); ?>" class="text-decoration-none">
                                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($funcionario['email']); ?>
                                    </a>
                                <?php else: ?>
                                    Não informado
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Endereço:</span>
                            <span class="info-value"><?php echo !empty($funcionario['endereco']) ? htmlspecialchars($funcionario['endereco']) : 'Não informado'; ?></span>
                        </div>
                    </div>

                    <!-- Informações Profissionais -->
                    <div class="info-card fade-in">
                        <h5><i class="fas fa-briefcase"></i>Informações Profissionais</h5>
                        <div class="info-row">
                            <span class="info-label">Código:</span>
                            <span class="info-value"><strong><?php echo htmlspecialchars($funcionario['codigo']); ?></strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cargo:</span>
                            <span class="info-value"><?php echo htmlspecialchars($funcionario['cargo']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Departamento:</span>
                            <span class="info-value">
                                <span class="department-tag"><?php echo htmlspecialchars($funcionario['departamento']); ?></span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Data de Admissão:</span>
                            <span class="info-value">
                                <?php 
                                if (!empty($funcionario['data_admissao'])) {
                                    $data_admissao = new DateTime($funcionario['data_admissao']);
                                    echo $data_admissao->format('d/m/Y');
                                    
                                    // Calcular tempo de empresa
                                    $hoje = new DateTime();
                                    $intervalo = $hoje->diff($data_admissao);
                                    echo ' <small class="text-muted">(' . $intervalo->y . ' anos, ' . $intervalo->m . ' meses)</small>';
                                } else {
                                    echo 'Não informado';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value">
                                <span class="status-badge <?php echo $funcionario['status']; ?>">
                                    <i class="fas fa-<?php echo $funcionario['status'] == 'ativo' ? 'check-circle' : ($funcionario['status'] == 'ferias' ? 'umbrella-beach' : 'times-circle'); ?>"></i>
                                    <?php echo ucfirst($funcionario['status']); ?>
                                </span>
                            </span>
                        </div>
                        
                        <?php if (!empty($funcionario['salario']) && $funcionario['salario'] > 0): ?>
                        <div class="salary-display">
                            <div class="salary-main">R$ <?php echo number_format($funcionario['salario'], 2, ',', '.'); ?></div>
                            <div class="salary-info">
                                Salário Base Mensal
                                <br>
                                <small>R$ <?php echo number_format($funcionario['salario'] / 220, 2, ',', '.'); ?>/hora</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Horários de Trabalho -->
                    <div class="info-card fade-in">
                        <h5><i class="fas fa-clock"></i>Horários de Trabalho</h5>
                        <div class="schedule-card">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <i class="fas fa-sun text-warning fa-2x"></i>
                                    </div>
                                    <div class="time-display"><?php echo !empty($funcionario['horario_entrada']) ? date('H:i', strtotime($funcionario['horario_entrada'])) : '08:00'; ?></div>
                                    <small class="text-muted">Entrada</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <i class="fas fa-moon text-primary fa-2x"></i>
                                    </div>
                                    <div class="time-display"><?php echo !empty($funcionario['horario_saida']) ? date('H:i', strtotime($funcionario['horario_saida'])) : '18:00'; ?></div>
                                    <small class="text-muted">Saída</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <i class="fas fa-business-time text-success fa-2x"></i>
                                    </div>
                                    <div class="time-display">
                                        <?php 
                                        $entrada = !empty($funcionario['horario_entrada']) ? $funcionario['horario_entrada'] : '08:00:00';
                                        $saida = !empty($funcionario['horario_saida']) ? $funcionario['horario_saida'] : '18:00:00';
                                        
                                        $entrada_time = new DateTime($entrada);
                                        $saida_time = new DateTime($saida);
                                        $intervalo = $saida_time->diff($entrada_time);
                                        
                                        echo $intervalo->h . 'h' . str_pad($intervalo->i, 2, '0', STR_PAD_LEFT);
                                        ?>
                                    </div>
                                    <small class="text-muted">Carga Horária</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ações -->
                    <div class="action-buttons">
                        <a href="editar.php?id=<?php echo $funcionario['id']; ?>" class="btn btn-action btn-edit">
                            <i class="fas fa-edit me-2"></i>Editar Funcionário
                        </a>
                        <a href="../ponto/consultar.php?funcionario_id=<?php echo $funcionario['id']; ?>" class="btn btn-action btn-ponto">
                            <i class="fas fa-clock me-2"></i>Ver Ponto
                        </a>
                        <?php if ($funcionario['status'] == 'ativo'): ?>
                        <button onclick="confirmarInativacao(<?php echo $funcionario['id']; ?>, '<?php echo addslashes($funcionario['nome']); ?>')" class="btn btn-action btn-inativar">
                            <i class="fas fa-user-times me-2"></i>Inativar
                        </button>
                        <?php endif; ?>
                        <a href="listar.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar à Lista
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar com Estatísticas -->
        <div class="col-lg-4">
            <!-- Estatísticas Rápidas -->
            <div class="card fade-in">
                <div class="card-header" style="background: var(--funcionarios-gradient); color: white;">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estatísticas</h6>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo date('d'); ?></div>
                            <div class="stat-label">Dias no Mês</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">
                                <?php 
                                if (!empty($funcionario['data_admissao'])) {
                                    $data_admissao = new DateTime($funcionario['data_admissao']);
                                    $hoje = new DateTime();
                                    $intervalo = $hoje->diff($data_admissao);
                                    echo $intervalo->days;
                                } else {
                                    echo '0';
                                }
                                ?>
                            </div>
                            <div class="stat-label">Dias na Empresa</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline de Atividades -->
            <div class="card fade-in mt-4">
                <div class="card-header" style="background: var(--funcionarios-gradient); color: white;">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Histórico</h6>
                </div>
                <div class="card-body">
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Funcionário Cadastrado</div>
                            <div class="timeline-date">
                                <?php 
                                if (!empty($funcionario['created_at'])) {
                                    echo date('d/m/Y \à\s H:i', strtotime($funcionario['created_at']));
                                } else {
                                    echo 'Data não disponível';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($funcionario['data_admissao'])): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Admissão na Empresa</div>
                            <div class="timeline-date"><?php echo date('d/m/Y', strtotime($funcionario['data_admissao'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($funcionario['updated_at']) && $funcionario['updated_at'] != $funcionario['created_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Última Atualização</div>
                            <div class="timeline-date"><?php echo date('d/m/Y \à\s H:i', strtotime($funcionario['updated_at'])); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="card fade-in mt-4">
                <div class="card-header" style="background: var(--funcionarios-gradient); color: white;">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Ações Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="../ponto/registrar.php?funcionario_id=<?php echo $funcionario['id']; ?>" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-clock me-2"></i>Registrar Ponto
                        </a>
                        <button onclick="enviarWhatsApp('<?php echo htmlspecialchars($funcionario['telefone']); ?>', '<?php echo addslashes($funcionario['nome']); ?>')" class="btn btn-outline-primary btn-sm">
                            <i class="fab fa-whatsapp me-2"></i>Enviar WhatsApp
                        </button>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-print me-2"></i>Imprimir Perfil
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmarInativacao(id, nome) {
    Swal.fire({
        title: 'Confirmar Inativação',
        html: `Tem certeza que deseja inativar o funcionário <strong>"${nome}"</strong>?<br><br><span class="text-danger">Esta ação pode ser revertida posteriormente.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-user-times me-2"></i>Sim, inativar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Processando...',
                text: 'Aguarde enquanto o funcionário é inativado.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Redirecionar para inativação
            window.location.href = `inativar.php?id=${id}`;
        }
    });
}

function enviarWhatsApp(telefone, nome) {
    if (!telefone || telefone === 'Não informado') {
        Swal.fire({
            icon: 'warning',
            title: 'Telefone não disponível',
            text: 'Este funcionário não possui telefone cadastrado.',
            confirmButtonColor: '#7B68EE'
        });
        return;
    }
    
    // Limpar formatação do telefone
    const tel = telefone.replace(/\D/g, '');
    
    // Verificar se tem o código do país
    const telFormatted = tel.startsWith('55') ? tel : '55' + tel;
    
    const mensagem = `Olá ${nome}! Entrando em contato via sistema da empresa.`;
    const url = `https://wa.me/${telFormatted}?text=${encodeURIComponent(mensagem)}`;
    
    window.open(url, '_blank');
}

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    if (e.altKey) {
        switch(e.key) {
            case 'e':
                e.preventDefault();
                window.location.href = 'editar.php?id=<?php echo $funcionario['id']; ?>';
                break;
            case 'p':
                e.preventDefault();
                window.location.href = '../ponto/consultar.php?funcionario_id=<?php echo $funcionario['id']; ?>';
                break;
            case 'l':
                e.preventDefault();
                window.location.href = 'listar.php';
                break;
            case 'i':
                e.preventDefault();
                <?php if ($funcionario['status'] == 'ativo'): ?>
                confirmarInativacao(<?php echo $funcionario['id']; ?>, '<?php echo addslashes($funcionario['nome']); ?>');
                <?php endif; ?>
                break;
        }
    }
    
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
});

// Animações ao carregar
document.addEventListener('DOMContentLoaded', function() {
    // Animar elementos com delay
    const elements = document.querySelectorAll('.fade-in');
    elements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Tooltip de atalhos
    const tooltips = [
        { element: 'a[href*="editar"]', title: 'Alt+E - Editar funcionário' },
        { element: 'a[href*="ponto"]', title: 'Alt+P - Ver ponto' },
        { element: 'a[href*="listar"]', title: 'Alt+L - Voltar à lista' },
        { element: 'button[onclick*="print"]', title: 'Ctrl+P - Imprimir' }
    ];
    
    tooltips.forEach(tooltip => {
        const element = document.querySelector(tooltip.element);
        if (element) {
            element.setAttribute('title', tooltip.title);
            element.setAttribute('data-bs-toggle', 'tooltip');
        }
    });
    
    // Inicializar tooltips do Bootstrap
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Função para copiar informações
function copiarInfo(tipo) {
    let texto = '';
    
    switch(tipo) {
        case 'codigo':
            texto = '<?php echo $funcionario['codigo']; ?>';
            break;
        case 'email':
            texto = '<?php echo $funcionario['email']; ?>';
            break;
        case 'telefone':
            texto = '<?php echo $funcionario['telefone']; ?>';
            break;
        case 'cpf':
            texto = '<?php echo $funcionario['cpf']; ?>';
            break;
    }
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(texto).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Copiado!',
                text: `${tipo.toUpperCase()} copiado para a área de transferência.`,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });
    }
}

// Adicionar eventos de clique para copiar
document.addEventListener('DOMContentLoaded', function() {
    // Tornar campos clicáveis para copiar
    const copiableFields = [
        { selector: '.info-value:contains("<?php echo $funcionario['codigo']; ?>")', tipo: 'codigo' },
        { selector: 'a[href^="mailto:"]', tipo: 'email' },
        { selector: 'a[href^="tel:"]', tipo: 'telefone' }
    ];
    
    copiableFields.forEach(field => {
        const elements = document.querySelectorAll(field.selector);
        elements.forEach(element => {
            element.style.cursor = 'pointer';
            element.addEventListener('click', (e) => {
                e.preventDefault();
                copiarInfo(field.tipo);
            });
        });
    });
});
</script>

<!-- Estilos para impressão -->
<style media="print">
    .sidebar, .action-buttons, .navbar, .breadcrumb, .btn {
        display: none !important;
    }
    
    .profile-header {
        background: #7B68EE !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
    
    .info-card {
        margin-bottom: 20px !important;
        page-break-inside: avoid;
    }
    
    body {
        background: white !important;
    }
    
    .container-fluid {
        padding: 0 !important;
    }
    
    .col-lg-8 {
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .profile-avatar {
        border: 2px solid #7B68EE !important;
    }
    
    @page {
        margin: 1cm;
        size: A4;
    }
</style>

<?php
// Incluir o rodapé
include_once '../includes/footer.php';
?>