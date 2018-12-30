<?php

namespace common\libs;


use Curl\Curl;

class SolusVM
{
    public $errors;
    private $solus_url;
    private $api_key;
    private $api_id;

    /**
     * SolusVM constructor.
     * @param $solus_url
     * @param $api_key
     * @param $api_id
     */
    public function __construct($solus_url, $api_key, $api_id)
    {
        $this->solus_url = $solus_url;
        $this->api_key = $api_key;
        $this->api_id = $api_id;
    }

    public function list_clients()
    {
        $customPayload = ['action' => 'client-list'];
        return $this->executeRequest($customPayload);
    }

    private function executeRequest($payload = [])
    {
        $curl = new Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $post_data = $this->getAuth();
        $payload = array_merge($post_data, $payload);
        $curl->post($this->solus_url . '/api/admin/command.php', $payload);
        if ($curl->httpStatusCode !== 200) {
            $this->errors = $curl->response;
            return false;
        }
        return json_decode($curl->response, true);
    }

    private function getAuth()
    {
        return [
            'id' => $this->api_id,
            'key' => $this->api_key,
            'rdtype' => 'json',
        ];
    }

    public function add_client($username, $password, $email)
    {
        if (!$this->check_client_exists($username)) {
            $customPayload = [
                'action' => 'client-create',
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'firstname' => $username,
                'lastname' => $username,
            ];
            return $this->executeRequest($customPayload);
        }
        return false;
    }

    public function check_client_exists($username)
    {
        $customPayload = [
            'action' => 'client-checkexists'
        ];
        $r = $this->executeRequest($customPayload);
        return $r['statusmsg'] === 'Client exists';
    }

    public function create_server($username, $hd, $bw, $template = 'ubuntu-16.04-x86_64_network')
    {
        $customPayload = [
            'action' => 'vserver-create',
            'hostname' => $username . '.com',
            'username' => $username,
            'plan' => 'Base',
            'node' => 'localhost',
            'type' => 'openvz',
            'template' => $template,
            'randomipv4' => true,
            'ips' => 1,
//            'custommemory' => $ram,
            'customdiskspace' => $hd,
            'custombandwidth' => $bw,
        ];
        return $this->executeRequest($customPayload);
    }

    public function get_node_info($node_id)
    {
        $customPayload = [
            'action' => 'node-statistics',
            'nodeid' => $node_id
        ];
        return $this->executeRequest($customPayload);
    }

    public function change_server_memory($server_id, $memory)
    {
        $customPayload = [
            'action' => 'vserver-change-memory',
            'vserverid' => $server_id,
            'memory' => "32|$memory",
        ];
        return $this->executeRequest($customPayload);
    }

    public function change_bandwidth($server_id, $bandwidth)
    {
        $customPayload = [
            'action' => 'vserver-bandwidth',
            'vserverid' => $server_id,
            'limit' => $bandwidth,
            'overlimit' => $bandwidth,
        ];
        return $this->executeRequest($customPayload);
    }

    public function change_disk($server_id, $disk_space)
    {
        $customPayload = [
            'action' => 'vserver-change-hdd',
            'vserverid' => $server_id,
            'hdd' => $disk_space,
        ];
        return $this->executeRequest($customPayload);
    }

    public function change_network_speed($server_id, $network_speed)
    {
        $customPayload = [
            'action' => 'vserver-change-nspeed',
            'vserverid' => $server_id,
            'customnspeed' => $network_speed,
        ];
        return $this->executeRequest($customPayload);
    }

    public function get_server_info($server_id)
    {
        $customPayload = [
            'action' => 'vserver-infoall',
            'vserverid' => $server_id,
        ];
        return $this->executeRequest($customPayload);
    }

    public function terminate_server($server_id)
    {
        $customPayload = [
            'action' => 'vserver-terminate',
            'deleteclient' => false,
            'vserverid' => $server_id,
        ];
        return $this->executeRequest($customPayload);
    }

    public function change_root_password($server_id, $root_password)
    {
        $customPayload = [
            'action' => 'vserver-rootpassword',
            'rootpassword' => $root_password,
            'vserverid' => $server_id,
        ];
        return $this->executeRequest($customPayload);
    }

    public function serial_console($server_id, $action = null)
    {
        $customPayload = [
            'action' => 'vserver-console',
            'vserverid' => $server_id,
        ];
        if ($action !== null) {
            $customPayload['access'] = $action;
        }
        return $this->executeRequest($customPayload);
    }

}