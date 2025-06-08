<?php
// fornecedores/editar.php
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

// Verificar se o ID do fornecedor foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Fornecedor não especificado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$fornecedor_id = intval($_GET['id']);

// Buscar dados do fornecedor
$sql_fornecedor = "SELECT f.*, 
                   GROUP_CONCAT(DISTINCT fc.categoria_id) as categorias_selecionadas
                   FROM fornecedores f
                   LEFT JOIN fornecedor_categorias fc ON f.id = fc.fornecedor_id
                   WHERE f.id = $fornecedor_id
                   GROUP BY f.id";
$result_fornecedor = $conn->query($sql_fornecedor);

if (!$result_fornecedor || $result_fornecedor->num_rows == 0) {
    $_SESSION['msg'] = "Fornecedor não encontrado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$fornecedor = $result_fornecedor->fetch_assoc();
$categorias_selecionadas = !empty($fornecedor['categorias_selecionadas']) ? 
    explode(',', $fornecedor['categorias_selecionadas']) : [];

// Processar o formulário se foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $empresa = trim($_POST['empresa']);
    $cnpj = trim($_POST['cnpj']);
    $telefone = trim($_POST['telefone']);
    $whatsapp = trim($_POST['whatsapp']);
    $email = trim($_POST['email']);
    $endereco = trim($_POST['endereco']);
    $cidade = trim($_POST['cidade']);
    $estado = trim($_POST['estado']);
    $cep = trim($_POST['cep']);
    $observacoes = trim($_POST['observacoes']);
    $tipo_fornecedor = $_POST['tipo_fornecedor'];
    $status = $_POST['status'];
    $avaliacao = floatval($_POST['avaliacao']);
    $prazo_entrega_padrao = intval($_POST['prazo_entrega_padrao']);
    $forma_pagamento_preferida = trim($_POST['forma_pagamento_preferida']);
    $limite_credito = floatval(str_replace(',', '.', str_replace('.', '', $_POST['limite_credito'])));
    $categorias = isset($_POST['categorias']) ? $_POST['categorias'] : [];
    
    // Validações básicas
    $errors = [];
    
    if (empty($nome)) {
        $errors[] = "Nome é obrigatório";
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido";
    }
    
    if (!empty($cnpj)) {
        // Limpar CNPJ
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) != 14) {
            $errors[] = "CNPJ deve ter 14 dígitos";
        }
    }
    
    // Se não há erros, atualizar no banco
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Preparar statement para atualização
            $sql = "UPDATE fornecedores SET 
                nome = ?, empresa = ?, cnpj = ?, telefone = ?, whatsapp = ?, email = ?, 
                endereco = ?, cidade = ?, estado = ?, cep = ?, observacoes = ?, 
                tipo_fornecedor = ?, status = ?, avaliacao = ?, prazo_entrega_padrao = ?,
                forma_pagamento_preferida = ?, limite_credito = ?, data_ultima_atualizacao = NOW()
                WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssssssdisdi", 
                $nome, $empresa, $cnpj, $telefone, $whatsapp, $email, $endereco, $cidade, $estado, $cep,
                $observacoes, $tipo_fornecedor, $status, $avaliacao, $prazo_entrega_padrao,
                $forma_pagamento_preferida, $limite_credito, $fornecedor_id
            );
            
            $stmt->execute();
            
            // Remover categorias antigas
            $conn->query("DELETE FROM fornecedor_categorias WHERE fornecedor_id = $fornecedor_id");
            
            // Inserir novas categorias se foram selecionadas
            if (!empty($categorias)) {
                $sql_cat = "INSERT INTO fornecedor_categorias (fornecedor_id, categoria_id) VALUES (?, ?)";
                $stmt_cat = $conn->prepare($sql_cat);
                
                foreach ($categorias as $categoria_id) {
                    $stmt_cat->bind_param("ii", $fornecedor_id, $categoria_id);
                    $stmt_cat->execute();
                }
            }
            
            $conn->commit();
            $_SESSION['msg'] = "Fornecedor atualizado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: detalhes.php?id=" . $fornecedor_id);
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Erro ao atualizar fornecedor: " . $e->getMessage();
        }
    }
}

// Buscar categorias disponíveis
$sql_categorias = "SELECT id, nome, cor, icone FROM categorias_fornecedores WHERE ativo = 1 ORDER BY nome";
$result_categorias = $conn->query($sql_categorias);
$categorias_disponiveis = [];
while ($row = $result_categorias->fetch_assoc()) {
    $categorias_disponiveis[] = $row;
}

