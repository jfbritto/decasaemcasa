<?php $__env->startSection('title', $event->title); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
    <div class="px-4 sm:px-0">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?php echo e($event->city ?? $event->title); ?></h1>
            <div class="flex flex-wrap gap-2">
                <a target="_blank" href="<?php echo e(route('admin.events.participantes-pdf', $event)); ?>" style="background-color:#dc2626;color:#fff;" class="px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:opacity-80 text-center">
                    Lista de Presença
                </a>
                <a href="<?php echo e(route('admin.events.edit', $event)); ?>" class="bg-indigo-600 text-white px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:bg-indigo-700 text-center">
                    Editar
                </a>
                <a href="<?php echo e(route('admin.events.index')); ?>" class="bg-gray-600 text-white px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:bg-gray-700 text-center">
                    Voltar
                </a>
            </div>
        </div>

        
        <?php if($event->image): ?>
            <div class="rounded-lg overflow-hidden shadow-md mb-6">
                <img src="<?php echo e(asset('storage/' . $event->image)); ?>"
                     alt="<?php echo e($event->title); ?>"
                     class="w-full h-48 sm:h-56 object-cover">
            </div>
        <?php endif; ?>

        
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg sm:text-xl font-semibold">Informações do Encontro</h2>
                <span class="px-3 py-1 rounded-full text-xs font-semibold
                    <?php if($event->status === 'published'): ?> bg-green-100 text-green-800
                    <?php elseif($event->status === 'draft'): ?> bg-gray-100 text-gray-800
                    <?php else: ?> bg-red-100 text-red-800
                    <?php endif; ?>">
                    <?php echo e(ucfirst($event->status)); ?>

                </span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php if($event->title && $event->city && $event->title !== $event->city): ?>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Título</p>
                    <p class="text-sm font-medium text-gray-900 mt-1"><?php echo e($event->title); ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Cidade</p>
                    <p class="text-sm font-medium text-gray-900 mt-1"><?php echo e($event->city ?? '—'); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Data</p>
                    <p class="text-sm font-medium text-gray-900 mt-1"><?php echo e($event->date->format('d/m/Y H:i')); ?></p>
                </div>
                <?php if($event->arrival_time): ?>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Horário de Chegada</p>
                    <p class="text-sm font-medium text-gray-900 mt-1"><?php echo e($event->arrival_time); ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Capacidade</p>
                    <p class="text-sm font-medium text-gray-900 mt-1"><?php echo e($event->capacity ?: 'Ilimitada'); ?></p>
                </div>
            </div>

            <?php if($event->full_address): ?>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Endereço (SECRETO)</p>
                <p class="text-sm text-gray-900 bg-red-50 border border-red-200 rounded p-2"><?php echo e($event->full_address); ?></p>
            </div>
            <?php endif; ?>

            <?php if($event->description): ?>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Descrição</p>
                <p class="text-sm text-gray-700 leading-relaxed"><?php echo e($event->description); ?></p>
            </div>
            <?php endif; ?>
        </div>

        
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 sm:gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-3 sm:p-4 text-center border-t-4 border-indigo-500">
                <p class="text-xl sm:text-2xl font-bold text-indigo-600"><?php echo e($inscriptionStats['total']); ?></p>
                <p class="text-[10px] sm:text-xs text-gray-500 mt-1">Total</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-3 sm:p-4 text-center border-t-4 border-yellow-400">
                <p class="text-xl sm:text-2xl font-bold text-yellow-700"><?php echo e($inscriptionStats['pendente']); ?></p>
                <p class="text-[10px] sm:text-xs text-gray-500 mt-1">Pendentes</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-3 sm:p-4 text-center border-t-4 border-blue-400">
                <p class="text-xl sm:text-2xl font-bold text-blue-700"><?php echo e($inscriptionStats['aprovado']); ?></p>
                <p class="text-[10px] sm:text-xs text-gray-500 mt-1">Aprovados</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-3 sm:p-4 text-center border-t-4 border-green-400">
                <p class="text-xl sm:text-2xl font-bold text-green-700"><?php echo e($inscriptionStats['confirmado']); ?></p>
                <p class="text-[10px] sm:text-xs text-gray-500 mt-1">Confirmados</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-3 sm:p-4 text-center border-t-4 border-orange-400">
                <p class="text-xl sm:text-2xl font-bold text-orange-700"><?php echo e($inscriptionStats['fila_de_espera']); ?></p>
                <p class="text-[10px] sm:text-xs text-gray-500 mt-1">Fila Espera</p>
            </div>
        </div>

        
        <div class="mt-6 bg-white rounded-lg shadow-md p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <h2 class="text-lg sm:text-xl font-semibold">Inscrições deste Encontro</h2>
                <a href="<?php echo e(route('admin.inscricoes.index', ['city' => $event->city])); ?>" class="text-sm text-indigo-600 hover:text-indigo-800">
                    Ver todas com filtro →
                </a>
            </div>
            <?php if($event->inscriptions->count() > 0): ?>
                
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">WhatsApp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php $__currentLoopData = $event->inscriptions->sortByDesc('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inscription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <a href="<?php echo e(route('admin.inscricoes.show', $inscription)); ?>" class="font-medium text-indigo-600 hover:text-indigo-800">
                                            <?php echo e($inscription->full_name); ?>

                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?php echo e($inscription->whatsapp); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                            <?php if($inscription->isPending()): ?> bg-yellow-100 text-yellow-800
                                            <?php elseif($inscription->isApproved()): ?> bg-blue-100 text-blue-800
                                            <?php elseif($inscription->isConfirmed()): ?> bg-green-100 text-green-800
                                            <?php elseif($inscription->isWaitlisted()): ?> bg-orange-100 text-orange-800
                                            <?php endif; ?>">
                                            <?php echo e($inscription->status_label); ?>

                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="<?php echo e(route('admin.inscricoes.show', $inscription)); ?>" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                
                <div class="sm:hidden space-y-3">
                    <?php $__currentLoopData = $event->inscriptions->sortByDesc('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inscription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('admin.inscricoes.show', $inscription)); ?>" class="block bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900"><?php echo e($inscription->full_name); ?></p>
                                <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                    <?php if($inscription->isPending()): ?> bg-yellow-100 text-yellow-800
                                    <?php elseif($inscription->isApproved()): ?> bg-blue-100 text-blue-800
                                    <?php elseif($inscription->isConfirmed()): ?> bg-green-100 text-green-800
                                    <?php elseif($inscription->isWaitlisted()): ?> bg-orange-100 text-orange-800
                                    <?php endif; ?>">
                                    <?php echo e($inscription->status_label); ?>

                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1"><?php echo e($inscription->whatsapp); ?></p>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">Nenhuma inscrição para este encontro.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/admin/events/show.blade.php ENDPATH**/ ?>