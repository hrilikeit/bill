<?php

class FanCheckout extends LSFView
{
	
	public function getDashVersionHTML()
	{
		return __CLASS__;
	}
	
	public function getMainVersionHTML()
	{
		if (StaysailIO::get('verify')) {
			$this->completePurchase();
		}
		
		$writer = new StaysailWriter();
		
		$type = StaysailIO::get('type');
		$entity_id = StaysailIO::getInt('id');
		$item = $description = $price = $instructions = '';
		
		switch ($type)
		{
			case 'Library':
				$item = 'high-resolution image';
				$Library = new Library($entity_id);
				if ($Library->hasAccess($this->Member)) {
					// TODO: check access
				}
				$description = $Library->getWebHTML();
				$instructions = "<strong>Once you have completed this purchase, the {$item} will be available on your home page in the Library panel</strong>";
				break;
				
			case 'Entertainer':
				$Entertainer = new Entertainer($entity_id);
				$item = "one-year subscription to <strong>{$Entertainer->name}'s</strong> Local Strip Fan site";
				$description = StaysailWriter::makeImage("/avatar.php?a={$Entertainer->Member->id}", $Entertainer->name, 'web');
				$instructions = "<strong>Once you have completed this purcase, the {$item} will be visible on your home page.</strong>";					
				break;
				
			case 'Show_Schedule':
				$Show_Schedule = new Show_Schedule($entity_id);
				$item = $Show_Schedule->name;
				$description = StaysailWriter::makeImage("/avatar.php?a={$Show_Schedule->Entertainer->Member->id}", $Show_Schedule->Entertainer->name, 'web');
				break;				
		}
		
		$verify_form = new StaysailForm();
		$verify_form->setSubmit('Verify Purchase $9.99')
					->setPostMethod()
					->setAction("?mode=FanModule&focus=FanCheckout&verify=1")
					->setDefaults(array('type' => $type, 'id' => $entity_id))
					->addField(StaysailForm::Hidden, '', 'type')
					->addField(StaysailForm::Hidden, '', 'id');
		
		$writer->start('panel')
			   ->h1("Verify Purchase")
			   ->p("You have selected the following {$item} for purchase:\n{$description}")
			   ->p("Please click Verify Purchase below and this {$item} will be charged to your credit card ending in <strong>1111</strong>.")
			   ->p($instructions)
			   ->p("<i>Or, offer card entry info screen, dependent on processor.</i>")
			   ->draw($verify_form)
			   ->end('panel');			   
		return $writer->getHTML();			   
	}
	
	private function completePurchase()
	{
		$type = StaysailIO::post('type');
		$entity_id = StaysailIO::post('id');
		
		switch ($type)
		{
			case 'Library':
				//TODO: Again, check access
				$Fan_Library = new Fan_Library();
				$update = array('Fan_id' => $this->account->id,
							    'Library_id' => $entity_id,
								'File_Type_id' => 1,
							   );
				$Fan_Library->update($update);
				$Fan_Library->save();
				break;	

			case 'Entertainer':
				$Entertainer = new Entertainer($entity_id);
				$this->account->subscribeTo($Entertainer);				
				break;
		}
		
		header("Location: /?mode=FanModule");
	}
}