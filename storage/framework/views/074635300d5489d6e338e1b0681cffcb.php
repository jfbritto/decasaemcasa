<?php $__env->startSection('subject', 'Confirmado! Prepare o coração - De Casa em Casa'); ?>

<?php $__env->startSection('badge'); ?>
<span style="display:inline-block; background-color:#d1fae5; color:#065f46; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Confirmado(a)!
</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong><?php echo e($inscription->full_name); ?></strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Que alegria ter você conosco! Sua presença está <strong style="color:#065f46;">confirmada</strong>. Prepare o coração para um encontro especial.
</p>
<p style="margin:0; color:#4a4639; font-size:14px; line-height:1.6; font-style:italic;">
    Confira os detalhes do encontro abaixo.
</p>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('event_info'); ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <?php if($event->full_address): ?>
    <tr>
        <td style="padding-bottom:12px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="32" valign="top" style="padding-right:10px;">
                        <span style="font-size:18px;">&#128205;</span>
                    </td>
                    <td valign="top">
                        <p style="margin:0 0 2px; color:#9a9384; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Endereço</p>
                        <p style="margin:0; color:#1a2e6e; font-size:14px; font-weight:500;"><?php echo e($event->full_address); ?></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <?php endif; ?>
    <?php if($event->arrival_time): ?>
    <tr>
        <td style="padding-bottom:12px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="32" valign="top" style="padding-right:10px;">
                        <span style="font-size:18px;">&#128336;</span>
                    </td>
                    <td valign="top">
                        <p style="margin:0 0 2px; color:#9a9384; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Horário de Chegada</p>
                        <p style="margin:0; color:#1a2e6e; font-size:14px; font-weight:500;"><?php echo e($event->arrival_time); ?></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <?php endif; ?>
    <tr>
        <td>
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="32" valign="top" style="padding-right:10px;">
                        <span style="font-size:18px;">&#128197;</span>
                    </td>
                    <td valign="top">
                        <p style="margin:0 0 2px; color:#9a9384; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Data</p>
                        <p style="margin:0; color:#1a2e6e; font-size:14px; font-weight:500;"><?php echo e($event->date->format('d/m/Y')); ?> — <?php echo e($event->city); ?></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('cta_url', $statusUrl); ?>
<?php $__env->startSection('cta_text', 'Ver Detalhes do Encontro'); ?>

<?php echo $__env->make('emails.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/resources/views/emails/inscription-confirmed.blade.php ENDPATH**/ ?>