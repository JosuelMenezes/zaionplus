<?php
// contas/config.php
// Configuração específica do módulo Contas - Sistema Domaria Café

// 1. Utilizar a configuração principal de banco de dados
require_once __DIR__ . '/../config/database.php';

// 2. Criar a conexão PDO utilizando as credenciais do arquivo principal
$pdo_connection = null; 
try {
    $pdo_connection = new PDO(
        "mysql:host=" . $host . ";dbname=" . $database . ";charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Erro na conexão com banco de dados (PDO): " . $e->getMessage());
    die("Erro ao conectar com o sistema. Por favor, tente novamente mais tarde.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// ===== Constantes do Módulo =====
define('MODULO_CONTAS_VERSION', '1.0');
define('CONTAS_ITEMS_PER_PAGE', 25);
define('CONTAS_STATUS', [
    'pendente' => ['label' => 'Pendente', 'color' => 'warning', 'icon' => 'clock'],
    'pago_parcial' => ['label' => 'Pago Parcial', 'color' => 'info', 'icon' => 'clock-rotate-left'],
    'pago' => ['label' => 'Pago', 'color' => 'success', 'icon' => 'check-circle'],
    'vencido' => ['label' => 'Vencido', 'color' => 'danger', 'icon' => 'exclamation-triangle'],
    'cancelado' => ['label' => 'Cancelado', 'color' => 'secondary', 'icon' => 'times-circle']
]);
// ... (outras constantes que você possa ter) ...

class ContasManager {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function buscarContas($filtros = [], $pagina = 1, $itensPorPagina = CONTAS_ITEMS_PER_PAGE) {
        $where = ["c.status != 'cancelado'"];
        $params = [];
        if (!empty($filtros['tipo'])) { $where[] = "c.tipo = :tipo"; $params[':tipo'] = $filtros['tipo']; }
        if (!empty($filtros['status'])) { $where[] = "c.status = :status"; $params[':status'] = $filtros['status']; }
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT 
                c.*,
                cl.nome as cliente_nome,

                /* <<< CORREÇÃO DEFINITIVA APLICADA >>> */
                f.nome as fornecedor_nome, 
                
                cat.nome as categoria_nome,
                cat.cor as categoria_cor,
                cat.icone as categoria_icone,
                 CASE 
                    WHEN c.data_vencimento < CURDATE() AND c.status IN ('pendente', 'pago_parcial') 
                    THEN DATEDIFF(CURDATE(), c.data_vencimento) 
                    ELSE 0 
                END as dias_vencido
            FROM contas c
            LEFT JOIN clientes cl ON c.cliente_id = cl.id
            LEFT JOIN fornecedores f ON c.fornecedor_id = f.id
            LEFT JOIN categorias_contas cat ON c.categoria_id = cat.id
            WHERE {$whereClause}
            ORDER BY c.data_vencimento ASC
            LIMIT :offset, :limit
        ";
        
        $offset = ($pagina - 1) * $itensPorPagina;
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => &$value) { $stmt->bindParam($key, $value); }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarContas($filtros = []) {
        $where = ["status != 'cancelado'"];
        $params = [];
        if (!empty($filtros['tipo'])) { $where[] = "tipo = :tipo"; $params[':tipo'] = $filtros['tipo']; }
        if (!empty($filtros['status'])) { $where[] = "status = :status"; $params[':status'] = $filtros['status']; }
        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM contas WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function buscarCategorias() {
        $sql = "SELECT * FROM categorias_contas ORDER BY nome";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obterResumo() {
        // Implementar sua lógica de resumo aqui, se necessário
        return [];
    }
}

$contasManager = new ContasManager($pdo_connection);

// Funções de ajuda
function formatarMoeda($valor) { return 'R$ ' . number_format($valor ?? 0, 2, ',', '.'); }
function formatarData($data) { if (empty($data)) return '-'; return date('d/m/Y', strtotime($data)); }
function getBadgeStatus($status) {
    $config = CONTAS_STATUS[$status] ?? CONTAS_STATUS['pendente'];
    return ['class' => 'badge text-bg-' . $config['color'], 'icon' => $config['icon'], 'label' => $config['label']];
}
?>