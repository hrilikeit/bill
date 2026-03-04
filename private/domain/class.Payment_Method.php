<?php

final class Payment_Method extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;
	public $Member = parent::AssignOne;
	public $cc_number = parent::Line;
	public $cc_vc = parent::Line;
	public $expire_month = parent::Int;
	public $expire_year = parent::Int;
	public $cc_type = parent::Line;
	public $cc_tx = parent::Line;
	public $default_card = parent::Int;
	
	public $deleted = parent::Boolean;
	
	// Premier Payment billing fields
	public $firstname = parent::Line;
	public $lastname = parent::Line;
	public $company = parent::Line;
	public $address1 = parent::Line;
	public $address2 = parent::Line;
	public $city = parent::Line;
	public $state = parent::Line;
	public $country = parent::Line;
	public $zip = parent::Line;
	public $phone = parent::Line;
	public $email = parent::Line;
	
    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);		
    }

    public function delete_Job() 
    {
		$this->deleted = 1;
		$this->save();    
    }	

    public function copy_Job() {return $this->copy();}
    
    public function belongsTo(Member $Member)
    {
    	//return ($this->Member->id == $Member->id);

        return $Member->id;
    }
    
    public function setEncryptedNumbers($cc_number, $cc_vc = '')
    { 
	$this->cc_number = $this->encryption($cc_number);
	$this->cc_vc = $this->encryption($cc_vc);

	$this->save();
/*
	$sql = "UPDATE `Payment_Method`
                SET cc_number = '{$this->cc_number}',
                    cc_vc = '{$this->cc_vc}'
                WHERE id = {$this->id}";
        $this->_framework->query($sql);
        $pm = new Payment_Method($this->id);

        if ($this->decryption($pm->cc_number) != $cc_number) {
print $this->cc_number . "\n\n";
print $pm->cc_number . "\n\n";


            print "Bad encryption";
            exit;
        } else {
            print "encryption OK";
            exit;
        }  
*/ 
    }
    
    public function getCCNumber()
    {
	return $this->decryption($this->cc_number);
    }

    public function getCCVC()
    {
	return $this->decryption($this->cc_vc);
    }
    
    public function getExpiration()
    {
        	$mm = substr(trim($this->expire_month), 0, 2);
        	$mm = str_pad($mm, 2, '0', STR_PAD_LEFT);
        	$yy = substr(trim($this->expire_year), 2, 2);
        	$yy = str_pad($yy, 2, '0', STR_PAD_LEFT);
        	return "{$mm}{$yy}";
    }
    
    public function isExpired()
    {
        	if ($this->deleted) {return true;}
        	
        	$mm = substr(trim($this->expire_month), 0, 2);
        	$mm = str_pad($mm, 2, '0', STR_PAD_LEFT);
        	$yy = substr(trim($this->expire_year), 2, 2);
        	$yy = str_pad($yy, 2, '0', STR_PAD_LEFT);
        	
        	if (date('ym') > "{$yy}{$mm}") {
        		return true;
        	}
        	return false;
    }


    private function encryption($string)
    {
        // Store the cipher method
        $ciphering = "AES-128-CTR";
        $options = 0;

// Use openssl_encrypt() function to encrypt the data
       return  openssl_encrypt($string, $ciphering,
            DES_KEY_NEW, $options, DES_IV);

    }

    private function decryption($encryption)
    {

        // Store the cipher method
        $ciphering = "AES-128-CTR";
        $options = 0;
// Use openssl_decrypt() function to decrypt the data
         return openssl_decrypt ($encryption, $ciphering,
            DES_KEY_NEW, $options, DES_IV);


    }
}
