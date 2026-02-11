<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'De Casa em Casa'); ?> - Turnê</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('favicon.svg')); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('favicon.png')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(asset('favicon.png')); ?>">

    <!-- SEO e compartilhamento -->
    <meta name="description" content="<?php echo $__env->yieldContent('meta_description', 'De Casa em Casa é uma turnê que acontece onde a vida acontece. Dentro de casas reais, com pessoas reais.'); ?>">

    <!-- Open Graph (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $__env->yieldContent('og_title', 'De Casa em Casa - Turnê'); ?>">
    <meta property="og:description" content="<?php echo $__env->yieldContent('og_description', 'Uma turnê que acontece onde a vida acontece. Dentro de casas reais, com pessoas reais, criando um encontro inédito e poderoso.'); ?>">
    <meta property="og:image" content="<?php echo $__env->yieldContent('og_image', asset('images/og-share-compressed.jpg')); ?>">
    <meta property="og:image:width" content="1024">
    <meta property="og:image:height" content="1024">
    <meta property="og:url" content="<?php echo e(url()->current()); ?>">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="De Casa em Casa">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo $__env->yieldContent('og_title', 'De Casa em Casa - Turnê'); ?>">
    <meta name="twitter:description" content="<?php echo $__env->yieldContent('og_description', 'Uma turnê que acontece onde a vida acontece. Dentro de casas reais, com pessoas reais.'); ?>">
    <meta name="twitter:image" content="<?php echo $__env->yieldContent('og_image', asset('images/og-share-compressed.jpg')); ?>">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        [x-cloak] { display: none !important; }
        
        /* CSS específico para SweetAlert - carregado após Tailwind */
        .swal2-actions {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin-top: 1.5em !important;
            margin-bottom: 0 !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 0.5em !important;
        }
        
        .swal2-actions button {
            display: inline-flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            margin: 0.3125em !important;
            padding: 0.625em 1.5em !important;
            font-weight: 500 !important;
            border-radius: 0.25em !important;
            cursor: pointer !important;
            min-width: 100px !important;
            height: auto !important;
            align-items: center !important;
            justify-content: center !important;
            border: none !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            color: white !important;
            font-size: 1em !important;
            line-height: 1.5 !important;
            /* Não sobrescrever background-color se já estiver definido inline */
        }
        
        /* Garantir que as cores definidas via confirmButtonColor sejam aplicadas */
        /* O SweetAlert aplica as cores via style inline, mas o Tailwind pode estar resetando */
        /* Vamos usar uma especificidade maior e garantir que as cores sejam aplicadas */
        .swal2-popup .swal2-confirm {
            background-color: var(--swal2-confirm-button-background-color, #3085d6) !important;
            color: white !important;
        }
        
        .swal2-popup .swal2-cancel {
            background-color: var(--swal2-cancel-button-background-color, #6c757d) !important;
            color: white !important;
        }
        
        /* Garantir que o texto seja sempre branco e visível */
        .swal2-confirm,
        .swal2-cancel {
            color: white !important;
        }
        
        .swal2-confirm .swal2-confirm-button-text,
        .swal2-cancel .swal2-cancel-button-text {
            color: white !important;
        }
        
        .swal2-confirm:hover,
        .swal2-cancel:hover {
            opacity: 0.9 !important;
            transform: scale(1.02) !important;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15) !important;
        }
        
        .swal2-confirm:focus,
        .swal2-cancel:focus {
            outline: 2px solid currentColor !important;
            outline-offset: 2px !important;
        }
        
        .swal2-confirm .swal2-confirm-button-text,
        .swal2-cancel .swal2-cancel-button-text {
            color: white !important;
        }
        
        /* Ocultar qualquer botão "No" ou botões extras que não sejam confirm ou cancel */
        .swal2-actions button:not(.swal2-confirm):not(.swal2-cancel) {
            display: none !important;
        }
        
        /* Garantir que apenas dois botões sejam exibidos */
        .swal2-actions {
            display: flex !important;
            gap: 0.5em !important;
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-md border-b border-gray-200" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?php echo e(auth()->check() && auth()->user()->isAdmin() ? route('admin.dashboard') : route('inscricao.create')); ?>" class="text-2xl font-bold text-indigo-600">
                            De Casa em Casa
                        </a>
                    </div>
                    <!-- Menu Desktop -->
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <?php if(auth()->guard()->check()): ?>
                            <?php if(auth()->user()->isAdmin()): ?>
                                <a href="<?php echo e(route('admin.events.index')); ?>" class="<?php echo e(request()->routeIs('admin.events.*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'); ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Encontros
                                </a>
                                <a href="<?php echo e(route('admin.inscricoes.index')); ?>" class="<?php echo e(request()->routeIs('admin.inscricoes.*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'); ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Inscrições
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Menu Desktop - Direita -->
                <div class="hidden sm:flex sm:items-center">
                    <?php if(auth()->guard()->check()): ?>
                        <span class="text-gray-700 mr-4 text-sm"><?php echo e(auth()->user()->name); ?></span>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="text-gray-500 hover:text-gray-700 text-sm">Sair</button>
                        </form>
                    <?php else: ?>
                        
                    <?php endif; ?>
                </div>
                <!-- Botão Hambúrguer Mobile (apenas para logados) -->
                <?php if(auth()->guard()->check()): ?>
                <div class="sm:hidden flex items-center">
                    <button @click="open = !open" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" aria-expanded="false">
                        <span class="sr-only">Abrir menu principal</span>
                        <!-- Ícone Hambúrguer -->
                        <svg x-show="!open" class="h-6 w-6" style="display: block;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <!-- Ícone X -->
                        <svg x-show="open" class="h-6 w-6" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Menu Mobile -->
        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="sm:hidden" style="display: none;">
            <div class="pt-2 pb-3 space-y-1 border-t border-gray-200">
                <?php if(auth()->guard()->check()): ?>
                    <?php if(auth()->user()->isAdmin()): ?>
                        <a href="<?php echo e(route('admin.events.index')); ?>" @click="open = false" class="<?php echo e(request()->routeIs('admin.events.*') ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'); ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                            Encontros
                        </a>
                        <a href="<?php echo e(route('admin.inscricoes.index')); ?>" @click="open = false" class="<?php echo e(request()->routeIs('admin.inscricoes.*') ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'); ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                            Inscrições
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    
                <?php endif; ?>
            </div>
            <div class="pt-4 pb-3 border-t border-gray-200">
                <?php if(auth()->guard()->check()): ?>
                    <div class="px-4 mb-3">
                        <div class="text-base font-medium text-gray-800"><?php echo e(auth()->user()->name); ?></div>
                        <div class="text-sm font-medium text-gray-500"><?php echo e(auth()->user()->email); ?></div>
                    </div>
                    <div class="space-y-1">
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" @click="open = false" class="block w-full text-left pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300">
                                Sair
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="px-4 sm:px-0">
                <?php if(session('success')): ?>
                    <div id="success-alert" class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md relative" role="alert" style="display: none;">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="block sm:inline"><?php echo e(session('success')); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div id="error-alert" class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md relative" role="alert" style="display: none;">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="block sm:inline"><?php echo e(session('error')); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(session('info')): ?>
                    <div id="info-alert" class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-md relative" role="alert" style="display: none;">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span class="block sm:inline"><?php echo e(session('info')); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                &copy; <?php echo e(date('Y')); ?> De Casa em Casa. Todos os direitos reservados.
            </p>
        </div>
    </footer>

    <script>
        // Garantir que o Alpine.js está funcionando
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Alpine === 'undefined') {
                console.error('Alpine.js não foi carregado!');
            } else {
                console.log('Alpine.js carregado com sucesso');
            }
            
            // Interceptar o SweetAlert para garantir que as cores sejam aplicadas e remover botões extras
            if (typeof Swal !== 'undefined') {
                const originalFire = Swal.fire;
                Swal.fire = function(options) {
                    return originalFire.call(this, options).then((result) => {
                        // Após o SweetAlert ser renderizado, garantir que as cores sejam aplicadas
                        setTimeout(() => {
                            const confirmBtn = document.querySelector('.swal2-confirm');
                            const cancelBtn = document.querySelector('.swal2-cancel');
                            
                            // Remover qualquer botão "No" ou botões extras
                            const allButtons = document.querySelectorAll('.swal2-actions button');
                            allButtons.forEach(btn => {
                                const text = btn.textContent.trim();
                                // Se o botão não for confirm nem cancel, e tiver texto "No" ou vazio, remover
                                if (!btn.classList.contains('swal2-confirm') && 
                                    !btn.classList.contains('swal2-cancel') &&
                                    (text === 'No' || text === '' || text === 'Não')) {
                                    btn.remove();
                                }
                            });
                            
                            if (confirmBtn && options.confirmButtonColor) {
                                confirmBtn.style.backgroundColor = options.confirmButtonColor;
                                confirmBtn.style.color = 'white';
                            }
                            
                            if (cancelBtn && options.cancelButtonColor) {
                                cancelBtn.style.backgroundColor = options.cancelButtonColor;
                                cancelBtn.style.color = 'white';
                            }
                        }, 10);
                        
                        return result;
                    });
                };
            }

            // Exibir mensagens de sessão com SweetAlert
            <?php if(session('success')): ?>
                const successAlert = document.getElementById('success-alert');
                if (successAlert) successAlert.style.display = 'none';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: <?php echo json_encode(session('success')); ?>,
                    confirmButtonColor: '#16a34a',
                    timer: 5000,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    buttonsStyling: true,
                    allowOutsideClick: true
                });
            <?php endif; ?>

            <?php if(session('error')): ?>
                const errorAlert = document.getElementById('error-alert');
                if (errorAlert) errorAlert.style.display = 'none';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: <?php echo json_encode(session('error')); ?>,
                    confirmButtonColor: '#dc2626',
                    showConfirmButton: true,
                    buttonsStyling: true,
                    allowOutsideClick: true
                });
            <?php endif; ?>

            <?php if(session('info')): ?>
                const infoAlert = document.getElementById('info-alert');
                if (infoAlert) infoAlert.style.display = 'none';
                
                Swal.fire({
                    icon: 'info',
                    title: 'Informação',
                    text: <?php echo json_encode(session('info')); ?>,
                    confirmButtonColor: '#3b82f6',
                    timer: 5000,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    buttonsStyling: true,
                    allowOutsideClick: true
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>

<?php /**PATH /var/www/resources/views/layouts/app.blade.php ENDPATH**/ ?>