<?php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit();
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Validar dados obrigatórios
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $prioridade = $_POST['prioridade'] ?? 'media';
        $data_prazo = $_POST['data_prazo'] ?? null;
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (empty($nome)) {
            throw new Exception('O nome da lista é obrigatório.');
        }
        
        if (empty($data_prazo)) {
            $data_prazo = null;
        }
        
        // Inserir lista
        $sql = "INSERT INTO listas_compras (nome, descricao, prioridade, data_prazo, observacoes, criado_por) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $descricao, $prioridade, $data_prazo, $observacoes, $_SESSION['usuario_id']]);
        
        $lista_id = $pdo->lastInsertId();
        
        // Processar itens se foram enviados
        if (!empty($_POST['itens'])) {
            $sql_item = "INSERT INTO itens_lista_compras 
                         (lista_id, produto_descricao, categoria, quantidade, unidade, preco_estimado, observacoes, ordem) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_item = $pdo->prepare($sql_item);
            
            foreach ($_POST['itens'] as $index => $item) {
                if (!empty($item['produto_descricao'])) {
                    $stmt_item->execute([
                        $lista_id,
                        trim($item['produto_descricao']),
                        trim($item['categoria'] ?? ''),
                        floatval($item['quantidade'] ?? 1),
                        trim($item['unidade'] ?? 'un'),
                        floatval($item['preco_estimado'] ?? 0),
                        trim($item['observacoes'] ?? ''),
                        $index + 1
                    ]);
                }
            }
        }
        
        // Registrar no histórico
        $sql_historico = "INSERT INTO historico_listas_compras (lista_id, acao, descricao, usuario_id) 
                          VALUES (?, 'criada', 'Lista de compras criada', ?)";
        $stmt_historico = $pdo->prepare($sql_historico);
        $stmt_historico->execute([$lista_id, $_SESSION['usuario_id']]);
        
        $pdo->commit();
        
        $_SESSION['mensagem'] = 'Lista de compras criada com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
        
        header('Location: detalhes.php?id=' . $lista_id);
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = $e->getMessage();
    }
}

// Buscar categorias mais usadas para sugestões
$sql_categorias = "SELECT categoria, COUNT(*) as uso 
                   FROM itens_lista_compras 
                   WHERE categoria IS NOT NULL AND categoria != '' 
                   GROUP BY categoria 
                   ORDER BY uso DESC 
                   LIMIT 10";
$stmt_categorias = $pdo->query($sql_categorias);
$categorias_sugeridas = $stmt_categorias->fetchAll();

// Buscar produtos mais usados para sugestões
$sql_produtos = "SELECT produto_descricao, categoria, unidade, AVG(preco_estimado) as preco_medio, COUNT(*) as uso
                 FROM itens_lista_compras 
                 GROUP BY produto_descricao, categoria, unidade
                 ORDER BY uso DESC 
                 LIMIT 20";
