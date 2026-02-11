<?php $__env->startSection('title', 'Inscrições - Painel Admin'); ?>

<?php $__env->startSection('content'); ?>
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Curadoria de Inscrições</h1>
        </div>

        
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow p-4 text-center">
                <p class="text-2xl font-bold text-gray-900"><?php echo e($counts['total']); ?></p>
                <p class="text-sm text-gray-500">Total</p>
            </div>
            <div class="bg-yellow-50 rounded-xl shadow p-4 text-center border border-yellow-200">
                <p class="text-2xl font-bold text-yellow-700"><?php echo e($counts['pendente']); ?></p>
                <p class="text-sm text-yellow-600">Pendentes</p>
            </div>
            <div class="bg-blue-50 rounded-xl shadow p-4 text-center border border-blue-200">
                <p class="text-2xl font-bold text-blue-700"><?php echo e($counts['aprovado']); ?></p>
                <p class="text-sm text-blue-600">Aprovados</p>
            </div>
            <div class="bg-green-50 rounded-xl shadow p-4 text-center border border-green-200">
                <p class="text-2xl font-bold text-green-700"><?php echo e($counts['confirmado']); ?></p>
                <p class="text-sm text-green-600">Confirmados</p>
            </div>
            <div class="bg-orange-50 rounded-xl shadow p-4 text-center border border-orange-200">
                <p class="text-2xl font-bold text-orange-700"><?php echo e($counts['fila_de_espera']); ?></p>
                <p class="text-sm text-orange-600">Fila de Espera</p>
            </div>
        </div>

        
        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <form method="GET" action="<?php echo e(route('admin.inscricoes.index')); ?>" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo e(request('search')); ?>"
                           placeholder="Buscar por nome, email ou CPF..."
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div class="sm:w-48">
                    <select name="city" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Todas as cidades</option>
                        <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($city); ?>" <?php echo e(request('city') === $city ? 'selected' : ''); ?>><?php echo e($city); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="sm:w-40">
                    <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Todos os status</option>
                        <option value="pendente" <?php echo e(request('status') === 'pendente' ? 'selected' : ''); ?>>Pendente</option>
                        <option value="aprovado" <?php echo e(request('status') === 'aprovado' ? 'selected' : ''); ?>>Aprovado</option>
                        <option value="confirmado" <?php echo e(request('status') === 'confirmado' ? 'selected' : ''); ?>>Confirmado</option>
                        <option value="fila_de_espera" <?php echo e(request('status') === 'fila_de_espera' ? 'selected' : ''); ?>>Fila de Espera</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                    Filtrar
                </button>
                <?php if(request()->hasAny(['search', 'city', 'status'])): ?>
                    <a href="<?php echo e(route('admin.inscricoes.index')); ?>" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium text-center">
                        Limpar
                    </a>
                <?php endif; ?>
            </form>
        </div>

        
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <?php if($inscriptions->count() > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Cidade/Data</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">WhatsApp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Comprovante</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Data</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" x-data>
                            <?php $__currentLoopData = $inscriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inscription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div>
                                            <a href="<?php echo e(route('admin.inscricoes.show', $inscription)); ?>" class="font-medium text-indigo-600 hover:text-indigo-800">
                                                <?php echo e($inscription->full_name); ?>

                                            </a>
                                            <p class="text-xs text-gray-500"><?php echo e($inscription->email); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="font-medium text-gray-900"><?php echo e($inscription->event->city ?? $inscription->event->title); ?></span>
                                        <br>
                                        <span class="text-xs text-gray-500"><?php echo e($inscription->event->date->format('d/m/Y')); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?php echo e($inscription->whatsapp); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php if($inscription->isPending()): ?> bg-yellow-100 text-yellow-800
                                            <?php elseif($inscription->isApproved()): ?> bg-blue-100 text-blue-800
                                            <?php elseif($inscription->isConfirmed()): ?> bg-green-100 text-green-800
                                            <?php elseif($inscription->isWaitlisted()): ?> bg-orange-100 text-orange-800
                                            <?php endif; ?>">
                                            <?php echo e($inscription->status_label); ?>

                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if($inscription->payment_proof): ?>
                                            <span class="inline-flex items-center text-green-600">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Enviado
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo e($inscription->created_at->format('d/m/Y H:i')); ?>

                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <?php if($inscription->isPending()): ?>
                                                <form method="POST" action="<?php echo e(route('admin.inscricoes.aprovar', $inscription)); ?>" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700" title="Aprovar">
                                                        Aprovar
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?php echo e(route('admin.inscricoes.fila-espera', $inscription)); ?>" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="px-3 py-1 bg-orange-500 text-white text-xs font-medium rounded-lg hover:bg-orange-600" title="Fila de Espera">
                                                        Fila
                                                    </button>
                                                </form>
                                            <?php elseif($inscription->isApproved() && $inscription->payment_proof): ?>
                                                <form method="POST" action="<?php echo e(route('admin.inscricoes.confirmar', $inscription)); ?>" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="px-3 py-1 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700" title="Confirmar Pagamento">
                                                        Confirmar
                                                    </button>
                                                </form>
                                            <?php elseif($inscription->isWaitlisted()): ?>
                                                <form method="POST" action="<?php echo e(route('admin.inscricoes.aprovar', $inscription)); ?>" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700" title="Aprovar da Fila">
                                                        Aprovar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="<?php echo e(route('admin.inscricoes.show', $inscription)); ?>" class="px-3 py-1 border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50" title="Ver detalhes">
                                                Ver
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                
                                <tr x-data="{ showMotivation: false }">
                                    <td colspan="7" class="px-4 py-0">
                                        <button @click="showMotivation = !showMotivation"
                                                class="text-xs text-indigo-500 hover:text-indigo-700 py-1 flex items-center">
                                            <svg class="w-3 h-3 mr-1 transition-transform" :class="showMotivation ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                            <span x-text="showMotivation ? 'Ocultar história' : 'Ler história'"></span>
                                        </button>
                                        <div x-show="showMotivation" x-collapse class="pb-3">
                                            <div class="bg-amber-50 rounded-lg p-3 text-sm text-gray-700 italic border-l-3 border-amber-400">
                                                "<?php echo e($inscription->motivation); ?>"
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t">
                    <?php echo e($inscriptions->links()); ?>

                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="mt-4 text-gray-500">Nenhuma inscrição encontrada.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/admin/inscriptions/index.blade.php ENDPATH**/ ?>