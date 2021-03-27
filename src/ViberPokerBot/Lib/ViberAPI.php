<?php


namespace ViberPokerBot\Lib;

require_once 'Logger.php';

class ViberAPI
{
    public function sendMessage(array $data = null): void
    {
        $this->callApi('https://chatapi.viber.com/pa/send_message', $data);
    }

    public function broadcastMessage(array $data = null): void
    {
        $this->callApi('https://chatapi.viber.com/pa/broadcast_message', $data);
    }

    public function getAccountInfo(): object
    {
        return $this->callApi('https://chatapi.viber.com/pa/get_account_info');
    }

    protected function callApi(string $url, array $data = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'X-Viber-Auth-Token: ' . getenv('VIBER_AUTH_TOKEN')]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            Logger::log(': Error Resp: ' . json_encode(curl_getinfo($ch)));
        }

//        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
//        $header = substr($response, 0, $header_size);
//        $body = substr($response, $header_size);

        return json_decode($response);
    }

}