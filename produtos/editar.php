<?php
// produtos/editar.php - Versão Melhorada
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

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['msg'] = "ID do produto não fornecido";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$id = intval($_GET['id']);
$errors = [];

// Verificar quais colunas existem na tabela produtos
$columns_check = $conn->query("SHOW COLUMNS FROM produtos");
$existing_columns = [];
while ($column = $columns_check->fetch_assoc()) {
    $existing_columns[] = $column['Field'];
}

// Definir quais campos extras estão disponíveis
$has_categoria = in_array('categoria', $existing_columns);
$has_marca = in_array('marca', $existing_columns);
$has_sku = in_array('sku', $existing_columns);
$has_codigo_barras = in_array('codigo_barras', $existing_columns);
$has_peso = in_array('peso', $existing_columns);
$has_dimensoes = in_array('dimensoes', $existing_columns);
$has_observacoes = in_array('observacoes', $existing_columns);

// Buscar dados do produto
$sql = "SELECT * FROM produtos WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    $_SESSION['msg'] = "Produto não encontrado";
    $_SESSION['msg_type'] = "danger";
    header("Location: listar.php");
    exit;
}

$produto = $result->fetch_assoc();

// Buscar informações de vendas do produto
$sql_vendas = "SELECT 
    COUNT(*) as total_vendas,
    SUM(iv.quantidade) as quantidade_vendida,
    MAX(v.data_venda) as ultima_venda
    FROM itens_venda iv
    JOIN vendas v ON iv.venda_id = v.id
    WHERE iv.produto_id = $id";
$result_vendas = $conn->query($sql_vendas);
$stats_vendas = $result_vendas->fetch_assoc();

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validações
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $valor_venda = str_replace(',', '.', $_POST['valor_venda'] ?? '');
    
    // Campos extras (apenas se as colunas existirem)
    $categoria = $has_categoria ? trim($_POST['categoria'] ?? '') : '';
    $codigo_barras = $has_codigo_barras ? trim($_POST['codigo_barras'] ?? '') : '';
    $sku = $has_sku ? trim($_POST['sku'] ?? '') : '';
    $peso = $has_peso ? ($_POST['peso'] ?? '') : '';
    $dimensoes = $has_dimensoes ? trim($_POST['dimensoes'] ?? '') : '';
    $marca = $has_marca ? trim($_POST['marca'] ?? '') : '';
    $observacoes = $has_observacoes ? trim($_POST['observacoes'] ?? '') : '';
    
    // Validar campos obrigatórios
    if (empty($nome)) {
        $errors[] = "Nome do produto é obrigatório";
    }
    
    if (empty($valor_venda) || !is_numeric($valor_venda) || $valor_venda <= 0) {
        $errors[] = "Valor de venda deve ser um número válido maior que zero";
    }
    
    // Verificar se o nome já existe (exceto para este produto)
    if (!empty($nome)) {
        $check_sql = "SELECT id FROM produtos WHERE nome = '" . $conn->real_escape_string($nome) . "' AND id != $id";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            $errors[] = "Já existe outro produto com este nome";
        }
    }
    
    // Verificar SKU único se fornecido e se a coluna existe (exceto para este produto)
    if ($has_sku && !empty($sku)) {
        $check_sku = "SELECT id FROM produtos WHERE sku = '" . $conn->real_escape_string($sku) . "' AND id != $id";
        $check_result = $conn->query($check_sku);
        if ($check_result->num_rows > 0) {
            $errors[] = "SKU já está em uso por outro produto";
        }
    }
    
    // Se não há erros, atualizar no banco
    if (empty($errors)) {
        // Construir SQL dinamicamente baseado nas colunas disponíveis
        $updates = [
            "nome = ?",
            "descricao = ?", 
            "valor_venda = ?"
        ];
        $params = [$nome, $descricao, $valor_venda];
        $types = 'ssd';
        
        if ($has_categoria) {
            $updates[] = "categoria = ?";
            $params[] = $categoria;
            $types .= 's';
        }
        
        if ($has_marca) {
            $updates[] = "marca = ?";
            $params[] = $marca;
            $types .= 's';
        }
        
        if ($has_sku) {
            $updates[] = "sku = ?";
            $params[] = $sku;
            $types .= 's';
        }
        
        if ($has_codigo_barras) {
            $updates[] = "codigo_barras = ?";
            $params[] = $codigo_barras;
            $types .= 's';
        }
        
        if ($has_peso) {
            $updates[] = "peso = ?";
            $params[] = $peso ?: null;
            $types .= 'd';
        }
        
        if ($has_dimensoes) {
            $updates[] = "dimensoes = ?";
            $params[] = $dimensoes;
            $types .= 's';
        }
        
        if ($has_observacoes) {
            $updates[] = "observacoes = ?";
            $params[] = $observacoes;
            $types .= 's';
        }
        
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE produtos SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Registrar log se a função existir
            if (function_exists('registrar_log')) {
                registrar_log('Edição de Produto', "Produto '$nome' (ID: $id) foi atualizado");
            }
            
            $_SESSION['msg'] = "Produto atualizado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: listar.php");
            exit;
        } else {
            $errors[] = "Erro ao atualizar produto: " . $conn->error;
        }
    }
}

