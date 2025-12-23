<?php
require_once 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$msg_type = '';

// Buscar dados do produto
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "<script>window.location.href = 'produtos.php?mensagem=Produto não encontrado&tipo=danger';</script>";
    exit;
}

// Buscar categorias
$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Buscar todos os grupos adicionais ativos
$grupos_disponiveis = $pdo->query("SELECT * FROM grupos_adicionais WHERE ativo = 1 ORDER BY ordem ASC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Buscar grupos já associados a este produto
$stmt_grupos = $pdo->prepare("SELECT grupo_id FROM produto_grupos WHERE produto_id = ?");
$stmt_grupos->execute([$id]);
$grupos_associados = $stmt_grupos->fetchAll(PDO::FETCH_COLUMN);

// Função para download de imagem da web
function downloadWebImage($url, $prefix = 'prod_') {
    $upload_dir = __DIR__ . '/uploads/produtos/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $image_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || empty($image_content)) {
        return false;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_buffer($finfo, $image_content);
    finfo_close($finfo);
    
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    if (!isset($allowed_types[$mime_type])) {
        return false;
    }
    
    $extension = $allowed_types[$mime_type];
    $filename = $prefix . time() . '_' . rand(1000, 9999) . '.' . $extension;
    
    if (file_put_contents($upload_dir . $filename, $image_content) !== false) {
        return 'admin/uploads/produtos/' . $filename;
    }
    
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $categoria_id = (int)$_POST['categoria_id'];
    $descricao = $_POST['descricao'];
    $preco = str_replace(',', '.', str_replace('.', '', $_POST['preco']));
    $preco_promocional = !empty($_POST['preco_promocional']) ? str_replace(',', '.', str_replace('.', '', $_POST['preco_promocional'])) : NULL;
    $ordem = (int)$_POST['ordem'];
    $disponivel = isset($_POST['disponivel']) ? 1 : 0;

    // Upload de imagem (web ou local)
    $imagem_path = $produto['imagem_path'];
    
    // Primeiro verificar imagem da web
    if (!empty($_POST['web_image_url'])) {
        $new_path = downloadWebImage($_POST['web_image_url']);
        if ($new_path) {
            $imagem_path = $new_path;
        } else {
            $msg = 'Erro ao baixar imagem da web. Tente outra imagem.';
            $msg_type = 'danger';
        }
    }
    // Se não tem imagem web, verificar upload local
    elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = 'prod_' . time() . '.' . $ext;
        $upload_dir = __DIR__ . '/uploads/produtos/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_dir . $nome_arquivo)) {
            $imagem_path = 'admin/uploads/produtos/' . $nome_arquivo;
        }
    }

    if (empty($msg)) {
        $sql = "UPDATE produtos SET nome = ?, categoria_id = ?, descricao = ?, preco = ?, preco_promocional = ?, ordem = ?, disponivel = ?, imagem_path = ? WHERE id = ?";
        $params = [$nome, $categoria_id, $descricao, $preco, $preco_promocional, $ordem, $disponivel, $imagem_path, $id];
        
        try {
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                // Salvar associações de grupos adicionais
                $pdo->prepare("DELETE FROM produto_grupos WHERE produto_id = ?")->execute([$id]);
                
                if (!empty($_POST['grupos_adicionais'])) {
                    $stmt_grupo = $pdo->prepare("INSERT INTO produto_grupos (produto_id, grupo_id, ordem) VALUES (?, ?, ?)");
                    $ordem = 0;
                    foreach ($_POST['grupos_adicionais'] as $grupo_id) {
                        $stmt_grupo->execute([$id, (int)$grupo_id, $ordem++]);
                    }
                }
                
                echo "<script>window.location.href = 'produtos.php?mensagem=Produto atualizado com sucesso!&tipo=success';</script>";
                exit;
            } else {
                $msg = 'Erro ao atualizar produto.';
                $msg_type = 'danger';
            }
        } catch (PDOException $e) {
            $msg = 'Erro no banco de dados: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    }
}
?>

