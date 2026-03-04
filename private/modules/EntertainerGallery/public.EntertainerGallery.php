<?php

use function Couchbase\defaultDecoder;

require '../private/views/GalleryView.php';

class EntertainerGallery extends StaysailPublic
{
    protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Entertainer;
    public $Fan;
    public $valid;

    public function __construct($dbc = '')
    {
        $this->valid = false;
        $this->framework = StaysailIO::engage();
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        if (StaysailIO::session('Entertainer.id')) {
            $this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
        } else {
            $this->Entertainer = $this->Member->getAccountOfType('Entertainer');
        }
        if ($this->Member and $this->Entertainer) {
            $this->valid = true;
        }
    }

    public function getHTML()
    {
        $job = StaysailIO::get('job');
        $id = StaysailIO::get('id');
        $content_override = '';

        $map = Maps::getGalleryMap();
        if ($this->Entertainer->requiredFields() == true) {
            $this->Member->requiredEmailVerify();
        }

        if ($this->Member->getAccountType() != 'Fan') {
            if (!Member_Docs::hasMemberDocs($this->framework, $this->Member->id)) {
                header("Location:?mode=EntertainerProfile&job=update_bio");
                exit;
            }
            if ($this->Entertainer->checkContract() == false) {
                header("Location:?mode=EntertainerProfile&job=update_bio");
                exit;
            }
        }
        switch ($job) {
            case 'add':
                $content_override = $this->getUploadFormHTML();
                break;

            case 'edit':
                $content_override = $this->getEditHTML();
                break;

            case 'editVideo':
                $content_override = $this->getEditVideoHTML();
                break;

            case 'editVideoStore':
                $content_override = $this->getEditVideoStoreHTML();
                break;

            case 'edit_image':
                $content_override = $this->editImage($id);
                break;

            case 'edit_video':
                $content_override = $this->editVideo($id);
                break;

            case 'edit_video_store':
                $content_override = $this->editVideoStore($id);
                break;

            case 'update_image':
                $this->postEditLibrary($id);
                $content_override = $this->editImage($id);
                break;

            case 'update_video':
                $this->postEditLibrary($id);
                $content_override = $this->editVideo($id);
                break;

            case 'rotate':
                $this->rotateImage($id, StaysailIO::get('dir'));
                $content_override = $this->editImage($id);
                break;

            case 'delete_image':
                $this->deleteImage($id);
                $content_override = $this->getEditHTML();
                break;

            case 'delete_video':
                $this->deleteImage($id);
                $content_override = $this->getEditVideoHTML();
                break;

            case 'delete_video_store':
                $this->deleteVideoStore($id);
                $content_override = $this->getVideoStoreHTML();
                break;

            case 'purchase':
                $content_override = $this->getPurchaseHTML();
                break;

            case 'purchase_video':
                $content_override = $this->getPurchaseVideoHTML();
                break;

            case 'post':
                Post::uploadImage($this->Member, $this->Entertainer);
                header('Location: /?mode=EntertainerProfile');
                exit;
                break;

            case 'choose_avatar':
                $content_override = $this->chooseAvatar();
                break;

            case 'upload_avatar':
                $content_override = $this->uploadAvatar();
                break;

            case 'crop_avatar':
                $content_override = $this->cropAvatar($id);
                break;

            case 'set_avatar':
                $content_override = $this->setAvatar($id);
                break;

            case 'videos':
                $Fan = $this->Member->getAccountOfType('Fan');
//                var_dump('Type');
//                var_dump($Fan);
//die();
                if (!$Fan) {
                    $content_override = $this->getVideoHTML();
                }
                break;

            case 'videos_store':
                $Fan = $this->Member->getAccountOfType('Fan');
                if (!$Fan) {
                    $content_override = $this->getVideoStoreHTML();
                }
                break;

            case 'add_video':
                $content_override = $this->getUploadVideoFormHTML();
                break;

            case 'add_video_store':
                $content_override = $this->getUploadVideoStoreFormHTML();
                break;

            case 'post_video':
                Post::uploadVideo($this->Member);
                header('Location: /?mode=EntertainerProfile');
                exit;
                break;

            case 'activate':
                $this->activate($id);
                header('Location: /?mode=EntertainerProfile');
                break;
        }

        $header = new HeaderView();
        $footer = new FooterView();
        $action = new ActionsView($this->Member);
//        $banner = new BannerAdsView();

        $containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
            new StaysailContainer('F', 'footer', $footer->getHTML()),
            new StaysailContainer('A', 'action', $action->getHTML()),
//            new StaysailContainer('B', 'banner', $banner->getHTML()),
        );

