<?php
/**
 * @package Staysail
 */

/**
 * A StaysailWriter is a tool for generating basic HTML content, and
 * integrating other Staysail HTML tools.
 * 
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 * @see StaysailForm
 * @see StaysailTable
 */
class StaysailWriter
{
	private $class, $html, $div_stack;
	
	/**
	 * Constructs a StaysailWriter.
	 * 
	 * If the optional class parameter is provided, the StaysailWriter's
	 * HTML will be enclosed in a DIV of that class.
	 * 
	 * @see getHTML()
	 * @param unknown_type $class
	 */
	public function __construct($class = '')
	{
		$this->class = $class;
		$this->html = '';
	}
	
	/**
	 * Starts a DIV.
	 * 
	 * Both class and id parameters are HTML attributes for the DIV.
	 * 
	 * @param string $class
	 * @param string $id
	 */
	public function start($class, $id = '')
	{
		$id_html = $id ? " id=\"{$id}\"" : '';
		$this->div_stack[] = $class;
		$this->html .= "<div class=\"{$class}\"{$id_html}>\n";
		return $this;
	}
	
	/**
	 * Ends a started DIV.
	 * 
	 * If the optional class doesn't match the actual DIV being closed,
	 * there will be a warning in the comment after the DIV, to help you
	 * diagnose problems.  But otherwise, nothing is enforced.
	 * 
	 * If there are more ends than starts, this method will put a warning
	 * in an HTML comment instead of ending a DIV.
	 * 
	 * @param string $class
	 */
	public function end($class = '')
	{
	    if ($this->div_stack){
            if (!sizeof($this->div_stack)) {
                $this->comment("end before start (.{$class})");
                return;
            }
            $current = array_pop($this->div_stack);
            if ($class and $class != $current) {
                $current .= " (But specified .{$class})";
            }
            $this->html .= "</div> ";
            $this->comment("end of .{$current}");
        }
		
		return $this;
	}		
	
	/**
	 * Draws an object into the HTML.
	 * 
	 * Staysail objects are drawn using a getHTML() method.  If no getHTML()
	 * method exists for the specified object, this method adds a warning
	 * in the form of an HTML comment.
	 * 
	 * @param Object $drawable
	 */
	public function draw($drawable)
	{
		if (is_object($drawable)) {
			if (method_exists($drawable, 'getHTML')) {
				$this->html .= $drawable->getHTML();
			} else {
				$type = get_class($drawable);
				$this->comment("Not drawable: {$type}");
			}
		} else {
			StaysailIO::cleanse($drawable, StaysailIO::HTML);
			$this->comment("Not an object: {$drawable}");
		}
		return $this;
	}
	
	/**
	 * Returns the StaysailWriter's current HTML.
	 * 
	 * If a class was specified in the constructor, the HTML is wrapped
	 * in a DIV of that class.
	 * 
	 * @return string
	 */
	public function getHTML() 
	{
		if ($this->class) {
			return "<div class=\"{$this->class}\">\n{$this->html}\n</div> <!-- end of .{$this->class} -->\n\n";
		}
		return $this->html;
	}
	
	/**
	 * Generic method for drawing HTML tag elements.
	 * 
	 * @param string $tag
	 * @param string $txt
	 * @param string $class
	 */
	private function drawTag($tag, $txt, $class = '')
	{
		$class = $class ? " class=\"{$class}\"" : '';
		$this->html .= "<{$tag}{$class}>{$txt}</{$tag}>\n\n";
		return $this;
	}
	
	/**
	 * H1 - Header
	 * @param string $txt
	 * @param string $class
	 */
	public function h1($txt, $class = '') {$this->drawTag('h1', $txt, $class); return $this;}	
	
	/**
	 * H2 - Header
	 * @param string $txt
	 * @param string $class
	 */
	public function h2($txt, $class = '') {$this->drawTag('h2', $txt, $class); return $this;}

	/**
	 * H3 - Header
	 * @param string $txt
	 * @param string $class
	 */
	public function h3($txt, $class = '') {$this->drawTag('h3', $txt, $class); return $this;}
	
