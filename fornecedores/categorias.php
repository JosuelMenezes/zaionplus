<?php
// fornecedores/categorias.php
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

// Processar ações (adicionar, editar, excluir, ativar/desativar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'adicionar':
            $nome = trim($_POST['nome']);
            $descricao = trim($_POST['descricao']);
            $cor = $_POST['cor'];
            $icone = $_POST['icone'];
            
            if (!empty($nome)) {
                $sql = "INSERT INTO categorias_fornecedores (nome, descricao, cor, icone) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $nome, $descricao, $cor, $icone);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "Categoria '{$nome}' adicionada com sucesso!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['msg'] = "Erro ao adicionar categoria: " . $conn->error;
                    $_SESSION['msg_type'] = "danger";
                }
            }
            break;
            
        case 'editar':
            $id = intval($_POST['id']);
            $nome = trim($_POST['nome']);
            $descricao = trim($_POST['descricao']);
            $cor = $_POST['cor'];
            $icone = $_POST['icone'];
            
            if (!empty($nome) && $id > 0) {
                $sql = "UPDATE categorias_fornecedores SET nome = ?, descricao = ?, cor = ?, icone = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $nome, $descricao, $cor, $icone, $id);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "Categoria '{$nome}' atualizada com sucesso!";
                    $_SESSION['msg_type'] = "success";
                } else {
                    $_SESSION['msg'] = "Erro ao atualizar categoria: " . $conn->error;
                    $_SESSION['msg_type'] = "danger";
                }
            }
            break;
            
        case 'toggle_status':
            $id = intval($_POST['id']);
            if ($id > 0) {
                $sql = "UPDATE categorias_fornecedores SET ativo = NOT ativo WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "Status da categoria atualizado!";
                    $_SESSION['msg_type'] = "success";
                }
            }
            break;
            
        case 'excluir':
            $id = intval($_POST['id']);
            if ($id > 0) {
                // Verificar se a categoria está sendo usada
                $sql_check = "SELECT COUNT(*) as total FROM fornecedor_categorias WHERE categoria_id = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("i", $id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                $check = $result_check->fetch_assoc();
                
                if ($check['total'] > 0) {
                    $_SESSION['msg'] = "Não é possível excluir esta categoria pois ela está sendo usada por {$check['total']} fornecedor(es).";
                    $_SESSION['msg_type'] = "warning";
                } else {
                    $sql = "DELETE FROM categorias_fornecedores WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['msg'] = "Categoria excluída com sucesso!";
                        $_SESSION['msg_type'] = "success";
                    } else {
                        $_SESSION['msg'] = "Erro ao excluir categoria: " . $conn->error;
                        $_SESSION['msg_type'] = "danger";
                    }
                }
            }
            break;
    }
    
    // Redirecionar para evitar resubmissão
    header("Location: categorias.php");
    exit;
}

// Buscar todas as categorias com contagem de uso
$sql_categorias = "SELECT c.*, 
                   COUNT(fc.fornecedor_id) as total_fornecedores
                   FROM categorias_fornecedores c
                   LEFT JOIN fornecedor_categorias fc ON c.id = fc.categoria_id
                   GROUP BY c.id
                   ORDER BY c.ativo DESC, c.nome ASC";
$result_categorias = $conn->query($sql_categorias);

// Verificar se há mensagens na sessão
$msg = isset($_SESSION['msg']) ? $_SESSION['msg'] : '';
$msg_type = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : '';
unset($_SESSION['msg']);
unset($_SESSION['msg_type']);

// Ícones disponíveis para categorias
$icones_disponiveis = [
    'fas fa-utensils' => 'Utensílios/Comida',
    'fas fa-trash-alt' => 'Lixo/Descartáveis',
    'fas fa-broom' => 'Limpeza',
    'fas fa-tools' => 'Ferramentas',
    'fas fa-seedling' => 'Plantas/Natureza',
    'fas fa-handshake' => 'Parceria/Serviços',
    'fas fa-box-open' => 'Caixas/Embalagens',
    'fas fa-laptop' => 'Tecnologia',
    'fas fa-truck' => 'Transporte',
    'fas fa-industry' => 'Indústria',
    'fas fa-flask' => 'Química/Laboratório',
    'fas fa-wrench' => 'Manutenção',
    'fas fa-paint-brush' => 'Arte/Design',
    'fas fa-tshirt' => 'Vestuário',
    'fas fa-home' => 'Casa/Móveis',
    'fas fa-heartbeat' => 'Saúde',
    'fas fa-graduation-cap' => 'Educação',
    'fas fa-car' => 'Automotivo',
    'fas fa-fire' => 'Energia',
    'fas fa-leaf' => 'Sustentável'
];

include '../includes/header.php';
?>

<style>
:root {
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-danger: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    --gradient-fornecedores: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);
    --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
    --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
}

.categoria-card {
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
    margin-bottom: 1.5rem;
    position: relative;
}

.categoria-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.categoria-card.inativo {
    opacity: 0.6;
    filter: grayscale(0.3);
}

