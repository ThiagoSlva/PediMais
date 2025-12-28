<?php
/**
 * API de Gerenciamento de Endereços do Cliente
 * Suporta: GET, POST, PUT, PATCH, DELETE
 */

header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../includes/auth.php';

// Check authentication
if (!isset($_SESSION['cliente_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $cliente_id);
            break;
        case 'POST':
            handlePost($pdo, $cliente_id);
            break;
        case 'PUT':
            handlePut($pdo, $cliente_id);
            break;
        case 'PATCH':
            handlePatch($pdo, $cliente_id);
            break;
        case 'DELETE':
            handleDelete($pdo, $cliente_id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}

/**
 * GET - Listar endereços ou buscar um específico
 */
function handleGet($pdo, $cliente_id) {
    if (isset($_GET['id'])) {
        // Get specific address
        $id = (int) $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM cliente_enderecos WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$id, $cliente_id]);
        $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($endereco) {
            echo json_encode(['success' => true, 'endereco' => $endereco]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endereço não encontrado']);
        }
    } else {
        // List all addresses
        $stmt = $pdo->prepare("SELECT * FROM cliente_enderecos WHERE cliente_id = ? ORDER BY principal DESC, id DESC");
        $stmt->execute([$cliente_id]);
        $enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'enderecos' => $enderecos]);
    }
}

/**
 * POST - Criar novo endereço
 */
function handlePost($pdo, $cliente_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['cep', 'rua', 'numero', 'bairro', 'cidade', 'estado'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Check limit (max 5 addresses)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cliente_enderecos WHERE cliente_id = ?");
    $stmt->execute([$cliente_id]);
    $count = $stmt->fetch()['total'];
    
    if ($count >= 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Limite de 5 endereços atingido']);
        return;
    }
    
    // Clean CEP
    $cep = preg_replace('/\D/', '', $data['cep']);
    
    // Check if it's the first address (make it principal)
    $is_principal = $count === 0 ? 1 : 0;
    
    $stmt = $pdo->prepare("
        INSERT INTO cliente_enderecos (cliente_id, apelido, cep, rua, numero, complemento, bairro, cidade, estado, principal)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $cliente_id,
        $data['apelido'] ?? null,
        $cep,
        $data['rua'],
        $data['numero'],
        $data['complemento'] ?? null,
        $data['bairro'],
        $data['cidade'],
        $data['estado'],
        $is_principal
    ]);
    
    $id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Endereço cadastrado com sucesso!',
        'id' => $id
    ]);
}

/**
 * PUT - Atualizar endereço existente
 */
function handlePut($pdo, $cliente_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do endereço não informado']);
        return;
    }
    
    $id = (int) $data['id'];
    
    // Check if address belongs to client
    $stmt = $pdo->prepare("SELECT id FROM cliente_enderecos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $cliente_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endereço não encontrado']);
        return;
    }
    
    // Validate required fields
    $required = ['cep', 'rua', 'numero', 'bairro', 'cidade', 'estado'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Clean CEP
    $cep = preg_replace('/\D/', '', $data['cep']);
    
    $stmt = $pdo->prepare("
        UPDATE cliente_enderecos 
        SET apelido = ?, cep = ?, rua = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?
        WHERE id = ? AND cliente_id = ?
    ");
    
    $stmt->execute([
        $data['apelido'] ?? null,
        $cep,
        $data['rua'],
        $data['numero'],
        $data['complemento'] ?? null,
        $data['bairro'],
        $data['cidade'],
        $data['estado'],
        $id,
        $cliente_id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Endereço atualizado com sucesso!']);
}

/**
 * PATCH - Ações especiais (definir principal)
 */
function handlePatch($pdo, $cliente_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id']) || empty($data['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        return;
    }
    
    $id = (int) $data['id'];
    
    // Check if address belongs to client
    $stmt = $pdo->prepare("SELECT id FROM cliente_enderecos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $cliente_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endereço não encontrado']);
        return;
    }
    
    if ($data['action'] === 'set_principal') {
        // Remove principal from all addresses
        $stmt = $pdo->prepare("UPDATE cliente_enderecos SET principal = 0 WHERE cliente_id = ?");
        $stmt->execute([$cliente_id]);
        
        // Set new principal
        $stmt = $pdo->prepare("UPDATE cliente_enderecos SET principal = 1 WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$id, $cliente_id]);
        
        echo json_encode(['success' => true, 'message' => 'Endereço definido como principal!']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
}

/**
 * DELETE - Remover endereço
 */
function handleDelete($pdo, $cliente_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do endereço não informado']);
        return;
    }
    
    $id = (int) $data['id'];
    
    // Check if address belongs to client and get its status
    $stmt = $pdo->prepare("SELECT principal FROM cliente_enderecos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $cliente_id]);
    $endereco = $stmt->fetch();
    
    if (!$endereco) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endereço não encontrado']);
        return;
    }
    
    // Delete address
    $stmt = $pdo->prepare("DELETE FROM cliente_enderecos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $cliente_id]);
    
    // If was principal, set another as principal
    if ($endereco['principal']) {
        $stmt = $pdo->prepare("UPDATE cliente_enderecos SET principal = 1 WHERE cliente_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$cliente_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Endereço removido com sucesso!']);
}
