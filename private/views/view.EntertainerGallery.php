<?php
class EntertainerGallery extends LSFView
{
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter();
		
		$job = StaysailIO::get('job');
		$id = StaysailIO::getInt('id');
		
		switch ($job)
		{
			case 'add_image':
				$writer = $this->getAddImageScreen();
				break;
				
			case 'edit_image':
				$writer = $this->getEditImageScreen($id);
				break;
				
			case 'view_image_history':
				$writer = $this->getHistoryScreen($id);
				break;
		}
		return $writer->getHTML();			   
	}
	
	public function getDashVersionHTML()
	{
		$writer = new StaysailWriter('thumbnails');
		$writer->start('panel')
			   ->h1('Gallery')
			   ->start('dash_scrollarea');
		if (StaysailIO::get('job') == 'edit_bio') {
			// If the Entertainer is on the Edit Bio screen, show only the
			// web images for drag and drop.
			$webonly = Entertainer::LibraryWebOnly;
		} else {
			$webonly = false;
		}		   
		$gallery = $this->account->getGallery($webonly);
		
		foreach ($gallery as $Library)
		{
			if ($webonly) {
				// If the Gallery panel is acting as an image selector, don't permit image clicking
				$edit_link = $Library->getThumbnailHTML();
			} else {
				$edit_link = StaysailWriter::makeJobLink($Library->getThumbnailHTML(), 'EntertainerModule', 'edit_image&focus=EntertainerGallery', $Library->id);
			}
			$writer->start('image')
				   ->addHTML($edit_link)
				   ->end('image');
		}
		
		$writer->end('dash_scrollarea')
		       ->end('panel')
			   ->p(StaysailWriter::makeJobLink('Add Image', 'EntertainerModule', 'add_image&focus=EntertainerGallery'));
		return $writer->getHTML();
	}
	
	private function getAddImageScreen()
	{
		$writer = new StaysailWriter();
		$placements = array('web' => 'For use on your website', 'sale' => 'For sale to Fans');
		
		$upload = new StaysailForm();
		$upload->setJobAction('EntertainerModule', 'post_image')
			   ->setPostMethod()
			   ->setSubmit('Add This Image')
			   ->addField(StaysailForm::File, 'Image', 'image', 'required')
			   ->addField(StaysailForm::Radio, 'Type of Image', 'placement', 'require-choice', $placements)
			   ->addField(StaysailForm::Line, 'Image Name', 'name')
			   ->addField(StaysailForm::Text, 'Description', 'description')
			   ->addField(StaysailForm::Line, 'Keywords', 'keywords');
		
		$writer->h1('Add an Image')
			   ->draw($upload);
		return $writer;
	}
	
	private function getEditImageScreen($library_id)
	{
		$writer = new StaysailWriter();
		$Library = new Library($library_id);
		if (!$Library->belongsTo($this->Member)) {
			$writer->h1('Sorry...')
				   ->p("You do not have access to this image");
			return $writer;				   
		}
		if (!$Library->id) {
			return $this->getAddImageScreen();
		}
		
		$edit = new StaysailForm('narrow');
		$edit->setJobAction('EntertainerModule', 'post_image', $Library->id)
			 ->setPostMethod()
			 ->setSubmit('Update This Image')
			 ->setDefaults($Library->info())
			 ->addField(StaysailForm::Line, 'Image Name', 'name')
			 ->addField(StaysailForm::Text, 'Description', 'description')
			 ->addField(StaysailForm::Line, 'Keywords', 'keywords');

		$metadata = 'This image is ';
		if ($Library->placement == 'sale') {
			$metadata .= "<strong>for sale.</strong>  ";
			$metadata .= StaysailWriter::makeJobLink('View sales history &raquo;', 'EntertainerModule', 'view_image_history&focus=EntertainerGallery', $Library->id) 
			  . "&nbsp;&nbsp";
			$metadata .= StaysailWriter::makeJobLink('Remove image from sale  &raquo;', 'EntertainerModule', 'remove_image', $Library->id);
		} elseif ($Library->placement == 'web') {
			$metadata .= "<strong>for web viewing.</strong> ";
			$metadata .= StaysailWriter::makeJobLink('Remove image from site &raquo;', 'EntertainerModule', 'remove_image', $Library->id);
		} else {
			$metadata .= "<strong>inactive.</strong>";
		}
		$writer->h1('Edit Image')
			   ->p($metadata)
			   ->addHTML($Library->getWebHTML())
			   ->draw($edit);
		return $writer;			   
	}
	
	private function getHistoryScreen($library_id)
	{
		$writer = new StaysailWriter();
		$Library = new Library($library_id);
		if (!$Library->belongsTo($this->Member)) {
			$writer->h1('Sorry...')
				   ->p("You do not have access to this image");
			return $writer;				   
		}

		$writer->h1('Sales History')
		       ->addHTML($Library->getWebHTML())
		       ->h2($Library->name)
		       ->p("History is not yet implemented.  It will be available whent the Fan interface allows orders.");
		return $writer;		
	}
	
}