.categoria-header {
    padding: 1.5rem;
    color: white;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.categoria-icon-display {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-right: 1rem;
    backdrop-filter: blur(10px);
}

.categoria-info h5 {
    margin: 0;
    font-weight: 700;
}

.categoria-info p {
    margin: 0;
    opacity: 0.8;
    font-size: 0.9rem;
}

.categoria-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: rgba(255,255,255,0.2);
    color: white;
    transition: all 0.3s ease;
}

.btn-icon:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.1);
}

.categoria-stats {
    padding: 1rem 1.5rem;
    background: white;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.form-categoria {
    background: white;
    border: none;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    overflow: hidden;
}

.form-header {
    background: var(--gradient-fornecedores);
    color: white;
    padding: 1.5rem;
    text-align: center;
}

.color-picker {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.color-option {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 3px solid transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.color-option:hover {
    transform: scale(1.1);
    border-color: rgba(0,0,0,0.2);
}

.color-option.selected {
    border-color: #333;
    transform: scale(1.1);
}

.color-option.selected::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: bold;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.icon-picker {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
    margin-top: 0.5rem;
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
}

.icon-option {
    padding: 0.75rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.icon-option:hover {
    border-color: #fd7e14;
    background: rgba(253, 126, 20, 0.1);
}

.icon-option.selected {
    border-color: #fd7e14;
    background: rgba(253, 126, 20, 0.2);
    color: #fd7e14;
}

.icon-option i {
    font-size: 1.2rem;
    margin-bottom: 0.25rem;
}

.icon-option small {
    display: block;
    font-size: 0.7rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">
            <i class="fas fa-tags text-warning me-2"></i>
            Categorias de Fornecedores
        </h1>
        <p class="text-muted mb-0">Gerencie as categorias para organizar seus fornecedores</p>
    </div>
    <div class="d-flex gap-2">
        <a href="listar.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Voltar para Fornecedores
        </a>
        <button onclick="mostrarFormCategoria()" class="btn btn-warning">
            <i class="fas fa-plus-circle me-2"></i> Nova Categoria
        </button>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show slide-in" role="alert">
        <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Lista de Categorias -->
<div class="row">
    <?php if ($result_categorias && $result_categorias->num_rows > 0): ?>
        <?php while ($categoria = $result_categorias->fetch_assoc()): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card categoria-card <?php echo !$categoria['ativo'] ? 'inativo' : ''; ?> slide-in">
                    <div class="categoria-header" style="background: <?php echo $categoria['cor']; ?>;">
                        <div class="d-flex align-items-center">
                            <div class="categoria-icon-display">
                                <i class="<?php echo $categoria['icone']; ?>"></i>
                            </div>
                            <div class="categoria-info">
                                <h5><?php echo htmlspecialchars($categoria['nome']); ?></h5>
                                <?php if (!empty($categoria['descricao'])): ?>
                                    <p><?php echo htmlspecialchars($categoria['descricao']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="categoria-actions">
                            <button onclick="editarCategoria(<?php echo $categoria['id']; ?>)" 
                                    class="btn-icon" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="toggleStatus(<?php echo $categoria['id']; ?>)" 
                                    class="btn-icon" title="<?php echo $categoria['ativo'] ? 'Desativar' : 'Ativar'; ?>">
                                <i class="fas fa-<?php echo $categoria['ativo'] ? 'eye-slash' : 'eye'; ?>"></i>
                            </button>
                            <?php if ($categoria['total_fornecedores'] == 0): ?>
                                <button onclick="excluirCategoria(<?php echo $categoria['id']; ?>, '<?php echo addslashes($categoria['nome']); ?>')" 
                                        class="btn-icon" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="categoria-stats">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold"><?php echo $categoria['total_fornecedores']; ?></span>
                                <small class="text-muted">fornecedor(es)</small>
                            </div>
                            <div>
                                <span class="badge bg-<?php echo $categoria['ativo'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $categoria['ativo'] ? 'Ativa' : 'Inativa'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="empty-state">
                <i class="fas fa-tags"></i>
                <h4>Nenhuma categoria cadastrada</h4>
                <p class="mb-4">Comece criando categorias para organizar seus fornecedores.</p>
                <button onclick="mostrarFormCategoria()" class="btn btn-warning">
                    <i class="fas fa-plus-circle me-2"></i> Criar Primeira Categoria
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Cores pré-definidas
const coresPredefinidas = [
    '#28a745', '#dc3545', '#fd7e14', '#ffc107', '#6f42c1', '#e83e8c',
    '#17a2b8', '#6c757d', '#007bff', '#20c997', '#ff6b6b', '#4facfe',
    '#11998e', '#38ef7d', '#667eea', '#764ba2', '#f093fb', '#f5576c'
];

// Função para mostrar formulário de categoria
function mostrarFormCategoria(categoria = null) {
    const isEdit = categoria !== null;
    
    Swal.fire({
        title: isEdit ? 'Editar Categoria' : 'Nova Categoria',
        html: `
            <form id="formCategoria" class="text-start">
                <input type="hidden" id="categoria-id" value="${isEdit ? categoria.id : ''}">
                <input type="hidden" id="acao" value="${isEdit ? 'editar' : 'adicionar'}">
                
                <div class="mb-3">
                    <label class="form-label">Nome da Categoria *</label>
                    <input type="text" class="form-control" id="categoria-nome" 
                           value="${isEdit ? categoria.nome : ''}" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" id="categoria-descricao" rows="3"
                              placeholder="Descreva brevemente esta categoria...">${isEdit ? categoria.descricao : ''}</textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Cor</label>
                    <input type="color" class="form-control form-control-color" id="categoria-cor" 
                           value="${isEdit ? categoria.cor : '#fd7e14'}">
                    <div class="color-picker">
                        ${coresPredefinidas.map(cor => `
                            <div class="color-option ${isEdit && categoria.cor === cor ? 'selected' : ''}" 
                                 style="background: ${cor}" 
                                 onclick="selecionarCor('${cor}')"></div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Ícone</label>
                    <div class="icon-picker">
                        ${Object.entries(<?php echo json_encode($icones_disponiveis); ?>).map(([icone, nome]) => `
                            <div class="icon-option ${isEdit && categoria.icone === icone ? 'selected' : ''}" 
                                 onclick="selecionarIcone('${icone}')">
                                <i class="${icone}"></i>
                                <small>${nome}</small>
                            </div>
                        `).join('')}
                    </div>
                    <input type="hidden" id="categoria-icone" value="${isEdit ? categoria.icone : 'fas fa-tag'}">
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: isEdit ? 'Salvar Alterações' : 'Criar Categoria',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#fd7e14',
        width: '600px',
        preConfirm: () => {
            const nome = document.getElementById('categoria-nome').value.trim();
            const descricao = document.getElementById('categoria-descricao').value.trim();
            const cor = document.getElementById('categoria-cor').value;
            const icone = document.getElementById('categoria-icone').value;
            
            if (!nome) {
                Swal.showValidationMessage('Nome da categoria é obrigatório');
                return false;
            }
            
            return { nome, descricao, cor, icone };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            salvarCategoria(result.value, isEdit ? categoria.id : null, isEdit);
        }
    });
}

// Função para selecionar cor
function selecionarCor(cor) {
    document.getElementById('categoria-cor').value = cor;
    document.querySelectorAll('.color-option').forEach(el => el.classList.remove('selected'));
    event.target.classList.add('selected');
}

// Função para selecionar ícone
function selecionarIcone(icone) {
    document.getElementById('categoria-icone').value = icone;
    document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
    event.target.classList.add('selected');
}

// Função para salvar categoria
function salvarCategoria(dados, id, isEdit) {
    const formData = new FormData();
    formData.append('acao', isEdit ? 'editar' : 'adicionar');
    formData.append('nome', dados.nome);
    formData.append('descricao', dados.descricao);
    formData.append('cor', dados.cor);
    formData.append('icone', dados.icone);
    
    if (isEdit) {
        formData.append('id', id);
    }
    
    fetch('categorias.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        showNotification(
            isEdit ? 'Categoria atualizada com sucesso!' : 'Categoria criada com sucesso!', 
            'success'
        );
        setTimeout(() => location.reload(), 1500);
    })
    .catch(error => {
        showNotification('Erro ao salvar categoria', 'danger');
        console.error('Erro:', error);
    });
}

// Função para editar categoria
function editarCategoria(id) {
    // Buscar dados da categoria
    fetch(`get_categoria.php?id=${id}`)
        .then(response => response.json())
        .then(categoria => {
            mostrarFormCategoria(categoria);
        })
        .catch(error => {
            showNotification('Erro ao carregar categoria', 'danger');
            console.error('Erro:', error);
        });
}

// Função para toggle status
function toggleStatus(id) {
    const formData = new FormData();
    formData.append('acao', 'toggle_status');
    formData.append('id', id);
    
    fetch('categorias.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        showNotification('Status atualizado!', 'success');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        showNotification('Erro ao atualizar status', 'danger');
        console.error('Erro:', error);
    });
}

// Função para excluir categoria
function excluirCategoria(id, nome) {
    Swal.fire({
        title: 'Confirmar Exclusão',
        html: `Tem certeza que deseja excluir a categoria <strong>"${nome}"</strong>?<br><br><span class="text-danger">Esta ação não pode ser desfeita!</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('acao', 'excluir');
            formData.append('id', id);
            
            fetch('categorias.php', {
                method: 'POST',
                body: formData
            })
            .then(() => {
                showNotification('Categoria excluída com sucesso!', 'success');
                setTimeout(() => location.reload(), 1500);
            })
            .catch(error => {
                showNotification('Erro ao excluir categoria', 'danger');
                console.error('Erro:', error);
            });
        }
    });
}

// Sistema de notificações
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
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

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
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
});

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N para nova categoria
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        mostrarFormCategoria();
    }
});

// Adicionar SweetAlert2 se não estiver incluído
if (typeof Swal === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}
</script>

<!-- SweetAlert2 para confirmações -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include '../includes/footer.php'; ?>