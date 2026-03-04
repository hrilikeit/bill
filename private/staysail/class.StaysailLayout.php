<?php
/**
 * @package Staysail
 */

/**
 * A StaysailLayout is a tool for laying out HTML content in a grid.
 * 
 * Example usage:
 * 
 * <?php
 * // Set up an array of StaysailContainer instances, with a one-character key
 * // for each instance:
 * $containers = array(new StaysailContainer('H', 'header', $header_html),
 *                     new StaysailContainer('1', 'sidebar1', $sidebar1_html),
 *                     new StaysailContainer('2', 'sidebar2', $sidebar2_html),
 *                     new StaysailContainer('C', 'content', $content_html),
 *                     new StaysailContainer('N', 'navigation', $navigation_html),
 *                     new StaysailContainer('F', 'footer', $footer_html),
 *                    );
 * 
 * // Use the one-character keys to specify how the containers will be formatted:              
 * $map = <<<__END__
 *     HHHHHHHHHHHH
 *     NNCCCCCCCC11
 *     NNCCCCCCCC22
 *     FFFFFFFFFFFF
 * __END__;
 * 
 * // Create a StaysailLayout instance with the map and the containers, and call getHTML()
 * // to output the formatted containers:
 * $layout = new StaysailLayout($map, $containers);
 * print $layout->getHTML();
 * ?>                 
 * 
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 * @see StaysailContainer
 */
class StaysailLayout
{
	private $containers;
	private $matrix, $map;
	private $width, $height;
	private $system_width;
	private $direction_priority;
	private $borders;
	private $diagnostic;
	
	public function __construct($map, array $containers)
	{
		$this->map = $map;
		$this->direction_priority = 'y';
		$this->borders = array();
		$this->containers = $this->matrix = array();
		$this->width = $this->height = 0; // These will be set by mapToMatrix()
		
		// Set an array of containers indexed by their keys
		foreach ($containers as $container)
		{
			if (get_class($container) != 'StaysailContainer') {continue;}
			$key = $container->getKey();
			if (!$key) {continue;}
			$this->containers[$key] = $container;
		}
		
		// Set an empty container for missing keys
		for ($i = 0; $i < strlen($map); $i++)
		{
			$key = substr($map, $i, 1);
			if ($key != "\n" and $key != ' ' and $key != "\t" and !isset($this->containers[$key])) {
				$this->containers[$key] = new StaysailContainer($key, '', '&nbsp;');
			}
		}
	}
	
	/**
	 * Specifies that the computed with should be overridden.
	 * 
	 * The width should usually correspond to the number of grid sections in the
	 * page's layout.  StaysailLayout sets the width automatically based on the
	 * number of columns in the map, but this method may be used to override that
	 * width.
	 * 
	 * @param int $override_width
	 */
	public function overrideWidth($override_width) {$this->system_width = $override_width;}
	
	/**
	 * Returns the number of columns in the layout's grid.
	 * 
	 * @return int
	 */
	public function getWidth() {return $this->width;}
	
	/**
	 * Returns the number of containers in the layout.
	 * 
	 * @return int
	 */
	public function getContainerCount() {return sizeof($this->containers);}
	
	/**
	 * Specifies whether the layout generator absorbs adjacent containers horizontally
	 * ($direction = 'y') or vertically ($direction = 'x') first.  The default is 'y',
	 * but you can try 'x' if your layout doesn't look right.
	 * 
	 * @param string $direction
	 */
	public function setDirectionPriority($direction) {$this->direction_priority = $direction;}
	
	/**
	 * Turns on the diagnostic mode.
	 * 
	 * The diagnostic shows a new map at each step, allowing you to see how containers
	 * are absorbed into other containers during the layout process.
	 */
	public function diagnostic() {$this->diagnostic = true;}
	
