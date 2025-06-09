<?php
// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações do banco de dados
$host = 'localhost';
$username = 'dunkac76_jghoste'; // Substitua pelo seu usuário do banco
$password = "382707020@'";   // Substitua pela sua senha do banco
$database = 'dunkac76_uzumaki';   // Substitua pelo nome do seu banco

// Conectar ao banco de dados
$conn = new mysqli($host, $username, $password, $database);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Definir charset
$conn->set_charset("utf8");

// Carregar configurações do sistema
$config = [];

// Verificar se a tabela de configurações existe
$table_exists = $conn->query("SHOW TABLES LIKE 'configuracoes'")->num_rows > 0;

if ($table_exists) {
    $sql_config = "SELECT chave, valor FROM configuracoes";
    $result_config = $conn->query($sql_config);

    if ($result_config && $result_config->num_rows > 0) {
        while ($row = $result_config->fetch_assoc()) {
            $config[$row['chave']] = $row['valor'];
        }
    }
} else {
    // Criar a tabela de configurações se não existir
    $sql_create_table = "CREATE TABLE configuracoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(50) NOT NULL UNIQUE,
        valor TEXT,
        descricao VARCHAR(255),
        tipo VARCHAR(20) DEFAULT 'texto',
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql_create_table) === TRUE) {
        // Inserir configurações iniciais
        $sql_insert = "INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES
            ('nome_empresa', 'Zaion GC', 'Nome da empresa exibido no sistema e comprovantes', 'texto'),
            ('logo_url', 'assets/images/logo.png', 'URL da logomarca da empresa', 'imagem'),
            ('telefone_empresa', '', 'Telefone de contato da empresa', 'texto'),
            ('email_empresa', '', 'Email de contato da empresa', 'texto'),
            ('endereco_empresa', '', 'Endereço da empresa', 'texto'),
            ('mensagem_comprovante', 'Agradecemos pela preferência!\nPara mais informações, entre em contato conosco.', 'Mensagem exibida no final dos comprovantes', 'textarea'),
            ('cor_primaria', '#343a40', 'Cor primária do sistema', 'cor'),
            ('cor_secundaria', '#6c757d', 'Cor secundária do sistema', 'cor')";
        
        $conn->query($sql_insert);
        
        // Carregar as configurações padrão
        $config['nome_empresa'] = 'Zaion GC';
        $config['logo_url'] = 'assets/images/logo.png';
        $config['telefone_empresa'] = '';
        $config['email_empresa'] = '';
        $config['endereco_empresa'] = '';
        $config['mensagem_comprovante'] = "Agradecemos pela preferência!\nPara mais informações, entre em contato conosco.";
        $config['cor_primaria'] = '#343a40';
        $config['cor_secundaria'] = '#6c757d';
    }
}

// Definir configurações padrão se não existirem no banco
if (!isset($config['nome_empresa'])) {
    $config['nome_empresa'] = 'Zaion GC';
}
if (!isset($config['mensagem_comprovante'])) {
    $config['mensagem_comprovante'] = "Agradecemos pela preferência!\nPara mais informações, entre em contato conosco.";
}
if (empty($config['logo_url'])) {
    $config['logo_url'] = 'assets/images/logo.png';
}

// Disponibilizar configurações na sessão para uso global
$_SESSION['config'] = $config;

// Função para obter o caminho base da aplicação
function getBasePath() {
    $current_path = $_SERVER['PHP_SELF'];
    $path_parts = explode('/', $current_path);
    
    // Remover o arquivo atual e a pasta atual
    array_pop($path_parts);
    
    // Determinar quantos níveis voltar
    $levels_back = 0;
    if (in_array('config', $path_parts)) $levels_back++;
    if (in_array('clientes', $path_parts)) $levels_back++;
    if (in_array('produtos', $path_parts)) $levels_back++;
    if (in_array('vendas', $path_parts)) $levels_back++;
    if (in_array('usuarios', $path_parts)) $levels_back++;
    
    $base_path = '';
    for ($i = 0; $i < $levels_back; $i++) {
        $base_path .= '../';
    }
    
    return $base_path;
}

// Definir o caminho base para links
$base_path = getBasePath();

/**
 * Função para obter uma configuração do sistema
 * @param string $key Chave da configuração
 * @param mixed $default Valor padrão caso a configuração não exista
 * @return mixed Valor da configuração
 */
function get_config($key, $default = null) {
    global $config;
    
    if (isset($config[$key])) {
        return $config[$key];
    }
    
    return $default;
}

/**
 * Função para definir uma configuração do sistema
 * @param string $key Chave da configuração
 * @param mixed $value Valor da configuração
 * @return bool Sucesso ou falha
 */
function set_config($key, $value) {
    global $conn, $config;
    
    // Verificar se a configuração já existe
    $sql = "SELECT COUNT(*) as count FROM configuracoes WHERE chave = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // Atualizar configuração existente
        $sql = "UPDATE configuracoes SET valor = ? WHERE chave = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $value, $key);
    } else {
        // Inserir nova configuração
        $sql = "INSERT INTO configuracoes (chave, valor) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $key, $value);
    }
    
    $success = $stmt->execute();
    
    if ($success) {
        // Atualizar o array de configurações
        $config[$key] = $value;
    }
    
    return $success;
}

