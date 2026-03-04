<?php

final class Post extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $post_time = parent::Time;
    public $active = parent::Boolean;
    public $Post = parent::AssignOne;
    public $content = parent::Text;
    public $Library = parent::AssignOne;
    public $placement = parent::Enum;


    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
    }

    public function delete_Job()
    {
        parent::delete();
    }

    public function copy_Job()
    {
        return $this->copy();
    }

    public function getVideoShopHTML($entityId)
    {
        $content = '<div class="grid-item">';
        $Library = new Library($this->Library_id);
        $fanHome = new FanHome();
        $video = $Library->getWebVideoHTML(true);
        $avatar = $this->Member->getAvatarHTML(Member::AVATAR_TINY);
//    		if($Library->mime_type == 'video/quicktime' || $Library->mime_type == 'video/x-msvideo'){
//    			$content = '<embed src="'.$video.'" Pluginspage="https://support.apple.com/quicktime" width="320" height="240" CONTROLLER="true" LOOP="false" AUTOPLAY="false" type="'.$Library->mime_type.'" name="Video"></embed>';
//    		}else{
        $type = $Library->mime_type;

        if (in_array($Library->mime_type, ['video/quicktime', 'video/x-msvideo', ''])) {
            $type = 'video/mp4';
        }
        $timeFormat = "00:00:00";
        if ($Library->video_time){
            $seconds = $Library->video_time;
            $hours = floor($seconds / 3600);
            $mins = floor($seconds / 60 % 60);
            $secs = floor($seconds % 60);
            $timeFormat = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        }

        $content .= '<a href="?mode=FanHome&job=video_store_single&id=' . $Library->id . '">
                         <video controls>
                             <source src="' . $video . '" type="' . $type . '">
                             Your browser does not support the video tag.
                         </video>
                     </a>
                     <div class="video-info">
                     <span class="video_time">' . $timeFormat . '</span>
                         <div class="video-info-box-1">
                             ' . $avatar . '
                             <h2 class="video-title">' . $Library->name . '</h2>
                         </div>
                         <div class="video-info-box-2">
                             <p class="video-price">$<span>' . $Library->price . '</span></p>
                             <button class="buy-button">Buy</button>
                         </div>
                     </div>';

        $opt = '';
        $entity = new Member($entityId);
        $payment_methods = $entity->getPaymentMethods();
        if (sizeof($payment_methods)) {
            $options = array();
            foreach ($payment_methods as $Payment_Method) {
                $options[$Payment_Method->id] = $Payment_Method->name;
            }

            foreach ($options as $key=>$option){
                $opt .= "<option value='$key'>$option</option>";
            }
        }

        $content .= "
                <div id='payment_modal' class='modal'>
                
                  <div class='modal-content'>
                    <span class='close'>&times;</span>
                    <form method='post' class='profile_lock_form' action='?mode=Purchase&job=verify' onsubmit='return uniValidate(this)'>
                        <p>pay </p>
                        <input type='hidden' name='type' value='video_store'>
                        <input type='hidden' name='id' value='$Library->id'>
                        <select name='payment_method_id'>
                            $opt
                        </select>
                        <div class='field'><a href='?mode=FanHome&job=new_payment_method' class='spaced button'>Add Payment Method</a></div>
                        <div class='field'><div class='submit'><input type='submit' value='Purchase Video'></div></div>
                     </form>
                  </div>
                </div>
            ";
//						}
//        if ($Library->description) {
//            $content .= "<p>{$Library->description}</p>";
//        }
        return $content . '</div>';
    }
    public function getHTML()
    {
        $author = $this->Member->name;
        $time = date('l, F jS g:ia', strtotime($this->post_time));

        $current_Member = new Member(StaysailIO::session('Member.id'));

        $placement = "";
        if ($this->placement == 'web'){
            $placement = '&#127760';
        }
        elseif ($this->placement == 'subscribed'){
            $placement = '&#128101';
        }
        elseif ($this->placement == 'sale'){
            $placement = '&#128178';
        }
        elseif ($this->placement == 'shop'){
            $placement = '&#128722';
        }

        if (is_null($this->Library)) {
            $hide = 'height:auto;margin-bottom:20px';
        }else{
            $hide = '';
        }

        if ($current_Member->getRole() == Member::ROLE_ENTERTAINER) {
            if ($this->Library && in_array($this->Library->mime_type, ['video/quicktime', 'video/x-msvideo', ''])) {
                $editType = 'edit_video';
            }
            else{
                $editType = 'edit_image';
            }
            $commands = "{$placement} <div class=\"commands-div\">
                                        <div class=\"delete\">
                                            <a onclick=\"return confirm('Are you sure?');\" href=\"?mode=EntertainerProfile&job=delete_post&id={$this->id}\">
                                                <img src=\"/site_img/icons/close_16.png\">
                                            </a>
                                         </div>
                                         <div class=\"edit\">
                                            <a href=\"?mode=EntertainerGallery&job=$editType&id=$this->Library_id\">
                                                <img src=\"/site_img/icons/pencil_32.png\">
                                            </a>
                                        </div>
                                     </div>";
        } else {
            $commands = "{$placement}";
        }

        if (!$this->Post) {
            $responses = $this->getResponses();
            $avatar = $this->Member->getAvatarHTML(Member::AVATAR_TINY);
            $writer = new StaysailWriter('main_post');
            $writer->addHTML("<div class=\"header\">{$avatar}");
            $writer->addHTML("<div><strong>{$author} {$commands}</strong></div>");
            $writer->addHTML("<div>{$time}</div></div>");
            $writer->addHTML("<div style='{$hide}' class=\"content\">");
            $Entertainer = $this->Member->getAccountOfType('Entertainer');

            if ($this->placement == 'sale' || $this->placement == 'subscribed' || $this->placement == 'shop') {
                $Fan = $this->Member->getAccountOfType('Fan');
                $currentMemberFan = $current_Member->getAccountOfType('Fan');
                $Library_Obj = new Library($this->Library_id);
                if(($Fan && $Fan->hasPurchased($Library_Obj) && $this->placement == 'sale' && $this->placement == 'shop')
                    || ($currentMemberFan && $currentMemberFan->hasPurchased($Library_Obj) && $this->placement == 'sale')
                    || ($currentMemberFan && $currentMemberFan->hasPurchased($Library_Obj) && $this->placement == 'shop')
                    || ($currentMemberFan && $currentMemberFan->isSubscribedTo($Entertainer) && $this->placement == 'subscribed')
                    || $current_Member->getRole() == Member::ROLE_ENTERTAINER
                ) {
                    $thumb_url = $Library_Obj->getThumbnailURL();
                    $writer->addHTML("
                    {$this->content}");
                }
                else {
                    if ($Library_Obj->File_Type_id == 4) {
                        if ($current_Member->getRole() == Member::ROLE_FAN) {
                                $writer->addHTML( $this->content.
                                    ($this->placement == 'sale' ?
                                    "<a href=\"?mode=Purchase&job=purchaseVideo&type=Library&id={$this->Library_id}\"><div class='video_lock'><span class='profile_lock_text'>Unlock post for &#36;{$Library_Obj->price}</span></div></a>"
                                    : "<a >
                                        <div class='profile_lock'>
                                            <span class='profile_lock_text'>
                                                Subscribe to see post
                                            </span>
                                        </div>
                                    </a>"
                                    ));
                            if ($this->placement == 'sale' || $this->placement == 'shop'){
//                                $writer->addHTML($this->content."<a href=\"?mode=Purchase&job=purchaseVideo&type=Library&id={$this->Library_id}\"><div class='video_lock'><span class='profile_lock_text'>Unlock post for &#36;{$Library_Obj->price}</span></div></a>");


//
//
//                                $realPath = DATAROOT ."/private/library/$this->Library_id";
//                                foreach(['mp4','mov', 'MOV'] as $format) {
//                                    if (file_exists($realPath . '.' . $format)) {
//                                        $realPath = $realPath . '.' . $format;
//                                    };
//                                }
//                                $ext = pathinfo($realPath, PATHINFO_EXTENSION);
//
//
//
//
//                                $savePath = DATAROOT ."/private/library/trailer/$this->Library_id.$ext";
//
//                                $writer->addHTML($this->content."<video controls='' width='320' height='240' preload='none'>
//  								<source src='$savePath' type='video/mp4'>
//  								your browser does not support this tag.
//							</video>");
                            }
                            else{
                                $writer->addHTML($this->content."<a >
                                        <div class='profile_lock'>
                                            <span class='profile_lock_text'>
                                                Subscribe to see post
                                            </span>
                                        </div>
                                    </a>
                                ");
                            }
                        }
                        else {
                            $writer->addHTML($this->content . ($this->placement == 'sale' ?
                                    "<a href=\"?mode=Purchase&job=purchaseVideo&type=Library&id={$this->Library_id}\"><div class='video_lock'><span class='profile_lock_text'>Unlock post for &#36;{$Library_Obj->price}</span></div></a>"
                                    : ''));
                        }
                    } else {
                        $sliderPhotoIds = $Library_Obj->getGalleryPhotos();
                        if ($sliderPhotoIds) { //@TODO add ROW
                                $content = '<div  class="splide">
                                            <div class="splide__track">
                                                    <ul class="splide__list">';
                                foreach ($sliderPhotoIds as $sliderPhoto) {
                                    $Library = new Library($sliderPhoto[0]);
                                    $content .= '<li class="splide__slide">' . $Library->getWebHTML('mySlides') . '</li>';
                                }
                                $content .= '</ul></div></div>';
                                $writer->addHTML($content .
                                    ($this->placement == 'sale' ? "
                                <a href=\"?mode=Purchase&job=purchase&type=Library&id={$this->Library_id}\">
                                    <div class='profile_lock'>
                                        <span class='profile_lock_text'>
                                            Unlock post for &#36;{$Library_Obj->price}
                                        </span>
                                    </div>
                                </a>" :
                                        "  <a >
                                        <div class='profile_lock'>
                                            <span class='profile_lock_text'>
                                                Subscribe to see post
                                            </span>
                                        </div>
                                    </a>"
                                    ));
                        }
                        else {
                            if ($current_Member->getRole() == Member::ROLE_FAN) {
                                $html = $this->content;
                                $writer->addHTML($this->placement == 'sale' ? $html . "
                                    <a href=\"?mode=Purchase&job=purchase&type=Library&id={$this->Library_id}\">
                                        <div class='profile_lock'>
                                            <span class='profile_lock_text'>
                                                 Unlock post for &#36;{$Library_Obj->price}
                                            </span>
                                        </div>
                                    </a>" : $html . "
                                    <a >
                                        <div class='profile_lock'>
                                            <span class='profile_lock_text'>
                                                Subscribe to see post
                                            </span>
                                        </div>
                                    </a>");
                            }
                            else {
                                $html =$this->content;
                                if ($this->placement == 'sale') {
                                    $html .= "
                                    <a href=\"?mode=Purchase&job=purchase&type=Library&id={$this->Library_id}\">
                                        <div class='profile_lock'>
                                            <span class='profile_lock_text'>
                                                Unlock post for &#36;{$Library_Obj->price}
                                            </span>
                                        </div>
                                    </a>";
                                }
                                $writer->addHTML($html);
                            }
                        }
                    }
                }
            }
            else {
                $writer->addHTML("{$this->content}");
            }
            $writer->addHTML("</div>");
            //$writer->addHTML("<div class=\"content\">{$this->content}</div>");
            $writer->addHTML($this->getCommentBar(sizeof($responses)));
            $hide = $this->id != StaysailIO::get('id') ? "style=\"display:none\"" : '';
            $writer->addHTML("<div {$hide} id=\"post{$this->id}\">");
            foreach ($responses as $Post) {
                $writer->draw($Post);
            }
            $writer->addHTML($this->getCommentInput());
            $writer->addHTML("</div>");
        } else {
            $writer = new StaysailWriter('sub_post');
            $writer->addHTML("<div>{$commands}<strong>{$author}</strong> {$this->content}</div>");
            $writer->addHTML("<div>{$time}</div>");
        }
        return $writer->getHTML();
    }

    public function getResponses()
    {
        $filters = array(new Filter(Filter::Match, array('Post_id' => $this->id, 'active' => 1)),
            new Filter(Filter::Sort, 'post_time ASC'),
        );
        $responses = $this->_framework->getSubset('Post', $filters);
        return $responses;
    }

    public function bumpBy($Member)
    {
        $Entertainer = $this->Member->getAccountOfType('Entertainer');

        if ($Entertainer) {
            $Fan = $Member->getAccountOfType('Fan');
            if (($Fan and $Fan->isSubscribedTo($Entertainer) or $Member->id == $this->Member->id)) {
                $filters = array(new Filter(Filter::Match, array('Post_id' => $this->id, 'Member_id' => $Member->id)));
                $bumps = $this->_framework->getSubset('Post_Bump', $filters);
                if (count($bumps)) {
                    $Post_Bump = new Post_Bump($bumps[0]->id);
                    $Post_Bump->delete_Job();
                    return;
                } // The given Member has already bumped the post
                $Post_Bump = new Post_Bump();
                $Post_Bump->update(array('Post_id' => $this->id, 'Member_id' => $Member->id));
                $Post_Bump->save();
            }
        }
    }

    public function countBumps()
    {
        $filters = array(new Filter(Filter::Match, array('Post_id' => $this->id)));
        $bumps = $this->_framework->getSubset('Post_Bump', $filters);
        return count($bumps);
    }

    public function getCommentBar($response_count)
    {
        $bump_count = $this->countBumps();
        $html = <<<__END__
    	<div class="comment_opener">
    	<a onclick="bumpPost({$this->id})" title="Like This" style="font-size: 12px">&#128151</a> &middot; 
    	<a onclick="openComments({$this->id});openCommentInput({$this->id});" style="font-size: 12px">&#128172</a> &middot; 
    	<a href="/?mode=Purchase&job=purchase&type=Tip" class=\"button green\" style="font-size: 12px;text-decoration: none;">&#128178</a>
    	<span class="right"><img src="/site_img/icons/like.png" /><span id="bumps{$this->id}">{$bump_count}</span>&nbsp;
    	<a onclick="openComments({$this->id})" title="View Comments"><img src="/site_img/icons/comment_32.png" width="16" /> {$response_count}</a></span>
    	</div>
__END__;
        return $html;
    }

    public function getCommentInput()
    {
        $html = <<<__END__
    	<div style="display:none" class="comment_input" id="comment_input{$this->id}">
    	<form method="post" id="response{$this->id}" action="?mode=EntertainerProfile&job=post&id={$this->id}" />
    	<textarea id="textarea{$this->id}" name="name"></textarea>
    	</form>
		<div class="post_button"><a href="#" onclick="document.getElementById('response{$this->id}').submit();" >Post</a></div>
    	</div>
__END__;
        return $html;
    }

    public function getFanReplyForm()
    {
        $form = new StaysailForm();
        $form->setSubmit('Comment')
            ->setPostMethod()
            ->setDefaults(array('post_id' => $this->id))
            ->setAction('/index.php?mode=EntertainerSite&job=post_comment')
            ->addField(StaysailForm::Hidden, '', 'post_id')
            ->addField(StaysailForm::Text, 'Comment', 'comment');
        return $form;
    }

    public function postReply(Member $Member, $content, $Entertainer = null)
    {
        if (!trim($content)) {
            return false;
        }

        if ($Member->getRole() == Member::ROLE_FAN) {
            // A Fan must be subscribed
            $Fan = $Member->getAccountOfType('Fan');
            if (!$Entertainer) {
                $Entertainer = $this->Member->getAccountOfType('Entertainer');
            }
            if (!$Fan->isSubscribedTo($Entertainer)) {
                return false;
            }
            StaysailIO::setSession('Entertainer.id', $Entertainer->id);
        } elseif ($Member->getRole() == Member::ROLE_ENTERTAINER) {
            // An Entertainer must be the creator of the original post
            if ($this->id and $Member->id != $this->Member->id) {
                return false;
            }
        }

        $Post = new Post();
        $data = array('post_time' => date('Y-m-d H:i:s'),
            'active' => 1,
            'Member_id' => $Member->id,
            'Post_id' => $this->id,
            'content' => $content,
            'placement' => $this->placement,
        );
        $Post->update($data);
        $Post->save();
    }

    public function belongsTo(Member $Member)
    {
        // The post can be deleted if it was written by the Member
        if ($this->Member && $Member->id == $this->Member->id) {
            return true;
        }

        // The post can be deleted if its parent was written by the Member
        if ($this->Post and $Member->id == $this->Post->Member->id) {
            return true;
        }

        return false;
    }

    public static function createLibrary($file, $Member, $Entertainer, $galleryId = null)
    {
        $invitationLink = 'https://'.$_SERVER["SERVER_NAME"];
        $fanUrl = $Entertainer->fan_url;
        if ($fanUrl){
            $invitationLink = $invitationLink . '/' .$fanUrl ;
        }

        $Library = new Library();
//        echo '<pre>';
//        var_dump($Library);
        $Library->placement = StaysailIO::post('placement');
        $Library->admin_status = 'pending';
        $Library->gallery_id = $galleryId;
        $Library->Member = $Member;

            // TODO need refactor
//        $Library->is_public = null;

//        echo '<pre>';
//        var_dump($Library);
        $Library->save(); // Save here to get ID
        $Library->image = $Library->uploadFile('image', $Library->id, $file, $invitationLink);

//TODO
//        var_dump($Library);

        $Library->size = StaysailIO::getFileInfo('image', 'size', $file);
        $Library->mime_type = StaysailIO::getFileInfo('image', 'type', $file);
        if ($Library->placement == 'sale') {
            $image_price = StaysailIO::post('prices');
            $Library->price = (int)$image_price;
        }
        $Library->File_Type_id = 3;
        $Library->save();
        
        return self::updateLibrary($Library, $Entertainer, $galleryId ? true : false);
    }

    public static function updateLibrary($Library, $Entertainer, $slider = false)
    {
        $fields = array('name', 'description');
        $Library->updateFrom($fields);
        if (!$Library->placement) {
            $Library->placement = 'web';
        }
        $Library->save();

        // Notify fans according to the entertainer's marketing preferences
        $Entertainer->performImageMarketing();

        //if ($Library->placement == 'web') {
        if ($slider) {

            $content = '<li class="splide__slide">' .
                $Library->getWebHTML()
                . '<a target="_blank" class="view_in_new_page" href="'.$Library->getThumbnailURL().'"> </a></li>';
        } else {
          //  $content = '<p class="post_desc">' . $Library->name . '</p>' .
          $content =
                $Library->getWebHTML().'<a target="_blank" class="view_in_new_page" href="'.$Library->getThumbnailURL().'"></a>';
            if ($Library->description) {
                $content .= "<p>{$Library->description}</p>";
            }
        }

        return ['Library' => $Library, 'content' => $content];
    }

    public static function uploadImage($Member, $Entertainer, $library_id = null)
    {
        $libraryData = ['Library' => null, 'content' => ''];
        $content = '';
        $htmlDataStart = '';
        $htmlDataEnd = '';
        if (StaysailIO::post('name')){
            $content .= '<p class="post_desc">' . StaysailIO::post('name') . '</p>';
        }
        if (!$library_id) {
            // A new image has fields that can't be changed by the Entertainer later:
            if ((isset($_FILES['image']['name'])) && is_array($_FILES['image']['name'])) {
                $galleryId = count($_FILES['image']['name']) > 1 ? substr(md5(mt_rand()), 0, 7) : null;
                $files = [];
                foreach ($_FILES['image'] as $fileOptionKey => $data) {
                    foreach ($data as $dataKey => $val) {
                        if (!isset($files[$dataKey])) {
                            $files[$dataKey] = [];
                        }
                        $files[$dataKey][$fileOptionKey] = $val;
                    }
                }

                if(count($_FILES['image']['name']) > 1){
                    $htmlDataStart = '<div  class="splide">
                              <div class="splide__track">
                                    <ul class="splide__list">';
                    $htmlDataEnd = '</ul></div></div>';
                }

                $content .= $htmlDataStart;
                foreach ($files as $file) {
                    $libraryData = self::createLibrary($file, $Member, $Entertainer, $galleryId);
                    $content .= $libraryData['content'];
                }
                $libraryData['content'] = $content . $htmlDataEnd;
            } else {
                $libraryData = self::createLibrary($_FILES['image'], $Member, $Entertainer);
            }

        } else {
            $Library = new Library($library_id);
            if (!$Library->belongsTo($Member)) {
                return false;
            }
            $libraryData = self::updateLibrary($Library, $Entertainer);
        }
        $Library = $libraryData['Library'];
        $content = $libraryData['content'];
        $Post = new Post();
        $update = array('Member_id' => $Member->id,
            'post_time' => StaysailIO::now(),
            'active' => 1,
            'content' => $content,
            'Library_id' => $Library->id,
            'placement' => $Library->placement
        );
        $Post->update($update);
        $Post->save();

        /*}else{
            header('Location: /?mode=EntertainerGallery');
            exit;
        }*/
        return $Library;
    }

    public static function uploadVideo($Member, $library_id = null)
    {
        $video_price = 0;
        $content = '';
        $all = 0;

        if (!$library_id) {
            // A new image has fields that can't be changed by the Entertainer later:
            $Library = new Library();
            $Library->placement = StaysailIO::post('placement');
            $Library->admin_status = 'pending';
            $Library->gallery_id = NULL;
            $Library->Member = $Member;
            $Library->save(); // Save here to get ID
            $Library->image = $Library->uploadFile('video', $Library->id);
            $Library->size = StaysailIO::getFileInfo('video', 'size');
            $Library->mime_type = StaysailIO::getFileInfo('video', 'type');

            if ($Library->placement == 'sale' || $Library->placement == 'shop') {
                $video_price = StaysailIO::post('prices');
                $Library->price = (int)$video_price;

                $basePath = DATAROOT . "/private/library/{$Library->id}";
                $realPath = null;
                $ext = null;

                foreach (['mp4', 'mov', 'MOV'] as $format) {
                    if (file_exists($basePath . '.' . $format)) {
                        $realPath = $basePath . '.' . $format;
                        $ext = $format;
                        break;
                    }
                }
                if($realPath && file_exists($realPath)) {
                    $getID3 = new getID3();
                    $info = $getID3->analyze($realPath);
                    getid3_lib::CopyTagsToComments($info);

                    if (!empty($info['playtime_seconds'])) {
                    $all = (int)ceil($info['playtime_seconds']);
                    }
                }
            }
            $Library->File_Type_id = 4;
            $Library->save();
        } else {
            $Library = new Library($library_id);
            if (!$Library->belongsTo($Member)) {
                return false;
            }
        }
        $fields = array('name', 'description');
        $Library->updateFrom($fields);
        $Library->video_time = isset($all) ? $all : 0 ;
        if (!$Library->placement) {
            $Library->placement = 'web';
        }
        $Library->save();

        // Notify fans according to the entertainer's marketing preferences
        //$this->Entertainer->performImageMarketing();

        //if ($Library->placement == 'web') {

        $video = $Library->getWebVideoHTML();
//    		if($Library->mime_type == 'video/quicktime' || $Library->mime_type == 'video/x-msvideo'){
//    			$content = '<embed src="'.$video.'" Pluginspage="https://support.apple.com/quicktime" width="320" height="240" CONTROLLER="true" LOOP="false" AUTOPLAY="false" type="'.$Library->mime_type.'" name="Video"></embed>';
//    		}else{
        $type = $Library->mime_type;

        if (in_array($Library->mime_type, ['video/quicktime', 'video/x-msvideo', ''])) {
            $type = 'video/mp4';
        }
        if (StaysailIO::post('name')){
            $content .= '<p class="post_desc">' . StaysailIO::post('name') . '</p>';
        }
        $content .= '<video controls width="320" height="240" preload="auto">
  								<source src="' . $video . '" type="' . $type . '">
  								your browser does not support this tag.
							</video>';
//						}
        if ($Library->description) {
            $content .= "<p>{$Library->description}</p>";
        }

        $Post = new Post();
        $update = array('Member_id' => $Member->id,
            'post_time' => StaysailIO::now(),
            'active' => 1,
            'content' => $content,
            'Library_id' => $Library->id,
            'placement' => $Library->placement
        );
        $Post->update($update);
        $Post->save();

        /*}else{
            header('Location: /?mode=EntertainerGallery');
            exit;
        }*/
    }
}
