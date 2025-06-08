<?php
// produtos/cadastrar.php - Versão Melhorada
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

$errors = [];
$success = false;

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
    
    // Verificar se o nome já existe
    if (!empty($nome)) {
        $check_sql = "SELECT id FROM produtos WHERE nome = '" . $conn->real_escape_string($nome) . "'";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            $errors[] = "Já existe um produto com este nome";
        }
    }
    
    // Verificar SKU único se fornecido e se a coluna existe
    if ($has_sku && !empty($sku)) {
        $check_sku = "SELECT id FROM produtos WHERE sku = '" . $conn->real_escape_string($sku) . "'";
        $check_result = $conn->query($check_sku);
        if ($check_result->num_rows > 0) {
            $errors[] = "SKU já está em uso por outro produto";
        }
    }
    
    // Se não há erros, inserir no banco
    if (empty($errors)) {
        // Construir SQL dinamicamente baseado nas colunas disponíveis
        $fields = ['nome', 'descricao', 'valor_venda'];
        $values = ['?', '?', '?'];
        $params = [$nome, $descricao, $valor_venda];
        $types = 'ssd';
        
        if ($has_categoria && !empty($categoria)) {
            $fields[] = 'categoria';
            $values[] = '?';
            $params[] = $categoria;
            $types .= 's';
        }
        
        if ($has_marca && !empty($marca)) {
            $fields[] = 'marca';
            $values[] = '?';
            $params[] = $marca;
            $types .= 's';
        }
        
        if ($has_sku && !empty($sku)) {
            $fields[] = 'sku';
            $values[] = '?';
            $params[] = $sku;
            $types .= 's';
        }
        
        if ($has_codigo_barras && !empty($codigo_barras)) {
            $fields[] = 'codigo_barras';
            $values[] = '?';
            $params[] = $codigo_barras;
            $types .= 's';
        }
        
        if ($has_peso && !empty($peso)) {
            $fields[] = 'peso';
            $values[] = '?';
            $params[] = $peso;
            $types .= 'd';
        }
        
        if ($has_dimensoes && !empty($dimensoes)) {
            $fields[] = 'dimensoes';
            $values[] = '?';
            $params[] = $dimensoes;
            $types .= 's';
        }
        
        if ($has_observacoes && !empty($observacoes)) {
            $fields[] = 'observacoes';
            $values[] = '?';
            $params[] = $observacoes;
            $types .= 's';
        }
        
        $sql = "INSERT INTO produtos (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $produto_id = $conn->insert_id;
            
            // Registrar log se a função existir
            if (function_exists('registrar_log')) {
                registrar_log('Cadastro de Produto', "Produto '$nome' cadastrado com ID: $produto_id");
            }
            
            $_SESSION['msg'] = "Produto cadastrado com sucesso!";
            $_SESSION['msg_type'] = "success";
            
            // Verificar se deve cadastrar outro
            if (isset($_POST['cadastrar_outro'])) {
                header("Location: cadastrar.php?success=1");
            } else {
                header("Location: listar.php");
            }
            exit;
        } else {
            $errors[] = "Erro ao cadastrar produto: " . $conn->error;
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
    background: var(--gradient-primary);
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
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}

.form-section:hover {
    background: #f0f2ff;
    border-left-color: #764ba2;
}

.form-section h5 {
    color: #667eea;
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
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    transform: translateY(-2px);
}

.input-group-text {
    background: var(--gradient-primary);
    color: white;
    border: none;
    border-radius: 12px 0 0 12px;
    font-weight: 500;
}

