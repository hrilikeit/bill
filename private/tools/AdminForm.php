<?php

class AdminForm
{
	private $entity; // The object for which the form is created
	
	public function __construct(StaysailEntity $entity)
	{
		$this->entity = $entity;
	}
	
	public function getHTML()
	{
		$form = $this->getForm();
		return $form->getHTML();
	}
	
	public function getForm()
	{
		$form = new StaysailForm();
		
		$fields = $this->entity->fields();
		$defaults = $this->entity->info();
		if (isset($defaults['encoded_password'])) {
			$defaults['encoded_password'] = '';
		}
		$defaults['CLASS_NAME'] = get_class($this->entity);
		$form->setDefaults($defaults);
		foreach ($fields as $fieldname => $type)
		{
			$name = ucwords(str_replace('_', ' ', $fieldname));
			switch ($type)
			{
				case StaysailEntity::Boolean:
					$form->addField(StaysailForm::Boolean, $name, $fieldname);
					break;

				case StaysailEntity::Text:
					$form->addField(StaysailForm::Text, $name, $fieldname);
					break;
					
				case StaysailEntity::Enum:
					$enum_options = "{$fieldname}_Options";
					$form->addField(StaysailForm::Select, $name, $fieldname, 'required', $this->entity->$enum_options());
					break;
			
				default:
					$form->addField(StaysailForm::Line, $name, $fieldname);
			}
		}
		
		$form->setPostMethod();
		$form->setJobAction('Administrator', 'update', $this->entity->id);
		$form->addField(StaysailForm::Hidden, '', 'CLASS_NAME');
		
		return $form;
	}
}