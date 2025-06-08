<?php
// clientes/cadastrar.php - Versão Melhorada
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

// Verificar quais colunas existem na tabela clientes
$columns_check = $conn->query("SHOW COLUMNS FROM clientes");
$existing_columns = [];
while ($column = $columns_check->fetch_assoc()) {
    $existing_columns[] = $column['Field'];
}

// Definir quais campos extras estão disponíveis
$has_email = in_array('email', $existing_columns);
$has_endereco = in_array('endereco', $existing_columns);
$has_cpf_cnpj = in_array('cpf_cnpj', $existing_columns);
$has_data_nascimento = in_array('data_nascimento', $existing_columns);
$has_observacoes = in_array('observacoes', $existing_columns);

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validações
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $empresa = trim($_POST['empresa'] ?? '');
    $limite_compra = str_replace(',', '.', $_POST['limite_compra'] ?? '');
    
    // Campos extras (apenas se as colunas existirem)
    $email = $has_email ? trim($_POST['email'] ?? '') : '';
    $endereco = $has_endereco ? trim($_POST['endereco'] ?? '') : '';
    $cpf_cnpj = $has_cpf_cnpj ? preg_replace('/[^0-9]/', '', $_POST['cpf_cnpj'] ?? '') : '';
    $data_nascimento = $has_data_nascimento ? ($_POST['data_nascimento'] ?? '') : '';
    $observacoes = $has_observacoes ? trim($_POST['observacoes'] ?? '') : '';
    
    // Validar campos obrigatórios
    if (empty($nome)) {
        $errors[] = "Nome é obrigatório";
    }
    
    if (empty($telefone)) {
        $errors[] = "Telefone é obrigatório";
    }
    
    if (empty($empresa)) {
        $errors[] = "Empresa é obrigatória";
    }
    
    if (empty($limite_compra) || !is_numeric($limite_compra) || $limite_compra < 0) {
        $errors[] = "Limite de compra deve ser um valor válido";
    }
    
    // Validar email se fornecido
    if ($has_email && !empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido";
    }
    
    // Validar CPF/CNPJ se fornecido
    if ($has_cpf_cnpj && !empty($cpf_cnpj)) {
        if (strlen($cpf_cnpj) != 11 && strlen($cpf_cnpj) != 14) {
            $errors[] = "CPF deve ter 11 dígitos ou CNPJ deve ter 14 dígitos";
        }
    }
    
    // Verificar se já existe cliente com o mesmo nome
    if (!empty($nome)) {
        $check_sql = "SELECT id FROM clientes WHERE nome = '" . $conn->real_escape_string($nome) . "'";
        $check_result = $conn->query($check_sql);
        if ($check_result->num_rows > 0) {
            $errors[] = "Já existe um cliente com este nome";
        }
    }
    
    // Verificar se já existe cliente com o mesmo telefone
    if (!empty($telefone)) {
        $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone);
        $check_phone = "SELECT id FROM clientes WHERE REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = '$telefone_limpo'";
        $check_result = $conn->query($check_phone);
        if ($check_result->num_rows > 0) {
            $errors[] = "Já existe um cliente com este telefone";
        }
    }
    
    // Se não há erros, inserir no banco
    if (empty($errors)) {
        // Construir SQL dinamicamente baseado nas colunas disponíveis
        $fields = ['nome', 'telefone', 'empresa', 'limite_compra'];
        $values = ['?', '?', '?', '?'];
        $params = [$nome, $telefone, $empresa, $limite_compra];
        $types = 'sssd';
        
        if ($has_email && !empty($email)) {
            $fields[] = 'email';
            $values[] = '?';
            $params[] = $email;
            $types .= 's';
        }
        
        if ($has_endereco && !empty($endereco)) {
            $fields[] = 'endereco';
            $values[] = '?';
            $params[] = $endereco;
            $types .= 's';
        }
        
        if ($has_cpf_cnpj && !empty($cpf_cnpj)) {
            $fields[] = 'cpf_cnpj';
            $values[] = '?';
            $params[] = $cpf_cnpj;
            $types .= 's';
        }
        
        if ($has_data_nascimento && !empty($data_nascimento)) {
            $fields[] = 'data_nascimento';
            $values[] = '?';
            $params[] = $data_nascimento;
            $types .= 's';
        }
        
        if ($has_observacoes && !empty($observacoes)) {
            $fields[] = 'observacoes';
            $values[] = '?';
            $params[] = $observacoes;
            $types .= 's';
        }
        
        $sql = "INSERT INTO clientes (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $cliente_id = $conn->insert_id;
            
            // Registrar log se a função existir
            if (function_exists('registrar_log')) {
                registrar_log('Cadastro de Cliente', "Cliente '$nome' cadastrado com ID: $cliente_id");
            }
            
            $_SESSION['msg'] = "Cliente cadastrado com sucesso!";
            $_SESSION['msg_type'] = "success";
            
            // Verificar se deve cadastrar outro
            if (isset($_POST['cadastrar_outro'])) {
                header("Location: cadastrar.php?success=1");
            } else {
                header("Location: listar.php");
            }
            exit;
        } else {
            $errors[] = "Erro ao cadastrar cliente: " . $conn->error;
        }
    }
}

