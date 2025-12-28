<?php
/**
 * Carregador de variáveis de ambiente (.env)
 * Carrega configurações do arquivo .env para variáveis de ambiente
 */

class EnvLoader {
    private static $loaded = false;
    private static $vars = [];
    
    /**
     * Carrega o arquivo .env
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($path)) {
            error_log("Arquivo .env não encontrado: $path");
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Separar chave=valor
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover aspas se presentes
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$vars[$key] = $value;
                
                // Definir como variável de ambiente
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Obtém uma variável de ambiente
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$vars[$key] ?? getenv($key) ?: $default;
    }
    
    /**
     * Verifica se uma variável existe
     */
    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset(self::$vars[$key]) || getenv($key) !== false;
    }
}

// Auto-carregar o .env ao incluir este arquivo
EnvLoader::load();

/**
 * Função helper para acessar variáveis de ambiente
 */
function env($key, $default = null) {
    return EnvLoader::get($key, $default);
}
