<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Polls controller (backend).
 *
 * Providers, admins and assigned secretaries create group meeting polls, see the votes and book the winning time.
 */
class Polls extends EA_Controller
{
    const MAX_OPTIONS = 20;
    const MAX_INVITEES = 40;
    const MAX_MONTHS_AHEAD = 3;

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
        $this->load->library('email_messages');
    }

    /**
     * Poll list and the creation form.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('polls')]);

        $this->require_login();

        $providers = [];
        $provider_services = [];

        foreach ($this->providers_model->get() as $provider) {
            if (!can_manage_provider((int) $provider['id'])) {
                continue;
            }

            $providers[] = [
                'id' => $provider['id'],
                'name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
            ];

            $provider_services[$provider['id']] = array_map('intval', $provider['services'] ?? []);
        }

        $services = [];

        foreach ($this->services_model->get() as $service) {
            $services[] = ['id' => $service['id'], 'name' => $service['name'], 'duration' => $service['duration']];
        }

        $polls = [];

        foreach ($this->db->order_by('id', 'DESC')->get('polls')->result_array() as $poll) {
            if (!can_manage_provider((int) $poll['id_users_provider'])) {
                continue;
            }

            $poll['votes'] = (int) $this->db
                ->select('COUNT(DISTINCT email) AS c')
                ->get_where('poll_votes', ['id_polls' => $poll['id']])
                ->row()->c;

            $polls[] = $poll;
        }

        html_vars([
            'page_title' => 'Polls',
            'active_menu' => 'polls',
            'user_display_name' => $this->accounts->get_user_display_name(session('user_id')),
            'providers' => $providers,
            'provider_services' => $provider_services,
            'services' => $services,
            'polls' => $polls,
            'flash' => (string) request('done', ''),
            'error' => (string) request('error', ''),
        ]);

        $this->load->view('pages/polls');
    }

    /**
     * Create a poll.
     */
    public function create(): void
    {
        method('post');

        $this->require_login();

        try {
            $title = trim((string) request('title'));
            $description = trim((string) request('description', ''));
            $provider_id = (int) request('id_users_provider');
            $service_id = (int) request('id_services');
            $times = request('options') ?: [];
            $invitees_raw = (string) request('invitees', '');

            if ($title === '' || !$provider_id || !$service_id) {
                throw new InvalidArgumentException('Title, provider and service are required.');
            }

            if (!can_manage_provider($provider_id)) {
                abort(403, 'Forbidden');
            }

            $provider = $this->providers_model->find($provider_id);

            if (!in_array($service_id, array_map('intval', $provider['services'] ?? []), true)) {
                throw new InvalidArgumentException('The provider does not offer this service.');
            }

            $options = [];

            // Allowed window: from now until 3 months ahead, in the host's time zone.
            $timezone = new DateTimeZone($provider['timezone'] ?: date_default_timezone_get());
            $now = new DateTime('now', $timezone);
            $latest = (clone $now)->modify('+' . self::MAX_MONTHS_AHEAD . ' months');

            foreach ((array) $times as $time) {
                $time = str_replace('T', ' ', trim((string) $time));

                if ($time === '') {
                    continue;
                }

                $time = date('Y-m-d H:i:00', strtotime($time));

                if (!validate_datetime($time)) {
                    throw new InvalidArgumentException('Invalid date/time option.');
                }

                $option_time = new DateTime($time, $timezone);

                if ($option_time <= $now) {
                    throw new InvalidArgumentException('Time options must be in the future: ' . $time);
                }

                if ($option_time > $latest) {
                    throw new InvalidArgumentException(
                        'Time options can be at most ' . self::MAX_MONTHS_AHEAD . ' months ahead: ' . $time,
                    );
                }

                $options[$time] = $time;
            }

            if (count($options) < 2) {
                throw new InvalidArgumentException('Add at least two time options.');
            }

            if (count($options) > self::MAX_OPTIONS) {
                throw new InvalidArgumentException('Too many time options (max ' . self::MAX_OPTIONS . ').');
            }

            ksort($options);

            $hash = random_string('alnum', 16);

            $this->db->insert('polls', [
                'hash' => $hash,
                'title' => mb_substr($title, 0, 250),
                'description' => $description,
                'id_users_provider' => $provider_id,
                'id_services' => $service_id,
                'id_users_creator' => (int) session('user_id'),
                'status' => 'open',
                'create_datetime' => date('Y-m-d H:i:s'),
            ]);

            $poll_id = (int) $this->db->insert_id();

            foreach ($options as $time) {
                $this->db->insert('poll_options', ['id_polls' => $poll_id, 'start_datetime' => $time]);
            }

            $sent = $this->send_invites($hash, $title, $description, $provider, $invitees_raw);

            redirect('polls/results/' . $poll_id . '?done=created&sent=' . $sent);
        } catch (Throwable $e) {
            redirect('polls?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Results of a poll, with the option to book the winning time.
     */
    public function results(): void
    {
        method('get');

        $this->require_login();

        $poll = $this->load_poll((int) $this->uri->segment(3));

        $options = $this->db->order_by('start_datetime')->get_where('poll_options', ['id_polls' => $poll['id']])->result_array();

        $votes = $this->db->get_where('poll_votes', ['id_polls' => $poll['id']])->result_array();

        foreach ($options as &$option) {
            $option['voters'] = [];

            foreach ($votes as $vote) {
                if ((int) $vote['id_poll_options'] === (int) $option['id']) {
                    $option['voters'][] = trim($vote['first_name'] . ' ' . $vote['last_name']) . ' <' . $vote['email'] . '>';
                }
            }
        }

        $provider = $this->providers_model->find($poll['id_users_provider']);
        $service = $this->services_model->find($poll['id_services']);

        html_vars([
            'page_title' => 'Poll results',
            'active_menu' => 'polls',
            'user_display_name' => $this->accounts->get_user_display_name(session('user_id')),
            'poll' => $poll,
            'options' => $options,
            'provider_name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
            'provider_timezone' => $provider['timezone'],
            'service_name' => $service['name'] ?? '',
            'poll_link' => trusted_site_url('poll/of/' . $poll['hash']),
            'flash' => (string) request('done', ''),
            'sent' => (int) request('sent', 0),
            'booked' => (int) request('booked', 0),
            'error' => (string) request('error', ''),
        ]);

        $this->load->view('pages/poll_results');
    }

    /**
     * Book the chosen option: one appointment per voter who picked it.
     */
    public function finalize(): void
    {
        method('post');

        $this->require_login();

        $poll = $this->load_poll((int) request('poll_id'));

        if ($poll['status'] !== 'open') {
            abort(409, 'This poll is already closed.');
        }

        $option = $this->db->get_where('poll_options', ['id' => (int) request('option_id'), 'id_polls' => $poll['id']])->row_array();

        if (!$option) {
            abort(400, 'Invalid option.');
        }

        $voters = $this->db->get_where('poll_votes', ['id_polls' => $poll['id'], 'id_poll_options' => $option['id']])->result_array();

        if (!$voters) {
            redirect('polls/results/' . $poll['id'] . '?done=novotes');

            return;
        }

        $provider = $this->providers_model->find($poll['id_users_provider']);
        $service = $this->services_model->find($poll['id_services']);

        $options = json_decode(setting('appointment_status_options', '[]'), true) ?: [];
        $status = in_array('Confirmed', $options, true) ? 'Confirmed' : ($options[0] ?? 'Confirmed');

        $company_color = setting('company_color');

        $settings = [
            'company_name' => setting('company_name'),
            'company_link' => setting('company_link'),
            'company_email' => setting('company_email'),
            'company_color' =>
                !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
        ];

        $booked = 0;
        $failed = [];

        foreach ($voters as $voter) {
            try {
                $customer_id = $this->find_or_create_customer($voter, $provider);
                $customer = $this->customers_model->find($customer_id);

                $appointment = [
                    'start_datetime' => $option['start_datetime'],
                    'id_services' => $service['id'],
                    'id_users_provider' => $provider['id'],
                    'id_users_customer' => $customer_id,
                    'is_unavailability' => false,
                    'status' => $status,
                    'location' => $service['location'] ?? null,
                    'notes' => 'Booked from poll: ' . $poll['title'],
                ];

                if (!empty($service['color'])) {
                    $appointment['color'] = $service['color'];
                }

                $appointment['end_datetime'] = $this->appointments_model->calculate_end_datetime($appointment);

                $appointment_id = $this->appointments_model->save($appointment);
                $appointment = $this->appointments_model->find($appointment_id);

                // Sync and emails must not stop the remaining voters from being booked.
                try {
                    $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);
                } catch (Throwable $e) {
                    log_message('error', 'Poll sync failed: ' . $e->getMessage());
                }

                try {
                    $this->notifications->notify_appointment_saved($appointment, $service, $provider, $customer, $settings, false);
                } catch (Throwable $e) {
                    log_message('error', 'Poll notification failed: ' . $e->getMessage());
                }

                $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);

                $booked++;
            } catch (Throwable $e) {
                log_message('error', 'Poll booking failed for ' . $voter['email'] . ': ' . $e->getMessage());

                $failed[] = $voter['email'] . ' (' . $e->getMessage() . ')';
            }
        }

        if ($booked === 0) {
            redirect('polls/results/' . $poll['id'] . '?done=failed&error=' . urlencode(implode('; ', $failed)));

            return;
        }

        $this->db->update('polls', ['status' => 'closed', 'id_chosen_option' => $option['id']], ['id' => $poll['id']]);

        redirect(
            'polls/results/' . $poll['id'] . '?done=booked&booked=' . $booked .
                ($failed ? '&error=' . urlencode(implode('; ', $failed)) : ''),
        );
    }

    /**
     * Close without booking.
     */
    public function close(): void
    {
        method('post');

        $this->require_login();

        $poll = $this->load_poll((int) request('poll_id'));

        $this->db->update('polls', ['status' => 'closed'], ['id' => $poll['id']]);

        redirect('polls/results/' . $poll['id'] . '?done=closed');
    }

    public function delete(): void
    {
        method('post');

        $this->require_login();

        $poll = $this->load_poll((int) request('poll_id'));

        $this->db->delete('poll_votes', ['id_polls' => $poll['id']]);
        $this->db->delete('poll_options', ['id_polls' => $poll['id']]);
        $this->db->delete('polls', ['id' => $poll['id']]);

        redirect('polls?done=deleted');
    }

    /**
     * Reuse the voter's customer record (untouched) or create a new one.
     *
     * Required booking fields the poll does not ask for (phone, address, ...) get a "-" placeholder, so that the
     * customer validation of the booking settings does not reject the record.
     */
    private function find_or_create_customer(array $voter, array $provider): int
    {
        $lookup = ['email' => $voter['email']];

        if ($this->customers_model->exists($lookup)) {
            return $this->customers_model->find_record_id($lookup);
        }

        $customer = [
            'first_name' => $voter['first_name'] ?: '-',
            'last_name' => $voter['last_name'] ?: '-',
            'email' => $voter['email'],
            'phone_number' => '-',
            'address' => '-',
            'city' => '-',
            'zip_code' => '-',
            'notes' => 'Created from a meeting poll',
            'language' => config('language'),
            'timezone' => $provider['timezone'],
        ];

        return $this->customers_model->save($customer);
    }

    private function require_login(): void
    {
        if (cannot('view', PRIV_APPOINTMENTS)) {
            if (session('user_id')) {
                abort(403, 'Forbidden');
            }

            redirect('login');
            exit();
        }
    }

    private function load_poll(int $poll_id): array
    {
        $poll = $this->db->get_where('polls', ['id' => $poll_id])->row_array();

        if (!$poll) {
            abort(404, 'Poll not found.');
        }

        if (!can_manage_provider((int) $poll['id_users_provider'])) {
            abort(403, 'Forbidden');
        }

        return $poll;
    }

    /**
     * Email the poll link to the listed invitees. Returns the number of emails sent.
     */
    private function send_invites(string $hash, string $title, string $description, array $provider, string $raw): int
    {
        $emails = array_unique(array_filter(array_map('trim', preg_split('/[\s,;]+/', $raw) ?: [])));

        $emails = array_slice($emails, 0, self::MAX_INVITEES);

        $company_color = setting('company_color');

        $settings = [
            'company_name' => setting('company_name'),
            'company_color' =>
                !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
        ];

        $sent = 0;

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                $this->email_messages->send_approval_email(
                    $email,
                    'Please vote: ' . $title,
                    $title,
                    ($description !== '' ? $description . ' ' : '') .
                        'Please pick the times that work for you. The meeting is booked once the host picks a time.',
                    ['Host' => trim($provider['first_name'] . ' ' . $provider['last_name'])],
                    $settings,
                    trusted_site_url('poll/of/' . $hash),
                    'Vote now',
                );

                $sent++;
            } catch (Throwable $e) {
                log_message('error', 'Poll invite failed for ' . $email . ': ' . $e->getMessage());
            }
        }

        return $sent;
    }
}
