<?php
// funcionarios/extras.php - Sistema Premium de Extras Salariais
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

// Verificar se o usuário é administrador
$is_admin = isset($_SESSION['nivel_acesso']) && ($_SESSION['nivel_acesso'] == 'admin');

// Obter mês atual ou mês selecionado
$mes_atual = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
$mes_referencia = $mes_atual . '-01';

// Buscar funcionários com seus extras do mês
try {
    $sql = "SELECT 
                f.id, f.codigo, f.nome, f.cargo, f.departamento, f.status, f.foto,
                COALESCE(SUM(fe.valor), 0) as total_extras,
                COUNT(fe.id) as quantidade_extras
            FROM funcionarios f
            LEFT JOIN funcionarios_extras fe ON f.id = fe.funcionario_id 
                AND DATE_FORMAT(fe.mes_referencia, '%Y-%m') = ?
            WHERE f.status = 'ativo'
            GROUP BY f.id, f.codigo, f.nome, f.cargo, f.departamento, f.status, f.foto
            ORDER BY total_extras DESC, f.nome ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $mes_atual);
    $stmt->execute();
    $funcionarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Buscar tipos de extras disponíveis
    $sql_tipos = "SELECT * FROM extras_tipos WHERE ativo = TRUE ORDER BY nome ASC";
    $tipos_extras = $conn->query($sql_tipos)->fetch_all(MYSQLI_ASSOC);

    // Estatísticas do mês
    $sql_stats = "SELECT 
                    COUNT(DISTINCT fe.funcionario_id) as funcionarios_premiados,
                    COUNT(fe.id) as total_extras_concedidos,
                    COALESCE(SUM(fe.valor), 0) as valor_total_mes,
                    COALESCE(AVG(fe.valor), 0) as valor_medio_extra
                  FROM funcionarios_extras fe 
                  WHERE DATE_FORMAT(fe.mes_referencia, '%Y-%m') = ?";
    
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bind_param("s", $mes_atual);
    $stmt_stats->execute();
    $stats = $stmt_stats->get_result()->fetch_assoc();
    $stmt_stats->close();

} catch (Exception $e) {
    $_SESSION['msg'] = "Erro ao buscar dados: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
    $funcionarios = [];
    $tipos_extras = [];
    $stats = [
        'funcionarios_premiados' => 0,
        'total_extras_concedidos' => 0,
        'valor_total_mes' => 0,
        'valor_medio_extra' => 0
    ];
}

// Função para gerar cor baseada no nome
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
    --extras-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gold-gradient: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
    --border-radius: 15px;
}

