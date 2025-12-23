// Estado da Aplicação
window.carrinho = JSON.parse(localStorage.getItem('cardapio_carrinho_v1')) || [];
window.estabelecimentoAberto = true; // Será atualizado pelo PHP/HTML
window.produtoAtual = null;
window.meioAMeioAtivo = false;
window.primeiroSabor = null;

// Constantes
const STORAGE_KEY_CARRINHO = 'cardapio_carrinho_v1';

// Inicialização
document.addEventListener('DOMContentLoaded', function () {
    // Accordion
    const headers = document.querySelectorAll(".accordion-header");

    headers.forEach((h, index) => {
        h.addEventListener("click", function (e) {
            const content = h.nextElementSibling;
            const icon = h.querySelector("i");

            if (!content) return;

            // Fechar outros
            headers.forEach(other => {
                if (other !== h) {
                    const otherContent = other.nextElementSibling;
                    const otherIcon = other.querySelector("i");
                    if (otherContent) {
                        otherContent.classList.remove('active');
                        otherContent.style.display = "none";
                    }
                    if (otherIcon) {
                        otherIcon.style.transform = "rotate(0deg)";
                    }
                }
            });

            // Toggle atual
            const isOpen = content.classList.contains('active');
            if (isOpen) {
                content.classList.remove('active');
                content.style.display = "none";
                if (icon) icon.style.transform = "rotate(0deg)";
            } else {
                content.classList.add('active');
                content.style.display = "block";
                if (icon) icon.style.transform = "rotate(180deg)";
            }
        });
    });

    // Abrir primeira categoria por padrão
    if (headers.length > 0) {
        setTimeout(() => {
            headers[0].click();
        }, 100);
    }

    // Categorias scroll
    const categoryElements = document.querySelectorAll('.category');
    categoryElements.forEach((cat, index) => {
        cat.addEventListener('click', function () {
            categoryElements.forEach(c => c.classList.remove('active'));
            cat.classList.add('active');

            const catId = cat.dataset.category;
            const accordionItem = document.querySelector(`.accordion-item[data-category-id="${catId}"]`);

            if (accordionItem) {
                setTimeout(() => {
                    accordionItem.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
                    setTimeout(() => {
                        const header = accordionItem.querySelector('.accordion-header');
                        if (header) {
                            const content = header.nextElementSibling;
                            if (content && !content.classList.contains('active')) {
                                header.click();
                            }
                        }
                    }, 400);
                }, 300);
            }
        });
    });

    // Atualizar UI inicial
    atualizarCarrinho();
});

// Funções de Carrinho
function salvarCarrinhoSalvo() {
    localStorage.setItem(STORAGE_KEY_CARRINHO, JSON.stringify(window.carrinho));
}

function calcularPrecoTotalItem(item) {
    if (!item || typeof item !== 'object') return 0;
    let total = item.preco_base || item.preco || 0;
    if (item.adicionais && Array.isArray(item.adicionais)) {
        item.adicionais.forEach(ad => {
            total += parseFloat(ad.preco || 0);
        });
    }
    return total;
}

function atualizarCarrinho() {
    const totalQtd = window.carrinho.reduce((sum, item) => sum + item.quantidade, 0);
    atualizarCarrinhoFlutuante();
}

function atualizarCarrinhoFlutuante() {
    const carrinhoEl = document.getElementById('carrinho-flutuante');
    const qtdEl = document.getElementById('carrinho-qtd');
    const totalEl = document.getElementById('carrinho-total-valor');
    const listaEl = document.getElementById('carrinho-itens-lista');

    const carrinhoMobileBtn = document.getElementById('carrinho-flutuante-mobile');
    const badgeMobile = document.getElementById('carrinho-badge-mobile');

    let qtdTotal = 0;
    let valorTotal = 0;

    window.carrinho.forEach(item => {
        qtdTotal += item.quantidade;
        valorTotal += calcularPrecoTotalItem(item) * item.quantidade;
    });

    // Desktop
    if (carrinhoEl && qtdEl && totalEl && listaEl) {
        if (window.carrinho.length === 0) {
            carrinhoEl.style.display = 'none';
        } else {
            carrinhoEl.style.display = 'block';
            qtdEl.textContent = qtdTotal;
            totalEl.textContent = valorTotal.toFixed(2).replace('.', ',');

            listaEl.innerHTML = '';
            window.carrinho.forEach((item, index) => {
                const totalItem = calcularPrecoTotalItem(item) * item.quantidade;
                const div = document.createElement('div');
                div.className = 'carrinho-item';
                div.innerHTML = `
          <div class="carrinho-item-info">
            <div class="carrinho-item-nome">${item.quantidade}x ${item.nome}</div>
            <div class="carrinho-item-preco">R$ ${totalItem.toFixed(2).replace('.', ',')}</div>
          </div>
          <button class="carrinho-item-remover" onclick="removerItemCarrinho(${index})">
            <i class="fa-solid fa-trash"></i>
          </button>
        `;
                listaEl.appendChild(div);
            });
        }
    }

    // Mobile
    if (carrinhoMobileBtn && badgeMobile) {
        if (window.carrinho.length === 0) {
            carrinhoMobileBtn.style.display = 'none';
        } else {
            if (window.innerWidth <= 1024) {
                carrinhoMobileBtn.style.display = 'flex';
            }
            badgeMobile.textContent = qtdTotal;
            badgeMobile.classList.add('ativo');
        }
    }

    atualizarCarrinhoMobile();
}

function atualizarCarrinhoMobile() {
    const listaMobileEl = document.getElementById('carrinho-mobile-itens-lista');
    const resumoMobileEl = document.getElementById('carrinho-mobile-resumo');

    if (!listaMobileEl) return;

    if (window.carrinho.length === 0) {
        listaMobileEl.innerHTML = '<p style="text-align:center; padding:20px;">Carrinho vazio</p>';
        if (resumoMobileEl) resumoMobileEl.style.display = 'none';
        return;
    }

    let valorTotal = 0;
    listaMobileEl.innerHTML = '';

    window.carrinho.forEach((item, index) => {
        const totalItem = calcularPrecoTotalItem(item) * item.quantidade;
        valorTotal += totalItem;

        const div = document.createElement('div');
        div.className = 'carrinho-mobile-item';
        div.style.cssText = 'display:flex; justify-content:space-between; padding:10px; border-bottom:1px solid #eee;';
        div.innerHTML = `
      <div>
        <div>${item.quantidade}x ${item.nome}</div>
        <div style="color:var(--primary-color); font-weight:bold;">R$ ${totalItem.toFixed(2).replace('.', ',')}</div>
      </div>
      <button onclick="removerItemCarrinho(${index})" style="color:red; border:none; background:none;">
        <i class="fa-solid fa-trash"></i>
      </button>
    `;
        listaMobileEl.appendChild(div);
    });

    if (resumoMobileEl) {
        resumoMobileEl.style.display = 'block';
        resumoMobileEl.innerHTML = `
      <div style="padding:15px; font-weight:bold; font-size:1.2rem; display:flex; justify-content:space-between;">
        <span>Total:</span>
        <span>R$ ${valorTotal.toFixed(2).replace('.', ',')}</span>
      </div>
      <button onclick="irParaCheckout()" style="width:100%; padding:15px; background:green; color:white; border:none; border-radius:8px; font-weight:bold;">
        Finalizar Pedido
      </button>
    `;
    }
}

function removerItemCarrinho(index) {
    window.carrinho.splice(index, 1);
    salvarCarrinhoSalvo();
    atualizarCarrinho();
}

function limparCarrinho() {
    if (confirm('Deseja limpar o carrinho?')) {
        window.carrinho = [];
        salvarCarrinhoSalvo();
        atualizarCarrinho();
    }
}

// Modals
function fecharModal(id) {
    if (id) {
        document.getElementById(id).style.display = 'none';
        document.getElementById(id).classList.remove('active');
    } else {
        // Fechar todos
        document.querySelectorAll('.modal, .produto-modal').forEach(m => {
            m.style.display = 'none';
            m.classList.remove('active');
        });
    }
}

