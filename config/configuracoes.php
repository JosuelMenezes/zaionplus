<?php
// config/configuracoes.php
session_start();
require_once '../config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php?msg=Acesso negado. Faça login primeiro.&type=danger");
    exit;
}

// Verificar se o usuário tem permissão de administrador
if ($_SESSION['nivel_acesso'] != 'admin') {
    header("Location: ../dashboard.php?msg=Acesso negado. Apenas administradores podem acessar as configurações.&type=danger");
    exit;
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Processar upload de logo se houver
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] == 0) {
        $upload_dir = '../uploads/';
        
        // Criar diretório se não existir
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Validar tipo de arquivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['logo_upload']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['msg'] = "Tipo de arquivo não permitido. Use apenas imagens (JPG, PNG, GIF, WEBP).";
            $_SESSION['msg_type'] = "danger";
            header("Location: configuracoes.php");
            exit;
        }
        
        // Limitar tamanho do arquivo (2MB)
        if ($_FILES['logo_upload']['size'] > 2 * 1024 * 1024) {
            $_SESSION['msg'] = "O arquivo é muito grande. O tamanho máximo permitido é 2MB.";
            $_SESSION['msg_type'] = "danger";
            header("Location: configuracoes.php");
            exit;
        }
        
        $file_name = 'logo_' . time() . '_' . basename($_FILES['logo_upload']['name']);
        $upload_file = $upload_dir . $file_name;
        
        // Mover o arquivo para o diretório de uploads
        if (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $upload_file)) {
            // Atualizar a URL da logo no banco de dados
            $logo_url = 'uploads/' . $file_name;
            $stmt = $conn->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'logo_url'");
            $stmt->bind_param("s", $logo_url);
            $stmt->execute();
            
            // Registrar log
            if (function_exists('registrar_log')) {
                registrar_log('Atualização de Logo', 'Logo da empresa atualizada: ' . $logo_url);
            }
        } else {
            $_SESSION['msg'] = "Erro ao fazer upload da imagem. Verifique as permissões do diretório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: configuracoes.php");
            exit;
        }
    }
    
    // Atualizar outras configurações
    $updated_configs = [];
    
    foreach ($_POST as $chave => $valor) {
        if ($chave != 'logo_upload' && strpos($chave, 'config_') === 0) {
            $config_key = substr($chave, 7); // Remover o prefixo 'config_'
            
            // Validar e sanitizar o valor
            $sanitized_value = trim($valor);
            
            // Validações específicas para cada tipo de configuração
            if ($config_key == 'email_empresa' && !empty($sanitized_value) && !filter_var($sanitized_value, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['msg'] = "O email informado não é válido.";
                $_SESSION['msg_type'] = "danger";
                header("Location: configuracoes.php");
                exit;
            }
            
            // Atualizar a configuração
            $stmt = $conn->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
            $stmt->bind_param("ss", $sanitized_value, $config_key);
            if ($stmt->execute()) {
                $updated_configs[] = $config_key;
            }
        }
    }
    
    // Limpar o cache de configurações se a função existir
    if (function_exists('clear_config_cache')) {
        clear_config_cache();
    }
    
    // Registrar log se a função existir
    if (function_exists('registrar_log') && !empty($updated_configs)) {
        registrar_log('Atualização de Configurações', 'Configurações atualizadas: ' . implode(', ', $updated_configs));
    }
    
    $_SESSION['msg'] = "Configurações atualizadas com sucesso!";
    $_SESSION['msg_type'] = "success";
    header("Location: configuracoes.php");
    exit;
}

// Buscar todas as configurações
$sql = "SELECT * FROM configuracoes ORDER BY id";
$result = $conn->query($sql);
$configuracoes = [];

