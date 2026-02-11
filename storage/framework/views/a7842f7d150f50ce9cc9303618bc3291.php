<?php $__env->startSection('subject', 'Sua participação foi aprovada! - De Casa em Casa'); ?>

<?php $__env->startSection('badge'); ?>
<span style="display:inline-block; background-color:#dbeafe; color:#1e40af; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Aprovado(a)!
</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<p style="margin:0 0 16px; color:#3d3a34; font-size:16px; line-height:1.6;">
    Olá <strong><?php echo e($inscription->full_name); ?></strong>,
</p>
<p style="margin:0 0 16px; color:#5c584f; font-size:15px; line-height:1.7;">
    Tudo pronto! Sua participação foi <strong style="color:#1e40af;">aprovada</strong>. Para garantir sua cadeira na sala, conclua sua contribuição no link abaixo.
</p>
<p style="margin:0; color:#5c584f; font-size:15px; line-height:1.7;">
    Acesse o link para enviar o comprovante de pagamento e finalizar sua confirmação.
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
<?php $__env->startSection('cta_text', 'Enviar Comprovante'); ?>

<?php echo $__env->make('emails.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/emails/inscription-approved.blade.php ENDPATH**/ ?>