function toggleCarrinho() {
    const corpo = document.querySelector('.carrinho-corpo');
    const icon = document.querySelector('.carrinho-toggle-icon');
    if (corpo.style.display === 'none') {
        corpo.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        corpo.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Produto
window.abrirProduto = async function (produtoId) {
    if (!window.estabelecimentoAberto) {
        alert('Estabelecimento fechado.');
        return;
    }

    const modal = document.getElementById('produto-modal');
    modal.style.display = 'block'; // Ensure it overrides previous display:none
    // Pequeno delay para permitir transição CSS se houver
    setTimeout(() => { modal.classList.add('active'); }, 10);
    document.getElementById('produto-loading').style.display = 'block';
    document.getElementById('produto-detalhes').style.display = 'none';

    try {
        // Fetch product data
        const response = await fetch(`api/get_produto.php?id=${produtoId}`);
        const produto = await response.json();

        if (produto.erro) {
            alert(produto.erro);
            fecharModal('produto-modal');
            return;
        }

        // Fetch additionals for this product
        try {
            const adicionaisResponse = await fetch(`api/get_produto_adicionais.php?produto_id=${produtoId}`);
            const adicionaisData = await adicionaisResponse.json();
            if (adicionaisData.success && adicionaisData.grupos) {
                produto.grupos_adicionais = adicionaisData.grupos;
            } else {
                produto.grupos_adicionais = [];
            }
        } catch (e) {
            console.log('Sem adicionais ou erro ao buscar:', e);
            produto.grupos_adicionais = [];
        }

        window.produtoAtual = produto;
        window.adicionaisSelecionados = {}; // Reset selected additionals
        renderizarModalProduto(produto);

        document.getElementById('produto-loading').style.display = 'none';
        document.getElementById('produto-detalhes').style.display = 'block';
    } catch (err) {
        console.error(err);
        alert('Erro ao carregar produto.');
        fecharModal('produto-modal');
    }
};

function renderizarModalProduto(produto) {
    // Imagem
    const img = document.getElementById('modal-produto-imagem');
    img.src = produto.imagem_path || 'admin/assets/images/sem-foto.jpg';

    document.getElementById('modal-produto-nome').innerText = produto.nome;
    document.getElementById('modal-produto-descricao').innerText = produto.descricao || '';

    const preco = parseFloat(produto.preco);
    const precoPromo = parseFloat(produto.preco_promocional || 0);
    const precoFinal = (precoPromo > 0 && precoPromo < preco) ? precoPromo : preco;

    document.getElementById('modal-produto-preco').innerText = 'R$ ' + precoFinal.toFixed(2).replace('.', ',');
    document.getElementById('quantidade-valor').innerText = '1';
    document.getElementById('produto-obs').value = '';

    // Renderizar Grupos de Adicionais
    const addSection = document.getElementById('adicionais-section');
    const addLista = document.getElementById('adicionais-lista');

    if (produto.grupos_adicionais && produto.grupos_adicionais.length > 0) {
        addSection.style.display = 'block';
        addLista.innerHTML = '';

        produto.grupos_adicionais.forEach(grupo => {
            const grupoDiv = document.createElement('div');
            grupoDiv.className = 'grupo-adicional';
            grupoDiv.style.cssText = 'background:#1e2433; padding:15px; border-radius:12px; margin-bottom:15px; border:1px solid #2d3446;';

            // Header do grupo
            const headerHtml = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div>
                        <strong style="color:#fff; font-size:1rem;">${grupo.nome}</strong>
                        ${grupo.obrigatorio ? '<span style="background:#ef4444; color:white; font-size:0.7rem; padding:2px 8px; border-radius:10px; margin-left:8px;">Obrigatório</span>' : ''}
                    </div>
                    <small style="color:#a0aec0;">
                        ${grupo.tipo_escolha === 'unico' ? 'Escolha 1' : `Escolha ${grupo.minimo_escolha} a ${grupo.maximo_escolha}`}
                    </small>
                </div>
            `;
            grupoDiv.innerHTML = headerHtml;

            // Itens do grupo
            const itensContainer = document.createElement('div');
            itensContainer.style.cssText = 'display:flex; flex-direction:column; gap:8px;';

            grupo.itens.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.style.cssText = 'display:flex; align-items:center; gap:12px; padding:10px; background:#0f131a; border-radius:8px; cursor:pointer; transition:all 0.2s;';
                itemDiv.setAttribute('data-grupo-id', grupo.id);
                itemDiv.setAttribute('data-item-id', item.id);
                itemDiv.setAttribute('data-preco', item.preco_adicional);
                itemDiv.setAttribute('data-nome', item.nome);

                const inputType = grupo.tipo_escolha === 'unico' ? 'radio' : 'checkbox';
                const inputName = `grupo_${grupo.id}`;

                itemDiv.innerHTML = `
                    <input type="${inputType}" name="${inputName}" id="item_${item.id}" 
                           style="width:20px; height:20px; accent-color:#4a66f9;"
                           onchange="toggleAdicional(${grupo.id}, ${item.id}, ${item.preco_adicional}, '${item.nome.replace(/'/g, "\\'")}', '${inputType}', ${grupo.maximo_escolha})">
                    <label for="item_${item.id}" style="flex:1; color:#e2e8f0; cursor:pointer;">${item.nome}</label>
                    ${item.preco_adicional > 0
                        ? `<span style="color:#4a66f9; font-weight:600;">+ R$ ${item.preco_adicional.toFixed(2).replace('.', ',')}</span>`
                        : '<span style="color:#10b981; font-size:0.85rem;">Grátis</span>'}
                `;

                // Click on the row toggles the input
                itemDiv.addEventListener('click', (e) => {
                    if (e.target.tagName !== 'INPUT') {
                        const input = itemDiv.querySelector('input');
                        input.checked = !input.checked;
                        input.dispatchEvent(new Event('change'));
                    }
                });

                itensContainer.appendChild(itemDiv);
            });

            grupoDiv.appendChild(itensContainer);
            addLista.appendChild(grupoDiv);
        });
    } else {
        addSection.style.display = 'none';
        addLista.innerHTML = '';
    }

    // Meio a Meio
    const meioMeioSection = document.getElementById('segundo-sabor-section');
    const meioMeioLista = document.getElementById('segundo-sabor-lista');

    if (produto.permite_meio_a_meio) {
        meioMeioSection.style.display = 'block';
        meioMeioLista.innerHTML = '';

        // Reset estado
        window.meioAMeioAtivo = false;
        window.segundoSabor = null;
        window.tipoCobrancaPizza = 'maior_valor';

        // Botão para ativar meio a meio
        const toggleContainer = document.createElement('div');
        toggleContainer.style.cssText = 'display:flex; gap:10px; margin-bottom:15px;';

        const btnInteira = document.createElement('button');
        btnInteira.id = 'btn-pizza-inteira';
        btnInteira.style.cssText = 'flex:1; padding:12px; background:#4a66f9; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;';
        btnInteira.innerHTML = '🍕 Pizza Inteira';
        btnInteira.onclick = () => selecionarTipoPizza('inteira');

        const btnMeioMeio = document.createElement('button');
        btnMeioMeio.id = 'btn-pizza-meioameio';
        btnMeioMeio.style.cssText = 'flex:1; padding:12px; background:#1e2433; color:white; border:1px solid #2d3446; border-radius:8px; font-weight:600; cursor:pointer;';
        btnMeioMeio.innerHTML = '🍕🍕 Meio a Meio';
        btnMeioMeio.onclick = () => selecionarTipoPizza('meioameio');

        toggleContainer.appendChild(btnInteira);
        toggleContainer.appendChild(btnMeioMeio);
        meioMeioLista.appendChild(toggleContainer);

        // Container para lista de sabores (aparece ao clicar meio a meio)
        const saboresContainer = document.createElement('div');
        saboresContainer.id = 'sabores-meioameio-container';
        saboresContainer.style.display = 'none';
        meioMeioLista.appendChild(saboresContainer);

    } else {
        meioMeioSection.style.display = 'none';
        window.meioAMeioAtivo = false;
        window.segundoSabor = null;
    }

    // Itens para Retirar
    carregarItensRetirar();

    // Atualizar botão de adicionar com o preço inicial
    atualizarBotaoAdicionar();
}

// Carregar itens para retirar
async function carregarItensRetirar() {
    const retirarSection = document.getElementById('retirar-section');
    const retirarLista = document.getElementById('retirar-lista');

    if (!retirarSection || !retirarLista) return;

    // Reset
    window.itensRetirarSelecionados = [];
    retirarLista.innerHTML = '';

    try {
        const response = await fetch('api/get_itens_retirar.php');
        const data = await response.json();

        if (data.success && data.itens && data.itens.length > 0) {
            retirarSection.style.display = 'block';

            data.itens.forEach(item => {
                const chip = document.createElement('label');
                chip.style.cssText = `
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 10px 15px;
                    background: #1e2433;
                    border: 1px solid #2d3446;
                    border-radius: 25px;
                    cursor: pointer;
                    transition: all 0.2s;
                    font-size: 0.9rem;
                `;

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.id = `retirar_${item.id}`;
                checkbox.value = item.nome;
                checkbox.style.cssText = 'width: 16px; height: 16px; accent-color: #ef4444;';
                checkbox.addEventListener('change', function () {
                    if (this.checked) {
                        window.itensRetirarSelecionados.push(item.nome);
                        chip.style.background = 'rgba(239, 68, 68, 0.2)';
                        chip.style.borderColor = '#ef4444';
                    } else {
                        window.itensRetirarSelecionados = window.itensRetirarSelecionados.filter(n => n !== item.nome);
                        chip.style.background = '#1e2433';
                        chip.style.borderColor = '#2d3446';
                    }
                });

                const span = document.createElement('span');
                span.textContent = `Sem ${item.nome}`;
                span.style.color = '#e2e8f0';

                chip.appendChild(checkbox);
                chip.appendChild(span);
                retirarLista.appendChild(chip);
            });
        } else {
            retirarSection.style.display = 'none';
        }
    } catch (e) {
        console.log('Erro ao carregar itens para retirar:', e);
        retirarSection.style.display = 'none';
    }
}

// Toggle adicional selection
window.toggleAdicional = function (grupoId, itemId, preco, nome, inputType, maxEscolha) {
    if (!window.adicionaisSelecionados) {
        window.adicionaisSelecionados = {};
    }

    if (inputType === 'radio') {
        // Single choice - replace any previous selection for this group
        window.adicionaisSelecionados[grupoId] = [{ id: itemId, preco: preco, nome: nome }];
    } else {
        // Multiple choice
        if (!window.adicionaisSelecionados[grupoId]) {
            window.adicionaisSelecionados[grupoId] = [];
        }

        const existingIndex = window.adicionaisSelecionados[grupoId].findIndex(i => i.id === itemId);
        const input = document.getElementById(`item_${itemId}`);

        if (input.checked) {
            // Check max limit
            if (window.adicionaisSelecionados[grupoId].length >= maxEscolha) {
                input.checked = false;
                alert(`Máximo de ${maxEscolha} itens neste grupo.`);
                return;
            }
            if (existingIndex === -1) {
                window.adicionaisSelecionados[grupoId].push({ id: itemId, preco: preco, nome: nome });
            }
        } else {
            if (existingIndex > -1) {
                window.adicionaisSelecionados[grupoId].splice(existingIndex, 1);
            }
        }
    }

    atualizarBotaoAdicionar();
};

window.adicionarAoCarrinho = function () {
    console.log('=== adicionarAoCarrinho called ===');
    const produto = window.produtoAtual;
    console.log('Produto atual:', produto);
    console.log('Adicionais selecionados:', window.adicionaisSelecionados);

    const qtd = parseInt(document.getElementById('quantidade-valor').innerText);
    const obs = document.getElementById('produto-obs').value;
    console.log('Quantidade:', qtd, 'Obs:', obs);

    // Validate required groups
    if (produto.grupos_adicionais && produto.grupos_adicionais.length > 0) {
        for (const grupo of produto.grupos_adicionais) {
            if (grupo.obrigatorio) {
                const selecionados = window.adicionaisSelecionados && window.adicionaisSelecionados[grupo.id];
                const minimo = parseInt(grupo.minimo_escolha) || 1;
                const qtdSelecionados = selecionados ? selecionados.length : 0;

                if (qtdSelecionados < minimo) {
                    alert(`Por favor, selecione pelo menos ${minimo} item(s) em "${grupo.nome}".`);
                    return;
                }
            }
        }
    }

    const preco = parseFloat(produto.preco);
    const precoPromo = parseFloat(produto.preco_promocional || 0);
    let precoFinal = (precoPromo > 0 && precoPromo < preco) ? precoPromo : preco;

    // Verificar meio a meio
    let nomeItem = produto.nome;
    let segundoSaborData = null;

    if (window.meioAMeioAtivo && window.segundoSabor) {
        const preco2 = parseFloat(window.segundoSabor.preco);

        if (window.tipoCobrancaPizza === 'maior_valor') {
            precoFinal = Math.max(precoFinal, preco2);
        } else {
            precoFinal = (precoFinal + preco2) / 2;
        }

        nomeItem = `${produto.nome} + ${window.segundoSabor.nome} (Meio a Meio)`;
        segundoSaborData = {
            id: window.segundoSabor.id,
            nome: window.segundoSabor.nome,
            preco: preco2
        };
    }

    // Collect all selected additionals
    const adicionaisArray = [];
    if (window.adicionaisSelecionados) {
        Object.values(window.adicionaisSelecionados).forEach(grupoItens => {
            grupoItens.forEach(item => {
                adicionaisArray.push({
                    id: item.id,
                    nome: item.nome,
                    preco: parseFloat(item.preco)
                });
            });
        });
    }

    // Itens para retirar
    const itensRetirar = window.itensRetirarSelecionados || [];

    // Montar observações incluindo itens para retirar
    let observacoesCompletas = obs;
    if (itensRetirar.length > 0) {
        const retirarTexto = itensRetirar.map(n => `SEM ${n}`).join(', ');
        observacoesCompletas = observacoesCompletas ? `${observacoesCompletas} | ${retirarTexto}` : retirarTexto;
    }

    const item = {
        id: produto.id,
        nome: nomeItem,
        preco: precoFinal,
        preco_base: precoFinal,
        quantidade: qtd,
        observacoes: observacoesCompletas,
        adicionais: adicionaisArray,
        itens_retirar: itensRetirar,
        imagem: produto.imagem_path,
        meio_a_meio: window.meioAMeioAtivo,
        segundo_sabor: segundoSaborData
    };

    window.carrinho.push(item);
    salvarCarrinhoSalvo();
    atualizarCarrinho();
    fecharModal('produto-modal');

    // Toast
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed; bottom:20px; right:20px; background:green; color:white; padding:15px; border-radius:8px; z-index:9999;';
    toast.innerText = 'Produto adicionado!';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
};

window.alterarQuantidade = function (delta) {
    const el = document.getElementById('quantidade-valor');
    let qtd = parseInt(el.innerText) + delta;
    if (qtd < 1) qtd = 1;
    el.innerText = qtd;

    // Atualizar preço no botão
    atualizarBotaoAdicionar();
};

window.atualizarBotaoAdicionar = function () {
    if (!window.produtoAtual) return;

    // Garantir que os elementos existem
    const elQtd = document.getElementById('quantidade-valor');
    const elBtn = document.getElementById('modal-total-btn');

    if (!elQtd || !elBtn) return;

    const qtd = parseInt(elQtd.innerText);
    const preco = parseFloat(window.produtoAtual.preco);
    const precoPromo = parseFloat(window.produtoAtual.preco_promocional || 0);
    let precoFinal = (precoPromo > 0 && precoPromo < preco) ? precoPromo : preco;

    // Se meio a meio está ativo e tem segundo sabor selecionado
    if (window.meioAMeioAtivo && window.segundoSabor) {
        const preco2 = parseFloat(window.segundoSabor.preco);

        if (window.tipoCobrancaPizza === 'maior_valor') {
            precoFinal = Math.max(precoFinal, preco2);
        } else {
            // média
            precoFinal = (precoFinal + preco2) / 2;
        }
    }

    // Somar adicionais selecionados
    let totalAdicionais = 0;
    if (window.adicionaisSelecionados) {
        Object.values(window.adicionaisSelecionados).forEach(grupoItens => {
            grupoItens.forEach(item => {
                totalAdicionais += parseFloat(item.preco || 0);
            });
        });
    }

    let total = (precoFinal + totalAdicionais) * qtd;

    elBtn.innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
};

// Funções para Pizza Meio a Meio
window.selecionarTipoPizza = async function (tipo) {
    const btnInteira = document.getElementById('btn-pizza-inteira');
    const btnMeioMeio = document.getElementById('btn-pizza-meioameio');
    const container = document.getElementById('sabores-meioameio-container');

    if (!btnInteira || !btnMeioMeio || !container) return;

    if (tipo === 'inteira') {
        btnInteira.style.background = '#4a66f9';
        btnInteira.style.border = 'none';
        btnMeioMeio.style.background = '#1e2433';
        btnMeioMeio.style.border = '1px solid #2d3446';
        container.style.display = 'none';
        window.meioAMeioAtivo = false;
        window.segundoSabor = null;
        atualizarBotaoAdicionar();
    } else {
        btnMeioMeio.style.background = '#4a66f9';
        btnMeioMeio.style.border = 'none';
        btnInteira.style.background = '#1e2433';
        btnInteira.style.border = '1px solid #2d3446';
        window.meioAMeioAtivo = true;

        // Carregar pizzas da mesma categoria
        container.style.display = 'block';
        container.innerHTML = '<p style="text-align:center; padding:15px; color:#a0aec0;"><i class="fa-solid fa-spinner fa-spin"></i> Carregando sabores...</p>';

        try {
            const categoriaId = window.produtoAtual.categoria_id;
            const produtoId = window.produtoAtual.id;
            const resp = await fetch(`api/get_pizzas_categoria.php?categoria_id=${categoriaId}&excluir=${produtoId}`);
            const data = await resp.json();

            if (data.success && data.pizzas.length > 0) {
                window.tipoCobrancaPizza = data.tipo_cobranca;
                renderizarSaboresMeioMeio(data.pizzas);
            } else {
                container.innerHTML = '<p style="text-align:center; padding:15px; color:#ef4444;">Nenhum outro sabor disponível nesta categoria.</p>';
            }
        } catch (e) {
            console.error('Erro ao carregar sabores:', e);
            container.innerHTML = '<p style="text-align:center; padding:15px; color:#ef4444;">Erro ao carregar sabores.</p>';
        }
    }
};

window.renderizarSaboresMeioMeio = function (pizzas) {
    const container = document.getElementById('sabores-meioameio-container');
    if (!container) return;

    const tipoCobranca = window.tipoCobrancaPizza === 'maior_valor' ? 'Cobra pelo maior valor' : 'Cobra pela média';

    container.innerHTML = `
        <p style="color:#a0aec0; margin-bottom:10px; font-size:0.85rem;">
            <i class="fa-solid fa-info-circle"></i> Escolha o segundo sabor (${tipoCobranca})
        </p>
        <div id="sabores-lista" style="display:flex; flex-direction:column; gap:8px; max-height:250px; overflow-y:auto;"></div>
    `;

    const lista = document.getElementById('sabores-lista');

    pizzas.forEach(pizza => {
        const div = document.createElement('div');
        div.className = 'sabor-item';
        div.style.cssText = 'display:flex; align-items:center; gap:12px; padding:12px; background:#0f131a; border-radius:8px; cursor:pointer; border:2px solid transparent; transition:all 0.2s;';
        div.setAttribute('data-pizza-id', pizza.id);
        div.setAttribute('data-pizza-preco', pizza.preco_final);
        div.setAttribute('data-pizza-nome', pizza.nome);

        div.innerHTML = `
            <img src="${pizza.imagem_path}" style="width:45px; height:45px; border-radius:8px; object-fit:cover;" onerror="this.src='admin/assets/images/sem-foto.jpg'">
            <div style="flex:1;">
                <div style="font-weight:600; color:#fff;">${pizza.nome}</div>
                <div style="color:#4a66f9; font-size:0.9rem;">R$ ${parseFloat(pizza.preco_final).toFixed(2).replace('.', ',')}</div>
            </div>
            <i class="fa-solid fa-check-circle" style="color:#10b981; font-size:1.3rem; display:none;"></i>
        `;

        div.onclick = () => selecionarSegundoSabor(pizza);
        lista.appendChild(div);
    });
};

window.selecionarSegundoSabor = function (pizza) {
    // Desmarcar anterior
    document.querySelectorAll('.sabor-item').forEach(el => {
        el.style.borderColor = 'transparent';
        el.querySelector('.fa-check-circle').style.display = 'none';
    });

    // Marcar atual
    const selecionado = document.querySelector(`.sabor-item[data-pizza-id="${pizza.id}"]`);
    if (selecionado) {
        selecionado.style.borderColor = '#10b981';
        selecionado.querySelector('.fa-check-circle').style.display = 'block';
    }

    window.segundoSabor = {
        id: pizza.id,
        nome: pizza.nome,
        preco: pizza.preco_final
    };

    atualizarBotaoAdicionar();
};


// Filtrar apenas promoções
window.filtrarPromocoes = function () {
    // Esconder categorias vazias ou sem promoções
    const accordions = document.querySelectorAll('.accordion-item');
    let found = false;

    accordions.forEach(item => {
        const products = item.querySelectorAll('.product-card');
        let hasPromo = false;

        products.forEach(prod => {
            const priceEl = prod.querySelector('.price');
            // Basic detection if it has strikethrough price (promo)
            if (priceEl && priceEl.querySelector('span[style*="text-decoration: line-through"]')) {
                prod.style.display = 'flex';
                hasPromo = true;
                found = true;
            } else {
                prod.style.display = 'none';
            }
        });

        if (hasPromo) {
            item.style.display = 'block';
            // Open accordion to show promos
            const content = item.querySelector('.accordion-content');
            if (content) {
                content.classList.add('active');
                content.style.display = 'block';
                const icon = item.querySelector('.accordion-header i');
                if (icon) icon.style.transform = "rotate(180deg)";
            }
        } else {
            item.style.display = 'none';
        }
    });

    if (!found) {
        alert('Nenhuma promoção ativa no momento!');
        // Reset filter
        setTimeout(() => window.location.reload(), 1000);
    } else {
        // Scroll to first promo
        const first = document.querySelector('.accordion-item[style="display: block;"]');
        if (first) {
            first.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Show "Clear Filter" button logic could be added here
        // For now, simpler is better as per user request style
    }
};

// Checkout - Versão Completa
window.irParaCheckout = async function () {
    if (window.carrinho.length === 0) {
        alert('Carrinho vazio!');
        return;
    }

    // Verificar se loja está aberta antes de ir ao checkout
    try {
        const statusResp = await fetch('api/check_loja_status.php');
        const statusData = await statusResp.json();

        if (!statusData.aberta) {
            mostrarModalLojaFechada(statusData.mensagem, statusData.horario_hoje);
            return;
        }
    } catch (e) {
        console.log('Erro ao verificar status da loja:', e);
        // Continuar mesmo com erro (permite pedido se API falhar)
    }

    fecharModal(); // Fecha todos
    document.getElementById('modal-checkout').style.display = 'flex';

    // Buscar formas de pagamento da API
    try {
        const response = await fetch('api/get_formas_pagamento.php');
        const formasPagamento = await response.json();
        renderCheckoutCompleto(formasPagamento);

        // Preencher dados se cliente está logado
        if (window.clienteLogado) {
            preencherDadosClienteLogado();
        } else {
            // Tentar restaurar dados salvos localmente
            restaurarDadosClienteLocal();
        }
    } catch (error) {
        console.error('Erro ao buscar formas de pagamento:', error);
        renderCheckoutCompleto([]); // Fallback sem formas de pagamento
    }
};

// Função para preencher dados do cliente logado
window.preencherDadosClienteLogado = async function () {
    const cliente = window.clienteLogado;
    if (!cliente) return;

    console.log('👤 Preenchendo dados do cliente logado:', cliente.nome);

    // Guardar ID do cliente
    window.clienteEncontradoId = cliente.id;

    // Preencher campos pessoais
    const telefoneInput = document.getElementById('checkout-telefone');
    const nomeInput = document.getElementById('checkout-nome');
    const emailInput = document.getElementById('checkout-email');

    if (telefoneInput && cliente.telefone) telefoneInput.value = cliente.telefone;
    if (nomeInput && cliente.nome) nomeInput.value = cliente.nome;
    if (emailInput && cliente.email) emailInput.value = cliente.email;

    // Buscar endereços salvos do cliente
    if (cliente.telefone) {
        const telefoneLimpo = cliente.telefone.replace(/\D/g, '');
        try {
            const enderecosResp = await fetch(`api/get_enderecos_cliente.php?telefone=${telefoneLimpo}`);
            const enderecosData = await enderecosResp.json();

            if (enderecosData.sucesso && enderecosData.enderecos && enderecosData.enderecos.length > 0) {
                window.enderecosCliente = enderecosData.enderecos;
                console.log(`📍 Cliente logado tem ${enderecosData.enderecos.length} endereço(s) salvo(s)`);

                // Se tem apenas 1 endereço, preencher automaticamente
                if (enderecosData.enderecos.length === 1) {
                    const end = enderecosData.enderecos[0];
                    preencherCamposEndereco(end);
                }
            } else {
                // Sem endereços na tabela, usa dados do cliente (legado)
                preencherCamposEnderecoCliente(cliente);
            }
        } catch (e) {
            console.log('Erro ao buscar endereços:', e);
            preencherCamposEnderecoCliente(cliente);
        }
    } else {
        preencherCamposEnderecoCliente(cliente);
    }

    // Mostrar feedback
    const feedback = document.getElementById('cliente-feedback');
    if (feedback) {
        feedback.innerHTML = '<i class="fa-solid fa-check-circle"></i> Bem-vindo de volta, ' + cliente.nome + '!';
        feedback.className = 'cliente-feedback success';
        feedback.style.display = 'block';

        setTimeout(() => {
            feedback.style.opacity = '0';
            setTimeout(() => {
                feedback.style.display = 'none';
                feedback.style.opacity = '1';
            }, 300);
        }, 3000);
    }

    // Se tem algum endereço, selecionar delivery automaticamente
    if (cliente.bairro || cliente.rua || (window.enderecosCliente && window.enderecosCliente.length > 0)) {
        const deliveryBtn = document.querySelector('.tipo-entrega-btn[data-tipo="delivery"]');
        if (deliveryBtn) {
            selecionarTipoEntregaCheckout(deliveryBtn, 'delivery');
        }
    }
};

// Função auxiliar para preencher campos de endereço
function preencherCamposEndereco(end) {
    const cepInput = document.getElementById('checkout-cep');
    const ruaInput = document.getElementById('checkout-rua');
    const numeroInput = document.getElementById('checkout-numero');
    const complementoInput = document.getElementById('checkout-complemento');
    const bairroInput = document.getElementById('checkout-bairro-input');
    const cidadeInput = document.getElementById('checkout-cidade-input');
    const ufInput = document.getElementById('checkout-uf');

    if (cepInput) cepInput.value = end.cep || '';
    if (ruaInput) ruaInput.value = end.rua || '';
    if (numeroInput) numeroInput.value = end.numero || '';
    if (complementoInput) complementoInput.value = end.complemento || '';
    if (bairroInput) bairroInput.value = end.bairro || '';
    if (cidadeInput) cidadeInput.value = end.cidade || '';
    if (ufInput) ufInput.value = end.estado || '';

    window.enderecoSelecionadoId = end.id;

    // Calcular taxa
    if (end.bairro && end.cidade) {
        calcularTaxaPorBairroTexto(end.bairro, end.cidade);
    }
}

// Função auxiliar para preencher endereço da tabela clientes (legado)
function preencherCamposEnderecoCliente(cliente) {
    if (cliente.cep || cliente.rua || cliente.bairro) {
        const cepInput = document.getElementById('checkout-cep');
        const ruaInput = document.getElementById('checkout-rua');
        const numeroInput = document.getElementById('checkout-numero');
        const complementoInput = document.getElementById('checkout-complemento');
        const bairroInput = document.getElementById('checkout-bairro-input');
        const cidadeInput = document.getElementById('checkout-cidade-input');
        const ufInput = document.getElementById('checkout-uf');

        if (cepInput && cliente.cep) cepInput.value = cliente.cep;
        if (ruaInput && cliente.rua) ruaInput.value = cliente.rua;
        if (numeroInput && cliente.numero) numeroInput.value = cliente.numero;
        if (complementoInput && cliente.complemento) complementoInput.value = cliente.complemento;
        if (bairroInput && cliente.bairro) bairroInput.value = cliente.bairro;
        if (cidadeInput && cliente.cidade) cidadeInput.value = cliente.cidade;
        if (ufInput && cliente.estado) ufInput.value = cliente.estado;

        // Calcular taxa
        if (cliente.bairro && cliente.cidade) {
            calcularTaxaPorBairroTexto(cliente.bairro, cliente.cidade);
        }
    }
}

function renderCheckoutCompleto(formasPagamento) {
    const body = document.getElementById('modal-body-checkout');
    let subtotal = 0;

    // Calcular subtotal
    window.carrinho.forEach(item => {
        subtotal += calcularPrecoTotalItem(item) * item.quantidade;
    });

    let html = `
    <style>
      #modal-checkout .modal-content {
          background-color: #151922; /* Fundo escuro igual print */
          color: #fff;
          border: 1px solid #2d3446;
      }
      #modal-checkout h3 { color: #fff !important; }
      #modal-checkout .close-btn { color: #fff !important; } /* Ajuste se tiver classe close botton */
      
      .checkout-section { margin-bottom: 24px; }
      .checkout-section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #fff; }
      .checkout-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #2d3446; }
      .checkout-item:last-child { border-bottom: none; }
      .checkout-item-nome { font-weight: 600; color: #e2e8f0; }
      .checkout-item-preco { color: var(--primary-color); font-weight: 700; }
      .checkout-item-remove { background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; }
      
      .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
      .tipo-entrega-opcoes { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
      .tipo-entrega-btn { padding: 20px; border: 1px solid #2d3446; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.2s; background: #1e2433; color: #fff; }
      .tipo-entrega-btn:hover { border-color: var(--primary-color); }
      .tipo-entrega-btn.selected { border-color: var(--primary-color); background: rgba(0, 158, 227, 0.1); }
      .tipo-entrega-btn i { font-size: 2rem; color: var(--primary-color); margin-bottom: 8px; display: block; }
      
      .formas-pagamento-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
      .forma-pagamento-card { padding: 16px; border: 1px solid #2d3446; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.2s; background: #1e2433; color: #fff; position: relative; }
      .forma-pagamento-card:hover { border-color: var(--primary-color); }
      .forma-pagamento-card.selected { border-color: var(--primary-color); background: rgba(0, 158, 227, 0.15); }
      .forma-pagamento-card i { font-size: 1.8rem; color: var(--primary-color); margin-bottom: 8px; display: block; }
      .forma-pagamento-card .badge-online { position: absolute; top: 5px; right: 5px; background: #28a745; color: white; font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
      
      .resumo-box { background: #1e2433; padding: 20px; border-radius: 12px; margin-top: 20px; border: 1px solid #2d3446; }
      .resumo-linha { display: flex; justify-content: space-between; padding: 8px 0; color: #e2e8f0; }
      .resumo-total { font-size: 1.3rem; font-weight: 700; color: #fff; border-top: 1px solid #2d3446; padding-top: 12px; margin-top: 8px; }
      
      /* Estilos para inputs, selects e textareas do checkout */
      #modal-body-checkout .form-group { margin-bottom: 16px; }
      #modal-body-checkout .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #fff; }
      #modal-body-checkout input[type="text"],
      #modal-body-checkout input[type="tel"],
      #modal-body-checkout input[type="email"],
      #modal-body-checkout select,
      #modal-body-checkout textarea,
      #modal-body-checkout .checkout-textarea {
        width: 100%;
        padding: 14px 16px;
        background: #0f131a;
        border: 1px solid #2d3446;
        border-radius: 8px;
        color: #fff;
        font-size: 1rem;
        font-family: inherit;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
      }
      #modal-body-checkout input::placeholder,
      #modal-body-checkout textarea::placeholder {
        color: #64748b;
        opacity: 0.7;
      }
      #modal-body-checkout input:focus,
      #modal-body-checkout select:focus,
      #modal-body-checkout textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(0, 158, 227, 0.2);
      }
      #modal-body-checkout select {
        appearance: none;
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
        cursor: pointer;
      }
      #modal-body-checkout select option {
        background: #0f131a;
        color: #fff;
      }
      #modal-body-checkout .checkout-textarea {
        resize: vertical;
        min-height: 80px;
      }
      
      /* Feedback de busca de cliente */
      .cliente-feedback {
        margin-top: 10px;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
      }
      .cliente-feedback.loading {
        background: rgba(255, 193, 7, 0.15);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
      }
      .cliente-feedback.success {
        background: rgba(40, 167, 69, 0.15);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
      }
      .cliente-feedback.info {
        background: rgba(23, 162, 184, 0.15);
        color: #17a2b8;
        border: 1px solid rgba(23, 162, 184, 0.3);
      }
      .cliente-feedback i { font-size: 1rem; }
      
      /* Botão Finalizar */
      .btn-finalizar {
          width: 100%;
          padding: 16px;
          border-radius: 12px;
          margin-top: 20px;
          font-size: 1.1rem;
          font-weight: 700;
      }
      
      @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
    </style>
    
    <!-- Itens do Pedido -->
    <div class="checkout-section">
      <div class="checkout-section-title">
        <i class="fa-solid fa-shopping-cart"></i> Itens do Pedido (${window.carrinho.length})
      </div>
      <div class="checkout-itens">
    `;

    window.carrinho.forEach((item, index) => {
        const itemTotal = calcularPrecoTotalItem(item) * item.quantidade;
        html += `
        <div class="checkout-item">
          <span class="checkout-item-nome">${item.quantidade}x ${item.nome}</span>
          <span>
            <span class="checkout-item-preco">R$ ${itemTotal.toFixed(2).replace('.', ',')}</span>
            <button class="checkout-item-remove" onclick="removerItemCheckout(${index})"><i class="fa-solid fa-trash"></i></button>
          </span>
        </div>
        `;
    });

    html += `
      </div>
    </div>
    
    <!-- Seus Dados -->
    <div class="checkout-section">
      <div class="checkout-section-title">
        <i class="fa-solid fa-user"></i> Seus Dados
      </div>
      <div class="form-group">
        <label>WhatsApp *</label>
        <input type="tel" id="checkout-telefone" placeholder="(00) 00000-0000" required oninput="formatarTelefone(this)">
        <small style="display: block; margin-top: 5px; color: var(--text-muted);">Digite seu telefone para buscar dados cadastrados</small>
        <div id="cliente-feedback" class="cliente-feedback" style="display: none;"></div>
      </div>
      <div class="form-group">
        <label>Nome Completo *</label>
        <input type="text" id="checkout-nome" placeholder="Seu nome completo" required>
      </div>
      <div class="form-group">
        <label>E-mail (opcional)</label>
        <input type="email" id="checkout-email" placeholder="seuemail@exemplo.com">
      </div>
    </div>
    
    <!-- Tipo de Entrega -->
    <div class="checkout-section">
      <div class="checkout-section-title">
        <i class="fa-solid fa-truck"></i> Tipo de Entrega
      </div>
      <div class="tipo-entrega-opcoes">
        <div class="tipo-entrega-btn selected" data-tipo="balcao" onclick="selecionarTipoEntregaCheckout(this, 'balcao')">
          <i class="fa-solid fa-shopping-bag"></i>
          <span>Retirar no Local</span>
        </div>
        <div class="tipo-entrega-btn" data-tipo="delivery" onclick="selecionarTipoEntregaCheckout(this, 'delivery')">
          <i class="fa-solid fa-motorcycle"></i>
          <span>Delivery</span>
        </div>
      </div>
      <input type="hidden" id="checkout-tipo-entrega" value="balcao">
    </div>
    
    <!-- Endereço (oculto inicialmente) -->
    <div id="checkout-endereco-section" class="checkout-section" style="display: none;">
      <div class="checkout-section-title">
        <i class="fa-solid fa-location-dot"></i> Endereço de Entrega
      </div>
      
      <!-- Campos do formulário (podem ser ocultados quando usar endereço salvo) -->
      <div id="form-endereco-fields">
        <!-- CEP com botão buscar -->
        <div class="form-row" style="align-items: flex-end;">
          <div class="form-group" style="max-width: 200px;">
            <label>CEP *</label>
            <input type="text" id="checkout-cep" placeholder="00000-000" oninput="formatarCEP(this)" maxlength="9">
          </div>
          <div class="form-group" style="max-width: 120px;">
            <button type="button" class="btn-buscar-cep" onclick="buscarCEP()" style="padding: 14px 20px; background: var(--primary-color); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
              <i class="fa-solid fa-search"></i> Buscar
            </button>
          </div>
        </div>
        
        <!-- Rua -->
        <div class="form-group">
          <label>Rua *</label>
          <input type="text" id="checkout-rua" placeholder="Nome da rua">
        </div>
        
        <!-- Número e Complemento -->
        <div class="form-row">
          <div class="form-group" style="max-width: 120px;">
            <label>Número *</label>
            <input type="text" id="checkout-numero" placeholder="123">
          </div>
          <div class="form-group" style="flex: 1;">
            <label>Complemento</label>
            <input type="text" id="checkout-complemento" placeholder="Apto, bloco, etc.">
          </div>
        </div>
        
        <!-- Bairro -->
        <div class="form-group">
          <label>Bairro *</label>
          <input type="text" id="checkout-bairro-input" placeholder="Nome do bairro" onblur="recalcularTaxaComCidade()">
        </div>
        
        <!-- Cidade e UF -->
        <div class="form-row">
          <div class="form-group" style="flex: 1;">
            <label>Cidade *</label>
            <input type="text" id="checkout-cidade-input" placeholder="Nome da cidade" onblur="recalcularTaxaComCidade()">
          </div>
          <div class="form-group" style="max-width: 80px;">
            <label>UF *</label>
            <input type="text" id="checkout-uf" placeholder="SP" maxlength="2" style="text-transform: uppercase;">
          </div>
        </div>
        
        <!-- Opções para salvar novo endereço -->
        <div id="salvar-endereco-container" style="display: none; gap: 15px; flex-direction: column; margin-top: 15px; padding: 15px; background: #1e2433; border-radius: 8px; border: 1px solid #2d3446;">
          <div class="form-group" style="margin-bottom: 0;">
            <label>Apelido do endereço (opcional)</label>
            <input type="text" id="endereco-apelido" placeholder="Ex: Casa, Trabalho" style="max-width: 250px;">
          </div>
          <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #e2e8f0;">
            <input type="checkbox" id="salvar-endereco-check" checked style="width: 18px; height: 18px; accent-color: #4a66f9;">
            <span>Salvar este endereço para próximos pedidos</span>
          </label>
        </div>
      </div>
      
      <!-- Taxa de entrega calculada -->
      <div id="taxa-entrega-info" style="display: none; background: var(--surface-soft); padding: 12px; border-radius: 8px; margin-top: 15px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span><i class="fa-solid fa-motorcycle"></i> Taxa de Entrega:</span>
          <span id="taxa-entrega-valor" style="font-weight: 700; color: var(--primary-color);">R$ 0,00</span>
        </div>
      </div>
      
      <input type="hidden" id="checkout-taxa-entrega" value="0">
    </div>
    
    <!-- Forma de Pagamento -->
    <div class="checkout-section">
      <div class="checkout-section-title">
        <i class="fa-solid fa-credit-card"></i> Forma de Pagamento
      </div>
      <div class="formas-pagamento-grid">
    `;

    // Renderizar formas de pagamento da API
    if (formasPagamento && formasPagamento.length > 0) {
        formasPagamento.forEach((forma, idx) => {
            const isMercadoPago = forma.tipo === 'mercadopago';
            const isSelected = idx === 0 ? 'selected' : '';
            let icone = forma.icone || 'fa-solid fa-wallet';
            if (isMercadoPago) icone = 'fa-brands fa-pix';

            html += `
            <div class="forma-pagamento-card ${isSelected}" data-id="${forma.id}" data-tipo="${forma.tipo || ''}" onclick="selecionarFormaPagamentoCheckout(this)">
              ${isMercadoPago ? '<span class="badge-online">ONLINE</span>' : ''}
              <i class="${icone}"></i>
              <span>${forma.nome}</span>
            </div>
            `;
        });
    } else {
        // Fallback se API falhar
        html += `
        <div class="forma-pagamento-card selected" data-id="1" data-tipo="pix" onclick="selecionarFormaPagamentoCheckout(this)">
          <i class="fa-brands fa-pix"></i>
          <span>PIX</span>
        </div>
        <div class="forma-pagamento-card" data-id="2" data-tipo="dinheiro" onclick="selecionarFormaPagamentoCheckout(this)">
          <i class="fa-solid fa-money-bill"></i>
          <span>Dinheiro</span>
        </div>
        <div class="forma-pagamento-card" data-id="3" data-tipo="cartao" onclick="selecionarFormaPagamentoCheckout(this)">
          <i class="fa-solid fa-credit-card"></i>
          <span>Cartão</span>
        </div>
        `;
    }

    html += `
      </div>
    </div>
    
    <!-- Observações -->
    <div class="checkout-section">
      <div class="checkout-section-title">
        <i class="fa-solid fa-message"></i> Observações (opcional)
      </div>
      <div class="form-group">
        <textarea id="checkout-obs" rows="3" placeholder="Alguma observação sobre o pedido?" class="checkout-textarea"></textarea>
      </div>
    </div>
    
    <!-- Resumo -->
    <div class="resumo-box">
      <div class="resumo-linha">
        <span>Subtotal:</span>
        <span>R$ ${subtotal.toFixed(2).replace('.', ',')}</span>
      </div>
      <div class="resumo-linha" id="resumo-entrega" style="display: none;">
        <span>Entrega:</span>
        <span id="valor-entrega">R$ 0,00</span>
      </div>
      <div class="resumo-linha resumo-total">
        <span>Total:</span>
        <span id="checkout-total">R$ ${subtotal.toFixed(2).replace('.', ',')}</span>
      </div>
    </div>
    
    <button type="button" class="btn-finalizar" onclick="finalizarPedidoCompleto()" style="width: 100%; margin-top: 20px; padding: 16px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: 700; cursor: pointer;">
      <i class="fa-solid fa-check-circle"></i> Finalizar Pedido
    </button>
    `;

    body.innerHTML = html;

    // Selecionar primeira forma de pagamento se existir
    const primeiraForma = document.querySelector('.forma-pagamento-card');
    if (primeiraForma) {
        window.formaPagamentoSelecionada = primeiraForma.dataset.id;
        window.formaPagamentoTipo = primeiraForma.dataset.tipo;
    }

    // Inicializar busca de cliente por telefone
    inicializarBuscaCliente();
}

// Função para formatar telefone (melhorada para permitir exclusão)
window.formatarTelefone = function (input) {
    // Salvar posição do cursor
    const cursorPos = input.selectionStart;
    const valorAnterior = input.value;

    // Remover tudo que não é número
    let value = input.value.replace(/\D/g, '');

    if (value.length > 11) value = value.slice(0, 11);

    // Aplicar máscara somente se tiver pelo menos 2 dígitos
    let valorFormatado = '';
    if (value.length >= 2) {
        if (value.length > 6) {
            valorFormatado = `(${value.slice(0, 2)}) ${value.slice(2, 7)}-${value.slice(7)}`;
        } else if (value.length > 2) {
            valorFormatado = `(${value.slice(0, 2)}) ${value.slice(2)}`;
        } else {
            valorFormatado = `(${value})`;
        }
    } else {
        // 0 ou 1 dígito - não aplicar máscara
        valorFormatado = value;
    }

    input.value = valorFormatado;

    // Restaurar posição do cursor para permitir edição no meio
    if (valorAnterior.length > valorFormatado.length) {
        const novaPosicao = Math.max(0, cursorPos - (valorAnterior.length - valorFormatado.length));
        input.setSelectionRange(novaPosicao, novaPosicao);
    }
};

// Função auxiliar para remover item do checkout
window.removerItemCheckout = function (index) {
    window.carrinho.splice(index, 1);
    salvarCarrinhoSalvo();
    atualizarCarrinho();
    if (window.carrinho.length === 0) {
        fecharModal('modal-checkout');
    } else {
        irParaCheckout(); // Recarregar checkout
    }
};

// Selecionar tipo de entrega
window.selecionarTipoEntregaCheckout = function (el, tipo) {
    document.querySelectorAll('.tipo-entrega-btn').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('checkout-tipo-entrega').value = tipo;

    const enderecoSection = document.getElementById('checkout-endereco-section');
    if (enderecoSection) {
        enderecoSection.style.display = tipo === 'delivery' ? 'block' : 'none';

        // Carregar dados de entrega quando delivery é selecionado
        if (tipo === 'delivery') {
            carregarDadosEntrega();

            // Mostrar seletor de endereços se cliente tem endereços salvos
            if (window.enderecosCliente && window.enderecosCliente.length > 1) {
                // Múltiplos endereços - mostrar seletor e ocultar formulário
                mostrarSeletorEnderecos(window.enderecosCliente);
                const formEnderecoFields = document.getElementById('form-endereco-fields');
                if (formEnderecoFields) formEnderecoFields.style.display = 'none';
            } else if (window.enderecosCliente && window.enderecosCliente.length === 1) {
                // 1 endereço - mostrar seletor mas manter formulário oculto (já está preenchido)
                mostrarSeletorEnderecos(window.enderecosCliente);
                const formEnderecoFields = document.getElementById('form-endereco-fields');
                if (formEnderecoFields) formEnderecoFields.style.display = 'none';
            } else {
                // Sem endereços salvos - Mostrar formulário de endereço normalmente
                const formEnderecoFields = document.getElementById('form-endereco-fields');
                if (formEnderecoFields) formEnderecoFields.style.display = 'block';

                // Mostrar opção de salvar endereço (sempre para delivery, mesmo cliente novo)
                const salvarContainer = document.getElementById('salvar-endereco-container');
                if (salvarContainer) {
                    salvarContainer.style.display = 'flex';
                }
            }
        }
    }

    // Atualizar total
    atualizarTotalCheckout();
};

// Selecionar forma de pagamento
window.selecionarFormaPagamentoCheckout = function (el) {
    document.querySelectorAll('.forma-pagamento-card').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    window.formaPagamentoSelecionada = el.dataset.id;
    window.formaPagamentoTipo = el.dataset.tipo;
};

// Finalizar pedido completo
window.finalizarPedidoCompleto = async function () {
    console.log('🚀 Iniciando finalizarPedidoCompleto');

    const nome = document.getElementById('checkout-nome')?.value.trim() || '';
    const telefone = document.getElementById('checkout-telefone')?.value.trim() || '';
    const email = document.getElementById('checkout-email')?.value.trim() || '';
    const tipoEntrega = document.getElementById('checkout-tipo-entrega')?.value || 'balcao';
    const obs = document.getElementById('checkout-obs')?.value.trim() || '';

    console.log('📋 Dados coletados:', { nome, telefone, email, tipoEntrega, obs });

    // Montar endereço a partir dos campos separados
    let endereco = '';
    let taxaEntrega = 0;

    if (tipoEntrega === 'delivery') {
        // Verificar se está usando endereço salvo
        if (window.enderecoSelecionadoId) {
            const enderecoSalvo = window.enderecosCliente?.find(e => e.id == window.enderecoSelecionadoId);
            if (enderecoSalvo) {
                taxaEntrega = parseFloat(document.getElementById('checkout-taxa-entrega')?.value) || 0;

                // Montar string de endereço a partir dos dados salvos
                endereco = enderecoSalvo.rua + ', ' + enderecoSalvo.numero;
                if (enderecoSalvo.complemento) endereco += ' - ' + enderecoSalvo.complemento;
                endereco += ', ' + enderecoSalvo.bairro;
                endereco += ' - ' + enderecoSalvo.cidade;
                if (enderecoSalvo.estado) endereco += '/' + enderecoSalvo.estado;
                if (enderecoSalvo.cep) endereco += ' - CEP: ' + enderecoSalvo.cep;

                console.log('📍 Usando endereço salvo:', enderecoSalvo);
            }
        } else {
            // Usar dados do formulário
            const cep = document.getElementById('checkout-cep')?.value.trim() || '';
            const rua = document.getElementById('checkout-rua')?.value.trim() || '';
            const numero = document.getElementById('checkout-numero')?.value.trim() || '';
            const complemento = document.getElementById('checkout-complemento')?.value.trim() || '';
            const bairro = document.getElementById('checkout-bairro-input')?.value.trim() || '';
            const cidade = document.getElementById('checkout-cidade-input')?.value.trim() || '';
            const uf = document.getElementById('checkout-uf')?.value.trim().toUpperCase() || '';

            taxaEntrega = parseFloat(document.getElementById('checkout-taxa-entrega')?.value) || 0;

            console.log('📍 Dados de endereço:', { cep, rua, numero, bairro, cidade, uf });

            // Verificar campos obrigatórios para delivery
            if (!rua || !numero) {
                alert('Por favor, preencha a rua e o número.');
                return;
            }

            if (!bairro || !cidade) {
                alert('Por favor, preencha o bairro e a cidade.');
                return;
            }

            // Montar string de endereço completa
            endereco = rua + ', ' + numero;
            if (complemento) endereco += ' - ' + complemento;
            endereco += ', ' + bairro;
            endereco += ' - ' + cidade;
            if (uf) endereco += '/' + uf;
            if (cep) endereco += ' - CEP: ' + cep;

            // Salvar endereço se checkbox marcado
            const salvarEnderecoCheck = document.getElementById('salvar-endereco-check');
            console.log('📝 Salvar endereço check:', salvarEnderecoCheck?.checked, 'Cliente ID:', window.clienteEncontradoId);

            if (salvarEnderecoCheck?.checked && window.clienteEncontradoId) {
                console.log('💾 Salvando novo endereço...');
                await salvarNovoEndereco();
            }
        }
    }

    if (!nome || !telefone) {
        alert('Por favor, preencha nome e telefone.');
        return;
    }

    if (!window.formaPagamentoSelecionada) {
        alert('Por favor, selecione uma forma de pagamento.');
        return;
    }

    console.log('✅ Validação passou, iniciando envio...');

    const btnFinalizar = document.querySelector('.btn-finalizar');
    if (btnFinalizar) {
        btnFinalizar.disabled = true;
        btnFinalizar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processando...';
    }

    // Mostrar overlay de loading
    mostrarLoadingPedido();

    const dados = {
        nome,
        telefone,
        email,
        endereco,
        taxa_entrega: taxaEntrega,
        tipo_entrega: tipoEntrega,
        forma_pagamento_id: parseInt(window.formaPagamentoSelecionada),
        forma_pagamento_tipo: window.formaPagamentoTipo || null,
        observacoes: obs,
        carrinho: window.carrinho
    };

    // Sempre enviar dados de endereço para delivery (backend decide se salva)
    // Para cliente novo: salva automaticamente
    // Para cliente existente: salva apenas se checkbox marcado
    if (tipoEntrega === 'delivery') {
        const cep = document.getElementById('checkout-cep')?.value.trim() || '';
        const rua = document.getElementById('checkout-rua')?.value.trim() || '';
        const numero = document.getElementById('checkout-numero')?.value.trim() || '';
        const complemento = document.getElementById('checkout-complemento')?.value.trim() || '';
        const bairro = document.getElementById('checkout-bairro-input')?.value.trim() || '';
        const cidade = document.getElementById('checkout-cidade-input')?.value.trim() || '';
        const uf = document.getElementById('checkout-uf')?.value.trim().toUpperCase() || '';
        const apelido = document.getElementById('endereco-apelido')?.value.trim() || 'Casa';

        const salvarEnderecoCheck = document.getElementById('salvar-endereco-check');

        // Enviar flag se checkbox marcado OU se cliente não foi encontrado (novo)
        dados.salvar_endereco = salvarEnderecoCheck?.checked || !window.clienteEncontradoId;
        dados.endereco_detalhado = {
            apelido,
            cep: cep.replace(/\D/g, ''),
            rua,
            numero,
            complemento,
            bairro,
            cidade,
            uf
        };
        console.log('💾 Dados de endereço para envio:', dados.endereco_detalhado, 'Salvar:', dados.salvar_endereco);
    }

    console.log('📦 Dados para envio:', dados);

    try {
        const response = await fetch('api/finalizar_pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });

        console.log('📡 Response status:', response.status);

        const responseText = await response.text();
        console.log('📩 Response text:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Erro ao parsear JSON:', parseError);
            fecharLoadingPedido();
            alert('Erro no servidor. Verifique o console.');
            if (btnFinalizar) {
                btnFinalizar.disabled = false;
                btnFinalizar.innerHTML = '<i class="fa-solid fa-check-circle"></i> Finalizar Pedido';
            }
            return;
        }

        console.log('📋 Result:', result);

        if (result.erro) {
            fecharLoadingPedido();

            // Verificar se precisa de verificação de primeiro pedido
            if (result.precisa_verificar) {
                const telefone = document.getElementById('checkout-telefone')?.value || '';
                const nome = document.getElementById('checkout-nome')?.value || '';
                mostrarModalVerificacao(telefone, nome);
            } else {
                alert('Erro: ' + result.erro);
            }

            if (btnFinalizar) {
                btnFinalizar.disabled = false;
                btnFinalizar.innerHTML = '<i class="fa-solid fa-check-circle"></i> Finalizar Pedido';
            }
            return;
        }

        if (result.sucesso) {
            console.log('🎉 Pedido finalizado com sucesso!');
            window.carrinho = [];
            salvarCarrinhoSalvo();
            atualizarCarrinho();

            // Salvar dados do cliente localmente para próximos pedidos
            salvarDadosClienteLocal({
                nome: document.getElementById('checkout-nome')?.value.trim() || '',
                telefone: document.getElementById('checkout-telefone')?.value.trim() || '',
                email: document.getElementById('checkout-email')?.value.trim() || ''
            });

            // Se for PIX Online, abrir modal
            if (result.pagamento_online && result.pix_data) {
                atualizarLoadingPedido('Gerando pagamento PIX...', 'Aguarde enquanto preparamos seu QR Code');

                setTimeout(() => {
                    fecharLoadingPedido();
                    fecharModal('modal-checkout');
                    // Fechar carrinho mobile se estiver aberto
                    const cartMobile = document.getElementById('modal-carrinho-mobile');
                    if (cartMobile) cartMobile.classList.remove('active');

                    mostrarModalPix(result);
                }, 800);
            } else {
                // Fluxo normal - atualizar mensagem e depois redirecionar
                atualizarLoadingPedido('Pedido confirmado! ✓', 'Redirecionando para confirmação...');

                setTimeout(() => {
                    fecharLoadingPedido();
                    fecharModal('modal-checkout');
                    window.location.href = 'confirmacao.php?codigo=' + result.codigo;
                }, 1500);
            }
        }
    } catch (error) {
        console.error('❌ Erro:', error);
        fecharLoadingPedido();
        alert('Erro ao finalizar pedido. Tente novamente.');
        if (btnFinalizar) {
            btnFinalizar.disabled = false;
            btnFinalizar.innerHTML = '<i class="fa-solid fa-check-circle"></i> Finalizar Pedido';
        }
    }
}

// ===== FUNÇÕES DE PIX MODAL =====

window.pollingInterval = null;

window.mostrarModalPix = function (dados) {
    const modal = document.getElementById('modal-pagamento-pix');
    if (!modal) return;

    // Popular dados
    document.getElementById('pix-codigo-pedido').textContent = '#' + dados.codigo;
    document.getElementById('pix-qrcode-img').src = 'data:image/png;base64,' + dados.pix_data.qr_code_base64;
    document.getElementById('pix-copia-cola').value = dados.pix_data.qr_code;

    // Mostrar modal
    modal.style.display = 'flex';

    // Iniciar verificação de pagamento
    iniciarPollingPagamento(dados.pedido_id, dados.codigo);
};

window.fecharModalPix = function () {
    const modal = document.getElementById('modal-pagamento-pix');
    if (modal) modal.style.display = 'none';

    // Parar polling
    if (window.pollingInterval) clearInterval(window.pollingInterval);

    // Redirecionar para confirmação mesmo se fechou (assume que vai pagar depois ou desistiu, mas pedido tá lá)
    // O usuário pode reabrir? Por enquanto vamos redirecionar para evitar pedido perdido na tela
    const codigo = document.getElementById('pix-codigo-pedido').textContent.replace('#', '');
    if (codigo && codigo !== '-------') {
        window.location.href = 'confirmacao.php?codigo=' + codigo;
    }
};

window.copiarPix = function () {
    const textarea = document.getElementById('pix-copia-cola');
    textarea.select();
    document.execCommand('copy');

    const feedback = document.getElementById('copy-feedback');
    feedback.style.display = 'block';
    setTimeout(() => {
        feedback.style.display = 'none';
    }, 2000);
};

// Função para enviar comprovante no WhatsApp
window.enviarComprovanteZap = function () {
    const pedidoEl = document.getElementById('pix-codigo-pedido');
    let pedidoId = '000';

    if (pedidoEl) {
        pedidoId = pedidoEl.innerText.replace('#', '').trim();
    }

    // Obter número do config
    let telefone = '5511932261834'; // Fallback padrão

    if (window.siteConfig && window.siteConfig.whatsapp) {
        // Remover não dígitos
        let rawPhone = window.siteConfig.whatsapp.replace(/\D/g, '');

        // Se tiver numero valido
        if (rawPhone.length >= 10) {
            telefone = rawPhone;
            // Adicionar 55 se não tiver (assumindo BR)
            if (telefone.length <= 11) {
                telefone = '55' + telefone;
            }
        }
    }

    const msg = `Olá, realizei o pagamento do pedido #${pedidoId}. Segue o comprovante em anexo.`;
    const url = `https://wa.me/${telefone}?text=${encodeURIComponent(msg)}`;

    window.open(url, '_blank');
};

window.iniciarPollingPagamento = function (pedidoId, codigoPedido) {
    if (window.pollingInterval) clearInterval(window.pollingInterval);

    window.pollingInterval = setInterval(async () => {
        try {
            const response = await fetch(`api/consultar_status_pagamento.php?pedido_id=${pedidoId}`);
            const data = await response.json();

            if (data.status === 'aprovado' || data.status === 'pago') {
                clearInterval(window.pollingInterval);
                // Redirecionar para confirmação
                window.location.href = 'confirmacao.php?codigo=' + codigoPedido;
            }
        } catch (error) {
            console.error('Erro no polling:', error);
        }
    }, 5000); // Checa a cada 5 segundos
};

window.selectEntrega = function (el, tipo) {
    document.querySelectorAll('.tipo-entrega-item').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('checkout-entrega').value = tipo;
    document.getElementById('checkout-endereco-group').style.display = tipo === 'delivery' ? 'block' : 'none';
};

window.selectPagamento = function (el, tipo) {
    document.querySelectorAll('.forma-pagamento-item').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('checkout-pagamento').value = tipo;
};

window.finalizarPedido = function () {
    const nome = document.getElementById('checkout-nome').value;
    const telefone = document.getElementById('checkout-telefone').value;
    const entrega = document.getElementById('checkout-entrega').value;
    const endereco = document.getElementById('checkout-endereco').value;
    const pagamento = document.getElementById('checkout-pagamento').value;

    if (!nome || !telefone) {
        alert('Preencha nome e telefone');
        return;
    }
    if (entrega === 'delivery' && !endereco) {
        alert('Preencha o endereço');
        return;
    }

    const pedido = {
        cliente: { nome, telefone, endereco },
        itens: window.carrinho,
        tipo_entrega: entrega,
        pagamento: pagamento,
        total: window.carrinho.reduce((acc, item) => acc + (calcularPrecoTotalItem(item) * item.quantidade), 0)
    };

    fetch('api/checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(pedido)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.carrinho = [];
                salvarCarrinhoSalvo();
                atualizarCarrinho();
                fecharModal();

                if (data.pagamento_online && data.pix_data) {
                    // Mostrar modal PIX (simplificado)
                    alert('Pedido realizado! Pagamento PIX: ' + data.pix_data.qr_code);
                } else {
                    alert('Pedido realizado com sucesso! #' + data.codigo_pedido);
                }
                window.location.reload();
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(err => console.error(err));
};

// ===== FUNÇÕES DE ENTREGA =====

// Dados globais de entrega
window.dadosEntrega = {
    config: null,
    cidades: [],
    bairroSelecionado: null,
    taxaEntrega: 0
};

// Carregar configuração de entrega
window.carregarDadosEntrega = async function () {
    try {
        const response = await fetch('api/get_bairros_entrega.php');
        const data = await response.json();

        if (data.sucesso) {
            window.dadosEntrega.config = data.config;
            window.dadosEntrega.cidades = data.cidades;

            // Inicializar lista plana de bairros (independente da UI)
            window.dadosEntrega.todosBairros = [];

            if (data.cidades && data.cidades.length > 0) {
                data.cidades.forEach(cidade => {
                    if (cidade.bairros && cidade.bairros.length > 0) {
                        cidade.bairros.forEach(bairro => {
                            window.dadosEntrega.todosBairros.push({
                                ...bairro,
                                cidade_nome: cidade.nome,
                                cidade_id: cidade.id
                            });
                        });
                    }
                });
            }

            // Atualizar select de cidades (se existir)
            const selectCidade = document.getElementById('checkout-cidade');
            if (selectCidade) {
                selectCidade.innerHTML = '<option value="">Selecione sua cidade</option>';
                data.cidades.forEach(cidade => {
                    selectCidade.innerHTML += `<option value="${cidade.id}" data-estado="${cidade.estado}">${cidade.nome} - ${cidade.estado}</option>`;
                });
            }

            // Mostrar seleção de bairro se modo por bairro estiver ativo
            const selecaoBairro = document.getElementById('selecao-bairro-container');
            if (selecaoBairro && data.config.modo_por_bairro_ativo && data.cidades.length > 0) {
                // Manter oculto pois agora usamos input de texto, mas a lógica de taxa precisa estar ativa
                selecaoBairro.style.display = 'none';
            }

            // Adicionar listener para o input de bairro para calcular taxa ao digitar
            const bairroInput = document.getElementById('checkout-bairro-input');
            if (bairroInput) {
                bairroInput.addEventListener('blur', function () {
                    calcularTaxaPorBairroTexto(this.value);
                });

                // Opcional: calcular enquanto digita com debounce (pode ser irritante se mudar muito rápido)
                // bairroInput.addEventListener('input', debounce(function() {
                //    calcularTaxaPorBairroTexto(this.value);
                // }, 1000));
            }
        }
    } catch (error) {
        console.error('Erro ao carregar dados de entrega:', error);
    }
};

// Carregar bairros de uma cidade
window.carregarBairrosCidade = function () {
    const selectCidade = document.getElementById('checkout-cidade');
    const selectBairro = document.getElementById('checkout-bairro');
    const cidadeId = selectCidade.value;

    if (!cidadeId || !selectBairro) {
        selectBairro.innerHTML = '<option value="">Selecione a cidade primeiro</option>';
        return;
    }

    // Buscar cidade nos dados
    const cidade = window.dadosEntrega.cidades.find(c => c.id == cidadeId);

    if (cidade && cidade.bairros) {
        selectBairro.innerHTML = '<option value="">Selecione seu bairro</option>';
        cidade.bairros.forEach(bairro => {
            const gratis = bairro.gratis_acima_de ? ` (Grátis acima de R$ ${bairro.gratis_acima_de.toFixed(2).replace('.', ',')})` : '';
            selectBairro.innerHTML += `<option value="${bairro.id}" data-valor="${bairro.valor_entrega}" data-gratis="${bairro.gratis_acima_de || 0}">${bairro.nome} - R$ ${bairro.valor_entrega.toFixed(2).replace('.', ',')}${gratis}</option>`;
        });
    } else {
        selectBairro.innerHTML = '<option value="">Nenhum bairro cadastrado</option>';
    }

    // Limpar taxa
    atualizarTaxaEntrega();
};

// Função auxiliar para calcular subtotal do carrinho
function calcularSubtotalCarrinho() {
    let subtotal = 0;
    if (window.carrinho && window.carrinho.length > 0) {
        window.carrinho.forEach(item => {
            subtotal += calcularPrecoTotalItem(item) * item.quantidade;
        });
    }
    return subtotal;
}

// Função para calcular taxa baseada no TEXTO do bairro
// Função para calcular taxa baseada no TEXTO do bairro e cidade
// Respeita os modos de entrega: 1) Gratis Todos, 2) Valor Fixo, 3) Gratis Valor, 4) Por Bairro
window.calcularTaxaPorBairroTexto = async function (nomeBairro, nomeCidade = null) {
    // Garantir que os dados de entrega estão carregados
    if (!window.dadosEntrega || !window.dadosEntrega.config) {
        console.log('⏳ Carregando dados de entrega antes de calcular taxa...');
        await carregarDadosEntrega();
    }

    const config = window.dadosEntrega.config;

    // Debug: Mostrar modos ativos
    console.log('📊 Modos de entrega:', {
        gratisTodos: config.modo_gratis_todos_ativo,
        valorFixo: config.modo_valor_fixo_ativo,
        gratisValor: config.modo_gratis_valor_ativo,
        porBairro: config.modo_por_bairro_ativo
    });

    // MODO 2: Entrega Grátis para Todos (maior prioridade)
    if (config.modo_gratis_todos_ativo) {
        console.log('🎁 Modo Grátis para Todos ativo - Taxa: R$ 0,00');
        aplicarTaxa(0);
        return;
    }

    // MODO 3: Valor Fixo Único
    if (config.modo_valor_fixo_ativo) {
        const valorFixo = parseFloat(config.valor_fixo_entrega) || 0;
        console.log('💵 Modo Valor Fixo ativo - Taxa: R$', valorFixo);

        // Verificar se modo gratis_valor está ativo junto (pode ter promo)
        if (config.modo_gratis_valor_ativo) {
            const subtotal = calcularSubtotalCarrinho();
            const valorMinimoGratis = parseFloat(config.valor_minimo_gratis) || 0;
            if (subtotal >= valorMinimoGratis) {
                console.log('🎉 Grátis! Subtotal R$', subtotal, '>= Mínimo R$', valorMinimoGratis);
                aplicarTaxa(0);
                return;
            }
        }

        aplicarTaxa(valorFixo);
        return;
    }

    // MODO 1: Grátis a partir de X (se não tem modo por bairro)
    if (config.modo_gratis_valor_ativo && !config.modo_por_bairro_ativo) {
        const subtotal = calcularSubtotalCarrinho();
        const valorMinimoGratis = parseFloat(config.valor_minimo_gratis) || 0;
        if (subtotal >= valorMinimoGratis) {
            console.log('🎉 Grátis! Subtotal R$', subtotal, '>= Mínimo R$', valorMinimoGratis);
            aplicarTaxa(0);
        } else {
            console.log('⚠️ Modo grátis valor ativo, mas subtotal abaixo do mínimo');
            aplicarTaxa(0); // Se não tem outro modo, define como 0
        }
        return;
    }

    // MODO 5: Por Bairro/Região
    if (!config.modo_por_bairro_ativo) {
        // Nenhum modo de taxa ativo
        console.log('ℹ️ Nenhum modo de taxa ativo - Taxa: R$ 0,00');
        aplicarTaxa(0);
        return;
    }

    // Continuar com cálculo por bairro se o modo por bairro está ativo
    if (!nomeBairro) return;

    // Garantir que os dados de entrega estão carregados
    if (!window.dadosEntrega || !window.dadosEntrega.todosBairros || window.dadosEntrega.todosBairros.length === 0) {
        console.log('⏳ Carregando dados de entrega antes de calcular taxa...');
        await carregarDadosEntrega();
    }

    // Normalizar nomes para comparação (minúsculas, sem acentos)
    const bairroNormalizado = nomeBairro.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
    const cidadeNormalizada = nomeCidade ? nomeCidade.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim() : null;

    // Se não temos a cidade, tentar pegar do campo
    let cidadeParaBusca = cidadeNormalizada;
    if (!cidadeParaBusca) {
        const cidadeInput = document.getElementById('checkout-cidade-input');
        if (cidadeInput && cidadeInput.value) {
            cidadeParaBusca = cidadeInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
        }
    }

    // Tentar encontrar bairro na lista carregada
    let bairroEncontrado = null;

    if (window.dadosEntrega && window.dadosEntrega.todosBairros) {
        console.log('🔍 Buscando bairro:', bairroNormalizado, cidadeParaBusca ? `na cidade: ${cidadeParaBusca}` : '(sem cidade)');

        // Filtrar bairros que correspondem ao nome (exato ou parcial)
        const bairrosComNome = window.dadosEntrega.todosBairros.filter(b => {
            const bNome = b.nome.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
            return bNome === bairroNormalizado || bNome.includes(bairroNormalizado) || bairroNormalizado.includes(bNome);
        });

        console.log(`📋 Encontrados ${bairrosComNome.length} bairro(s) com nome "${bairroNormalizado}":`,
            bairrosComNome.map(b => `${b.nome} (${b.cidade_nome})`));

        if (bairrosComNome.length === 0) {
            // Nenhum bairro encontrado
            bairroEncontrado = null;
        } else if (bairrosComNome.length === 1) {
            // Apenas 1 bairro com esse nome, usar ele
            bairroEncontrado = bairrosComNome[0];
        } else {
            // Múltiplos bairros com mesmo nome - PRECISA da cidade para desempatar

            // Se não temos cidade ainda, tentar ler do campo novamente
            if (!cidadeParaBusca) {
                const cidadeInput = document.getElementById('checkout-cidade-input');
                if (cidadeInput && cidadeInput.value) {
                    cidadeParaBusca = cidadeInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
                    console.log('📍 Cidade lida do campo:', cidadeParaBusca);
                }
            }

            if (cidadeParaBusca) {
                // Tentar match exato de cidade
                bairroEncontrado = bairrosComNome.find(b => {
                    const cNome = b.cidade_nome.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
                    return cNome === cidadeParaBusca;
                });

                // Se não achou exato, tentar match parcial de cidade
                if (!bairroEncontrado) {
                    bairroEncontrado = bairrosComNome.find(b => {
                        const cNome = b.cidade_nome.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
                        return cNome.includes(cidadeParaBusca) || cidadeParaBusca.includes(cNome);
                    });
                }

                if (!bairroEncontrado) {
                    console.log(`⚠️ Cidade "${cidadeParaBusca}" não encontrada entre os bairros "${bairroNormalizado}"`);
                }
            } else {
                // Sem cidade para desempatar, pegar o primeiro (fallback)
                console.log('⚠️ Múltiplos bairros e sem cidade no formulário - usando primeiro da lista');
                bairroEncontrado = bairrosComNome[0];
            }
        }
    }

    const taxaInfo = document.getElementById('taxa-entrega-info');
    const taxaValor = document.getElementById('taxa-entrega-valor');
    const taxaInput = document.getElementById('checkout-taxa-entrega');

    // Se não encontrou bairro ou não tem lista
    if (!bairroEncontrado) {
        console.log('❌ Bairro não encontrado na lista de taxas:', nomeBairro, nomeCidade);

        // Verificar se tem taxa padrão ou fixa
        const config = window.dadosEntrega.config;
        if (config && config.modo_valor_fixo_ativo) {
            aplicarTaxa(config.valor_fixo_entrega);
        } else {
            // Caso não encontre e não seja fixo, define como 0
            aplicarTaxa(0);
            if (taxaInfo) {
                taxaValor.innerHTML = '<span style="color: #ffc107;">A calcular</span>';
            }
        }
        return;
    }

    console.log('✅ Bairro encontrado:', bairroEncontrado.nome, 'Cidade:', bairroEncontrado.cidade_nome, 'Taxa:', bairroEncontrado.valor_entrega);

    // Se encontrou, pega os valores
    const valorTaxa = parseFloat(bairroEncontrado.valor_entrega) || 0;
    const gratisAcimaDe = parseFloat(bairroEncontrado.gratis_acima_de) || 0;

    aplicarTaxaEVerificarGratis(valorTaxa, gratisAcimaDe);
};

// Função auxiliar para aplicar a taxa e verificar regras de grátis
function aplicarTaxaEVerificarGratis(valorTaxa, gratisAcimaDe) {
    // Calcular subtotal
    let subtotal = 0;
    window.carrinho.forEach(item => {
        subtotal += calcularPrecoTotalItem(item) * item.quantidade;
    });

    let taxaFinal = valorTaxa;
    const config = window.dadosEntrega.config;

    if (config) {
        // Modo grátis para todos
        if (config.modo_gratis_todos_ativo) {
            taxaFinal = 0;
        }
        // Modo grátis acima de valor mínimo global
        else if (config.modo_gratis_valor_ativo && subtotal >= config.valor_minimo_gratis) {
            taxaFinal = 0;
        }
        // Grátis acima de valor do bairro (específico)
        else if (gratisAcimaDe > 0 && subtotal >= gratisAcimaDe) {
            taxaFinal = 0;
        }
    }

    aplicarTaxa(taxaFinal);
}

// Função para recalcular taxa lendo bairro E cidade do formulário
window.recalcularTaxaComCidade = function () {
    const bairroInput = document.getElementById('checkout-bairro-input');
    const cidadeInput = document.getElementById('checkout-cidade-input');

    const bairro = bairroInput?.value?.trim() || '';
    const cidade = cidadeInput?.value?.trim() || '';

    if (bairro) {
        console.log('🔄 Recalculando taxa - Bairro:', bairro, 'Cidade:', cidade);
        calcularTaxaPorBairroTexto(bairro, cidade);
    }
};

function aplicarTaxa(valor) {
    const taxaInfo = document.getElementById('taxa-entrega-info');
    const taxaValor = document.getElementById('taxa-entrega-valor');
    const taxaInput = document.getElementById('checkout-taxa-entrega');

    window.dadosEntrega.taxaEntrega = valor;

    if (taxaInfo && taxaValor) {
        taxaInfo.style.display = 'block';
        if (valor === 0) {
            taxaValor.innerHTML = '<span style="color: #28a745;">GRÁTIS!</span>';
        } else {
            taxaValor.textContent = 'R$ ' + valor.toFixed(2).replace('.', ',');
        }
    }

    if (taxaInput) taxaInput.value = valor;

    atualizarTotalCheckout();
}

// Atualizar taxa de entrega (Mantida para compatibilidade, mas redirecionada ou obsoleta)
window.atualizarTaxaEntrega = function () {
    // Esta função era chamada pelo select. Agora usamos calcularTaxaPorBairroTexto.
    // Se necessário, manter lógica antiga aqui ou remover.
};

// Atualizar total do checkout
window.atualizarTotalCheckout = function () {
    let subtotal = 0;
    window.carrinho.forEach(item => {
        subtotal += calcularPrecoTotalItem(item) * item.quantidade;
    });

    const tipoEntrega = document.getElementById('checkout-tipo-entrega')?.value || 'balcao';
    const taxa = tipoEntrega === 'delivery' ? (window.dadosEntrega.taxaEntrega || 0) : 0;
    const total = subtotal + taxa;

    const resumoEntrega = document.getElementById('resumo-entrega');
    const valorEntrega = document.getElementById('valor-entrega');
    const checkoutTotal = document.getElementById('checkout-total');

    if (resumoEntrega && valorEntrega) {
        if (tipoEntrega === 'delivery' && taxa > 0) {
            resumoEntrega.style.display = 'flex';
            valorEntrega.textContent = 'R$ ' + taxa.toFixed(2).replace('.', ',');
        } else if (tipoEntrega === 'delivery' && taxa === 0 && window.dadosEntrega.bairroSelecionado) {
            resumoEntrega.style.display = 'flex';
            valorEntrega.innerHTML = '<span style="color: #28a745;">GRÁTIS</span>';
        } else {
            resumoEntrega.style.display = 'none';
        }
    }

    if (checkoutTotal) {
        checkoutTotal.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    }
};

// ===== FUNÇÕES DE BUSCA DE CLIENTE =====

// Debounce timer para busca de cliente
let buscaClienteTimer = null;

// Inicializar busca de cliente quando checkout é aberto
window.inicializarBuscaCliente = function () {
    const telefoneInput = document.getElementById('checkout-telefone');
    if (!telefoneInput) return;

    // Remover evento antigo se existir
    telefoneInput.removeEventListener('input', handleTelefoneInput);
    telefoneInput.addEventListener('input', handleTelefoneInput);
};

// Handler para input do telefone
function handleTelefoneInput(e) {
    const telefone = e.target.value.replace(/\D/g, '');

    // Limpar timer anterior
    if (buscaClienteTimer) {
        clearTimeout(buscaClienteTimer);
    }

    // Só buscar se tiver pelo menos 10 dígitos
    if (telefone.length >= 10) {
        // Mostrar loading
        const feedback = document.getElementById('cliente-feedback');
        if (feedback) {
            feedback.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Buscando...';
            feedback.style.display = 'block';
            feedback.className = 'cliente-feedback loading';
        }

        // Debounce de 800ms para não fazer muitas requisições
        buscaClienteTimer = setTimeout(() => {
            buscarClientePorTelefone(telefone);
        }, 800);
    }
}

// Buscar cliente por telefone na API
window.buscarClientePorTelefone = async function (telefone) {
    try {
        const response = await fetch(`api/buscar_cliente_telefone.php?telefone=${telefone}`);
        const data = await response.json();

        const feedback = document.getElementById('cliente-feedback');

        if (data.sucesso && data.encontrado && data.cliente) {
            const cliente = data.cliente;

            // Preencher campos pessoais
            const nomeInput = document.getElementById('checkout-nome');
            const emailInput = document.getElementById('checkout-email');

            if (nomeInput && cliente.nome) nomeInput.value = cliente.nome;
            if (emailInput && cliente.email) emailInput.value = cliente.email;

            // Guardar ID do cliente
            window.clienteEncontradoId = cliente.id;

            // Buscar endereços salvos do cliente PRIMEIRO
            let enderecosSalvos = [];
            try {
                const telefoneLimpo = telefone.replace(/\D/g, '');
                const enderecosResp = await fetch(`api/get_enderecos_cliente.php?telefone=${telefoneLimpo}`);
                const enderecosData = await enderecosResp.json();

                if (enderecosData.sucesso && enderecosData.enderecos && enderecosData.enderecos.length > 0) {
                    enderecosSalvos = enderecosData.enderecos;
                    window.enderecosCliente = enderecosSalvos;
                    console.log(`📍 Cliente tem ${enderecosSalvos.length} endereço(s) salvo(s)`);
                }
            } catch (e) {
                console.log('Erro ao buscar endereços:', e);
            }

            // Referências aos campos de endereço
            const cepInput = document.getElementById('checkout-cep');
            const ruaInput = document.getElementById('checkout-rua');
            const numeroInput = document.getElementById('checkout-numero');
            const complementoInput = document.getElementById('checkout-complemento');
            const bairroInput = document.getElementById('checkout-bairro-input');
            const cidadeInput = document.getElementById('checkout-cidade-input');
            const ufInput = document.getElementById('checkout-uf');

            // Se tem MÚLTIPLOS endereços salvos, NÃO preencher automaticamente
            // O seletor será mostrado quando clicar em Delivery
            if (enderecosSalvos.length > 1) {
                console.log('🏠 Múltiplos endereços - aguardando seleção do usuário');
                // Limpar campos de endereço para forçar seleção
                if (cepInput) cepInput.value = '';
                if (ruaInput) ruaInput.value = '';
                if (numeroInput) numeroInput.value = '';
                if (complementoInput) complementoInput.value = '';
                if (bairroInput) bairroInput.value = '';
                if (cidadeInput) cidadeInput.value = '';
                if (ufInput) ufInput.value = '';
            } else if (enderecosSalvos.length === 1) {
                // Se tem apenas 1 endereço salvo, usar ele
                const end = enderecosSalvos[0];
                console.log('🏠 1 endereço salvo - preenchendo automaticamente');
                if (cepInput) cepInput.value = end.cep || '';
                if (ruaInput) ruaInput.value = end.rua || '';
                if (numeroInput) numeroInput.value = end.numero || '';
                if (complementoInput) complementoInput.value = end.complemento || '';
                if (bairroInput) {
                    bairroInput.value = end.bairro || '';
                    if (end.bairro) calcularTaxaPorBairroTexto(end.bairro);
                }
                if (cidadeInput) cidadeInput.value = end.cidade || '';
                if (ufInput) ufInput.value = end.estado || '';
                window.enderecoSelecionadoId = end.id;
            } else {
                // Sem endereços salvos - usar dados da tabela clientes (legado)
                console.log('🏠 Sem endereços salvos - usando dados do cliente');
                if (cepInput && cliente.cep) cepInput.value = cliente.cep;
                if (ruaInput && cliente.rua) ruaInput.value = cliente.rua;
                if (numeroInput && cliente.numero) numeroInput.value = cliente.numero;
                if (complementoInput && cliente.complemento) complementoInput.value = cliente.complemento;
                if (bairroInput && cliente.bairro) {
                    bairroInput.value = cliente.bairro;
                    calcularTaxaPorBairroTexto(cliente.bairro);
                }
                if (cidadeInput && cliente.cidade) cidadeInput.value = cliente.cidade;
                if (ufInput && cliente.estado) ufInput.value = cliente.estado;
            }

            // Se tem endereço, mostrar seção de delivery automaticamente
            if (enderecosSalvos.length > 0 || cliente.rua || cliente.bairro || cliente.cidade) {
                const deliveryBtn = document.querySelector('.tipo-entrega-btn[data-tipo="delivery"]');
                if (deliveryBtn) {
                    selecionarTipoEntregaCheckout(deliveryBtn, 'delivery');
                }
            }

            // Mostrar feedback de sucesso
            if (feedback) {
                feedback.innerHTML = '<i class="fa-solid fa-check-circle"></i> Dados do cliente carregados!';
                feedback.className = 'cliente-feedback success';
                feedback.style.display = 'block';

                // Esconder após 3 segundos
                setTimeout(() => {
                    feedback.style.opacity = '0';
                    setTimeout(() => {
                        feedback.style.display = 'none';
                        feedback.style.opacity = '1';
                    }, 300);
                }, 3000);
            }

            // Toast de sucesso
            mostrarToast('Dados do cliente carregados!', 'success');


        } else {
            // Não encontrado - limpar feedback
            if (feedback) {
                feedback.innerHTML = '<i class="fa-solid fa-user-plus"></i> Cliente novo - preencha seus dados';
                feedback.className = 'cliente-feedback info';
                feedback.style.display = 'block';
            }
            window.clienteEncontradoId = null;
        }

    } catch (error) {
        console.error('Erro ao buscar cliente:', error);
        const feedback = document.getElementById('cliente-feedback');
        if (feedback) {
            feedback.style.display = 'none';
        }
    }
};

// Função para mostrar toast
window.mostrarToast = function (mensagem, tipo = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${tipo === 'success' ? '#28a745' : tipo === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = `<i class="fa-solid fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'times-circle' : 'info-circle'}"></i> ${mensagem}`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// ===== FUNÇÕES DE CEP =====

// Formatar CEP e auto-buscar quando completo
window.formatarCEP = function (input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 8) value = value.slice(0, 8);

    // Formatar com hífen
    if (value.length > 5) {
        input.value = `${value.slice(0, 5)}-${value.slice(5)}`;
    } else {
        input.value = value;
    }

    // Auto-buscar quando tiver 8 dígitos
    if (value.length === 8) {
        buscarCEP();
    }
};

// Buscar CEP via ViaCEP
window.buscarCEP = async function () {
    const cepInput = document.getElementById('checkout-cep');
    const cep = cepInput.value.replace(/\D/g, '');

    if (cep.length !== 8) {
        mostrarToast('Digite um CEP válido com 8 dígitos', 'error');
        return;
    }

    try {
        // Mostrar loading
        const btn = document.querySelector('.btn-buscar-cep');
        const btnOriginal = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();

        btn.innerHTML = btnOriginal;
        btn.disabled = false;

        if (data.erro) {
            mostrarToast('CEP não encontrado', 'error');
            return;
        }

        // Preencher TODOS os campos primeiro
        const ruaInput = document.getElementById('checkout-rua');
        const bairroInput = document.getElementById('checkout-bairro-input');
        const cidadeInput = document.getElementById('checkout-cidade-input');
        const ufInput = document.getElementById('checkout-uf');

        if (ruaInput && data.logradouro) ruaInput.value = data.logradouro;
        if (bairroInput && data.bairro) bairroInput.value = data.bairro;
        if (cidadeInput && data.localidade) cidadeInput.value = data.localidade;
        if (ufInput && data.uf) ufInput.value = data.uf;

        // DEPOIS de preencher tudo, calcular taxa com bairro + cidade
        if (data.bairro && data.localidade) {
            console.log('🚀 CEP preenchido - calculando taxa com cidade:', data.localidade);
            calcularTaxaPorBairroTexto(data.bairro, data.localidade);
        } else if (data.bairro) {
            calcularTaxaPorBairroTexto(data.bairro);
        }

        // Focar no número
        const numeroInput = document.getElementById('checkout-numero');
        if (numeroInput) numeroInput.focus();

        mostrarToast('Endereço encontrado!', 'success');

    } catch (error) {
        console.error('Erro ao buscar CEP:', error);
        mostrarToast('Erro ao buscar CEP. Tente novamente.', 'error');

        const btn = document.querySelector('.btn-buscar-cep');
        btn.innerHTML = '<i class="fa-solid fa-search"></i> Buscar';
        btn.disabled = false;
    }
};

// ===== FUNÇÕES DE MÚLTIPLOS ENDEREÇOS =====

// Mostrar seletor de endereços salvos
window.mostrarSeletorEnderecos = function (enderecos) {
    // Verificar se a seção de endereço já existe no DOM
    const enderecoSection = document.getElementById('checkout-endereco-section');
    if (!enderecoSection) return;

    // Remover seletor anterior se existir
    const seletorAnterior = document.getElementById('endereco-selector');
    if (seletorAnterior) seletorAnterior.remove();

    // Criar HTML do seletor
    let html = `
        <div id="endereco-selector" style="margin-bottom:20px; padding:15px; background:#1e2433; border-radius:12px; border:1px solid #2d3446;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                <i class="fa-solid fa-location-dot" style="color:#4a66f9; font-size:1.2rem;"></i>
                <h4 style="margin:0; color:#e2e8f0; font-size:1rem;">Onde entregar?</h4>
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
    `;

    enderecos.forEach((end, index) => {
        const apelido = end.apelido || 'Endereço ' + (index + 1);
        const principal = end.principal ? '<span style="background:#10b981; color:white; font-size:0.7rem; padding:2px 6px; border-radius:4px; margin-left:8px;">Principal</span>' : '';

        html += `
            <label style="display:flex; align-items:center; gap:12px; padding:12px; background:#0f131a; border-radius:8px; cursor:pointer; border:2px solid transparent; transition:all 0.2s;" 
                   class="endereco-option" data-endereco-id="${end.id}"
                   onclick="selecionarEndereco(${end.id})">
                <input type="radio" name="endereco_selecionado" value="${end.id}" 
                       ${index === 0 ? 'checked' : ''} 
                       style="width:18px; height:18px; accent-color:#4a66f9;">
                <div style="flex:1;">
                    <div style="color:#e2e8f0; font-weight:600;">${apelido}${principal}</div>
                    <div style="color:#a0aec0; font-size:0.85rem; margin-top:3px;">${end.endereco_completo}</div>
                </div>
            </label>
        `;
    });

    // Opção de novo endereço
    html += `
            <label style="display:flex; align-items:center; gap:12px; padding:12px; background:#0f131a; border-radius:8px; cursor:pointer; border:2px solid transparent; transition:all 0.2s;" 
                   class="endereco-option" data-endereco-id="novo"
                   onclick="selecionarEndereco('novo')">
                <input type="radio" name="endereco_selecionado" value="novo" 
                       style="width:18px; height:18px; accent-color:#4a66f9;">
                <div style="flex:1;">
                    <div style="color:#4a66f9; font-weight:600;"><i class="fa-solid fa-plus"></i> Adicionar novo endereço</div>
                </div>
            </label>
        </div>
    </div>
    `;

    // Inserir seletor antes dos campos de endereço
    enderecoSection.insertAdjacentHTML('afterbegin', html);

    // Selecionar primeiro endereço automaticamente
    if (enderecos.length > 0) {
        selecionarEndereco(enderecos[0].id);
    }
};

// Selecionar endereço da lista
window.selecionarEndereco = function (enderecoId) {
    // Atualizar estilos visuais
    document.querySelectorAll('.endereco-option').forEach(opt => {
        opt.style.borderColor = 'transparent';
    });
    const selectedOption = document.querySelector(`.endereco-option[data-endereco-id="${enderecoId}"]`);
    if (selectedOption) {
        selectedOption.style.borderColor = '#4a66f9';
        const radio = selectedOption.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    }

    // Ocultar/Mostrar formulário de endereço
    const formEnderecoFields = document.getElementById('form-endereco-fields');
    const salvarEnderecoCheckbox = document.getElementById('salvar-endereco-container');

    if (enderecoId === 'novo') {
        // Mostrar formulário
        if (formEnderecoFields) formEnderecoFields.style.display = 'block';
        if (salvarEnderecoCheckbox) salvarEnderecoCheckbox.style.display = 'flex';

        // Limpar campos
        ['checkout-cep', 'checkout-rua', 'checkout-numero', 'checkout-complemento',
            'checkout-bairro-input', 'checkout-cidade-input', 'checkout-uf'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });

        window.enderecoSelecionadoId = null;

        // Resetar taxa para "A calcular" sem disparar busca
        const taxaInfo = document.getElementById('taxa-entrega-info');
        const taxaValor = document.getElementById('taxa-entrega-valor');
        const taxaInput = document.getElementById('checkout-taxa-entrega');
        if (taxaInfo) taxaInfo.style.display = 'block';
        if (taxaValor) taxaValor.innerHTML = '<span style="color: #ffc107;">A calcular</span>';
        if (taxaInput) taxaInput.value = '0';
        window.dadosEntrega.taxaEntrega = 0;
    } else {
        // Ocultar formulário e preencher com dados do endereço selecionado
        if (formEnderecoFields) formEnderecoFields.style.display = 'none';
        if (salvarEnderecoCheckbox) salvarEnderecoCheckbox.style.display = 'none';

        // Buscar dados do endereço selecionado
        const endereco = window.enderecosCliente?.find(e => e.id == enderecoId);
        if (endereco) {
            window.enderecoSelecionadoId = enderecoId;

            // Calcular taxa baseado no bairro E cidade
            if (endereco.bairro) {
                calcularTaxaPorBairroTexto(endereco.bairro, endereco.cidade);
            }
        }
    }
};

// Salvar novo endereço
window.salvarNovoEndereco = async function () {
    console.log('=== salvarNovoEndereco chamado ===');
    console.log('Cliente ID:', window.clienteEncontradoId);

    if (!window.clienteEncontradoId) {
        console.log('❌ Cliente não identificado, endereço não será salvo');
        return;
    }

    const dados = {
        cliente_id: window.clienteEncontradoId,
        apelido: document.getElementById('endereco-apelido')?.value || null,
        cep: document.getElementById('checkout-cep')?.value || null,
        rua: document.getElementById('checkout-rua')?.value || null,
        numero: document.getElementById('checkout-numero')?.value || null,
        complemento: document.getElementById('checkout-complemento')?.value || null,
        bairro: document.getElementById('checkout-bairro-input')?.value || null,
        cidade: document.getElementById('checkout-cidade-input')?.value || null,
        estado: document.getElementById('checkout-uf')?.value || null,
        principal: 0
    };

    console.log('📦 Dados do endereço a salvar:', dados);

    try {
        const response = await fetch('api/salvar_endereco.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        const result = await response.json();
        console.log('📡 Resposta da API:', result);

        if (result.sucesso) {
            mostrarToast('Endereço salvo!', 'success');
            window.enderecoSelecionadoId = result.endereco_id;
        } else {
            console.error('❌ Erro ao salvar:', result.erro);
        }
    } catch (e) {
        console.error('❌ Erro na requisição:', e);
    }
};

// ===== LOADING OVERLAY =====

// Mostrar overlay de loading durante processamento do pedido
window.mostrarLoadingPedido = function (mensagem = 'Finalizando seu pedido...') {
    // Remover overlay anterior se existir
    const existente = document.getElementById('loading-pedido-overlay');
    if (existente) existente.remove();

    const overlay = document.createElement('div');
    overlay.id = 'loading-pedido-overlay';
    overlay.innerHTML = `
        <div class="loading-pedido-content">
            <div class="loading-pedido-spinner">
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
                <div class="spinner-ring"></div>
                <i class="fa-solid fa-utensils spinner-icon"></i>
            </div>
            <h3 class="loading-pedido-titulo">${mensagem}</h3>
            <p class="loading-pedido-subtitulo">Por favor, aguarde alguns instantes...</p>
            <div class="loading-pedido-dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    `;

    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 19, 26, 0.95);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        animation: fadeIn 0.3s ease;
    `;

    // Adicionar estilos CSS
    if (!document.getElementById('loading-pedido-styles')) {
        const styles = document.createElement('style');
        styles.id = 'loading-pedido-styles';
        styles.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.1); opacity: 0.8; }
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            @keyframes bounce {
                0%, 80%, 100% { transform: scale(0); }
                40% { transform: scale(1); }
            }
            
            .loading-pedido-content {
                text-align: center;
                color: white;
            }
            
            .loading-pedido-spinner {
                position: relative;
                width: 120px;
                height: 120px;
                margin: 0 auto 30px;
            }
            
            .spinner-ring {
                position: absolute;
                width: 100%;
                height: 100%;
                border-radius: 50%;
                border: 3px solid transparent;
            }
            
            .spinner-ring:nth-child(1) {
                border-top-color: #4a66f9;
                animation: spin 1.5s linear infinite;
            }
            
            .spinner-ring:nth-child(2) {
                border-right-color: #f9564a;
                animation: spin 2s linear infinite reverse;
                width: 85%;
                height: 85%;
                top: 7.5%;
                left: 7.5%;
            }
            
            .spinner-ring:nth-child(3) {
                border-bottom-color: #10b981;
                animation: spin 2.5s linear infinite;
                width: 70%;
                height: 70%;
                top: 15%;
                left: 15%;
            }
            
            .spinner-icon {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                color: #4a66f9;
                animation: pulse 1.5s ease-in-out infinite;
            }
            
            .loading-pedido-titulo {
                font-size: 1.5rem;
                font-weight: 600;
                margin: 0 0 10px 0;
                background: linear-gradient(135deg, #4a66f9, #f9564a);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .loading-pedido-subtitulo {
                font-size: 1rem;
                color: #94a3b8;
                margin: 0 0 20px 0;
            }
            
            .loading-pedido-dots {
                display: flex;
                justify-content: center;
                gap: 8px;
            }
            
            .loading-pedido-dots span {
                width: 10px;
                height: 10px;
                background: #4a66f9;
                border-radius: 50%;
                animation: bounce 1.4s ease-in-out infinite both;
            }
            
            .loading-pedido-dots span:nth-child(1) { animation-delay: 0s; }
            .loading-pedido-dots span:nth-child(2) { animation-delay: 0.16s; }
            .loading-pedido-dots span:nth-child(3) { animation-delay: 0.32s; }
        `;
        document.head.appendChild(styles);
    }

    document.body.appendChild(overlay);
};

// Fechar overlay de loading
window.fecharLoadingPedido = function () {
    const overlay = document.getElementById('loading-pedido-overlay');
    if (overlay) {
        overlay.style.animation = 'fadeIn 0.3s ease reverse';
        setTimeout(() => overlay.remove(), 300);
    }
};

// Atualizar mensagem do loading
window.atualizarLoadingPedido = function (mensagem, subtitulo = null) {
    const titulo = document.querySelector('.loading-pedido-titulo');
    const sub = document.querySelector('.loading-pedido-subtitulo');
    if (titulo) titulo.textContent = mensagem;
    if (sub && subtitulo) sub.textContent = subtitulo;
};

// ===== MODAL LOJA FECHADA =====

// Mostrar modal de loja fechada
window.mostrarModalLojaFechada = function (mensagem, horarioHoje = null) {
    // Remover modal anterior se existir
    const existente = document.getElementById('modal-loja-fechada');
    if (existente) existente.remove();

    let horarioInfo = '';
    if (horarioHoje) {
        horarioInfo = `
            <div class="loja-fechada-horario">
                <i class="fa-solid fa-clock"></i>
                <span>Horário de hoje: ${horarioHoje.abertura} às ${horarioHoje.fechamento}</span>
            </div>
        `;
    } else {
        horarioInfo = `
            <div class="loja-fechada-horario fechado-hoje">
                <i class="fa-solid fa-calendar-xmark"></i>
                <span>Hoje não abrimos</span>
            </div>
        `;
    }

    const modal = document.createElement('div');
    modal.id = 'modal-loja-fechada';
    modal.innerHTML = `
        <div class="loja-fechada-overlay" onclick="fecharModalLojaFechada()">
            <div class="loja-fechada-content" onclick="event.stopPropagation()">
                <div class="loja-fechada-icon">
                    <i class="fa-solid fa-store-slash"></i>
                </div>
                <h3 class="loja-fechada-titulo">Estamos Fechados</h3>
                <p class="loja-fechada-mensagem">${mensagem}</p>
                ${horarioInfo}
                <button class="loja-fechada-btn" onclick="fecharModalLojaFechada()">
                    <i class="fa-solid fa-check"></i>
                    Entendi
                </button>
            </div>
        </div>
    `;

    // Adicionar estilos CSS
    if (!document.getElementById('loja-fechada-styles')) {
        const styles = document.createElement('style');
        styles.id = 'loja-fechada-styles';
        styles.textContent = `
            .loja-fechada-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(15, 19, 26, 0.95);
                backdrop-filter: blur(10px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                animation: fadeIn 0.3s ease;
                padding: 20px;
            }
            
            .loja-fechada-content {
                background: linear-gradient(135deg, #1e2433 0%, #151922 100%);
                border-radius: 24px;
                padding: 40px;
                text-align: center;
                max-width: 400px;
                width: 100%;
                border: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            }
            
            .loja-fechada-icon {
                width: 100px;
                height: 100px;
                background: linear-gradient(135deg, #f9564a 0%, #c94136 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
                animation: pulse 2s infinite;
            }
            
            .loja-fechada-icon i {
                font-size: 2.5rem;
                color: white;
            }
            
            .loja-fechada-titulo {
                font-size: 1.8rem;
                font-weight: 700;
                color: white;
                margin: 0 0 12px 0;
            }
            
            .loja-fechada-mensagem {
                font-size: 1.1rem;
                color: #94a3b8;
                margin: 0 0 24px 0;
                line-height: 1.5;
            }
            
            .loja-fechada-horario {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                padding: 16px 20px;
                background: rgba(74, 102, 249, 0.1);
                border-radius: 12px;
                margin-bottom: 24px;
                border: 1px solid rgba(74, 102, 249, 0.2);
            }
            
            .loja-fechada-horario i {
                color: #4a66f9;
                font-size: 1.2rem;
            }
            
            .loja-fechada-horario span {
                color: #e2e8f0;
                font-weight: 500;
            }
            
            .loja-fechada-horario.fechado-hoje {
                background: rgba(249, 86, 74, 0.1);
                border-color: rgba(249, 86, 74, 0.2);
            }
            
            .loja-fechada-horario.fechado-hoje i {
                color: #f9564a;
            }
            
            .loja-fechada-btn {
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #4a66f9 0%, #3b53d9 100%);
                color: white;
                border: none;
                border-radius: 12px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .loja-fechada-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(74, 102, 249, 0.3);
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        `;
        document.head.appendChild(styles);
    }

    document.body.appendChild(modal);
};

// Fechar modal de loja fechada
window.fecharModalLojaFechada = function () {
    const modal = document.getElementById('modal-loja-fechada');
    if (modal) {
        modal.querySelector('.loja-fechada-overlay').style.animation = 'fadeIn 0.3s ease reverse';
        setTimeout(() => modal.remove(), 300);
    }
};

// ===== MODAL DE VERIFICAÇÃO DE TELEFONE =====

window.mostrarModalVerificacao = async function (telefone, nome) {
    // Remover modal anterior se existir
    const existente = document.getElementById('modal-verificacao');
    if (existente) existente.remove();

    const modal = document.createElement('div');
    modal.id = 'modal-verificacao';
    modal.innerHTML = `
        <div class="verificacao-overlay">
            <div class="verificacao-content">
                <div class="verificacao-icon">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
                <h3 class="verificacao-titulo">Verificação Necessária</h3>
                <p class="verificacao-subtitulo">Para seu primeiro pedido, precisamos verificar seu telefone.</p>
                
                <div id="verificacao-step-1" class="verificacao-step">
                    <p class="verificacao-info">
                        <i class="fa-brands fa-whatsapp"></i>
                        Enviaremos um código de 6 dígitos para:<br>
                        <strong>${telefone}</strong>
                    </p>
                    <button class="verificacao-btn-enviar" onclick="enviarCodigoVerificacao('${telefone}', '${nome}')">
                        <i class="fa-solid fa-paper-plane"></i>
                        Enviar Código
                    </button>
                </div>
                
                <div id="verificacao-step-2" class="verificacao-step" style="display: none;">
                    <p class="verificacao-info">
                        <i class="fa-solid fa-mobile-screen"></i>
                        Digite o código de 6 dígitos enviado para seu WhatsApp:
                    </p>
                    <div class="verificacao-codigo-inputs">
                        <input type="text" maxlength="1" class="codigo-digit" data-index="0" inputmode="numeric" pattern="[0-9]*">
                        <input type="text" maxlength="1" class="codigo-digit" data-index="1" inputmode="numeric" pattern="[0-9]*">
                        <input type="text" maxlength="1" class="codigo-digit" data-index="2" inputmode="numeric" pattern="[0-9]*">
                        <input type="text" maxlength="1" class="codigo-digit" data-index="3" inputmode="numeric" pattern="[0-9]*">
                        <input type="text" maxlength="1" class="codigo-digit" data-index="4" inputmode="numeric" pattern="[0-9]*">
                        <input type="text" maxlength="1" class="codigo-digit" data-index="5" inputmode="numeric" pattern="[0-9]*">
                    </div>
                    <p id="verificacao-erro" class="verificacao-erro" style="display: none;"></p>
                    <button class="verificacao-btn-validar" onclick="validarCodigoVerificacao('${telefone}')">
                        <i class="fa-solid fa-check-circle"></i>
                        Verificar Código
                    </button>
                    <button class="verificacao-btn-reenviar" onclick="enviarCodigoVerificacao('${telefone}', '${nome}')">
                        <i class="fa-solid fa-rotate"></i>
                        Reenviar Código
                    </button>
                </div>
                
                <button class="verificacao-btn-cancelar" onclick="fecharModalVerificacao()">
                    Cancelar
                </button>
            </div>
        </div>
    `;

    // Adicionar estilos CSS
    if (!document.getElementById('verificacao-styles')) {
        const styles = document.createElement('style');
        styles.id = 'verificacao-styles';
        styles.textContent = `
            .verificacao-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(15, 19, 26, 0.95);
                backdrop-filter: blur(10px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                animation: fadeIn 0.3s ease;
                padding: 20px;
            }
            
            .verificacao-content {
                background: linear-gradient(135deg, #1e2433 0%, #151922 100%);
                border-radius: 24px;
                padding: 40px 30px;
                text-align: center;
                max-width: 400px;
                width: 100%;
                border: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            }
            
            .verificacao-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #4a66f9 0%, #3b53d9 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
            }
            
            .verificacao-icon i {
                font-size: 2rem;
                color: white;
            }
            
            .verificacao-titulo {
                font-size: 1.5rem;
                font-weight: 700;
                color: white;
                margin: 0 0 8px 0;
            }
            
            .verificacao-subtitulo {
                font-size: 0.95rem;
                color: #94a3b8;
                margin: 0 0 24px 0;
            }
            
            .verificacao-info {
                color: #e2e8f0;
                font-size: 0.95rem;
                margin-bottom: 20px;
                line-height: 1.5;
            }
            
            .verificacao-info i {
                font-size: 1.2rem;
                margin-right: 8px;
                color: #25D366;
            }
            
            .verificacao-codigo-inputs {
                display: flex;
                gap: 8px;
                justify-content: center;
                margin-bottom: 20px;
            }
            
            .codigo-digit {
                width: 45px;
                height: 55px;
                border: 2px solid rgba(255, 255, 255, 0.2);
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.05);
                color: white;
                font-size: 1.5rem;
                font-weight: 700;
                text-align: center;
                transition: border-color 0.2s, background 0.2s;
            }
            
            .codigo-digit:focus {
                border-color: #4a66f9;
                background: rgba(74, 102, 249, 0.1);
                outline: none;
            }
            
            .verificacao-erro {
                color: #f9564a;
                font-size: 0.9rem;
                margin-bottom: 15px;
            }
            
            .verificacao-btn-enviar,
            .verificacao-btn-validar {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
                color: white;
                border: none;
                border-radius: 12px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                margin-bottom: 12px;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .verificacao-btn-validar {
                background: linear-gradient(135deg, #4a66f9 0%, #3b53d9 100%);
            }
            
            .verificacao-btn-enviar:hover,
            .verificacao-btn-validar:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            }
            
            .verificacao-btn-reenviar {
                width: 100%;
                padding: 12px;
                background: transparent;
                color: #94a3b8;
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                font-weight: 500;
                font-size: 0.9rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                margin-bottom: 12px;
            }
            
            .verificacao-btn-reenviar:hover {
                border-color: rgba(255, 255, 255, 0.3);
                color: white;
            }
            
            .verificacao-btn-cancelar {
                width: 100%;
                padding: 12px;
                background: transparent;
                color: #64748b;
                border: none;
                font-size: 0.9rem;
                cursor: pointer;
            }
            
            .verificacao-btn-cancelar:hover {
                color: #94a3b8;
            }
            
            .verificacao-btn-enviar:disabled,
            .verificacao-btn-validar:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
        `;
        document.head.appendChild(styles);
    }

    document.body.appendChild(modal);

    // Setup listeners para inputs de código
    setTimeout(() => {
        const inputs = modal.querySelectorAll('.codigo-digit');
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = value;
                if (value && index < 5) {
                    inputs[index + 1].focus();
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            // Paste support
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                for (let i = 0; i < pasteData.length && i < 6; i++) {
                    inputs[i].value = pasteData[i];
                }
                if (pasteData.length > 0) {
                    inputs[Math.min(pasteData.length, 5)].focus();
                }
            });
        });
    }, 100);
};

window.enviarCodigoVerificacao = async function (telefone, nome) {
    const btnEnviar = document.querySelector('.verificacao-btn-enviar');
    const btnReenviar = document.querySelector('.verificacao-btn-reenviar');
    const btn = btnEnviar?.style.display !== 'none' ? btnEnviar : btnReenviar;

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';
    }

    // Coletar dados do endereço do formulário de checkout para salvar junto com cliente
    let endereco_detalhado = null;
    const tipoEntrega = document.querySelector('.delivery-option.active')?.dataset?.value;
    if (tipoEntrega === 'delivery') {
        const cep = document.getElementById('checkout-cep')?.value.trim() || '';
        const rua = document.getElementById('checkout-rua')?.value.trim() || '';
        const numero = document.getElementById('checkout-numero')?.value.trim() || '';
        const complemento = document.getElementById('checkout-complemento')?.value.trim() || '';
        const bairro = document.getElementById('checkout-bairro-input')?.value.trim() || '';
        const cidade = document.getElementById('checkout-cidade-input')?.value.trim() || '';
        const uf = document.getElementById('checkout-uf')?.value.trim().toUpperCase() || '';
        const apelido = document.getElementById('endereco-apelido')?.value.trim() || 'Casa';

        if (rua) {
            endereco_detalhado = {
                apelido,
                cep: cep.replace(/\D/g, ''),
                rua,
                numero,
                complemento,
                bairro,
                cidade,
                uf
            };
        }
    }

    try {
        const response = await fetch('api/enviar_codigo_verificacao.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ telefone, nome, endereco_detalhado })
        });

        const result = await response.json();

        if (result.sucesso) {
            // Mostrar step 2 (input de código)
            document.getElementById('verificacao-step-1').style.display = 'none';
            document.getElementById('verificacao-step-2').style.display = 'block';
            document.querySelector('.codigo-digit')?.focus();

            if (result.ja_verificado) {
                // Já verificado, fechar modal e refazer pedido
                fecharModalVerificacao();
                alert('Seu telefone já está verificado! Por favor, finalize o pedido novamente.');
            }
        } else {
            alert('Erro: ' + (result.erro || 'Falha ao enviar código'));
        }
    } catch (error) {
        console.error('Erro ao enviar código:', error);
        alert('Erro ao enviar código. Tente novamente.');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = btn === btnEnviar
                ? '<i class="fa-solid fa-paper-plane"></i> Enviar Código'
                : '<i class="fa-solid fa-rotate"></i> Reenviar Código';
        }
    }
};

