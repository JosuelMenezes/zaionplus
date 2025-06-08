<?php
// funcionarios/cadastrar.php - Sistema Premium de Funcionários
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

// Processar formulário se foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validações server-side
        $codigo = trim($_POST['codigo'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $rg = trim($_POST['rg'] ?? '');
        $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $data_admissao = $_POST['data_admissao'] ?? '';
        $status = $_POST['status'] ?? 'ativo';
        $salario = !empty($_POST['salario']) ? (float)str_replace(['.', ','], ['', '.'], $_POST['salario']) : null;
        $horario_entrada = $_POST['horario_entrada'] ?? '08:00:00';
        $horario_saida = $_POST['horario_saida'] ?? '18:00:00';

        // Validações obrigatórias
        if (empty($codigo) || empty($nome) || empty($cpf) || empty($cargo) || empty($departamento) || empty($data_admissao)) {
            throw new Exception("Todos os campos obrigatórios devem ser preenchidos.");
        }

        // Verificar se código já existe
        $stmt_check_codigo = $conn->prepare("SELECT id FROM funcionarios WHERE codigo = ?");
        $stmt_check_codigo->bind_param("s", $codigo);
        $stmt_check_codigo->execute();
        $result_check = $stmt_check_codigo->get_result();
        
        if ($result_check->num_rows > 0) {
            throw new Exception("Código já existe. Use outro código.");
        }
        $stmt_check_codigo->close();

        // Verificar se CPF já existe
        if (!empty($cpf)) {
            $stmt_check_cpf = $conn->prepare("SELECT id FROM funcionarios WHERE cpf = ?");
            $stmt_check_cpf->bind_param("s", $cpf);
            $stmt_check_cpf->execute();
            $result_cpf = $stmt_check_cpf->get_result();
            
            if ($result_cpf->num_rows > 0) {
                throw new Exception("CPF já cadastrado no sistema.");
            }
            $stmt_check_cpf->close();
        }

        // Upload da foto
        $foto_path = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['foto']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../uploads/funcionarios/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $new_filename = $codigo . '_' . time() . '.' . $file_extension;
                $foto_path = 'uploads/funcionarios/' . $new_filename;
                
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], '../' . $foto_path)) {
                    $foto_path = null;
                }
            }
        }

        // Inserir funcionário usando prepared statements
        $sql = "INSERT INTO funcionarios (
            codigo, nome, cpf, rg, telefone, email, endereco, cargo, 
            departamento, data_admissao, status, salario, horario_entrada, 
            horario_saida, foto
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssdsss", 
            $codigo, $nome, $cpf, $rg, $telefone, $email, $endereco, 
            $cargo, $departamento, $data_admissao, $status, $salario, 
            $horario_entrada, $horario_saida, $foto_path
        );

        if ($stmt->execute()) {
            $funcionario_id = $conn->insert_id;
            $stmt->close();
            
            // Definir mensagem de sucesso
            $_SESSION['msg'] = "Funcionário '{$nome}' cadastrado com sucesso!";
            $_SESSION['msg_type'] = "success";
            
            // Redirecionar para listagem
            header("Location: listar.php");
            exit;
        } else {
            throw new Exception("Erro ao cadastrar funcionário: " . $conn->error);
        }

    } catch (Exception $e) {
        $_SESSION['msg'] = $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
}

// Incluir o cabeçalho
include_once '../includes/header.php';
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    --danger-gradient: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
    --funcionarios-gradient: linear-gradient(135deg, #7B68EE 0%, #9370DB 100%);
}

.main-container {
    min-height: 80vh;
    padding: 20px 0;
}

.wizard-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    overflow: hidden;
    max-width: 900px;
    margin: 0 auto;
}

.wizard-header {
    background: var(--funcionarios-gradient);
    color: white;
    padding: 30px;
    text-align: center;
    position: relative;
}

.wizard-header::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 20px solid transparent;
    border-right: 20px solid transparent;
    border-top: 20px solid #9370DB;
}

.wizard-progress {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px;
    background: #f8f9fa;
}

.step {
    display: flex;
    align-items: center;
    color: #6c757d;
    font-weight: 500;
    transition: all 0.3s ease;
}

.step.active {
    color: #7B68EE;
}

.step.completed {
    color: #28a745;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.step.active .step-number {
    background: var(--funcionarios-gradient);
    color: white;
    transform: scale(1.1);
}

.step.completed .step-number {
    background: var(--success-gradient);
    color: white;
}

