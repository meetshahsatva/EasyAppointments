<?php extend('layouts/message_layout'); ?>

<?php section('content'); ?>

<?php $poll = vars('poll'); $notice = vars('notice'); $open = $poll['status'] === 'open'; ?>

<div class="text-start">
    <h3 class="mb-1"><?= e($poll['title']) ?></h3>
    <p class="text-muted mb-3">
        <?= e(vars('service_name')) ?> with <?= e(vars('provider_name')) ?>
        <?= vars('duration') ? '· ' . (int) vars('duration') . ' min' : '' ?>
    </p>

    <?php if (!empty($poll['description'])): ?>
        <p><?= nl2br(e($poll['description'])) ?></p>
    <?php endif; ?>

    <?php if ($notice === 'voted'): ?>
        <div class="alert alert-success">Thanks! Your votes were saved. You can vote again with the same email to change them.</div>
    <?php elseif ($notice === 'invalid'): ?>
        <div class="alert alert-danger">Please enter your name and a valid email, and pick at least one time.</div>
    <?php endif; ?>

    <?php if (!$open || $notice === 'closed'): ?>
        <div class="alert alert-secondary">This poll is closed.</div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('poll/vote') ?>">
        <input type="hidden" name="csrf_token" value="<?= vars('csrf_token') ?>">
        <input type="hidden" name="hash" value="<?= e($poll['hash']) ?>">

        <p class="small text-muted mb-2">Times are shown in <?= e(vars('provider_timezone')) ?>. Tick every time that works for you.</p>

        <?php foreach (vars('options') as $option): ?>
            <label class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                <span>
                    <input type="checkbox" class="form-check-input me-2" name="options[]"
                           value="<?= (int) $option['id'] ?>" <?= $open ? '' : 'disabled' ?>>
                    <?= e(date('D, d M Y · H:i', strtotime($option['start_datetime']))) ?>
                </span>
                <span class="badge bg-secondary"><?= (int) $option['count'] ?></span>
            </label>
        <?php endforeach; ?>

        <?php if ($open): ?>
            <div class="row g-2 mt-3">
                <div class="col-6"><input class="form-control" name="first_name" placeholder="First name" required></div>
                <div class="col-6"><input class="form-control" name="last_name" placeholder="Last name"></div>
                <div class="col-12"><input class="form-control" type="email" name="email" placeholder="Email" required></div>
            </div>
            <button class="btn btn-primary w-100 mt-3">Submit my votes</button>
        <?php endif; ?>
    </form>
</div>

<?php end_section('content'); ?>