.btn-gradient {
    background: var(--gradient-primary);
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

.btn-success-gradient {
    background: var(--gradient-success);
}

.btn-warning-gradient {
    background: var(--gradient-warning);
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-text {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.required::after {
    content: ' *';
    color: #dc3545;
}

.preview-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: var(--shadow-soft);
    border: 2px dashed #e9ecef;
    transition: all 0.3s ease;
}

.preview-card.active {
    border-color: #667eea;
    border-style: solid;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
}

.product-preview {
    text-align: center;
    padding: 2rem;
}

.product-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    background: var(--gradient-info);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    font-weight: bold;
    margin: 0 auto 1rem;
    transition: all 0.3s ease;
}

.error-message {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
    border: none;
    border-radius: 15px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
}

.success-message {
    background: var(--gradient-success);
    color: white;
    border: none;
    border-radius: 15px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
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
    color: #667eea;
    font-weight: 600;
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
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        Cadastrar Produto
                    </h1>
                    <p class="text-muted mb-0">Adicione um novo produto ao seu catálogo</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="listar.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Voltar
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="preencherExemplo()">
                        <i class="fas fa-magic me-2"></i> Exemplo
                    </button>
                </div>
            </div>

            <!-- Mensagens de erro -->
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Corrija os erros abaixo:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Mensagem de sucesso -->
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle me-2"></i>
                    Produto cadastrado com sucesso! Cadastre outro produto abaixo.
                </div>
            <?php endif; ?>

            <!-- Formulário -->
            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <h3 class="mb-0">Informações do Produto</h3>
                    <p class="mb-0 opacity-75">Preencha os dados do novo produto</p>
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
                                    <div class="floating-label">
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               placeholder=" " required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                                        <label for="nome" class="required">Nome do Produto</label>
                                    </div>
                                    <div class="form-text">Nome único e descritivo do produto</div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="valor_venda" class="form-label required">
                                        <i class="fas fa-dollar-sign"></i>
                                        Valor de Venda
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="text" class="form-control" id="valor_venda" name="valor_venda" 
                                               required value="<?php echo htmlspecialchars($_POST['valor_venda'] ?? ''); ?>"
                                               placeholder="0,00">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="floating-label">
                                    <textarea class="form-control" id="descricao" name="descricao" 
                                              rows="3" placeholder=" "><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
                                    <label for="descricao">Descrição</label>
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
                                               value="<?php echo htmlspecialchars($_POST['categoria'] ?? ''); ?>"
                                               autocomplete="off">
                                        <div class="suggestions-dropdown d-none" id="categoriaSuggestions"></div>
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
                                               value="<?php echo htmlspecialchars($_POST['marca'] ?? ''); ?>"
                                               autocomplete="off">
                                        <div class="suggestions-dropdown d-none" id="marcaSuggestions"></div>
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
                                    <input type="text" class="form-control" id="sku" name="sku" 
                                           placeholder="Ex: PRD001, CAFE250ML..." 
                                           value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                                    <div class="form-text">Código único do produto (opcional)</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($has_codigo_barras): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="codigo_barras" class="form-label">
                                        <i class="fas fa-barcode"></i>
                                        Código de Barras
                                    </label>
                                    <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" 
                                           placeholder="Ex: 7891234567890" 
                                           value="<?php echo htmlspecialchars($_POST['codigo_barras'] ?? ''); ?>">
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
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="peso" name="peso" 
                                               placeholder="0" step="0.001" min="0"
                                               value="<?php echo htmlspecialchars($_POST['peso'] ?? ''); ?>">
                                        <span class="input-group-text">kg</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($has_dimensoes): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="dimensoes" class="form-label">
                                        <i class="fas fa-ruler-combined"></i>
                                        Dimensões
                                    </label>
                                    <input type="text" class="form-control" id="dimensoes" name="dimensoes" 
                                           placeholder="Ex: 10x5x2 cm" 
                                           value="<?php echo htmlspecialchars($_POST['dimensoes'] ?? ''); ?>">
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
                            
                            <div class="floating-label">
                                <textarea class="form-control" id="observacoes" name="observacoes" 
                                          rows="3" placeholder=" "><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
                                <label for="observacoes">Observações</label>
                            </div>
                            <div class="form-text">Informações extras, cuidados especiais, etc. (opcional)</div>
                        </div>
                        <?php endif; ?>

                        <!-- Botões de Ação -->
                        <div class="form-step" style="animation-delay: 0.5s">
                            <div class="d-flex justify-content-between align-items-center pt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cadastrar_outro" name="cadastrar_outro">
                                    <label class="form-check-label" for="cadastrar_outro">
                                        Cadastrar outro produto após salvar
                                    </label>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-2"></i> Limpar
                                    </button>
                                    <button type="submit" class="btn btn-gradient btn-success-gradient">
                                        <i class="fas fa-save me-2"></i> Cadastrar Produto
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview do Produto -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 2rem;">
                <div class="preview-card" id="previewCard">
                    <h5 class="mb-3">
                        <i class="fas fa-eye me-2"></i>
                        Preview do Produto
                    </h5>
                    
                    <div class="product-preview">
                        <div class="product-avatar-large" id="previewAvatar">
                            <i class="fas fa-box"></i>
                        </div>
                        
                        <h4 id="previewNome" class="text-muted">Nome do Produto</h4>
                        <p id="previewDescricao" class="text-muted small">Descrição do produto aparecerá aqui...</p>
                        
                        <div class="row text-center mt-4">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 id="previewPreco" class="text-success mb-0">R$ 0,00</h5>
                                    <small class="text-muted">Preço</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 id="previewCategoria" class="text-info mb-0">-</h5>
                                <small class="text-muted">Categoria</small>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="text-start">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">SKU:</span>
                                    <span id="previewSku" class="badge bg-secondary">-</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Marca:</span>
                                    <span id="previewMarca" class="text-dark">-</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Peso:</span>
                                    <span id="previewPeso" class="text-dark">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dicas -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Dicas
                        </h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Use nomes descritivos e únicos
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Categorize para facilitar a busca
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                SKU ajuda no controle interno
                            </li>
                            <li>
                                <i class="fas fa-check text-success me-2"></i>
                                Use <kbd>Ctrl+Enter</kbd> para salvar rapidamente
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
    
    updatePreview();
});

