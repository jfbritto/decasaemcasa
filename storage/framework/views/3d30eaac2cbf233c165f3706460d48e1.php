<?php $__env->startSection('title', $event->title); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
    <div class="px-4 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900"><?php echo e($event->city ?? $event->title); ?></h1>
            <div class="flex gap-2">
                <a href="<?php echo e(route('admin.events.edit', $event)); ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Editar
                </a>
                <a href="<?php echo e(route('admin.events.index')); ?>" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Voltar
                </a>
            </div>
        </div>

        
        <?php if($event->image): ?>
            <div class="rounded-lg overflow-hidden shadow-md mb-6">
                <img src="<?php echo e(asset('storage/' . $event->image)); ?>"
                     alt="<?php echo e($event->title); ?>"
                     class="w-full h-56 object-cover">
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Informações do Encontro</h2>
                <div class="space-y-3">
                    <?php if($event->title && $event->city && $event->title !== $event->city): ?>
                    <div>
                        <p class="text-sm text-gray-500">Título</p>
                        <p class="text-gray-900"><?php echo e($event->title); ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-500">Cidade</p>
                        <p class="text-gray-900"><?php echo e($event->city ?? '—'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Data</p>
                        <p class="text-gray-900"><?php echo e($event->date->format('d/m/Y H:i')); ?></p>
                    </div>
                    <?php if($event->arrival_time): ?>
                    <div>
                        <p class="text-sm text-gray-500">Horário de Chegada</p>
                        <p class="text-gray-900"><?php echo e($event->arrival_time); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if($event->full_address): ?>
                    <div>
                        <p class="text-sm text-gray-500">Endereço (SECRETO)</p>
                        <p class="text-gray-900 bg-red-50 border border-red-200 rounded p-2 text-sm"><?php echo e($event->full_address); ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-500">Descrição</p>
                        <p class="text-gray-900"><?php echo e($event->description ?? 'Sem descrição'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <span class="px-2 py-1 rounded text-xs
                            <?php if($event->status === 'published'): ?> bg-green-100 text-green-800
                            <?php elseif($event->status === 'draft'): ?> bg-gray-100 text-gray-800
                            <?php else: ?> bg-red-100 text-red-800
                            <?php endif; ?>">
                            <?php echo e(ucfirst($event->status)); ?>

                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Estatísticas de Inscrições</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Capacidade</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo e($event->capacity ?: 'Ilimitada'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total de Inscrições</p>
                        <p class="text-2xl font-bold text-indigo-600"><?php echo e($inscriptionStats['total']); ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-yellow-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-yellow-700"><?php echo e($inscriptionStats['pendente']); ?></p>
                            <p class="text-xs text-yellow-600">Pendentes</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-blue-700"><?php echo e($inscriptionStats['aprovado']); ?></p>
                            <p class="text-xs text-blue-600">Aprovados</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-green-700"><?php echo e($inscriptionStats['confirmado']); ?></p>
                            <p class="text-xs text-green-600">Confirmados</p>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-orange-700"><?php echo e($inscriptionStats['fila_de_espera']); ?></p>
                            <p class="text-xs text-orange-600">Fila de Espera</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="mt-6 bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">Inscrições deste Encontro</h2>
                <a href="<?php echo e(route('admin.inscricoes.index', ['city' => $event->city])); ?>" class="text-sm text-indigo-600 hover:text-indigo-800">
                    Ver todas com filtro
                </a>
            </div>
            <?php if($event->inscriptions->count() > 0): ?>
                <div class="overflow-x-auto">
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
            <?php else: ?>
                <p class="text-gray-500">Nenhuma inscrição para este encontro.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/admin/events/show.blade.php ENDPATH**/ ?>