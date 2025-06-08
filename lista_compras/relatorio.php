<?php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit();
}

$lista_id = (int)($_GET['id'] ?? 0);

if (!$lista_id) {
    header('Location: index.php');
    exit();
}

// Buscar dados da lista
$sql = "SELECT l.*
        FROM listas_compras l
        WHERE l.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$lista_id]);
$lista = $stmt->fetch();

if (!$lista) {
    $_SESSION['mensagem'] = 'Lista de compras não encontrada.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: index.php');
    exit();
}

// Buscar itens da lista
$sql_itens = "SELECT i.*,
                     fs.nome as fornecedor_sugerido_nome,
                     fe.nome as fornecedor_escolhido_nome
              FROM itens_lista_compras i
              LEFT JOIN fornecedores fs ON i.fornecedor_sugerido_id = fs.id
              LEFT JOIN fornecedores fe ON i.fornecedor_escolhido_id = fe.id
              WHERE i.lista_id = ?
              ORDER BY i.ordem, i.id";

$stmt_itens = $pdo->prepare($sql_itens);
$stmt_itens->execute([$lista_id]);
$itens = $stmt_itens->fetchAll();

// Buscar envios
$sql_envios = "SELECT e.*, f.nome as fornecedor_nome, f.cnpj, f.telefone, f.whatsapp, f.email
               FROM envios_lista_fornecedores e
               JOIN fornecedores f ON e.fornecedor_id = f.id
               WHERE e.lista_id = ?
               ORDER BY e.data_envio";

$stmt_envios = $pdo->prepare($sql_envios);
$stmt_envios->execute([$lista_id]);
$envios = $stmt_envios->fetchAll();

// Calcular totais
$total_itens = count($itens);
$valor_total_estimado = array_sum(array_map(fn($item) => $item['quantidade'] * $item['preco_estimado'], $itens));
$valor_total_final = array_sum(array_map(fn($item) => $item['quantidade'] * $item['preco_final'], $itens));

$itens_por_status = [
    'pendente' => 0,
    'cotado' => 0,
    'aprovado' => 0,
    'comprado' => 0
];

foreach ($itens as $item) {
    $itens_por_status[$item['status_item']]++;
}

