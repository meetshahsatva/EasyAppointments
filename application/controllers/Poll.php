<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Poll controller (public voting page, no login).
 */
class Poll extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('providers_model');
        $this->load->model('services_model');
    }

    /**
     * Show the poll: /poll/of/{hash}
     */
    public function of(): void
    {
        method('get');

        $poll = $this->find_poll((string) $this->uri->segment(3));

        $this->render($poll, (string) request('done', ''));
    }

    /**
     * Save the votes of a participant.
     */
    public function vote(): void
    {
        method('post');

        $poll = $this->find_poll((string) request('hash'));

        if ($poll['status'] !== 'open') {
            $this->render($poll, 'closed');

            return;
        }

        $first_name = trim((string) request('first_name'));
        $last_name = trim((string) request('last_name', ''));
        $email = strtolower(trim((string) request('email')));
        $chosen = array_map('intval', (array) (request('options') ?: []));

        if ($first_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$chosen) {
            $this->render($poll, 'invalid');

            return;
        }

        $valid_ids = array_column(
            $this->db->select('id')->get_where('poll_options', ['id_polls' => $poll['id']])->result_array(),
            'id',
        );

        $chosen = array_values(array_intersect($chosen, array_map('intval', $valid_ids)));

        if (!$chosen) {
            $this->render($poll, 'invalid');

            return;
        }

        // A participant can vote again with the same email: the previous votes are replaced.
        $this->db->delete('poll_votes', ['id_polls' => $poll['id'], 'email' => $email]);

        foreach ($chosen as $option_id) {
            $this->db->insert('poll_votes', [
                'id_polls' => $poll['id'],
                'id_poll_options' => $option_id,
                'first_name' => mb_substr($first_name, 0, 120),
                'last_name' => mb_substr($last_name, 0, 120),
                'email' => mb_substr($email, 0, 250),
                'create_datetime' => date('Y-m-d H:i:s'),
            ]);
        }

        redirect('poll/of/' . $poll['hash'] . '?done=voted');
    }

    private function find_poll(string $hash): array
    {
        $poll = $this->db->get_where('polls', ['hash' => preg_replace('/[^A-Za-z0-9]/', '', $hash)])->row_array();

        if (!$poll) {
            abort(404, 'This poll does not exist.');
        }

        return $poll;
    }

    private function render(array $poll, string $notice): void
    {
        $provider = $this->providers_model->find($poll['id_users_provider']);
        $service = $this->services_model->find($poll['id_services']);

        $options = $this->db->order_by('start_datetime')->get_where('poll_options', ['id_polls' => $poll['id']])->result_array();

        foreach ($options as &$option) {
            $option['count'] = (int) $this->db
                ->select('COUNT(*) AS c')
                ->get_where('poll_votes', ['id_poll_options' => $option['id']])
                ->row()->c;
        }

        html_vars([
            'page_title' => $poll['title'],
            'company_color' => setting('company_color'),
            'poll' => $poll,
            'options' => $options,
            'provider_name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
            'provider_timezone' => $provider['timezone'],
            'service_name' => $service['name'] ?? '',
            'duration' => $service['duration'] ?? '',
            'notice' => $notice,
        ]);

        $this->load->view('pages/poll_public');
    }
}
