<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title ?? 'Visi') ?> — Visi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#fff7ed',100:'#ffedd5',200:'#fed7aa',300:'#fdba74',400:'#fb923c',500:'#f97316',600:'#ea580c',700:'#c2410c',800:'#9a3412',900:'#7c2d12' }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:.5rem;font-weight:500;transition:all .15s;gap:.5rem}
        .btn:disabled{opacity:.5;pointer-events:none}
        .btn-primary{background:#f97316;color:#fff}.btn-primary:hover{background:#ea580c}
        .btn-secondary{background:#f3f4f6;color:#111827}.btn-secondary:hover{background:#e5e7eb}
        .btn-outline{border:1px solid #d1d5db;background:#fff;color:#374151}.btn-outline:hover{background:#f9fafb}
        .btn-ghost{color:#4b5563}.btn-ghost:hover{background:#f3f4f6}
        .btn-danger{background:#ef4444;color:#fff}.btn-danger:hover{background:#dc2626}
        .btn-sm{height:2rem;padding:0 .75rem;font-size:.75rem}
        .btn-md{height:2.5rem;padding:0 1rem;font-size:.875rem}
        .btn-lg{height:3rem;padding:0 1.5rem;font-size:1rem}
        .input{width:100%;height:2.5rem;border:1px solid #d1d5db;border-radius:.5rem;padding:0 .75rem;font-size:.875rem;outline:none}
        .input:focus{border-color:#f97316;box-shadow:0 0 0 2px rgba(249,115,22,.2)}
        .card{border:1px solid #e5e7eb;border-radius:.75rem;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.05)}
        .badge{display:inline-flex;align-items:center;border-radius:9999px;padding:.125rem .625rem;font-size:.75rem;font-weight:500}
        .badge-success{background:#d1fae5;color:#065f46}
        .badge-warning{background:#fef3c7;color:#92400e}
        .badge-danger{background:#fee2e2;color:#991b1b}
        .badge-info{background:#dbeafe;color:#1e40af}
        .badge-neutral{background:#f3f4f6;color:#374151}
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="sticky top-0 z-40 border-b bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4">
            <a href="/" class="flex items-center gap-2">
                <svg class="h-6 w-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                <span class="text-lg font-bold">Visi</span>
            </a>
            <div class="flex items-center gap-3">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a href="/admin/dashboard" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</a>
                    <a href="/logout" class="text-sm text-gray-600 hover:text-gray-900">Keluar</a>
                <?php else: ?>
                    <a href="/login" class="text-sm text-gray-600 hover:text-gray-900">Masuk</a>
                    <a href="/onboarding" class="btn btn-primary btn-sm">Mulai Gratis</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="flex-1">
        <?= $content ?? '' ?>
    </main>
    <footer class="border-t bg-white py-6 text-center text-xs text-gray-500">
        &copy; <?= date('Y') ?> Visi — Platform Tiket
    </footer>
</body>
</html>