while ($row = $result->fetch_assoc()) {
    $configuracoes[$row['chave']] = $row;
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Conteúdo Principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-cog"></i> Configurações do Sistema</h1>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="empresa-tab" data-bs-toggle="tab" data-bs-target="#empresa" type="button" role="tab" aria-controls="empresa" aria-selected="true">
                                    <i class="fas fa-building"></i> Empresa
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="aparencia-tab" data-bs-toggle="tab" data-bs-target="#aparencia" type="button" role="tab" aria-controls="aparencia" aria-selected="false">
                                    <i class="fas fa-palette"></i> Aparência
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="comprovantes-tab" data-bs-toggle="tab" data-bs-target="#comprovantes" type="button" role="tab" aria-controls="comprovantes" aria-selected="false">
                                    <i class="fas fa-file-invoice"></i> Comprovantes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sistema-tab" data-bs-toggle="tab" data-bs-target="#sistema" type="button" role="tab" aria-controls="sistema" aria-selected="false">
                                    <i class="fas fa-sliders-h"></i> Sistema
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="configTabsContent">
                            <!-- Aba Empresa -->
                            <div class="tab-pane fade show active" id="empresa" role="tabpanel" aria-labelledby="empresa-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="config_nome_empresa" class="form-label">Nome da Empresa</label>
                                            <input type="text" class="form-control" id="config_nome_empresa" name="config_nome_empresa" value="<?php echo htmlspecialchars($configuracoes['nome_empresa']['valor'] ?? 'Zaion GC'); ?>" required>
                                            <div class="form-text"><?php echo $configuracoes['nome_empresa']['descricao'] ?? 'Nome da empresa exibido no sistema e comprovantes'; ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="config_telefone_empresa" class="form-label">Telefone</label>
                                            <input type="text" class="form-control" id="config_telefone_empresa" name="config_telefone_empresa" value="<?php echo htmlspecialchars($configuracoes['telefone_empresa']['valor'] ?? ''); ?>">
                                            <div class="form-text"><?php echo $configuracoes['telefone_empresa']['descricao'] ?? 'Telefone de contato da empresa'; ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="config_email_empresa" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="config_email_empresa" name="config_email_empresa" value="<?php echo htmlspecialchars($configuracoes['email_empresa']['valor'] ?? ''); ?>">
                                            <div class="form-text"><?php echo $configuracoes['email_empresa']['descricao'] ?? 'Email de contato da empresa'; ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="config_endereco_empresa" class="form-label">Endereço</label>
                                            <textarea class="form-control" id="config_endereco_empresa" name="config_endereco_empresa" rows="3"><?php echo htmlspecialchars($configuracoes['endereco_empresa']['valor'] ?? ''); ?></textarea>
                                            <div class="form-text"><?php echo $configuracoes['endereco_empresa']['descricao'] ?? 'Endereço da empresa'; ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="config_cnpj_empresa" class="form-label">CNPJ</label>
                                            <input type="text" class="form-control" id="config_cnpj_empresa" name="config_cnpj_empresa" value="<?php echo htmlspecialchars($configuracoes['cnpj_empresa']['valor'] ?? ''); ?>">
                                            <div class="form-text">CNPJ da empresa (opcional)</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Logomarca</label>
                                            <div class="card mb-3">
                                                <div class="card-body text-center">
                                                    <?php if (!empty($configuracoes['logo_url']['valor'])): ?>
                                                        <img src="../<?php echo $configuracoes['logo_url']['valor']; ?>" alt="Logo" class="img-fluid mb-3" style="max-height: 150px;">
                                                        <div class="mt-2">
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('Tem certeza que deseja remover a logo?')) { document.getElementById('config_logo_url').value = ''; this.form.submit(); }">
                                                                <i class="fas fa-trash"></i> Remover Logo
                                                            </button>
                                                            <input type="hidden" id="config_logo_url" name="config_logo_url" value="<?php echo htmlspecialchars($configuracoes['logo_url']['valor']); ?>">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-info">
                                                            Nenhuma logomarca definida
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="logo_upload" name="logo_upload" accept="image/*">
                                                <label class="input-group-text" for="logo_upload">Upload</label>
                                            </div>
                                            <div class="form-text">Formatos aceitos: JPG, PNG, GIF, WEBP. Tamanho máximo: 2MB</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="config_site_empresa" class="form-label">Website</label>
                                            <input type="url" class="form-control" id="config_site_empresa" name="config_site_empresa" value="<?php echo htmlspecialchars($configuracoes['site_empresa']['valor'] ?? ''); ?>">
                                            <div class="form-text">Website da empresa (opcional)</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="config_whatsapp_empresa" class="form-label">WhatsApp</label>
                                            <input type="text" class="form-control" id="config_whatsapp_empresa" name="config_whatsapp_empresa" value="<?php echo htmlspecialchars($configuracoes['whatsapp_empresa']['valor'] ?? ''); ?>">
                                            <div class="form-text">Número de WhatsApp para contato (com DDD)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aba Aparência -->
                            <div class="tab-pane fade" id="aparencia" role="tabpanel" aria-labelledby="aparencia-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="config_cor_primaria" class="form-label">Cor Primária</label>
                                            <input type="color" class="form-control form-control-color" id="config_cor_primaria" name="config_cor_primaria" value="<?php echo htmlspecialchars($configuracoes['cor_primaria']['valor'] ?? '#343a40'); ?>">
                                            <div class="form-text"><?php echo $configuracoes['cor_primaria']['descricao'] ?? 'Cor primária do sistema'; ?></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="config_cor_secundaria" class="form-label">Cor Secundária</label>
                                            <input type="color" class="form-control form-control-color" id="config_cor_secundaria" name="config_cor_secundaria" value="<?php echo htmlspecialchars($configuracoes['cor_secundaria']['valor'] ?? '#6c757d'); ?>">
                                            <div class="form-text"><?php echo $configuracoes['cor_secundaria']['descricao'] ?? 'Cor secundária do sistema'; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="config_cor_botoes" class="form-label">Cor dos Botões</label>
                                            <input type="color" class="form-control form-control-color" id="config_cor_botoes" name="config_cor_botoes" value="<?php echo htmlspecialchars($configuracoes['cor_botoes']['valor'] ?? '#0d6efd'); ?>">
                                            <div class="form-text">Cor dos botões principais</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="config_cor_texto" class="form-label">Cor do Texto</label>
                                            <input type="color" class="form-control form-control-color" id="config_cor_texto" name="config_cor_texto" value="<?php echo htmlspecialchars($configuracoes['cor_texto']['valor'] ?? '#212529'); ?>">
                                            <div class="form-text">Cor do texto principal</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_tema" class="form-label">Tema</label>
                                    <select class="form-select" id="config_tema" name="config_tema">
                                        <option value="claro" <?php echo ($configuracoes['tema']['valor'] ?? 'claro') == 'claro' ? 'selected' : ''; ?>>Claro</option>
                                        <option value="escuro" <?php echo ($configuracoes['tema']['valor'] ?? 'claro') == 'escuro' ? 'selected' : ''; ?>>Escuro</option>
                                        <option value="sistema" <?php echo ($configuracoes['tema']['valor'] ?? 'claro') == 'sistema' ? 'selected' : ''; ?>>Seguir sistema</option>
                                    </select>
                                    <div class="form-text">Tema geral do sistema</div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> As alterações de cores e tema serão aplicadas após reiniciar a sessão.
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Prévia das Cores</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <div class="p-3 rounded" id="preview_cor_primaria">Cor Primária</div>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <div class="p-3 rounded" id="preview_cor_secundaria">Cor Secundária</div>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <div class="p-3 rounded" id="preview_cor_botoes">Cor dos Botões</div>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <div class="p-3 rounded" id="preview_cor_texto">Cor do Texto</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aba Comprovantes -->
                            <div class="tab-pane fade" id="comprovantes" role="tabpanel" aria-labelledby="comprovantes-tab">
                                <div class="mb-3">
                                    <label for="config_mensagem_comprovante" class="form-label">Mensagem de Rodapé do Comprovante</label>
                                    <textarea class="form-control" id="config_mensagem_comprovante" name="config_mensagem_comprovante" rows="4"><?php echo htmlspecialchars($configuracoes['mensagem_comprovante']['valor'] ?? "Agradecemos pela preferência!\nPara mais informações, entre em contato conosco."); ?></textarea>
                                    <div class="form-text"><?php echo $configuracoes['mensagem_comprovante']['descricao'] ?? 'Mensagem exibida no final dos comprovantes'; ?></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_formato_comprovante" class="form-label">Formato do Comprovante</label>
                                    <select class="form-select" id="config_formato_comprovante" name="config_formato_comprovante">
                                        <option value="simples" <?php echo ($configuracoes['formato_comprovante']['valor'] ?? 'simples') == 'simples' ? 'selected' : ''; ?>>Simples</option>
                                        <option value="detalhado" <?php echo ($configuracoes['formato_comprovante']['valor'] ?? 'simples') == 'detalhado' ? 'selected' : ''; ?>>Detalhado</option>
                                    </select>
                                    <div class="form-text">Formato do comprovante enviado aos clientes</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_incluir_logo_comprovante" class="form-label">Incluir Logo no Comprovante</label>
                                    <select class="form-select" id="config_incluir_logo_comprovante" name="config_incluir_logo_comprovante">
                                        <option value="sim" <?php echo ($configuracoes['incluir_logo_comprovante']['valor'] ?? 'sim') == 'sim' ? 'selected' : ''; ?>>Sim</option>
                                        <option value="nao" <?php echo ($configuracoes['incluir_logo_comprovante']['valor'] ?? 'sim') == 'nao' ? 'selected' : ''; ?>>Não</option>
                                    </select>
                                    <div class="form-text">Incluir a logomarca da empresa no comprovante</div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Prévia do Comprovante</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="border p-3 bg-light">
                                            <?php if (($configuracoes['incluir_logo_comprovante']['valor'] ?? 'sim') == 'sim' && !empty($configuracoes['logo_url']['valor'])): ?>
                                                <div class="text-center mb-3">
                                                    <img src="../<?php echo $configuracoes['logo_url']['valor']; ?>" alt="Logo" class="img-fluid" style="max-height: 80px;">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <p>Olá <strong>Nome do Cliente</strong>!</p>
                                            <p>Segue o comprovante da sua compra na <strong><?php echo htmlspecialchars($configuracoes['nome_empresa']['valor'] ?? 'Zaion GC'); ?></strong>:</p>
                                            <p><strong>COMPROVANTE DE VENDA #123</strong></p>
                                            <p>Data: <?php echo date('d/m/Y'); ?></p>
                                            <p><strong>ITENS:</strong></p>
                                            <p>- Produto Exemplo<br>
                                               Qtd: 2 x R$ 50,00<br>
                                               Subtotal: R$ 100,00</p>
                                            <p><strong>TOTAL: R$ 100,00</strong></p>
                                            <p id="preview_mensagem_comprovante"><?php echo nl2br(htmlspecialchars($configuracoes['mensagem_comprovante']['valor'] ?? "Agradecemos pela preferência!\nPara mais informações, entre em contato conosco.")); ?></p>
                                            
                                            <?php if (!empty($configuracoes['telefone_empresa']['valor'])): ?>
                                                <p>Telefone: <?php echo htmlspecialchars($configuracoes['telefone_empresa']['valor']); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($configuracoes['email_empresa']['valor'])): ?>
                                                <p>Email: <?php echo htmlspecialchars($configuracoes['email_empresa']['valor']); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($configuracoes['site_empresa']['valor'])): ?>
                                                <p>Site: <?php echo htmlspecialchars($configuracoes['site_empresa']['valor']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aba Sistema -->
                            <div class="tab-pane fade" id="sistema" role="tabpanel" aria-labelledby="sistema-tab">
                                <div class="mb-3">
                                    <label for="config_itens_por_pagina" class="form-label">Itens por Página</label>
                                    <select class="form-select" id="config_itens_por_pagina" name="config_itens_por_pagina">
                                        <option value="10" <?php echo ($configuracoes['itens_por_pagina']['valor'] ?? '20') == '10' ? 'selected' : ''; ?>>10</option>
                                        <option value="20" <?php echo ($configuracoes['itens_por_pagina']['valor'] ?? '20') == '20' ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo ($configuracoes['itens_por_pagina']['valor'] ?? '20') == '50' ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo ($configuracoes['itens_por_pagina']['valor'] ?? '20') == '100' ? 'selected' : ''; ?>>100</option>
                                    </select>
                                    <div class="form-text">Número de itens exibidos por página nas listagens</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_moeda" class="form-label">Moeda</label>
                                    <select class="form-select" id="config_moeda" name="config_moeda">
                                        <option value="BRL" <?php echo ($configuracoes['moeda']['valor'] ?? 'BRL') == 'BRL' ? 'selected' : ''; ?>>Real (R$)</option>
                                        <option value="USD" <?php echo ($configuracoes['moeda']['valor'] ?? 'BRL') == 'USD' ? 'selected' : ''; ?>>Dólar (US$)</option>
                                        <option value="EUR" <?php echo ($configuracoes['moeda']['valor'] ?? 'BRL') == 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                    </select>
                                    <div class="form-text">Moeda utilizada no sistema</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_formato_data" class="form-label">Formato de Data</label>
                                    <select class="form-select" id="config_formato_data" name="config_formato_data">
                                        <option value="d/m/Y" <?php echo ($configuracoes['formato_data']['valor'] ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/AAAA</option>
                                        <option value="m/d/Y" <?php echo ($configuracoes['formato_data']['valor'] ?? 'd/m/Y') == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/AAAA</option>
                                        <option value="Y-m-d" <?php echo ($configuracoes['formato_data']['valor'] ?? 'd/m/Y') == 'Y-m-d' ? 'selected' : ''; ?>>AAAA-MM-DD</option>
                                    </select>
                                    <div class="form-text">Formato de exibição das datas</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_timezone" class="form-label">Fuso Horário</label>
                                    <select class="form-select" id="config_timezone" name="config_timezone">
                                        <option value="America/Sao_Paulo" <?php echo ($configuracoes['timezone']['valor'] ?? 'America/Sao_Paulo') == 'America/Sao_Paulo' ? 'selected' : ''; ?>>Brasília (GMT-3)</option>
                                        <option value="America/Manaus" <?php echo ($configuracoes['timezone']['valor'] ?? 'America/Sao_Paulo') == 'America/Manaus' ? 'selected' : ''; ?>>Manaus (GMT-4)</option>
                                        <option value="America/Belem" <?php echo ($configuracoes['timezone']['valor'] ?? 'America/Sao_Paulo') == 'America/Belem' ? 'selected' : ''; ?>>Belém (GMT-3)</option>
                                        <option value="America/Bahia" <?php echo ($configuracoes['timezone']['valor'] ?? 'America/Sao_Paulo') == 'America/Bahia' ? 'selected' : ''; ?>>Salvador (GMT-3)</option>
                                    </select>
                                    <div class="form-text">Fuso horário do sistema</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_backup_automatico" class="form-label">Backup Automático</label>
                                    <select class="form-select" id="config_backup_automatico" name="config_backup_automatico">
                                        <option value="nao" <?php echo ($configuracoes['backup_automatico']['valor'] ?? 'nao') == 'nao' ? 'selected' : ''; ?>>Desativado</option>
                                        <option value="diario" <?php echo ($configuracoes['backup_automatico']['valor'] ?? 'nao') == 'diario' ? 'selected' : ''; ?>>Diário</option>
                                        <option value="semanal" <?php echo ($configuracoes['backup_automatico']['valor'] ?? 'nao') == 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                                        <option value="mensal" <?php echo ($configuracoes['backup_automatico']['valor'] ?? 'nao') == 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                                    </select>
                                    <div class="form-text">Frequência de backup automático do banco de dados</div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Algumas configurações do sistema podem exigir reinicialização da sessão para serem aplicadas.
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_versao_sistema" class="form-label">Versão do Sistema</label>
                                    <input type="text" class="form-control" id="config_versao_sistema" name="config_versao_sistema" value="<?php echo htmlspecialchars($configuracoes['versao_sistema']['valor'] ?? '1.0.1'); ?>">
                                    <div class="form-text">Versão atual do sistema</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_manutencao" class="form-label">Modo de Manutenção</label>
                                    <select class="form-select" id="config_manutencao" name="config_manutencao">
                                        <option value="nao" <?php echo ($configuracoes['manutencao']['valor'] ?? 'nao') == 'nao' ? 'selected' : ''; ?>>Desativado</option>
                                        <option value="sim" <?php echo ($configuracoes['manutencao']['valor'] ?? 'nao') == 'sim' ? 'selected' : ''; ?>>Ativado</option>
                                    </select>
                                    <div class="form-text">Ativar modo de manutenção (apenas administradores poderão acessar o sistema)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="config_mensagem_manutencao" class="form-label">Mensagem de Manutenção</label>
                                    <textarea class="form-control" id="config_mensagem_manutencao" name="config_mensagem_manutencao" rows="3"><?php echo htmlspecialchars($configuracoes['mensagem_manutencao']['valor'] ?? 'O sistema está em manutenção. Por favor, tente novamente mais tarde.'); ?></textarea>
                                    <div class="form-text">Mensagem exibida quando o sistema estiver em manutenção</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar prévia do comprovante quando a mensagem for alterada
    document.getElementById('config_mensagem_comprovante').addEventListener('input', function() {
        const previa = document.querySelector('#preview_mensagem_comprovante');
        previa.innerHTML = this.value.replace(/\n/g, '<br>');
    });
    
    // Atualizar prévia do comprovante quando o nome da empresa for alterado
    document.getElementById('config_nome_empresa').addEventListener('input', function() {
        const previa = document.querySelector('#comprovantes .card-body .border p:nth-child(2)');
        previa.innerHTML = 'Segue o comprovante da sua compra na <strong>' + this.value + '</strong>:';
    });
    
    // Atualizar prévia das cores
    function atualizarPreviaCores() {
        const corPrimaria = document.getElementById('config_cor_primaria').value;
        const corSecundaria = document.getElementById('config_cor_secundaria').value;
        const corBotoes = document.getElementById('config_cor_botoes').value;
        const corTexto = document.getElementById('config_cor_texto').value;
        
        document.getElementById('preview_cor_primaria').style.backgroundColor = corPrimaria;
        document.getElementById('preview_cor_primaria').style.color = '#ffffff';
        
        document.getElementById('preview_cor_secundaria').style.backgroundColor = corSecundaria;
        document.getElementById('preview_cor_secundaria').style.color = '#ffffff';
        
        document.getElementById('preview_cor_botoes').style.backgroundColor = corBotoes;
        document.getElementById('preview_cor_botoes').style.color = '#ffffff';
        
        document.getElementById('preview_cor_texto').style.backgroundColor = '#ffffff';
        document.getElementById('preview_cor_texto').style.color = corTexto;
    }
    
    // Inicializar prévia de cores
    atualizarPreviaCores();
    
    // Atualizar prévia quando as cores forem alteradas
    document.getElementById('config_cor_primaria').addEventListener('input', atualizarPreviaCores);
    document.getElementById('config_cor_secundaria').addEventListener('input', atualizarPreviaCores);
    document.getElementById('config_cor_botoes').addEventListener('input', atualizarPreviaCores);
    document.getElementById('config_cor_texto').addEventListener('input', atualizarPreviaCores);
    
    // Atualizar visibilidade da logo no comprovante
    document.getElementById('config_incluir_logo_comprovante').addEventListener('change', function() {
        const logoContainer = document.querySelector('#comprovantes .card-body .border .text-center');
        if (logoContainer) {
            logoContainer.style.display = this.value === 'sim' ? 'block' : 'none';
        }
    });
});
</script>

