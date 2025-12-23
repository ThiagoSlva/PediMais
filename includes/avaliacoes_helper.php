<?php
// avaliacoes_helper.php - Recreated
// Handles order reviews

class AvaliacoesHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function salvarAvaliacao($pedido_id, $nota, $comentario = '') {
        try {
            // Check if already reviewed
            $stmt = $this->pdo->prepare("SELECT id FROM avaliacoes WHERE pedido_id = ?");
            $stmt->execute([$pedido_id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Pedido já avaliado'];
            }

            $stmt = $this->pdo->prepare("INSERT INTO avaliacoes (pedido_id, nota, comentario, data_avaliacao) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$pedido_id, $nota, $comentario]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAvaliacao($pedido_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM avaliacoes WHERE pedido_id = ?");
        $stmt->execute([$pedido_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMediaAvaliacoes() {
        $stmt = $this->pdo->query("SELECT AVG(nota) as media, COUNT(*) as total FROM avaliacoes");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