	/**
	 * Specifies that a container is a border container.
	 * 
	 * Usually, all contiguous containers with the same height or width are absorbed at
	 * the same time.  For example
	 * 
	 * AAADDD           AAADDD
	 * BBBDDD  becomes  AAADDD
	 * CCCEEE           AAAEEE
	 * 
	 * in a single pass.  In some cases, it may be desirable to modify this behavior.  Once
	 * a border container is absorbed, the pass stops and the layout generator begins again
	 * at the top left container.  That is, if the B container above is a border, then
	 * 
	 * AAADDD           AAADDD
	 * BBBDDD  becomes  AAADDD
	 * CCCEEE           CCCEEE
	 * 	
	 * in the first pass.
	 *  
	 * The border is a container key.
	 * 
	 * @param string $border
	 */
	public function setBorder($border) {$this->borders[] = $border;}

	/**
	 * Returns HTML representation of the provided layout and containers.
	 * 
	 * @return string
	 */
	public function getHTML() 
	{
		$this->matrix = $this->mapToMatrix($this->map);
		if (!$this->system_width) {$this->system_width = $this->width;}
		
		// Start in upper left of matrix
		$start_key = $this->matrix[0][0];
		$this->absorbFrom($start_key);

		$html = "<div class=\"container_{$this->system_width}\">\n";
		foreach ($this->containers as $container)
		{
			$container->disableAlphaOmega();
			$html .= $container->getHTML();
			
		}
		$html .= "</div><!-- end of .container_{$this->system_width} -->\n";
		
		/*if (sizeof($this->containers) > 1) {
			$html .= "<strong>WARNING:</strong> The specified layout cannot be completely reduced.";
			print_r($this->containers);
		}*/

		return $html;
	}
	
	/**
	 * Uses the textual map string to generate and return a two-dimensional matrix.
	 * 
	 * For example "AA\nBC" becomes 
	 * array(0 => array(0 => 'A', 1 => 'A'), 1 => array(0 => 'B', 1 => 'C'))
	 * 
	 * @param string $map
	 * @return array
	 */
	private function mapToMatrix($map)
	{
		if ($this->diagnostic) {print "<pre>\n{$map}\n</pre><br/>\n\n";}
		$matrix = array();
		$this->width = $this->height = 0;
		$lines = explode("\n", $map);
		$lines = array_map('trim', $lines);
		$y = 0;
		$start_y = $end_y = $start_x = $end_x = array();
		$last = '';
		foreach ($lines as $L)
		{
			if (strlen($L) > $this->width) {$this->width = strlen($L);}
			if ($L == $last) {continue;} // No need for identical lines
			$last = $L;
			for ($x = 0; $x < strlen($L); $x++)
			{
				$key = substr($L, $x, 1);

				$matrix[$y][$x] = $key;
				if (!isset($start_y[$key])) {$start_y[$key] = $y;}
				$end_y[$key] = $y;
				
				if (!isset($start_x[$key])) {$start_x[$key] = $x;}
				$end_x[$key] = $x;
			}
			$y++;
		}
		$this->height = $y;
		
		// Set positions, widths, and heights of containers
		foreach ($this->containers as $key => $container)
		{
			if (isset($end_y[$key])) {
				$width = $end_x[$key] - $start_x[$key] + 1;
				$height = $end_y[$key] - $start_y[$key] + 1;
				
				$container->setWidth($width);
				$container->setHeight($height);
				$container->setX($start_x[$key]);
				$container->setY($start_y[$key]);
			}
		}
		
		return $matrix;
	}
	
	/**
	 * Starting from the given key, finds contiguous containers of the same width or height
	 * and absorbs them into a single container.  When the container in the upper left-hand
	 * cell is the only container left, absorbFrom() exits.
	 * 
	 * @param string $key
	 * @return void
	 */
	private function absorbFrom($key)
	{
		if (isset($this->containers[$key])) {
			
			$search_order = $this->direction_priority == 'x' ? array('x', 'y') : array('y', 'x');
			$found = false;
			foreach ($search_order as $direction)
			{
				$neighbors = $this->findNeighbors($key, $direction);
				if (sizeof($neighbors)) {
					$this->absorbContainers($key, $neighbors);
					$found = true;
					break;
				}
			}
			if (!$found) {
				$key = $this->getNextKeyFrom($key);
			}
		} else {
			return;
		}
		
		if ($key !== null) {
			$this->absorbFrom($key);
		}
	}
	
