<?php
/**
 * Rate Limiter Simples baseado em arquivo
 * Não requer banco de dados ou Redis
 */

function check_rate_limit(string $action, string $identifier, int $max_attempts, int $window_seconds): bool
{
    $cache_dir = sys_get_temp_dir() . '/pedimais_rate_limit/';
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }

    $file = $cache_dir . md5($action . '_' . $identifier) . '.json';

    $data = [];
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        $data = $content ? json_decode($content, true) : [];
        if (!is_array($data))
            $data = [];
    }

    $now = time();

    // Limpar tentativas antigas (fora da janela)
    $data = array_filter($data, function ($timestamp) use ($now, $window_seconds) {
        return ($now - $timestamp) < $window_seconds;
    });

    // Verificar se excedeu o limite
    if (count($data) >= $max_attempts) {
        return false; // Rate limit excedido
    }

    // Registrar nova tentativa
    $data[] = $now;
    @file_put_contents($file, json_encode(array_values($data)));

    return true; // Permitido
}

function get_remaining_time(string $action, string $identifier, int $window_seconds): int
{
    $cache_dir = sys_get_temp_dir() . '/pedimais_rate_limit/';
    $file = $cache_dir . md5($action . '_' . $identifier) . '.json';

    if (!file_exists($file))
        return 0;

    $content = @file_get_contents($file);
    $data = $content ? json_decode($content, true) : [];
    if (empty($data))
        return 0;

    $oldest = min($data);
    $remaining = $window_seconds - (time() - $oldest);

    return max(0, $remaining);
}
