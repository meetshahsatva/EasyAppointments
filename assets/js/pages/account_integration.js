/* Per-user calendar integration (Google) on the Account page. Scoped to the logged-in provider only. */
(function () {
    const integration = vars('integration') || {};
    const userId = Number(integration.user_id);
    const $ = window.jQuery;

    if (!integration.is_provider || !$('#calendar-integration').length) {
        return;
    }

    function status(text, isError) {
        $('#integration-status').text(text).toggleClass('text-danger', !!isError).toggleClass('text-success', !isError);
    }

    function render() {
        const feature = !!integration.google_feature;
        $('#integration-google-off').prop('hidden', feature);
        $('#integration-disconnected').prop('hidden', !feature || !!integration.google_sync);
        $('#integration-connected').prop('hidden', !feature || !integration.google_sync);
        if (!integration.google_sync) {
            $('#integration-calendars').prop('hidden', true);
        }
    }

    function loadCalendars() {
        App.Http.Google.getGoogleCalendars(userId)
            .done(function (calendars) {
                const $select = $('#integration-calendar-select').empty();
                calendars.forEach(function (c) {
                    $select.append(new Option(c.summary, c.id));
                });
                $('#integration-calendars').prop('hidden', false);
            })
            .fail(function () {
                status('Could not load your Google calendars.', true);
            });
    }

    $('#integration-connect').on('click', function () {
        const url = App.Utils.Url.siteUrl('google/oauth/' + userId);
        const win = window.open(url, 'Easy!Appointments', 'width=800, height=600');

        function onMessage(event) {
            if (event.origin !== window.location.origin || event.data !== 'oauth_success') {
                return;
            }
            window.removeEventListener('message', onMessage);
            if (win && !win.closed) {
                win.close();
            }
            integration.google_sync = true;
            render();
            status('Connected. Choose the calendar to sync with.', false);
            loadCalendars();
        }

        window.addEventListener('message', onMessage);
    });

    $('#integration-select').on('click', loadCalendars);

    $('#integration-calendar-save').on('click', function () {
        App.Http.Google.selectGoogleCalendar(userId, $('#integration-calendar-select').val())
            .done(function () {
                $('#integration-calendars').prop('hidden', true);
                status('Calendar saved.', false);
            })
            .fail(function () {
                status('Could not save the calendar.', true);
            });
    });

    $('#integration-sync').on('click', function () {
        status('Syncing...', false);
        App.Http.Google.syncWithGoogle(userId)
            .done(function () {
                status('Sync completed.', false);
            })
            .fail(function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Sync failed.';
                status(msg, true);
            });
    });

    $('#integration-disconnect').on('click', function () {
        if (!window.confirm('Disconnect your Google Calendar?')) {
            return;
        }
        App.Http.Google.disableProviderSync(userId).done(function () {
            integration.google_sync = false;
            render();
            status('Disconnected.', false);
        });
    });

    // Meeting approval toggle.
    $('#require-approval')
        .prop('checked', !!integration.require_approval)
        .on('change', function () {
            const checked = $(this).prop('checked');
            $.post(App.Utils.Url.siteUrl('account/save_approval_setting'), {
                csrf_token: vars('csrf_token'),
                require_approval: checked ? 1 : 0,
            })
                .done(function () {
                    $('#approval-status').text(checked ? 'Approval is now required.' : 'Approval is no longer required.').removeClass('text-danger').addClass('text-success');
                })
                .fail(function () {
                    $('#require-approval').prop('checked', !checked);
                    $('#approval-status').text('Could not save the setting.').removeClass('text-success').addClass('text-danger');
                });
        });

    // Copy booking links.
    $(document).on('click', '.copy-booking-link', function () {
        const $button = $(this);
        const input = document.getElementById($button.data('target'));
        const done = function () {
            const original = $button.html();
            $button.html('<i class="fas fa-check me-1"></i>Copied');
            setTimeout(function () {
                $button.html(original);
            }, 1500);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(input.value).then(done);
        } else {
            input.select();
            document.execCommand('copy');
            done();
        }
    });

    render();
})();
