<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

define('WEBCAM_API_TOKEN', 'd27cf0d4-30c1-492c-840b-c5e8f1c0b065');
define('WEBCAM_URL', 'https://login.netromedia.com/rest.svc/');
define('WEBCAM_DIAGNOSTIC', false);

class NetroMediaWebCam
{
	private $channel_name;
	private $channel_id;
	private $primary_dns;
	private $width, $height;
	private $username, $password;
	
	public function __construct($channel_name, $channel_id = null, $primary_dns = null)
	{
		$this->channel_name = preg_replace('/[^A-Za-z0-9]/', '', $channel_name);
		$this->channel_id = $channel_id;
		$this->primary_dns = $primary_dns;
		$this->width = 360;
		$this->height = 350;
	}
	
	public function setAuth($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}
	
	public function setSize($width, $height)
	{
		$this->width = $width;
		$this->height = $height;
	}
	
	public function setChannelID($channel_id) {$this->channel_id = $channel_id;}
	
	public function setPrimaryDNS($primary_dns) {$this->primary_dns = $primary_dns;}
	
	public function getPublisher()
	{		
		$html = <<<__END__
  <video
    id="my-video"
    class="video-js"
    controls
    preload="auto"
    width="{$this->width}"
    height="{$this->height}"
    poster="MY_VIDEO_POSTER.jpg"
    data-setup="{}"
  >
    <source src="rtmp://{$this->primary_dns}/{$this->channel_name}" type="video/webm" />
    <p class="vjs-no-js">
      To view this video please enable JavaScript, and consider upgrading to a
      web browser that
      <a href="https://videojs.com/html5-video-support/" target="_blank"
        >supports HTML5 video</a
      >
    </p>
  </video>

  <script src="https://vjs.zencdn.net/7.8.4/video.js"></script>

__END__;
		return $html;
	}
	
	public function getViewer()
	{
		$html = <<<__END__
  <video
    id="my-video"
    class="video-js"
    controls
    preload="auto"
    width="{$this->width}"
    height="{$this->height}"
    poster="MY_VIDEO_POSTER.jpg"
    data-setup="{}"
  >
    <source src="rtmp://{$this->primary_dns}/{$this->channel_name}" type="video/webm" />
    <p class="vjs-no-js">
      To view this video please enable JavaScript, and consider upgrading to a
      web browser that
      <a href="https://videojs.com/html5-video-support/" target="_blank"
        >supports HTML5 video</a
      >
    </p>
  </video>

  <script src="https://vjs.zencdn.net/7.8.4/video.js"></script>

__END__;
		
		return $html;
	}
	
	public function create()
	{
		$parameters = array(
			'ChannelName' => $this->channel_name,
			'ChannelFormat' => 'FLASH_LIVE',
		);
		
		$response = $this->sendRequest('CreateChannel', $parameters);
//print "<!--";
//print_r($response);
//print "-->";
		$info = $this->parseResponse($response);
		return $info;
	}
	
	public function stop()
	{
		$parameters = array(
			'ChannelId' => $this->channel_id,
		);
		
		$this->sendRequest('DeleteChannel', $parameters);
	}
	
	public function getStatus()
	{
		$parameters = array(
			'ChannelId' => $this->channel_id,
		);
		
		$response = $this->sendRequest('GetChannelStats', $parameters);
  		$info = $this->parseResponse($response);
		return (isset($info['Status']) and $info['Status'] == 'Online');
	}
	
	public function getRunningStatus()
	{
		$parameters = array();
		$response = $this->sendRequest('GetAllChannelRunningStatuses', $parameters);
		$info = $this->parseResponse($response);
		print_r($response);
	}
	
	private function sendRequest($method, $parameters)
	{
		$parameters['Token'] = WEBCAM_API_TOKEN;
		$json = $this->toJSON($parameters);
		$url = WEBCAM_URL . $method;
		$curl = curl_init();
		curl_setopt_array($curl, array(
CURLOPT_SSL_VERIFYPEER => false,
		    CURLOPT_RETURNTRANSFER => 1,
		    CURLOPT_URL => $url,
		    CURLOPT_HTTPHEADER => array('Content-type: application/json; charset=utf-8'),
    		CURLOPT_POST => 1,
		    CURLOPT_POSTFIELDS => $json,	    
		));
		$res = curl_exec($curl);
		return $res;
	}
	
	private function parseResponse($txt)
	{
		$params = array();
		if (preg_match('/"Data":{(.*)/', $txt, $m)) {
			$data = $m[1];
			$kv = explode(',', $data);
			foreach ($kv as $pair)
			{
				if (preg_match('/"(.*?)":"(.*?)"/', $pair, $m)) {
					$params[$m[1]] = $m[2];
				}
			}
		}
		if (WEBCAM_DIAGNOSTIC) {print_r($params);}
		return $params;
	}
	
	private function toJSON($parameters)
	{
		$req = '{';
		foreach ($parameters as $key => $value)
		{
			$req .= "\"{$key}\":\"{$value}\",";
		}
		$req = trim($req, ',') . '}';
		return $req;
	}
}
