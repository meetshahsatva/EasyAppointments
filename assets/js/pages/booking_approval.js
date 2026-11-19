/* Tells customers when the selected provider needs to approve the meeting. */
(function () {
    const $ = window.jQuery;
    const MESSAGE =
        'This meeting needs approval from the provider. It is only a request until they accept it, and you will be notified by email.';

    function needsApproval() {
        const id = $('#select-provider').val();
        const provider = (vars('available_providers') || []).find((p) => String(p.id) === String(id));
        return !!(provider && provider.requires_approval);
    }

    function notice(id) {
        return $('<div id="' + id + '" class="alert alert-warning small mb-3" role="alert" hidden></div>').text(MESSAGE);
    }

    function update() {
        const show = needsApproval();
        $('#approval-notice, #approval-notice-confirm').prop('hidden', !show);
    }

    $(function () {
        $('#select-provider').after(notice('approval-notice'));
        $('#wizard-frame-4 .frame-container').find('.frame-title').after(notice('approval-notice-confirm'));
        $('#select-provider, #select-service').on('change', update);
        $(document).on('click', '.button-next, #button-next-1, #button-next-2, #button-next-3', update);
        update();
    });
})();