// Buscar categorias existentes para sugestões (apenas se a coluna existir)
$categorias_existentes = [];
if ($has_categoria) {
    $sql_categorias = "SELECT DISTINCT categoria FROM produtos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria";
    $result_categorias = $conn->query($sql_categorias);
    while ($row = $result_categorias->fetch_assoc()) {
        $categorias_existentes[] = $row['categoria'];
    }
}

// Buscar marcas existentes para sugestões (apenas se a coluna existir)
$marcas_existentes = [];
if ($has_marca) {
    $sql_marcas = "SELECT DISTINCT marca FROM produtos WHERE marca IS NOT NULL AND marca != '' ORDER BY marca";
    $result_marcas = $conn->query($sql_marcas);
    while ($row = $result_marcas->fetch_assoc()) {
        $marcas_existentes[] = $row['marca'];
    }
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

.form-card {
    background: white;
    border: none;
    border-radius: 20px;
    box-shadow: var(--shadow-soft);
    overflow: hidden;
    transition: all 0.3s ease;
}

.form-card:hover {
    box-shadow: var(--shadow-hover);
}

.form-header {
    background: var(--gradient-warning);
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
    left: -50%;
    width: 200%;
    height: 200%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
    animation: float 20s linear infinite;
}

@keyframes float {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

.form-icon {
    background: rgba(255,255,255,0.2);
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
    position: relative;
    z-index: 1;
}

.form-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-left: 4px solid #f093fb;
    transition: all 0.3s ease;
}

.form-section:hover {
    background: #fff0f8;
    border-left-color: #f5576c;
}

.form-section h5 {
    color: #f093fb;
    margin-bottom: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.form-control:focus, .form-select:focus {
    border-color: #f093fb;
    box-shadow: 0 0 0 0.2rem rgba(240, 147, 251, 0.25);
    transform: translateY(-2px);
}

.input-group-text {
    background: var(--gradient-warning);
    color: white;
    border: none;
    border-radius: 12px 0 0 12px;
    font-weight: 500;
}

.btn-gradient {
    background: var(--gradient-warning);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 2rem;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-gradient:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
    color: white;
}

.btn-gradient::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-gradient:hover::before {
    left: 100%;
}

.btn-danger-gradient {
    background: var(--gradient-danger);
}

.stats-card {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border: none;
    border-radius: 15px;
    color: white;
    box-shadow: var(--shadow-soft);
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.product-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    background: var(--gradient-warning);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    font-weight: bold;
    margin: 0 auto 1rem;
    transition: all 0.3s ease;
}

.floating-label {
    position: relative;
}

.floating-label input,
.floating-label textarea {
    padding-top: 1.5rem;
    padding-bottom: 0.5rem;
}

.floating-label label {
    position: absolute;
    top: 0.75rem;
    left: 1rem;
    transition: all 0.3s ease;
    pointer-events: none;
    color: #6c757d;
}

.floating-label input:focus ~ label,
.floating-label input:not(:placeholder-shown) ~ label,
.floating-label textarea:focus ~ label,
.floating-label textarea:not(:placeholder-shown) ~ label {
    top: 0.25rem;
    font-size: 0.75rem;
    color: #f093fb;
    font-weight: 600;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.required::after {
    content: ' *';
    color: #dc3545;
}

.suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 0 0 12px 12px;
    box-shadow: var(--shadow-soft);
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
}

.suggestion-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.suggestion-item:hover {
    background-color: #f8f9fa;
}

.form-step {
    opacity: 0;
    transform: translateX(20px);
    animation: slideIn 0.5s ease forwards;
}

@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.danger-zone {
    background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(238, 90, 82, 0.1));
    border: 2px solid #ff6b6b;
    border-radius: 15px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.danger-zone h5 {
    color: #dc3545;
    margin-bottom: 1rem;
}

.change-indicator {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
}

.change-indicator.show {
    opacity: 1;
    transform: scale(1);
}

.comparison-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid #6c757d;
}

.comparison-card.changed {
    border-left-color: #28a745;
    background: rgba(40, 167, 69, 0.1);
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1">
                        <i class="fas fa-edit text-warning me-2"></i>
                        Editar Produto
                    </h1>
                    <p class="text-muted mb-0">Atualize as informações do produto</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="listar.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Voltar
                    </a>
                    <a href="cadastrar.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i> Novo Produto
                    </a>
                </div>
            </div>

            <!-- Mensagens de erro -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Corrija os erros abaixo:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulário -->
            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h3 class="mb-0"><?php echo htmlspecialchars($produto['nome']); ?></h3>
                    <p class="mb-0 opacity-75">Atualize as informações do produto</p>
                </div>

                <div class="p-4">
                    <form method="post" action="" id="produtoForm">
                        <!-- Informações Básicas -->
                        <div class="form-section form-step">
                            <h5>
                                <i class="fas fa-info-circle"></i>
                                Informações Básicas
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <div class="floating-label position-relative">
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               placeholder=" " required value="<?php echo htmlspecialchars($produto['nome']); ?>"
                                               data-original="<?php echo htmlspecialchars($produto['nome']); ?>">
                                        <label for="nome" class="required">Nome do Produto</label>
                                        <div class="change-indicator">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <div class="form-text">Nome único e descritivo do produto</div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="valor_venda" class="form-label required">
                                        <i class="fas fa-dollar-sign"></i>
                                        Valor de Venda
                                    </label>
                                    <div class="input-group position-relative">
                                        <span class="input-group-text">R$</span>
                                        <input type="text" class="form-control" id="valor_venda" name="valor_venda" 
                                               required value="<?php echo number_format($produto['valor_venda'], 2, ',', ''); ?>"
                                               data-original="<?php echo number_format($produto['valor_venda'], 2, ',', ''); ?>"
                                               placeholder="0,00">
                                        <div class="change-indicator">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="floating-label position-relative">
                                    <textarea class="form-control" id="descricao" name="descricao" 
                                              rows="3" placeholder=" " 
                                              data-original="<?php echo htmlspecialchars($produto['descricao'] ?? ''); ?>"><?php echo htmlspecialchars($produto['descricao'] ?? ''); ?></textarea>
                                    <label for="descricao">Descrição</label>
                                    <div class="change-indicator">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                                <div class="form-text">Descrição detalhada do produto (opcional)</div>
                            </div>
                        </div>

                        <!-- Classificação -->
                        <?php if ($has_categoria || $has_marca): ?>
                        <div class="form-section form-step" style="animation-delay: 0.1s">
                            <h5>
                                <i class="fas fa-tags"></i>
                                Classificação
                            </h5>
                            
                            <div class="row">
                                <?php if ($has_categoria): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="categoria" class="form-label">
                                        <i class="fas fa-folder"></i>
                                        Categoria
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="categoria" name="categoria" 
                                               placeholder="Ex: Bebidas, Lanches, Doces..." 
                                               value="<?php echo htmlspecialchars($produto['categoria'] ?? ''); ?>"
                                               data-original="<?php echo htmlspecialchars($produto['categoria'] ?? ''); ?>"
                                               autocomplete="off">
                                        <div class="suggestions-dropdown d-none" id="categoriaSuggestions"></div>
                                        <div class="change-indicator">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($has_marca): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="marca" class="form-label">
                                        <i class="fas fa-copyright"></i>
                                        Marca
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="marca" name="marca" 
                                               placeholder="Ex: Coca-Cola, Nestlé..." 
                                               value="<?php echo htmlspecialchars($produto['marca'] ?? ''); ?>"
                                               data-original="<?php echo htmlspecialchars($produto['marca'] ?? ''); ?>"
                                               autocomplete="off">
                                        <div class="suggestions-dropdown d-none" id="marcaSuggestions"></div>
                                        <div class="change-indicator">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Códigos e Identificação -->
                        <?php if ($has_sku || $has_codigo_barras): ?>
                        <div class="form-section form-step" style="animation-delay: 0.2s">
                            <h5>
                                <i class="fas fa-barcode"></i>
                                Códigos e Identificação
                            </h5>
                            
                            <div class="row">
                                <?php if ($has_sku): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="sku" class="form-label">
                                        <i class="fas fa-hashtag"></i>
                                        SKU
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="sku" name="sku" 
                                               placeholder="Ex: PRD001, CAFE250ML..." 
                                               value="<?php echo htmlspecialchars($produto['sku'] ?? ''); ?>"
                                               data-original="<?php echo htmlspecialchars($produto['sku'] ?? ''); ?>">
                                        <div class="change-indicator">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <div class="form-text">Código único do produto (opcional)</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($has_codigo_barras): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="codigo_barras" class="form-label">
                                        <i class="fas fa-barcode"></i>
                                        Código de Barras
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" 
                                               placeholder="Ex: 7891234567890" 
                                               value="<?php echo htmlspecialchars($produto['codigo_barras'] ?? ''); ?>"
                                               data-original="<?php echo htmlspecialchars($produto['codigo_barras'] ?? ''); ?>">
                                        <div class="change-indicator">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <div class="form-text">EAN, UPC ou similar (opcional)</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Especificações Físicas -->
                        <?php if ($has_peso || $has_dimensoes): ?>
                        <div class="form-section form-step" style="animation-delay: 0.3s">
                            <h5>
                                <i class="fas fa-weight"></i>
                                Especificações Físicas
                            </h5>
                            
                            <div class="row">
                                <?php if ($has_peso): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="peso" class="form-label">
                                        <i class="fas fa-weight-hanging"></i>
                                        Peso
                                    </label>
                                    <div class="input-group position-relative">
                                        <input type="number" class="form-control" id="peso" name="peso" 
                                               placeholder="0" step="0.001" min="0"
                                               value="<?php echo htmlspecialchars($produto['peso'] ?? ''); ?>"
                                               data-original="<?php echo htmlspecialchars($produto['peso'] ?? ''); ?>">
                                        <span class="input-group-text">kg</span>
                                        <div class="change-indicator">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($has_dimensoes): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="dimensoes" class="form-label">
                                        <i class="fas fa-ruler-combined"></i>
                                        Dimensões
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="dimensoes" name="dimensoes" 
                                               placeholder="Ex: 10x5x2 cm" 
                                               value="<?php echo htmlspecialchars($produto['dimensoes'] ?? ''); ?>"
                                               data-original="<?php echo htmlspecialchars($produto['dimensoes'] ?? ''); ?>">
                                        <div class="change-indicator">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <div class="form-text">Comprimento x Largura x Altura (opcional)</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Observações -->
                        <?php if ($has_observacoes): ?>
                        <div class="form-section form-step" style="animation-delay: 0.4s">
                            <h5>
                                <i class="fas fa-sticky-note"></i>
                                Observações Adicionais
                            </h5>
                            
                            <div class="floating-label position-relative">
                                <textarea class="form-control" id="observacoes" name="observacoes" 
                                          rows="3" placeholder=" " 
                                          data-original="<?php echo htmlspecialchars($produto['observacoes'] ?? ''); ?>"><?php echo htmlspecialchars($produto['observacoes'] ?? ''); ?></textarea>
                                <label for="observacoes">Observações</label>
                                <div class="change-indicator">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="form-text">Informações extras, cuidados especiais, etc. (opcional)</div>
                        </div>
                        <?php endif; ?>

                        <!-- Botões de Ação -->
                        <div class="form-step" style="animation-delay: 0.5s">
                            <div class="d-flex justify-content-between align-items-center pt-3">
                                <div id="changesCounter" class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span id="changesText">Nenhuma alteração feita</span>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="button" id="resetBtn" class="btn btn-outline-secondary" onclick="resetForm()">
                                        <i class="fas fa-undo me-2"></i> Desfazer Alterações
                                    </button>
                                    <button type="submit" id="saveBtn" class="btn btn-gradient" disabled>
                                        <i class="fas fa-save me-2"></i> Salvar Alterações
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Zona de Perigo (apenas se produto não tem vendas) -->
            <?php if ($stats_vendas['total_vendas'] == 0): ?>
            <div class="danger-zone form-step" style="animation-delay: 0.6s">
                <h5>
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Zona de Perigo
                </h5>
                <p class="mb-3">Este produto não possui vendas registradas. Você pode excluí-lo permanentemente se necessário.</p>
                <button type="button" class="btn btn-danger" onclick="confirmarExclusao()">
                    <i class="fas fa-trash-alt me-2"></i> Excluir Produto
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Preview e Estatísticas -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 2rem;">
                <!-- Preview do Produto -->
                <div class="card mb-3">
                    <div class="card-body text-center">
                        <div class="product-avatar-large" id="previewAvatar">
                            <?php echo strtoupper(substr($produto['nome'], 0, 1)); ?>
                        </div>
                        
                        <h4 id="previewNome"><?php echo htmlspecialchars($produto['nome']); ?></h4>
                        <p id="previewDescricao" class="text-muted small"><?php echo htmlspecialchars($produto['descricao'] ?: 'Sem descrição'); ?></p>
                        
                        <div class="row text-center mt-4">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 id="previewPreco" class="text-success mb-0">R$ <?php echo number_format($produto['valor_venda'], 2, ',', '.'); ?></h5>
                                    <small class="text-muted">Preço</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 id="previewCategoria" class="text-info mb-0"><?php echo htmlspecialchars($produto['categoria'] ?? '-'); ?></h5>
                                <small class="text-muted">Categoria</small>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="text-start">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">SKU:</span>
                                    <span id="previewSku" class="badge bg-secondary"><?php echo htmlspecialchars($produto['sku'] ?? '-'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Marca:</span>
                                    <span id="previewMarca" class="text-dark"><?php echo htmlspecialchars($produto['marca'] ?? '-'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Peso:</span>
                                    <span id="previewPeso" class="text-dark"><?php echo $produto['peso'] ? $produto['peso'] . ' kg' : '-'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas de Vendas -->
                <div class="stats-card mb-3">
                    <div class="card-body text-center">
                        <h6 class="mb-3">
                            <i class="fas fa-chart-bar me-2"></i>
                            Estatísticas de Vendas
                        </h6>
                        
                        <div class="row">
                            <div class="col-4">
                                <h4 class="mb-0"><?php echo $stats_vendas['total_vendas'] ?: '0'; ?></h4>
                                <small>Vendas</small>
                            </div>
                            <div class="col-4">
                                <h4 class="mb-0"><?php echo $stats_vendas['quantidade_vendida'] ?: '0'; ?></h4>
                                <small>Unidades</small>
                            </div>
                            <div class="col-4">
                                <h4 class="mb-0">
                                    <?php 
                                    if ($stats_vendas['ultima_venda']) {
                                        $days = floor((time() - strtotime($stats_vendas['ultima_venda'])) / (60 * 60 * 24));
                                        echo $days . 'd';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </h4>
                                <small>Últ. Venda</small>
                            </div>
                        </div>
                        
                        <?php if ($stats_vendas['total_vendas'] > 0): ?>
                        <div class="mt-3">
                            <small class="opacity-75">
                                <i class="fas fa-info-circle me-1"></i>
                                Produto com histórico de vendas
                            </small>
                        </div>
                        <?php else: ?>
                        <div class="mt-3">
                            <small class="opacity-75">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                Produto sem vendas registradas
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Histórico de Alterações -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-history text-warning me-2"></i>
                            Alterações Detectadas
                        </h6>
                        <div id="changesHistory">
                            <p class="text-muted small mb-0">Nenhuma alteração feita ainda.</p>
                        </div>
                    </div>
                </div>

                <!-- Atalhos -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-keyboard text-info me-2"></i>
                            Atalhos
                        </h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2">
                                <kbd>Ctrl+S</kbd> Salvar alterações
                            </li>
                            <li class="mb-2">
                                <kbd>Ctrl+Z</kbd> Desfazer alterações
                            </li>
                            <li class="mb-2">
                                <kbd>Esc</kbd> Cancelar edição
                            </li>
                            <li>
                                <kbd>F5</kbd> Recarregar dados originais
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dados para autocomplete
const categorias = <?php echo json_encode($categorias_existentes); ?>;
const marcas = <?php echo json_encode($marcas_existentes); ?>;

let changesCount = 0;
const originalData = {};

// Máscara para valor monetário
document.getElementById('valor_venda').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value === '') {
        e.target.value = '';
        return;
    }
    
    value = parseInt(value);
    value = (value / 100).toFixed(2);
    value = value.replace('.', ',');
    e.target.value = value;
    
    checkForChanges();
    updatePreview();
});

// Máscara para código de barras (apenas números)
const codigoBarrasEl = document.getElementById('codigo_barras');
if (codigoBarrasEl) {
    codigoBarrasEl.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
}

// Autocomplete para categoria e marca (apenas se as colunas existirem)
<?php if ($has_categoria): ?>
setupAutocomplete('categoria', categorias);
<?php endif; ?>

<?php if ($has_marca): ?>
setupAutocomplete('marca', marcas);
<?php endif; ?>

function setupAutocomplete(fieldId, suggestions) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    
    const dropdown = document.getElementById(fieldId + 'Suggestions');
    
    input.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        const filtered = suggestions.filter(item => 
            item.toLowerCase().includes(value)
        ).slice(0, 5);
        
        if (filtered.length > 0 && value) {
            dropdown.innerHTML = filtered.map(item => 
                `<div class="suggestion-item" onclick="selectSuggestion('${fieldId}', '${item}')">${item}</div>`
            ).join('');
            dropdown.classList.remove('d-none');
        } else {
            dropdown.classList.add('d-none');
        }
        checkForChanges();
        updatePreview();
    });
    
    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('d-none');
        }
    });
}

