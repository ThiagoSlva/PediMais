<?php
/**
 * Validação de Senha
 * Funções para validar requisitos mínimos de senha
 */

function validar_senha(string $senha): array
{
    $erros = [];

    if (strlen($senha) < 8) {
        $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
    }

    if (!preg_match('/[A-Z]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos uma letra maiúscula.';
    }

    if (!preg_match('/[a-z]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos uma letra minúscula.';
    }

    if (!preg_match('/[0-9]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos um número.';
    }

    return $erros;
}

function senha_atende_requisitos(string $senha): bool
{
    return empty(validar_senha($senha));
}
