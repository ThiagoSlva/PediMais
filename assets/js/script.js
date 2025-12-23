// Script.js para funcionalidades do frontend
document.addEventListener('DOMContentLoaded', function() {
    // Função para atualizar o contador do carrinho na navegação
    function updateCartCount() {
        // Esta função seria implementada de forma mais robusta com chamadas AJAX 
        // ou lendo um cookie/localStorage em um ambiente real.
        // No momento, apenas lê o valor do elemento no carrinho.php
        const cartCountElement = document.getElementById('carrinho-count');
        if (cartCountElement) {
            // Se estiver na página do carrinho, o PHP já preencheu o valor correto.
            // Se não estiver, manteremos o 0 ou podemos tentar ler do localStorage.
            let count = 0;
            if (window.location.pathname.includes('carrinho.php')) {
                // O PHP já deve ter preenchido o valor
            } else {
                // Simulação de leitura de carrinho (se usarmos localStorage)
                const carrinho = JSON.parse(localStorage.getItem('carrinho')) || [];
                count = carrinho.length;
            }
            // Apenas para garantir que o contador seja atualizado se o PHP não o fez
            // No nosso caso, o PHP está preenchendo o valor correto no carrinho.php
            // e mantendo 0 nas outras páginas por simplicidade.
        }
    }

    updateCartCount();

    // Adiciona evento de clique aos botões "Pedir" para redirecionar para a página do produto
    const pedirButtons = document.querySelectorAll('.btn-pedir');
    pedirButtons.forEach(button => {
        button.addEventListener('click', function() {
            const produtoId = this.getAttribute('data-id');
            window.location.href = 'produto.php?id=' + produtoId;
        });
    });

    // Adiciona funcionalidade de rolagem suave para as categorias (se implementarmos links âncora)
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});