// Estados brasileiros
$estados = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
    'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
    'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
    'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
    'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
    'SE' => 'Sergipe', 'TO' => 'Tocantins'
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

.form-card {
    background: white;
    border: none;
    border-radius: 20px;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s ease;
    overflow: hidden;
}

.form-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-hover);
}

.form-header {
    background: var(--gradient-fornecedores);
    color: white;
    padding: 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.form-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.1; }
    50% { transform: scale(1.1); opacity: 0.2; }
}

.form-section {
    border-left: 4px solid transparent;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: 10px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.form-section.basic-info {
    border-left-color: #fd7e14;
    background: linear-gradient(135deg, rgba(253, 126, 20, 0.05), rgba(232, 62, 140, 0.05));
}

.form-section.contact-info {
    border-left-color: #28a745;
    background: linear-gradient(135deg, rgba(17, 153, 142, 0.05), rgba(56, 239, 125, 0.05));
}

.form-section.address-info {
    border-left-color: #007bff;
    background: linear-gradient(135deg, rgba(79, 172, 254, 0.05), rgba(0, 242, 254, 0.05));
}

.form-section.business-info {
    border-left-color: #6f42c1;
    background: linear-gradient(135deg, rgba(111, 66, 193, 0.05), rgba(147, 112, 219, 0.05));
}

.form-section.categories-info {
    border-left-color: #e83e8c;
    background: linear-gradient(135deg, rgba(240, 147, 251, 0.05), rgba(245, 87, 108, 0.05));
}

.form-section h5 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.form-section h5 i {
    margin-right: 0.5rem;
    font-size: 1.2rem;
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    background: white;
}

.form-control:focus, .form-select:focus {
    border-color: #fd7e14;
    box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25);
    background: white;
}

