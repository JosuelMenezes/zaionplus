<?php
// funcionarios/editar.php - Sistema Premium de Funcionários
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
        
        // Validar status contra valores permitidos no ENUM
        $status_validos = ['ativo', 'inativo', 'ferias', 'licenca'];
        if (!in_array($status, $status_validos)) {
            $status = 'ativo'; // Valor padrão seguro
        }
        
        $salario = !empty($_POST['salario']) ? (float)str_replace(['.', ','], ['', '.'], $_POST['salario']) : null;
        
        // Garantir formato correto dos horários
        $horario_entrada = $_POST['horario_entrada'] ?? '08:00';
        $horario_saida = $_POST['horario_saida'] ?? '18:00';
        
        // Adicionar segundos se não estiver presente
        if (strlen($horario_entrada) === 5) {
            $horario_entrada .= ':00';
        }
        if (strlen($horario_saida) === 5) {
            $horario_saida .= ':00';
        }

        // Validações obrigatórias
        if (empty($codigo) || empty($nome) || empty($cpf) || empty($cargo) || empty($departamento) || empty($data_admissao)) {
            throw new Exception("Todos os campos obrigatórios devem ser preenchidos.");
        }

        // Verificar se código já existe (exceto o próprio funcionário)
        $stmt_check_codigo = $conn->prepare("SELECT id FROM funcionarios WHERE codigo = ? AND id != ?");
        $stmt_check_codigo->bind_param("si", $codigo, $funcionario_id);
        $stmt_check_codigo->execute();
        $result_check = $stmt_check_codigo->get_result();
        
        if ($result_check->num_rows > 0) {
            throw new Exception("Código já existe. Use outro código.");
        }
        $stmt_check_codigo->close();

        // Verificar se CPF já existe (exceto o próprio funcionário)
        if (!empty($cpf)) {
            $stmt_check_cpf = $conn->prepare("SELECT id FROM funcionarios WHERE cpf = ? AND id != ?");
            $stmt_check_cpf->bind_param("si", $cpf, $funcionario_id);
            $stmt_check_cpf->execute();
            $result_cpf = $stmt_check_cpf->get_result();
            
            if ($result_cpf->num_rows > 0) {
                throw new Exception("CPF já cadastrado no sistema.");
            }
            $stmt_check_cpf->close();
        }

        // Upload da foto (se houver)
        $foto_path = $funcionario['foto']; // Manter foto atual por padrão
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['foto']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../uploads/funcionarios/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Remover foto anterior se existir
                if (!empty($funcionario['foto']) && file_exists('../' . $funcionario['foto'])) {
                    unlink('../' . $funcionario['foto']);
                }
                
                $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $new_filename = $codigo . '_' . time() . '.' . $file_extension;
                $foto_path = 'uploads/funcionarios/' . $new_filename;
                
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], '../' . $foto_path)) {
                    $foto_path = $funcionario['foto']; // Manter foto anterior se falhar
                }
            }
        }

        // Atualizar funcionário usando prepared statements
        $sql = "UPDATE funcionarios SET 
                codigo = ?, nome = ?, cpf = ?, rg = ?, telefone = ?, email = ?, endereco = ?, 
                cargo = ?, departamento = ?, data_admissao = ?, status = ?, salario = ?, 
                horario_entrada = ?, horario_saida = ?, foto = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssdsssi", 
            $codigo, $nome, $cpf, $rg, $telefone, $email, $endereco, 
            $cargo, $departamento, $data_admissao, $status, $salario, 
            $horario_entrada, $horario_saida, $foto_path, $funcionario_id
        );

        if ($stmt->execute()) {
            $stmt->close();
            
            // Definir mensagem de sucesso
            $_SESSION['msg'] = "Funcionário '{$nome}' atualizado com sucesso!";
            $_SESSION['msg_type'] = "success";
            
            // Redirecionar para visualização
            header("Location: visualizar.php?id={$funcionario_id}");
            exit;
        } else {
            // Debug detalhado do erro
            $error_msg = "Erro ao atualizar funcionário: " . $stmt->error;
            error_log("MySQL Error: " . $stmt->error);
            error_log("Status value: " . $status);
            error_log("SQL: " . $sql);
            throw new Exception($error_msg);
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
    --funcionarios-gradient: linear-gradient(135deg, #7B68EE 0%, #9370DB 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    --danger-gradient: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
}

.edit-header {
    background: var(--funcionarios-gradient);
    color: white;
    padding: 30px;
    text-align: center;
    border-radius: 15px 15px 0 0;
    position: relative;
    overflow: hidden;
}

.edit-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 100px;
    height: 100px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(20px, -20px) rotate(120deg); }
    66% { transform: translate(-10px, 10px) rotate(240deg); }
}

