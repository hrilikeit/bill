<?php

class LSFMetadataEntity extends StaysailEntity
{
    public function __construct($class, $id = null)
    {
        parent::__construct($class, $id);		
    }	
	
    public static function getMetadataTypes()
    {
    	return array('full_name', 'username', 'maiden_name', 'stage_name', 'alias', 'DOB');
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
}