function selectSuggestion(fieldId, value) {
    document.getElementById(fieldId).value = value;
    const dropdown = document.getElementById(fieldId + 'Suggestions');
    if (dropdown) {
        dropdown.classList.add('d-none');
    }
    checkForChanges();
    updatePreview();
}

// Atualizar preview em tempo real
function updatePreview() {
    const nome = document.getElementById('nome').value || 'Nome do Produto';
    const descricao = document.getElementById('descricao').value || 'Sem descrição';
    const valor = document.getElementById('valor_venda').value || '0,00';
    
    // Campos opcionais - verificar se existem
    const categoriaEl = document.getElementById('categoria');
    const skuEl = document.getElementById('sku');
    const marcaEl = document.getElementById('marca');
    const pesoEl = document.getElementById('peso');
    
    const categoria = categoriaEl ? (categoriaEl.value || '-') : '-';
    const sku = skuEl ? (skuEl.value || '-') : '-';
    const marca = marcaEl ? (marcaEl.value || '-') : '-';
    const peso = pesoEl ? pesoEl.value : '';
    
    // Atualizar textos
    document.getElementById('previewNome').textContent = nome;
    document.getElementById('previewDescricao').textContent = descricao;
    document.getElementById('previewPreco').textContent = 'R$ ' + valor;
    document.getElementById('previewCategoria').textContent = categoria;
    document.getElementById('previewSku').textContent = sku;
    document.getElementById('previewMarca').textContent = marca;
    document.getElementById('previewPeso').textContent = peso ? peso + ' kg' : '-';
    
    // Atualizar avatar
    const avatar = document.getElementById('previewAvatar');
    if (nome && nome !== 'Nome do Produto') {
        avatar.innerHTML = nome.charAt(0).toUpperCase();
        avatar.style.background = `linear-gradient(135deg, ${stringToColor(nome)}, ${stringToColor(nome + 'x')})`;
    }
}