.form-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 20px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    border-left: 4px solid var(--funcionarios-gradient);
    transition: all 0.3s ease;
}

.form-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
}

.form-section h5 {
    color: #7B68EE;
    font-weight: 600;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
}

.form-section h5 i {
    margin-right: 10px;
    width: 24px;
    text-align: center;
}

.form-floating {
    margin-bottom: 20px;
}

.form-control:focus {
    border-color: #7B68EE;
    box-shadow: 0 0 0 0.2rem rgba(123, 104, 238, 0.25);
}

.form-select:focus {
    border-color: #7B68EE;
    box-shadow: 0 0 0 0.2rem rgba(123, 104, 238, 0.25);
}

.photo-section {
    text-align: center;
    margin-bottom: 25px;
}

.current-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 4px solid #e9ecef;
    display: inline-block;
    background: #f8f9fa;
    position: relative;
    overflow: hidden;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.current-photo:hover {
    border-color: #7B68EE;
    transform: scale(1.05);
}

.current-photo img {
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

.photo-upload-area {
    border: 2px dashed #7B68EE;
    border-radius: 15px;
    padding: 30px;
    background: rgba(123, 104, 238, 0.05);
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 15px;
}

.photo-upload-area:hover {
    background: rgba(123, 104, 238, 0.1);
    border-color: #9370DB;
}

.btn-update {
    background: var(--funcionarios-gradient);
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-update::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.btn-update:hover::before {
    left: 100%;
}

.btn-update:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(123, 104, 238, 0.4);
    color: white;
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

.salary-preview {
    background: var(--success-gradient);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin-top: 15px;
    display: none;
}

.schedule-preview {
    background: rgba(123, 104, 238, 0.1);
    border: 1px solid rgba(123, 104, 238, 0.2);
    border-radius: 10px;
    padding: 15px;
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

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    flex-wrap: wrap;
    justify-content: center;
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

@media (max-width: 768px) {
    .form-section {
        padding: 20px;
    }
    
    .edit-header {
        padding: 20px;
    }
    
    .current-photo {
        width: 100px;
        height: 100px;
    }
    
    .photo-upload-area {
        padding: 20px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-update {
        width: 100%;
    }
}
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="listar.php">Funcionários</a></li>
        <li class="breadcrumb-item"><a href="visualizar.php?id=<?php echo $funcionario['id']; ?>"><?php echo htmlspecialchars($funcionario['nome']); ?></a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <!-- Header -->
                <div class="edit-header">
                    <h2><i class="fas fa-user-edit me-3"></i>Editar Funcionário</h2>
                    <p class="mb-0"><?php echo htmlspecialchars($funcionario['nome']); ?> - <?php echo htmlspecialchars($funcionario['codigo']); ?></p>
                </div>

                <!-- Form -->
                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <div class="card-body">
                        
                        <!-- Seção Foto -->
                        <div class="form-section fade-in">
                            <h5><i class="fas fa-camera"></i>Foto do Funcionário</h5>
                            
                            <div class="photo-section">
                                <div class="current-photo" style="background: linear-gradient(135deg, <?php echo substr(md5($funcionario['nome']), 0, 6); ?>, <?php echo substr(md5($funcionario['nome'] . 'x'), 0, 6); ?>);">
                                    <?php if (!empty($funcionario['foto']) && file_exists('../' . $funcionario['foto'])): ?>
                                        <img src="../<?php echo htmlspecialchars($funcionario['foto']); ?>" alt="Foto atual" id="currentImage">
                                    <?php else: ?>
                                        <div class="photo-placeholder">
                                            <?php echo strtoupper(substr($funcionario['nome'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="photo-upload-area" onclick="document.getElementById('foto').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-3"></i>
                                    <p class="mb-0"><strong>Clique para alterar a foto</strong></p>
                                    <small class="text-muted">JPG, PNG ou GIF - Máx. 5MB</small>
                                </div>
                                
                                <input type="file" id="foto" name="foto" accept="image/*" style="display: none;" onchange="previewNewPhoto(this)">
                            </div>
                        </div>

                        <!-- Seção Dados Pessoais -->
                        <div class="form-section fade-in">
                            <h5><i class="fas fa-user"></i>Informações Pessoais</h5>

                            <div class="quick-actions">
                                <button type="button" class="quick-btn" onclick="gerarCodigo()">
                                    <i class="fas fa-hashtag me-1"></i>Gerar Novo Código
                                </button>
                                <button type="button" class="quick-btn" onclick="resetarFoto()">
                                    <i class="fas fa-image me-1"></i>Remover Foto
                                </button>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="codigo" name="codigo" 
                                               value="<?php echo htmlspecialchars($funcionario['codigo']); ?>" 
                                               placeholder="Código do funcionário" maxlength="10" required>
                                        <label for="codigo">Código do Funcionário *</label>
                                        <div class="validation-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               value="<?php echo htmlspecialchars($funcionario['nome']); ?>" 
                                               placeholder="Nome completo" maxlength="100" required>
                                        <label for="nome">Nome Completo *</label>
                                        <div class="validation-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="cpf" name="cpf" 
                                               value="<?php echo htmlspecialchars($funcionario['cpf']); ?>" 
                                               placeholder="CPF" maxlength="14" required>
                                        <label for="cpf">CPF *</label>
                                        <div class="validation-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="rg" name="rg" 
                                               value="<?php echo htmlspecialchars($funcionario['rg']); ?>" 
                                               placeholder="RG" maxlength="20">
                                        <label for="rg">RG</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="telefone" name="telefone" 
                                               value="<?php echo htmlspecialchars($funcionario['telefone']); ?>" 
                                               placeholder="Telefone" maxlength="15">
                                        <label for="telefone">Telefone</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($funcionario['email']); ?>" 
                                               placeholder="E-mail" maxlength="100">
                                        <label for="email">E-mail</label>
                                        <div class="validation-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating">
                                <textarea class="form-control" id="endereco" name="endereco" 
                                          placeholder="Endereço completo" style="height: 100px"><?php echo htmlspecialchars($funcionario['endereco']); ?></textarea>
                                <label for="endereco">Endereço Completo</label>
                            </div>
                        </div>

                        <!-- Seção Dados Profissionais -->
                        <div class="form-section fade-in">
                            <h5><i class="fas fa-briefcase"></i>Informações Profissionais</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-control" id="cargo" name="cargo" required>
                                            <option value="">Selecione o cargo</option>
                                            <option value="Vendedor" <?php echo $funcionario['cargo'] == 'Vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                            <option value="Vendedor Senior" <?php echo $funcionario['cargo'] == 'Vendedor Senior' ? 'selected' : ''; ?>>Vendedor Sênior</option>
                                            <option value="Supervisor de Vendas" <?php echo $funcionario['cargo'] == 'Supervisor de Vendas' ? 'selected' : ''; ?>>Supervisor de Vendas</option>
                                            <option value="Gerente" <?php echo $funcionario['cargo'] == 'Gerente' ? 'selected' : ''; ?>>Gerente</option>
                                            <option value="Caixa" <?php echo $funcionario['cargo'] == 'Caixa' ? 'selected' : ''; ?>>Caixa</option>
                                            <option value="Estoquista" <?php echo $funcionario['cargo'] == 'Estoquista' ? 'selected' : ''; ?>>Estoquista</option>
                                            <option value="Atendente" <?php echo $funcionario['cargo'] == 'Atendente' ? 'selected' : ''; ?>>Atendente</option>
                                            <option value="Recepcionista" <?php echo $funcionario['cargo'] == 'Recepcionista' ? 'selected' : ''; ?>>Recepcionista</option>
                                            <option value="Auxiliar Administrativo" <?php echo $funcionario['cargo'] == 'Auxiliar Administrativo' ? 'selected' : ''; ?>>Auxiliar Administrativo</option>
                                            <option value="Analista" <?php echo $funcionario['cargo'] == 'Analista' ? 'selected' : ''; ?>>Analista</option>
                                            <option value="Coordenador" <?php echo $funcionario['cargo'] == 'Coordenador' ? 'selected' : ''; ?>>Coordenador</option>
                                            <option value="Diretor" <?php echo $funcionario['cargo'] == 'Diretor' ? 'selected' : ''; ?>>Diretor</option>
                                        </select>
                                        <label for="cargo">Cargo *</label>
                                        <div class="validation-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-control" id="departamento" name="departamento" required>
                                            <option value="">Selecione o departamento</option>
                                            <option value="Vendas" <?php echo $funcionario['departamento'] == 'Vendas' ? 'selected' : ''; ?>>Vendas</option>
                                            <option value="Administrativo" <?php echo $funcionario['departamento'] == 'Administrativo' ? 'selected' : ''; ?>>Administrativo</option>
                                            <option value="Financeiro" <?php echo $funcionario['departamento'] == 'Financeiro' ? 'selected' : ''; ?>>Financeiro</option>
                                            <option value="Estoque" <?php echo $funcionario['departamento'] == 'Estoque' ? 'selected' : ''; ?>>Estoque</option>
                                            <option value="Atendimento" <?php echo $funcionario['departamento'] == 'Atendimento' ? 'selected' : ''; ?>>Atendimento</option>
                                            <option value="Marketing" <?php echo $funcionario['departamento'] == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                            <option value="Recursos Humanos" <?php echo $funcionario['departamento'] == 'Recursos Humanos' ? 'selected' : ''; ?>>Recursos Humanos</option>
                                            <option value="TI" <?php echo $funcionario['departamento'] == 'TI' ? 'selected' : ''; ?>>TI</option>
                                            <option value="Logística" <?php echo $funcionario['departamento'] == 'Logística' ? 'selected' : ''; ?>>Logística</option>
                                            <option value="Compras" <?php echo $funcionario['departamento'] == 'Compras' ? 'selected' : ''; ?>>Compras</option>
                                        </select>
                                        <label for="departamento">Departamento *</label>
                                        <div class="validation-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="data_admissao" name="data_admissao" 
                                               value="<?php echo $funcionario['data_admissao']; ?>" required>
                                        <label for="data_admissao">Data de Admissão *</label>
                                        <div class="validation-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-control" id="status" name="status">
                                            <option value="ativo" <?php echo $funcionario['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="inativo" <?php echo $funcionario['status'] == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                            <option value="ferias" <?php echo $funcionario['status'] == 'ferias' ? 'selected' : ''; ?>>Férias</option>
                                            <option value="licenca" <?php echo $funcionario['status'] == 'licenca' ? 'selected' : ''; ?>>Licença</option>
                                        </select>
                                        <label for="status">Status</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating">
                                <input type="number" class="form-control" id="salario" name="salario" 
                                       value="<?php echo $funcionario['salario']; ?>" 
                                       placeholder="Salário" step="0.01" min="0">
                                <label for="salario">Salário Base (R$)</label>
                            </div>

                            <div class="salary-preview" id="salaryPreview">
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

                        <!-- Seção Horários -->
                        <div class="form-section fade-in">
                            <h5><i class="fas fa-clock"></i>Horários de Trabalho</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="time" class="form-control" id="horario_entrada" name="horario_entrada" 
                                               value="<?php echo !empty($funcionario['horario_entrada']) ? date('H:i', strtotime($funcionario['horario_entrada'])) : '08:00'; ?>">
                                        <label for="horario_entrada">Horário de Entrada</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="time" class="form-control" id="horario_saida" name="horario_saida" 
                                               value="<?php echo !empty($funcionario['horario_saida']) ? date('H:i', strtotime($funcionario['horario_saida'])) : '18:00'; ?>">
                                        <label for="horario_saida">Horário de Saída</label>
                                    </div>
                                </div>
                            </div>

                            <div class="schedule-preview">
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
                        </div>

                        <!-- Botões de Ação -->
                        <div class="action-buttons">
                            <a href="visualizar.php?id=<?php echo $funcionario['id']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-update">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Inicialização
$(document).ready(function() {
    initMasks();
    initValidations();
    initEventListeners();
    updateSalaryPreview();
    updateSchedulePreview();
    
    // Auto-save a cada 30 segundos
    setInterval(autoSave, 30000);
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
                data: { 
                    codigo: codigo,
                    funcionario_id: <?php echo $funcionario_id; ?>
                },
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
        updateSalaryPreview();
    });

    // Atualizar resumo de horários
    $('#horario_entrada, #horario_saida').on('change', function() {
        updateSchedulePreview();
    });

    // Atalhos de teclado
    $(document).on('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 's':
                    e.preventDefault();
                    $('#editForm').submit();
                    break;
                case 'z':
                    e.preventDefault();
                    if (confirm('Descartar todas as alterações?')) {
                        location.reload();
                    }
                    break;
            }
        }
        
        if (e.key === 'Escape') {
            e.preventDefault();
            window.location.href = 'visualizar.php?id=<?php echo $funcionario_id; ?>';
        }
    });

    // Confirmação antes de sair sem salvar
    let formChanged = false;
    $('#editForm input, #editForm select, #editForm textarea').on('change', function() {
        formChanged = true;
    });

    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Resetar flag quando submeter
    $('#editForm').on('submit', function() {
        formChanged = false;
    });
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

function updateSalaryPreview() {
    const salario = parseFloat($('#salario').val()) || 0;
    if (salario > 0) {
        $('#salarioBruto').text('R$ ' + salario.toLocaleString('pt-BR', {minimumFractionDigits: 2}));
        $('#salarioHora').text('R$ ' + (salario / 220).toLocaleString('pt-BR', {minimumFractionDigits: 2}));
        $('#salaryPreview').show();
    } else {
        $('#salaryPreview').hide();
    }
}

function updateSchedulePreview() {
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

// Preview da nova foto
function previewNewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#currentImage').attr('src', e.target.result);
            $('.photo-placeholder').hide();
            if (!$('#currentImage').length) {
                $('.current-photo').html(`<img src="${e.target.result}" id="currentImage" alt="Nova foto">`);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Ações rápidas
function gerarCodigo() {
    const codigo = 'FUNC' + Math.random().toString().substr(2, 6);
    $('#codigo').val(codigo).trigger('blur');
    
    Swal.fire({
        icon: 'info',
        title: 'Novo Código Gerado!',
        text: `Código gerado: ${codigo}`,
        timer: 2000,
        showConfirmButton: false
    });
}

function resetarFoto() {
    Swal.fire({
        title: 'Remover Foto',
        text: 'Deseja remover a foto atual do funcionário?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            // Resetar para placeholder
            const inicial = '<?php echo strtoupper(substr($funcionario['nome'], 0, 1)); ?>';
            $('.current-photo').html(`<div class="photo-placeholder">${inicial}</div>`);
            $('#foto').val('');
            
            Swal.fire({
                icon: 'success',
                title: 'Foto Removida!',
                text: 'A foto será removida quando salvar as alterações.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

// Auto-save
function autoSave() {
    const formData = {
        codigo: $('#codigo').val(),
        nome: $('#nome').val(),
        cargo: $('#cargo').val(),
        departamento: $('#departamento').val(),
        status: $('#status').val(),
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem(`funcionario_edit_${<?php echo $funcionario_id; ?>}`, JSON.stringify(formData));
}

// Submissão do formulário
$('#editForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validar campos obrigatórios
    let isValid = true;
    $(this).find('input[required], select[required]').each(function() {
        if (!$(this).val()) {
            $(this).addClass('is-invalid');
            isValid = false;
        }
    });

    if (!isValid) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos obrigatórios',
            text: 'Por favor, preencha todos os campos obrigatórios.',
            confirmButtonColor: '#7B68EE'
        });
        return;
    }

    Swal.fire({
        title: 'Confirmar Alterações',
        text: 'Deseja salvar as alterações feitas no funcionário?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, salvar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#7B68EE',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                // Submeter o formulário real
                this.submit();
                resolve();
            });
        }
    });
});

// Animações ao carregar
document.addEventListener('DOMContentLoaded', function() {
    // Animar seções com delay
    const sections = document.querySelectorAll('.form-section');
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, index * 200);
    });

    // Atualizar previews na inicialização
    updateSalaryPreview();
    updateSchedulePreview();
});

// Dica de atalhos
function mostrarAtalhos() {
    Swal.fire({
        title: 'Atalhos de Teclado',
        html: `
            <div class="text-start">
                <strong>Ações:</strong><br>
                Ctrl + S = Salvar alterações<br>
                Ctrl + Z = Descartar alterações<br>
                Esc = Cancelar e voltar<br><br>
                
                <strong>Navegação:</strong><br>
                Tab = Próximo campo<br>
                Shift + Tab = Campo anterior
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'Entendi',
        confirmButtonColor: '#7B68EE'
    });
}

// Atalho para mostrar ajuda
$(document).on('keydown', function(e) {
    if (e.key === 'F1') {
        e.preventDefault();
        mostrarAtalhos();
    }
});
</script>

<?php
// Incluir o rodapé
include_once '../includes/footer.php';
?>