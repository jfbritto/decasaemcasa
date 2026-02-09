<?php $__env->startSection('title', 'Status da Inscrição - De Casa em Casa'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-gradient-to-b from-amber-50 to-white py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Status da Inscrição</h1>
            <p class="text-gray-600 mt-1"><?php echo e($inscription->full_name); ?></p>
        </div>

        
        <?php if($inscription->event->image): ?>
            <div class="rounded-2xl overflow-hidden shadow-lg mb-6">
                <img src="<?php echo e(asset('storage/' . $inscription->event->image)); ?>"
                     alt="<?php echo e($inscription->event->title); ?>"
                     class="w-full h-48 sm:h-64 object-cover">
            </div>
        <?php endif; ?>

        
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-6">

            
            <div class="text-center mb-6">
                <?php if($inscription->isPending()): ?>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        Pendente - Em Curadoria
                    </span>
                <?php elseif($inscription->isApproved()): ?>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Aprovado - Aguardando Pagamento
                    </span>
                <?php elseif($inscription->isConfirmed()): ?>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Confirmado
                    </span>
                <?php elseif($inscription->isWaitlisted()): ?>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-orange-100 text-orange-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        Fila de Espera
                    </span>
                <?php endif; ?>
            </div>

            
            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900"><?php echo e($inscription->event->city ?? $inscription->event->title); ?></p>
                        <p class="text-sm text-gray-600"><?php echo e($inscription->event->date->format('d/m/Y')); ?></p>
                    </div>
                    <?php if($inscription->event->title && $inscription->event->city && $inscription->event->title !== $inscription->event->city): ?>
                        <p class="text-sm text-gray-500"><?php echo e($inscription->event->title); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="rounded-xl p-5 mb-6
                <?php if($inscription->isPending()): ?> bg-yellow-50 border border-yellow-200
                <?php elseif($inscription->isApproved()): ?> bg-blue-50 border border-blue-200
                <?php elseif($inscription->isConfirmed()): ?> bg-green-50 border border-green-200
                <?php elseif($inscription->isWaitlisted()): ?> bg-orange-50 border border-orange-200
                <?php endif; ?>">

                <?php if($inscription->isPending()): ?>
                    <p class="text-gray-700 leading-relaxed">
                        Recebemos sua história! Estamos em fase de curadoria. Como os lugares são limitados e em lares, fazemos essa leitura com carinho. Aguarde nosso retorno.
                    </p>
                <?php elseif($inscription->isApproved()): ?>
                    <p class="text-gray-700 leading-relaxed">
                        Tudo pronto! Sua participação foi aprovada. Para garantir sua cadeira na sala, envie o comprovante de pagamento abaixo.
                    </p>
                <?php elseif($inscription->isConfirmed()): ?>
                    <p class="text-gray-700 leading-relaxed">
                        Que alegria ter você conosco! Prepare o coração!
                    </p>
                <?php elseif($inscription->isWaitlisted()): ?>
                    <p class="text-gray-700 leading-relaxed">
                        Recebemos sua história e ficamos muito felizes! No momento, as cadeiras para este encontro já foram preenchidas. Vamos manter seu contato em nossa "Fila de Espera"; caso haja alguma desistência ou uma nova data por perto, avisaremos você.
                    </p>
                <?php endif; ?>
            </div>

            
            <?php if($inscription->isApproved()): ?>
                <div class="border-2 border-dashed border-blue-300 rounded-xl p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Enviar Comprovante de Pagamento</h3>

                    <?php if($inscription->payment_proof): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                            <div class="flex items-center text-green-700">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span class="text-sm font-medium">Comprovante já enviado. Aguardando confirmação da equipe.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(route('inscricao.upload-comprovante', $inscription->token)); ?>" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="mb-4">
                            <label for="payment_proof" class="block text-sm text-gray-600 mb-2">
                                <?php echo e($inscription->payment_proof ? 'Enviar novo comprovante (substituir):' : 'Selecione o comprovante (imagem ou PDF, max 5MB):'); ?>

                            </label>
                            <input type="file" name="payment_proof" id="payment_proof" accept=".jpg,.jpeg,.png,.pdf"
                                   class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                            <?php $__errorArgs = ['payment_proof'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <button type="submit" class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200">
                            Enviar Comprovante
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            
            <?php if($inscription->isConfirmed()): ?>
                <div class="bg-green-50 border-2 border-green-300 rounded-xl p-6">
                    <h3 class="font-semibold text-green-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Informações do Encontro
                    </h3>

                    <?php if($inscription->event->full_address): ?>
                        <div class="space-y-2">
                            <p class="text-gray-700">
                                <strong>Endereço:</strong> <?php echo e($inscription->event->full_address); ?>

                            </p>
                            <?php if($inscription->event->arrival_time): ?>
                                <p class="text-gray-700">
                                    <strong>Horário de Chegada:</strong> <?php echo e($inscription->event->arrival_time); ?>

                                </p>
                            <?php endif; ?>
                            <p class="text-gray-700">
                                <strong>Data:</strong> <?php echo e($inscription->event->date->format('d/m/Y')); ?>

                            </p>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600">As informações de endereço serão disponibilizadas em breve.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="text-center">
            <p class="text-sm text-gray-500 mb-2">Salve este link para acompanhar o status da sua inscrição:</p>
            <div class="bg-white rounded-xl border border-gray-200 p-3 flex items-center justify-between">
                <input type="text" value="<?php echo e(route('inscricao.status', $inscription->token)); ?>" readonly
                       class="flex-1 text-sm text-gray-600 bg-transparent border-none focus:ring-0 truncate" id="status-url">
                <button onclick="navigator.clipboard.writeText(document.getElementById('status-url').value); this.textContent = 'Copiado!'; setTimeout(() => this.textContent = 'Copiar', 2000);"
                        class="ml-2 px-3 py-1 bg-indigo-100 text-indigo-600 text-sm font-medium rounded-lg hover:bg-indigo-200 transition-colors">
                    Copiar
                </button>
            </div>
        </div>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/inscriptions/status.blade.php ENDPATH**/ ?>