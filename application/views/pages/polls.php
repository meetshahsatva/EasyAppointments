<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container backend-page py-3" id="polls-page">
    <div class="row">
        <div class="col-lg-10 offset-lg-1">
            <div class="border-bottom mb-4 py-2">
                <h4 class="mb-0 fw-light">Group meeting polls</h4>
            </div>

            <?php if (vars('flash') === 'deleted'): ?>
                <div class="alert alert-secondary">Poll deleted.</div>
            <?php endif; ?>
            <?php if (vars('error') !== ''): ?>
                <div class="alert alert-danger"><?= e(vars('error')) ?></div>
            <?php endif; ?>

            <h5 class="fw-light mb-3">Your polls</h5>
            <?php if (empty(vars('polls'))): ?>
                <p class="text-muted">No polls yet. Create one below.</p>
            <?php else: ?>
                <div class="list-group mb-5">
                    <?php foreach (vars('polls') as $poll): ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                           href="<?= site_url('polls/results/' . (int) $poll['id']) ?>">
                            <span><?= e($poll['title']) ?></span>
                            <span>
                                <span class="badge bg-<?= $poll['status'] === 'open' ? 'success' : 'secondary' ?> me-2">
                                    <?= e($poll['status']) ?>
                                </span>
                                <span class="text-muted small"><?= (int) $poll['votes'] ?> voter(s)</span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h5 class="fw-light mb-3">Create a poll</h5>
            <form method="post" action="<?= site_url('polls/create') ?>" class="border rounded p-3 mb-5">
                <input type="hidden" name="csrf_token" value="<?= vars('csrf_token') ?>">

                <div class="mb-3">
                    <label class="form-label" for="poll-title">Title</label>
                    <input class="form-control" id="poll-title" name="title" required maxlength="250"
                           placeholder="Team kickoff meeting">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="poll-description">Description (optional)</label>
                    <textarea class="form-control" id="poll-description" name="description" rows="2"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="poll-provider">Host (provider)</label>
                        <select class="form-select" id="poll-provider" name="id_users_provider" required>
                            <?php foreach (vars('providers') as $provider): ?>
                                <option value="<?= (int) $provider['id'] ?>"><?= e($provider['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="poll-service">Service (sets the duration)</label>
                        <select class="form-select" id="poll-service" name="id_services" required>
                            <?php foreach (vars('services') as $service): ?>
                                <option value="<?= (int) $service['id'] ?>">
                                    <?= e($service['name']) ?> (<?= (int) $service['duration'] ?> min)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Time options (in the host's time zone, at least 2, within the next 3 months)</label>
                    <div id="poll-options"></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="poll-add-option">
                        + Add option
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="poll-invitees">Invite by email (optional, one per line)</label>
                    <textarea class="form-control" id="poll-invitees" name="invitees" rows="3"></textarea>
                </div>

                <button class="btn btn-primary">Create poll</button>
            </form>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
    (function () {
        const map = <?= json_encode((object) vars('provider_services')) ?>;
        const providerSelect = document.getElementById('poll-provider');
        const serviceSelect = document.getElementById('poll-service');

        function filterServices() {
            const allowed = (map[providerSelect.value] || []).map(String);
            let firstVisible = null;
            Array.from(serviceSelect.options).forEach(function (o) {
                const ok = allowed.indexOf(o.value) !== -1;
                o.hidden = !ok;
                o.disabled = !ok;
                if (ok && firstVisible === null) firstVisible = o.value;
            });
            if (serviceSelect.selectedOptions.length === 0 || serviceSelect.selectedOptions[0].disabled) {
                if (firstVisible !== null) serviceSelect.value = firstVisible;
            }
        }

        function toLocal(d) {
            const pad = (n) => String(n).padStart(2, '0');
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }

        function addOption() {
            const wrap = document.getElementById('poll-options');
            const input = document.createElement('input');
            input.type = 'datetime-local';
            input.name = 'options[]';
            input.className = 'form-control mb-2';
            input.min = toLocal(new Date());
            const max = new Date();
            max.setMonth(max.getMonth() + 3);
            input.max = toLocal(max);
            wrap.appendChild(input);
        }

        providerSelect.addEventListener('change', filterServices);
        document.getElementById('poll-add-option').addEventListener('click', addOption);
        filterServices();
        addOption();
        addOption();
    })();
</script>
<?php end_section('scripts'); ?>