.step-connector {
    width: 80px;
    height: 2px;
    background: #e9ecef;
    margin: 0 20px;
}

.step.completed + .step-connector {
    background: var(--success-gradient);
}

.wizard-content {
    padding: 40px;
}

.step-content {
    display: none;
    animation: fadeIn 0.5s ease;
}

.step-content.active {
    display: block;
}

.form-floating {
    margin-bottom: 20px;
}

.form-control:focus {
    border-color: #7B68EE;
    box-shadow: 0 0 0 0.2rem rgba(123, 104, 238, 0.25);
}

.btn-wizard {
    background: var(--funcionarios-gradient);
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-wizard:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(123, 104, 238, 0.3);
    color: white;
}

.btn-secondary-wizard {
    background: #6c757d;
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-secondary-wizard:hover {
    background: #5a6268;
    color: white;
}

.wizard-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #e9ecef;
}

.photo-upload {
    text-align: center;
    margin-bottom: 20px;
}

.photo-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 3px solid #e9ecef;
    display: inline-block;
    background: #f8f9fa;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
}

.photo-preview:hover {
    border-color: #7B68EE;
    transform: scale(1.05);
}

.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-placeholder {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #6c757d;
    font-size: 48px;
}

.quick-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.quick-btn {
    background: var(--info-gradient);
    border: none;
    padding: 8px 16px;
    border-radius: 15px;
    color: white;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.quick-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    color: white;
}

.info-card {
    background: var(--info-gradient);
    color: white;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 20px;
}

.salary-info {
    background: var(--success-gradient);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin-top: 15px;
}

.validation-feedback {
    display: block;
    font-size: 12px;
    margin-top: 5px;
}

.is-invalid {
    border-color: #dc3545;
}

