<?php
// contas/criar.php
// Formulário para criar nova conta - Sistema Domaria Café

require_once 'config.php';

$page_title = "Nova Conta";
$page_description = "Cadastrar conta a pagar ou receber";

// Verificar se a conexão PDO existe
if (!isset($pdo_connection) || $pdo_connection === null) {
    die("Erro: Conexão com banco de dados não estabelecida");
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo_connection->beginTransaction();
    try {
        // Validar dados obrigatórios
        $required_fields = ['tipo', 'descricao', 'valor_original', 'data_vencimento', 'data_competencia'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Campo obrigatório não preenchido: " . $field);
            }
        }
        
        // Preparar dados
        $dados = [
            'tipo' => $_POST['tipo'],
            'categoria_id' => !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
            'descricao' => trim($_POST['descricao']),
            'valor_original' => str_replace(['.', ','], ['', '.'], $_POST['valor_original']),
            'valor_pendente' => str_replace(['.', ','], ['', '.'], $_POST['valor_original']),
            'data_vencimento' => $_POST['data_vencimento'],
            'data_competencia' => $_POST['data_competencia'],
            'prioridade' => $_POST['prioridade'] ?? 'media',
            'cliente_id' => !empty($_POST['cliente_id']) ? $_POST['cliente_id'] : null,
            'fornecedor_id' => !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null,
            'venda_id' => !empty($_POST['venda_id']) ? $_POST['venda_id'] : null,
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'documento' => trim($_POST['documento'] ?? ''),
            'forma_pagamento' => $_POST['forma_pagamento'] ?? '',
            'recorrente' => isset($_POST['recorrente']) ? 1 : 0,
            'periodicidade' => !empty($_POST['periodicidade']) ? $_POST['periodicidade'] : null,
            'dia_vencimento' => !empty($_POST['dia_vencimento']) ? $_POST['dia_vencimento'] : null,
            'usuario_cadastro' => $_SESSION['usuario_id']
        ];
        
        // Validações específicas
        if ($dados['valor_original'] <= 0) {
            throw new Exception("Valor deve ser maior que zero");
        }
        
        if ($dados['recorrente'] && empty($dados['periodicidade'])) {
            throw new Exception("Periodicidade é obrigatória para contas recorrentes");
        }
        
        // Inserir conta
        $sql = "
            INSERT INTO contas (
                tipo, categoria_id, descricao, valor_original, valor_pendente,
                data_vencimento, data_competencia, prioridade,
                cliente_id, fornecedor_id, venda_id, observacoes, documento,
                forma_pagamento, recorrente, periodicidade, dia_vencimento,
                usuario_cadastro
            ) VALUES (
                :tipo, :categoria_id, :descricao, :valor_original, :valor_pendente,
                :data_vencimento, :data_competencia, :prioridade,
                :cliente_id, :fornecedor_id, :venda_id, :observacoes, :documento,
                :forma_pagamento, :recorrente, :periodicidade, :dia_vencimento,
                :usuario_cadastro
            )
        ";
        
        $stmt = $pdo_connection->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Erro ao preparar statement");
        }
        
        $stmt->execute($dados);
        
        $conta_id = $pdo_connection->lastInsertId();
        
        // Se for recorrente, criar próximas contas
        if ($dados['recorrente']) {
            criarContasRecorrentes($conta_id, $dados, $pdo_connection);
        }
        
        $pdo_connection->commit();
        
        $_SESSION['msg'] = "Conta cadastrada com sucesso!";
        $_SESSION['msg_type'] = "success";
        
        header("Location: detalhes.php?id=" . $conta_id);
        exit;
        
    } catch (Exception $e) {
        if ($pdo_connection && $pdo_connection->inTransaction()) {
            $pdo_connection->rollBack();
        }
        $erro = $e->getMessage();
    }
}

// Buscar dados para formulário
$categorias = [];
if (isset($contasManager) && $contasManager !== null) {
    $categorias = $contasManager->buscarCategorias();
}

