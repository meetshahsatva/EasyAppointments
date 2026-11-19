<?php defined('BASEPATH') or exit('No direct script access allowed');

// Add custom values by settings them to the $config array.
// Example: $config['smtp_host'] = 'smtp.gmail.com';
// @link https://codeigniter.com/user_guide/libraries/email.html

$config['useragent'] = 'Easy!Appointments';
$config['protocol'] = 'smtp';
$config['mailtype'] = 'html';
$config['smtp_debug'] = '0';
$config['smtp_auth'] = TRUE;
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_user'] = 'meet@satvasolutions.com';
$config['smtp_pass'] = 'dcspnsammaesdcom';
$config['smtp_crypto'] = 'tls';
$config['smtp_port'] = 587;
$config['from_name'] = 'Satva Solutions';
$config['from_address'] = 'meet@satvasolutions.com';
$config['reply_to'] = 'meet@satvasolutions.com';
$config['crlf'] = "\r\n";
$config['newline'] = "\r\n";