	/**
	 * H4 - Header
	 * @param string $txt
	 * @param string $class
	 */
	public function h4($txt, $class = '') {$this->drawTag('h4', $txt, $class); return $this;}
	
	/**
	 * H5 - Header
	 * @param string $txt
	 * @param string $class
	 */
	public function h5($txt, $class = '') {$this->drawTag('h5', $txt, $class); return $this;}
	
	/**
	 * H6 - Header
	 * @param string $txt
	 * @param string $class
	 */
	public function h6($txt, $class = '') {$this->drawTag('h6', $txt, $class); return $this;}

	/**
	 * P - Paragraph
	 * @param string $txt
	 * @param string $class
	 */
	public function p($txt, $class='') {$this->drawTag('p', $txt, $class); return $this;}

	/**
	 * SPAN
	 * @param string $txt
	 * @param string $class
	 */
	public function span($txt, $class = '') {$this->drawTag('span', $txt, $class); return $this;}

	/**
	 * Adds free-form HTML.
	 * @param string $txt
	 */
	public function addHTML($html) {$this->html .= $html; return $this;}
	
	/**
	 * Adds HTML comment
	 * @param string $txt
	 */
	public function comment($txt) {$this->html .= "<!-- {$txt} -->\n\n"; return $this;}

	/**
	 * Adds an unordered list.
	 * 
	 * The provided $list is an array of the string content of the list.
	 * 
	 * @param array $list
	 * @param string $class
	 */
	public function ul(array $list, $class = '')
	{
		$this->lis('ul', $list, $class);
		return $this;
	}
	
	/**
	 * Adds an ordered list.
	 * 
	 * The provided $list is an array of the string content of the list.
	 * 
	 * @param array $list
	 * @param string $class
	 */
	public function ol(array $list, $class = '')
	{
		$this->lis('ol', $list, $class);
		return $this;
	}
	
	/**
	 * Draws anything that uses LIs.
	 * 
	 * @param string $type
	 * @param array $list
	 * @param string $class
	 */
	private function lis($type, array $list, $class = '')
	{
		$l = '';
		foreach ($list as $li)
		{
			$l .= "<li>{$li}</li>\n";
		}
		$this->drawTag($type, $l, $class);
	}
	
	/**
	 * Generates an HTML link given the link text, the link HREF, an optional
	 * class, and an optional set of handlers.
	 * 
	 * Handlers is an array of (event_name => action).  For example
	 * array('onclick' => 'verifyForm(this)')
	 * 
	 * @param string $text
	 * @param string $href
	 * @param string $class
	 * @param array $handlers
	 * @return string
	 */
	public static function makeLink($text, $href, $class = '', $handlers = '', $target = '')
	{
		$class_element = $class ? "class=\"{$class}\"" : '';
		$events = '';
		if (is_array($handlers)) {
			foreach ($handlers as $on => $event)
			{
				$events .= "{$on}=\"{$event}\" ";
			}
		} elseif ($handlers) {
			$events = "onclick=\"return confirm('{$handlers}');\"";
		}
		$target_element = $target ? "target=\"{$target}\"" : '';
		$html = "<a href=\"{$href}\" {$class_element} {$target_element} {$events}>{$text}</a>";
		return $html;
	}
	
	/**
	 * Generates an HTML link to the specified module and job, with an optional
	 * item id.
	 * 
	 * @param string $text
	 * @param string $mode
	 * @param string $job
	 * @param string $id
	 * @return string
	 */
	public static function makeJobLink($text, $mode, $job = '', $id = '', $class = '', $handlers = '')
	{
		$id_query = $id ? "&id={$id}" : '';
		return self::makeLink($text, "?mode={$mode}&job={$job}{$id_query}", $class, $handlers);
	}
	
	public static function makeImage($url, $alt = '', $class = '')
	{
		$class_element = $class ? "class=\"{$class}\"" : '';
		return "<img src=\"{$url}\" alt=\"{$alt}\" {$class_element} />";
	}
	
	public static function textToHTML($text)
	{
		$html = str_replace("<", '&lt;', $text);
		$html = str_replace("\n", '<br/>', $html);
		return $html;
	}
}