<x-mail::message>
Olá {{ $name }},

Recebemos um pedido para redefinir a senha da sua conta. Para criar uma nova senha, use o botão abaixo.

<x-mail::button :url="$resetUrl">
Redefinir minha senha
</x-mail::button>

O link vale por {{ $expiresInMinutes }} minutos. Se você não pediu a redefinição, ignore este e-mail: sua senha continua a mesma.

Este é um e-mail automático, não é necessário respondê-lo.

{{ config('app.name') }}
</x-mail::message>
