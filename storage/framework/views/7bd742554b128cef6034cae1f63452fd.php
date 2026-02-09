<?php $__env->startSection('title', 'Dashboard - De Casa em Casa'); ?>

<?php $__env->startSection('content'); ?>
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard - De Casa em Casa</h1>

        
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-5">
                <p class="text-sm text-gray-500">Encontros Ativos</p>
                <p class="text-3xl font-bold text-indigo-600"><?php echo e($stats['active_events']); ?></p>
                <p class="text-xs text-gray-400 mt-1">de <?php echo e($stats['total_events']); ?> total</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl shadow p-5">
                <p class="text-sm text-yellow-600">Pendentes</p>
                <p class="text-3xl font-bold text-yellow-700"><?php echo e($stats['pending_inscriptions']); ?></p>
                <p class="text-xs text-yellow-500 mt-1">aguardando curadoria</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-xl shadow p-5">
                <p class="text-sm text-blue-600">Aprovados</p>
                <p class="text-3xl font-bold text-blue-700"><?php echo e($stats['approved_inscriptions']); ?></p>
                <p class="text-xs text-blue-500 mt-1">aguardando pagamento</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-xl shadow p-5">
                <p class="text-sm text-green-600">Confirmados</p>
                <p class="text-3xl font-bold text-green-700"><?php echo e($stats['confirmed_inscriptions']); ?></p>
                <p class="text-xs text-green-500 mt-1">participações garantidas</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            
            <div class="bg-white rounded-xl shadow">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Inscrições Recentes</h2>
                    <a href="<?php echo e(route('admin.inscricoes.index')); ?>" class="text-sm text-indigo-600 hover:text-indigo-800">Ver todas</a>
                </div>
                <div class="divide-y">
                    <?php $__empty_1 = true; $__currentLoopData = $recent_inscriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inscription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="px-6 py-3 flex items-center justify-between">
                            <div>
                                <a href="<?php echo e(route('admin.inscricoes.show', $inscription)); ?>" class="font-medium text-gray-900 hover:text-indigo-600">
                                    <?php echo e($inscription->full_name); ?>

                                </a>
                                <p class="text-xs text-gray-500">
                                    <?php echo e($inscription->event->city ?? $inscription->event->title); ?> - <?php echo e($inscription->created_at->diffForHumans()); ?>

                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                <?php if($inscription->isPending()): ?> bg-yellow-100 text-yellow-800
                                <?php elseif($inscription->isApproved()): ?> bg-blue-100 text-blue-800
                                <?php elseif($inscription->isConfirmed()): ?> bg-green-100 text-green-800
                                <?php elseif($inscription->isWaitlisted()): ?> bg-orange-100 text-orange-800
                                <?php endif; ?>">
                                <?php echo e($inscription->status_label); ?>

                            </span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="px-6 py-8 text-center text-gray-500">Nenhuma inscrição ainda.</div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="bg-white rounded-xl shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Inscrições por Cidade</h2>
                </div>
                <div class="divide-y">
                    <?php $__empty_1 = true; $__currentLoopData = $inscriptions_by_city; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="px-6 py-3 flex items-center justify-between">
                            <span class="font-medium text-gray-900"><?php echo e($item->city); ?></span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                                <?php echo e($item->count); ?>

                            </span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="px-6 py-8 text-center text-gray-500">Nenhum dado disponível.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>