<?php

define('WEBCAM_API_TOKEN', '7bc6e4c0-73e5-4b3b-9c7c-af2de0a62ba5');
define('WEBCAM_URL', 'https://login.netromedia.com/rest.svc/');

class WebCam
{
	private $channel_name;
	private $width, $height;
	const FANCAM = 1;
	
	public function __construct($channel_name)
	{
		$this->channel_name = $channel_name;
		$this->width = 360;
		$this->height = 350;
	}
	
	public function setSize($width, $height)
	{
		$this->width = $width;
		$this->height = $height;
	}
	
	public function getPublisher($fancam = false)
	{
		$fan = $fancam ? '_fan' : '';
		
		$html = <<<__END__
			<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="{$this->width}" height="{$this->height}" id="publisher{$fan}" align="middle">
			<param name="allowScriptAccess" value="sameDomain" />
			<param name="allowFullScreen" value="false" />
			<param name="movie" value="/flash/nspublisher.swf" />
			<param name="quality" value="high" />
			<param name="bgcolor" value="#ffffff" />	
			<param name="FlashVars" value="playerFMSUrl=rtmp://154.obj.netromedia.net/{$this->channel_name}/_definst_/username=N9jBR9gY/password=x4Zrzzbm/&playerStream={th}" />
			<embed src="https://login.netromedia.com/flash/nspublisher.swf" FlashVars="playerFMSUrl=rtmp://154.obj.netromedia.net/{$this->channel_name}/_definst_/username=N9jBR9gY/password=x4Zrzzbm/&playerStream={$this->channel_name}" quality="high" bgcolor="#ffffff" width="360" height="350" name="publisher" align="middle" allowScriptAccess="sameDomain" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
			</object>
__END__;
		return $html;
	}
	
	public function getViewer($fancam = false)
	{
		$fan = $fancam ? '_fan' : '';
		
		$html = <<<__END__
			<object width="{$this->width}" height="{$this->height}" id="_fms{$fan}" name="_fms{$fan}" 
			data="http://player.netromedia.com/flowplayer.commercial-3.2.7.swf" 
			type="application/x-shockwave-flash">
			<param name="movie" value="http://player.netromedia.com/flowplayer.commercial-3.2.7.swf" />
			<param name="allowfullscreen" value="true" />
			<param name="allowscriptaccess" value="always" />
			<param name="flashvars" 
			    value='config={"key":"#@e334c866df3eabb2176",
			    			   "clip":{"autoPlay":true,"autoBuffering":true,"debug":true,"live":true,"scaling":"fit","url":"{$this->channel_name}","wmode":"transparent","provider":"netromedia","metaData":false},
			    			   "plugins":{"controls":{"autoHide":true,"fullscreen":true},"netromedia":{"url":"flowplayer.rtmp-3.2.3.swf","netConnectionUrl":"rtmp://154.obj.netromedia.net/{$this->channel_name}"}},
			    			   "playlist":[{"autoPlay":true,"autoBuffering":true,"debug":true,"live":true,"scaling":"fit","url":"{$this->channel_name}","wmode":"transparent","provider":"netromedia","metaData":false}]}' /></object>		
__END__;
		return $html;
	}
	
	public function create()
	{
		$parameters = array(
			'ChannelName' => $this->channel_name,
			'ChannelFormat' => 'FLASH_LIVE',
		);
		
		//print "Output" . $this->sendRequest('CreateChannel', $parameters);
	}
	
	private function sendRequest($method, $parameters)
	{
		$parameters['Token'] = WEBCAM_API_TOKEN;
		$json = $this->toJSON($parameters);
		$url = WEBCAM_URL . $method;
		$curl = curl_init();
		curl_setopt_array($curl, array(
		    CURLOPT_RETURNTRANSFER => 1,
		    CURLOPT_URL => $url,
		    CURLOPT_HTTPHEADER => array('Content-type: application/json; charset=utf-8'),
    		CURLOPT_POST => 1,
		    CURLOPT_POSTFIELDS => $json,	    
		));		
		$res = curl_exec($curl);
		return $res;
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