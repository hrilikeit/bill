<?php

final class Fan_Subscription extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Fan = parent::AssignOne;
    public $Entertainer = parent::AssignOne;
    public $Club = parent::AssignOne;
    public $active_time = parent::Time;
    public $expire_time = parent::Time;
    public $active = parent::Boolean;
    public $auto_renew = parent::Boolean;
    public $banned_until_time = parent::Time;


    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
    }

    public function delete_Job() {parent::delete();}

    public function copy_Job() {return $this->copy();}

    public function getShortExpireDate()
    {
    	return date(SHORT_DATE_FORMAT, strtotime($this->expire_time));
    }

    public function getAvatarLink()
    {
		$avatar = $this->Entertainer->Member->getAvatarHTML(Member::AVATAR_LITTLE);
		$online = $this->Entertainer->isOnline() ? ' - <strong style="color: #6d6">Online</strong>' : '';
    	$entertainer_link = "<div class=\"avatar_link\">{$this->Entertainer->name}{$online}<br/>"
    		. $avatar
    		. '</div>';
    	return $entertainer_link;
    }

    public function getFromMember()
    {
        $member_id = $this->from_Member_id;
        $Member = new Member($member_id);
        return $Member;
    }

    public function getReport()
    {
    	$writer = new StaysailWriter('fan_report');
    	$Fan = $this->Fan;
    	$start_date = date('F j, Y', strtotime($this->active_time));
    	//$online = $Fan->isOnline() ? ' - Online' : '';
        $online = $Fan->isOnline() ? '<span class="onlineFan"> - Online</span>' : '';



        $sender = $this->getFromMember();
        $avatar = $sender->getAvatarHTML(Member::AVATAR_TINY);

        $buttons = "<a href=\"?mode=Message&job=compose&target={$Fan->Member->id}\" class=\"button\">Write Message</a>";
       // $buttons2 = "<a href=\"?mode=Message&job=compose&target={$Fan->Member->id}\" class=\"button\" style='margin-left: 15px;'>Delete Fan</a>";
        $name = $Fan->name . $online;


        //$writer->h2($Fan->name . $online);
    	//$writer->p("<a href=\"?mode=Message&job=compose&target={$Fan->Member->id}\" class=\"button\">Write Message</a>");
    	//$writer->p("Fan Since {$start_date}");

        $paid_renew = $this->auto_renew;

        if ($paid_renew == 1){
            $paid_answ = 'Paid ';
            $html = <<<__END__
        <div class="fantab">
            <div class="fanavatar" style="margin-top: 10px;">{$avatar}</div>
    		<h1 style="margin-top: 10px;">{$name}</h1>
    		<div class="fanpaidyes">Status: {$paid_answ}</div>
    		<div class="fansince">Fan Since {$start_date}</div>
    		<div class="fanbutton">{$buttons}</div>
    	</div>
 
    
<style>
        .onlineFan {
            color: green;
        }
    </style>
__END__;
        }
        else if($paid_renew == 0){
            $paid_answ = 'Not Paid';
            $html = <<<__END__
        <div class="fantab">
    		<h1 style="margin-left: 5px;margin-top: 5px;">{$avatar} {$name}</h1>
    		<div class="fanpaidno">Status: {$paid_answ}</div>
    		<div class="fansince">Fan Since {$start_date}</div>
    		<div class="fanbutton">{$buttons}</div>
    	</div>
 
    
<style>
        .onlineFan {
            color: green;
        }
    </style>
__END__;
        }


        $writer->addHTML($html);

    	return $writer->getHTML();
    }

    public function isBanned()
    {
        $now = time();
        if ($this->banned_until_time != null) {
            $banned_until = strtotime($this->banned_until_time);
            if ($now > $banned_until) {
                $this->banned_until_time = null;
                $this->save();
                return false;
            }
            return true;
        }
        return false;
    }

    public function getFormattedBanLiftTime()
    {
    	$banned_until = strtotime($this->banned_until_time);
    	$format = date('F j, Y h:ia', $banned_until);
    	return $format;
    }
}