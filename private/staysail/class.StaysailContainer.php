<?php
/**
 * @package Staysail
 */

/**
 * A StaysailContainer holds HTML content that can be arranged using a StaysailLayout.
 *
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 * @see StaysailLayout
 */
class StaysailContainer
{
    private $div_id;    // The ID (<div id="{$div_id}">)
    private $key;       // The single-character key the associates the map with the container
    private $html;
    private $x, $y, $width, $height;

    public function __construct($key, $div_id = '', $html = '')
    {
        $this->key = substr($key, 0, 1); // Enforce a one-character key
        $this->div_id = $div_id;
        if ($html) {
            $this->setHTML($html);
        } else {
            $this->html = '';
        }
    }

    public function getKey() {return $this->key;}

    public function setWidth($width) {$this->width = $width;}
    public function getWidth() {return $this->width;}

    public function setX($x) {$this->x = $x;}
    public function getX() {return $this->x;}

    public function setHeight($height) {$this->height = $height;}
    public function getHeight() {return $this->height;}

    public function setY($y) {$this->y = $y;}
    public function getY() {return $this->y;}

    public function setHTML($html)
    {
        $id_attr = $this->div_id ? "id=\"{$this->div_id}\"" : '';
        $this->html = "<div class=\"container\" {$id_attr}>{$html}</div>\n";
    }

    public function getHTML()
    {
        return $this->html;
    }

    public function enableAlphaOmega()
    {
        $this->html = str_replace('|@AL@|', 'alpha', $this->html);
        $this->html = str_replace('|@OM@|', 'omega', $this->html);
    }

    public function disableAlphaOmega()
    {
        $this->html = preg_replace('/\|@(AL|OM)@\|/', '', $this->html);
    }

    /**
     * Direct this container to absorb a group of specified adjacent containers
     *
     * @param array $containers
     * @param StaysailLayout $layout
     */
    public function absorb(array $containers, StaysailLayout $layout)
    {
        if (!sizeof($containers)) {return;}

        $direction = $this->getX() == $containers[0]->getX() ? 'y' : 'x';
        array_unshift($containers, $this);
        $html = '';

        for ($i = 0; $i < sizeof($containers); $i++)
        {
            $container = $containers[$i];
            $container->enableAlphaOmega();
            $content = $container->getHTML();
            $width = $container->getWidth();

            if ($direction == 'x') {
                $alpha = ($i == 0) ? '|@AL@|' : '';
                $omega = ($i == (sizeof($containers) - 1)) ? '|@OM@|' : '';
                if(StaysailIO::session('Member.id') && StaysailIO::get('job') != 'join' && StaysailIO::get('job') != 'new_member' ){
                    if($i == 1){ $html .= '<div class="middle-container grid_12">'; }
                    if($i == (sizeof($containers) -1)){ $html .= "</div>"; }
                }
                $html .= "<div class=\"grid_{$width} {$alpha} {$omega}\">\n{$content}\n</div><!-- end .grid_{$width} -->\n\n";
            } else {
                if ($container->getWidth() < $this->width or $layout->getContainerCount() == sizeof($containers)) {
                    $html .= "<div class=\"grid_{$width} this |@AL@|\">\n{$content}\n</div><!-- end .grid_{$width} -->\n\n";
                } else{
                    $html .= $content;
                }
                $html .= "<div class=\"clear\"></div>\n\n";
            }

        }

        $this->html = $html;
        //$this->html = print_r($containers,true);
    }
}