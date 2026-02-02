<?php
/**
 * Sistema de Rastreamento de Visitantes
 * Captura IP, geolocalização, dispositivo e páginas visitadas
 */

// Prevenir execução direta
if (!defined('SITE_URL') && !isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

/**
 * Obter IP real do visitante (suporta proxies, CDN, Cloudflare)
 */
function get_visitor_ip() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Proxy
        'HTTP_X_REAL_IP',            // Nginx proxy
        'HTTP_CLIENT_IP',            // Cliente
        'REMOTE_ADDR'                // Padrão
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Se tiver múltiplos IPs (X-Forwarded-For), pegar o primeiro
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            // Validar se é IP válido
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Obter geolocalização via ip-api.com (gratuita, 45 req/min)
 */
function get_geolocation($ip) {
    // IPs locais não têm geolocalização
    if (in_array($ip, ['127.0.0.1', '::1', 'localhost']) || 
        strpos($ip, '192.168.') === 0 || 
        strpos($ip, '10.') === 0) {
        return [
            'country' => 'Local',
            'countryCode' => 'LC',
            'region' => 'Local',
            'city' => 'Localhost',
            'lat' => 0,
            'lon' => 0,
            'isp' => 'Local Network'
        ];
    }
    
    try {
        // Cache em sessão para evitar muitas requisições
        $cache_key = 'geo_' . md5($ip);
        if (isset($_SESSION[$cache_key])) {
            return $_SESSION[$cache_key];
        }
        
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city,lat,lon,isp";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                $result = [
                    'country' => $data['country'] ?? null,
                    'countryCode' => $data['countryCode'] ?? null,
                    'region' => $data['regionName'] ?? null,
                    'city' => $data['city'] ?? null,
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null,
                    'isp' => $data['isp'] ?? null
                ];
                
                // Cachear por 1 hora na sessão
                $_SESSION[$cache_key] = $result;
                
                return $result;
            }
        }
    } catch (Exception $e) {
        error_log("Analytics Geo Error: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Detectar tipo de dispositivo e navegador
 */
function detect_device($user_agent) {
    $user_agent = strtolower($user_agent);
    
    // Tipo de dispositivo
    $device_type = 'Desktop';
    if (preg_match('/(android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile)/i', $user_agent)) {
        $device_type = 'Mobile';
        if (preg_match('/(ipad|tablet|playbook|silk)/i', $user_agent)) {
            $device_type = 'Tablet';
        }
    }
    
    // Navegador
    $browser = 'Outro';
    if (strpos($user_agent, 'edg') !== false) {
        $browser = 'Edge';
    } elseif (strpos($user_agent, 'opr') !== false || strpos($user_agent, 'opera') !== false) {
        $browser = 'Opera';
    } elseif (strpos($user_agent, 'chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($user_agent, 'safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($user_agent, 'firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($user_agent, 'msie') !== false || strpos($user_agent, 'trident') !== false) {
        $browser = 'Internet Explorer';
    }
    
    // Sistema Operacional
    $os = 'Outro';
    if (strpos($user_agent, 'windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($user_agent, 'mac') !== false) {
        $os = 'MacOS';
    } elseif (strpos($user_agent, 'linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($user_agent, 'android') !== false) {
        $os = 'Android';
    } elseif (strpos($user_agent, 'iphone') !== false || strpos($user_agent, 'ipad') !== false) {
        $os = 'iOS';
    }
    
    return [
        'device_type' => $device_type,
        'browser' => $browser,
        'os' => $os
    ];
}

/**
 * Rastrear visita do usuário
 * @param string $page_title Título da página (ex: "Cardápio", "Checkout")
 */
function track_visitor($page_title = null) {
    global $pdo;
    
    // Não rastrear bots conhecidos
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/(bot|crawler|spider|slurp|googlebot|bingbot|yandex)/i', $user_agent)) {
        return false;
    }
    
    // Não rastrear requisições AJAX/API
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return false;
    }
    
    try {
        // Garantir que a tabela existe
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS site_analytics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                country VARCHAR(100) NULL,
                country_code VARCHAR(10) NULL,
                region VARCHAR(100) NULL,
                city VARCHAR(100) NULL,
                latitude DECIMAL(10, 8) NULL,
                longitude DECIMAL(11, 8) NULL,
                isp VARCHAR(200) NULL,
                page_url VARCHAR(500) NOT NULL,
                page_title VARCHAR(200) NULL,
                referrer VARCHAR(500) NULL,
                user_agent TEXT NULL,
                device_type VARCHAR(50) NULL,
                browser VARCHAR(100) NULL,
                os VARCHAR(100) NULL,
                session_id VARCHAR(100) NULL,
                visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address),
                INDEX idx_visited (visited_at),
                INDEX idx_page (page_url(100)),
                INDEX idx_country (country_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $ip = get_visitor_ip();
        $current_url = $_SERVER['REQUEST_URI'] ?? '/';
        
        // ⚡ OTIMIZAÇÃO: Evitar duplicatas de refresh (mesmo IP, mesma URL, intervalo curto)
        // Mas PERMITIR navegação entre páginas diferentes para ver o fluxo do usuário
        $stmt_check = $pdo->prepare("
            SELECT id FROM site_analytics 
            WHERE ip_address = ? 
            AND page_url = ? 
            AND visited_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            LIMIT 1
        ");
        $stmt_check->execute([$ip, $current_url]);
        
        if ($stmt_check->fetch()) {
            // Visita repetida na mesma página em menos de 5 min -> ignorar
            return false;
        }
        
        $geo = get_geolocation($ip);
        $device = detect_device($user_agent);
        
        // Gerar/recuperar session ID
        if (!isset($_SESSION['analytics_session_id'])) {
            $_SESSION['analytics_session_id'] = bin2hex(random_bytes(16));
        }
        
        $data = [
            'ip_address' => $ip,
            'country' => $geo['country'] ?? null,
            'country_code' => $geo['countryCode'] ?? null,
            'region' => $geo['region'] ?? null,
            'city' => $geo['city'] ?? null,
            'latitude' => $geo['lat'] ?? null,
            'longitude' => $geo['lon'] ?? null,
            'isp' => $geo['isp'] ?? null,
            'page_url' => $_SERVER['REQUEST_URI'] ?? '/',
            'page_title' => $page_title,
            'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
            'user_agent' => $user_agent,
            'device_type' => $device['device_type'],
            'browser' => $device['browser'],
            'os' => $device['os'],
            'session_id' => $_SESSION['analytics_session_id']
        ];
        
        $sql = "INSERT INTO site_analytics 
                (ip_address, country, country_code, region, city, latitude, longitude, isp, 
                 page_url, page_title, referrer, user_agent, device_type, browser, os, session_id)
                VALUES 
                (:ip_address, :country, :country_code, :region, :city, :latitude, :longitude, :isp,
                 :page_url, :page_title, :referrer, :user_agent, :device_type, :browser, :os, :session_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Analytics Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Obter estatísticas de analytics
 */
function get_analytics_stats($period = 'today') {
    global $pdo;
    
    $where = "";
    switch ($period) {
        case 'today':
            $where = "WHERE DATE(visited_at) = CURDATE()";
            break;
        case 'yesterday':
            $where = "WHERE DATE(visited_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $where = "WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where = "WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        default:
            $where = "";
    }
    
    try {
        // Total de visitas
        $total = $pdo->query("SELECT COUNT(*) FROM site_analytics {$where}")->fetchColumn();
        
        // Visitantes únicos (por IP)
        $unique = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM site_analytics {$where}")->fetchColumn();
        
        // Sessões únicas
        $sessions = $pdo->query("SELECT COUNT(DISTINCT session_id) FROM site_analytics {$where}")->fetchColumn();
        
        return [
            'total_visits' => (int)$total,
            'unique_visitors' => (int)$unique,
            'sessions' => (int)$sessions
        ];
        
    } catch (Exception $e) {
        return ['total_visits' => 0, 'unique_visitors' => 0, 'sessions' => 0];
    }
}
?>