// Buscar empresas existentes para sugestões
$sql_empresas = "SELECT DISTINCT empresa FROM clientes WHERE empresa IS NOT NULL AND empresa != '' ORDER BY empresa";
$result_empresas = $conn->query($sql_empresas);
$empresas_existentes = [];
while ($row = $result_empresas->fetch_assoc()) {
    $empresas_existentes[] = $row['empresa'];
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
    background: var(--gradient-success);
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
    border-left: 4px solid #11998e;
    transition: all 0.3s ease;
}

.form-section:hover {
    background: #f0fff4;
    border-left-color: #38ef7d;
}

.form-section h5 {
    color: #11998e;
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
    border-color: #11998e;
    box-shadow: 0 0 0 0.2rem rgba(17, 153, 142, 0.25);
    transform: translateY(-2px);
}

.input-group-text {
    background: var(--gradient-success);
    color: white;
    border: none;
    border-radius: 12px 0 0 12px;
    font-weight: 500;
}

.btn-gradient {
    background: var(--gradient-success);
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
    border-color: #11998e;
    border-style: solid;
    background: linear-gradient(135deg, rgba(17, 153, 142, 0.05), rgba(56, 239, 125, 0.05));
}

.client-preview {
    text-align: center;
    padding: 2rem;
}

.client-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    background: var(--gradient-success);
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
    color: #11998e;
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

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
    z-index: 10;
}

.input-with-icon {
    position: relative;
}

.input-with-icon input {
    padding-left: 2.5rem;
}

