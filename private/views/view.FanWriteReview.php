<?php

class FanWriteReview extends LSFView
{
	
	public function getDashVersionHTML()
	{
		return __CLASS__;
	}
	
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter();
		
		if (StaysailIO::get('job') == 'post') {
			$this->postReview();
			$writer->p('<strong>Thank you for posting your review!</strong>');
		}
		
		$writer->start('panel')
		       ->h1("Write a Review");
		
		// Has the fan already reviewed this entity?
		$type = StaysailIO::get('type');
		$id = StaysailIO::getInt('id');
		
		if ($type == 'Entertainer' or $type == 'Club') {
			$form = new StaysailForm();
			$form->setSubmit('Submit Review')
				 ->setPostMethod()
				 ->setAction("?mode=FanModule&focus=FanWriteReview&job=post&type={$type}&id={$id}");
			$entity = new $type($id);
			$writer->p("For {$entity->name}");
			$Review = $this->Member->getReviewFor($entity);
			if ($Review) {
				$form->setDefaults($Review->info());
			} else {
				$form->setDefaults(array('rating' => 3));
			}
			$ratings = array(1 => '*', 2 => '**', 3 => '***', 4 => '****', 5 => '*****');
			$form->addField(StaysailForm::Line, 'Review Title', 'name', 'required')
			     ->addField(StaysailForm::Text, 'Review', 'content', 'required')
			     ->addField(StaysailForm::Select, 'Rating', 'rating', 'required', $ratings);
			$writer->draw($form);			     
		}
		
		$writer->end('panel');
		return $writer->getHTML();			   
		
		
	}
	
	private function postReview()
	{
		$type = StaysailIO::get('type');
		$id = StaysailIO::getInt('id');
		
		if ($type == 'Entertainer' or $type == 'Club') {
			$fields = array('name', 'content', 'rating');
			$entity = new $type($id);
			$Review = $this->Member->getReviewFor($entity);
			if (!$Review) {$Review = new Review();}
			$Review->updateFrom($fields);
			$Review->update(array('review_time' => StaysailIO::now(),
								  'admin_status' => 'pending',
								  "{$type}_id" => $entity->id,
								  'Member_id' => $this->Member->id));
			$Review->save();
		}
	}
}