// Detectar se é para imprimir
$imprimir = isset($_GET['print']) && $_GET['print'] == '1';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório - <?= htmlspecialchars($lista['nome']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --gradient-compras: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --cor-principal: #764ba2;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }

        .relatorio-header {
            background: var(--gradient-compras);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .relatorio-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .relatorio-header .content {
            position: relative;
            z-index: 1;
        }

        .company-info {
            background: #f8f9fa;
            border-left: 4px solid var(--cor-principal);
            padding: 1.5rem;
            border-radius: 0 8px 8px 0;
            margin-bottom: 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            border-top: 3px solid var(--cor-principal);
        }

        .info-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--cor-principal);
            margin-bottom: 0.5rem;
        }

        .info-card .label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .section-title {
            background: var(--cor-principal);
            color: white;
            padding: 0.75rem 1rem;
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 6px 6px 0 0;
        }

        .section-content {
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 6px 6px;
            padding: 0;
        }

        .table {
            margin-bottom: 0;
            font-size: 11px;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            border-top: none;
            padding: 0.5rem;
        }

        .table td {
            padding: 0.5rem;
            vertical-align: middle;
        }

        .status-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .status-pendente { background: #f8f9fa; color: #6c757d; }
        .status-cotado { background: #cce7ff; color: #0066cc; }
        .status-aprovado { background: #fff3cd; color: #856404; }
        .status-comprado { background: #d4edda; color: #155724; }

        .prioridade-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .prioridade-baixa { background: #e3f2fd; color: #1976d2; }
        .prioridade-media { background: #f3e5f5; color: #7b1fa2; }
        .prioridade-alta { background: #fff3e0; color: #f57c00; }
        .prioridade-urgente { background: #ffebee; color: #d32f2f; }

        .lista-status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .lista-status-rascunho { background: #f8f9fa; color: #6c757d; }
        .lista-status-enviada { background: #cce7ff; color: #0066cc; }
        .lista-status-em_cotacao { background: #fff3cd; color: #856404; }
        .lista-status-finalizada { background: #d4edda; color: #155724; }
        .lista-status-cancelada { background: #f8d7da; color: #721c24; }

        .rodape {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .total-row {
            background: #f8f9fa;
            font-weight: bold;
            border-top: 2px solid var(--cor-principal);
        }

        .no-print {
            margin-bottom: 2rem;
        }

        /* Estilos para impressão */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                font-size: 10px;
                line-height: 1.3;
            }
            
            .relatorio-header {
                background: var(--cor-principal) !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .section-title {
                background: var(--cor-principal) !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .status-badge,
            .prioridade-badge,
            .lista-status-badge {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .table {
                font-size: 9px;
            }
        }
    </style>
</head>
<body>
    <!-- Controles (não imprimir) -->
    <?php if (!$imprimir): ?>
        <div class="container-fluid no-print">
            <div class="d-flex justify-content-between align-items-center py-3">
                <div>
                    <a href="detalhes.php?id=<?= $lista['id'] ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar aos Detalhes
                    </a>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                    <a href="?id=<?= $lista['id'] ?>&print=1" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Versão para Impressão
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="container-fluid">
        <!-- Cabeçalho do Relatório -->
        <div class="relatorio-header">
            <div class="content">
                <h1 class="mb-3">
                    <i class="fas fa-clipboard-list me-3"></i>
                    RELATÓRIO DE LISTA DE COMPRAS
                </h1>
                <h2 class="mb-3"><?= htmlspecialchars($lista['nome']) ?></h2>
                <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap">
                    <span class="lista-status-badge lista-status-<?= $lista['status'] ?>">
                        <?= strtoupper($lista['status']) ?>
                    </span>
                    <span class="prioridade-badge prioridade-<?= $lista['prioridade'] ?>">
                        PRIORIDADE <?= strtoupper($lista['prioridade']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Informações da Empresa -->
        <div class="company-info">
            <div class="row">
                <div class="col-md-8">
                    <h5 class="mb-2">
                        <i class="fas fa-building me-2" style="color: var(--cor-principal);"></i>
                        DOMARIA CAFÉ
                    </h5>
                    <p class="mb-1"><strong>Sistema de Gestão de Compras</strong></p>
                    <p class="mb-0 text-muted">
                        Relatório gerado em <?= date('d/m/Y H:i:s') ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Criada em: <?= date('d/m/Y', strtotime($lista['data_criacao'])) ?><br>
                        <?php if ($lista['data_prazo']): ?>
                            <i class="fas fa-clock me-1"></i>
                            Prazo: <?= date('d/m/Y', strtotime($lista['data_prazo'])) ?><br>
                        <?php endif; ?>
                        <i class="fas fa-hashtag me-1"></i>
                        ID: <?= $lista['id'] ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumo Executivo -->
        <div class="info-grid">
            <div class="info-card">
                <div class="value"><?= $total_itens ?></div>
                <div class="label">Total de Itens</div>
            </div>
            <div class="info-card">
                <div class="value"><?= $itens_por_status['pendente'] ?></div>
                <div class="label">Pendentes</div>
            </div>
            <div class="info-card">
                <div class="value"><?= $itens_por_status['cotado'] ?></div>
                <div class="label">Cotados</div>
            </div>
            <div class="info-card">
                <div class="value"><?= $itens_por_status['comprado'] ?></div>
                <div class="label">Comprados</div>
            </div>
            <div class="info-card">
                <div class="value">R$ <?= number_format($valor_total_estimado, 2, ',', '.') ?></div>
                <div class="label">Valor Estimado</div>
            </div>
            <div class="info-card">
                <div class="value"><?= $total_itens > 0 ? round(($itens_por_status['comprado'] / $total_itens) * 100) : 0 ?>%</div>
                <div class="label">Progresso</div>
            </div>
        </div>

        <!-- Descrição da Lista -->
        <?php if ($lista['descricao']): ?>
            <div class="mb-4">
                <h5 class="section-title">DESCRIÇÃO</h5>
                <div class="section-content">
                    <div class="p-3">
                        <?= nl2br(htmlspecialchars($lista['descricao'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Itens da Lista -->
        <div class="mb-4">
            <h5 class="section-title">ITENS DA LISTA</h5>
            <div class="section-content">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 30%;">Produto/Descrição</th>
                                <th style="width: 10%;">Categoria</th>
                                <th style="width: 8%;">Qtd</th>
                                <th style="width: 5%;">Un</th>
                                <th style="width: 10%;">Preço Est.</th>
                                <th style="width: 10%;">Total Est.</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 12%;">Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $index => $item): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($item['produto_descricao']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($item['categoria'] ?: '-') ?></td>
                                    <td><?= number_format($item['quantidade'], 2, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($item['unidade']) ?></td>
                                    <td>R$ <?= number_format($item['preco_estimado'], 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($item['quantidade'] * $item['preco_estimado'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $item['status_item'] ?>">
                                            <?= ucfirst($item['status_item']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($item['observacoes'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Linha de Total -->
                            <tr class="total-row">
                                <td colspan="6" class="text-end"><strong>TOTAL GERAL:</strong></td>
                                <td><strong>R$ <?= number_format($valor_total_estimado, 2, ',', '.') ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Envios para Fornecedores -->
        <?php if (!empty($envios)): ?>
            <div class="mb-4">
                <h5 class="section-title">FORNECEDORES CONTATADOS</h5>
                <div class="section-content">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fornecedor</th>
                                    <th>CNPJ</th>
                                    <th>Contato</th>
                                    <th>Data Envio</th>
                                    <th>Meio</th>
                                    <th>Status</th>
                                    <th>Prazo</th>
                                    <th>Valor Cotação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($envios as $envio): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($envio['fornecedor_nome']) ?></strong></td>
                                        <td><?= htmlspecialchars($envio['cnpj'] ?: '-') ?></td>
                                        <td>
                                            <?php if ($envio['telefone']): ?>
                                                📞 <?= htmlspecialchars($envio['telefone']) ?><br>
                                            <?php endif; ?>
                                            <?php if ($envio['whatsapp']): ?>
                                                💬 <?= htmlspecialchars($envio['whatsapp']) ?><br>
                                            <?php endif; ?>
                                            <?php if ($envio['email']): ?>
                                                ✉️ <?= htmlspecialchars($envio['email']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($envio['data_envio'])) ?></td>
                                        <td>
                                            <?php
                                            $meios = [
                                                'whatsapp' => '💬 WhatsApp',
                                                'email' => '✉️ Email',
                                                'telefone' => '📞 Telefone',
                                                'presencial' => '👤 Presencial'
                                            ];
                                            echo $meios[$envio['meio_envio']] ?? $envio['meio_envio'];
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= str_replace('_', '-', $envio['status_resposta']) ?>">
                                                <?= ucwords(str_replace('_', ' ', $envio['status_resposta'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $envio['prazo_resposta'] ? date('d/m/Y', strtotime($envio['prazo_resposta'])) : '-' ?>
                                        </td>
                                        <td>
                                            <?= $envio['valor_cotacao'] > 0 ? 'R$ ' . number_format($envio['valor_cotacao'], 2, ',', '.') : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Observações Gerais -->
        <?php if ($lista['observacoes']): ?>
            <div class="mb-4">
                <h5 class="section-title">OBSERVAÇÕES GERAIS</h5>
                <div class="section-content">
                    <div class="p-3">
                        <?= nl2br(htmlspecialchars($lista['observacoes'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Rodapé -->
        <div class="rodape">
            <div class="row">
                <div class="col-md-6 text-start">
                    <strong>DOMARIA CAFÉ - Sistema de Gestão</strong><br>
                    Lista de Compras ID: <?= $lista['id'] ?>
                </div>
                <div class="col-md-6 text-end">
                    Relatório gerado em <?= date('d/m/Y H:i:s') ?><br>
                    Página 1 de 1
                </div>
            </div>
            <hr>
            <p class="mb-0">
                <small>
                    Este relatório contém informações confidenciais da empresa. 
                    Use apenas para fins autorizados.
                </small>
            </p>
        </div>
    </div>

    <script>
        // Auto-imprimir se estiver na versão de impressão
        <?php if ($imprimir): ?>
            window.onload = function() {
                setTimeout(() => {
                    window.print();
                }, 500);
            };
        <?php endif; ?>

        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>