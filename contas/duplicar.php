<?php
// contas/duplicar.php
// Página para duplicar contas

require_once 'config.php';

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['msg'] = "Conta não encontrada.";
    $_SESSION['msg_type'] = "error";
    header("Location: index.php");
    exit;
}

$conta_id = (int)$_GET['id'];

try {
    // Buscar dados da conta original
    $sql = "SELECT * FROM contas WHERE id = :id";
    $stmt = $pdo_connection->prepare($sql);
    $stmt->execute([':id' => $conta_id]);
    $conta_original = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conta_original) {
        throw new Exception('Conta não encontrada');
    }
    
    // Processar duplicação
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pdo_connection->beginTransaction();
        
        // Preparar dados para nova conta
        $dados_nova = [
            'tipo' => $conta_original['tipo'],
            'categoria_id' => $conta_original['categoria_id'],
            'descricao' => $_POST['descricao'],
            'valor_original' => str_replace(['.', ','], ['', '.'], $_POST['valor_original']),
            'valor_pendente' => str_replace(['.', ','], ['', '.'], $_POST['valor_original']),
            'data_vencimento' => $_POST['data_vencimento'],
            'data_competencia' => $_POST['data_competencia'],
            'prioridade' => $_POST['prioridade'] ?? $conta_original['prioridade'],
            'cliente_id' => $conta_original['cliente_id'],
            'fornecedor_id' => $conta_original['fornecedor_id'],
            'observacoes' => $_POST['observacoes'] ?? $conta_original['observacoes'],
            'documento' => $_POST['documento'] ?? '',
            'forma_pagamento' => $conta_original['forma_pagamento'],
            'recorrente' => isset($_POST['recorrente']) ? 1 : 0,
            'periodicidade' => $_POST['periodicidade'] ?? null,
            'dia_vencimento' => $_POST['dia_vencimento'] ?? null,
            'usuario_cadastro' => $_SESSION['usuario_id']
        ];
        
        // Inserir nova conta
        $sql_insert = "
            INSERT INTO contas (
                tipo, categoria_id, descricao, valor_original, valor_pendente,
                data_vencimento, data_competencia, prioridade,
                cliente_id, fornecedor_id, observacoes, documento,
                forma_pagamento, recorrente, periodicidade, dia_vencimento,
                usuario_cadastro
            ) VALUES (
                :tipo, :categoria_id, :descricao, :valor_original, :valor_pendente,
                :data_vencimento, :data_competencia, :prioridade,
                :cliente_id, :fornecedor_id, :observacoes, :documento,
                :forma_pagamento, :recorrente, :periodicidade, :dia_vencimento,
                :usuario_cadastro
            )
        ";
        
        $stmt_insert = $pdo_connection->prepare($sql_insert);
        $stmt_insert->execute($dados_nova);
        
        $nova_conta_id = $pdo_connection->lastInsertId();
        
        $pdo_connection->commit();
        
        $_SESSION['msg'] = "Conta duplicada com sucesso!";
        $_SESSION['msg_type'] = "success";
        
        header("Location: detalhes.php?id=" . $nova_conta_id);
        exit;
    }
    
} catch (Exception $e) {
    if (isset($pdo_connection)) {
        $pdo_connection->rollBack();
    }
    $erro = $e->getMessage();
}

$page_title = "Duplicar Conta";
$page_description = "Duplicar: " . htmlspecialchars($conta_original['descricao']);

require_once '../header.php';
?>

<style>
.form-duplicar {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-soft);
    border-top: 4px solid;
    border-image: var(--gradient-contas) 1;
}

.conta-original {
    background: rgba(102, 126, 234, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.1);
    border-radius: 8px;
    margin-bottom: 2rem;
}

.campo-modificado {
    background: rgba(255, 193, 7, 0.1);
    border-color: rgba(255, 193, 7, 0.3);
}

