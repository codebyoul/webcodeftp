<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Login - <?= htmlspecialchars($app_name ?? 'WebCodeFTP', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.ico">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Apply saved theme and language immediately (prevents flash) -->
    <script>
        (function () {
            // Helper function to get cookie value
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }

            // Apply theme from localStorage (client-side only)
            const savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Apply language from cookie (works on both server and client)
            const cookieName = '<?= $language_cookie_name ?>';
            const savedLanguage = getCookie(cookieName) || '<?= $language ?>';
            document.documentElement.setAttribute('lang', savedLanguage);
        })();
    </script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configure Tailwind after it loads
        if (typeof tailwind !== 'undefined' && tailwind.config) {
            tailwind.config = {
                darkMode: 'class'
            };
        }
    </script>
</head>

<body
    class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 min-h-screen flex items-center justify-center p-4 transition-colors duration-200">

    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                <?= htmlspecialchars($app_name ?? 'WebCodeFTP', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-gray-600 dark:text-slate-400">
                <?= htmlspecialchars($translations['app_subtitle'] ?? 'Secure File Management Portal', ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>

        <!-- Login Card -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-2xl p-8 border border-gray-200 dark:border-slate-700">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                <?= htmlspecialchars($translations['login_title'] ?? 'Sign In', ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-gray-600 dark:text-slate-400 text-sm mb-6">
                <?= htmlspecialchars($translations['login_subtitle'] ?? 'Enter your FTP credentials to access your files', ENT_QUOTES, 'UTF-8') ?>
            </p>

            <?php if (isset($error) && $error): ?>
                <!-- Error Alert -->
                <div class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-circle-xmark text-red-400 mt-0.5 mr-3"></i>
                        <p class="text-red-300 text-sm"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($success) && $success): ?>
                <!-- Success Alert -->
                <div class="bg-green-500/10 border border-green-500/50 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-circle-check text-green-400 mt-0.5 mr-3"></i>
                        <p class="text-green-300 text-sm"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="/login" class="space-y-6">
                <!-- CSRF Token -->
                <input type="hidden" name="<?= htmlspecialchars($csrf_field_name, ENT_QUOTES, 'UTF-8') ?>"
                    value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                        <i class="fas fa-user mr-1"></i>
                        <?= htmlspecialchars($translations['username_label'] ?? 'FTP Username', ENT_QUOTES, 'UTF-8') ?>
                    </label>
                    <input type="text" id="username" name="username" required autocomplete="username"
                        placeholder="<?= htmlspecialchars($translations['username_placeholder'] ?? 'Enter your FTP username', ENT_QUOTES, 'UTF-8') ?>"
                        autofocus
                        class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">
                        <i class="fas fa-lock mr-1"></i>
                        <?= htmlspecialchars($translations['password_label'] ?? 'FTP Password', ENT_QUOTES, 'UTF-8') ?>
                    </label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        placeholder="<?= htmlspecialchars($translations['password_placeholder'] ?? 'Enter your FTP password', ENT_QUOTES, 'UTF-8') ?>"
                        class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-800">
                    <i class="fas fa-right-to-bracket mr-2"></i>
                    <?= htmlspecialchars($translations['signin_button'] ?? 'Sign In to FTP', ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>

            <!-- Security Notice -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                <div class="flex items-start text-xs text-gray-600 dark:text-slate-400">
                    <i class="fas fa-shield-halved mr-2 mt-0.5 flex-shrink-0"></i>
                    <p><?= htmlspecialchars($translations['security_notice'] ?? 'Your credentials are encrypted and secure.', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-gray-500 dark:text-slate-500 text-sm">
            <p>Powered by <?= htmlspecialchars($app_name ?? 'WebCodeFTP', ENT_QUOTES, 'UTF-8') ?> -
                <?= htmlspecialchars($translations['footer_text'] ?? 'Secure File Management', ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
    </div>

</body>

</html>