// Gerar cor baseada em string
function stringToColor(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const hue = hash % 360;
    return `hsl(${hue}, 70%, 50%)`;
}

// Verificar alterações
function checkForChanges() {
    const fields = document.querySelectorAll('input[data-original], textarea[data-original]');
    changesCount = 0;
    const changes = [];
    
    fields.forEach(field => {
        const original = field.getAttribute('data-original') || '';
        const current = field.value || '';
        const indicator = field.parentElement.querySelector('.change-indicator');
        
        if (original !== current) {
            changesCount++;
            if (indicator) {
                indicator.classList.add('show');
            }
            
            // Adicionar ao histórico
            const fieldName = field.getAttribute('name');
            const fieldLabel = document.querySelector(`label[for="${field.id}"]`)?.textContent || fieldName;
            changes.push({
                field: fieldLabel.replace(' *', ''),
                from: original,
                to: current
            });
        } else {
            if (indicator) {
                indicator.classList.remove('show');
            }
        }
    });
    
    updateChangesUI(changes);
}

function updateChangesUI(changes) {
    const saveBtn = document.getElementById('saveBtn');
    const resetBtn = document.getElementById('resetBtn');
    const changesText = document.getElementById('changesText');
    const changesHistory = document.getElementById('changesHistory');
    
    if (changesCount > 0) {
        saveBtn.disabled = false;
        saveBtn.classList.add('btn-warning-gradient');
        resetBtn.disabled = false;
        changesText.innerHTML = `<i class="fas fa-edit me-1"></i>${changesCount} alteração(ões) pendente(s)`;
        changesText.style.color = '#f093fb';
        
        // Atualizar histórico
        let historyHTML = '';
        changes.forEach(change => {
            historyHTML += `
                <div class="comparison-card changed">
                    <div class="small">
                        <strong>${change.field}</strong><br>
                        <span class="text-muted">De:</span> "${change.from || 'Vazio'}"<br>
                        <span class="text-success">Para:</span> "${change.to || 'Vazio'}"
                    </div>
                </div>
            `;
        });
        changesHistory.innerHTML = historyHTML;
        
    } else {
        saveBtn.disabled = true;
        saveBtn.classList.remove('btn-warning-gradient');
        resetBtn.disabled = true;
        changesText.innerHTML = '<i class="fas fa-check me-1"></i>Nenhuma alteração feita';
        changesText.style.color = '#6c757d';
        changesHistory.innerHTML = '<p class="text-muted small mb-0">Nenhuma alteração feita ainda.</p>';
    }
}

