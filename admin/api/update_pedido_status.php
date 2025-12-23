<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

verificar_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Suporte para POST normal ou JSON
    $pedido_id = isset($_POST['pedido_id']) ? $_POST['pedido_id'] : (isset($input['pedido_id']) ? $input['pedido_id'] : null);
    $status = isset($_POST['status']) ? $_POST['status'] : (isset($input['status']) ? $input['status'] : null);
    
    // Suporte para field/value (usado pelos botões do Kanban)
    $field = isset($input['field']) ? $input['field'] : null;
    $value = isset($input['value']) ? $input['value'] : null;

    // Se temos field/value, processar toggle de campo individual
    if ($pedido_id && $field !== null) {
        try {
            // Validar campo permitido
            $allowed_fields = ['pago', 'em_preparo', 'saiu_entrega', 'entregue'];
            if (!in_array($field, $allowed_fields)) {
                echo json_encode(['success' => false, 'error' => 'Campo não permitido']);
                exit;
            }
            
            // Converter valor para inteiro (0 ou 1)
            $new_value = $value ? 1 : 0;
            
            // Atualizar campo específico
            $stmt = $pdo->prepare("UPDATE pedidos SET $field = ?, atualizado_em = NOW() WHERE id = ?");
            $stmt->execute([$new_value, $pedido_id]);
            
            // Mapear field para status e enviar WhatsApp
            $status_para_whatsapp = null;
            
            // Se marcou como entregue, atualizar status também
            if ($field === 'entregue' && $new_value == 1) {
                $pdo->prepare("UPDATE pedidos SET status = 'concluido' WHERE id = ?")->execute([$pedido_id]);
                $status_para_whatsapp = 'concluido';
            }
            
            // Se marcou saiu_entrega, atualizar status
            if ($field === 'saiu_entrega' && $new_value == 1) {
                $pdo->prepare("UPDATE pedidos SET status = 'saiu_entrega' WHERE id = ?")->execute([$pedido_id]);
                $status_para_whatsapp = 'saiu_entrega';
            }
            
            // Se marcou em_preparo, atualizar status
            if ($field === 'em_preparo' && $new_value == 1) {
                $pdo->prepare("UPDATE pedidos SET status = 'em_andamento' WHERE id = ?")->execute([$pedido_id]);
                $status_para_whatsapp = 'em_andamento';
            }
            
            // Enviar notificação WhatsApp se mudou status
            $whatsapp_result = ['success' => false, 'error' => 'Não aplicável'];
            if ($status_para_whatsapp && $new_value == 1) {
                try {
                    require_once '../../includes/whatsapp_helper.php';
                    $whatsapp = new WhatsAppHelper($pdo);
                    
                    $stmt_pedido = $pdo->prepare("SELECT codigo_pedido, cliente_nome, cliente_telefone, valor_total, tipo_entrega FROM pedidos WHERE id = ?");
                    $stmt_pedido->execute([$pedido_id]);
                    $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);
                    
                    if ($pedido && !empty($pedido['cliente_telefone'])) {
                        $whatsapp_result = $whatsapp->sendStatusUpdate($pedido, $status_para_whatsapp);
                    }
                } catch (Exception $e) {
                    $whatsapp_result = ['success' => false, 'error' => $e->getMessage()];
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Campo atualizado com sucesso',
                'whatsapp_enviado' => $whatsapp_result['success'] ?? false
            ]);
            exit;
            
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Erro ao atualizar: ' . $e->getMessage()]);
            exit;
        }
    }

    // Processamento original para mudança de STATUS completo
    if ($pedido_id && $status) {
        try {
            // Atualizar status do pedido
            $stmt = $pdo->prepare("UPDATE pedidos SET status = ?, atualizado_em = NOW() WHERE id = ?");
            $stmt->execute([$status, $pedido_id]);
            
            // Mapear status para flags e lane do Kanban
            $em_preparo = 0;
            $saiu_entrega = 0;
            $entregue = 0;
            $lane_nome = '';
            
            switch ($status) {
                case 'em_andamento':
                    $em_preparo = 1;
                    $lane_nome = 'preparo';
                    break;
                case 'pronto':
                    $em_preparo = 1;
                    $lane_nome = 'pronto';
                    break;
                case 'saiu_entrega':
                    $em_preparo = 1;
                    $saiu_entrega = 1;
                    $lane_nome = 'saiu';
                    break;
                case 'concluido':
                    $em_preparo = 1;
                    $saiu_entrega = 1;
                    $entregue = 1;
                    $lane_nome = 'entregue';
                    break;
                case 'cancelado':
                    $lane_nome = 'cancelado';
                    break;
                default:
                    $lane_nome = 'novo';
            }
            
            // Atualizar flags
            $stmt = $pdo->prepare("UPDATE pedidos SET em_preparo = ?, saiu_entrega = ?, entregue = ? WHERE id = ?");
            $stmt->execute([$em_preparo, $saiu_entrega, $entregue, $pedido_id]);
            
            // Sincronizar com Kanban - buscar lane correspondente
            $stmt_lane = $pdo->prepare("SELECT id FROM kanban_lanes WHERE LOWER(nome) LIKE ? LIMIT 1");
            $stmt_lane->execute(['%' . $lane_nome . '%']);
            $lane = $stmt_lane->fetch();
            
            if ($lane) {
                $stmt = $pdo->prepare("UPDATE pedidos SET lane_id = ? WHERE id = ?");
                $stmt->execute([$lane['id'], $pedido_id]);
            }
            
            // Enviar notificação WhatsApp de mudança de status
            $whatsapp_result = ['success' => false, 'error' => 'Não iniciado'];
            try {
                require_once '../../includes/whatsapp_helper.php';
                $whatsapp = new WhatsAppHelper($pdo);
                
                // Buscar dados do pedido para notificação
                $stmt_pedido = $pdo->prepare("SELECT codigo_pedido, cliente_nome, cliente_telefone, valor_total, tipo_entrega FROM pedidos WHERE id = ?");
                $stmt_pedido->execute([$pedido_id]);
                $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);
                
                if ($pedido && !empty($pedido['cliente_telefone'])) {
                    $whatsapp_result = $whatsapp->sendStatusUpdate($pedido, $status);
                } else {
                    $whatsapp_result = ['success' => false, 'error' => 'Pedido não encontrado ou sem telefone'];
                }
            } catch (Exception $e) {
                $whatsapp_result = ['success' => false, 'error' => 'Exceção: ' . $e->getMessage()];
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Status atualizado com sucesso', 
                'whatsapp_enviado' => $whatsapp_result['success'] ?? false,
                'whatsapp_detalhes' => $whatsapp_result
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
}
?>