<?php
require_once __DIR__ . '/cache.php';

/**
 * Classe para gerenciamento de configurações do sistema
 */
class ConfigManager {
    private $conn;
    private $cache;
    private $config = null;
    private $cache_key = 'system_config';
    
    /**
     * Construtor
     * 
     * @param mysqli $conn Conexão com o banco de dados
     */
    public function __construct($conn) {
        $this->conn = $conn;
        $this->cache = new Cache();
        
        // Tentar carregar do cache de sessão primeiro (mais rápido)
        if (isset($_SESSION['system_config'])) {
            $this->config = $_SESSION['system_config'];
        } 
        // Depois tentar do cache em arquivo
        else if ($this->cache->has($this->cache_key)) {
            $this->config = $this->cache->get($this->cache_key);
            // Armazenar na sessão para acesso mais rápido
            $_SESSION['system_config'] = $this->config;
        }
    }
    
    /**
     * Carrega todas as configurações do banco de dados
     * 
     * @param bool $force Forçar recarga do banco de dados
     * @return array Configurações do sistema
     */
    public function loadAll($force = false) {
        // Se já temos as configurações e não estamos forçando recarga, retorna
        if ($this->config !== null && !$force) {
            return $this->config;
        }
        
        // Buscar do banco de dados
        $sql = "SELECT * FROM configuracoes";
        $result = $this->conn->query($sql);
        
        $config = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $config[$row['chave']] = $row['valor'];
            }
        }
        
        // Armazenar em cache
        $this->cache->set($this->cache_key, $config);
        
        // Armazenar na sessão para acesso mais rápido
        $_SESSION['system_config'] = $config;
        
        // Armazenar na instância
        $this->config = $config;
        
        return $config;
    }
    
    /**
     * Obtém uma configuração específica
     * 
     * @param string $key Chave da configuração
     * @param mixed $default Valor padrão se a configuração não existir
     * @return mixed Valor da configuração ou valor padrão
     */
    public function get($key, $default = null) {
        // Carregar configurações se ainda não foram carregadas
        if ($this->config === null) {
            $this->loadAll();
        }
        
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    /**
     * Define uma configuração
     * 
     * @param string $key Chave da configuração
     * @param mixed $value Valor da configuração
     * @return bool True se a configuração foi definida com sucesso
     */
    public function set($key, $value) {
        // Verificar se a configuração já existe
        $sql = "SELECT COUNT(*) as count FROM configuracoes WHERE chave = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            // Atualizar configuração existente
            $sql = "UPDATE configuracoes SET valor = ? WHERE chave = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $value, $key);
        } else {
            // Inserir nova configuração
            $sql = "INSERT INTO configuracoes (chave, valor) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $key, $value);
        }
        
        $success = $stmt->execute();
        
        if ($success) {
            // Atualizar cache
            if ($this->config !== null) {
                $this->config[$key] = $value;
                $this->cache->set($this->cache_key, $this->config);
                $_SESSION['system_config'] = $this->config;
            } else {
                // Forçar recarga
                $this->loadAll(true);
            }
        }
        
        return $success;
    }
    
    /**
     * Limpa o cache de configurações
     */
    public function clearCache() {
        $this->cache->delete($this->cache_key);
        if (isset($_SESSION['system_config'])) {
            unset($_SESSION['system_config']);
        }
        $this->config = null;
    }
}