// Resetar formulário
function resetForm() {
    const fields = document.querySelectorAll('input[data-original], textarea[data-original]');
    fields.forEach(field => {
        field.value = field.getAttribute('data-original') || '';
    });
    checkForChanges();
    updatePreview();
    
    showNotification('Alterações desfeitas!', 'info');
}

// Confirmação de exclusão
function confirmarExclusao() {
    Swal.fire({
        title: 'Confirmar Exclusão',
        html: `Tem certeza que deseja excluir o produto <strong>"<?php echo addslashes($produto['nome']); ?>"</strong>?<br><br>
               <span class="text-danger">Esta ação não pode ser desfeita!</span>`,
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
            window.location.href = `excluir.php?id=<?php echo $id; ?>`;
        }
    });
}

// Sistema de notificações
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
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
    // Ctrl+S para salvar
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (!document.getElementById('saveBtn').disabled) {
            document.getElementById('produtoForm').submit();
        }
    }
    
    // Ctrl+Z para desfazer
    if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
        e.preventDefault();
        if (!document.getElementById('resetBtn').disabled) {
            resetForm();
        }
    }
    
    // Escape para cancelar
    if (e.key === 'Escape') {
        window.location.href = 'listar.php';
    }
    
    // F5 para recarregar
    if (e.key === 'F5') {
        e.preventDefault();
        if (changesCount > 0) {
            if (confirm('Você tem alterações não salvas. Deseja realmente recarregar?')) {
                window.location.reload();
            }
        } else {
            window.location.reload();
        }
    }
});

