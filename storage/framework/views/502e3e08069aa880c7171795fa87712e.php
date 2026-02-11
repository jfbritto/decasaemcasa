<?php $__env->startSection('title', 'Gerenciar Encontros'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
    <div class="px-4 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mt-6">Encontros</h1>
            <a href="<?php echo e(route('admin.events.create')); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Novo Encontro
            </a>
        </div>

        <div class="bg-white shadow-lg border border-gray-200 overflow-x-auto sm:rounded-md">
            <table class="min-w-full divide-y divide-gray-300 border border-gray-200">
                <thead class="bg-gray-100 border-b border-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-16"></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Cidade</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Capacidade</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Inscrições</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-300">
                    <?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900"><?php echo e($event->city ?? $event->title); ?></div>
                                <?php if($event->title && $event->city && $event->title !== $event->city): ?>
                                    <div class="text-xs text-gray-500"><?php echo e($event->title); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo e($event->date->format('d/m/Y H:i')); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <span class="<?php echo e($event->isFull() ? 'text-red-600 font-bold' : ''); ?>">
                                    <?php echo e($event->confirmed_count); ?> / <?php echo e($event->capacity ?: '-'); ?>

                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo e($event->inscriptions_count ?? 0); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded
                                    <?php if($event->status === 'published'): ?> bg-green-100 text-green-800
                                    <?php elseif($event->status === 'draft'): ?> bg-gray-100 text-gray-800
                                    <?php else: ?> bg-red-100 text-red-800
                                    <?php endif; ?>">
                                    <?php if($event->status === 'published'): ?> Publicado
                                    <?php elseif($event->status === 'draft'): ?> Rascunho
                                    <?php elseif($event->status === 'cancelled'): ?> Cancelado
                                    <?php elseif($event->status === 'finished'): ?> Finalizado
                                    <?php else: ?> <?php echo e(ucfirst($event->status)); ?>

                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="<?php echo e(route('admin.events.show', $event)); ?>" class="text-indigo-600 hover:text-indigo-800 font-semibold mr-3">Ver</a>
                                <a href="<?php echo e(route('admin.events.edit', $event)); ?>" class="text-indigo-600 hover:text-indigo-800 font-semibold mr-3">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <?php echo e($events->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/admin/events/index.blade.php ENDPATH**/ ?>