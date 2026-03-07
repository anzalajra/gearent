<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full items-center justify-center bg-gray-100 dark:bg-gray-900">
    <div class="mx-4 w-full max-w-md text-center">
        <div class="rounded-xl bg-white p-8 shadow-lg dark:bg-gray-800">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <svg class="h-8 w-8 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
            </div>

            <h1 class="mb-2 text-xl font-bold text-gray-900 dark:text-white">
                Subscription Expired
            </h1>

            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                Subscription Anda telah berakhir dan akun telah disuspend.
                Silakan perpanjang subscription untuk melanjutkan menggunakan layanan.
            </p>

            <a href="/admin/subscription-billing"
               class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-3 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                </svg>
                Perpanjang Subscription
            </a>

            <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                Butuh bantuan? Hubungi admin Zewalo.
            </p>
        </div>
    </div>
</body>
</html>