window.validarCodigoVerificacao = async function (telefone) {
    const inputs = document.querySelectorAll('.codigo-digit');
    const codigo = Array.from(inputs).map(i => i.value).join('');

    if (codigo.length !== 6) {
        document.getElementById('verificacao-erro').textContent = 'Digite o código completo de 6 dígitos';
        document.getElementById('verificacao-erro').style.display = 'block';
        return;
    }

    const btn = document.querySelector('.verificacao-btn-validar');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
    }

    try {
        const response = await fetch('api/validar_codigo_verificacao.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ telefone, codigo })
        });

        const result = await response.json();

        if (result.sucesso) {
            fecharModalVerificacao();
            alert('✅ Telefone verificado com sucesso! Agora finalize seu pedido.');
        } else {
            document.getElementById('verificacao-erro').textContent = result.erro || 'Código inválido';
            document.getElementById('verificacao-erro').style.display = 'block';
            inputs.forEach(i => i.value = '');
            inputs[0].focus();
        }
    } catch (error) {
        console.error('Erro ao validar código:', error);
        document.getElementById('verificacao-erro').textContent = 'Erro ao validar. Tente novamente.';
        document.getElementById('verificacao-erro').style.display = 'block';
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Verificar Código';
        }
    }
};

window.fecharModalVerificacao = function () {
    const modal = document.getElementById('modal-verificacao');
    if (modal) {
        modal.querySelector('.verificacao-overlay').style.animation = 'fadeIn 0.3s ease reverse';
        setTimeout(() => modal.remove(), 300);
    }
};

