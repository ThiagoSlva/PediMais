<?php
// log_helper.php - Recreated
// Helper for logging system events

class LogHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nivel VARCHAR(20) NOT NULL,
            mensagem TEXT NOT NULL,
            contexto TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function log($nivel, $mensagem, $contexto = []) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO system_logs (nivel, mensagem, contexto) VALUES (?, ?, ?)");
            $stmt->execute([$nivel, $mensagem, json_encode($contexto)]);
        } catch (PDOException $e) {
            // Silent fail or write to file
            error_log("Failed to write to DB log: " . $e->getMessage());
        }
    }

    public function info($mensagem, $contexto = []) {
        $this->log('INFO', $mensagem, $contexto);
    }

    public function error($mensagem, $contexto = []) {
        $this->log('ERROR', $mensagem, $contexto);
    }

    public function warning($mensagem, $contexto = []) {
        $this->log('WARNING', $mensagem, $contexto);
    }
}
?>