.is-valid {
    border-color: #28a745;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); opacity: 0.9; }
    70% { transform: scale(0.9); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

.animate-bounce-in {
    animation: bounceIn 0.6s ease;
}

.department-badge {
    display: inline-block;
    padding: 5px 12px;
    background: var(--warning-gradient);
    color: white;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
    margin: 2px;
}

.schedule-info {
    background: rgba(123, 104, 238, 0.1);
    border: 1px solid rgba(123, 104, 238, 0.2);
    border-radius: 10px;
    padding: 15px;
    margin-top: 15px;
}

@media (max-width: 768px) {
    .wizard-progress {
        padding: 20px 10px;
    }
    
    .step-connector {
        width: 40px;
        margin: 0 10px;
    }
    
    .step-text {
        font-size: 12px;
    }
    
    .wizard-content {
        padding: 20px;
    }
}
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="listar.php">Funcionários</a></li>
        <li class="breadcrumb-item active">Cadastrar</li>
    </ol>
</nav>

<div class="main-container">
    <div class="container">
        <div class="wizard-container animate__animated animate__fadeIn">
            <!-- Header -->
            <div class="wizard-header">
                <h2><i class="fas fa-user-plus me-3"></i>Cadastrar Funcionário</h2>
                <p class="mb-0">Sistema Premium de Gestão de Funcionários</p>
            </div>

            <!-- Progress Steps -->
            <div class="wizard-progress">
                <div class="step active" id="step1">
                    <div class="step-number">1</div>
                    <span class="step-text">Dados Pessoais</span>
                </div>
                <div class="step-connector"></div>
                <div class="step" id="step2">
                    <div class="step-number">2</div>
                    <span class="step-text">Dados Profissionais</span>
                </div>
                <div class="step-connector"></div>
                <div class="step" id="step3">
                    <div class="step-number">3</div>
                    <span class="step-text">Horários & Foto</span>
                </div>
            </div>

            <!-- Form -->
            <form id="funcionarioForm" method="POST" enctype="multipart/form-data">
                <div class="wizard-content">
                    
                    <!-- Step 1: Dados Pessoais -->
                    <div class="step-content active" id="content1">
                        <div class="info-card animate-bounce-in">
                            <h5><i class="fas fa-info-circle me-2"></i>Informações Pessoais</h5>
                            <p class="mb-0">Preencha os dados pessoais do funcionário. Todos os campos marcados com * são obrigatórios.</p>
                        </div>

                        <div class="quick-actions">
                            <button type="button" class="quick-btn" onclick="preencherDadosDemo()">
                                <i class="fas fa-magic me-1"></i>Dados Demo
                            </button>
                            <button type="button" class="quick-btn" onclick="gerarCodigo()">
                                <i class="fas fa-hashtag me-1"></i>Gerar Código
                            </button>
                            <button type="button" class="quick-btn" onclick="limparFormulario()">
                                <i class="fas fa-eraser me-1"></i>Limpar Tudo
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Código do funcionário" maxlength="10" required>
                                    <label for="codigo">Código do Funcionário *</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome completo" maxlength="100" required>
                                    <label for="nome">Nome Completo *</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="cpf" name="cpf" placeholder="CPF" maxlength="14" required>
                                    <label for="cpf">CPF *</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="rg" name="rg" placeholder="RG" maxlength="20">
                                    <label for="rg">RG</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="Telefone" maxlength="15">
                                    <label for="telefone">Telefone</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="E-mail" maxlength="100">
                                    <label for="email">E-mail</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating">
                            <textarea class="form-control" id="endereco" name="endereco" placeholder="Endereço completo" style="height: 100px"></textarea>
                            <label for="endereco">Endereço Completo</label>
                        </div>
                    </div>

                    <!-- Step 2: Dados Profissionais -->
                    <div class="step-content" id="content2">
                        <div class="info-card animate-bounce-in">
                            <h5><i class="fas fa-briefcase me-2"></i>Informações Profissionais</h5>
                            <p class="mb-0">Configure o cargo, departamento e informações contratuais do funcionário.</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-control" id="cargo" name="cargo" required>
                                        <option value="">Selecione o cargo</option>
                                        <option value="Vendedor">Vendedor</option>
                                        <option value="Vendedor Senior">Vendedor Sênior</option>
                                        <option value="Supervisor de Vendas">Supervisor de Vendas</option>
                                        <option value="Gerente">Gerente</option>
                                        <option value="Caixa">Caixa</option>
                                        <option value="Estoquista">Estoquista</option>
                                        <option value="Atendente">Atendente</option>
                                        <option value="Recepcionista">Recepcionista</option>
                                        <option value="Auxiliar Administrativo">Auxiliar Administrativo</option>
                                        <option value="Analista">Analista</option>
                                        <option value="Coordenador">Coordenador</option>
                                        <option value="Diretor">Diretor</option>
                                    </select>
                                    <label for="cargo">Cargo *</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-control" id="departamento" name="departamento" required>
                                        <option value="">Selecione o departamento</option>
                                        <option value="Vendas">Vendas</option>
                                        <option value="Administrativo">Administrativo</option>
                                        <option value="Financeiro">Financeiro</option>
                                        <option value="Estoque">Estoque</option>
                                        <option value="Atendimento">Atendimento</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Recursos Humanos">Recursos Humanos</option>
                                        <option value="TI">TI</option>
                                        <option value="Logística">Logística</option>
                                        <option value="Compras">Compras</option>
                                    </select>
                                    <label for="departamento">Departamento *</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="data_admissao" name="data_admissao" required>
                                    <label for="data_admissao">Data de Admissão *</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-control" id="status" name="status">
                                        <option value="ativo" selected>Ativo</option>
                                        <option value="inativo">Inativo</option>
                                        <option value="ferias">Férias</option>
                                        <option value="licenca">Licença</option>
                                    </select>
                                    <label for="status">Status</label>
                                    <div class="validation-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating">
                            <input type="number" class="form-control" id="salario" name="salario" placeholder="Salário" step="0.01" min="0">
                            <label for="salario">Salário Base (R$)</label>
                        </div>

                        <div class="salary-info" id="salaryInfo" style="display: none;">
                            <h6><i class="fas fa-calculator me-2"></i>Informações Salariais</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small>Salário Bruto: <strong id="salarioBruto">R$ 0,00</strong></small>
                                </div>
                                <div class="col-6">
                                    <small>Salário/Hora: <strong id="salarioHora">R$ 0,00</strong></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Horários & Foto -->
                    <div class="step-content" id="content3">
                        <div class="info-card animate-bounce-in">
                            <h5><i class="fas fa-clock me-2"></i>Horários de Trabalho & Foto</h5>
                            <p class="mb-0">Defina os horários padrão e adicione uma foto do funcionário.</p>
                        </div>

                        <div class="photo-upload">
                            <div class="photo-preview" onclick="document.getElementById('foto').click()">
                                <div class="photo-placeholder">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <img id="previewImage" style="display: none;">
                            </div>
                            <input type="file" id="foto" name="foto" accept="image/*" style="display: none;" onchange="previewPhoto(this)">
                            <p class="mt-2 text-muted">Clique para adicionar foto</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="time" class="form-control" id="horario_entrada" name="horario_entrada" value="08:00">
                                    <label for="horario_entrada">Horário de Entrada</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="time" class="form-control" id="horario_saida" name="horario_saida" value="18:00">
                                    <label for="horario_saida">Horário de Saída</label>
                                </div>
                            </div>
                        </div>

                        <div class="schedule-info">
                            <h6><i class="fas fa-business-time me-2"></i>Resumo dos Horários</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <small>Entrada: <strong id="resumoEntrada">08:00</strong></small>
                                </div>
                                <div class="col-md-4">
                                    <small>Saída: <strong id="resumoSaida">18:00</strong></small>
                                </div>
                                <div class="col-md-4">
                                    <small>Carga Horária: <strong id="cargaHoraria">8h00</strong></small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="department-badge">Vendas</div>
                                <div class="department-badge">Horário Comercial</div>
                                <div class="department-badge">Segunda à Sexta</div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="wizard-actions">
                        <div class="d-flex gap-2">
                            <a href="listar.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar à Lista
                            </a>
                            <button type="button" class="btn btn-secondary-wizard" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                                <i class="fas fa-arrow-left me-2"></i>Anterior
                            </button>
                        </div>
                        <button type="button" class="btn btn-wizard" id="nextBtn" onclick="changeStep(1)">
                            Próximo<i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentStep = 1;
const totalSteps = 3;

// Inicialização
$(document).ready(function() {
    initMasks();
    initValidations();
    initEventListeners();
    
    // Definir data de admissão padrão como hoje
    const today = new Date().toISOString().split('T')[0];
    $('#data_admissao').val(today);
    
    // Auto-save a cada 30 segundos
    setInterval(autoSave, 30000);
    
    // Verificar rascunho salvo
    setTimeout(recuperarRascunho, 1000);
});

// Máscaras de entrada
function initMasks() {
    $('#cpf').mask('000.000.000-00');
    $('#telefone').mask('(00) 00000-0000');
}

// Validações em tempo real
function initValidations() {
    // Validação de CPF
    $('#cpf').on('blur', function() {
        const cpf = $(this).val().replace(/\D/g, '');
        if (cpf && !validarCPF(cpf)) {
            $(this).addClass('is-invalid').removeClass('is-valid');
            $(this).siblings('.validation-feedback').text('CPF inválido').addClass('invalid-feedback');
        } else if (cpf) {
            $(this).addClass('is-valid').removeClass('is-invalid');
            $(this).siblings('.validation-feedback').text('CPF válido').removeClass('invalid-feedback');
        }
    });

    // Validação de email
    $('#email').on('blur', function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid').removeClass('is-valid');
            $(this).siblings('.validation-feedback').text('E-mail inválido').addClass('invalid-feedback');
        } else if (email) {
            $(this).addClass('is-valid').removeClass('is-invalid');
            $(this).siblings('.validation-feedback').text('E-mail válido').removeClass('invalid-feedback');
        }
    });

    // Validação de código único
    $('#codigo').on('blur', function() {
        const codigo = $(this).val();
        if (codigo) {
            // Verificar se código já existe via AJAX
            $.ajax({
                url: 'verificar_codigo.php',
                method: 'POST',
                data: { codigo: codigo },
                success: function(response) {
                    if (response.exists) {
                        $('#codigo').addClass('is-invalid').removeClass('is-valid');
                        $('#codigo').siblings('.validation-feedback').text('Código já existe').addClass('invalid-feedback');
                    } else {
                        $('#codigo').addClass('is-valid').removeClass('is-invalid');
                        $('#codigo').siblings('.validation-feedback').text('Código disponível').removeClass('invalid-feedback');
                    }
                },
                error: function() {
                    // Se falhar a verificação, assumir que está ok
                    $('#codigo').addClass('is-valid').removeClass('is-invalid');
                    $('#codigo').siblings('.validation-feedback').text('Código verificado').removeClass('invalid-feedback');
                }
            });
        }
    });
}