.btn-modern {
    border-radius: 12px;
    padding: 0.75rem 2rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-modern:hover::before {
    left: 100%;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.btn-primary-modern {
    background: var(--gradient-fornecedores);
    color: white;
}

.btn-secondary-modern {
    background: var(--gradient-primary);
    color: white;
}

.btn-danger-modern {
    background: var(--gradient-danger);
    color: white;
}

.categoria-checkbox {
    position: relative;
    margin-bottom: 1rem;
}

.categoria-checkbox input[type="checkbox"] {
    display: none;
}

.categoria-checkbox .categoria-label {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    border: 2px solid #e9ecef;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.categoria-checkbox input[type="checkbox"]:checked + .categoria-label {
    border-color: var(--categoria-cor, #fd7e14);
    background: var(--categoria-cor-light, rgba(253, 126, 20, 0.1));
    transform: translateY(-2px);
    box-shadow: var(--shadow-soft);
}

.categoria-checkbox .categoria-label:hover {
    border-color: var(--categoria-cor, #fd7e14);
    transform: translateY(-1px);
}

.categoria-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.1rem;
    color: white;
}

.categoria-info h6 {
    margin: 0;
    font-weight: 600;
    color: #495057;
}

.categoria-info small {
    color: #6c757d;
}

.rating-input {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stars-container {
    display: flex;
    gap: 0.25rem;
}

.star {
    font-size: 1.5rem;
    color: #dee2e6;
    cursor: pointer;
    transition: all 0.2s ease;
}

.star:hover,
.star.active {
    color: #ffc107;
    transform: scale(1.1);
}

.input-group {
    position: relative;
}

.input-group .form-control {
    padding-left: 3rem;
}

.input-group-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 3;
}

.floating-label {
    position: relative;
}

.floating-label .form-control,
.floating-label .form-select {
    padding-top: 1.625rem;
    padding-bottom: 0.625rem;
}

.floating-label label {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    padding: 1rem;
    pointer-events: none;
    border: 1px solid transparent;
    transform-origin: 0 0;
    transition: all 0.1s ease-in-out;
    color: #6c757d;
}

.floating-label .form-control:focus ~ label,
.floating-label .form-control:not(:placeholder-shown) ~ label,
.floating-label .form-select:focus ~ label,
.floating-label .form-select:not([value=""]) ~ label {
    opacity: 0.65;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
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
    animation: slideInUp 0.6s ease-out forwards;
}
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1">
                        <i class="fas fa-edit text-warning me-2"></i>
                        Editar Fornecedor
                    </h1>
                    <p class="text-muted mb-0">Atualize as informações do fornecedor</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="detalhes.php?id=<?php echo $fornecedor_id; ?>" class="btn btn-outline-secondary btn-modern">
                        <i class="fas fa-arrow-left me-2"></i> Voltar aos Detalhes
                    </a>
                    <a href="listar.php" class="btn btn-outline-info btn-modern">
                        <i class="fas fa-list me-2"></i> Lista de Fornecedores
                    </a>
                </div>
            </div>

            <!-- Exibir erros se houver -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show slide-in" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Erro ao atualizar:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulário -->
            <form method="POST" id="fornecedorForm" novalidate>
                <div class="form-card slide-in">
                    <div class="form-header">
                        <h3 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Editar: <?php echo htmlspecialchars($fornecedor['nome']); ?>
                        </h3>
                        <p class="mb-0 mt-2 opacity-75">Atualize as informações do fornecedor</p>
                    </div>
                    
                    <div class="card-body p-4">
                        
                        <!-- Informações Básicas -->
                        <div class="form-section basic-info">
                            <h5>
                                <i class="fas fa-user text-warning"></i>
                                Informações Básicas
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               placeholder=" " required value="<?php echo htmlspecialchars($fornecedor['nome']); ?>">
                                        <label for="nome">Nome do Fornecedor *</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <input type="text" class="form-control" id="empresa" name="empresa" 
                                               placeholder=" " value="<?php echo htmlspecialchars($fornecedor['empresa']); ?>">
                                        <label for="empresa">Nome da Empresa</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <input type="text" class="form-control" id="cnpj" name="cnpj" 
                                               placeholder=" " value="<?php echo htmlspecialchars($fornecedor['cnpj']); ?>">
                                        <label for="cnpj">CNPJ</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <select class="form-select" id="tipo_fornecedor" name="tipo_fornecedor" required>
                                            <option value="">Selecione...</option>
                                            <option value="produtos" <?php echo $fornecedor['tipo_fornecedor'] == 'produtos' ? 'selected' : ''; ?>>Produtos</option>
                                            <option value="servicos" <?php echo $fornecedor['tipo_fornecedor'] == 'servicos' ? 'selected' : ''; ?>>Serviços</option>
                                            <option value="ambos" <?php echo $fornecedor['tipo_fornecedor'] == 'ambos' ? 'selected' : ''; ?>>Ambos</option>
                                        </select>
                                        <label for="tipo_fornecedor">Tipo de Fornecedor *</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informações de Contato -->
                        <div class="form-section contact-info">
                            <h5>
                                <i class="fas fa-phone text-success"></i>
                                Informações de Contato
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <i class="fas fa-phone input-group-icon"></i>
                                        <input type="text" class="form-control" id="telefone" name="telefone" 
                                               placeholder="Telefone" value="<?php echo htmlspecialchars($fornecedor['telefone']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <i class="fab fa-whatsapp input-group-icon text-success"></i>
                                        <input type="text" class="form-control" id="whatsapp" name="whatsapp" 
                                               placeholder="WhatsApp" value="<?php echo htmlspecialchars($fornecedor['whatsapp']); ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-group">
                                        <i class="fas fa-envelope input-group-icon"></i>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="E-mail" value="<?php echo htmlspecialchars($fornecedor['email']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Endereço -->
                        <div class="form-section address-info">
                            <h5>
                                <i class="fas fa-map-marker-alt text-info"></i>
                                Endereço
                            </h5>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="floating-label">
                                        <input type="text" class="form-control" id="endereco" name="endereco" 
                                               placeholder=" " value="<?php echo htmlspecialchars($fornecedor['endereco']); ?>">
                                        <label for="endereco">Endereço Completo</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <input type="text" class="form-control" id="cidade" name="cidade" 
                                               placeholder=" " value="<?php echo htmlspecialchars($fornecedor['cidade']); ?>">
                                        <label for="cidade">Cidade</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="floating-label">
                                        <select class="form-select" id="estado" name="estado">
                                            <option value="">UF</option>
                                            <?php foreach ($estados as $sigla => $nome_estado): ?>
                                                <option value="<?php echo $sigla; ?>" <?php echo $fornecedor['estado'] == $sigla ? 'selected' : ''; ?>>
                                                    <?php echo $sigla; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="estado">Estado</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="floating-label">
                                        <input type="text" class="form-control" id="cep" name="cep" 
                                               placeholder=" " value="<?php echo htmlspecialchars($fornecedor['cep']); ?>">
                                        <label for="cep">CEP</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informações Comerciais -->
                        <div class="form-section business-info">
                            <h5>
                                <i class="fas fa-handshake text-purple"></i>
                                Informações Comerciais
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Avaliação</label>
                                    <div class="rating-input">
                                        <div class="stars-container" id="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star star <?php echo $i <= $fornecedor['avaliacao'] ? 'active' : ''; ?>" data-rating="<?php echo $i; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="ms-2 text-muted" id="rating-text"><?php echo number_format($fornecedor['avaliacao'], 1); ?> estrelas</span>
                                        <input type="hidden" name="avaliacao" id="avaliacao" value="<?php echo $fornecedor['avaliacao']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <input type="number" class="form-control" id="prazo_entrega_padrao" name="prazo_entrega_padrao" 
                                               placeholder=" " min="1" max="365" value="<?php echo $fornecedor['prazo_entrega_padrao']; ?>">
                                        <label for="prazo_entrega_padrao">Prazo de Entrega (dias)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <input type="text" class="form-control" id="forma_pagamento_preferida" name="forma_pagamento_preferida" 
                                               placeholder=" " value="<?php echo htmlspecialchars($fornecedor['forma_pagamento_preferida']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <input type="text" class="form-control money-input" id="limite_credito" name="limite_credito" 
                                               placeholder=" " value="<?php echo number_format($fornecedor['limite_credito'], 2, ',', '.'); ?>">
                                        <label for="limite_credito">Limite de Crédito (R$)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="ativo" <?php echo $fornecedor['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="inativo" <?php echo $fornecedor['status'] == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                        </select>
                                        <label for="status">Status</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Categorias -->
                        <div class="form-section categories-info">
                            <h5>
                                <i class="fas fa-tags text-pink"></i>
                                Categorias do Fornecedor
                            </h5>
                            <p class="text-muted mb-3">Selecione as categorias que melhor descrevem este fornecedor:</p>
                            
                            <div class="row g-3">
                                <?php foreach ($categorias_disponiveis as $categoria): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="categoria-checkbox">
                                            <input type="checkbox" id="cat_<?php echo $categoria['id']; ?>" 
                                                   name="categorias[]" value="<?php echo $categoria['id']; ?>"
                                                   <?php echo in_array($categoria['id'], $categorias_selecionadas) ? 'checked' : ''; ?>>
                                            <label for="cat_<?php echo $categoria['id']; ?>" class="categoria-label"
                                                   style="--categoria-cor: <?php echo $categoria['cor']; ?>; --categoria-cor-light: <?php echo $categoria['cor']; ?>20;">
                                                <div class="categoria-icon" style="background: <?php echo $categoria['cor']; ?>;">
                                                    <i class="<?php echo $categoria['icone']; ?>"></i>
                                                </div>
                                                <div class="categoria-info">
                                                    <h6><?php echo htmlspecialchars($categoria['nome']); ?></h6>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Observações -->
                        <div class="form-section">
                            <h5>
                                <i class="fas fa-sticky-note text-secondary"></i>
                                Observações Adicionais
                            </h5>
                            <div class="floating-label">
                                <textarea class="form-control" id="observacoes" name="observacoes" 
                                          placeholder=" " rows="4"><?php echo htmlspecialchars($fornecedor['observacoes']); ?></textarea>
                                <label for="observacoes">Observações sobre o fornecedor</label>
                            </div>
                        </div>

                        <!-- Informações do Sistema -->
                        <div class="form-section">
                            <h5>
                                <i class="fas fa-info-circle text-info"></i>
                                Informações do Sistema
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <small class="text-muted">Cadastrado em:</small>
                                        <div><?php echo date('d/m/Y H:i', strtotime($fornecedor['data_cadastro'])); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <small class="text-muted">Última atualização:</small>
                                        <div><?php echo date('d/m/Y H:i', strtotime($fornecedor['data_ultima_atualizacao'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Campos marcados com * são obrigatórios
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" onclick="confirmarExclusao()" class="btn btn-danger-modern btn-modern">
                                    <i class="fas fa-trash me-2"></i> Excluir Fornecedor
                                </button>
                                <a href="detalhes.php?id=<?php echo $fornecedor_id; ?>" class="btn btn-secondary-modern btn-modern">
                                    <i class="fas fa-times me-2"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary-modern btn-modern" id="btnSalvar">
                                    <i class="fas fa-save me-2"></i> Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Máscaras para campos (mesmo código do cadastro)
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para CNPJ
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });
    }

    // Máscara para telefones
    function applyPhoneMask(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            }
        });
    }

    const telefoneInput = document.getElementById('telefone');
    const whatsappInput = document.getElementById('whatsapp');
    if (telefoneInput) applyPhoneMask(telefoneInput);
    if (whatsappInput) applyPhoneMask(whatsappInput);

    // Máscara para CEP
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        // Buscar endereço por CEP
        cepInput.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                buscarEnderecoPorCEP(cep);
            }
        });
    }

    // Máscara para valores monetários
    const moneyInputs = document.querySelectorAll('.money-input');
    moneyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = value;
        });
    });

    // Sistema de avaliação por estrelas
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('avaliacao');
    const ratingText = document.getElementById('rating-text');

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            ratingInput.value = rating + '.0';
            ratingText.textContent = rating + '.0 estrelas';
            
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });

        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#dee2e6';
                }
            });
        });
    });

    document.getElementById('rating-stars').addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingInput.value);
        stars.forEach((s, index) => {
            if (index < currentRating) {
                s.style.color = '#ffc107';
            } else {
                s.style.color = '#dee2e6';
            }
        });
    });

    // Animações de entrada
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

