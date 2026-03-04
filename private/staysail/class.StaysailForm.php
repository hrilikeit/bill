<?php
/**
 * @package Staysail
 */

/**
 * A StaysailForm is a tool for creating and populating HTML forms.
 * 
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 */
class StaysailForm
{
	// The supported field types:
	const Line = 'text';
	const Password = 'password';
	const Hidden = 'hidden';
	const Text = 'textarea';
    const File = 'file';
    const File_multiple = 'multiple';
	const Select = 'select';
	const Radio = 'radio';
	const Checkbox = 'checkbox';
	const Boolean = 'bool';
	const Bool = 'bool';  // Deprecated alias for Boolean
	
	private $html, $hidden, $defaults;
	private $form_action, $form_class, $submit, $method, $enctype;	

	/**
	 * Construct a StaysailForm.
	 * 
	 * @param string $form_class
	 */
	public function __construct($form_class = '')
	{
		$this->form_class = $form_class;
		$this->form_action = '?';
		$this->submit = 'Submit';
		$this->method = 'get';
	}
	
	/**
	 * Converts a numeric array into an associative array with its
	 * key as its value.
	 * 
	 * @param array $options
	 * @return array
	 */
	public static function textOptions(array $options)
	{
		$text_options = array();
		foreach ($options as $option)
		{
			$option = trim($option);
			$text_options[$option] = $option;
		}
		return $text_options;
	}
	
	/**
	 * Set the action attribute for the FORM element
	 * 
	 * @param string $form_action
	 */
	public function setAction($form_action) {$this->form_action = $form_action; return $this;}
	
	/**
	 * Set the action as a mode/job/id group (for Staysail applications)
	 * 
	 * @param string $mode
	 * @param string $job
	 * @param string $id
	 */
	public function setJobAction($mode, $job, $id = '') {$this->form_action = "?mode={$mode}&job={$job}" . ($id ? "&id={$id}" : ''); return $this;}
	
	/**
	 * Set the form's method to POST.
	 */
	public function setPostMethod() {$this->method = 'post'; return $this;}	
	
	
	/**
	 * Set the form's method to GET.
	 */
	public function setGetMethod() {$this->method = 'get'; return $this;}

	/**
	 * Set the value attribute for the submit button.
	 * 
	 * @param string $submit
	 */
	public function setSubmit($submit) {$this->submit = $submit; return $this;}

	
	/**
	 * Set the prefill array as an array of name=>value pairs.
	 * 
	 * @param array $defaults
	 */
	public function setDefaults(array $defaults) {$this->defaults = $defaults; return $this;}
	
	/**
	 * Return form default value
	 * 
	 * @param string $name
	 * @return string
	 */
	public function getDefault($name)
	{
		return isset($this->defaults[$name]) ? $this->defaults[$name] : '';
	}

    /**
     * Adds a block of HTML into the form.  The HTML is enclosed in a
     * "field" DIV so that it can be formatted like fields within
     * the form.
     * 
     * @param string $html
     */
    public function addHTML($html)
    {
        $this->html .= "<div class=\"field\">{$html}</div>";
        return $this;
    }
		
	/**
	 * Adds field and field formatting HTML to the form.  The field HTML
	 * comes from the makeFieldHTML method.
	 * 
	 * @see makeFieldHTML
	 * @param string $type
	 * @param string $label
	 * @param string $name
	 * @param string $class
	 * @param string $options
	 * @param string $instruction
	 */
	public function addField($type, $label, $name, $class = '', $options = '', $handlers = '', $instruction = '', $allowedTypes ="*", $placeholder='')
	{
		$default = $this->getDefault($name);
		$control = StaysailForm::makeFieldHTML($type, $label, $name, $default, $class, $options, $handlers, $allowedTypes, $placeholder);
		if ($type != self::Hidden) {		
			$this->html .= "<div class=\"field {$class} {$type}\" id=\"field_{$name}\">";
			$this->html .= "<div class=\"label\">{$label}</div>";
			if($instruction != ''){
				$this->html .= "<div class=\"control\">{$control} <span class=\"instruction\">{$instruction}</span></div>";
			}else{
				$this->html .= "<div class=\"control\">{$control}</div>";
			}
			$this->html .= "</div>";
		
		} else {
			$this->hidden .= $control;
		}
		if (in_array($type, [self::File, self::File_multiple])) {
			$this->enctype = 'enctype="multipart/form-data"';
		}
		return $this;
	}
	
