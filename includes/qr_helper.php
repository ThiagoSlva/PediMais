<?php
// qr_helper.php - Recreated
// Helper for generating QR Codes

class QrHelper {
    
    public static function gerarQrCode($conteudo, $tamanho = 300) {
        // Using Google Charts API for simplicity and reliability without external dependencies
        // Alternative: https://api.qrserver.com/v1/create-qr-code/?size={$tamanho}x{$tamanho}&data={$encoded}
        $encoded = urlencode($conteudo);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$tamanho}x{$tamanho}&data={$encoded}";
    }

    public static function gerarQrCodeBase64($conteudo, $tamanho = 300) {
        $url = self::gerarQrCode($conteudo, $tamanho);
        $image = file_get_contents($url);
        if ($image) {
            return 'data:image/png;base64,' . base64_encode($image);
        }
        return null;
    }
}
?>
