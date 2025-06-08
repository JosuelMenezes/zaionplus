<?php
// perfil.php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'config/database.php';

$usuario_id = $_SESSION['usuario_id'];
$msg = '';
$msg_type = '';

// Buscar informações do usuário
$sql = "SELECT * FROM usuarios WHERE id = $usuario_id";
$result = $conn->query($sql);
$usuario = $result->fetch_assoc();

// Processar formulário de atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['atualizar_perfil'])) {
        $nome = $conn->real_escape_string($_POST['nome']);
        $email = $conn->real_escape_string($_POST['email']);
        
        // Verificar se o email já está em uso por outro usuário
        $sql_check = "SELECT id FROM usuarios WHERE email = '$email' AND id != $usuario_id";
        $result_check = $conn->query($sql_check);
        
        if ($result_check->num_rows > 0) {
            $msg = "Este email já está sendo usado por outro usuário.";
            $msg_type = "danger";
        } else {
            // Atualizar perfil
            $sql_update = "UPDATE usuarios SET nome = '$nome', email = '$email' WHERE id = $usuario_id";
            
            if ($conn->query($sql_update)) {
                $_SESSION['usuario_nome'] = $nome;
                $_SESSION['usuario_email'] = $email;
                $msg = "Perfil atualizado com sucesso!";
                $msg_type = "success";
                
                // Atualizar dados do usuário na sessão
                $usuario['nome'] = $nome;
                $usuario['email'] = $email;
            } else {
                $msg = "Erro ao atualizar perfil: " . $conn->error;
                $msg_type = "danger";
            }
        }
    } elseif (isset($_POST['alterar_senha'])) {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];
        
        // Verificar se a senha atual está correta
        if (!password_verify($senha_atual, $usuario['senha'])) {
            $msg = "Senha atual incorreta.";
            $msg_type = "danger";
        } elseif ($nova_senha !== $confirmar_senha) {
            $msg = "A nova senha e a confirmação não coincidem.";
            $msg_type = "danger";
        } elseif (strlen($nova_senha) < 6) {
            $msg = "A nova senha deve ter pelo menos 6 caracteres.";
            $msg_type = "danger";
        } else {
            // Hash da nova senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            // Atualizar senha
            $sql_update = "UPDATE usuarios SET senha = '$senha_hash' WHERE id = $usuario_id";
            
            if ($conn->query($sql_update)) {
                $msg = "Senha alterada com sucesso!";
                $msg_type = "success";
            } else {
                $msg = "Erro ao alterar senha: " . $conn->error;
                $msg_type = "danger";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Meu Perfil</h1>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar para o Dashboard
        </a>
    </div>
    
    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informações do Perfil</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nível de Acesso</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($usuario['nivel_acesso']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data de Criação</label>
                            <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($usuario['data_criacao'])); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Último Acesso</label>
                            <input type="text" class="form-control" value="<?php echo $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Primeiro acesso'; ?>" readonly>
                        </div>
                        <button type="submit" name="atualizar_perfil" class="btn btn-primary">
                            <i class="fas fa-save"></i> Atualizar Perfil
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Alterar Senha</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                        </div>
                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6">
                            <div class="form-text">A senha deve ter pelo menos 6 caracteres.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                        </div>
                        <button type="submit" name="alterar_senha" class="btn btn-warning">
                            <i class="fas fa-key"></i> Alterar Senha
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>