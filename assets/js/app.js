// Estado da Aplicação
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let currentProduct = null;

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
    setupEventListeners();
});

// Event Listeners
function setupEventListeners() {
    // Accordion
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            content.classList.toggle('active');
        });
    });

    // Categorias
    document.querySelectorAll('.category').forEach(cat => {
        cat.addEventListener('click', () => {
            const catId = cat.dataset.category;
            const item = document.querySelector(`.accordion-item[data-category-id="${catId}"]`);
            if (item) {
                item.scrollIntoView({ behavior: 'smooth' });
                const header = item.querySelector('.accordion-header');
                const content = header.nextElementSibling;
                if (!content.classList.contains('active')) {
                    header.click();
                }
            }
        });
    });

    // Botão Flutuante
    document.getElementById('floating-cart').addEventListener('click', () => {
        abrirModal('modal-carrinho');
        renderCart();
    });
}

// Funções de Modal
function abrirModal(id) {
    document.getElementById(id).classList.add('active');
}

function fecharModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Produto
function abrirProduto(id) {
    fetch(`api/get_produto.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            currentProduct = data;
            renderModalProduto(data);
            abrirModal('modal-produto');
        })
        .catch(err => console.error('Erro:', err));
}

function renderModalProduto(produto) {
    const modalContent = document.getElementById('modal-produto-content');

    let html = `
        <div style="padding: 0;">
            ${produto.imagem_path ? `<img src="${produto.imagem_path}" style="width: 100%; height: 200px; object-fit: cover;">` : ''}
            <div style="padding: 20px;">
                <h2>${produto.nome}</h2>
                <p style="color: #666;">${produto.descricao}</p>
                <h3 style="color: var(--primary-color); margin: 10px 0;">${produto.preco_formatado}</h3>
                
                <div style="margin-bottom: 15px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Observações:</label>
                    <textarea id="prod-obs" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;" placeholder="Ex: Sem cebola, bem passado..."></textarea>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px; border: 1px solid #ddd; padding: 5px 10px; border-radius: 5px;">
                        <button onclick="alterarQtd(-1)" style="background: none; border: none; font-size: 1.2rem;">-</button>
                        <span id="prod-qtd" style="font-weight: bold;">1</span>
                        <button onclick="alterarQtd(1)" style="background: none; border: none; font-size: 1.2rem;">+</button>
                    </div>
                    <button onclick="adicionarAoCarrinho()" style="flex: 1; margin-left: 15px; padding: 12px; background: var(--primary-color); color: white; border: none; border-radius: 5px; font-weight: bold;">
                        Adicionar ${produto.preco_formatado}
                    </button>
                </div>
            </div>
            <button onclick="fecharModal('modal-produto')" style="position: absolute; top: 10px; right: 10px; background: white; border: none; border-radius: 50%; width: 30px; height: 30px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">&times;</button>
        </div>
    `;
    modalContent.innerHTML = html;
}

function alterarQtd(delta) {
    const el = document.getElementById('prod-qtd');
    let qtd = parseInt(el.innerText);
    qtd += delta;
    if (qtd < 1) qtd = 1;
    el.innerText = qtd;
}

// Carrinho
function adicionarAoCarrinho() {
    const qtd = parseInt(document.getElementById('prod-qtd').innerText);
    const obs = document.getElementById('prod-obs').value;

    const item = {
        id: currentProduct.id,
        nome: currentProduct.nome,
        preco: parseFloat(currentProduct.preco), // Assumindo que vem limpo ou tratar
        quantidade: qtd,
        observacoes: obs,
        adicionais: [] // Futuro: suportar adicionais
    };

    // Verificar se já existe igual no carrinho
    const existing = cart.find(i => i.id === item.id && i.observacoes === item.observacoes);
    if (existing) {
        existing.quantidade += qtd;
    } else {
        cart.push(item);
    }

    salvarCarrinho();
    updateCartUI();
    fecharModal('modal-produto');

    // Feedback visual (opcional)
    alert('Produto adicionado!');
}

function removerDoCarrinho(index) {
    cart.splice(index, 1);
    salvarCarrinho();
    updateCartUI();
    renderCart(); // Re-renderizar modal se aberto
}

function salvarCarrinho() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function updateCartUI() {
    const total = cart.reduce((acc, item) => acc + (item.preco * item.quantidade), 0);
    const count = cart.reduce((acc, item) => acc + item.quantidade, 0);

    document.getElementById('cart-total').innerText = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('cart-count').innerText = count;

    const floatBtn = document.getElementById('floating-cart');
    if (count > 0) {
        floatBtn.style.display = 'flex';
    } else {
        floatBtn.style.display = 'none';
    }
}

function renderCart() {
    const container = document.getElementById('cart-items');
    container.innerHTML = '';

    if (cart.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999;">Seu carrinho está vazio.</p>';
        return;
    }

    let total = 0;
    cart.forEach((item, index) => {
        const itemTotal = item.preco * item.quantidade;
        total += itemTotal;

        const div = document.createElement('div');
        div.style.cssText = 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f5f5f5; padding-bottom: 10px;';
        div.innerHTML = `
            <div>
                <div style="font-weight: bold;">${item.quantidade}x ${item.nome}</div>
                ${item.observacoes ? `<div style="font-size: 0.8rem; color: #666;">Obs: ${item.observacoes}</div>` : ''}
                <div style="font-size: 0.9rem; color: var(--primary-color);">R$ ${item.preco.toFixed(2).replace('.', ',')}</div>
            </div>
            <button onclick="removerDoCarrinho(${index})" style="color: red; background: none; border: none;"><i class="fa-solid fa-trash"></i></button>
        `;
        container.appendChild(div);
    });

    document.getElementById('cart-total-modal').innerText = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// Checkout
function abrirCheckout() {
    fecharModal('modal-carrinho');
    abrirModal('modal-checkout');
}

function toggleEndereco() {
    const tipo = document.getElementById('tipo-entrega').value;
    const container = document.getElementById('endereco-container');
    if (tipo === 'delivery') {
        container.style.display = 'block';
        document.getElementById('cliente-endereco').required = true;
    } else {
        container.style.display = 'none';
        document.getElementById('cliente-endereco').required = false;
    }
}

function toggleTroco() {
    const forma = document.getElementById('forma-pagamento').value;
    const container = document.getElementById('troco-container');
    if (forma === 'dinheiro') {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

function enviarPedido() {
    const nome = document.getElementById('cliente-nome').value;
    const telefone = document.getElementById('cliente-telefone').value;
    const tipoEntrega = document.getElementById('tipo-entrega').value;
    const endereco = document.getElementById('cliente-endereco').value;
    const pagamento = document.getElementById('forma-pagamento').value;
    const troco = document.getElementById('troco-para').value;
    const obs = document.getElementById('observacoes').value;

    if (!nome || !telefone) {
        alert('Por favor, preencha seu nome e telefone.');
        return;
    }
    if (tipoEntrega === 'delivery' && !endereco) {
        alert('Por favor, informe o endereço de entrega.');
        return;
    }

    const total = cart.reduce((acc, item) => acc + (item.preco * item.quantidade), 0);

    const pedido = {
        cliente: {
            nome: nome,
            telefone: telefone,
            endereco: endereco
        },
        itens: cart,
        total: total,
        tipo_entrega: tipoEntrega,
        pagamento: pagamento,
        troco_para: troco,
        observacoes: obs
    };

    // Loading state
    const btn = document.querySelector('#modal-checkout button');
    const originalText = btn.innerText;
    btn.innerText = 'Enviando...';
    btn.disabled = true;

    fetch('api/checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(pedido)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cart = [];
                salvarCarrinho();
                updateCartUI();
                fecharModal('modal-checkout');

                document.getElementById('sucesso-pedido-id').innerText = data.codigo_pedido;

                // PIX Logic
                if (data.pagamento_online && data.pix_data) {
                    document.getElementById('pix-container').style.display = 'block';
                    document.getElementById('pix-qrcode').src = `data:image/png;base64,${data.pix_data.qr_code_base64}`;
                    document.getElementById('pix-copia-cola').value = data.pix_data.qr_code;
                } else {
                    document.getElementById('pix-container').style.display = 'none';
                }

                abrirModal('modal-sucesso');
            } else {
                alert('Erro ao enviar pedido: ' + data.error);
            }
        })
        .catch(err => {
            console.error('Erro:', err);
            alert('Erro de conexão ao enviar pedido.');
        })
        .finally(() => {
            btn.innerText = originalText;
            btn.disabled = false;
        });
}

function copiarPix() {
    const copyText = document.getElementById("pix-copia-cola");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Código PIX copiado!");
}