// Event listeners
function initEventListeners() {
    // Atualizar informações salariais
    $('#salario').on('input', function() {
        const salario = parseFloat($(this).val()) || 0;
        if (salario > 0) {
            $('#salarioBruto').text('R$ ' + salario.toLocaleString('pt-BR', {minimumFractionDigits: 2}));
            $('#salarioHora').text('R$ ' + (salario / 220).toLocaleString('pt-BR', {minimumFractionDigits: 2}));
            $('#salaryInfo').show();
        } else {
            $('#salaryInfo').hide();
        }
    });

    // Atualizar resumo de horários
    $('#horario_entrada, #horario_saida').on('change', function() {
        updateScheduleSummary();
    });

    // Atalhos de teclado
    $(document).on('keydown', function(e) {
        if (e.altKey) {
            switch(e.key) {
                case '1':
                    e.preventDefault();
                    goToStep(1);
                    break;
                case '2':
                    e.preventDefault();
                    goToStep(2);
                    break;
                case '3':
                    e.preventDefault();
                    goToStep(3);
                    break;
                case 'n':
                    e.preventDefault();
                    changeStep(1);
                    break;
                case 'p':
                    e.preventDefault();
                    changeStep(-1);
                    break;
                case 's':
                    e.preventDefault();
                    if (currentStep === totalSteps) {
                        submitForm();
                    }
                    break;
            }
        }
        
        // Atalhos de função
        if (e.key === 'Escape') {
            e.preventDefault();
            mostrarAtalhos();
        }
        if (e.key === 'F1') {
            e.preventDefault();
            preencherDadosDemo();
        }
        if (e.key === 'F2') {
            e.preventDefault();
            gerarCodigo();
        }
        if (e.key === 'F3') {
            e.preventDefault();
            limparFormulario();
        }
    });
}