// Salvar dados originais e configurar listeners
document.addEventListener('DOMContentLoaded', function() {
    // Configurar dados originais
    const fields = document.querySelectorAll('input[data-original], textarea[data-original]');
    fields.forEach(field => {
        originalData[field.name] = field.getAttribute('data-original') || '';
        
        // Adicionar listeners
        field.addEventListener('input', function() {
            checkForChanges();
            updatePreview();
        });
    });
    
    // Atualizar preview inicial
    updatePreview();
    checkForChanges();
    
    // Focar no primeiro campo
    document.getElementById('nome').focus();
    
    // Animação dos passos do formulário
    const steps = document.querySelectorAll('.form-step');
    steps.forEach((step, index) => {
        step.style.animationDelay = (index * 0.1) + 's';
    });
    
    // Auto-resize para textareas
    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
    
    // Aviso antes de sair com alterações não salvas
    window.addEventListener('beforeunload', function(e) {
        if (changesCount > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

// Validação do formulário antes do envio
document.getElementById('produtoForm').addEventListener('submit', function(e) {
    const nome = document.getElementById('nome').value.trim();
    const valor = document.getElementById('valor_venda').value.trim();
    
    if (!nome || nome.length < 2) {
        e.preventDefault();
        showNotification('Por favor, informe um nome válido para o produto.', 'danger');
        document.getElementById('nome').focus();
        return;
    }
    
    if (!valor || parseFloat(valor.replace(',', '.')) <= 0) {
        e.preventDefault();
        showNotification('Por favor, informe um valor válido para o produto.', 'danger');
        document.getElementById('valor_venda').focus();
        return;
    }
    
    // Mostrar loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Salvando...';
    submitBtn.disabled = true;
    
    showNotification('Salvando alterações...', 'info');
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