// Função para buscar endereço por CEP
async function buscarEnderecoPorCEP(cep) {
    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();
        
        if (!data.erro) {
            document.getElementById('endereco').value = data.logradouro;
            document.getElementById('cidade').value = data.localidade;
            document.getElementById('estado').value = data.uf;
            
            showNotification('Endereço atualizado automaticamente!', 'success');
        } else {
            showNotification('CEP não encontrado', 'warning');
        }
    } catch (error) {
        console.error('Erro ao buscar CEP:', error);
    }
}

// Validação do formulário
document.getElementById('fornecedorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const nome = document.getElementById('nome').value.trim();
    const tipo = document.getElementById('tipo_fornecedor').value;
    
    if (!nome) {
        showNotification('Nome do fornecedor é obrigatório', 'danger');
        document.getElementById('nome').focus();
        return;
    }
    
    if (!tipo) {
        showNotification('Tipo de fornecedor é obrigatório', 'danger');
        document.getElementById('tipo_fornecedor').focus();
        return;
    }
    
    // Validar CNPJ se preenchido
    const cnpj = document.getElementById('cnpj').value.replace(/\D/g, '');
    if (cnpj && cnpj.length !== 14) {
        showNotification('CNPJ deve ter 14 dígitos', 'danger');
        document.getElementById('cnpj').focus();
        return;
    }
    
    // Validar email se preenchido
    const email = document.getElementById('email').value;
    if (email && !isValidEmail(email)) {
        showNotification('Email inválido', 'danger');
        document.getElementById('email').focus();
        return;
    }
    
    // Mostrar loading
    const btnSalvar = document.getElementById('btnSalvar');
    const originalText = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Salvando...';
    btnSalvar.disabled = true;
    
    // Submeter formulário
    this.submit();
});

