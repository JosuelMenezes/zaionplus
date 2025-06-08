<?php
// funcionarios/extras_gerenciar.php - Gerenciar tipos de extras
// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    $_SESSION['msg'] = "Acesso negado. Apenas administradores podem gerenciar tipos de extras.";
    $_SESSION['msg_type'] = "danger";
    header("Location: extras.php");
    exit;
}

// Incluir arquivo de conexão
require_once '../config/database.php';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $acao = $_POST['acao'] ?? '';
        
        switch ($acao) {
            case 'criar':
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $valor_padrao = (float)($_POST['valor_padrao'] ?? 0);
                $cor = $_POST['cor'] ?? '#28a745';
                $icone = $_POST['icone'] ?? 'fas fa-star';
                
                if (empty($nome)) {
                    throw new Exception('Nome do tipo de extra é obrigatório.');
                }
                
                $sql = "INSERT INTO extras_tipos (nome, descricao, valor_padrao, cor, icone) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdss", $nome, $descricao, $valor_padrao, $cor, $icone);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "Tipo de extra '{$nome}' criado com sucesso!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    throw new Exception('Erro ao criar tipo de extra.');
                }
                $stmt->close();
                break;
                
            case 'editar':
                $id = (int)($_POST['id'] ?? 0);
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $valor_padrao = (float)($_POST['valor_padrao'] ?? 0);
                $cor = $_POST['cor'] ?? '#28a745';
                $icone = $_POST['icone'] ?? 'fas fa-star';
                
                if (empty($nome) || $id <= 0) {
                    throw new Exception('Dados inválidos para edição.');
                }
                
                $sql = "UPDATE extras_tipos SET nome = ?, descricao = ?, valor_padrao = ?, cor = ?, icone = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdssi", $nome, $descricao, $valor_padrao, $cor, $icone, $id);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "Tipo de extra '{$nome}' atualizado com sucesso!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    throw new Exception('Erro ao atualizar tipo de extra.');
                }
                $stmt->close();
                break;
                
            case 'toggle_ativo':
                $id = (int)($_POST['id'] ?? 0);
                $ativo = ($_POST['ativo'] ?? '0') === '1';
                
                if ($id <= 0) {
                    throw new Exception('ID inválido.');
                }
                
                $sql = "UPDATE extras_tipos SET ativo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $ativo, $id);
                
                if ($stmt->execute()) {
                    $status = $ativo ? 'ativado' : 'desativado';
                    $_SESSION['msg'] = "Tipo de extra {$status} com sucesso!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    throw new Exception('Erro ao alterar status do tipo de extra.');
                }
                $stmt->close();
                break;
        }
        
    } catch (Exception $e) {
        $_SESSION['msg'] = $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
    
    // Redirecionar para evitar resubmissão
    header("Location: extras_gerenciar.php");
    exit;
}

// Buscar tipos de extras
try {
    $sql = "SELECT et.*, 
            COUNT(fe.id) as total_usos,
            COALESCE(SUM(fe.valor), 0) as valor_total_concedido
            FROM extras_tipos et
            LEFT JOIN funcionarios_extras fe ON et.id = fe.extra_tipo_id
            GROUP BY et.id, et.nome, et.descricao, et.valor_padrao, et.cor, et.icone, et.ativo, et.created_at, et.updated_at
            ORDER BY et.ativo DESC, et.nome ASC";
    
    $tipos_extras = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $_SESSION['msg'] = "Erro ao buscar tipos de extras: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
    $tipos_extras = [];
}

// Incluir o cabeçalho
include_once '../includes/header.php';
?>

<style>
:root {
    --extras-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --admin-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
    --border-radius: 15px;
}

.admin-header {
    background: var(--admin-gradient);
    color: white;
    padding: 40px 0;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.admin-header::before {
    content: '⚙️';
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 4rem;
    opacity: 0.3;
    animation: rotate 10s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.tipo-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    border-left: 4px solid;
    position: relative;
}

.tipo-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.tipo-card.ativo {
    border-left-color: #28a745;
}

.tipo-card.inativo {
    border-left-color: #dc3545;
    opacity: 0.7;
}

.tipo-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    margin-right: 20px;
    box-shadow: var(--shadow-soft);
}

