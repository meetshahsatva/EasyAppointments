<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<?php $poll = vars('poll'); $open = $poll['status'] === 'open'; ?>

<div class="container backend-page py-3">
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                <h4 class="mb-0 fw-light"><?= e($poll['title']) ?></h4>
                <a href="<?= site_url('polls') ?>" class="btn btn-outline-secondary btn-sm">All polls</a>
            </div>

            <?php if (vars('flash') === 'created'): ?>
                <div class="alert alert-success">
                    Poll created<?= vars('sent') ? ' and ' . (int) vars('sent') . ' invitation(s) sent' : '' ?>.
                    Share the link below.
                </div>
            <?php elseif (vars('flash') === 'booked'): ?>
                <div class="alert alert-success">
                    Booked: <?= (int) vars('booked') ?> appointment(s) created and everyone was emailed. The poll is closed.
                </div>
            <?php elseif (vars('flash') === 'failed'): ?>
                <div class="alert alert-danger">Nothing could be booked. The poll stays open.</div>
            <?php elseif (vars('flash') === 'novotes'): ?>
                <div class="alert alert-warning">Nobody voted for that option yet.</div>
            <?php elseif (vars('flash') === 'closed'): ?>
                <div class="alert alert-secondary">Poll closed.</div>
            <?php endif; ?>

            <?php if (vars('error') !== ''): ?>
                <div class="alert alert-warning small">Problems: <?= e(vars('error')) ?></div>
            <?php endif; ?>

            <p class="text-muted mb-1">
                <?= e(vars('service_name')) ?> with <?= e(vars('provider_name')) ?> ·
                times in <?= e(vars('provider_timezone')) ?> ·
                status: <strong><?= e($poll['status']) ?></strong>
            </p>

            <div class="input-group mb-4">
                <input class="form-control" id="poll-link" readonly value="<?= e(vars('poll_link')) ?>">
                <button class="btn btn-outline-secondary" type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('poll-link').value)">
                    Copy link
                </button>
            </div>

            <?php foreach (vars('options') as $option): ?>
                <?php $chosen = (int) ($poll['id_chosen_option'] ?? 0) === (int) $option['id']; ?>
                <div class="border rounded p-3 mb-3 <?= $chosen ? 'border-success border-2' : '' ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= e(date('D, d M Y H:i', strtotime($option['start_datetime']))) ?></strong>
                            <span class="badge bg-primary ms-2"><?= count($option['voters']) ?> available</span>
                            <?php if ($chosen): ?><span class="badge bg-success ms-1">Booked</span><?php endif; ?>
                        </div>
                        <?php if ($open): ?>
                            <form method="post" action="<?= site_url('polls/finalize') ?>">
                                <input type="hidden" name="csrf_token" value="<?= vars('csrf_token') ?>">
                                <input type="hidden" name="poll_id" value="<?= (int) $poll['id'] ?>">
                                <input type="hidden" name="option_id" value="<?= (int) $option['id'] ?>">
                                <button class="btn btn-success btn-sm"
                                        onclick="return confirm('Book this time for everyone who voted for it?')">
                                    Book this time
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if ($option['voters']): ?>
                        <div class="small text-muted mt-2"><?= e(implode(', ', $option['voters'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="d-flex gap-2 mt-4">
                <?php if ($open): ?>
                    <form method="post" action="<?= site_url('polls/close') ?>">
                        <input type="hidden" name="csrf_token" value="<?= vars('csrf_token') ?>">
                        <input type="hidden" name="poll_id" value="<?= (int) $poll['id'] ?>">
                        <button class="btn btn-outline-secondary">Close without booking</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?= site_url('polls/delete') ?>">
                    <input type="hidden" name="csrf_token" value="<?= vars('csrf_token') ?>">
                    <input type="hidden" name="poll_id" value="<?= (int) $poll['id'] ?>">
                    <button class="btn btn-outline-danger"
                            onclick="return confirm('Delete this poll and its votes?')">Delete poll</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>