// Função para validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Função para confirmar exclusão
function confirmarExclusao() {
    Swal.fire({
        title: 'Confirmar Exclusão',
        html: `
            <div class="text-start">
                <p>Tem certeza que deseja excluir o fornecedor <strong>"<?php echo addslashes($fornecedor['nome']); ?>"</strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atenção:</strong> Esta ação irá excluir também:
                    <ul class="mb-0 mt-2">
                        <li>Todos os pedidos deste fornecedor</li>
                        <li>Histórico de comunicações</li>
                        <li>Contatos associados</li>
                    </ul>
                </div>
                <p class="text-danger"><strong>Esta ação não pode ser desfeita!</strong></p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash-alt me-2"></i>Sim, excluir',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Excluindo...',
                text: 'Aguarde enquanto o fornecedor é excluído.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            window.location.href = `excluir.php?id=<?php echo $fornecedor_id; ?>`;
        }
    });
}

// Sistema de notificações
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'danger' ? 'exclamation-triangle' : 'info-circle')} me-2"></i>
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
    // Ctrl/Cmd + S para salvar
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('fornecedorForm').dispatchEvent(new Event('submit'));
    }
    
    // Esc para cancelar
    if (e.key === 'Escape') {
        if (confirm('Deseja cancelar a edição? As alterações não salvas serão perdidas.')) {
            window.location.href = 'detalhes.php?id=<?php echo $fornecedor_id; ?>';
        }
    }
    
    // Ctrl/Cmd + Delete para excluir
    if ((e.ctrlKey || e.metaKey) && e.key === 'Delete') {
        e.preventDefault();
        confirmarExclusao();
    }
});

// Detecção de mudanças não salvas
let formChanged = false;
const formInputs = document.querySelectorAll('#fornecedorForm input, #fornecedorForm select, #fornecedorForm textarea');
formInputs.forEach(input => {
    const originalValue = input.value;
    input.addEventListener('input', function() {
        if (this.value !== originalValue) {
            formChanged = true;
        }
    });
});

// Aviso ao sair da página com mudanças não salvas
window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
        return '';
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