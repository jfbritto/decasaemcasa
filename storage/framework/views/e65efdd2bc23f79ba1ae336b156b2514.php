<?php $__env->startSection('subject', 'Inscrição recebida - De Casa em Casa'); ?>

<?php $__env->startSection('badge'); ?>
<span style="display:inline-block; background-color:#fef3c7; color:#92400e; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Inscrição Recebida
</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<p style="margin:0 0 16px; color:#3d3a34; font-size:16px; line-height:1.6;">
    Olá <strong><?php echo e($inscription->full_name); ?></strong>,
</p>
<p style="margin:0 0 16px; color:#5c584f; font-size:15px; line-height:1.7;">
    Recebemos sua história! Estamos em fase de curadoria. Como os lugares são limitados e em lares, fazemos essa leitura com carinho.
</p>
<p style="margin:0; color:#5c584f; font-size:15px; line-height:1.7;">
    Aguarde nosso retorno — avisaremos assim que tivermos novidades.
</p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('event_info'); ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="40" valign="top" style="padding-right:14px;">
            <div style="width:36px; height:36px; background:linear-gradient(135deg, #4f46e5, #7c3aed); border-radius:8px; text-align:center; line-height:36px; color:#fff; font-size:16px;">
                &#127968;
            </div>
        </td>
        <td valign="top">
            <p style="margin:0 0 4px; color:#3d3a34; font-size:15px; font-weight:600;"><?php echo e($event->city); ?></p>
            <p style="margin:0; color:#8a8578; font-size:13px;"><?php echo e($event->date->format('d/m/Y')); ?></p>
        </td>
    </tr>
</table>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('cta_url', $statusUrl); ?>
<?php $__env->startSection('cta_text', 'Acompanhar Minha Inscrição'); ?>

<?php echo $__env->make('emails.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/emails/inscription-received.blade.php ENDPATH**/ ?>