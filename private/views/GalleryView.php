<?php

class GalleryView
{
    private $Member;      // Who's logged in
    private $Entertainer; // Whose Gallery we're looking at

    public function __construct(Member $Member)
    {
        $this->Member = $Member;
        if (StaysailIO::session('Entertainer.id')) {
            $this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
        } else {
            $this->Entertainer = $this->Member->getAccountOfType('Entertainer');
        }
    }

    public function getHTML()
    {
        $writer = new StaysailWriter();
//        $writer->h1($this->Entertainer->name . "'s Gallery Store");
        $job = StaysailIO::get('job');

        if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
            if($job == 'videos'){
                $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=add_video\" class=\"button\">Add New Video</a> <a href=\"?mode=EntertainerGallery&job=editVideo\" class=\"button\">Edit or Remove a Video</a>");
            }
            elseif ($job == 'videos_store'){
                $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=add_video_store\" class=\"button\">Add New Video To Store</a> <a href=\"?mode=EntertainerGallery&job=editVideo\" class=\"button\">Edit or Remove a Video From Store</a>");
            }
            else{
                $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=add\" class=\"button\">Add New Image</a> <a href=\"?mode=EntertainerGallery&job=edit\" class=\"button\">Edit or Remove an Image</a> <a href=\"?mode=EntertainerGallery&job=choose_avatar\" class=\"button\">Create Your Headshot</a>");
            }
        } elseif ($this->Member->getRole() == Member::ROLE_FAN) {
            if($job == 'videos'){
                $writer->h1($this->Entertainer->name . "'s Gallery");
            }
            elseif ($job == 'videos_store'){
                $writer->h1($this->Entertainer->name . "'s Gallery Store");
            }
            else{
                $writer->h1($this->Entertainer->name . "'s Gallery");
            }
//            if($job == 'videos'){
//                $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=purchase_video\" class=\"button\">Buy a Video</a>");
//            }else{
//                $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=purchase\" class=\"button\">Buy An Image</a>");
//            }
        }

        if (!$this->Entertainer) {
            return '';
        }
        $Fan = $this->Member->getAccountOfType('Fan');

        //	$gallery = $this->Entertainer->getGallery();
        if ($job == 'videos'){
            $gallery = $this->Entertainer->getVideosFan();
        }
        else if($job == 'videos_store'){
            $gallery = $this->Entertainer->getVideoStore();
        }
        else {
            $gallery = $this->Entertainer->getImageGallery();
        }

        $list = array();
        foreach ($gallery as $Library)
        {
            $options = '';
            if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
                $options .= StaysailWriter::makeJobLink('Edit', 'EntertainerGallery', 'edit_image', $Library->id);
            }
            if ($this->Member->getRole() == Member::ROLE_FAN) {
                $options .= StaysailWriter::makeJobLink('Buy', 'EntertainerGallery', 'buy_image', $Library->id);
            }
            if(($Library->File_Type_id == 3 || $Library->File_Type_id == NULL) && $job == '' ){
                $thumb_url = $Library->getThumbnailURL();
                $full_url = $Library->getFullSizeURL();
                if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
                    if($Library->placement == 'web' || $Library->Member_id ==$this->Member->id ){
                        $list[] = "<p class=\"post_desc\">$Library->name</p><a target='_blank' href=\"{$thumb_url}\"><img height=\"120\" src=\"{$thumb_url}\" /></a>";
                    }else{
                        $list[] = "<p class=\"post_desc\">$Library->name</p><a target='_blank' href=\"?mode=Purchase&job=purchase&type=Library&id=".$Library->id."\"><div class='roll-cover'><img height=\"120\" src=\"{$thumb_url}\" /></div></a>";
                    }
                }elseif($this->Member->getRole() == Member::ROLE_FAN){
                    if($Library->placement == "web" || $Library->placement == "sale" || $Library->placement == "subscribed"){
                        $html = "<div class='gallery_item'><p class=\"post_desc\">$Library->name</p>
                                   <a href=\"javascript:void(0);\">
                                       <div class='roll-cover'>
                                       <a target='_blank' href=\"$thumb_url\">
                                           <img height=\"120\" src=\"{$thumb_url}\"/>
                                       </a>
                                       </div>
                                   </a>";
                        if ($Library->placement == "sale") {
                            $html .= "<a  class='gallery_item_link' target='_blank' href=\"?mode=Purchase&job=purchase&type=Library&id=".$Library->id."\">
                                    <div class='profile_lock'>
                                        <span class='profile_lock_text'>
                                            Unlock post for &#36;{$Library->price}
                                        </span>
                                    </div>
                                </a></div>";
                        }
                        elseif ($Library->placement == "subscribed" && $Fan && !$Fan->isSubscribedTo($this->Entertainer)){
                            $html .= "<a  class='gallery_item_link' target='_blank' href=\"?mode=EntertainerProfile&entertainer_id=".$this->Entertainer->id."\">
                                        <div class='profile_lock'>
                                            <span class='profile_lock_text'>
                                                Subscribe to see post
                                            </span>
                                        </div>
                                      </a></div>";
                        }
                        $list[] = $html;
                    }
                }
            }
            $Post = $Library->getLibraryPost();
            if($Library->File_Type_id == 4 && $job == 'videos' && $Post['active'] != 0 || $Library->File_Type_id == 4 && $job == 'videos_store' && $Post['active'] != 0){
                $video = $Library->getWebVideoHTML();
                if($this->Member->getRole() == Member::ROLE_FAN){
                    $type = $Library->mime_type;
                    if(in_array($type, [ 'video/quicktime',  'video/x-msvideo' , ''])) {
                        $type = 'video/mp4';
                    }
                    $html = '<div class="gallery_item" style="width: auto"><a href="?mode=Purchase&job=purchaseVideo&type=Library&id='.$Library->id.'">
                                    <div class="roll-cover">
                                        <video controls width="320" height="240" preload="auto">
                                            <source src="'.$video.'" type="'.$type.'">
                                            your browser does not support this tag.
                                        </video>
                                    </div>
                                </a>';
                    if ($Library->placement == "sale" || $Library->placement == "shop") {
                        $html .= "<a class='gallery_item_link' target='_blank' href=\"?mode=Purchase&job=purchaseVideo&type=Library&id=".$Library->id."\">
                                <div class='profile_lock'>
                                    <span class='profile_lock_text'>
                                        Unlock post for &#36;{$Library->price}
                                    </span>
                                </div>
                            </a></div>";
                    }
                    elseif ($Library->placement == "subscribed"  && $Fan && !$Fan->isSubscribedTo($this->Entertainer)){
                        $html .= "<a class='gallery_item_link' target='_blank' href=\"?mode=EntertainerProfile&entertainer_id=".$this->Entertainer->id."\">
                                    <div class='profile_lock'>
                                        <span class='profile_lock_text'>
                                            Subscribe to see post
                                        </span>
                                    </div>
                                  </a></div>";
                    }
                }

                $list[] = $html;
                if($this->Member->getRole() == Member::ROLE_ENTERTAINER){
                    $list[] = '<video controls width="320" height="240" preload="auto">
                            <source src="'.$video.'" type="'.$type.'">
                            your browser does not support this tag.
                        </video>';
                }
            }
        }
        $writer->ul($list, '" id="gallery');
        return $writer->getHTML();
    }
}