/**
 * Função para formatar um valor monetário
 * @param float $value Valor a ser formatado
 * @param bool $with_symbol Incluir o símbolo da moeda
 * @return string Valor formatado
 */
function format_money($value, $with_symbol = true) {
    $formatted = number_format($value, 2, ',', '.');
    return $with_symbol ? 'R$ ' . $formatted : $formatted;
}

/**
 * Função para formatar uma data
 * @param string $date Data no formato Y-m-d
 * @param string $format Formato de saída
 * @return string Data formatada
 */
function format_date($date, $format = 'd/m/Y') {
    if (empty($date) || $date == '0000-00-00') {
        return '';
    }
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Função para verificar se o usuário tem o nível de acesso necessário
 * @param string|array $required_levels Nível ou níveis de acesso necessários
 * @return bool Verdadeiro se o usuário tem acesso, falso caso contrário
 */
function check_access($required_levels) {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['nivel_acesso'])) {
        return false;
    }
    
    $user_level = $_SESSION['nivel_acesso'];
    
    if (is_array($required_levels)) {
        return in_array($user_level, $required_levels);
    } else {
        return $user_level === $required_levels || $user_level === 'admin';
    }
}

/**
 * Função para registrar um log no sistema
 * @param string $acao Ação realizada
 * @param string $descricao Descrição detalhada
 * @param string $tipo Tipo de log (info, warning, error)
 * @return bool Sucesso ou falha
 */
function registrar_log($acao, $descricao = '', $tipo = 'info') {
    global $conn;
    
    // Verificar se a tabela de logs existe
    $table_exists = $conn->query("SHOW TABLES LIKE 'logs_sistema'")->num_rows > 0;
    
    if (!$table_exists) {
        // Criar a tabela de logs
        $sql_create = "CREATE TABLE logs_sistema (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT,
            usuario_nome VARCHAR(100),
            acao VARCHAR(100) NOT NULL,
            descricao TEXT,
            tipo VARCHAR(20) DEFAULT 'info',
            ip VARCHAR(45),
            data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($sql_create)) {
            return false;
        }
    }
    
    // Obter informações do usuário
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
    $usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Sistema';
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Inserir o log
    $sql = "INSERT INTO logs_sistema (usuario_id, usuario_nome, acao, descricao, tipo, ip) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $usuario_id, $usuario_nome, $acao, $descricao, $tipo, $ip);
    
    return $stmt->execute();
}

/**
 * Função para limpar o cache de configurações
 */
function clear_config_cache() {
    global $config, $conn;
    
    // Recarregar configurações do banco
    $config = [];
    $sql_config = "SELECT chave, valor FROM configuracoes";
    $result_config = $conn->query($sql_config);

    if ($result_config && $result_config->num_rows > 0) {
        while ($row = $result_config->fetch_assoc()) {
            $config[$row['chave']] = $row['valor'];
        }
    }
}

/**
 * Função para gerar um token CSRF
 * @return string Token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Função para verificar um token CSRF
 * @param string $token Token a ser verificado
 * @return bool Verdadeiro se o token é válido, falso caso contrário
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Função para formatar um número de telefone
 * @param string $phone Número de telefone
 * @return string Número formatado
 */
function format_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 11) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
    } elseif (strlen($phone) === 10) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6);
    }
    
    return $phone;
}

/**
 * Função para calcular o saldo devedor de um cliente
 * @param int $cliente_id ID do cliente
 * @return float Saldo devedor
 */
function calcular_saldo_devedor($cliente_id) {
    global $conn;
    
    // Calcular o total de vendas
    $sql_vendas = "SELECT 
                    SUM(iv.quantidade * iv.valor_unitario) as total_vendas
                  FROM 
                    vendas v
                    JOIN itens_venda iv ON v.id = iv.venda_id
                  WHERE 
                    v.cliente_id = ? AND v.status = 'pendente'";
    
    $stmt_vendas = $conn->prepare($sql_vendas);
    $stmt_vendas->bind_param("i", $cliente_id);
    $stmt_vendas->execute();
    $result_vendas = $stmt_vendas->get_result();
    $row_vendas = $result_vendas->fetch_assoc();
    $total_vendas = $row_vendas['total_vendas'] ?? 0;
    
    // Calcular o total de pagamentos
    $sql_pagamentos = "SELECT 
                        SUM(p.valor) as total_pagamentos
                      FROM 
                        pagamentos p
                        JOIN vendas v ON p.venda_id = v.id
                      WHERE 
                        v.cliente_id = ? AND v.status = 'pendente'";
    
    $stmt_pagamentos = $conn->prepare($sql_pagamentos);
    $stmt_pagamentos->bind_param("i", $cliente_id);
    $stmt_pagamentos->execute();
    $result_pagamentos = $stmt_pagamentos->get_result();
    $row_pagamentos = $result_pagamentos->fetch_assoc();
    $total_pagamentos = $row_pagamentos['total_pagamentos'] ?? 0;
    
    // Calcular saldo devedor
    return $total_vendas - $total_pagamentos;
}
?>