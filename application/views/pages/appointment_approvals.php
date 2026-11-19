<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="appointment-approvals-page">
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="border-bottom mb-4 py-2">
                <h4 class="mb-0 fw-light">Meeting requests</h4>
            </div>

            <?php if (vars('done') === 'approved'): ?>
                <div class="alert alert-success">Request accepted. The meeting is confirmed and the customer was notified.</div>
            <?php elseif (vars('done') === 'declined'): ?>
                <div class="alert alert-secondary">Request declined. The customer was notified.</div>
            <?php endif; ?>

            <?php if (empty(vars('requests'))): ?>
                <p class="text-muted">No requests are waiting for approval.</p>
            <?php endif; ?>

            <?php foreach (vars('requests') as $request): ?>
                <div class="border rounded p-3 mb-3 <?= (int) $request['id'] === (int) vars('highlight_id') ? 'border-primary border-2' : '' ?>">
                    <div class="d-flex flex-wrap justify-content-between">
                        <div>
                            <div class="fw-bold"><?= e($request['service']) ?> with <?= e($request['provider']) ?></div>
                            <div><?= e($request['start']) ?> &rarr; <?= e($request['end']) ?></div>
                            <div class="text-muted">
                                <?= e($request['customer']) ?> (<?= e($request['customer_email']) ?>)
                            </div>
                            <?php if ($request['notes'] !== ''): ?>
                                <div class="small mt-1"><?= e($request['notes']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2 align-items-start mt-2 mt-md-0">
                            <form method="post" action="<?= site_url('appointment_approvals/approve') ?>">
                                <input type="hidden" name="csrf_token" value="<?= vars('csrf_token') ?>">
                                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                <button class="btn btn-success">Accept</button>
                            </form>
                            <form method="post" action="<?= site_url('appointment_approvals/decline') ?>"
                                  class="d-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?= vars('csrf_token') ?>">
                                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                <input name="reason" class="form-control" placeholder="Reason (optional)">
                                <button class="btn btn-outline-danger">Decline</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php end_section('content'); ?>