.extras-header {
    background: var(--extras-gradient);
    color: white;
    padding: 40px 0;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.extras-header::before {
    content: '💰';
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 4rem;
    opacity: 0.3;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.stats-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    text-align: center;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    border-top: 4px solid;
    margin-bottom: 20px;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.stats-card.premiados { border-top-color: #28a745; }
.stats-card.total-extras { border-top-color: #17a2b8; }
.stats-card.valor-total { border-top-color: #ffc107; }
.stats-card.valor-medio { border-top-color: #6f42c1; }

.stats-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.stats-number.money {
    color: #28a745;
}

.stats-number.count {
    color: #17a2b8;
}

.funcionario-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.funcionario-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.funcionario-card.destaque {
    border: 2px solid #ffd700;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 237, 78, 0.05));
}

.funcionario-card.destaque::before {
    content: '⭐';
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 1.5rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.funcionario-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2rem;
    margin-right: 20px;
    box-shadow: var(--shadow-soft);
    position: relative;
    overflow: hidden;
}

.funcionario-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.extra-value {
    font-size: 2.5rem;
    font-weight: bold;
    background: var(--gold-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 10px;
}

.extra-value.zero {
    color: #6c757d;
    background: none;
    -webkit-text-fill-color: #6c757d;
    font-size: 1.5rem;
}

.extra-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
    color: white;
    margin: 2px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.month-selector {
    background: white;
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-soft);
    text-align: center;
}

.btn-add-extra {
    background: var(--extras-gradient);
    border: none;
    color: white;
    border-radius: 25px;
    padding: 10px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-add-extra:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
    color: white;
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

.admin-panel {
    background: linear-gradient(135deg, rgba(108, 117, 125, 0.1), rgba(108, 117, 125, 0.05));
    border: 2px dashed #6c757d;
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 20px;
    text-align: center;
}

.ranking-medal {
    position: absolute;
    top: -10px;
    left: -10px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    font-size: 1.2rem;
    z-index: 10;
}

.ranking-1 { background: #ffd700; color: #333; }
.ranking-2 { background: #c0c0c0; color: #333; }
.ranking-3 { background: #cd7f32; color: white; }

@media (max-width: 768px) {
    .funcionario-card {
        padding: 20px;
    }
    
    .funcionario-avatar {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
        margin-right: 15px;
    }
    
    .extra-value {
        font-size: 2rem;
    }
    
    .stats-number {
        font-size: 2rem;
    }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="listar.php">Funcionários</a></li>
        <li class="breadcrumb-item active">Extras Salariais</li>
    </ol>
</nav>

<!-- Header Principal -->
<div class="extras-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fas fa-gift me-3"></i>
                    Extras Salariais
                </h1>
                <p class="mb-0 fs-5">
                    Sistema de bonificações e incentivos para a equipe
                </p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($is_admin): ?>
                    <a href="extras_gerenciar.php" class="btn btn-light btn-lg">
                        <i class="fas fa-cog me-2"></i>Gerenciar Tipos
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Seletor de Mês -->
<div class="month-selector fade-in">
    <div class="row align-items-center">
        <div class="col-md-4">
            <h5 class="mb-0">
                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                Mês de Referência
            </h5>
        </div>
        <div class="col-md-4">
            <select class="form-select form-select-lg" id="mesSelector" onchange="alterarMes()">
                <?php
                // Gerar opções dos últimos 12 meses
                for ($i = 0; $i < 12; $i++) {
                    $mes = date('Y-m', strtotime("-$i months"));
                    $mes_nome = date('F/Y', strtotime($mes . '-01'));
                    $selected = ($mes == $mes_atual) ? 'selected' : '';
                    echo "<option value='$mes' $selected>" . ucfirst(strftime('%B/%Y', strtotime($mes . '-01'))) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($is_admin): ?>
                <button class="btn btn-add-extra" onclick="abrirModalConcederExtra()">
                    <i class="fas fa-plus"></i>
                    Conceder Extra
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Estatísticas do Mês -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card premiados fade-in">
            <div class="stats-number count"><?php echo $stats['funcionarios_premiados']; ?></div>
            <h6 class="text-muted mb-0">Funcionários Premiados</h6>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card total-extras fade-in">
            <div class="stats-number count"><?php echo $stats['total_extras_concedidos']; ?></div>
            <h6 class="text-muted mb-0">Extras Concedidos</h6>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card valor-total fade-in">
            <div class="stats-number money">R$ <?php echo number_format($stats['valor_total_mes'], 2, ',', '.'); ?></div>
            <h6 class="text-muted mb-0">Valor Total do Mês</h6>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stats-card valor-medio fade-in">
            <div class="stats-number money">R$ <?php echo number_format($stats['valor_medio_extra'], 2, ',', '.'); ?></div>
            <h6 class="text-muted mb-0">Valor Médio por Extra</h6>
        </div>
    </div>
</div>

<!-- Lista de Funcionários -->
<?php if (!empty($funcionarios)): ?>
    <div class="row">
        <?php foreach ($funcionarios as $index => $funcionario): 
            $ranking = $index + 1;
            $destaque = $funcionario['total_extras'] > 0;
        ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="funcionario-card <?php echo $destaque ? 'destaque' : ''; ?> fade-in">
                    <?php if ($ranking <= 3 && $funcionario['total_extras'] > 0): ?>
                        <div class="ranking-medal ranking-<?php echo $ranking; ?>">
                            <?php echo $ranking; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="funcionario-avatar" style="background: <?php echo stringToColor($funcionario['nome']); ?>;">
                            <?php if (!empty($funcionario['foto']) && file_exists('../' . $funcionario['foto'])): ?>
                                <img src="../<?php echo htmlspecialchars($funcionario['foto']); ?>" alt="Foto">
                            <?php else: ?>
                                <?php echo strtoupper(substr($funcionario['nome'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1"><?php echo htmlspecialchars($funcionario['nome']); ?></h5>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($funcionario['cargo']); ?></p>
                            <small class="text-muted"><?php echo htmlspecialchars($funcionario['departamento']); ?></small>
                        </div>
                    </div>
                    
                    <div class="text-center mb-3">
                        <div class="extra-value <?php echo $funcionario['total_extras'] == 0 ? 'zero' : ''; ?>">
                            <?php if ($funcionario['total_extras'] > 0): ?>
                                + R$ <?php echo number_format($funcionario['total_extras'], 2, ',', '.'); ?>
                            <?php else: ?>
                                Sem extras
                            <?php endif; ?>
                        </div>
                        <?php if ($funcionario['quantidade_extras'] > 0): ?>
                            <small class="text-muted">
                                <?php echo $funcionario['quantidade_extras']; ?> 
                                extra<?php echo $funcionario['quantidade_extras'] > 1 ? 's' : ''; ?> 
                                concedido<?php echo $funcionario['quantidade_extras'] > 1 ? 's' : ''; ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="extras_detalhes.php?funcionario_id=<?php echo $funcionario['id']; ?>&mes=<?php echo $mes_atual; ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>Detalhes
                            </a>
                        </div>
                        <?php if ($is_admin): ?>
                            <button class="btn btn-add-extra btn-sm" 
                                    onclick="concederExtra(<?php echo $funcionario['id']; ?>, '<?php echo addslashes($funcionario['nome']); ?>')">
                                <i class="fas fa-plus"></i>Adicionar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users-slash"></i>
        <h4>Nenhum funcionário ativo encontrado</h4>
        <p>Cadastre funcionários para gerenciar seus extras salariais.</p>
        <a href="cadastrar.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i>Cadastrar Funcionário
        </a>
    </div>
<?php endif; ?>

<!-- Modal para Conceder Extra -->
<?php if ($is_admin): ?>
<div class="modal fade" id="modalConcederExtra" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--extras-gradient); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-gift me-2"></i>Conceder Extra Salarial
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formConcederExtra" method="POST" action="extras_conceder.php">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="funcionario_id" name="funcionario_id" required>
                                    <option value="">Selecione o funcionário</option>
                                    <?php foreach ($funcionarios as $func): ?>
                                        <option value="<?php echo $func['id']; ?>">
                                            <?php echo htmlspecialchars($func['nome']) . ' - ' . htmlspecialchars($func['cargo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="funcionario_id">Funcionário *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="extra_tipo_id" name="extra_tipo_id" required onchange="atualizarValorPadrao()">
                                    <option value="">Selecione o tipo de extra</option>
                                    <?php foreach ($tipos_extras as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>" 
                                                data-valor="<?php echo $tipo['valor_padrao']; ?>"
                                                data-cor="<?php echo $tipo['cor']; ?>"
                                                data-icone="<?php echo $tipo['icone']; ?>">
                                            <?php echo htmlspecialchars($tipo['nome']); ?> 
                                            (R$ <?php echo number_format($tipo['valor_padrao'], 2, ',', '.'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="extra_tipo_id">Tipo de Extra *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="number" class="form-control" id="valor" name="valor" 
                                       step="0.01" min="0" max="9999.99" required>
                                <label for="valor">Valor (R$) *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="month" class="form-control" id="mes_referencia" name="mes_referencia" 
                                       value="<?php echo $mes_atual; ?>" required>
                                <label for="mes_referencia">Mês de Referência *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="observacao" name="observacao" 
                                  style="height: 80px" placeholder="Observações sobre o extra"></textarea>
                        <label for="observacao">Observações</label>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Verificação de Segurança:</strong> 
                        Digite a senha de administrador para confirmar a concessão do extra.
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="senha_admin" name="senha_admin" 
                               placeholder="Senha do administrador" required>
                        <label for="senha_admin">Senha do Administrador *</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-add-extra">
                        <i class="fas fa-gift me-2"></i>Conceder Extra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function alterarMes() {
    const mes = document.getElementById('mesSelector').value;
    window.location.href = `extras.php?mes=${mes}`;
}

function abrirModalConcederExtra() {
    const modal = new bootstrap.Modal(document.getElementById('modalConcederExtra'));
    modal.show();
}

function concederExtra(funcionarioId, funcionarioNome) {
    // Pré-selecionar o funcionário no modal
    document.getElementById('funcionario_id').value = funcionarioId;
    abrirModalConcederExtra();
}

function atualizarValorPadrao() {
    const select = document.getElementById('extra_tipo_id');
    const option = select.options[select.selectedIndex];
    
    if (option && option.dataset.valor) {
        document.getElementById('valor').value = parseFloat(option.dataset.valor).toFixed(2);
    }
}

// Validação do formulário
document.getElementById('formConcederExtra').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const funcionario = document.getElementById('funcionario_id');
    const tipoExtra = document.getElementById('extra_tipo_id');
    const valor = document.getElementById('valor');
    const senha = document.getElementById('senha_admin');
    
    // Validações básicas
    if (!funcionario.value || !tipoExtra.value || !valor.value || !senha.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos obrigatórios',
            text: 'Por favor, preencha todos os campos obrigatórios.',
            confirmButtonColor: '#f093fb'
        });
        return;
    }
    
    if (parseFloat(valor.value) <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Valor inválido',
            text: 'O valor do extra deve ser maior que zero.',
            confirmButtonColor: '#f093fb'
        });
        return;
    }
    
    // Confirmação com SweetAlert
    const funcionarioNome = funcionario.options[funcionario.selectedIndex].text;
    const tipoNome = tipoExtra.options[tipoExtra.selectedIndex].text;
    const valorFormatado = parseFloat(valor.value).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
    
    Swal.fire({
        title: 'Confirmar Concessão de Extra',
        html: `
            <div class="text-start">
                <p><strong>Funcionário:</strong> ${funcionarioNome}</p>
                <p><strong>Tipo de Extra:</strong> ${tipoNome}</p>
                <p><strong>Valor:</strong> <span style="color: #28a745; font-weight: bold;">${valorFormatado}</span></p>
                <p><strong>Mês:</strong> ${document.getElementById('mes_referencia').value}</p>
            </div>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                Esta ação será registrada no sistema com sua identificação.
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-gift me-2"></i>Sim, conceder extra',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: '#f093fb',
        cancelButtonColor: '#6c757d',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                // Submeter o formulário
                const formData = new FormData(this);
                
                fetch('extras_conceder.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    resolve(data);
                })
                .catch(error => {
                    Swal.showValidationMessage('Erro de comunicação: ' + error);
                });
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Extra Concedido!',
                    text: result.value.message,
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    // Recarregar a página para mostrar as alterações
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: result.value.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        }
    });
});

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
    
    // Highlight do mês atual
    const mesAtual = '<?php echo date('Y-m'); ?>';
    const mesSelecionado = '<?php echo $mes_atual; ?>';
    
    if (mesAtual === mesSelecionado) {
        document.querySelector('.month-selector').style.borderLeft = '4px solid #28a745';
    }
    
    // Tooltips
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    if (e.altKey) {
        switch(e.key) {
            case 'n':
                e.preventDefault();
                <?php if ($is_admin): ?>
                abrirModalConcederExtra();
                <?php endif; ?>
                break;
            case 'g':
                e.preventDefault();
                <?php if ($is_admin): ?>
                window.location.href = 'extras_gerenciar.php';
                <?php endif; ?>
                break;
            case 'l':
                e.preventDefault();
                window.location.href = 'listar.php';
                break;
        }
    }
});

// Função para mostrar atalhos
function mostrarAtalhos() {
    Swal.fire({
        title: 'Atalhos de Teclado',
        html: `
            <div class="text-start">
                <strong>Navegação:</strong><br>
                Alt + L = Voltar para lista de funcionários<br>
                <?php if ($is_admin): ?>
                Alt + N = Novo extra<br>
                Alt + G = Gerenciar tipos de extras<br>
                <?php endif; ?>
                <br>
                <strong>Outros:</strong><br>
                F1 = Mostrar esta ajuda
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Entendi',
        confirmButtonColor: '#f093fb'
    });
}

// Atalho F1 para ajuda
document.addEventListener('keydown', function(e) {
    if (e.key === 'F1') {
        e.preventDefault();
        mostrarAtalhos();
    }
});

// Auto-atualização a cada 5 minutos para mostrar novos extras
setInterval(function() {
    // Verificar se há novos extras sem recarregar a página completamente
    fetch(`extras_check_updates.php?mes=<?php echo $mes_atual; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.hasUpdates) {
                // Mostrar notificação discreta
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed';
                toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-gift me-2"></i>
                            Novos extras foram adicionados! Recarregue a página para ver.
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="location.reload()"></button>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 10000);
            }
        })
        .catch(error => {
            console.log('Erro ao verificar atualizações:', error);
        });
}, 300000); // 5 minutos
</script>

<?php
// Incluir o rodapé
include_once '../includes/footer.php';
?>