// Navegação entre steps
function changeStep(direction) {
    if (direction === 1 && currentStep < totalSteps) {
        if (validateCurrentStep()) {
            goToStep(currentStep + 1);
        }
    } else if (direction === -1 && currentStep > 1) {
        goToStep(currentStep - 1);
    } else if (direction === 1 && currentStep === totalSteps) {
        submitForm();
    }
}

function goToStep(step) {
    // Ocultar step atual
    $(`#content${currentStep}`).removeClass('active');
    $(`#step${currentStep}`).removeClass('active').addClass('completed');

    // Mostrar novo step
    currentStep = step;
    $(`#content${currentStep}`).addClass('active');
    $(`#step${currentStep}`).addClass('active').removeClass('completed');

    // Atualizar botões
    updateButtons();
}

function updateButtons() {
    if (currentStep === 1) {
        $('#prevBtn').hide();
    } else {
        $('#prevBtn').show();
    }

    if (currentStep === totalSteps) {
        $('#nextBtn').html('<i class="fas fa-save me-2"></i>Cadastrar Funcionário');
    } else {
        $('#nextBtn').html('Próximo<i class="fas fa-arrow-right ms-2"></i>');
    }
}

// Validação do step atual
function validateCurrentStep() {
    let isValid = true;
    const currentContent = $(`#content${currentStep}`);

    // Limpar validações anteriores
    currentContent.find('.is-invalid').removeClass('is-invalid');

    // Validar campos obrigatórios
    currentContent.find('input[required], select[required]').each(function() {
        if (!$(this).val()) {
            $(this).addClass('is-invalid');
            $(this).siblings('.validation-feedback').text('Campo obrigatório').addClass('invalid-feedback');
            isValid = false;
        }
    });

    if (!isValid) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos obrigatórios',
            text: 'Por favor, preencha todos os campos obrigatórios antes de continuar.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#7B68EE'
        });
    }

    return isValid;
}

// Funções utilitárias
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, '');
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    
    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let resto = 11 - (soma % 11);
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.charAt(9))) return false;
    
    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += parseInt(cpf.charAt(i)) * (11 - i);
    }
    resto = 11 - (soma % 11);
    if (resto === 10 || resto === 11) resto = 0;
    return resto === parseInt(cpf.charAt(10));
}

function updateScheduleSummary() {
    const entrada = $('#horario_entrada').val();
    const saida = $('#horario_saida').val();
    
    $('#resumoEntrada').text(entrada);
    $('#resumoSaida').text(saida);
    
    if (entrada && saida) {
        const [h1, m1] = entrada.split(':').map(Number);
        const [h2, m2] = saida.split(':').map(Number);
        const totalMinutos = (h2 * 60 + m2) - (h1 * 60 + m1);
        const horas = Math.floor(totalMinutos / 60);
        const minutos = totalMinutos % 60;
        $('#cargaHoraria').text(`${horas}h${minutos.toString().padStart(2, '0')}`);
    }
}