// Máscara para código de barras (apenas números)
document.getElementById('codigo_barras').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '');
});

// Autocomplete para categoria e marca (apenas se as colunas existirem)
<?php if ($has_categoria): ?>
setupAutocomplete('categoria', categorias);
<?php endif; ?>

<?php if ($has_marca): ?>
setupAutocomplete('marca', marcas);
<?php endif; ?>

function setupAutocomplete(fieldId, suggestions) {
    const input = document.getElementById(fieldId);
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
    document.getElementById(fieldId + 'Suggestions').classList.add('d-none');
    updatePreview();
}

// Atualizar preview em tempo real
function updatePreview() {
    const nome = document.getElementById('nome').value || 'Nome do Produto';
    const descricao = document.getElementById('descricao').value || 'Descrição do produto aparecerá aqui...';
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
        document.getElementById('previewCard').classList.add('active');
    } else {
        avatar.innerHTML = '<i class="fas fa-box"></i>';
        avatar.style.background = 'var(--gradient-info)';
        document.getElementById('previewCard').classList.remove('active');
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

// Preencher exemplo
function preencherExemplo() {
    const exemplos = [
        {
            nome: 'Café Expresso Duplo',
            descricao: 'Café expresso encorpado, servido em dose dupla para mais energia',
            valor_venda: '7,90',
            categoria: 'Bebidas Quentes',
            marca: 'Domaria',
            sku: 'CAFE-EXP-002',
            peso: '0.250',
            dimensoes: '8x8x10 cm',
            observacoes: 'Servido em xícara de porcelana'
        },
        {
            nome: 'Pão de Queijo Tradicional',
            descricao: 'Pão de queijo mineiro tradicional, quentinho e saboroso',
            valor_venda: '3,50',
            categoria: 'Salgados',
            marca: 'Casa da Vovó',
            sku: 'PAO-QJO-001',
            peso: '0.080',
            observacoes: 'Melhor servido quente'
        },
        {
            nome: 'Brownie de Chocolate',
            descricao: 'Brownie artesanal com chocolate belga e nozes',
            valor_venda: '12,90',
            categoria: 'Doces',
            marca: 'Doceria Artesanal',
            sku: 'BROWN-CHOC-001',
            peso: '0.120',
            dimensoes: '8x8x3 cm',
            observacoes: 'Contém glúten e lactose'
        }
    ];
    
    const exemplo = exemplos[Math.floor(Math.random() * exemplos.length)];
    
    Object.keys(exemplo).forEach(key => {
        const input = document.getElementById(key);
        if (input) {
            input.value = exemplo[key];
            // Disparar evento para atualizar preview
            input.dispatchEvent(new Event('input'));
        }
    });
    
    updatePreview();
    
    // Mostrar notificação
    showNotification('Exemplo preenchido! Você pode editar os campos conforme necessário.', 'info');
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
    // Ctrl+Enter para salvar
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('produtoForm').submit();
    }
    
    // Ctrl+R para limpar formulário
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        document.getElementById('produtoForm').reset();
        updatePreview();
    }
    
    // Escape para limpar campo focado
    if (e.key === 'Escape') {
        if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
            document.activeElement.value = '';
            updatePreview();
        }
    }
});

