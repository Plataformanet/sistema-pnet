<x-mail::message>
Olá {{ $name }},

Você foi cadastrado(a) como proponente da proposta **Nº {{ $proposalNumber }}**.

Para acompanhar o andamento, defina sua senha de acesso pelo botão abaixo.

<x-mail::button :url="$setPasswordUrl">
Definir minha senha
</x-mail::button>

O link vale por {{ intdiv((int) config('auth.passwords.welcome.expire'), 60) }} horas. Depois disso, use "Esqueci minha senha" na tela de login.

Se você não reconhece este cadastro, ignore este e-mail.

Este é um e-mail automático, não é necessário respondê-lo.

{{ config('app.name') }}
</x-mail::message>
