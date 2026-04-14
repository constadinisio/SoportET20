<?php
declare(strict_types=1);

// If already logged in, skip login screen.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!empty($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

$pageTitle = 'SoportET20 ET20 - Login';
require __DIR__ . '/partials/head.php';
?>
<body class="bg-[#0b111a] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-[#0f172a] border border-slate-800 rounded-2xl p-8 shadow-2xl">
        <div class="flex flex-col items-center mb-8">
            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center text-white mb-4 shadow-lg shadow-blue-600/20">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </div>
            <h1 class="text-white text-2xl font-bold tracking-tight">SoportET20 ET20</h1>
            <p class="text-slate-400 text-sm mt-1">Gestión de Tickets y Monitoreo</p>
        </div>

        <form id="loginForm" class="space-y-6">
            <div>
                <label class="block text-slate-300 text-sm font-medium mb-2">Usuario</label>
                <input type="text" id="usuario" name="usuario" required
                    class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                    placeholder="Ingresa tu usuario">
            </div>
            <div>
                <label class="block text-slate-300 text-sm font-medium mb-2">Clave</label>
                <input type="password" id="clave" name="clave" required
                    class="w-full bg-[#1e293b] border border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                    placeholder="••••••••">
            </div>

            <div id="errorMessage" class="text-rose-500 text-sm text-center hidden"></div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-blue-600/20 active:scale-[0.98]">
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-800 text-center">
            <p class="text-slate-500 text-xs">
                © 2024 Escuela Técnica ET20. Todos los derechos reservados.
            </p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const usuario = document.getElementById('usuario').value;
            const clave = document.getElementById('clave').value;
            const errorMsg = document.getElementById('errorMessage');

            try {
                const response = await fetch('../backend/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ usuario, clave })
                });

                const result = await response.json();

                if (result.success) {
                    sessionStorage.setItem('user', JSON.stringify(result.user));
                    window.location.href = 'home.php';
                } else {
                    errorMsg.textContent = result.message || 'Usuario o clave incorrectos';
                    errorMsg.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error:', error);
                errorMsg.textContent = 'Error de conexión con el servidor. Verificá que el backend esté corriendo.';
                errorMsg.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