        $gallery = new GalleryView($this->Member);
        $content = $content_override ? $content_override : $gallery->getHTML();
        $containers[] = new StaysailContainer('C', 'content', $content);
        $layout = new StaysailLayout($map, $containers);
        return $layout->getHTML();
    }

    private function getUploadFormHTML()
    {
        $writer = new StaysailWriter();
        $placements = array('web' => 'Post to your page', 'sale' => 'For sale to Fans');
        $prices = array('5' => '$5', '10' => '$10', '15' => '$15', '20' => '$20', '25' => '$25');
        $upload = new StaysailForm('add-image-form');
        $upload->setJobAction('EntertainerGallery', 'post', '')
            ->setPostMethod()
            ->setSubmit('Add This Image')
            ->addField(StaysailForm::File_multiple, 'Image', 'image[]', 'required img_add multi_add', '', '', '', 'image/png, image/jpg, image/jpeg')
            //			   ->addField(StaysailForm::Select, 'Where should this image go?', 'placement', '', $placements)
            ->addHTML('<div style="width: 600px;
			height: 200px;
			display: flex;
			justify-content: flex-start;
			flex-wrap: wrap;" id="myImg_multi"></div>')
            ->addHTML('<div class="dollar_post_container">Where should this image go?
                                <div class="dollar_input" style="display: none">
                                    <input id="dollar_input_val" type="number" name="prices" placeholder="Dollar">
                                </div>
                                <select name="placement" id="dollar_select">
                                  <option value="web">&#127760</option>
                                  <option value="subscribed">&#128101</option>
                                  <option value="sale">&#128178</option>
                                </select>
                            </div>')
//			   ->addField(StaysailForm::Select, 'Image Price', 'prices', '', $prices)
            ->addField(StaysailForm::Line, 'Image Name', 'name', 'name_count')
            ->addHTML('<div class="counter"></div>')
            ->addField(StaysailForm::Text, 'Description', 'description')
            ->addHTML('<h1>Required Information</h1>')
            ->addHTML('<p>The following information is required for each person in this picture.  Click the Add Person button for each additional person in the picture.</p>')
            ->addHTML($this->getMetadataFields())
            ->addHTML('<p id="add_person_button"><a class="button" onclick="addPicturePerson()"/>Add Person</a></p>');

        $writer->h1('Add an Image')
            ->draw($upload);
        return $writer->getHTML();
    }

    private function getUploadVideoFormHTML()
    {
        $writer = new StaysailWriter();
        $placements = array('web' => 'Post to your page', 'sale' => 'For sale to Fans');
        $prices = array('10' => '$10', '20' => '$20', '30' => '$30', '40' => '$40', '50' => '$50', '60' => '$60', '70' => '$70', '80' => '$80', '90' => '$90', '100' => '$100');
        $upload = new StaysailForm();
        $upload->setJobAction('EntertainerGallery', 'post_video')
            ->setPostMethod()
            ->setSubmit('Add This Video')
            ->addField(StaysailForm::File, 'Video', 'video', 'required video_add', '', '', '', 'video/mp4,video/x-m4v,video/*')
            //			   ->addField(StaysailForm::Select, 'Where should this image go?', 'placement', '', $placements)
            ->addHTML('<div class="dollar_post_container">Where should this video go?
                                <div class="dollar_input" style="display: none">
                                    <input id="dollar_input_val" type="number" name="prices" placeholder="Dollar">
                                </div>
                                <select name="placement" id="dollar_select">
                                  <option value="web">&#127760</option>
                                  <option value="subscribed">&#128101</option>
                                  <option value="sale">&#128178</option>
                                </select>
                            </div>')
//			   ->addField(StaysailForm::Select, 'Image Price', 'prices', '', $prices)
            ->addField(StaysailForm::Line, 'Video Name', 'name', 'name_count')
            ->addHTML('<div class="counter"></div>')
//            ->addField(StaysailForm::Line, 'Video Name', 'name')
            ->addField(StaysailForm::Text, 'Description', 'description');

        $writer->h1('Add a Video')
            ->draw($upload);
        return $writer->getHTML();
    }

    private function getUploadVideoStoreFormHTML()
    {
        $writer = new StaysailWriter();
        $placements = array('web' => 'Post to your page', 'sale' => 'For sale to Fans');
        $prices = array('10' => '$10', '20' => '$20', '30' => '$30', '40' => '$40', '50' => '$50', '60' => '$60', '70' => '$70', '80' => '$80', '90' => '$90', '100' => '$100');
        $upload = new StaysailForm();
        $upload->setJobAction('EntertainerGallery', 'post_video')
            ->setPostMethod()
            ->setSubmit('Add This Video To Store')
            ->addField(StaysailForm::File, 'Video', 'video', 'required video_add', '', '', '', 'video/mp4,video/x-m4v,video/*')
            //			   ->addField(StaysailForm::Select, 'Where should this image go?', 'placement', '', $placements)
            ->addHTML('<div class="dollar_post_container">
                                <div class="dollar_input">
                                    <input id="dollar_input_val" type="number" name="prices" placeholder="Dollar" required>
                                </div>
                                <select name="placement" id="dollar_select">
                                  <option value="shop" selected>&#128722</option>
                                </select>
                            </div>')
//			   ->addField(StaysailForm::Select, 'Image Price', 'prices', '', $prices)
            ->addField(StaysailForm::Line, 'Video Name', 'name', 'name_count required')
            ->addHTML('<div class="counter"></div>')
//            ->addField(StaysailForm::Line, 'Video Name', 'name')
            ->addField(StaysailForm::Text, 'Description', 'description', 'required');

        $writer->h1('Add a Video To Store')
            ->draw($upload);
        return $writer->getHTML();
    }

    public function getImageChooser($job, $sale = false)
    {
        $writer = new StaysailWriter();

        if ($sale) {
            $gallery = $this->Entertainer->getSaleImages();
        } else {
            // $gallery = $this->Entertainer->getGallery();
            $gallery = $this->Entertainer->getImageGallery();
        }
        $list = array();
        foreach ($gallery as $Library) {
            if (!in_array($Library->mime_type, ['video/quicktime', 'video/x-msvideo', ''])) {
                $name = '';
                $thumb_url = $Library->getThumbnailURL();
                $img = "<img height=\"120px\" src=\"{$thumb_url}\" alt=\"{$Library->name}\"/>";
                if ($Library->name) {
                    $name = '<p class="post_desc">' . $Library->name . '</p>';
                }
//                $list[] = "<p class=\"post_desc\">$Library->name</p><a href=\"?{$job}&id={$Library->id}\">{$img}</a>";
                $list[] = "$name<a href=\"?{$job}&id={$Library->id}\">{$img}</a>";
            }
        }
        $writer->ul($list, 'image_list');
        return $writer->getHTML();
    }

    public function getVideoChooser($job, $sale = false, $shop = false)
    {
        $writer = new StaysailWriter();
        if (!$shop) {
            if ($sale) {
                $gallery = $this->Entertainer->getSaleImages();
            } else {
                //            $gallery = $this->Entertainer->getGallery();
                $gallery = $this->Entertainer->getVideosFan();
            }
        } else {
            $gallery = $this->Entertainer->getVideoStore();
        }

        $list = array();
        foreach ($gallery as $Library) {
            $Post = $Library->getLibraryPost();
            if ($Post['active'] != 0) {
                if (in_array($Library->mime_type, ['video/quicktime', 'video/x-msvideo', 'video/mp4'])) {
                    $video = $Library->getWebVideoHTML();
                    $type = $Library->mime_type;

                    if (in_array($Library->mime_type, ['video/quicktime', 'video/x-msvideo', ''])) {
                        $type = 'video/mp4';
                    }
                    $name = '';
                    if ($Library->name) {
                        $name = '<p class="post_desc">' . $Library->name . '</p>';
                    }
                    $video = '' . $name . '<video controls width="320" height="240" preload="auto">
                        <source src="' . $video . '" type="' . $type . '">
                        your browser does not support this tag.
                    </video>';
                    $list[] = "<a href=\"?{$job}&id={$Library->id}\">{$video}</a>";
                }
            }
        }
        $writer->ul($list, 'image_list');
        return $writer->getHTML();
    }

    public function getEditHTML()
    {
        $writer = new StaysailWriter();
        $writer->h1("Click on an Image to edit");
        $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=add\" class=\"button\">Add New Image</a> <a href=\"?mode=EntertainerGallery\" class=\"button\">Return to Gallery</a>");
        $writer->addHTML($this->getImageChooser('mode=EntertainerGallery&job=edit_image', false));
        return $writer->getHTML();
    }

    public function getEditVideoHTML()
    {
        $writer = new StaysailWriter();
        $writer->h1("Click on Video to edit");
        $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=add_video\" class=\"button\">Add New Video</a> <a href=\"?mode=EntertainerGallery&job=videos\" class=\"button\">Return to Gallery</a>");
        $writer->addHTML($this->getVideoChooser('mode=EntertainerGallery&job=edit_video', false));

        return $writer->getHTML();
    }

    public function getEditVideoStoreHTML()
    {
        $writer = new StaysailWriter();
        $writer->h1("Click on Video to edit");
        $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=add_video_store\" class=\"button\">Add New Video To Store</a> <a href=\"?mode=EntertainerGallery&job=videos_store\" class=\"button\">Return to Gallery</a>");
        $writer->addHTML($this->getVideoChooser('mode=EntertainerGallery&job=edit_video_store', false, true));

        return $writer->getHTML();
    }

    //get all videos
    public function getVideoHTML()
    {
        $writer = new StaysailWriter();
        $writer->h1("Videos");
        $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=add_video\" class=\"button\">Add New Video</a> <a href=\"?mode=EntertainerGallery&job=editVideo\" class=\"button\">Edit or Remove a Video</a>");
        $writer->addHTML($this->getVideoChooser('mode=EntertainerGallery&job=videos', false));

        return $writer->getHTML();
    }

    //get all video shop
    public function getVideoStoreHTML()
    {
        $writer = new StaysailWriter();
        $writer->h1("Video Store");
        $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=add_video_store\" class=\"button\">Add New Video To Store</a> <a href=\"?mode=EntertainerGallery&job=editVideoStore\" class=\"button\">Edit or Remove a Video From Store</a>");
        $writer->addHTML($this->getVideoChooser('mode=EntertainerGallery&job=video_store', false, true));

        return $writer->getHTML();
    }

    public function editImage($library_id)
    {
        $writer = new StaysailWriter();
        $Library = new Library($library_id);
        // Is it hers?
        if (!$Library->belongsTo($this->Member)) {
            $writer->h1("Sorry...")
                ->p("You do not appear to have access to this image");
            return $writer->getHTML();
        }

        $writer->h1("Edit Image");
        $writer->p("<a onclick=\"return confirm('Are you sure?');\" href=\"?mode=EntertainerGallery&job=delete_image&id={$library_id}\" class=\"button\">Remove this Image</a>");
        $Library = new Library($library_id);
        $PostData = $Library->getLibraryPost();
        $Post = new Post($PostData['id']);
        if ($Post->active == 0) {
            $writer->p("<a onclick=\"return confirm('Are you sure?');\" href=\"?mode=EntertainerGallery&job=activate&id={$library_id}\" class=\"button\">Activate</a>");
        }

        $dollar = '';
        if ($Library->price) {
            $dollar = $Library->price;
        }
        $style = $dollar ? 'block' : 'none';
        $placements = [
            'web' => '&#127760',
            'subscribed' => '&#128101',
            'sale' => '&#128178',
        ];
        $placementOptions = '';
        foreach ($placements as $placementName => $placementIcon) {
            $selected = $placementName == $Library->placement ? 'selected' : '';
            $placementOptions .= '<option value="' . $placementName . '"  ' . $selected . '>' . $placementIcon . '</option>';
        }
        $writer->p($Library->getRotateControls(__CLASS__));
        $writer->p($Library->getWebHTML());
        $edit = new StaysailForm('edit-image-form');
        $edit->setJobAction(__CLASS__, 'update_image', $library_id)
            ->setPostMethod()
            ->setDefaults($Library->info())
            ->setSubmit('Update This Image')
            ->addHTML('<div class="dollar_post_container">Where should this image go?
                                <div class="dollar_input" style="display: ' . $style . '">
                                    <input id="dollar_input_val" type="number" name="prices" value="' . $dollar . '" placeholder="Dollar">
                                </div>
                                <select name="placement" id="dollar_select">
                                   ' . $placementOptions . '
                                </select>
                            </div>')
            ->addField(StaysailForm::Line, 'Image Name', 'name', 'image-name')
            ->addField(StaysailForm::Text, 'Description', 'description', 'image-description')
            ->addHTML('<h1>Required Information</h1>')
            ->addHTML('<p>The following information is required for each person in this picture.  Click the Add Person button for each additional person in the picture.</p>')
            ->addHTML($this->getMetadataFields($Library))
            ->addHTML('<p id="add_person_button"><a class="button" onclick="addPicturePerson()"/>Add Person</a></p>');
        $writer->draw($edit);
        return $writer->getHTML();
    }

    public function editVideo($library_id)
    {
        $writer = new StaysailWriter();
        $Library = new Library($library_id);
        // Is it hers?
        if (!$Library->belongsTo($this->Member)) {
            $writer->h1("Sorry...")
                ->p("You do not appear to have access to this Video");
            return $writer->getHTML();
        }
        $dollar = '';
        if ($Library->price) {
            $dollar = $Library->price;
        }
        $style = $dollar ? 'block' : 'none';
        $placements = [
            'web' => '&#127760',
            'subscribed' => '&#128101',
            'sale' => '&#128178',
            'shop' => '&#128722',
        ];
        $placementOptions = '';
        foreach ($placements as $placementName => $placementIcon) {
            $selected = $placementName == $Library->placement ? 'selected' : '';
            $placementOptions .= '<option value="' . $placementName . '"  ' . $selected . '>' . $placementIcon . '</option>';
        }
        $writer->h1("Edit Video");
        $writer->p("<a onclick=\"return confirm('Are you sure?');\" href=\"?mode=EntertainerGallery&job=delete_video&id={$library_id}\" class=\"button\">Remove this Video</a>");
        $Library = new Library($library_id);
        $PostData = $Library->getLibraryPost();
        $Post = new Post($PostData['id']);
        if ($Post->active == 0) {
            $writer->p("<a onclick=\"return confirm('Are you sure?');\" href=\"?mode=EntertainerGallery&job=activate&id={$library_id}\" class=\"button\">Activate</a>");
        }
        //        $writer->p($Library->getRotateControls(__CLASS__));

        $video = $Library->getWebVideoHTML();
        $type = $Library->mime_type;
        if (in_array($Library->mime_type, ['video/quicktime', 'video/x-msvideo', ''])) {
            $type = 'video/mp4';
        }
        $writer->p('<video controls width="320" height="240" preload="auto">
  								<source src="' . $video . '" type="' . $type . '">
  								your browser does not support this tag.
							</video>');

//        $placements = array('web' => 'Post to your page', 'sale' => 'For sale to Fans');
//        $prices = array('10'=>'$10', '20'=>'$20', '30'=>'$30', '40'=>'$40','50'=>'$50', '60'=>'$60', '70'=>'$70', '80'=>'$80', '90'=>'$90', '100'=>'$100');
        $edit = new StaysailForm();
        $edit->setJobAction('EntertainerGallery', 'update_video', $library_id)
            ->setPostMethod()
            ->setSubmit('Update This Video')
            ->setDefaults($Library->info())
            ->addHTML('<div class="dollar_post_container">Where should this image go?
                                <div class="dollar_input" style="display: ' . $style . '">
                                    <input id="dollar_input_val" type="number" name="prices" value="' . $dollar . '" placeholder="Dollar">
                                </div>
                                <select name="placement" id="dollar_select">
                                  ' . $placementOptions . '
                                </select>
                            </div>')
//            ->addField(StaysailForm::Select, 'Where should this video go?', 'placement', '', $placements)
//            ->addField(StaysailForm::Select, 'Video Price', 'prices', '', $prices)
            ->addField(StaysailForm::Line, 'Video Name', 'name')
            ->addField(StaysailForm::Text, 'Description', 'description');
        $writer->draw($edit);

        return $writer->getHTML();
    }

    public function editVideoStore($library_id)
    {
        $writer = new StaysailWriter();
        $Library = new Library($library_id);
        // Is it hers?
        if (!$Library->belongsTo($this->Member)) {
            $writer->h1("Sorry...")
                ->p("You do not appear to have access to this Video");
            return $writer->getHTML();
        }
        $dollar = '';
        if ($Library->price) {
            $dollar = $Library->price;
        }
        $style = $dollar ? 'block' : 'none';
        $placements = [
            'shop' => '&#128722',
        ];
        $placementOptions = '';
        foreach ($placements as $placementName => $placementIcon) {
            $selected = $placementName == $Library->placement ? 'selected' : '';
            $placementOptions .= '<option value="' . $placementName . '"  ' . $selected . '>' . $placementIcon . '</option>';
        }
        $writer->h1("Edit Video From Store");
        $writer->p("<a onclick=\"return confirm('Are you sure?');\" href=\"?mode=EntertainerGallery&job=delete_video_store&id={$library_id}\" class=\"button\">Remove this Video</a>");
        $Library = new Library($library_id);
        $PostData = $Library->getLibraryPost();
        $Post = new Post($PostData['id']);
        if ($Post->active == 0) {
            $writer->p("<a onclick=\"return confirm('Are you sure?');\" href=\"?mode=EntertainerGallery&job=activate&id={$library_id}\" class=\"button\">Activate</a>");
        }
        //        $writer->p($Library->getRotateControls(__CLASS__));

        $video = $Library->getWebVideoHTML();
        $type = $Library->mime_type;
        if (in_array($Library->mime_type, ['video/quicktime', 'video/x-msvideo', ''])) {
            $type = 'video/mp4';
        }
        $writer->p('<video controls width="320" height="240" preload="auto">
  								<source src="' . $video . '" type="' . $type . '">
  								your browser does not support this tag.
							</video>');

//        $placements = array('web' => 'Post to your page', 'sale' => 'For sale to Fans');
//        $prices = array('10'=>'$10', '20'=>'$20', '30'=>'$30', '40'=>'$40','50'=>'$50', '60'=>'$60', '70'=>'$70', '80'=>'$80', '90'=>'$90', '100'=>'$100');
        $edit = new StaysailForm();
        $edit->setJobAction('EntertainerGallery', 'update_video', $library_id)
            ->setPostMethod()
            ->setSubmit('Update This Video')
            ->setDefaults($Library->info())
            ->addHTML('<div class="dollar_post_container">Where should this image go?
                                <div class="dollar_input" style="display: ' . $style . '">
                                    <input id="dollar_input_val" type="number" name="prices" value="' . $dollar . '" placeholder="Dollar">
                                </div>
                                <select name="placement" id="dollar_select">
                                  ' . $placementOptions . '
                                </select>
                            </div>')
//            ->addField(StaysailForm::Select, 'Where should this video go?', 'placement', '', $placements)
//            ->addField(StaysailForm::Select, 'Video Price', 'prices', '', $prices)
            ->addField(StaysailForm::Line, 'Video Name', 'name')
            ->addField(StaysailForm::Text, 'Description', 'description');
        $writer->draw($edit);

        return $writer->getHTML();
    }

    public function getMetadataFields(Library $Library = null)
    {
        $html = '';
        $metadata = $Library ? $Library->getMetadata() : array();
        $data = Library::getMetadataTypes();

        if (!sizeof($metadata)) {
            // Create an entry for the entertainer
            $metadata[] = array_keys($data);
        }

        $html = "<div id=\"picture_metadata\">";
        for ($i = 0; $i <= 8; $i++) {
            if (isset($metadata[$i])) {
                $person = $metadata[$i];
            } else {
                $person = array();
            }
            $display = $i <= sizeof($person) ? 'block' : 'none';

            $html .= "<div id=\"person{$i}\" style=\"float: left; clear: both; display:{$display}\">";
            if ($i == 0) {
                $html .= "<h2>Your Information</h2>\n";
            } else {
                $html .= "<h2>Next Pictured Person</h2>\n";
                $html .= "<p><a href=\"#\" onclick=\"removePerson({$i});return false;\">Remove</a></p>";
            }

            foreach ($data as $type) {
                $html .= '<div class="label">' . ucwords(str_replace('_', ' ', $type)) . '</div>';
                $value = isset($person[$type]) ? $person[$type] : '';
                $html .= "<div class=\"control\"><input type=\"text\" name=\"{$type}[]\" value=\"{$value}\" /></div>";
            }
            $html .= "</div>\n";
        }
        $html .= "</div>";
        $html .= "<script>person_number=" . sizeof($person) . "</script>";
        return $html;
    }

    public function postEditLibrary($library_id)
    {
        $Library = new Library($library_id);
        // Is it hers?
        if (!$Library->belongsTo($this->Member)) {
            return;
        }
        $fields = array('name', 'description', 'keywords', 'placement');
        $Library->updateFrom($fields);
        $Library->price = StaysailIO::post('prices');
        $Library->save();
        $PostData = $Library->getLibraryPost();
        $content = '';
        if (!empty($PostData['id'])) {
            $Post = new Post($PostData['id']);
            if (strpos($Library->mime_type, 'video') !== false) {
                $type = 'video/mp4';
                $videoLink = $Library->getWebVideoHtml();

                if ($Library->name) {
                    $content .= '<p class="post_desc">' . $Library->name . '</p>';
                }
                $content .= '<video controls width="320" height="240" preload="auto">
  								<source src="' . $videoLink . '" type="' . $type . '">
  								your browser does not support this tag.
							</video>';
            } else {
                if ($Library->name) {
                    $content .= '<p class="post_desc">' . $Library->name . '</p>';
                }
                $content .= $Library->getWebHtml();
            }
            $Post->update(['content' =>
                $content . "<p>{$Library->description}</p>",
                'placement' => $Library->placement
            ]);
            $Post->save();
        }

        $this->postMetadata($Library);
    }

    private function postMetadata(Library $Library)
    {
        $metadata = [];
        $data = Library::getMetadataTypes();
        foreach ($data as $type) {
            $posted = StaysailIO::post($type);

            if (empty($posted) || !is_array($posted)) {
                continue;
            }

            for ($i = 0; $i < sizeof($posted); $i++) {
                if (!isset($metadata[$i])) {
                    $metadata[$i] = array();
                }
                $metadata[$i][$type] = $posted[$i];
            }
        }
        $Library->setMetadata($metadata);
    }

    public function deleteImage($library_id)
    {
        $Library = new Library($library_id);
        // Is it hers?
        if (!$Library->belongsTo($this->Member)) {
            return;
        }
        $PostData = $Library->getLibraryPost();
        if (!empty($PostData['id'])) {
            $Post = new Post($PostData['id']);
            if (!$Post->belongsTo($this->Member)) {
                return;
            }
        }
        if (isset($Post)) {
            $Post->delete_Job();
        }
        $Library->delete_Job();
        $img = DATAROOT . "/private/library/{$library_id}";
        foreach (['png', 'jpg', 'jpeg', 'mp4', 'MOV', 'mov'] as $format) {
            if (file_exists($img . '.' . $format)) {
                unlink($img . '.' . $format);
            };
        }
    }

    public function deleteVideoStore($library_id)
    {
        $Library = new Library($library_id);
        // Is it hers?
        if (!$Library->belongsTo($this->Member)) {
            return;
        }
        $PostData = $Library->getLibraryPost();
        if (!empty($PostData['id'])) {
            $Post = new Post($PostData['id']);
            if (!$Post->belongsTo($this->Member)) {
                return;
            }
        }
        if (isset($Post)) {
            $Post->delete_Job();
        }
        $Library->delete_Job();
        $img = DATAROOT . "/private/library/{$library_id}";
        foreach (['png', 'jpg', 'jpeg', 'mp4', 'MOV', 'mov'] as $format) {
            if (file_exists($img . '.' . $format)) {
                unlink($img . '.' . $format);
            };
        }
    }

    public function getPurchaseHTML()
    {
        $writer = new StaysailWriter();
        $writer->h1("Which image would you like to purchase?");
        $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery\" class=\"button\">Return to Gallery</a>");
        $writer->addHTML($this->getImageChooser('mode=Purchase&job=purchase&type=Library', true));
        return $writer->getHTML();
    }

    public function getPurchaseVideoHTML()
    {
        $writer = new StaysailWriter();
        $writer->h1("Which video would you like to purchase?");
        $writer->p("<br/><br/><a href=\"?mode=EntertainerGallery&job=videos\" class=\"button\">Return to Gallery</a>");
        //$writer->addHTML($this->getImageChooser('mode=Purchase&job=purchase&type=Library', true));
        $writer->addHTML($this->getVideoChooser('mode=Purchase&job=purchaseVideo&type=Library', true));
        return $writer->getHTML();
    }

    public function chooseAvatar()
    {
        $writer = new StaysailWriter();
        $writer->h1("Step 1: Choose Headshot Image");
        $writer->p("Choose the image you would like to use for your headshot.  You will be able to crop your headshot in the next step.");
        $writer->p("<a href=\"?mode=EntertainerGallery&job=add\" class=\"button\">Upload an Image</a>");

        $writer->addHTML($this->getImageChooser('mode=EntertainerGallery&job=crop_avatar'));
        return $writer->getHTML();

    }

    public function uploadAvatar()
    {
        $avatarPhoto = !empty($_FILES['avatar_image']['name']) ? $_FILES['avatar_image'] : [];
        $Library = '';
        $galleryId = count($_FILES['avatar_image']['name']) > 1 ? substr(md5(mt_rand()), 0, 7) : null;
        if (isset($avatarPhoto['size']) && $avatarPhoto['size'] != 0) {
            $Library = Post::createLibrary($avatarPhoto, $this->Member, $this->Entertainer, $galleryId);
        }
        $x = 1;
        $y = 0;
        $hw = 360;
        $Library->setAsAvatarUsing($x, $y, $hw);

        header('Location: /?mode=EntertainerProfile');
        exit;
    }

    public function cropAvatar($library_id)
    {
        $Library = new Library($library_id);
        // Is it hers?
        if (!$Library->belongsTo($this->Member)) {
            return;
        }
        $url = $Library->getThumbnailURL();

        $html = <<<__END__
    	<h1>Step 2: Crop Your Headshot</h1>
    	<p>The headshot needs to be a square.  Click the starting point of your headshot, and drag the mouse pointer while holding down the button.  When you're happy with the size and composition, click the Set Headshot button.</p>
    	<p><a href="?mode=EntertainerGallery&job=choose_avatar" class="button">Choose a Different Image</a></p>
    	
		<div id="squarifier">
		<div id="square"></div>
		</div>

		<form action="?mode=EntertainerGallery&job=set_avatar&id={$library_id}" method="post">
		<input type="hidden" name="x" id="x" value="20" />
		<input type="hidden" name="y" id="y" value="20" />
		<input type="hidden" name="hw" id="hw" value="320" />
		<input type="submit" value="Set Headshot" />
		</form>
		
		<style>
		#squarifier {
			background-image: url("{$url}");
    	}
    	</style>
__END__;
        return $html;
    }

    public function setAvatar($library_id)
    {
        $Library = new Library($library_id);
        // Is it hers?
        if (!$Library->belongsTo($this->Member)) {
            return;
        }

        $x = StaysailIO::post('x');
        $y = StaysailIO::post('y');
        $hw = StaysailIO::post('hw');
        $Library->setAsAvatarUsing($x, $y, $hw);

        header('Location: /?mode=EntertainerProfile&h=' . $library_id); // &h=n indicates the headshot image id
        exit;
    }

    public function rotateImage($library_id, $angle)
    {
        $Library = new Library($library_id);
        // Is it hers?
        if (!$Library->belongsTo($this->Member)) {
            return;
        }

        if ($angle < 1 or $angle > 360) {
            $angle = 0;
        }
        $Library->rotate($angle);
    }

    public function activate($library_id)
    {
        $Library = new Library($library_id);
        $PostData = $Library->getLibraryPost();
        if (!empty($PostData['id'])) {
            $Post = new Post($PostData['id']);
            $update = array('Member_id' => $this->Member->id,
                'post_time' => StaysailIO::now(),
                'active' => 1,
            );
            $Post->update($update);
            $Post->save();
        }
    }
}
