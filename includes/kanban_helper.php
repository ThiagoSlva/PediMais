<?php
// kanban_helper.php - Recreated
// Helper functions for Kanban board

class KanbanHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getLanes() {
        $stmt = $this->pdo->query("SELECT * FROM kanban_lanes ORDER BY ordem ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLanePedidos($lane_id) {
        $stmt = $this->pdo->prepare("SELECT p.*, c.nome as cliente_nome, c.telefone as cliente_telefone 
                                     FROM pedidos p 
                                     LEFT JOIN clientes c ON p.cliente_id = c.id 
                                     WHERE p.lane_id = ? 
                                     ORDER BY p.data_pedido DESC");
        $stmt->execute([$lane_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function movePedido($pedido_id, $lane_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE pedidos SET lane_id = ? WHERE id = ?");
            $stmt->execute([$lane_id, $pedido_id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