.valor-destaque {
    font-size: 1.8rem;
    font-weight: bold;
    background: var(--extras-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stats-mini {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    margin-top: 15px;
}

.btn-admin {
    background: var(--admin-gradient);
    border: none;
    color: white;
    border-radius: 25px;
    padding: 10px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-admin:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    color: white;
}

.form-floating label {
    font-weight: 500;
}

.color-preview {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 2px solid #dee2e6;
    display: inline-block;
    margin-left: 10px;
    cursor: pointer;
}

.icon-selector {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 10px;
    max-height: 200px;
    overflow-y: auto;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.icon-option {
    width: 50px;
    height: 50px;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.icon-option:hover,
.icon-option.selected {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

.status-toggle {
    position: absolute;
    top: 15px;
    right: 15px;
}

@media (max-width: 768px) {
    .tipo-card {
        padding: 20px;
    }
    
    .admin-header {
        padding: 20px 0;
    }
    
    .icon-selector {
        grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
        max-height: 150px;
    }
}
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="listar.php">Funcionários</a></li>
        <li class="breadcrumb-item"><a href="extras.php">Extras</a></li>
        <li class="breadcrumb-item active">Gerenciar Tipos</li>
    </ol>
</nav>

<!-- Header -->
<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fas fa-cogs me-3"></i>
                    Gerenciar Tipos de Extras
                </h1>
                <p class="mb-0 fs-5">
                    Configure os tipos de bonificações disponíveis no sistema
                </p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-light btn-lg" onclick="abrirModalNovoTipo()">
                    <i class="fas fa-plus me-2"></i>Novo Tipo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Tipos -->
<div class="container">
    <?php if (!empty($tipos_extras)): ?>
        <div class="row">
            <?php foreach ($tipos_extras as $tipo): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="tipo-card <?php echo $tipo['ativo'] ? 'ativo' : 'inativo'; ?>">
                        <div class="status-toggle">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       <?php echo $tipo['ativo'] ? 'checked' : ''; ?>
                                       onchange="toggleStatus(<?php echo $tipo['id']; ?>, this.checked)">
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-3">
                            <div class="tipo-icon" style="background-color: <?php echo $tipo['cor']; ?>;">
                                <i class="<?php echo $tipo['icone']; ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1"><?php echo htmlspecialchars($tipo['nome']); ?></h5>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($tipo['descricao']); ?></p>
                                <div class="valor-destaque">
                                    R$ <?php echo number_format($tipo['valor_padrao'], 2, ',', '.'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stats-mini">
                            <div class="row text-center">
                                <div class="col-6">
                                    <strong class="text-primary"><?php echo $tipo['total_usos']; ?></strong>
                                    <br><small class="text-muted">Usos</small>
                                </div>
                                <div class="col-6">
                                    <strong class="text-success">R$ <?php echo number_format($tipo['valor_total_concedido'], 2, ',', '.'); ?></strong>
                                    <br><small class="text-muted">Total Pago</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-<?php echo $tipo['ativo'] ? 'success' : 'danger'; ?>">
                                <?php echo $tipo['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                            <button class="btn btn-admin btn-sm" 
                                    onclick="editarTipo(<?php echo htmlspecialchars(json_encode($tipo)); ?>)">
                                <i class="fas fa-edit me-1"></i>Editar
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-gift fa-4x text-muted mb-3"></i>
            <h4>Nenhum tipo de extra cadastrado</h4>
            <p class="text-muted mb-4">Crie o primeiro tipo de extra para começar a bonificar seus funcionários.</p>
            <button class="btn btn-admin btn-lg" onclick="abrirModalNovoTipo()">
                <i class="fas fa-plus me-2"></i>Criar Primeiro Tipo
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para Criar/Editar Tipo -->
<div class="modal fade" id="modalTipo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--admin-gradient); color: white;">
                <h5 class="modal-title" id="modalTipoTitle">
                    <i class="fas fa-plus me-2"></i>Novo Tipo de Extra
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTipo" method="POST">
                <input type="hidden" id="acao" name="acao" value="criar">
                <input type="hidden" id="tipo_id" name="id">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       placeholder="Nome do tipo de extra" maxlength="100" required>
                                <label for="nome">Nome do Tipo de Extra *</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="number" class="form-control" id="valor_padrao" name="valor_padrao" 
                                       placeholder="Valor padrão" step="0.01" min="0" max="9999.99" required>
                                <label for="valor_padrao">Valor Padrão (R$) *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="descricao" name="descricao" 
                                  placeholder="Descrição do tipo de extra" style="height: 80px"></textarea>
                        <label for="descricao">Descrição</label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Cor do Tipo <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" 
                                       id="cor" name="cor" value="#28a745" onchange="atualizarPreviewCor()">
                                <div class="color-preview" id="colorPreview" style="background-color: #28a745;"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ícone <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center">
                                <input type="text" class="form-control" id="icone" name="icone" 
                                       value="fas fa-star" placeholder="fas fa-star" readonly>
                                <button type="button" class="btn btn-outline-primary ms-2" onclick="abrirSeletorIcone()">
                                    <i class="fas fa-icons"></i>
                                </button>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Preview: </small>
                                <span class="badge" style="background-color: #28a745;" id="iconePreview">
                                    <i class="fas fa-star"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seletor de Ícones -->
                    <div class="mt-3" id="iconSelector" style="display: none;">
                        <label class="form-label">Escolha um ícone:</label>
                        <div class="icon-selector">
                            <div class="icon-option" onclick="selecionarIcone('fas fa-star')">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-trophy')">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-medal')">
                                <i class="fas fa-medal"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-crown')">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-gem')">
                                <i class="fas fa-gem"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-thumbs-up')">
                                <i class="fas fa-thumbs-up"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-heart')">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-rocket')">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-lightning-bolt')">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-fire')">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-magic')">
                                <i class="fas fa-magic"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-gift')">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-clock')">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-users')">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-lightbulb')">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <div class="icon-option" onclick="selecionarIcone('fas fa-handshake')">
                                <i class="fas fa-handshake"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-admin">
                        <i class="fas fa-save me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function abrirModalNovoTipo() {
    document.getElementById('modalTipoTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Novo Tipo de Extra';
    document.getElementById('acao').value = 'criar';
    document.getElementById('formTipo').reset();
    document.getElementById('tipo_id').value = '';
    document.getElementById('cor').value = '#28a745';
    document.getElementById('icone').value = 'fas fa-star';
    atualizarPreviewCor();
    atualizarPreviewIcone();
    
    const modal = new bootstrap.Modal(document.getElementById('modalTipo'));
    modal.show();
}

function editarTipo(tipo) {
    document.getElementById('modalTipoTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Tipo de Extra';
    document.getElementById('acao').value = 'editar';
    document.getElementById('tipo_id').value = tipo.id;
    document.getElementById('nome').value = tipo.nome;
    document.getElementById('descricao').value = tipo.descricao || '';
    document.getElementById('valor_padrao').value = parseFloat(tipo.valor_padrao).toFixed(2);
    document.getElementById('cor').value = tipo.cor;
    document.getElementById('icone').value = tipo.icone;
    
    atualizarPreviewCor();
    atualizarPreviewIcone();
    
    const modal = new bootstrap.Modal(document.getElementById('modalTipo'));
    modal.show();
}

function atualizarPreviewCor() {
    const cor = document.getElementById('cor').value;
    document.getElementById('colorPreview').style.backgroundColor = cor;
    document.getElementById('iconePreview').style.backgroundColor = cor;
}

function atualizarPreviewIcone() {
    const icone = document.getElementById('icone').value;
    document.getElementById('iconePreview').innerHTML = `<i class="${icone}"></i>`;
}

function abrirSeletorIcone() {
    const selector = document.getElementById('iconSelector');
    selector.style.display = selector.style.display === 'none' ? 'block' : 'none';
}

function selecionarIcone(classe) {
    document.getElementById('icone').value = classe;
    atualizarPreviewIcone();
    
    // Destacar ícone selecionado
    document.querySelectorAll('.icon-option').forEach(option => {
        option.classList.remove('selected');
    });
    event.target.closest('.icon-option').classList.add('selected');
    
    // Fechar seletor após 1 segundo
    setTimeout(() => {
        document.getElementById('iconSelector').style.display = 'none';
    }, 1000);
}

function toggleStatus(id, ativo) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const acaoInput = document.createElement('input');
    acaoInput.name = 'acao';
    acaoInput.value = 'toggle_ativo';
    
    const idInput = document.createElement('input');
    idInput.name = 'id';
    idInput.value = id;
    
    const ativoInput = document.createElement('input');
    ativoInput.name = 'ativo';
    ativoInput.value = ativo ? '1' : '0';
    
    form.appendChild(acaoInput);
    form.appendChild(idInput);
    form.appendChild(ativoInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Validação do formulário
document.getElementById('formTipo').addEventListener('submit', function(e) {
    const nome = document.getElementById('nome').value.trim();
    const valor = parseFloat(document.getElementById('valor_padrao').value);
    
    if (!nome) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Nome obrigatório',
            text: 'Por favor, informe o nome do tipo de extra.',
            confirmButtonColor: '#667eea'
        });
        return;
    }
    
    if (valor <= 0 || valor > 9999.99) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Valor inválido',
            text: 'O valor deve estar entre R$ 0,01 e R$ 9.999,99.',
            confirmButtonColor: '#667eea'
        });
        return;
    }
});

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    if (e.altKey) {
        switch(e.key) {
            case 'n':
                e.preventDefault();
                abrirModalNovoTipo();
                break;
            case 'e':
                e.preventDefault();
                window.location.href = 'extras.php';
                break;
            case 'l':
                e.preventDefault();
                window.location.href = 'listar.php';
                break;
        }
    }
});

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar previews iniciais
    atualizarPreviewCor();
    atualizarPreviewIcone();
    
    // Animações
    const cards = document.querySelectorAll('.tipo-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php
// Incluir o rodapé
include_once '../includes/footer.php';
?>