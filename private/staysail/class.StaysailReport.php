<?php
/**
 * @package Staysail
 */

/**
 * A StaysailReport is a base class for classes that create and output reports
 * 
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 */
abstract class StaysailReport
{
	protected $_framework;                   // Reference to Staysail Framework
	protected $sql;
	protected $parameters;
	protected $subsummaries;
	
	public function __construct()
	{
		$this->_framework = StaysailIO::engage();
		$this->sql = '';
		$this->parameters = array();
		$this->subsummaries = array();
	}
	
	abstract function getHTML();
	abstract function getCSV();
	abstract function getName();
	
	public function setSQL($sql) {$this->sql = $sql;}
	public function setParameters($parameters) {$this->parameters = $parameters;}
	public function setSubsummaries($subsummaries) {$this->subsummaries = $subsummaries;}

	public function runReportAsHTML()
	{
		$html = '';
		$sql = $this->getSQLwithParameters();		
		$this->_framework->query($sql);
		
		$sub_current = array();
		$table = ''; // the current table
		while ($row = $this->_framework->getNextRow())
		{
			if (sizeof($this->subsummaries)) {
				// Is the list of subsummary fields different?  If so, at which subsummary level
				// does the difference begin?
				$differs_on = null;
				for ($i = 0; $i < sizeof($this->subsummaries); $i++)
				{
					$fieldname = $this->subsummaries[$i];
					if (!isset($sub_current[$fieldname]) or $row[$fieldname] != $sub_current[$fieldname]) {
						$differs_on = $i;
						break;
					}
				}
				if ($differs_on !== null) {
					for ($i = $differs_on; $i < sizeof($this->subsummaries); $i++)
					{
						$fieldname = $this->subsummaries[$i];					
						$h = $i + 1;          // HTML header level is 1-indexed,
						if ($h > 6) {$h = 6;} // and maxes out at 6
						if ($table) {
							$table .= "</tbody></table>\n"; // end the current table
							$html .= $table; // append it to the HTML
						}
						$table = ''; // reset the current table to start a new one
						$html .= "<h{$h}>{$row[$fieldname]}</h{$h}>\n";
					}
				}
				$sub_current = $row;
			}
			
			if (!$table) {
				$table = "<table class=\"StaysailReport\">\n";
				$table .= $this->getHeader($row);
				$table .= "<tbody>\n";
			}
			
			$table .= "<tr>";
			foreach ($row as $fieldname => $value)
			{
				if (in_array($fieldname, $this->subsummaries)) {continue;}
				
				StaysailIO::cleanse($value, StaysailIO::HTML);
				$table .= "<td>{$value}</td>";
			}
			$table .= "</tr>\n";
		}
		if ($table) {
			$html .= $table;
			$html .= "</tbody></table>";
		}
		
		return $html;
	}
	
	public function runReportAsCSV($header = true)
	{
		$csv = $csv_header = '';
		$sql = $this->getSQLwithParameters();		
		$this->_framework->query($sql);
		
		while ($row = $this->_framework->getNextRow())
		{
            if ($csv_header === '') {
                $rowKeys = array_keys($row);
                $csv_header = implode(',', $rowKeys);
            }
			foreach ($row as $fieldname => $value)
			{
//				if ($header) {
//					$csv_header .= "\"{$fieldname}\",";
//				}
				$value = str_replace('"', '\"', $value);
				$csv .= "\"{$value}\",";
			}
			$csv = trim($csv, ',') . "\n";
		}
		
//		if ($header) {
//			$csv_header = trim($csv_header, ',') . "\n";
//		}

        $csv_header = $csv_header . "\n";
		return "{$csv_header}{$csv}";
	}
	
	public function getSQLwithParameters()
	{
		$sql = $this->sql;
		
		foreach ($this->parameters as $key => $value)
		{
			$sql = str_replace("/%{$key}%/", $value, $sql);
		}
		
		return $sql;
	}
	
	private function getHeader($row)
	{
		$html = "<thead><tr>";
		foreach ($row as $fieldname => $value)
		{
			if (in_array($fieldname, $this->subsummaries)) {continue;}
			
			StaysailIO::cleanse($fieldname, StaysailIO::HTML);
			$title = ucwords(str_replace('_', ' ', $fieldname));
			$html .= "<th>{$title}</th>";
		}
		$html .= "</tr></thead>\n\n";
		//echo '<pre>' ; print_r($row); die;
		return $html;
	}
}