.format-indicator {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #28a745;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.format-indicator.valid {
    opacity: 1;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1">
                        <i class="fas fa-user-plus text-success me-2"></i>
                        Cadastrar Cliente
                    </h1>
                    <p class="text-muted mb-0">Adicione um novo cliente ao seu sistema</p>
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
                    Cliente cadastrado com sucesso! Cadastre outro cliente abaixo.
                </div>
            <?php endif; ?>

            <!-- Formulário -->
            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="mb-0">Informações do Cliente</h3>
                    <p class="mb-0 opacity-75">Preencha os dados do novo cliente</p>
                </div>

                <div class="p-4">
                    <form method="post" action="" id="clienteForm">
                        <!-- Informações Básicas -->
                        <div class="form-section form-step">
                            <h5>
                                <i class="fas fa-info-circle"></i>
                                Informações Básicas
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="floating-label">
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               placeholder=" " required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                                        <label for="nome" class="required">Nome Completo</label>
                                    </div>
                                    <div class="form-text">Nome completo do cliente</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="telefone" class="form-label required">
                                        <i class="fas fa-phone"></i>
                                        Telefone
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-phone input-icon"></i>
                                        <input type="text" class="form-control" id="telefone" name="telefone" 
                                               required value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>"
                                               placeholder="(00) 00000-0000">
                                        <i class="fas fa-check format-indicator" id="phoneValid"></i>
                                    </div>
                                    <div class="form-text">Telefone com WhatsApp preferencialmente</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="empresa" class="form-label required">
                                        <i class="fas fa-building"></i>
                                        Empresa
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="empresa" name="empresa" 
                                               required value="<?php echo htmlspecialchars($_POST['empresa'] ?? ''); ?>"
                                               placeholder="Nome da empresa..." autocomplete="off">
                                        <div class="suggestions-dropdown d-none" id="empresaSuggestions"></div>
                                    </div>
                                    <div class="form-text">Empresa ou estabelecimento do cliente</div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="limite_compra" class="form-label required">
                                        <i class="fas fa-credit-card"></i>
                                        Limite de Compra
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="text" class="form-control" id="limite_compra" name="limite_compra" 
                                               required value="<?php echo htmlspecialchars($_POST['limite_compra'] ?? ''); ?>"
                                               placeholder="0,00">
                                    </div>
                                    <div class="form-text">Limite para compras a prazo</div>
                                </div>
                            </div>
                        </div>

                        <!-- Informações de Contato -->
                        <?php if ($has_email || $has_endereco): ?>
                        <div class="form-section form-step" style="animation-delay: 0.1s">
                            <h5>
                                <i class="fas fa-address-book"></i>
                                Informações de Contato
                            </h5>
                            
                            <?php if ($has_email): ?>
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email
                                </label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                           placeholder="cliente@empresa.com">
                                    <i class="fas fa-check format-indicator" id="emailValid"></i>
                                </div>
                                <div class="form-text">Email para contato e envio de comprovantes</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($has_endereco): ?>
                            <div class="mb-3">
                                <div class="floating-label">
                                    <textarea class="form-control" id="endereco" name="endereco" 
                                              rows="3" placeholder=" "><?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?></textarea>
                                    <label for="endereco">Endereço Completo</label>
                                </div>
                                <div class="form-text">Endereço completo para entrega</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Documentos -->
                        <?php if ($has_cpf_cnpj || $has_data_nascimento): ?>
                        <div class="form-section form-step" style="animation-delay: 0.2s">
                            <h5>
                                <i class="fas fa-id-card"></i>
                                Documentos e Dados Pessoais
                            </h5>
                            
                            <div class="row">
                                <?php if ($has_cpf_cnpj): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="cpf_cnpj" class="form-label">
                                        <i class="fas fa-id-card"></i>
                                        CPF/CNPJ
                                    </label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-id-card input-icon"></i>
                                        <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" 
                                               value="<?php echo htmlspecialchars($_POST['cpf_cnpj'] ?? ''); ?>"
                                               placeholder="000.000.000-00">
                                        <i class="fas fa-check format-indicator" id="docValid"></i>
                                    </div>
                                    <div class="form-text">CPF (pessoa física) ou CNPJ (pessoa jurídica)</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($has_data_nascimento): ?>
                                <div class="col-md-6 mb-3">
                                    <label for="data_nascimento" class="form-label">
                                        <i class="fas fa-birthday-cake"></i>
                                        Data de Nascimento
                                    </label>
                                    <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" 
                                           value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
                                    <div class="form-text">Data de nascimento (opcional)</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Observações -->
                        <?php if ($has_observacoes): ?>
                        <div class="form-section form-step" style="animation-delay: 0.3s">
                            <h5>
                                <i class="fas fa-sticky-note"></i>
                                Observações
                            </h5>
                            
                            <div class="floating-label">
                                <textarea class="form-control" id="observacoes" name="observacoes" 
                                          rows="3" placeholder=" "><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
                                <label for="observacoes">Observações</label>
                            </div>
                            <div class="form-text">Informações adicionais sobre o cliente</div>
                        </div>
                        <?php endif; ?>

                        <!-- Botões de Ação -->
                        <div class="form-step" style="animation-delay: 0.4s">
                            <div class="d-flex justify-content-between align-items-center pt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cadastrar_outro" name="cadastrar_outro">
                                    <label class="form-check-label" for="cadastrar_outro">
                                        Cadastrar outro cliente após salvar
                                    </label>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-2"></i> Limpar
                                    </button>
                                    <button type="submit" class="btn btn-gradient">
                                        <i class="fas fa-save me-2"></i> Cadastrar Cliente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview do Cliente -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 2rem;">
                <div class="preview-card" id="previewCard">
                    <h5 class="mb-3">
                        <i class="fas fa-eye me-2"></i>
                        Preview do Cliente
                    </h5>
                    
                    <div class="client-preview">
                        <div class="client-avatar-large" id="previewAvatar">
                            <i class="fas fa-user"></i>
                        </div>
                        
                        <h4 id="previewNome" class="text-muted">Nome do Cliente</h4>
                        <p id="previewEmpresa" class="text-muted small">Empresa aparecerá aqui...</p>
                        
                        <div class="row text-center mt-4">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 id="previewTelefone" class="text-info mb-0">-</h5>
                                    <small class="text-muted">Telefone</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 id="previewLimite" class="text-success mb-0">R$ 0,00</h5>
                                <small class="text-muted">Limite</small>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="text-start">
                                <?php if ($has_email): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Email:</span>
                                    <span id="previewEmail" class="text-dark">-</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($has_cpf_cnpj): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">CPF/CNPJ:</span>
                                    <span id="previewDoc" class="text-dark">-</span>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Status:</span>
                                    <span class="badge bg-success">NOVO CLIENTE</span>
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
                                Use o telefone com WhatsApp para facilitar contato
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                O limite de compra controla vendas a prazo
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Empresa ajuda na organização dos clientes
                            </li>
                            <li>
                                <i class="fas fa-check text-success me-2"></i>
                                Use <kbd>Ctrl+Enter</kbd> para salvar rapidamente
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Últimos Clientes -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-users text-info me-2"></i>
                            Últimos Clientes
                        </h6>
                        <?php
                        $sql_ultimos = "SELECT nome, empresa FROM clientes ORDER BY data_cadastro DESC LIMIT 3";
                        $result_ultimos = $conn->query($sql_ultimos);
                        if ($result_ultimos && $result_ultimos->num_rows > 0):
                        ?>
                            <div class="list-group list-group-flush">
                                <?php while ($ultimo = $result_ultimos->fetch_assoc()): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2" style="width: 30px; height: 30px; font-size: 0.8rem; background: var(--gradient-info);">
                                                <?php echo strtoupper(substr($ultimo['nome'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold small"><?php echo htmlspecialchars($ultimo['nome']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($ultimo['empresa']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0">Nenhum cliente cadastrado ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dados para autocomplete
const empresas = <?php echo json_encode($empresas_existentes); ?>;

// Máscaras e validações
document.getElementById('telefone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substring(0, 11);
    
    if (value.length > 7) {
        value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
    } else if (value.length > 2) {
        value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
    }
    
    e.target.value = value;
    
    // Validar formato
    const phoneValid = document.getElementById('phoneValid');
    if (value.length >= 14) {
        phoneValid.classList.add('valid');
    } else {
        phoneValid.classList.remove('valid');
    }
    
    updatePreview();
});

// Máscara para limite de compra
document.getElementById('limite_compra').addEventListener('input', function(e) {
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

// Máscara para CPF/CNPJ
const cpfCnpjInput = document.getElementById('cpf_cnpj');
if (cpfCnpjInput) {
    cpfCnpjInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length <= 11) {
            // CPF
            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})(\d{0,3})/, '$1.$2');
            }
        } else {
            // CNPJ
            if (value.length > 12) {
                value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5');
            } else if (value.length > 8) {
                value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
            } else if (value.length > 5) {
                value = value.replace(/^(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,3})/, '$1.$2');
            }
        }
        
        e.target.value = value;
        
        // Validar formato
        const docValid = document.getElementById('docValid');
        const cleanValue = value.replace(/\D/g, '');
        if (cleanValue.length === 11 || cleanValue.length === 14) {
            docValid.classList.add('valid');
        } else {
            docValid.classList.remove('valid');
        }
        
        updatePreview();
    });
}