$stmt_produtos = $pdo->query($sql_produtos);
$produtos_sugeridos = $stmt_produtos->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Lista de Compras - Sistema Domaria Café</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --gradient-compras: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --shadow-soft: 0 2px 15px rgba(0,0,0,0.1);
            --shadow-hover: 0 5px 25px rgba(0,0,0,0.15);
        }

        .header-compras {
            background: var(--gradient-compras);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--shadow-soft);
            margin-bottom: 2rem;
        }

        .form-card .card-header {
            background: var(--gradient-compras);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }

        .item-row {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .item-row:hover {
            background: #e3f2fd;
            border-color: #2196F3;
        }

        .item-row.new-item {
            animation: slideInUp 0.5s ease forwards;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-remove-item {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: #dc3545;
            color: white;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .item-row:hover .btn-remove-item {
            opacity: 1;
        }

        .btn-add-item {
            background: var(--gradient-success);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-add-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            color: white;
        }

        .btn-salvar {
            background: var(--gradient-compras);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-salvar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            color: white;
        }

        .suggestions-container {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            position: absolute;
            width: 100%;
            z-index: 1000;
            display: none;
        }

        .suggestion-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: background 0.2s ease;
        }

        .suggestion-item:hover {
            background: #f8f9fa;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .priority-badge.baixa { background: #e3f2fd; color: #1976d2; }
        .priority-badge.media { background: #f3e5f5; color: #7b1fa2; }
        .priority-badge.alta { background: #fff3e0; color: #f57c00; }
        .priority-badge.urgente { background: #ffebee; color: #d32f2f; }

        .priority-badge.active {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .counter {
            font-size: 0.9rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.25rem;
        }

        .floating-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            z-index: 1000;
        }

        .floating-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            color: white;
        }

        .floating-btn.btn-primary {
            background: var(--gradient-compras);
        }

        .floating-btn.btn-success {
            background: var(--gradient-success);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Header da Seção -->
    <div class="header-compras">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-plus-circle me-3"></i>
                        Nova Lista de Compras
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">
                        Crie uma nova lista para organizar suas compras
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>
                        Voltar às Listas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($erro) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="formLista">
            <!-- Informações Básicas -->
            <div class="card form-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informações Básicas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       placeholder="Nome da lista" required maxlength="200"
                                       value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                                <label for="nome">Nome da Lista *</label>
                                <div class="counter">
                                    <span id="nome-counter">0</span>/200 caracteres
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="data_prazo" name="data_prazo"
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= htmlspecialchars($_POST['data_prazo'] ?? '') ?>">
                                <label for="data_prazo">Data Limite</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="descricao" name="descricao" 
                                          placeholder="Descrição da lista" style="height: 100px" maxlength="500"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
                                <label for="descricao">Descrição</label>
                                <div class="counter">
                                    <span id="descricao-counter">0</span>/500 caracteres
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prioridade *</label>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="priority-badge baixa <?= ($_POST['prioridade'] ?? 'media') == 'baixa' ? 'active' : '' ?>" 
                                      data-priority="baixa">Baixa</span>
                                <span class="priority-badge media <?= ($_POST['prioridade'] ?? 'media') == 'media' ? 'active' : '' ?>" 
                                      data-priority="media">Média</span>
                                <span class="priority-badge alta <?= ($_POST['prioridade'] ?? 'media') == 'alta' ? 'active' : '' ?>" 
                                      data-priority="alta">Alta</span>
                                <span class="priority-badge urgente <?= ($_POST['prioridade'] ?? 'media') == 'urgente' ? 'active' : '' ?>" 
                                      data-priority="urgente">Urgente</span>
                            </div>
                            <input type="hidden" id="prioridade" name="prioridade" value="<?= htmlspecialchars($_POST['prioridade'] ?? 'media') ?>">
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="observacoes" name="observacoes" 
                                  placeholder="Observações gerais" style="height: 80px" maxlength="1000"><?= htmlspecialchars($_POST['observacoes'] ?? '') ?></textarea>
                        <label for="observacoes">Observações Gerais</label>
                        <div class="counter">
                            <span id="observacoes-counter">0</span>/1000 caracteres
                        </div>
                    </div>
                </div>
            </div>

            <!-- Itens da Lista -->
            <div class="card form-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Itens da Lista
                    </h5>
                    <span class="badge bg-light text-dark">
                        <span id="total-itens">0</span> itens
                    </span>
                </div>
                <div class="card-body">
                    <div id="itens-container">
                        <!-- Os itens serão adicionados aqui via JavaScript -->
                    </div>

                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-add-item" onclick="adicionarItem()">
                            <i class="fas fa-plus me-2"></i>
                            Adicionar Item
                        </button>
                    </div>

                    <!-- Sugestões de Produtos -->
                    <?php if (!empty($produtos_sugeridos)): ?>
                        <div class="mt-4">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-lightbulb me-2"></i>
                                Produtos Frequentes
                            </h6>
                            <div class="row">
                                <?php foreach (array_slice($produtos_sugeridos, 0, 8) as $produto): ?>
                                    <div class="col-md-3 col-sm-6 mb-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start produto-sugerido"
                                                data-produto="<?= htmlspecialchars($produto['produto_descricao']) ?>"
                                                data-categoria="<?= htmlspecialchars($produto['categoria']) ?>"
                                                data-unidade="<?= htmlspecialchars($produto['unidade']) ?>"
                                                data-preco="<?= number_format($produto['preco_medio'], 2, '.', '') ?>">
                                            <i class="fas fa-plus-circle me-1"></i>
                                            <?= htmlspecialchars($produto['produto_descricao']) ?>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="row">
                <div class="col-md-6">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>
                        Cancelar
                    </a>
                </div>
                <div class="col-md-6 text-end">
                    <button type="submit" class="btn btn-salvar">
                        <i class="fas fa-save me-2"></i>
                        Salvar Lista
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Ações Flutuantes -->
    <div class="floating-actions">
        <button type="button" class="floating-btn btn-success" onclick="adicionarItem()" title="Adicionar Item">
            <i class="fas fa-plus"></i>
        </button>
        <button type="button" class="floating-btn btn-primary" onclick="document.getElementById('formLista').submit()" title="Salvar Lista">
            <i class="fas fa-save"></i>
        </button>
    </div>

    <!-- Template para Item -->
    <template id="item-template">
        <div class="item-row" data-index="">
            <button type="button" class="btn-remove-item" onclick="removerItem(this)">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-floating mb-3 position-relative">
                        <input type="text" class="form-control produto-input" name="itens[][produto_descricao]" 
                               placeholder="Descrição do produto" required>
                        <label>Produto/Descrição *</label>
                        <div class="suggestions-container"></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control categoria-input" name="itens[][categoria]" 
                               placeholder="Categoria" list="categorias-list">
                        <label>Categoria</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="row">
                        <div class="col-8">
                            <div class="form-floating mb-3">
                                <input type="number" class="form-control" name="itens[][quantidade]" 
                                       placeholder="Qtd" min="0.01" step="0.01" value="1">
                                <label>Qtd</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-floating mb-3">
                                <select class="form-select" name="itens[][unidade]">
                                    <option value="un">un</option>
                                    <option value="kg">kg</option>
                                    <option value="g">g</option>
                                    <option value="L">L</option>
                                    <option value="ml">ml</option>
                                    <option value="pc">pc</option>
                                    <option value="cx">cx</option>
                                    <option value="pct">pct</option>
                                    <option value="m">m</option>
                                    <option value="cm">cm</option>
                                </select>
                                <label>Un</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control preco-input" name="itens[][preco_estimado]" 
                               placeholder="Preço" min="0" step="0.01">
                        <label>Preço Est.</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control total-input" readonly 
                               placeholder="Total" style="background-color: #f8f9fa;">
                        <label>Total</label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="form-floating">
                        <textarea class="form-control" name="itens[][observacoes]" 
                                  placeholder="Observações do item" style="height: 60px"></textarea>
                        <label>Observações do Item</label>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Datalist para categorias -->
    <datalist id="categorias-list">
        <?php foreach ($categorias_sugeridas as $categoria): ?>
            <option value="<?= htmlspecialchars($categoria['categoria']) ?>">
        <?php endforeach; ?>
    </datalist>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let itemCounter = 0;
        const produtosSugeridos = <?= json_encode($produtos_sugeridos) ?>;

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            adicionarItem(); // Adicionar um item inicial
            atualizarContadores();
            configurarPrioridade();
        });

        // Configurar seleção de prioridade
        function configurarPrioridade() {
            document.querySelectorAll('.priority-badge').forEach(badge => {
                badge.addEventListener('click', function() {
                    // Remover active de todos
                    document.querySelectorAll('.priority-badge').forEach(b => b.classList.remove('active'));
                    // Adicionar active no clicado
                    this.classList.add('active');
                    // Atualizar input hidden
                    document.getElementById('prioridade').value = this.dataset.priority;
                });
            });
        }

        // Adicionar novo item
        function adicionarItem(dadosProduto = null) {
            const template = document.getElementById('item-template');
            const clone = template.content.cloneNode(true);
            const itemRow = clone.querySelector('.item-row');
            
            itemRow.dataset.index = itemCounter;
            itemRow.classList.add('new-item');
            
            // Preencher dados se fornecidos
            if (dadosProduto) {
                const inputs = itemRow.querySelectorAll('input, select');
                inputs[0].value = dadosProduto.produto || '';
                inputs[1].value = dadosProduto.categoria || '';
                inputs[2].value = dadosProduto.quantidade || 1;
                inputs[3].value = dadosProduto.unidade || 'un';
                inputs[4].value = dadosProduto.preco || '';
            }
            
            document.getElementById('itens-container').appendChild(clone);
            
            // Configurar eventos do novo item
            const novoItem = document.querySelector(`[data-index="${itemCounter}"]`);
            configurarEventosItem(novoItem);
            
            itemCounter++;
            atualizarTotalItens();
        }

        // Configurar eventos de um item
        function configurarEventosItem(itemRow) {
            const produtoInput = itemRow.querySelector('.produto-input');
            const categoriaInput = itemRow.querySelector('.categoria-input');
            const quantidadeInput = itemRow.querySelector('input[name*="quantidade"]');
            const precoInput = itemRow.querySelector('.preco-input');
            const totalInput = itemRow.querySelector('.total-input');
            
            // Autocomplete para produtos
            produtoInput.addEventListener('input', function() {
                mostrarSugestoesProdutos(this);
            });
            
            // Calcular total automaticamente
            function calcularTotal() {
                const quantidade = parseFloat(quantidadeInput.value) || 0;
                const preco = parseFloat(precoInput.value) || 0;
                const total = quantidade * preco;
                totalInput.value = total > 0 ? 'R$ ' + total.toFixed(2).replace('.', ',') : '';
            }
            
            quantidadeInput.addEventListener('input', calcularTotal);
            precoInput.addEventListener('input', calcularTotal);
        }

        // Mostrar sugestões de produtos
        function mostrarSugestoesProdutos(input) {
            const container = input.parentNode.querySelector('.suggestions-container');
            const valor = input.value.toLowerCase();
            
            if (valor.length < 2) {
                container.style.display = 'none';
                return;
            }
            
            const sugestoes = produtosSugeridos.filter(p => 
                p.produto_descricao.toLowerCase().includes(valor)
            ).slice(0, 5);
            
            if (sugestoes.length === 0) {
                container.style.display = 'none';
                return;
            }
            
            container.innerHTML = sugestoes.map(produto => `
                <div class="suggestion-item" onclick="selecionarProduto(this, '${produto.produto_descricao}', '${produto.categoria}', '${produto.unidade}', '${produto.preco_medio}')">
                    <strong>${produto.produto_descricao}</strong><br>
                    <small class="text-muted">${produto.categoria} - ${produto.unidade} - R$ ${parseFloat(produto.preco_medio).toFixed(2).replace('.', ',')}</small>
                </div>
            `).join('');
            
            container.style.display = 'block';
        }

        // Selecionar produto sugerido
        function selecionarProduto(element, produto, categoria, unidade, preco) {
            const itemRow = element.closest('.item-row');
            const inputs = itemRow.querySelectorAll('input, select');
            
            inputs[0].value = produto; // produto_descricao
            inputs[1].value = categoria; // categoria
            inputs[3].value = unidade; // unidade
            inputs[4].value = parseFloat(preco).toFixed(2); // preco_estimado
            
            // Calcular total
            const quantidade = parseFloat(inputs[2].value) || 1;
            const precoVal = parseFloat(preco) || 0;
            const total = quantidade * precoVal;
            inputs[5].value = total > 0 ? 'R$ ' + total.toFixed(2).replace('.', ',') : '';
            
            element.closest('.suggestions-container').style.display = 'none';
        }

        // Remover item
        function removerItem(btn) {
            btn.closest('.item-row').remove();
            atualizarTotalItens();
        }

        // Atualizar contador de itens
        function atualizarTotalItens() {
            const total = document.querySelectorAll('.item-row').length;
            document.getElementById('total-itens').textContent = total;
        }

        // Atualizar contadores de caracteres
        function atualizarContadores() {
            const campos = ['nome', 'descricao', 'observacoes'];
            
            campos.forEach(campo => {
                const input = document.getElementById(campo);
                const counter = document.getElementById(campo + '-counter');
                
                if (input && counter) {
                    function atualizar() {
                        counter.textContent = input.value.length;
                    }
                    
                    input.addEventListener('input', atualizar);
                    atualizar(); // Inicializar
                }
            });
        }

        // Adicionar produto sugerido
        document.querySelectorAll('.produto-sugerido').forEach(btn => {
            btn.addEventListener('click', function() {
                const dados = {
                    produto: this.dataset.produto,
                    categoria: this.dataset.categoria,
                    unidade: this.dataset.unidade,
                    preco: this.dataset.preco
                };
                adicionarItem(dados);
            });
        });

        // Ocultar sugestões ao clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.form-floating')) {
                document.querySelectorAll('.suggestions-container').forEach(container => {
                    container.style.display = 'none';
                });
            }
        });

        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('formLista').submit();
            }
            
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                adicionarItem();
            }
        });

        // Validação antes do envio
        document.getElementById('formLista').addEventListener('submit', function(e) {
            const itens = document.querySelectorAll('.item-row');
            let temItens = false;
            
            itens.forEach(item => {
                const produtoInput = item.querySelector('.produto-input');
                if (produtoInput.value.trim()) {
                    temItens = true;
                }
            });
            
            if (!temItens) {
                e.preventDefault();
                alert('Adicione pelo menos um item à lista antes de salvar.');
                return false;
            }
        });
    </script>
</body>
</html>