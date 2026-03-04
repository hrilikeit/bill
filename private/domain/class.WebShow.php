<?php

require '../private/tools/NetroMediaWebCam.php';

final class WebShow extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line; // Channel name
    public $sort = parent::Int;

    public $Entertainer = parent::AssignOne;
    public $Fan = parent::AssignOne;
	public $Show_Schedule = parent::AssignOne;

	public $start_time = parent::Time;
	public $running = parent::Boolean;
	public $username = parent::Line;
	public $password = parent::Line;
    public $metadata = parent::Text;
    public $channel_id = parent::Line;
    public $primary_dns = parent::Line;
    public $channel_price = parent::Currency;
    public $last_poll_time = parent::Time;
    
    // Properties for Fan web cam
    public $fancam_username = parent::Line;
    public $fancam_password = parent::Line;
    public $fancam_channel_id = parent::Line;
    public $fancam_primary_dns = parent::Line;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';
    private $_WebCam; // Reference to WebCam object
    private $_FanWebCam; // Reference to reverse WebCam object

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);	
        if ($this->name) {
        	$this->_WebCam = new NetroMediaWebCam($this->name, $this->channel_id, $this->primary_dns);

        	if ($this->isPrivate()) {
	        	$this->_FanWebCam = new NetroMediaWebCam("{$this->name}FAN", $this->fancam_channel_id, $this->fancam_primary_dns);
	        }
        }
    }
    
    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}
    
    
    public static function getMetadataTypes()
    {
    	return array('full_name', 'username', 'stage_name', 'DOB');
    }
    
    public function setMetadata(array $person_data)
    {
    	$data = Library::getMetadataTypes();
    	
    	$xml = '';
    	for ($i = 0; $i < sizeof($person_data); $i++)
    	{
    		$person = $person_data[$i];
    		$xml .= '<person>';
    		foreach ($data as $type)
    		{
    			if (isset($person[$type])) {
    				$xml .= "<{$type}>{$person[$type]}</{$type}>";
    			}
    		}
    		$xml .= '</person>';
    	}

    	$this->metadata = $xml;
    	$this->save();
    }
    
    public function getMetadata()
    {
    	$data = Library::getMetadataTypes();
    	
    	$metadata = array();
    	$people = explode('</person>', $this->metadata);
    	foreach ($people as $person_xml)
    	{
    		$person = array();
	    	foreach ($data as $type)
    		{
    			if (preg_match("/<{$type}>(.+)<\/{$type}>/", $person_xml, $m)) {
    				$person[$type] = $m[1];
    			}
    		}
    		$metadata[] = $person;
    	}
    	return $metadata;
    }
    
    public function getMetadataHTML()
    {
    	$html = '';
    	$metadata = $this->getMetadata();
    	
    	foreach ($metadata as $person)
    	{
    		$html .= '<p>';
    		foreach ($person as $key => $value)
    		{
    			$key = ucwords(str_replace('_', ' ', $key));
    			$html .= "<strong>{$key}</strong> : {$value}<br/>";
    		}
    		$html .= '</p>';
    	}
    	return $html;
    }
    
    public function createChannelName($identifier)
    {
    	$identifier = preg_replace('[!A-Za-z0-9]', '', $identifier);
    	$channel_name = uniqid($identifier);
    	return $channel_name;
    }
    
    public function start()
    {
    	$this->start_time = StaysailIO::now();
    	$this->running = 1;
    	$this->save();
//    	$this->_WebCam = new NetroMediaWebCam($this->name);
//    	$info = $this->_WebCam->create();
//    	if (isset($info['User Name']) and isset($info['Password'])) {
//	    	$this->username = $info['User Name'];
//    		$this->password = $info['Password'];
//    		$this->channel_id = $info['Channel Id'];
//    		$this->primary_dns = $info['Primary DNS'];
//    		$this->save();
//    		$this->_WebCam->setChannelID($this->channel_id);
//    		$this->_WebCam->setPrimaryDNS($this->primary_dns);
//
//    		if ($this->isPrivate()) {
//    			$this->_FanWebCam = new NetroMediaWebCam("{$this->name}FAN");
//    			$faninfo = $this->_FanWebCam->create();
//    			if (isset($faninfo['User Name']) and isset($faninfo['Password'])) {
//    				$this->fancam_username = $faninfo['User Name'];
//    				$this->fancam_password = $faninfo['Password'];
//    				$this->fancam_channel_id = $faninfo['Channel Id'];
//    				$this->fancam_primary_dns = $faninfo['Primary DNS'];
//    				$this->save();
//    				$this->_FanWebCam->setChannelID($this->fancam_channel_id);
//    				$this->_FanWebCam->setPrimaryDNS($this->fancam_primary_dns);
//    			}
//    		}
//
//    		return true;
//    	} else {
//    		return false;
//    	}
    }
    
    public function end()
    {
//    	$this->_WebCam = new NetroMediaWebCam($this->name, $this->channel_id, $this->primary_dns);
//    	$this->_WebCam->stop();
//
//    	if ($this->isPrivate()) {
//    		$this->_FanWebCam = new NetroMediaWebCam("{$this->name}FAN", $this->fancam_channel_id, $this->fancam_primary_dns);
//    		$this->_FanWebCam->stop();
//    	}

       $this->last_poll_time = date('Y-m-d H:i:s');
    	 $this->running = 0;
        $this->save();
    }
    
    public function getStatus()
    {
    	$this->_WebCam = new NetroMediaWebCam($this->name, $this->channel_id, $this->primary_dns);
    	return $this->_WebCam->getStatus();
    }
    
    public function getRunningStatus()
    {
    	$this->_WebCam = new NetroMediaWebCam($this->name, $this->channel_id, $this->primary_dns);
    	return $this->_WebCam->getRunningStatus();
    }
    
    public function getWebCam()
    {
    	return $this->_WebCam;
    }
    
    public function getFanWebCam()
    {
    	return $this->_FanWebCam;
    }
    
    public function isPrivate()
    {
    	return ($this->Fan ? true : false);
    }
    
    public function getWatchers() 
    {
    	$watchers = array();
    	$sql = "SELECT Fan_WebShow_Status.Fan_id, Fan.name, Fan_WebShow_Status.start_time, Fan_WebShow_Status.last_poll_time,
    			    Fan_WebShow_Status.minutes_purchased, Fan_WebShow_Status.polls
    			FROM `Fan_WebShow_Status`
    			INNER JOIN `Fan`
    			    ON Fan.id = Fan_WebShow_Status.Fan_id
    			WHERE WebShow_id = {$this->id}
    			ORDER BY Fan_WebShow_Status.id";
    	$this->_framework->query($sql);
    	while ($row = $this->_framework->getNextRow())
    	{
    		// Using the fan id as the array key to allow only a single show for each participant
    		$watchers[$row['Fan_id']] = $row;
    	}
    	return $watchers;
    }
    
    public function advancePoll()
    {
    	$this->last_poll_time = date('Y-m-d H:i:s');
    	$this->save();
    }


    public function compareTime($channelId)
    {
        if ($this->last_poll_time !== null && (strtotime($this->last_poll_time)+10 < strtotime(StaysailIO::now()) )) {
            $sql = "UPDATE `WebShow`
                SET `running` = 0
                WHERE `channel_id` = '{$channelId}'";
        } else{
            $sql = "UPDATE `WebShow`
                SET `running` = 1
                WHERE `channel_id` = '{$channelId}'";
        }

        $this->_framework->query($sql);

    }

    public function updatePollTime($channelId)
    {
        $lastPollTime = $this->last_poll_time = date('Y-m-d H:i:s');

        $sql = "UPDATE `WebShow`
            SET `last_poll_time` = '{$lastPollTime}'
            WHERE `channel_id` = '{$channelId}'";

        $this->_framework->query($sql);
    }

}