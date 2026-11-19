<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="account-page" class="container backend-page py-3">
    <div id="account">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <form>
                    <fieldset>
                        <div class="d-flex justify-content-between align-items-center border-bottom mb-4 py-2">
                            <h4 class="mb-0 fw-light">
                                <?= lang('account') ?>
                            </h4>

                            <?php if (can('edit', PRIV_USER_SETTINGS)): ?>
                                <button type="button" id="save-settings" class="btn btn-primary">
                                    <i class="fas fa-check-square me-2"></i>
                                    <?= lang('save') ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <input type="hidden" id="user-id">

                                <div class="mb-3">
                                    <label class="form-label" for="first-name">
                                        <?= lang('first_name') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="first-name" class="form-control required">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="last-name">
                                        <?= lang('last_name') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="last-name" class="form-control required">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="email">
                                        <?= lang('email') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="email" class="form-control required">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="phone-number">
                                        <?= lang('phone_number') ?>
                                    </label>
                                    <input id="phone-number" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="mobile-number">
                                        <?= lang('mobile_number') ?>
                                    </label>
                                    <input id="mobile-number" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="address">
                                        <?= lang('address') ?>
                                    </label>
                                    <input id="address" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="city">
                                        <?= lang('city') ?>
                                    </label>
                                    <input id="city" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="state">
                                        <?= lang('state') ?>
                                    </label>
                                    <input id="state" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="zip-code">
                                        <?= lang('zip_code') ?>
                                    </label>
                                    <input id="zip-code" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="notes">
                                        <?= lang('notes') ?>
                                    </label>
                                    <textarea id="notes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label" for="username">
                                        <?= lang('username') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input id="username" class="form-control required">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="current-password">
                                        <?= lang('current_password') ?>
                                    </label>
                                    <input type="password" id="current-password" class="form-control"
                                           autocomplete="current-password">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="password">
                                        <?= lang('password') ?>
                                    </label>
                                    <input type="password" id="password" class="form-control"
                                           autocomplete="new-password">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="retype-password">
                                        <?= lang('retype_password') ?>
                                    </label>
                                    <input type="password" id="retype-password" class="form-control"
                                           autocomplete="new-password">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="calendar-view"><?= lang('calendar') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select id="calendar-view" class="form-select required">
                                        <option value="default"><?= lang('default') ?></option>
                                        <option value="table"><?= lang('table') ?></option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="language">
                                        <?= lang('language') ?>
                                        <span class="text-danger" hidden>*</span>
                                    </label>
                                    <select id="language" class="form-select required">
                                        <?php foreach (vars('available_languages') as $available_language): ?>
                                            <option value="<?= $available_language ?>">
                                                <?= ucfirst($available_language) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="timezone">
                                        <?= lang('timezone') ?>
                                    </label>
                                    <?php component('timezone_dropdown', [
                                        'attributes' => 'id="timezone" class="form-select required"',
                                        'grouped_timezones' => vars('grouped_timezones'),
                                    ]); ?>
                                </div>

                                <div>
                                    <label class="form-label mb-3">
                                        <?= lang('options') ?>
                                    </label>
                                </div>

                                <div class="border rounded mb-3 p-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" id="notifications" type="checkbox">
                                        <label class="form-check-label" for="notifications">
                                            <?= lang('receive_notifications') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </form>

                <?php if (vars('role_slug') === DB_SLUG_PROVIDER || session('role_slug') === DB_SLUG_PROVIDER): ?>
                    <div id="calendar-integration" class="border rounded p-3 mt-4">
                        <h5 class="fw-light mb-3">Calendar Integration</h5>
                        <p class="text-muted small mb-3">
                            Connect your own Google Calendar. This only affects your own appointments and availability.
                        </p>
                        <div id="integration-google-off" class="alert alert-warning" hidden>
                            Google Calendar sync is not enabled by the administrator.
                        </div>
                        <div id="integration-disconnected" hidden>
                            <button type="button" class="btn btn-outline-primary" id="integration-connect">
                                <i class="fas fa-link me-2"></i>Connect my Google Calendar
                            </button>
                        </div>
                        <div id="integration-connected" hidden>
                            <span class="badge bg-success me-2">Connected</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="integration-select">Change calendar</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="integration-sync">Sync now</button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="integration-disconnect">Disconnect</button>
                        </div>
                        <div id="integration-calendars" class="mt-3" hidden>
                            <label class="form-label" for="integration-calendar-select">Calendar to sync with</label>
                            <select id="integration-calendar-select" class="form-select mb-2"></select>
                            <button type="button" class="btn btn-primary btn-sm" id="integration-calendar-save">Save</button>
                        </div>
                        <div id="integration-status" class="small mt-3"></div>
                    </div>

                    <div id="booking-links" class="border rounded p-3 mt-4">
                        <h5 class="fw-light mb-3">My booking link</h5>
                        <p class="text-muted small mb-3">
                            Share these links so customers book directly with you. The service links also preselect the service.
                        </p>
                        <?php foreach (vars('booking_links') ?? [] as $i => $link): ?>
                            <label class="form-label small mb-1" for="booking-link-<?= $i ?>"><?= e($link['label']) ?></label>
                            <div class="input-group mb-2">
                                <input class="form-control" id="booking-link-<?= $i ?>" readonly value="<?= e($link['url']) ?>">
                                <button type="button" class="btn btn-outline-primary copy-booking-link"
                                        data-target="booking-link-<?= $i ?>">
                                    <i class="fas fa-copy me-1"></i>Copy
                                </button>
                                <a class="btn btn-outline-secondary" href="<?= e($link['url']) ?>" target="_blank" rel="noopener">
                                    Open
                                </a>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty(vars('booking_links'))): ?>
                            <p class="small text-muted mb-0">No services are assigned to you yet.</p>
                        <?php endif; ?>
                    </div>

                    <div id="meeting-approval" class="border rounded p-3 mt-4">
                        <h5 class="fw-light mb-3">Meeting approval</h5>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="require-approval">
                            <label class="form-check-label" for="require-approval">
                                Require my approval for new meetings
                            </label>
                        </div>
                        <p class="text-muted small mb-2">
                            When on, customers' bookings stay pending until you accept them, and customers are told this
                            before they book. Nothing is added to your Google Calendar until you accept.
                        </p>
                        <a href="<?= site_url('appointment_approvals') ?>">View meeting requests</a>
                        <div id="approval-status" class="small mt-2"></div>
                    </div>
                    <div hidden>
                        <div class="small mt-2 text-muted">For CalDAV, use Enable Sync on the Calendar page.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/http/account_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/account.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/google_http_client.js') ?>"></script>
<script src="<?= base_url('assets/js/pages/account_integration.js') ?>"></script>

<?php end_section('scripts'); ?>