.fade-in {
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Header da Página -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <h1 class="h3 mb-1">
            <i class="fas fa-copy text-primary me-2"></i>
            Duplicar Conta
        </h1>
        <p class="text-muted mb-0">Criar nova conta baseada em conta existente</p>
    </div>
    <div>
        <a href="detalhes.php?id=<?php echo $conta_id; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Voltar
        </a>
    </div>
</div>

<!-- Conta Original -->
<div class="conta-original p-4 fade-in">
    <h5 class="mb-3">
        <i class="fas fa-file-invoice text-primary me-2"></i>
        Conta Original
    </h5>
    
    <div class="row">
        <div class="col-md-8">
            <strong><?php echo htmlspecialchars($conta_original['descricao']); ?></strong>
            <div class="text-muted">
                <?php echo formatarMoeda($conta_original['valor_original']); ?> • 
                Vencimento: <?php echo formatarData($conta_original['data_vencimento']); ?>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <span class="badge bg-primary">
                <?php echo $conta_original['tipo'] == 'pagar' ? 'A Pagar' : 'A Receber'; ?>
            </span>
        </div>
    </div>
</div>

<!-- Alertas -->
<?php if (isset($erro)): ?>
    <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($erro); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Formulário -->
<form method="POST" class="fade-in" style="animation-delay: 0.2s">
    <div class="form-duplicar p-4">
        
        <h5 class="mb-4">
            <i class="fas fa-edit text-success me-2"></i>
            Dados da Nova Conta
        </h5>
        
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Descrição *</label>
                <input type="text" class="form-control" name="descricao" 
                       value="<?php echo htmlspecialchars($conta_original['descricao']); ?> (Cópia)" 
                       required maxlength="255">
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Valor *</label>
                <input type="text" class="form-control" name="valor_original" 
                       value="<?php echo number_format($conta_original['valor_original'], 2, ',', '.'); ?>" 
                       required data-mask="money">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Data de Vencimento *</label>
                <input type="date" class="form-control campo-modificado" name="data_vencimento" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Data de Competência *</label>
                <input type="date" class="form-control campo-modificado" name="data_competencia" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="col-md-4 mb-3">
                <label class="form-label">Prioridade</label>
                <select class="form-select" name="prioridade">
                    <?php foreach (CONTAS_PRIORIDADES as $key => $config): ?>
                        <option value="<?php echo $key; ?>" 
                                <?php echo $conta_original['prioridade'] == $key ? 'selected' : ''; ?>>
                            <?php echo $config['label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Documento/Referência</label>
                <input type="text" class="form-control campo-modificado" name="documento" 
                       placeholder="Novo documento" maxlength="100">
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control" name="observacoes" rows="2"><?php echo htmlspecialchars($conta_original['observacoes'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <!-- Recorrência -->
        <div class="mb-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="recorrente" id="recorrente">
                <label class="form-check-label" for="recorrente">
                    <strong>Criar como conta recorrente</strong>
                </label>
            </div>
            
            <div id="recorrencia-config" style="display: none;" class="mt-3 p-3 bg-light rounded">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Periodicidade</label>
                        <select class="form-select" name="periodicidade">
                            <option value="">Selecione</option>
                            <?php foreach (CONTAS_PERIODICIDADES as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dia do Vencimento</label>
                        <input type="number" class="form-control" name="dia_vencimento" 
                               min="1" max="31" placeholder="Ex: 5">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botões de Ação -->
        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                <i class="fas fa-times me-2"></i>Cancelar
            </button>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-copy me-2"></i>Duplicar Conta
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara de valor
    const valorInput = document.querySelector('input[name="valor_original"]');
    valorInput.addEventListener('input', function() {
        let valor = this.value.replace(/\D/g, '');
        valor = valor.replace(/(\d)(\d{2})$/, '$1,$2');
        valor = valor.replace(/(?=(\d{3})+(\D))\B/g, '.');
        this.value = valor;
    });
    
    // Controle de recorrência
    const recorrenteCheck = document.getElementById('recorrente');
    const recorrenciaConfig = document.getElementById('recorrencia-config');
    
    recorrenteCheck.addEventListener('change', function() {
        recorrenciaConfig.style.display = this.checked ? 'block' : 'none';
    });
    
    // Auto-sync das datas
    const dataVencimento = document.querySelector('input[name="data_vencimento"]');
    const dataCompetencia = document.querySelector('input[name="data_competencia"]');
    
    dataVencimento.addEventListener('change', function() {
        if (!dataCompetencia.value || dataCompetencia.value === dataVencimento.defaultValue) {
            dataCompetencia.value = this.value;
        }
    });
});
</script>

<?php require_once '../footer.php'; ?>