// <<< CORREÇÃO APLICADA AQUI >>>
// Buscar clientes para select (removido o filtro WHERE ativo = 1)
$clientes = [];
try {
    $sql_clientes = "SELECT id, nome FROM clientes ORDER BY nome";
    $stmt_clientes = $pdo_connection->query($sql_clientes);
    if ($stmt_clientes !== false) {
        $clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Erro ao buscar clientes: " . $e->getMessage());
}

// <<< CORREÇÃO APLICADA AQUI >>>
// Buscar fornecedores para select (removido o filtro WHERE ativo = 1)
$fornecedores = [];
try {
    $sql_fornecedores = "SELECT id, nome FROM fornecedores ORDER BY nome";
    $stmt_fornecedores = $pdo_connection->query($sql_fornecedores);
    if ($stmt_fornecedores !== false) {
        $fornecedores = $stmt_fornecedores->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Erro ao buscar fornecedores: " . $e->getMessage());
}


/**
 * Função para criar contas recorrentes
 */
function criarContasRecorrentes($conta_origem_id, $dados, PDO $pdo) {
    $intervalos = [
        'mensal' => 'P1M', 'bimestral' => 'P2M', 'trimestral' => 'P3M',
        'semestral' => 'P6M', 'anual' => 'P1Y'
    ];
    if (!isset($intervalos[$dados['periodicidade']])) return;

    $intervalo = new DateInterval($intervalos[$dados['periodicidade']]);
    $data_vencimento = new DateTime($dados['data_vencimento']);
    $data_competencia = new DateTime($dados['data_competencia']);

    $sql = "INSERT INTO contas (tipo, categoria_id, descricao, valor_original, valor_pendente, data_vencimento, data_competencia, prioridade, cliente_id, fornecedor_id, observacoes, documento, forma_pagamento, recorrente, periodicidade, dia_vencimento, usuario_cadastro, conta_origem_id) VALUES (:tipo, :categoria_id, :descricao, :valor_original, :valor_pendente, :data_vencimento, :data_competencia, :prioridade, :cliente_id, :fornecedor_id, :observacoes, :documento, :forma_pagamento, :recorrente, :periodicidade, :dia_vencimento, :usuario_cadastro, :conta_origem_id)";
    $stmt = $pdo->prepare($sql);

    // Gera as próximas 11 parcelas
    for ($i = 1; $i <= 11; $i++) {
        $data_vencimento->add($intervalo);
        $data_competencia->add($intervalo);

        if (!empty($dados['dia_vencimento'])) {
            $dia = (int)$dados['dia_vencimento'];
            $ultimo_dia_mes = (int)$data_vencimento->format('t');
            $data_vencimento->setDate($data_vencimento->format('Y'), $data_vencimento->format('m'), min($dia, $ultimo_dia_mes));
        }
        
        $dados_recorrente = $dados;
        $dados_recorrente['data_vencimento'] = $data_vencimento->format('Y-m-d');
        $dados_recorrente['data_competencia'] = $data_competencia->format('Y-m-d');
        $dados_recorrente['descricao'] = $dados['descricao'] . " (" . $data_vencimento->format('m/Y') . ")";
        $dados_recorrente['conta_origem_id'] = $conta_origem_id;
        unset($dados_recorrente['venda_id']);

        $stmt->execute($dados_recorrente);
    }
}

require_once '../includes/header.php';
?>

<style>
/* SEU CSS COMPLETO AQUI */
.form-conta { background: white; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-top: 4px solid #667eea; }
.form-section { margin-bottom: 1.5rem; }
.form-section h6 { font-weight: 600; color: #343a40; padding-bottom: 0.5rem; border-bottom: 1px solid #e9ecef; margin-bottom: 1rem; }
.tipo-selector { display: flex; gap: 1rem; }
.tipo-option { flex: 1; }
.tipo-option input[type="radio"] { display: none; }
.tipo-option label { display: block; padding: 1.5rem; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.2s ease; }
.tipo-option label:hover { border-color: #667eea; }
.tipo-option input[type="radio"]:checked + label { border-color: #667eea; background-color: #f0f2ff; box-shadow: 0 0 0 2px #667eea; }
.recorrente-config { display: none; }
.recorrente-config.show { display: block; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Nova Conta</h1>
        <p class="text-muted mb-0">Cadastrar conta a pagar ou receber</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
    </div>
</div>

<?php if (isset($erro)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($erro); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" id="formConta">
    <div class="form-conta p-4">
        
        <div class="form-section">
            <h6><i class="fas fa-tags me-2 text-primary"></i>Tipo da Conta</h6>
            <div class="tipo-selector">
                <div class="tipo-option">
                    <input type="radio" name="tipo" value="pagar" id="tipo_pagar" required>
                    <label for="tipo_pagar"><i class="fas fa-arrow-down text-danger fa-2x mb-2"></i><div>A Pagar</div></label>
                </div>
                <div class="tipo-option">
                    <input type="radio" name="tipo" value="receber" id="tipo_receber" required>
                    <label for="tipo_receber"><i class="fas fa-arrow-up text-success fa-2x mb-2"></i><div>A Receber</div></label>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h6><i class="fas fa-info-circle me-2 text-primary"></i>Informações Básicas</h6>
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="descricao" id="descricao" placeholder="Descrição" required>
                        <label for="descricao">Descrição da Conta *</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select class="form-select" name="categoria_id" id="categoria_id"><option value="">Selecione...</option></select>
                        <label for="categoria_id">Categoria</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="valor_original" id="valor_original" placeholder="Valor" required>
                        <label for="valor_original">Valor *</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="date" class="form-control" name="data_vencimento" id="data_vencimento" required>
                        <label for="data_vencimento">Data de Vencimento *</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="date" class="form-control" name="data_competencia" id="data_competencia" required>
                        <label for="data_competencia">Data de Competência *</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating">
                        <select class="form-select" name="prioridade" id="prioridade">
                            <option value="media" selected>Média</option><option value="baixa">Baixa</option><option value="alta">Alta</option>
                        </select>
                        <label for="prioridade">Prioridade</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h6><i class="fas fa-link me-2 text-primary"></i>Relacionamentos</h6>
            <div class="row g-3">
                <div class="col-md-6" id="campo_cliente" style="display: none;">
                    <div class="form-floating">
                        <select class="form-select" name="cliente_id" id="cliente_id">
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="cliente_id">Cliente</label>
                    </div>
                </div>
                <div class="col-md-6" id="campo_fornecedor" style="display: none;">
                    <div class="form-floating">
                        <select class="form-select" name="fornecedor_id" id="fornecedor_id">
                            <option value="">Selecione um fornecedor</option>
                            <?php foreach ($fornecedores as $fornecedor): ?>
                                <option value="<?php echo $fornecedor['id']; ?>"><?php echo htmlspecialchars($fornecedor['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="fornecedor_id">Fornecedor</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-section">
             <h6><i class="fas fa-sync-alt me-2 text-primary"></i>Recorrência</h6>
             <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="recorrente" id="recorrente">
                <label class="form-check-label" for="recorrente">Esta é uma conta recorrente</label>
            </div>
            <div class="recorrente-config mt-3" id="recorrente-config">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="periodicidade" id="periodicidade"><option value="">Selecione...</option><option value="mensal">Mensal</option></select>
                            <label for="periodicidade">Periodicidade</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="dia_vencimento" id="dia_vencimento" min="1" max="31" placeholder="Dia">
                            <label for="dia_vencimento">Dia do Vencimento (Opcional)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end">
            <a href="index.php" class="btn btn-secondary me-2">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Conta</button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorias = <?php echo json_encode($categorias, JSON_NUMERIC_CHECK); ?>;
    const categoriasPorTipo = {
        'pagar': categorias.filter(cat => cat.tipo === 'pagar' || cat.tipo === 'ambos'),
        'receber': categorias.filter(cat => cat.tipo === 'receber' || cat.tipo === 'ambos')
    };

    function atualizarVisibilidade(tipo) {
        document.getElementById('campo_cliente').style.display = tipo === 'receber' ? 'block' : 'none';
        document.getElementById('campo_fornecedor').style.display = tipo === 'pagar' ? 'block' : 'none';
        document.getElementById('cliente_id').value = '';
        document.getElementById('fornecedor_id').value = '';

        const categoriaSelect = document.getElementById('categoria_id');
        categoriaSelect.innerHTML = '<option value="">Selecione uma categoria</option>';
        const lista = categoriasPorTipo[tipo] || [];
        lista.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.nome;
            categoriaSelect.appendChild(option);
        });
    }

    document.querySelectorAll('input[name="tipo"]').forEach(input => {
        input.addEventListener('change', () => atualizarVisibilidade(input.value));
    });

    const recorrenteCheck = document.getElementById('recorrente');
    recorrenteCheck.addEventListener('change', () => {
        document.getElementById('recorrente-config').style.display = recorrenteCheck.checked ? 'block' : 'none';
        document.getElementById('periodicidade').required = recorrenteCheck.checked;
    });

    const hoje = new Date().toISOString().split('T')[0];
    document.getElementById('data_vencimento').value = hoje;
    document.getElementById('data_competencia').value = hoje;
});
</script>

<?php require_once '../includes/footer.php'; ?>