<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Appointment approvals controller.
 *
 * Lets providers (and admins / assigned secretaries) accept or decline pending meeting requests.
 */
class Appointment_approvals extends EA_Controller
{
    const STATUS_PENDING = 'Pending';

    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');

        $this->load->library('accounts');
        $this->load->library('notifications');
        $this->load->library('synchronization');
        $this->load->library('webhooks_client');
    }

    /**
     * List the pending requests the current user may handle.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('appointment_approvals')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_APPOINTMENTS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $rows = [];

        foreach ($this->appointments_model->get(['status' => self::STATUS_PENDING, 'is_unavailability' => false]) as $appointment) {
            if (!can_manage_provider((int) $appointment['id_users_provider'])) {
                continue;
            }

            $service = $this->services_model->find($appointment['id_services']);
            $customer = $this->customers_model->find($appointment['id_users_customer']);
            $provider = $this->providers_model->find($appointment['id_users_provider']);

            $rows[] = [
                'id' => $appointment['id'],
                'service' => $service['name'] ?? '',
                'provider' => trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')),
                'customer' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                'customer_email' => $customer['email'] ?? '',
                'start' => $appointment['start_datetime'],
                'end' => $appointment['end_datetime'],
                'notes' => $appointment['notes'] ?? '',
            ];
        }

        usort($rows, fn($a, $b) => strcmp($a['start'], $b['start']));

        html_vars([
            'page_title' => 'Meeting requests',
            'active_menu' => PRIV_APPOINTMENTS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'requests' => $rows,
            'highlight_id' => (int) request('id', 0),
            'done' => (string) request('done', ''),
        ]);

        $this->load->view('pages/appointment_approvals');
    }

    public function approve(): void
    {
        method('post');

        [$appointment, $service, $provider, $customer] = $this->load_pending();

        $options = json_decode(setting('appointment_status_options', '[]'), true) ?: [];

        $status = in_array('Confirmed', $options, true) ? 'Confirmed' : ($options[0] ?? 'Confirmed');

        $appointment['status'] = $status;

        $this->appointments_model->save($appointment);

        $appointment = $this->appointments_model->find($appointment['id']);

        $settings = $this->mail_settings();

        // Now the meeting is real: sync it and send the normal confirmation emails.
        $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);

        $this->notifications->notify_appointment_saved($appointment, $service, $provider, $customer, $settings, false);

        $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);

        redirect('appointment_approvals?done=approved');
    }

    public function decline(): void
    {
        method('post');

        [$appointment, $service, $provider, $customer] = $this->load_pending();

        $reason = trim((string) request('reason', ''));

        $this->appointments_model->delete($appointment['id']);

        $this->notifications->notify_approval_declined(
            $appointment,
            $service,
            $provider,
            $customer,
            $this->mail_settings(),
            $reason,
        );

        $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_DELETE, $appointment);

        redirect('appointment_approvals?done=declined');
    }

    /**
     * Load the pending appointment of the request and verify the user may handle it.
     */
    private function load_pending(): array
    {
        if (cannot('edit', PRIV_APPOINTMENTS)) {
            abort(403, 'Forbidden');
        }

        $id = filter_var(request('id'), FILTER_VALIDATE_INT);

        if (!$id) {
            abort(400, 'Invalid request');
        }

        $appointment = $this->appointments_model->find($id);

        if (($appointment['status'] ?? '') !== self::STATUS_PENDING) {
            abort(409, 'This request has already been handled.');
        }

        if (!can_manage_provider((int) $appointment['id_users_provider'])) {
            abort(403, 'Forbidden');
        }

        return [
            $appointment,
            $this->services_model->find($appointment['id_services']),
            $this->providers_model->find($appointment['id_users_provider']),
            $this->customers_model->find($appointment['id_users_customer']),
        ];
    }

    private function mail_settings(): array
    {
        $company_color = setting('company_color');

        return [
            'company_name' => setting('company_name'),
            'company_link' => setting('company_link'),
            'company_email' => setting('company_email'),
            'company_color' =>
                !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
        ];
    }
}