// Validação de email
const emailInput = document.getElementById('email');
if (emailInput) {
    emailInput.addEventListener('input', function(e) {
        const emailValid = document.getElementById('emailValid');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (emailRegex.test(e.target.value)) {
            emailValid.classList.add('valid');
        } else {
            emailValid.classList.remove('valid');
        }
        
        updatePreview();
    });
}

// Autocomplete para empresa
setupAutocomplete('empresa', empresas);

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
    const nome = document.getElementById('nome').value || 'Nome do Cliente';
    const empresa = document.getElementById('empresa').value || 'Empresa aparecerá aqui...';
    const telefone = document.getElementById('telefone').value || '-';
    const limite = document.getElementById('limite_compra').value || '0,00';
    
    // Campos opcionais
    const emailEl = document.getElementById('email');
    const cpfCnpjEl = document.getElementById('cpf_cnpj');
    
    const email = emailEl ? (emailEl.value || '-') : '-';
    const cpfCnpj = cpfCnpjEl ? (cpfCnpjEl.value || '-') : '-';
    
    // Atualizar textos
    document.getElementById('previewNome').textContent = nome;
    document.getElementById('previewEmpresa').textContent = empresa;
    document.getElementById('previewTelefone').textContent = telefone;
    document.getElementById('previewLimite').textContent = 'R$ ' + limite;
    
    if (document.getElementById('previewEmail')) {
        document.getElementById('previewEmail').textContent = email;
    }
    if (document.getElementById('previewDoc')) {
        document.getElementById('previewDoc').textContent = cpfCnpj;
    }
    
    // Atualizar avatar
    const avatar = document.getElementById('previewAvatar');
    if (nome && nome !== 'Nome do Cliente') {
        avatar.innerHTML = nome.charAt(0).toUpperCase();
        avatar.style.background = `linear-gradient(135deg, ${stringToColor(nome)}, ${stringToColor(nome + 'x')})`;
        document.getElementById('previewCard').classList.add('active');
    } else {
        avatar.innerHTML = '<i class="fas fa-user"></i>';
        avatar.style.background = 'var(--gradient-success)';
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
            nome: 'João Silva',
            telefone: '(11) 99999-8888',
            empresa: 'Padaria do João',
            limite_compra: '500,00',
            email: 'joao@padariajo.com',
            endereco: 'Rua das Flores, 123 - Centro - São Paulo - SP',
            cpf_cnpj: '123.456.789-00'
        },
        {
            nome: 'Maria Santos',
            telefone: '(21) 98888-7777',
            empresa: 'Lanchonete da Maria',
            limite_compra: '1000,00',
            email: 'maria@lanchonete.com',
            endereco: 'Av. Principal, 456 - Centro - Rio de Janeiro - RJ',
            cpf_cnpj: '12.345.678/0001-90'
        },
        {
            nome: 'Carlos Oliveira',
            telefone: '(31) 97777-6666',
            empresa: 'Restaurante do Carlos',
            limite_compra: '1500,00',
            email: 'carlos@restaurante.com',
            endereco: 'Rua do Comércio, 789 - Centro - Belo Horizonte - MG',
            cpf_cnpj: '987.654.321-00'
        }
    ];
    
    const exemplo = exemplos[Math.floor(Math.random() * exemplos.length)];
    
    Object.keys(exemplo).forEach(key => {
        const input = document.getElementById(key);
        if (input) {
            input.value = exemplo[key];
            // Disparar evento para atualizar preview e máscaras
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
        document.getElementById('clienteForm').submit();
    }
    
    // Ctrl+R para limpar formulário
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        document.getElementById('clienteForm').reset();
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

document.getElementById('telefone').addEventListener('blur', function() {
    const telefone = this.value.replace(/\D/g, '');
    if (telefone.length < 10) {
        this.setCustomValidity('Telefone deve ter pelo menos 10 dígitos');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    }
});

document.getElementById('limite_compra').addEventListener('blur', function() {
    const valor = parseFloat(this.value.replace(',', '.'));
    if (isNaN(valor) || valor < 0) {
        this.setCustomValidity('Valor deve ser maior ou igual a zero');
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
document.getElementById('clienteForm').addEventListener('submit', function(e) {
    const nome = document.getElementById('nome').value.trim();
    const telefone = document.getElementById('telefone').value.trim();
    const empresa = document.getElementById('empresa').value.trim();
    const limite = document.getElementById('limite_compra').value.trim();
    
    if (!nome || nome.length < 2) {
        e.preventDefault();
        showNotification('Por favor, informe um nome válido para o cliente.', 'danger');
        document.getElementById('nome').focus();
        return;
    }
    
    if (!telefone || telefone.replace(/\D/g, '').length < 10) {
        e.preventDefault();
        showNotification('Por favor, informe um telefone válido.', 'danger');
        document.getElementById('telefone').focus();
        return;
    }
    
    if (!empresa) {
        e.preventDefault();
        showNotification('Por favor, informe a empresa do cliente.', 'danger');
        document.getElementById('empresa').focus();
        return;
    }
    
    if (!limite || parseFloat(limite.replace(',', '.')) < 0) {
        e.preventDefault();
        showNotification('Por favor, informe um limite de compra válido.', 'danger');
        document.getElementById('limite_compra').focus();
        return;
    }
    
    // Mostrar loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Cadastrando...';
    submitBtn.disabled = true;
    
    // Se chegou até aqui, formulário é válido
    showNotification('Cadastrando cliente...', 'info');
});

// Adicionar SweetAlert2 se não estiver incluído
if (typeof Swal === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    document.head.appendChild(script);
}
</script>

<!-- SweetAlert2 para notificações -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include '../includes/footer.php'; ?>