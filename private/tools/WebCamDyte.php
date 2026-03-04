<?php

define('WEBCAM_DYTE_USERNAME', 'e48eba8e-02ee-4e3f-ad7f-e1fe4928aa25');
define('WEBCAM_DYTE_PASS', '079d4f32e83442844ca2');
define('WEBCAM_DYTE_URL', 'https://api.cluster.dyte.in/v2/');

class WebCamDyte
{

    public function createMeeting(
        $title = 'Livestream',
        $preferredRegion = 'ap-south-1',
        $recordOnStart = false,
        $liveStreamOnStart = false
    )
    {
        $parameters = array(
            'title' => $title,
            'preferred_region' => $preferredRegion,
            "record_on_start" => $recordOnStart,
            "live_stream_on_start" => $liveStreamOnStart
        );

        return $this->sendRequest('meetings', $parameters);
    }

    public function createParticipant($webShowDyte, $Member)
    {
        $picture = 'https://yourfanslive.com/site_img/FullLogo.png';
        $custom_participant_id = $Member->id;
        $Fan = $Member->getAccountOfType('Fan');
        $name = $Member->name;

        if (!$Fan){
            $Entertainer = $Member->getAccountOfType('Entertainer');
            if (!$name )  {
                $name = $Entertainer->stage_name;
            }
            $preset_name = "livestream_host";
        }
        else{
            if (!$name )  {
                $name = $Member->first_name;
            }
            $preset_name = "livestream_participant";
        }
        
        $parameters = [
            "name" => $name,
            "picture" => $picture,
            "preset_name" => $preset_name,
            "custom_participant_id" => (string)$custom_participant_id
        ];

        $method = 'meetings/' . $webShowDyte->meeting_id . '/participants';

        return $this->sendRequest($method, $parameters);
    }

    public function createParticipantGuest($webShowDyte, $Member)
    {
        $picture = 'https://yourfanslive.com/site_img/FullLogo.png';
        $custom_participant_id = $Member->id;

        $name = 'Guest';
        $preset_name = "livestream_participant";


        $parameters = array(
            "name" => $name,
            "picture" => $picture,
            "preset_name" => $preset_name,
            "custom_participant_id" => (string)$custom_participant_id
        );

        $method = 'meetings/' . $webShowDyte->meeting_id . '/participants';

        return $this->sendRequest($method, $parameters);
    }
    
    public function checkActiveLiveStream($webShowDyte)
    {
        $parameters = array(
            "meeting_id" => $webShowDyte->meeting_id,
            "limit" => 10,
            "offset" =>  1,
        );

        $method = 'meetings/' . $webShowDyte->meeting_id . '/active-livestream';

        return $this->sendRequest($method, $parameters, 'GET');
    }

    private function sendRequest($method, $parameters, $request = 'POST')
    {

        $curl = curl_init();
        $url = WEBCAM_DYTE_URL . $method;
        $json = json_encode($parameters);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $request,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . base64_encode(WEBCAM_DYTE_USERNAME . ":" . WEBCAM_DYTE_PASS),
                "Content-Type: application/json"
            ],
        ]);

        $response = json_decode(curl_exec($curl), true);
        $err = curl_error($curl);
         curl_close($curl);
        
        return !empty($response['data']) ? $response['data'] : [];
    }

    private function toJSON($parameters)
    {
        $req = '{';
        foreach ($parameters as $key => $value) {
            $req .= "\"{$key}\":\"{$value}\",";
        }
        $req = trim($req, ',') . '}';
        return $req;
    }
}