	/**
	 * Returns HTML for a field.  Type is one of the supported field types
	 * listed at the beginning of this class.  The label is the field label
	 * displayed to the user.  The name is the name= attribute for the
	 * HTML.  The default is a pre-filled value.  The class is an optional
	 * DOM class name.  The options are an array of value=>label pairs,
	 * for use in SELECT boxes and button sets.
	 * 
	 * @param string $type
	 * @param string $label
	 * @param string $name
	 * @param string $default
	 * @param string $class
	 * @param string $placeholder
	 * @param array $options
	 * @param array $handlers
	 * @return string
	 */
	public static function makeFieldHTML($type, $label, $name, $default = '', $class = '', $options = '', $handlers = '', $allowedTypes = '*', $placeholder='' )
	{
		$html = '';
		$class_html = $class ? "class=\"{$class}\"" : '';
		
		$events = '';
		if (is_array($handlers)) {
			foreach ($handlers as $on => $event)
			{
				$events .= "{$on}=\"{$event}\" ";
			}
		}
				
		switch ($type)
		{
			case self::Line:
			case self::Password:
			case self::Hidden:
				StaysailIO::cleanse($value, StaysailIO::HTML);
				$html .= "<input type=\"{$type}\" name=\"{$name}\" value=\"{$default}\" {$events}{$class_html} placeholder='{$placeholder}'/>";
				break;
				
			case self::Text:
				StaysailIO::cleanse($value, StaysailIO::HTML);
				$html .= "<textarea name=\"{$name}\" {$events}{$class_html} placeholder='{$placeholder}'>{$default}</textarea>";
				break;
			
			case self::File:
				$html .= "<input type=\"file\" name=\"{$name}\" accept='$allowedTypes' {$events}{$class_html}/>";
				break;

            case self::File_multiple:
                $html .= "<input type=\"file\" name=\"{$name}\" accept='$allowedTypes' {$events}{$class_html} multiple/>";
                break;
	
			case self::Select:
				if (!is_array($options)) {return '';}
				$html .= "<select name=\"{$name}\" {$events}{$class_html}>\n";
				foreach ($options as $k => $v)
				{
					$selected = ($default == $k) ? 'selected="selected"' : '';
					StaysailIO::cleanse($k, StaysailIO::HTML);
					StaysailIO::cleanse($v, StaysailIO::HTML);					
					$html .= "<option {$selected} value=\"{$k}\">{$v}</option>\n";
				}
				$html .= "</select>\n";
				break;
				
			case self::Checkbox:
			case self::Radio:
				if (!is_array($options)) {return '';}
				$i = 0;
				foreach ($options as $k => $v)
				{
					$arr = $type == self::Checkbox ? '[]' : '';
					if (is_array($default)) {
						$checked = (in_array($k, $default)) ? 'checked="checked"' : '';
					} else {
						$checked = ($default == $k) ? 'checked="checked"' : '';
					}
					StaysailIO::cleanse($k, StaysailIO::HTML);
					StaysailIO::cleanse($v, StaysailIO::HTML);
					$html .= "<div><input type=\"{$type}\" name=\"{$name}{$arr}\" id=\"{$name}_{$i}\" value=\"{$k}\" {$checked} {$events}{$class_html}/>";
					$html .= "<label for=\"{$name}_{$i}\"> {$v}</label>\n</div>\n";
					$i++;
				}
				break;

			case self::Bool:
				$checked = ($default == 1) ? 'checked="checked"' : '';
				$html .= "<input type=\"checkbox\" name=\"{$name}\" value=\"1\" {$checked} {$events}{$class_html}/>";
				break;
		}
		
		return $html;
	}
	
	/**
	 * Return the forms's browser-ready output string.
	 * 
	 * @return string
	 */
	public function getHTML()
    {
        if ($this->form_action === "?mode=WebShowModule&job=add_fan_participant") {
            $this->form_class = 'add_fan_participant';
        }
        $formId = "";
        if ($this->form_class === "start_show_form") {
            $formId = ' id="start_show_form" ';
        } elseif ($this->form_class === 'add_fan_participant') {
            $formId = ' id="join_show_form" ';
        }
        $class = $this->form_class ? "class=\"{$this->form_class}\"" : '';
        $html = "<form method=\"{$this->method}\" {$formId} action=\"{$this->form_action}\" {$this->enctype} {$class} onsubmit=\"return uniValidate(this)\">\n";
        $html .= $this->hidden;
        $html .= $this->html;
        if ($this->form_class === "start_show_form") {
            $html .= "<div class=\"field\"><div class=\"submit\"><button type=\"button\" onclick=\"startShow()\" id=\"start-show-form-button\">{$this->submit}</button></div></div>";
        } elseif ($this->form_class === 'add_fan_participant') {
            $html .= "<div class=\"field\"><div class=\"submit\"><button type=\"button\" onclick=\"joinShow()\">{$this->submit}</button></div></div>";
        } else {
            $html .= "<div class=\"field\"><div class=\"submit\"><input type=\"submit\" value=\"{$this->submit}\" /></div></div>";
        }
        $html .= "</form>";
        return $html;
	}
		
}
