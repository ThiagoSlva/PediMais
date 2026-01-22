<?php
require_once 'includes/header.php';
verificar_permissao();

// Check if AI is configured (check new ai_config table first, fallback to gemini_config)
$ai_config = null;
$ai_ready = false;
$provider_name = 'IA';

try {
    $ai_config = $pdo->query("SELECT * FROM ai_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    if ($ai_config) {
        $provider = $ai_config['provider'] ?? 'gemini';
        if ($provider === 'openai') {
            $ai_ready = !empty($ai_config['openai_api_key']) && $ai_config['ativo'];
            $provider_name = 'OpenAI';
        } else {
            $ai_ready = !empty($ai_config['gemini_api_key']) && $ai_config['ativo'];
            $provider_name = 'Gemini';
        }
    }
} catch (PDOException $e) {
    // Fallback to old gemini_config
    $gemini_config = $pdo->query("SELECT * FROM gemini_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    $ai_ready = $gemini_config && !empty($gemini_config['api_key']) && $gemini_config['ativo'];
    $provider_name = 'Gemini';
}
?>

<style>
.upload-zone {
    border: 3px dashed #dee2e6;
    border-radius: 16px;
    padding: 60px 40px;
    text-align: center;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    cursor: pointer;
}
.upload-zone:hover, .upload-zone.dragover {
    border-color: #7c3aed;
    background: linear-gradient(135deg, #f3e8ff 0%, #ddd6fe 100%);
    transform: scale(1.01);
}
.upload-zone.has-file {
    border-color: #10b981;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
}
.upload-icon {
    font-size: 80px;
    color: #7c3aed;
    margin-bottom: 20px;
}
.preview-image {
    max-width: 100%;
    max-height: 400px;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}
.step-indicator {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
}
.step {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    border-radius: 50px;
    background: #f1f5f9;
    color: #64748b;
    font-weight: 600;
    transition: all 0.3s;
}
.step.active {
    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
}
.step.completed {
    background: #10b981;
    color: white;
}
.step-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.items-preview {
    max-height: 500px;
    overflow-y: auto;
}
.item-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.2s;
}
.item-card:hover {
    border-color: #7c3aed;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.1);
}
.size-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    margin-right: 8px;
    margin-bottom: 8px;
}
.size-badge.size-m { background: #dbeafe; color: #1e40af; }
.size-badge.size-g { background: #fef3c7; color: #92400e; }
.size-badge.size-p { background: #d1fae5; color: #065f46; }
.size-badge.size-unico { background: #e0e7ff; color: #3730a3; }
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}
.loading-overlay.show {
    opacity: 1;
    visibility: visible;
}
.loading-content {
    background: white;
    padding: 40px 60px;
    border-radius: 20px;
    text-align: center;
}
.loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid #e2e8f0;
    border-top-color: #7c3aed;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.gemini-icon {
    background: linear-gradient(135deg, #4285f4, #ea4335, #fbbc05, #34a853);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.success-animation {
    animation: successPulse 0.5s ease-out;
}
@keyframes successPulse {
    0% { transform: scale(0.8); opacity: 0; }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">
            <iconify-icon icon="ri:gemini-fill" class="gemini-icon me-2"></iconify-icon>
            Importar Cardápio com IA
        </h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Importar Cardápio</li>
        </ul>
    </div>

    <?php if (!$ai_ready): ?>
    <div class="alert alert-warning d-flex align-items-center gap-3" role="alert">
        <iconify-icon icon="solar:danger-triangle-bold" style="font-size: 32px;"></iconify-icon>
        <div>
            <h6 class="mb-1">API de IA não configurada</h6>
            <p class="mb-0">Configure sua API Key do Gemini ou OpenAI para usar esta funcionalidade.</p>
            <a href="gemini_config.php" class="btn btn-warning btn-sm mt-2">
                <iconify-icon icon="solar:settings-bold" class="me-1"></iconify-icon>
                Configurar IA
            </a>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Provider Badge -->
    <div class="text-end mb-3">
        <span class="badge bg-primary-subtle text-primary-main px-3 py-2">
            <iconify-icon icon="<?php echo $provider_name === 'OpenAI' ? 'simple-icons:openai' : 'ri:gemini-fill'; ?>" class="me-1"></iconify-icon>
            Usando: <?php echo $provider_name; ?>
        </span>
    </div>

    <!-- Step Indicators -->
    <div class="step-indicator">
        <div class="step active" id="step1">
            <span class="step-number">1</span>
            <span>Upload da Imagem</span>
        </div>
        <div class="step" id="step2">
            <span class="step-number">2</span>
            <span>Análise IA</span>
        </div>
        <div class="step" id="step3">
            <span class="step-number">3</span>
            <span>Confirmar Produtos</span>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Step 1: Upload -->
            <div class="card radius-12 mb-4" id="uploadSection">
                <div class="card-body p-24">
                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('menuImage').click()">
                        <div id="uploadPlaceholder">
                            <iconify-icon icon="solar:gallery-add-bold-duotone" class="upload-icon"></iconify-icon>
                            <h4 class="fw-semibold mb-2">Arraste a imagem do cardápio aqui</h4>
                            <p class="text-secondary-light mb-3">ou clique para selecionar</p>
                            <span class="badge bg-light text-dark px-3 py-2">
                                Formatos: JPG, PNG, WebP
                            </span>
                        </div>
                        <div id="imagePreview" style="display: none;">
                            <img id="previewImg" class="preview-image" alt="Preview">
                        </div>
                    </div>
                    <input type="file" id="menuImage" accept="image/*" style="display: none;">
                    
                    <div id="analyzeSection" class="text-center mt-4" style="display: none;">
                        <button type="button" class="btn btn-primary btn-lg radius-8 px-5" onclick="openCategoryModal()">
                            <iconify-icon icon="ri:gemini-fill" class="me-2"></iconify-icon>
                            Analisar com Gemini AI
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Results -->
            <div class="card radius-12" id="resultsSection" style="display: none;">
                <div class="card-header bg-success-subtle border-bottom-0 py-16 px-24">
                    <h5 class="fw-semibold mb-0 text-success-main">
                        <iconify-icon icon="solar:check-circle-bold" class="me-2"></iconify-icon>
                        <span id="resultsTitle">Produtos Identificados</span>
                    </h5>
                </div>
                <div class="card-body p-24">
                    <div id="categoryInfo" class="mb-4 p-3 bg-light radius-8">
                        <strong>Categoria:</strong> <span id="categoryNameDisplay"></span>
                    </div>
                    
                    <div class="items-preview" id="itemsList">
                        <!-- Items will be rendered here -->
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                        <div>
                            <span class="badge bg-primary-subtle text-primary-main px-3 py-2 me-2" id="totalItemsBadge">
                                0 produtos
                            </span>
                            <span class="badge bg-info-subtle text-info-main px-3 py-2" id="totalSizesBadge">
                                0 variações de tamanho
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary radius-8" onclick="resetImport()">
                                <iconify-icon icon="solar:restart-bold" class="me-1"></iconify-icon>
                                Nova Importação
                            </button>
                            <button type="button" class="btn btn-success btn-lg radius-8 px-4" onclick="createProducts()">
                                <iconify-icon icon="solar:check-circle-bold" class="me-2"></iconify-icon>
                                Criar Produtos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Section -->
            <div class="card radius-12" id="successSection" style="display: none;">
                <div class="card-body p-24 text-center">
                    <div class="success-animation">
                        <iconify-icon icon="solar:check-circle-bold" style="font-size: 100px; color: #10b981;"></iconify-icon>
                    </div>
                    <h3 class="fw-bold text-success mt-4" id="successTitle">Importação Concluída!</h3>
                    <p class="text-secondary-light mb-4" id="successMessage"></p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="produtos.php" class="btn btn-primary radius-8 px-4">
                            <iconify-icon icon="solar:bag-5-bold" class="me-2"></iconify-icon>
                            Ver Produtos
                        </a>
                        <button type="button" class="btn btn-outline-primary radius-8 px-4" onclick="resetImport()">
                            <iconify-icon icon="solar:add-circle-bold" class="me-2"></iconify-icon>
                            Nova Importação
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content radius-12">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold">
                    <iconify-icon icon="solar:folder-add-bold-duotone" class="text-primary me-2"></iconify-icon>
                    Nome da Categoria
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary-light mb-3">
                    Digite o nome da categoria onde os produtos serão criados.
                </p>
                <input type="text" id="categoryName" class="form-control form-control-lg radius-8 mb-3" 
                       placeholder="Ex: Cardápio de Quinta, Pratos do Dia...">
                
                <div class="form-check form-switch mt-3 p-3 bg-light radius-8">
                    <input class="form-check-input" type="checkbox" id="buscarImagens" checked>
                    <label class="form-check-label fw-medium" for="buscarImagens">
                        <iconify-icon icon="solar:gallery-bold" class="me-1 text-primary"></iconify-icon>
                        Buscar imagens automaticamente
                    </label>
                    <small class="d-block text-secondary-light mt-1">Pesquisa fotos dos pratos na internet</small>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary radius-8" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary radius-8" onclick="startAnalysis()">
                    <iconify-icon icon="ri:gemini-fill" class="me-1"></iconify-icon>
                    Iniciar Análise
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner mx-auto mb-4"></div>
        <h5 class="fw-semibold" id="loadingTitle">Analisando cardápio...</h5>
        <p class="text-secondary-light mb-0" id="loadingMessage">O Gemini está lendo a imagem</p>
    </div>
</div>

<script>
let selectedFile = null;
let extractedItems = [];
let categoryName = '';
let buscarImagens = true;

// Drag and drop
const uploadZone = document.getElementById('uploadZone');
const menuImage = document.getElementById('menuImage');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    uploadZone.addEventListener(eventName, () => uploadZone.classList.add('dragover'), false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadZone.addEventListener(eventName, () => uploadZone.classList.remove('dragover'), false);
});

uploadZone.addEventListener('drop', handleDrop, false);
menuImage.addEventListener('change', handleFileSelect);

function handleDrop(e) {
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
}

function handleFileSelect(e) {
    if (e.target.files.length > 0) {
        handleFile(e.target.files[0]);
    }
}

function handleFile(file) {
    if (!file.type.startsWith('image/')) {
        alert('Por favor, selecione uma imagem');
        return;
    }
    
    selectedFile = file;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('uploadPlaceholder').style.display = 'none';
        document.getElementById('imagePreview').style.display = 'block';
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('analyzeSection').style.display = 'block';
        uploadZone.classList.add('has-file');
    };
    reader.readAsDataURL(file);
}

function openCategoryModal() {
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

function startAnalysis() {
    categoryName = document.getElementById('categoryName').value.trim();
    
    if (!categoryName) {
        alert('Por favor, digite o nome da categoria');
        return;
    }
    
    bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
    
    // Show loading
    showLoading('Analisando cardápio...', 'O Gemini está identificando os pratos');
    
    // Update steps
    document.getElementById('step1').classList.remove('active');
    document.getElementById('step1').classList.add('completed');
    document.getElementById('step2').classList.add('active');
    
    // Upload and analyze
    const formData = new FormData();
    formData.append('menu_image', selectedFile);
    
    fetch('../api/gemini_analyze_menu.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            extractedItems = data.items;
            showResults();
        } else {
            alert('Erro: ' + data.message);
            resetSteps();
        }
    })
    .catch(err => {
        hideLoading();
        alert('Erro de conexão: ' + err.message);
        resetSteps();
    });
}

function showResults() {
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step2').classList.add('completed');
    document.getElementById('step3').classList.add('active');
    
    document.getElementById('uploadSection').style.display = 'none';
    document.getElementById('resultsSection').style.display = 'block';
    document.getElementById('categoryNameDisplay').textContent = categoryName;
    
    let totalSizes = 0;
    let html = '';
    
    extractedItems.forEach((item, index) => {
        totalSizes += item.tamanhos.length;
        
        let sizesHtml = '';
        item.tamanhos.forEach(tam => {
            const sizeClass = tam.tamanho.toLowerCase() === 'm' ? 'size-m' : 
                             tam.tamanho.toLowerCase() === 'g' ? 'size-g' :
                             tam.tamanho.toLowerCase() === 'p' ? 'size-p' : 'size-unico';
            sizesHtml += `<span class="size-badge ${sizeClass}">
                ${tam.tamanho}: R$ ${parseFloat(tam.preco).toFixed(2).replace('.', ',')}
            </span>`;
        });
        
        html += `
            <div class="item-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-semibold mb-1">${item.nome}</h6>
                        ${item.descricao ? `<p class="text-secondary-light small mb-2">${item.descricao}</p>` : ''}
                        <div>${sizesHtml}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger radius-8" onclick="removeItem(${index})">
                        <iconify-icon icon="solar:trash-bin-2-bold"></iconify-icon>
                    </button>
                </div>
            </div>
        `;
    });
    
    document.getElementById('itemsList').innerHTML = html;
    document.getElementById('totalItemsBadge').textContent = extractedItems.length + ' produtos';
    document.getElementById('totalSizesBadge').textContent = totalSizes + ' variações de tamanho';
}

function removeItem(index) {
    extractedItems.splice(index, 1);
    showResults();
}

function createProducts() {
    if (extractedItems.length === 0) {
        alert('Nenhum produto para criar');
        return;
    }
    
    buscarImagens = document.getElementById('buscarImagens')?.checked ?? true;
    const loadingMsg = buscarImagens ? 'Criando produtos e buscando imagens...' : 'Criando produtos...';
    showLoading(loadingMsg, buscarImagens ? 'Isso pode levar alguns segundos' : 'Salvando no banco de dados');
    
    fetch('../api/gemini_create_products.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            categoria_nome: categoryName,
            items: extractedItems,
            buscar_imagens: buscarImagens
        })
    })
    .then(r => r.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showSuccess(data);
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(err => {
        hideLoading();
        alert('Erro: ' + err.message);
    });
}