// ===== FUNÇÕES DE LEMBRAR DADOS DO CLIENTE (localStorage) =====

// Salvar dados do cliente no localStorage após pedido finalizado
window.salvarDadosClienteLocal = function (dados) {
    try {
        const dadosCliente = {
            nome: dados.nome || '',
            telefone: dados.telefone || '',
            email: dados.email || '',
            ultimoPedido: new Date().toISOString()
        };
        localStorage.setItem('cardapix_cliente', JSON.stringify(dadosCliente));
        console.log('💾 Dados do cliente salvos localmente:', dadosCliente.nome);
    } catch (e) {
        console.error('Erro ao salvar dados localmente:', e);
    }
};

// Restaurar dados do cliente do localStorage no checkout
window.restaurarDadosClienteLocal = function () {
    try {
        const dadosSalvos = localStorage.getItem('cardapix_cliente');
        if (!dadosSalvos) return;

        const dados = JSON.parse(dadosSalvos);
        console.log('📥 Restaurando dados do cliente:', dados.nome);

        // Preencher campos do formulário
        const telefoneInput = document.getElementById('checkout-telefone');
        const nomeInput = document.getElementById('checkout-nome');
        const emailInput = document.getElementById('checkout-email');

        if (telefoneInput && dados.telefone) {
            telefoneInput.value = dados.telefone;
            // Disparar busca automática do cliente
            setTimeout(() => {
                const tel = dados.telefone.replace(/\D/g, '');
                if (tel.length >= 10) {
                    buscarClientePorTelefone(tel);
                }
            }, 500);
        }
        if (nomeInput && dados.nome) nomeInput.value = dados.nome;
        if (emailInput && dados.email) emailInput.value = dados.email;

        // Mostrar feedback
        const feedback = document.getElementById('cliente-feedback');
        if (feedback) {
            feedback.innerHTML = '<i class="fa-solid fa-clock-rotate-left"></i> Dados restaurados do último pedido';
            feedback.style.display = 'block';
            feedback.className = 'cliente-feedback success';
            setTimeout(() => {
                feedback.style.display = 'none';
            }, 3000);
        }
    } catch (e) {
        console.error('Erro ao restaurar dados localmente:', e);
    }
};

// Limpar dados salvos localmente
window.limparDadosClienteLocal = function () {
    try {
        localStorage.removeItem('cardapix_cliente');
        console.log('🗑️ Dados do cliente removidos do localStorage');
    } catch (e) {
        console.error('Erro ao limpar dados:', e);
    }
};
