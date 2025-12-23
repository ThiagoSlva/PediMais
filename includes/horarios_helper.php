<?php
// horarios_helper.php - Recreated
// Manages store opening hours

class HorariosHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function isLojaAberta() {
        // Check manual override first
        try {
            $stmt = $this->pdo->query("SELECT loja_aberta_manual FROM configuracao_horarios LIMIT 1");
            if ($stmt) {
                $manual = $stmt->fetchColumn();
                if ($manual !== null) {
                    return (bool)$manual;
                }
            }
        } catch (PDOException $e) {
            // Table might not exist yet, assume open or closed? Default closed safe.
            return false;
        }

        // Check automatic schedule
        $dia_semana = strtolower(date('l')); // sunday, monday, etc.
        // Map to 0-6 (Sunday=0)
        $dias_map = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6
        ];
        $dia_hoje = $dias_map[$dia_semana];
        $hora_agora = date('H:i:s');

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM horarios_funcionamento WHERE dia_semana = ? AND ativo = 1");
            $stmt->execute([$dia_hoje]);
            $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($horarios as $horario) {
                if ($hora_agora >= $horario['abertura'] && $hora_agora <= $horario['fechamento']) {
                    return true;
                }
            }
        } catch (PDOException $e) {
            return false;
        }

        return false;
    }

    public function getHorariosHoje() {
        $dia_semana = strtolower(date('l'));
        $dias_map = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6
        ];
        $dia_hoje = $dias_map[$dia_semana];

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM horarios_funcionamento WHERE dia_semana = ? AND ativo = 1 ORDER BY abertura ASC");
            $stmt->execute([$dia_hoje]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
