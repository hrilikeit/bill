<?php
/**
 * @package Staysail
 */

/**
 * A StaysailTable is a tool for creating and populating HTML tables.
 * 
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 */
class StaysailTable
{
	private $head, $body, $table_class, $html;
	private $row_classes, $column_classes;
	private $alt_row;
	
	/**
	 * Construct a StaysailTable.
	 * 
	 * @param string $table_class
	 */
	public function __construct($table_class = '')
	{
		$this->html = '';
		$this->table_class = $table_class;
		$this->column_classes = array();
		$this->alt_row = '';
	}
	
	/**
	 * Set the DOM classes for rows in the table, from
	 * the leftmost, and toward the right.
	 * 
	 * @param array $classes
	 */
	public function setColumnClasses(array $classes)
	{
		$i = 0;
		foreach ($classes as $class)
		{
			$this->column_classes[$i++] = $class;
		}
		return $this;
	}
	
	/**
	 * Return the DOM class HTML attribute class="something" for the row at
	 * the specified index.
	 * 
	 * @param int $i
	 * @return string
	 */
	private function getCellClass($i)
	{
		$class = '';
		if (is_array($this->row_classes) and isset($this->row_classes[$i])) {
			$class .= " {$this->row_classes[$i]} ";
		}
		if (!is_array($this->row_classes) and $this->row_classes) {
			$class .= " {$this->row_classes}";
		}
		if (isset($this->column_classes[$i])) {
			$class .= " {$this->column_classes[$i]} ";
		}
		if ($class) {return " class=\"{$class}\"";}
		return '';
	}
		
	/**
	 * Generates the HTML for the <thead> element, given an
	 * array of table headers.
	 * 
	 * Headers are applied from the leftmost column, toward the right.
	 * 
	 * @param array $headers
	 */
	public function setColumnHeaders(array $headers)
	{
		$this->row_classes = null;
		$this->head = "<thead>\n<tr>\n";
		$i = 0;
		foreach ($headers as $header)
		{
			$class = $this->getCellClass($i++);
			$this->head .= "<th{$class}>{$header}</th>";
		}
		$this->head .= "</tr>\n</thead>\n";
		return $this;
	}
	
	/**
	 * Add an row to the HTML table.
	 * 
	 * Table data is added from the leftmost column toward the right.
	 * 
	 * @param array $row
	 * @param array $row_classes
	 */
	public function addRow(array $row, $row_classes = null, $dataAttr = '')
	{
		$this->row_classes = $row_classes;
		$this->body .= "<tr{$this->alt_row} {$dataAttr}>";
		$i = 0;
		foreach ($row as $column_data)
		{
			$class = $this->getCellClass($i++);
			$this->body .= "<td{$class}>{$column_data}</td>";
		}
		$this->body .= "</tr>\n";
		$this->alt_row = $this->alt_row ? '' : ' class="alt_row"';
		return $this;
	}
	
	/**
	 * Return the table's browser-ready output string.
	 * 
	 * @return string
	 */
	public function getHTML()
	{
		$html = "<table class=\"{$this->table_class}\">\n";
		$html .= $this->head;
	 	if ($this->body) {
	 		$html .= "<tbody>{$this->body}</tbody>";
	 	}
	 	$html .= "</table>\n";
		return $html;
	}
}