<style>
/* Estilos para busca de imagens */
.image-source-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}
.image-source-tabs .nav-link {
    padding: 10px 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    color: #495057;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s;
}
.image-source-tabs .nav-link.active {
    background: var(--primary-color, #7c3aed);
    color: white;
    border-color: var(--primary-color, #7c3aed);
}
.image-source-tabs .nav-link:hover:not(.active) {
    background: #e9ecef;
}
.tab-content > div {
    display: none;
}
.tab-content > div.active {
    display: block;
}
.image-search-box {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}
.image-search-box input {
    flex: 1;
}
.image-results {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}
.image-result-item {
    position: relative;
    cursor: pointer;
    border-radius: 8px;
    overflow: hidden;
    border: 3px solid transparent;
    transition: all 0.3s;
}
.image-result-item:hover {
    border-color: #7c3aed;
    transform: scale(1.05);
}
.image-result-item.selected {
    border-color: #10b981;
}
.image-result-item img {
    width: 100%;
    height: 100px;
    object-fit: cover;
}
.image-preview {
    margin-top: 15px;
    padding: 15px;
    background: #e8f5e9;
    border-radius: 8px;
    display: none;
    align-items: center;
    gap: 15px;
}
.image-preview.show {
    display: flex;
}
.image-preview img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}
.image-preview .info {
    flex: 1;
}
.image-preview .remove-btn {
    background: #ef4444;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
}
.loading-spinner {
    text-align: center;
    padding: 30px;
    color: #666;
}
.loading-spinner i {
    font-size: 24px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.current-image {
    margin-top: 10px;
    padding: 10px;
    background: #f0f0f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.current-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Editar Produto</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="produtos.php" class="hover-text-primary">Produtos</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Editar</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nome do Produto</label>
                        <input type="text" class="form-control radius-8" name="nome" id="productName" required value="<?php echo htmlspecialchars($produto['nome']); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Categoria</label>
                        <select class="form-select radius-8" name="categoria_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $produto['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Descrição</label>
                        <textarea class="form-control radius-8" name="descricao" rows="3"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Preço (R$)</label>
                        <input type="text" class="form-control radius-8" name="preco" required value="<?php echo number_format($produto['preco'], 2, ',', '.'); ?>" id="preco">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Preço Promocional (R$)</label>
                        <input type="text" class="form-control radius-8" name="preco_promocional" value="<?php echo $produto['preco_promocional'] ? number_format($produto['preco_promocional'], 2, ',', '.') : ''; ?>" id="preco_promocional">
                        <small class="text-secondary-light">Opcional</small>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Ordem de Exibição</label>
                        <input type="number" class="form-control radius-8" name="ordem" value="<?php echo $produto['ordem']; ?>">
                    </div>
                    
                    <!-- SEÇÃO: Imagem com tabs -->
                    <div class="col-md-12">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Imagem do Produto</label>
                        
                        <?php if ($produto['imagem_path']): ?>
                        <div class="current-image mb-3">
                            <img src="<?php echo str_replace('admin/', '', $produto['imagem_path']); ?>" 
                                 alt="Imagem atual"
                                 onerror="this.parentElement.style.display='none'">
                            <div>
                                <strong>Imagem Atual</strong>
                                <p class="text-muted mb-0 small">Selecione uma nova imagem para substituir</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Tabs para escolher fonte da imagem -->
                        <div class="image-source-tabs">
                            <button type="button" class="nav-link active" data-tab="upload">
                                <i class="fa-solid fa-upload me-2"></i>Upload Local
                            </button>
                            <button type="button" class="nav-link" data-tab="web">
                                <i class="fa-solid fa-globe me-2"></i>Buscar Online
                            </button>
                        </div>
                        
                        <div class="tab-content">
                            <!-- Tab: Upload Local -->
                            <div id="tab-upload" class="active">
                                <input type="file" class="form-control radius-8" name="imagem" accept="image/*" id="imageFile">
                                <small class="text-secondary-light">Formatos: JPG, PNG, GIF, WebP</small>
                            </div>
                            
                            <!-- Tab: Buscar Online -->
                            <div id="tab-web">
                                <div class="image-search-box">
                                    <input type="text" class="form-control radius-8" id="imageSearchTerm" placeholder="Ex: pizza calabresa, hamburguer artesanal..." value="<?php echo htmlspecialchars($produto['nome']); ?>">
                                    <button type="button" class="btn btn-primary radius-8" id="searchImagesBtn">
                                        <i class="fa-solid fa-search me-2"></i>Buscar
                                    </button>
                                </div>
                                
                                <div id="imageSearchResults" class="image-results" style="display: none;">
                                    <!-- Resultados aparecem aqui -->
                                </div>
                                
                                <div id="searchLoading" class="loading-spinner" style="display: none;">
                                    <i class="fa-solid fa-spinner"></i>
                                    <p>Buscando imagens...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview da imagem selecionada -->
                        <div id="imagePreview" class="image-preview">
                            <img id="previewImg" src="" alt="Preview" onerror="this.style.display='none'">
                            <div class="info">
                                <strong>Nova Imagem Selecionada</strong>
                                <p id="previewInfo" class="text-muted mb-0"></p>
                            </div>
                            <button type="button" class="remove-btn" id="removeImageBtn">
                                <i class="fa-solid fa-times"></i> Remover
                            </button>
                        </div>
                        
                        <!-- Campo oculto para URL da imagem web -->
                        <input type="hidden" name="web_image_url" id="webImageUrl">
                    </div>
                    
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="disponivel" name="disponivel" <?php echo $produto['disponivel'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-medium text-secondary-light" for="disponivel">Produto Disponível</label>
                        </div>
                    </div>
                    
                    <!-- Grupos de Adicionais -->
                    <?php if (!empty($grupos_disponiveis)): ?>
                    <div class="col-md-12">
                        <hr class="my-4">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            <i class="fas fa-layer-group me-1"></i> Grupos de Adicionais
                        </label>
                        <small class="text-muted d-block mb-3">Selecione quais grupos de opcionais estarão disponíveis para este produto.</small>
                        
                        <div class="row g-3">
                            <?php foreach ($grupos_disponiveis as $grupo): ?>
                            <div class="col-md-4 col-lg-3">
                                <div class="form-check p-3 border radius-8 h-100 <?php echo in_array($grupo['id'], $grupos_associados) ? 'border-primary bg-primary-light' : ''; ?>">
                                    <input class="form-check-input" type="checkbox" 
                                           name="grupos_adicionais[]" 
                                           value="<?php echo $grupo['id']; ?>" 
                                           id="grupo_<?php echo $grupo['id']; ?>"
                                           <?php echo in_array($grupo['id'], $grupos_associados) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-medium" for="grupo_<?php echo $grupo['id']; ?>">
                                        <?php echo htmlspecialchars($grupo['nome']); ?>
                                    </label>
                                    <small class="d-block text-muted">
                                        <?php echo $grupo['obrigatorio'] ? '<span class="text-danger">Obrigatório</span>' : 'Opcional'; ?> • 
                                        <?php echo $grupo['tipo_escolha'] == 'unico' ? 'Única escolha' : 'Múltipla (' . $grupo['minimo_escolha'] . '-' . $grupo['maximo_escolha'] . ')'; ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-12 d-flex justify-content-end gap-2 mt-4">
                        <a href="produtos.php" class="btn btn-outline-secondary radius-8 px-20 py-11">Cancelar</a>
                        <button type="submit" class="btn btn-primary radius-8 px-20 py-11">Salvar Alterações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Máscara simples para moeda
    function maskCurrency(input) {
        let value = input.value.replace(/\D/g, '');
        value = (value / 100).toFixed(2) + '';
        value = value.replace('.', ',');
        value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        input.value = value;
    }

    document.getElementById('preco').addEventListener('input', function() {
        maskCurrency(this);
    });
    
    document.getElementById('preco_promocional').addEventListener('input', function() {
        maskCurrency(this);
    });
    
    // Sistema de tabs para fonte da imagem
    document.querySelectorAll('.image-source-tabs .nav-link').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.image-source-tabs .nav-link').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content > div').forEach(c => c.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });
    
    // Buscar imagens online
    document.getElementById('searchImagesBtn').addEventListener('click', searchImages);
    document.getElementById('imageSearchTerm').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchImages();
        }
    });
    
    function searchImages() {
        const term = document.getElementById('imageSearchTerm').value.trim();
        if (!term) {
            alert('Digite um termo para buscar');
            return;
        }
        
        const resultsDiv = document.getElementById('imageSearchResults');
        const loadingDiv = document.getElementById('searchLoading');
        
        resultsDiv.style.display = 'none';
        loadingDiv.style.display = 'block';
        
        fetch('../api/search_images.php?q=' + encodeURIComponent(term))
            .then(response => response.json())
            .then(data => {
                loadingDiv.style.display = 'none';
                
                if (data.success && data.images.length > 0) {
                    resultsDiv.innerHTML = '';
                    resultsDiv.style.display = 'grid';
                    
                    data.images.forEach((image, index) => {
                        const div = document.createElement('div');
                        div.className = 'image-result-item';
                        const thumbUrl = image.thumb || image.url;
                        div.innerHTML = `<img src="${thumbUrl}" alt="${image.title}" loading="lazy" onerror="this.parentElement.style.display='none'">`;  
                        div.addEventListener('click', () => selectImage(image.url, thumbUrl, image.title));
                        resultsDiv.appendChild(div);
                    });
                } else {
                    resultsDiv.style.display = 'grid';
                    resultsDiv.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px; color: #666;">Nenhuma imagem encontrada. Tente outros termos.</div>';
                }
            })
            .catch(error => {
                loadingDiv.style.display = 'none';
                console.error('Erro:', error);
                alert('Erro ao buscar imagens. Tente novamente.');
            });
    }
    
    function selectImage(url, thumbUrl, title) {
        document.querySelectorAll('.image-result-item').forEach(item => {
            item.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        // Mostrar preview (usar thumb que já funcionou no grid)
        const previewImg = document.getElementById('previewImg');
        previewImg.style.display = 'block';
        previewImg.src = thumbUrl; // Usar thumb URL que já carregou no grid
        document.getElementById('previewInfo').textContent = 'Imagem da web: ' + title;
        document.getElementById('imagePreview').classList.add('show');
        
        // Guardar URL original para download no backend
        document.getElementById('webImageUrl').value = url;
        document.getElementById('imageFile').value = '';
    }
    
    document.getElementById('removeImageBtn').addEventListener('click', function() {
        document.getElementById('imagePreview').classList.remove('show');
        document.getElementById('webImageUrl').value = '';
        document.getElementById('previewImg').src = '';
        document.querySelectorAll('.image-result-item').forEach(item => {
            item.classList.remove('selected');
        });
    });
    
    document.getElementById('imageFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('previewInfo').textContent = 'Arquivo local: ' + file.name;
                document.getElementById('imagePreview').classList.add('show');
            };
            reader.readAsDataURL(file);
            
            document.getElementById('webImageUrl').value = '';
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>