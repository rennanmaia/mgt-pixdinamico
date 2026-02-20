<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

use PixDinamico\PixManager;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos');
    }
    
    $searchType = $input['type'] ?? '';
    $searchValue = $input['value'] ?? '';
    
    if (!$searchType || !$searchValue) {
        throw new Exception('Tipo de busca e valor são obrigatórios');
    }
    
    $pixManager = new PixManager();
    
    // Buscar lançamentos baseado no tipo
    $results = searchLancamentos($pixManager, $searchType, $searchValue);
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function searchLancamentos($pixManager, $type, $value) {
    $db = $pixManager->getDatabase();
    
    switch ($type) {
        case 'lanc_id':
            $query = "
                SELECT l.*, c.nome as cliente_nome, c.cpf_cnpj, c.email
                FROM sis_lanc l
                LEFT JOIN sis_cliente c ON l.login = c.login
                WHERE l.id = ?
                LIMIT 10
            ";
            $params = [$value];
            break;
            
        case 'recibo':
            $query = "
                SELECT l.*, c.nome as cliente_nome, c.cpf_cnpj, c.email
                FROM sis_lanc l
                LEFT JOIN sis_cliente c ON l.login = c.login
                WHERE l.recibo LIKE ?
                ORDER BY l.id DESC
                LIMIT 10
            ";
            $params = ["%{$value}%"];
            break;
            
        case 'cliente':
            $query = "
                SELECT l.*, c.nome as cliente_nome, c.cpf_cnpj, c.email
                FROM sis_lanc l
                LEFT JOIN sis_cliente c ON l.login = c.login
                WHERE c.nome LIKE ?
                ORDER BY l.id DESC
                LIMIT 10
            ";
            $params = ["%{$value}%"];
            break;
            
        case 'login':
            $query = "
                SELECT l.*, c.nome as cliente_nome, c.cpf_cnpj, c.email
                FROM sis_lanc l
                LEFT JOIN sis_cliente c ON l.login = c.login
                WHERE l.login LIKE ?
                ORDER BY l.id DESC
                LIMIT 10
            ";
            $params = ["%{$value}%"];
            break;
            
        default:
            throw new Exception('Tipo de busca inválido');
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    // Formatar resultados
    foreach ($results as &$result) {
        // Converter valor para float se necessário
        if (isset($result['valor'])) {
            $result['valor'] = str_replace(',', '.', $result['valor']);
        }
        
        // Formatar datas
        if (isset($result['datavenc'])) {
            $result['datavenc'] = date('Y-m-d H:i:s', strtotime($result['datavenc']));
        }
        
        // Garantir que cpf_cnpj não seja nulo
        $result['cpf_cnpj'] = $result['cpf_cnpj'] ?: '';
    }
    
    return $results;
}

// Adicionar método público para acessar o database
if (!class_exists('PixDinamico\PixManager')) {
    // Fallback se a classe não for carregada corretamente
    class SimplePixManager {
        private $db;
        
        public function __construct() {
            global $db_host, $db_name, $db_user, $db_pass;
            
            $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
            $this->db = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
        
        public function getDatabase() {
            return $this->db;
        }
    }
    
    // Redefinir função se necessário
    if (!function_exists('searchLancamentos')) {
        function searchLancamentos($pixManager, $type, $value) {
            if (method_exists($pixManager, 'getDatabase')) {
                $db = $pixManager->getDatabase();
            } else {
                // Fallback
                global $db_host, $db_name, $db_user, $db_pass;
                $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
                $db = new PDO($dsn, $db_user, $db_pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            }
            
            // Resto da lógica permanece igual...
            // (código duplicado do switch acima)
        }
    }
}
?>