<style>
    /* Estilos para as abas de configuração */
    #configTabs .nav-link {
        color: #495057;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        margin-right: 5px;
        border-bottom: none;
    }
    
    #configTabs .nav-link:hover {
        color: #0d6efd;
        background-color: #fff;
    }
    
    #configTabs .nav-link.active {
        color: #495057;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
        font-weight: bold;
    }
    
    /* Ajuste para o preview de cores */
    #preview_cor_primaria, 
    #preview_cor_secundaria, 
    #preview_cor_botoes {
        color: white !important;
        text-align: center;
        font-weight: bold;
    }
    
    #preview_cor_texto {
        background-color: white !important;
        text-align: center;
        font-weight: bold;
        color: #212529 !important;
    }
    
    /* Garantir que os botões das abas tenham cores visíveis */
    .nav-tabs .nav-link {
        color: #495057 !important;
    }
    
    .nav-tabs .nav-link.active {
        color: #0d6efd !important;
    }
    
    /* Garantir que os botões tenham cores visíveis */
    .btn-outline-danger {
        color: #dc3545 !important;
        border-color: #dc3545 !important;
    }
    
    .btn-outline-danger:hover {
        color: #fff !important;
        background-color: #dc3545 !important;
    }
    
    .btn-primary {
        color: #fff !important;
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    
    .btn-secondary {
        color: #fff !important;
        background-color: #6c757d !important;
        border-color: #6c757d !important;
    }
    
    /* Garantir que os textos nos previews sejam visíveis */
    #preview_cor_primaria {
        background-color: var(--cor-primaria, #343a40);
        color: white !important;
    }
    
    #preview_cor_secundaria {
        background-color: var(--cor-secundaria, #6c757d);
        color: white !important;
    }
    
    #preview_cor_botoes {
        background-color: var(--cor-botoes, #0d6efd);
        color: white !important;
    }
    
    #preview_cor_texto {
        background-color: white !important;
        color: var(--cor-texto, #212529) !important;
    }
</style>

<?php include '../includes/footer.php'; ?>