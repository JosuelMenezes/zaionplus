<?php
// clientes/editar.php
require_once '../config/database.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: listar.php?msg=ID do cliente não fornecido&type=danger");
    exit;
}

$id = intval($_GET['id']);

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $conn->real_escape_string($_POST['nome']);
    $telefone = $conn->real_escape_string($_POST['telefone']);
    $empresa = $conn->real_escape_string($_POST['empresa']);
    $limite_compra = str_replace(',', '.', $_POST['limite_compra']);
    
    $sql = "UPDATE clientes SET nome = '$nome', telefone = '$telefone', empresa = '$empresa', limite_compra = '$limite_compra' WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: listar.php?msg=Cliente atualizado com sucesso!&type=success");
        exit;
    } else {
        $error = "Erro ao atualizar cliente: " . $conn->error;
    }
}

// Buscar dados do cliente
$sql = "SELECT * FROM clientes WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header("Location: listar.php?msg=Cliente não encontrado&type=danger");
    exit;
}

$cliente = $result->fetch_assoc();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Editar Cliente</h1>
    <a href="listar.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>" required>
                <small class="form-text text-muted">Formato: (99) 99999-9999</small>
            </div>
            
            <div class="mb-3">
                <label for="empresa" class="form-label">Empresa</label>
                <input type="text" class="form-control" id="empresa" name="empresa" value="<?php echo htmlspecialchars($cliente['empresa']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="limite_compra" class="form-label">Limite de Compra</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" class="form-control" id="limite_compra" name="limite_compra" value="<?php echo number_format($cliente['limite_compra'], 2, ',', ''); ?>" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Atualizar</button>
        </form>
    </div>
</div>

<!-- Adicione antes do fechamento do body -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
$(document).ready(function(){
    $('#telefone').mask('(00) 00000-0000');
    
    // Máscara para o campo de valor
    $('#limite_compra').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        value = (parseInt(value || 0) / 100).toFixed(2).replace('.', ',');
        $(this).val(value);
    });
});
</script>

<?php include '../includes/footer.php'; ?>