// Validação em tempo real
document.getElementById('nome').addEventListener('blur', function() {
    if (this.value.length < 2) {
        this.setCustomValidity('Nome deve ter pelo menos 2 caracteres');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    }
});

document.getElementById('valor_venda').addEventListener('blur', function() {
    const valor = parseFloat(this.value.replace(',', '.'));
    if (isNaN(valor) || valor <= 0) {
        this.setCustomValidity('Valor deve ser maior que zero');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    }
});

// Auto-resize para textareas
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar preview inicial
    updatePreview();
    
    // Focar no primeiro campo
    document.getElementById('nome').focus();
    
    // Adicionar listeners para atualizar preview
    document.querySelectorAll('input, textarea, select').forEach(element => {
        element.addEventListener('input', updatePreview);
    });
    
    // Animação dos passos do formulário
    const steps = document.querySelectorAll('.form-step');
    steps.forEach((step, index) => {
        step.style.animationDelay = (index * 0.1) + 's';
    });
    
    // Mostrar dica inicial
    setTimeout(() => {
        showNotification('💡 Dica: Use o botão "Exemplo" para preencher automaticamente com dados de teste!', 'info');
    }, 2000);
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
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Cadastrando...';
    submitBtn.disabled = true;
    
    // Se chegou até aqui, formulário é válido
    showNotification('Cadastrando produto...', 'info');
});

// Salvar rascunho no localStorage
function salvarRascunho() {
    const formData = new FormData(document.getElementById('produtoForm'));
    const rascunho = {};
    
    for (let [key, value] of formData.entries()) {
        rascunho[key] = value;
    }
    
    localStorage.setItem('rascunho_produto', JSON.stringify(rascunho));
}

// Carregar rascunho
function carregarRascunho() {
    const rascunho = localStorage.getItem('rascunho_produto');
    if (rascunho) {
        const data = JSON.parse(rascunho);
        Object.keys(data).forEach(key => {
            const input = document.getElementById(key);
            if (input && data[key]) {
                input.value = data[key];
            }
        });
        updatePreview();
        
        showNotification('Rascunho carregado! 📝', 'info');
        
        // Perguntar se quer manter ou limpar
        setTimeout(() => {
            if (confirm('Deseja manter os dados do rascunho ou começar do zero?')) {
                // Manter dados
            } else {
                document.getElementById('produtoForm').reset();
                localStorage.removeItem('rascunho_produto');
                updatePreview();
            }
        }, 1000);
    }
}

// Salvar rascunho automaticamente
setInterval(salvarRascunho, 30000); // A cada 30 segundos

// Carregar rascunho ao iniciar (se existir)
// carregarRascunho(); // Descomente se quiser ativar o rascunho automático
</script>

<!-- SweetAlert2 para notificações mais bonitas -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include '../includes/footer.php'; ?>