	/**
	 * After contiguous containers are found with findNeighbors(), absorbContainers()
	 * absorbs these neighbors into the container with the given key.  Then, it deletes
	 * absorbed containers and re-starts the recursive absorb from the upper left-hand
	 * cell of the matrix.
	 * 
	 * @param string $key
	 * @param array $neighbors
	 * @return void
	 */
	private function absorbContainers($key, array $neighbors)
	{
		if (isset($this->containers[$key])) {
			$reference = $this->containers[$key];
			$to_absorb = array();
			foreach ($neighbors as $neighbor_key)
			{
				if ($neighbor_key == $key) {continue;}
				if (isset($this->containers[$neighbor_key])) {
					$to_absorb[] = $this->containers[$neighbor_key];
					$this->map = str_replace($neighbor_key, $key, $this->map);
				}
			}
			$reference->absorb($to_absorb, $this);

			// After the containers are absorbed, remove them from the containers list
			foreach ($to_absorb as $container)
			{
				$absorbed_key = $container->getKey();
				if (isset($this->containers[$absorbed_key])) {
					$this->containers[$absorbed_key] = null;
					unset ($this->containers[$absorbed_key]);
				}
			}

			$this->matrix = $this->mapToMatrix($this->map);

			// Continue with the upper left key
			$next_key = $this->matrix[0][0];
			$this->absorbFrom($next_key);
		}
	}

	/**
	 * If no neighbors are found in absorbFrom(), getNextKeyFrom() looks for the next
	 * container to the right.  If no containers are to the right, it looks for the next
	 * container below.  If no containers are below, it returns null, which would mean that
	 * the entire layout is absorbed into a single containers (i.e., finished).
	 * 
	 * The major differences between getNextKeyFrom() and findNeighbors() is that
	 * findNeighbors() cares about matching the height or width of its adjoining containers.  
	 * getNextKeyFrom() looks for the next container to the right, and then down, without
	 * caring if its height or width matches.
	 * 
	 * @param string $key
	 * @return NULL|string
	 */
	private function getNextKeyFrom($key)
	{
		if (isset($this->containers[$key])) {
			$reference = $this->containers[$key];
			$start_x = $reference->getX();
			$start_y = $reference->getY();
			
			for ($y = $start_y; $y < sizeof($this->matrix); $y++)
			{
				for ($x = $start_x; $x < $this->width; $x++)
				{
					if (isset($this->matrix[$y][$x]) and $this->matrix[$y][$x] != $key) {
						$k = $this->matrix[$y][$x];
						if (isset($this->containers[$k])) {
							if ($this->containers[$k]->getY() >= $y) {
								return $k;
							}
						}
					}
				}
				$start_x = 0; // When we drop to the next row, start at the first column
			}
		}
		return null;			
	}
	
	/**
	 * Searches down for containers with the same starting position and width (for y direction) or 
	 * height (for x direction).  Returns an array of keys for such containers.
	 * 
	 * Direction can be 'x' or 'y'.
	 * 
	 * @param string $from_key
	 * @param string $direction
	 * @return array
	 */
	private function findNeighbors($from_key, $direction)
	{
		if (isset($this->containers[$from_key])) {
			$reference = $this->containers[$from_key];
			$next_y = $reference->getY() + $reference->getHeight();
			$next_x = $reference->getX() + $reference->getWidth();
			$found = null;
			foreach ($this->containers as $key => $container)
			{
				if ($key == $from_key) {continue;}
				if ($direction == 'y' and $container->getY() == $next_y 
				  and $container->getX() == $reference->getX() 
				  and $container->getWidth() == $reference->getWidth()) {
					$found = $key;
					break;
				}
				if ($direction == 'x' and $container->getX() == $next_x 
				  and $container->getY() == $reference->getY()
				  and $container->getHeight() == $reference->getHeight()) {
					$found = $key;
					break;
				}
			}
			
			if ($found !== null) {
				$found_set = array($found);
				if (!in_array($found, $this->borders)) {
					$next_neighbor = $this->findNeighbors($found, $direction);
					if (sizeof($next_neighbor)) {
						$found_set = array_merge($found_set, $next_neighbor);
					}
				}
				return $found_set;
			}
		}
		
		return array();
	}
}