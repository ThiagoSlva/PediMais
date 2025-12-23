<?php
// entrega_helper.php - Recreated
// Handles delivery fee calculations

class EntregaHelper {
    private $pdo;
    private $config;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    private function loadConfig() {
        $stmt = $this->pdo->query("SELECT * FROM configuracao_entrega LIMIT 1");
        $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function calcularFrete($valor_pedido, $bairro_id = null, $tipo_entrega = 'entrega') {
        if ($tipo_entrega == 'retirada') {
            return $this->config['aceita_retirada'] ? (float)$this->config['taxa_retirada'] : 0.00;
        }

        // Modo 2: Grátis para todos
        if ($this->config['modo_gratis_todos_ativo']) {
            return 0.00;
        }

        // Modo 1: Grátis a partir de valor
        if ($this->config['modo_gratis_valor_ativo'] && $valor_pedido >= $this->config['valor_minimo_gratis']) {
            return 0.00;
        }

        // Modo 3: Valor Fixo
        if ($this->config['modo_valor_fixo_ativo']) {
            return (float)$this->config['valor_fixo_entrega'];
        }

        // Modo 5: Por Bairro
        if ($this->config['modo_por_bairro_ativo'] && $bairro_id) {
            $stmt = $this->pdo->prepare("SELECT valor_entrega, gratis_acima_de, entrega_disponivel FROM bairros WHERE id = ?");
            $stmt->execute([$bairro_id]);
            $bairro = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($bairro) {
                if (!$bairro['entrega_disponivel']) {
                    return -1; // Entrega indisponível
                }

                if ($bairro['gratis_acima_de'] !== null && $valor_pedido >= $bairro['gratis_acima_de']) {
                    return 0.00;
                }

                return (float)$bairro['valor_entrega'];
            }
        }

        // Default fallback if no method matches or bairro not found
        return 0.00; 
    }

    public function getBairros($cidade_id = null) {
        $sql = "SELECT b.*, c.nome as cidade_nome FROM bairros b JOIN cidades c ON b.cidade_id = c.id WHERE b.entrega_disponivel = 1";
        if ($cidade_id) {
            $sql .= " AND b.cidade_id = " . (int)$cidade_id;
        }
        $sql .= " ORDER BY b.nome ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
