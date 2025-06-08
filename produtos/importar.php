<?php
// produtos/importar.php
require_once '../config/database.php';
require '../vendor/autoload.php'; // Carrega o Composer autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;

$msg = '';
$msg_type = '';

// Processar o upload do arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo_excel'])) {
    $arquivo = $_FILES['arquivo_excel'];
    
    // Verificar se houve algum erro no upload
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $msg = "Erro no upload do arquivo. Código: " . $arquivo['error'];
        $msg_type = "danger";
    } else {
        // Verificar o tipo do arquivo
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        if (!in_array($extensao, ['xls', 'xlsx', 'csv'])) {
            $msg = "Formato de arquivo não suportado. Por favor, envie um arquivo Excel (.xls, .xlsx) ou CSV.";
            $msg_type = "danger";
        } else {
            try {
                // Carregar o arquivo Excel
                $spreadsheet = IOFactory::load($arquivo['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                // Verificar se há dados no arquivo
                if (count($rows) <= 1) { // Considerando que a primeira linha é o cabeçalho
                    $msg = "O arquivo não contém dados para importação.";
                    $msg_type = "warning";
                } else {
                    // Iniciar transação para garantir a integridade dos dados
                    $conn->begin_transaction();
                    
                    $total_importados = 0;
                    $total_atualizados = 0;
                    $total_erros = 0;
                    $erros = [];
                    
                    // Pular a primeira linha (cabeçalho)
                    for ($i = 1; $i < count($rows); $i++) {
                        $row = $rows[$i];
                        
                        // Verificar se a linha tem dados
                        if (empty($row[0])) continue;
                        
                        // Obter os dados da linha
                        $nome = $conn->real_escape_string(trim($row[0]));
                        $descricao = isset($row[1]) ? $conn->real_escape_string(trim($row[1])) : '';
                        $valor_venda = isset($row[2]) ? str_replace(',', '.', trim($row[2])) : 0;
                        
                        // Validar os dados
                        if (empty($nome)) {
                            $erros[] = "Linha " . ($i + 1) . ": Nome do produto é obrigatório.";
                            $total_erros++;
                            continue;
                        }
                        
                        if (!is_numeric($valor_venda) || $valor_venda <= 0) {
                            $erros[] = "Linha " . ($i + 1) . ": Valor de venda inválido para o produto '$nome'.";
                            $total_erros++;
                            continue;
                        }
                        
                        // Verificar se o produto já existe (pelo nome)
                        $sql_check = "SELECT id FROM produtos WHERE nome = '$nome'";
                        $result_check = $conn->query($sql_check);
                        
                        if ($result_check->num_rows > 0) {
                            // Atualizar produto existente
                            $produto_id = $result_check->fetch_assoc()['id'];
                            $sql_update = "UPDATE produtos SET descricao = '$descricao', valor_venda = $valor_venda WHERE id = $produto_id";
                            
                            if ($conn->query($sql_update)) {
                                $total_atualizados++;
                            } else {
                                $erros[] = "Erro ao atualizar o produto '$nome': " . $conn->error;
                                $total_erros++;
                            }
                        } else {
                            // Inserir novo produto
                            $sql_insert = "INSERT INTO produtos (nome, descricao, valor_venda) VALUES ('$nome', '$descricao', $valor_venda)";
                            
                            if ($conn->query($sql_insert)) {
                                $total_importados++;
                            } else {
                                $erros[] = "Erro ao inserir o produto '$nome': " . $conn->error;
                                $total_erros++;
                            }
                        }
                    }
                    
                    // Commit ou rollback dependendo do resultado
                    if ($total_erros > 0) {
                        $conn->rollback();
                        $msg = "Importação cancelada devido a erros. Nenhum produto foi importado.";
                        $msg_type = "danger";
                    } else {
                        $conn->commit();
                        $msg = "Importação concluída com sucesso! $total_importados produtos importados e $total_atualizados produtos atualizados.";
                        $msg_type = "success";
                    }
                    
                    // Armazenar erros na sessão para exibição
                    if (!empty($erros)) {
                        $_SESSION['import_errors'] = $erros;
                    }
                }
            } catch (Exception $e) {
                $msg = "Erro ao processar o arquivo: " . $e->getMessage();
                $msg_type = "danger";
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Importar Produtos</h1>
    <a href="listar.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Produtos
    </a>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['import_errors']) && !empty($_SESSION['import_errors'])): ?>
    <div class="alert alert-warning" role="alert">
        <h5 class="alert-heading">Erros encontrados:</h5>
        <ul>
            <?php foreach ($_SESSION['import_errors'] as $erro): ?>
                <li><?php echo $erro; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['import_errors']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Upload de Arquivo Excel</h5>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-4">
                <h6>Instruções:</h6>
                <ol>
                    <li>Prepare um arquivo Excel (.xls, .xlsx) ou CSV com as seguintes colunas:
                        <ul>
                            <li>Coluna A: Nome do Produto (obrigatório)</li>
                            <li>Coluna B: Descrição (opcional)</li>
                            <li>Coluna C: Valor de Venda (obrigatório, formato numérico)</li>
                        </ul>
                    </li>
                    <li>A primeira linha deve conter os cabeçalhos das colunas.</li>
                    <li>Se um produto com o mesmo nome já existir, seus dados serão atualizados.</li>
                </ol>
            </div>
            
            <div class="mb-3">
                <label for="arquivo_excel" class="form-label">Selecione o arquivo Excel</label>
                <input class="form-control" type="file" id="arquivo_excel" name="arquivo_excel" accept=".xls,.xlsx,.csv" required>
                <div class="form-text">Formatos aceitos: .xls, .xlsx, .csv</div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmar" required>
                    <label class="form-check-label" for="confirmar">
                        Confirmo que o arquivo está no formato correto e estou ciente que produtos existentes com o mesmo nome serão atualizados.
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Importar Produtos
            </button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Modelo de Arquivo</h5>
    </div>
    <div class="card-body">
        <p>Você pode baixar um modelo de arquivo Excel para importação:</p>
        <a href="modelo_importacao.php" class="btn btn-success">
            <i class="fas fa-download"></i> Baixar Modelo
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>