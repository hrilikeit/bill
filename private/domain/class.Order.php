<?php

final class Order extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $Payment_Method = parent::AssignOne;
    public $order_time = parent::Time;
    public $order_code = parent::Line;
    public $authcode = parent::Line;
    public $transactionid = parent::Line;
    public $payment_amount = parent::Currency;
    public $cancel = parent::Boolean;
    public $auth_only = parent::Boolean;
    public $payment_time = parent::Time;
    public $goal_id = parent::Int;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }

    public function delete_Job() {parent::delete();}	

    public function copy_Job() {return $this->copy();}

    public function belongsTo(Member $Member)
    {
    	//return ($this->Member->id == $Member->id);

        return $Member->id;
    }
    
    public function setMember(Member $Member)
    {
    	$this->Member = $Member;
    	$this->save();
    	
    	// Set order code and time at the same time
    	$this->order_code = 100000 + $this->id;
    	$this->order_time = StaysailIO::now();
    	$this->save();
    }
    
    public function addOrderLine($description, $price_ea, $quantity = 1, $entity = null)
    {
	   	$Order_Line = new Order_Line();
	   	$update = array('Order_id' => $this->id,
    						'line_time' => StaysailIO::now(),
    						'description' => $description,
    						'price_ea' => $price_ea,
    						'quantity' => $quantity,
    						'price' => $price_ea * $quantity,
    						'cancel' => 0,
    	 				);
		if ($entity) {
			$update['domain_entity'] = get_class($entity);
			$update['entity_id'] = $entity->id;
		}
    	$Order_Line->update($update);
		$Order_Line->save();    	 				   
    }
    
    public function applyPayment($amount, $authcode, $transactionid, $Payment_Method = null, $auth_only = 0)
    {

        	$auth_only = $auth_only ? 1 : 0;
    	    $update = array('authcode' => $authcode,
    				        'transactionid' => $transactionid,
    				        'payment_amount' => $amount,
    				        'auth_only' => $auth_only,
    				        'payment_time' => StaysailIO::now(),
      	            );
		if ($Payment_Method !== null) {
			// For a captured transaction, there's no need to change the payment method
			$update['Payment_Method_id'] = $Payment_Method->id;
			
			// Add the cc_tx for the original transaction id, for repeat billing
			if (!$Payment_Method->cc_tx) {
    			    $Payment_Method->update(array('cc_tx' => $transactionid));
    			    $Payment_Method->save();
			}
		}
		$this->update($update);
		$this->save();    	             
    }
    
    public function getTotalAmount()
    {
    	$lines = $this->_framework->getSubset('Order_Line', new Filter(Filter::Match, array('Order_id' => $this->id, 'cancel' => 0)));
    	$total = 0;
    	foreach ($lines as $Order_Line)
    	{
    		$total += $Order_Line->price;
    	}
    	return $total;
    }
    
    public function cancel()
    {
    	$lines = $this->_framework->getSubset('Order_Line', new Filter(Filter::Match, array('Order_id' => $this->id, 'cancel' => 0)));
    	foreach ($lines as $Order_Line)
    	{
    		$Order_Line->cancel();
    	}
    	$this->cancel = 1;
    	$this->save();
    	
    }
    
    public function getHTML()
    {
    	$writer = new StaysailWriter('order_info');
    	$writer->addHTML($this->getHeaderTable())
    		   ->addHTML($this->getLineTable());
		return $writer->getHTML();    		   
    }
    
    private function getHeaderTable()
    {
    	$table = new StaysailTable();
    	$table->addRow(array('Order Number', $this->order_code))
    		  ->addRow(array('Order Date', date('M j, Y', strtotime($this->order_time))))
    		  ->addRow(array('Payment Amount', "$" . number_format($this->payment_amount, 2)))
    		  ->addRow(array('Authorization Code', $this->authcode));
		return $table->getHTML();    		  
    }
    
    private function getLineTable()
    {
    	$table = new StaysailTable();
    	$lines = $this->_framework->getSubset('Order_Line', new Filter(Filter::Match, array('Order_id' => $this->id, 'cancel' => 0)));
    	foreach ($lines as $Order_Line)
    	{
    		$table->addRow(array($Order_Line->description,
								 '$' . number_format($Order_Line->price_ea, 2),
	    			  			 $Order_Line->quantity,
    			  				 '$' . number_format($Order_Line->price, 2)));
    	}
    	return $table->getHTML();
    }
}
    