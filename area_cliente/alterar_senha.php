<?php
// area_cliente/alterar_senha.php
require_once '../config/database.php';

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e é um cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] != 'cliente' || !isset($_SESSION['cliente_id'])) {
    $_SESSION['msg'] = "Acesso restrito";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../index.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    // Validações
    $erros = [];
    
    if (empty($senha_atual)) {
        $erros[] = "A senha atual é obrigatória";
    }
    
    if (empty($nova_senha)) {
        $erros[] = "A nova senha é obrigatória";
    } elseif (strlen($nova_senha) < 6) {
        $erros[] = "A nova senha deve ter pelo menos 6 caracteres";
    }
    
    if ($nova_senha !== $confirmar_senha) {
        $erros[] = "As senhas não conferem";
    }
    
    // Verificar se a senha atual está correta
    if (empty($erros)) {
        $sql = "SELECT senha FROM usuarios WHERE id = $usuario_id";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            
            if (!password_verify($senha_atual, $usuario['senha'])) {
                $erros[] = "Senha atual incorreta";
            }
        } else {
            $erros[] = "Usuário não encontrado";
        }
    }
    
    // Se não houver erros, atualizar a senha
    if (empty($erros)) {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql_update = "UPDATE usuarios SET senha = '$senha_hash' WHERE id = $usuario_id";
        
        if ($conn->query($sql_update)) {
            $_SESSION['msg'] = "Senha alterada com sucesso";
            $_SESSION['msg_type'] = "success";
            header("Location: dashboard.php");
            exit;
        } else {
            $erros[] = "Erro ao alterar senha: " . $conn->error;
        }
    }
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Alterar Senha</h1>
    <a href="dashboard.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<?php if (isset($_SESSION['msg']) && isset($_SESSION['msg_type'])): ?>
    <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
<?php endif; ?>

<?php if (isset($erros) && !empty($erros)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">Erro ao alterar senha:</h5>
        <ul class="mb-0">
            <?php foreach ($erros as $erro): ?>
                <li><?php echo $erro; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Alterar Senha</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="senha_atual" class="form-label">Senha Atual <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleSenhaAtual">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nova_senha" class="form-label">Nova Senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleNovaSenha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">A senha deve ter pelo menos 6 caracteres</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">Confirmar Nova Senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmarSenha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para mostrar/ocultar senha atual
    const toggleSenhaAtual = document.getElementById('toggleSenhaAtual');
    const senhaAtualInput = document.getElementById('senha_atual');
    
    toggleSenhaAtual.addEventListener('click', function() {
        const type = senhaAtualInput.getAttribute('type') === 'password' ? 'text' : 'password';
        senhaAtualInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    // Toggle para mostrar/ocultar nova senha
    const toggleNovaSenha = document.getElementById('toggleNovaSenha');
    const novaSenhaInput = document.getElementById('nova_senha');
    
    toggleNovaSenha.addEventListener('click', function() {
        const type = novaSenhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
        novaSenhaInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    // Toggle para mostrar/ocultar confirmação de senha
    const toggleConfirmarSenha = document.getElementById('toggleConfirmarSenha');
    const confirmarSenhaInput = document.getElementById('confirmar_senha');
    
    toggleConfirmarSenha.addEventListener('click', function() {
        const type = confirmarSenhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmarSenhaInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
});
</script>

<?php include '../includes/footer.php'; ?>