// Preview da foto
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#previewImage').attr('src', e.target.result).show();
            $('.photo-placeholder').hide();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Ações rápidas
function preencherDadosDemo() {
    $('#codigo').val('FUNC' + Math.random().toString().substr(2, 3));
    $('#nome').val('João Silva Santos');
    $('#cpf').val('123.456.789-01');
    $('#rg').val('12.345.678-9');
    $('#telefone').val('(11) 99999-9999');
    $('#email').val('joao.santos@empresa.com');
    $('#endereco').val('Rua das Flores, 123 - Centro - São Paulo/SP - CEP: 01234-567');
    $('#cargo').val('Vendedor');
    $('#departamento').val('Vendas');
    $('#salario').val('3000').trigger('input');
    
    Swal.fire({
        icon: 'success',
        title: 'Dados Demo Carregados!',
        text: 'Dados de exemplo foram preenchidos automaticamente.',
        timer: 2000,
        showConfirmButton: false
    });
}

function gerarCodigo() {
    const codigo = 'FUNC' + Math.random().toString().substr(2, 6);
    $('#codigo').val(codigo);
    
    Swal.fire({
        icon: 'info',
        title: 'Código Gerado!',
        text: `Código gerado: ${codigo}`,
        timer: 2000,
        showConfirmButton: false
    });
}

function limparFormulario() {
    Swal.fire({
        title: 'Confirmar Limpeza',
        text: 'Todos os dados serão perdidos. Deseja continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, limpar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('funcionarioForm').reset();
            $('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
            $('#previewImage').hide();
            $('.photo-placeholder').show();
            $('#salaryInfo').hide();
            
            // Resetar data de admissão para hoje
            const today = new Date().toISOString().split('T')[0];
            $('#data_admissao').val(today);
            
            goToStep(1);
            
            Swal.fire({
                icon: 'success',
                title: 'Formulário Limpo!',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

// Auto-save
function autoSave() {
    const formData = {
        step: currentStep,
        codigo: $('#codigo').val(),
        nome: $('#nome').val(),
        cpf: $('#cpf').val(),
        cargo: $('#cargo').val(),
        departamento: $('#departamento').val(),
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('funcionario_draft', JSON.stringify(formData));
}

// Recuperar rascunho
function recuperarRascunho() {
    const draft = localStorage.getItem('funcionario_draft');
    if (draft) {
        const data = JSON.parse(draft);
        
        Swal.fire({
            title: 'Rascunho Encontrado',
            text: `Encontramos um rascunho salvo em ${new Date(data.timestamp).toLocaleString()}. Deseja recuperar?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Recuperar',
            cancelButtonText: 'Começar novo',
            confirmButtonColor: '#7B68EE'
        }).then((result) => {
            if (result.isConfirmed) {
                Object.keys(data).forEach(key => {
                    if (key !== 'step' && key !== 'timestamp') {
                        $(`#${key}`).val(data[key]);
                    }
                });
                
                if (data.step) {
                    goToStep(data.step);
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Rascunho Recuperado!',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                localStorage.removeItem('funcionario_draft');
            }
        });
    }
}

// Submissão do formulário
function submitForm() {
    if (!validateCurrentStep()) {
        return;
    }

    Swal.fire({
        title: 'Confirmar Cadastro',
        text: 'Deseja cadastrar este funcionário?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, cadastrar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#7B68EE',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                // Submeter o formulário real
                document.getElementById('funcionarioForm').submit();
                resolve();
            });
        }
    });
}

// Dicas de atalhos
function mostrarAtalhos() {
    Swal.fire({
        title: 'Atalhos de Teclado',
        html: `
            <div class="text-start">
                <strong>Navegação:</strong><br>
                Alt + 1, 2, 3 = Ir para etapa específica<br>
                Alt + N = Próxima etapa<br>
                Alt + P = Etapa anterior<br>
                Alt + S = Salvar (na última etapa)<br><br>
                
                <strong>Ações Rápidas:</strong><br>
                F1 = Dados demo<br>
                F2 = Gerar código<br>
                F3 = Limpar formulário<br>
                Esc = Mostrar atalhos
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Entendi',
        confirmButtonColor: '#7B68EE'
    });
}

// Inicializar horários padrão
updateScheduleSummary();

// Tooltip para atalhos
$('[data-bs-toggle="tooltip"]').tooltip();
</script>

<?php
// Incluir o rodapé
include_once '../includes/footer.php';
?>