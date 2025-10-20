<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dashboard - <?= htmlspecialchars($app_name ?? 'WebCodeFTP', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">

    <!-- Header/Navigation -->
    <header class="bg-slate-800 border-b border-slate-700 shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-white">
                        <?= htmlspecialchars($app_name ?? 'WebCodeFTP', ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="hidden md:flex items-center space-x-2 text-sm">
                        <span class="px-3 py-1 bg-slate-700 text-slate-300 rounded-full">
                            <span class="text-slate-400">Host:</span>
                            <span
                                class="font-medium text-white"><?= htmlspecialchars($ftp_host ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <span class="px-3 py-1 bg-slate-700 text-slate-300 rounded-full">
                            <span class="text-slate-400">User:</span>
                            <span
                                class="font-medium text-white"><?= htmlspecialchars($ftp_username ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </div>
                </div>
                <div>
                    <a href="/logout"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">

        <!-- Welcome Card -->
        <div class="bg-slate-800 rounded-lg shadow-2xl p-12 border border-slate-700 text-center">

            <!-- Success Icon -->
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-500/20 rounded-full">
                    <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>

            <!-- Hello World Message -->
            <h2 class="text-5xl font-bold text-white mb-4">Hello World!</h2>

            <p class="text-xl text-slate-300 mb-8">
                You are successfully authenticated to the FTP server.
            </p>

            <!-- Connection Info -->
            <div class="bg-slate-900/50 rounded-lg p-6 max-w-2xl mx-auto">
                <h3 class="text-lg font-semibold text-white mb-4">Connection Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                    <div class="bg-slate-800 rounded-lg p-4">
                        <p class="text-sm text-slate-400 mb-1">FTP Host</p>
                        <p class="text-white font-mono"><?= htmlspecialchars($ftp_host ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div class="bg-slate-800 rounded-lg p-4">
                        <p class="text-sm text-slate-400 mb-1">Username</p>
                        <p class="text-white font-mono">
                            <?= htmlspecialchars($ftp_username ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="mt-8 max-w-2xl mx-auto">
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <div class="text-left">
                            <p class="text-blue-300 text-sm font-medium mb-1">Initial Implementation Complete</p>
                            <p class="text-blue-200 text-sm">
                                This is the first phase of the WebCodeFTP client. The login system is fully functional
                                with enterprise-grade security.
                                File management features will be implemented in the next phase.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Security Features Info -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white">CSRF Protected</h3>
                </div>
                <p class="text-slate-400 text-sm">All forms use cryptographically secure tokens to prevent cross-site
                    request forgery attacks.</p>
            </div>

            <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Session Security</h3>
                </div>
                <p class="text-slate-400 text-sm">Advanced session management with fingerprinting, regeneration, and
                    hijacking prevention.</p>
            </div>

            <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Rate Limiting</h3>
                </div>
                <p class="text-slate-400 text-sm">Brute force protection with intelligent rate limiting and temporary
                    account lockouts.</p>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="container mx-auto px-6 py-6 mt-8">
        <div class="text-center text-slate-500 text-sm">
            <p>Secure, modern, and blazing fast FTP client built with PHP 8.0+</p>
        </div>
    </footer>

</body>

</html>