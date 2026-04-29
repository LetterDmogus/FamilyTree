<x-mail::message>
# Kode Verifikasi Login

Gunakan kode di bawah ini untuk masuk ke akun silsilah keluarga Anda. Kode ini akan kedaluwarsa dalam 10 menit.

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

Jika Anda tidak merasa meminta kode ini, abaikan saja email ini.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