function showSuccess(data) {
    document.getElementById('step3').classList.remove('active');
    document.getElementById('step3').classList.add('completed');
    
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('successSection').style.display = 'block';
    
    let msg = `<strong>${data.produtos_criados}</strong> produtos criados na categoria <strong>${data.categoria_nome}</strong>`;
    if (data.opcoes_criadas > 0) {
        msg += `<br>com <strong>${data.opcoes_criadas}</strong> variações de tamanho`;
    }
    if (data.imagens_encontradas > 0) {
        msg += `<br><iconify-icon icon="solar:gallery-bold" class="text-success"></iconify-icon> <strong>${data.imagens_encontradas}</strong> imagens encontradas`;
    }
    document.getElementById('successMessage').innerHTML = msg;
}

function showLoading(title, message) {
    document.getElementById('loadingTitle').textContent = title;
    document.getElementById('loadingMessage').textContent = message;
    document.getElementById('loadingOverlay').classList.add('show');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

function resetSteps() {
    document.querySelectorAll('.step').forEach(el => el.classList.remove('active', 'completed'));
    document.getElementById('step1').classList.add('active');
}

function resetImport() {
    selectedFile = null;
    extractedItems = [];
    categoryName = '';
    
    document.getElementById('uploadPlaceholder').style.display = 'block';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('analyzeSection').style.display = 'none';
    document.getElementById('uploadSection').style.display = 'block';
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('successSection').style.display = 'none';
    document.getElementById('menuImage').value = '';
    document.getElementById('categoryName').value = '';
    uploadZone.classList.remove('has-file');
    
    resetSteps();
}
</script>

<?php require_once 'includes/footer.php'; ?>
