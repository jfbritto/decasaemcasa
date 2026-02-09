<?php $__env->startSection('title', 'Editar Encontro'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
    <div class="px-4 sm:px-0">
        <h1 class="text-3xl font-bold text-gray-900 mb-6 mt-6">Editar Encontro</h1>

        <form action="<?php echo e(route('admin.events.update', $event)); ?>" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                    Título *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="title" name="title" type="text" value="<?php echo e(old('title', $event->title)); ?>" required>
                <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-500 text-xs italic"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                    Descrição
                </label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="description" name="description" rows="4"><?php echo e(old('description', $event->description)); ?></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                    Imagem
                </label>
                <?php if($event->image): ?>
                    <div class="mb-3">
                        <img src="<?php echo e(asset('storage/' . $event->image)); ?>"
                             alt="Imagem atual"
                             class="w-40 h-28 object-cover rounded-lg border">
                        <p class="text-xs text-gray-500 mt-1">Envie outra imagem para substituir.</p>
                    </div>
                <?php endif; ?>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="image" name="image" type="file" accept="image/*">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="date">
                        Data e Hora *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="date" name="date" type="datetime-local" value="<?php echo e(old('date', $event->date->format('Y-m-d\TH:i'))); ?>" required>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="city">
                        Cidade *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="city" name="city" type="text" value="<?php echo e(old('city', $event->city)); ?>" placeholder="Ex: Belo Horizonte" required>
                    <?php $__errorArgs = ['city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-xs italic"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="address">
                    Endereço Completo (SECRETO - só visível para confirmados)
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="address" name="address" type="text" value="<?php echo e(old('address', $event->full_address)); ?>" placeholder="Rua, número, bairro...">
                <p class="text-xs text-red-500 mt-1">Este endereço só será exibido para participantes com status CONFIRMADO.</p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="arrival_time">
                        Horário de Chegada
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="arrival_time" name="arrival_time" type="text" value="<?php echo e(old('arrival_time', $event->arrival_time)); ?>" placeholder="Ex: 19h30">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="capacity">
                        Capacidade (lugares na casa) *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="capacity" name="capacity" type="number" min="1" value="<?php echo e(old('capacity', $event->capacity)); ?>" required>
                    <?php $__errorArgs = ['capacity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-xs italic"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                    Status *
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="status" name="status" required>
                    <option value="draft" <?php echo e(old('status', $event->status) === 'draft' ? 'selected' : ''); ?>>Rascunho</option>
                    <option value="published" <?php echo e(old('status', $event->status) === 'published' ? 'selected' : ''); ?>>Publicado</option>
                    <option value="cancelled" <?php echo e(old('status', $event->status) === 'cancelled' ? 'selected' : ''); ?>>Cancelado</option>
                    <option value="finished" <?php echo e(old('status', $event->status) === 'finished' ? 'selected' : ''); ?>>Finalizado</option>
                </select>
            </div>

            <div class="flex items-center justify-between">
                <a href="<?php echo e(route('admin.events.index')); ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Cancelar
                </a>
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Atualizar Encontro
                </button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/admin/events/edit.blade.php ENDPATH**/ ?>