<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login();

include 'includes/header.php';
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">QR Code do Cardápio</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">QR Code</li>
        </ul>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="text-lg fw-semibold mb-0">Seu Cardápio Digital</h6>
        </div>
        <div class="card-body p-24 text-center">
            <p class="text-secondary-light mb-4">
                Escaneie o QR Code abaixo para acessar o cardápio digital. <br>
                Você pode imprimir e colocar nas mesas.
            </p>

            <div class="d-flex justify-content-center mb-4">
                <div id="qrcode" class="p-3 bg-white border radius-8"></div>
            </div>

            <div class="d-flex justify-content-center gap-3">
                <a href="<?php echo SITE_URL; ?>" target="_blank" class="btn btn-primary-600 radius-8 px-20 py-11 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:link-bold-duotone"></iconify-icon>
                    Acessar Link
                </a>
                <button onclick="imprimirQRCode()" class="btn btn-outline-primary radius-8 px-20 py-11 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:printer-bold-duotone"></iconify-icon>
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    // URL do Cardápio
    const url = "<?php echo SITE_URL; ?>";

    // Gerar QR Code
    const qrcode = new QRCode(document.getElementById("qrcode"), {
        text: url,
        width: 256,
        height: 256,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });

    function imprimirQRCode() {
        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>QR Code Cardápio</title>');
        printWindow.document.write('<style>body{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;} h1{margin-bottom:20px;} img{max-width:100%;}</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h1>Acesse nosso Cardápio Digital</h1>');
        printWindow.document.write(document.getElementById('qrcode').innerHTML);
        printWindow.document.write('<p>' + url + '</p>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
</script>

<?php include 'includes/footer.php'; ?>
