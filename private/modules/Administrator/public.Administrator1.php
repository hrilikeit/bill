<?php

require '../private/tools/AdminForm.php';
//require '../private/tools/OrbitalPaymentGateway.php';
require '../private/tools/PayTechTrustPaymentGateway.php';
//require '../private/tools/SMSSender.php';
require '../private/domain/class.MailSend.php';

class Administrator extends StaysailPublic
{
    protected $page, $settings, $categories;
    protected $_framework;
    private $menu;
    private $Admin;
    private $setting_names;

    public function __construct($dbc = '')
    {
        $this->_framework = StaysailIO::engage();
        $this->Admin = new Admin(StaysailIO::session('Admin.id'));
        $this->menu = array(
            'Setup:settings' => 'Global Settings',
            'Content:banned_words' => 'Banned Words',
            'Content:approve_images' => 'Approve Images',
            'Content:reviews' => 'Flagged Reviews',
            'Content:banners' => 'Banner Ads',
            'Members:entertainer' => 'Manage Enterainers',
            /*'Members:club' =>           'Manage Clubs',*/
            'Members:fan' => 'Manage Fans',
            'Members:approved_collaborator_documents' => 'Approved 2257',
            'Communication:mailent' => 'Email Members',
            'Users:admin' => 'Manage Administrators',
            'Financial:capture' => 'WebShow Auth Capture',
            'Financial:revenue' => 'Default Revenue Split',
            'Financial:reports' => 'Reports',
            'Financial:earnings' => 'Earnings',
        );

        $this->setting_names = array('Promotional Membership Code' => 'code',
            'Entertainer of the Month URL' => 'eom_url',
            'SMS Notification Exceptions<ul><li>* Disables notification of Entertainers when a specified Fan logs in</li><li>* Space-separated list of screen names</li></ul>' => 'sms_exceptions',
        );
    }

    public function getHTML()
    {
        $job = StaysailIO::get('job');
        $id = StaysailIO::get('id');
        $map = <<<__END__
    		HHHHHHHHHHHHHHHHHHH
    		AAACCCCCCCCCCCCC---
    		FFFFFFFFFFFFFFFFFFF
__END__;

        $content = '';
        switch ($job) {
            case 'logout':
                StaysailIO::setSession('Admin.id', null);
                $content = $this->login();
                break;

            /* Banned Words */
            case 'banned_words':
                $content = $this->bannedWords();
                break;
            case 'add_word':
                $this->addBannedWord();
                $content = $this->bannedWords();
                break;
            case 'delete_word':
                $this->deleteBannedWord($id);
                $content = $this->bannedWords();
                break;

            /* Approve Images */
            case 'approve_images':
                $content = $this->approveImages();
                break;
            case 'image_ok':
                $this->markImageOK($id);
                $content = $this->approveImages();
                break;
            case 'deny_image':
                $content = $this->denyImage($id);
                break;
            case 'post_deny_image':
                $this->postDenyImage($id);
                $content = $this->approveImages();
                break;

            /* Flagged Reviews */
            case 'reviews':
                $content = $this->approveReviews();
                break;
            case 'review_ok':
                $this->markReviewOK($id);
                $content = $this->approveReviews();
                break;
            case 'deny_review':
                $content = $this->denyReview($id);
                break;
            case 'post_deny_review':
                $this->postDenyReview($id);
                $content = $this->approveReviews();
                break;

            /* Member Management */
            case 'entertainer':
            case 'club':
            case 'fan':
                $content = $this->selectorFor($job);
                break;
            case 'entertainerOrder':
                $content = $this->entertainerOrder($id);
                break;
            case 'edit_member':
                $content = $this->editMember($id);
                break;
            case 'view_documents':
            case 'approve_documents':
                $content = $this->viewDocuments($id);
                break;
            case 'approved_collaborator_documents':
                $content = $this->viewApprovedCollaboratorDocuments();
                break;
            case 'view_collaborator_docs':
                $content = $this->viewCollaboratorDocuments($id);
                break;
            case 'approve_collaborator_docs':
                $content = $this->approveCollaboratorDocuments($id);
                break;
            case 'edit_account':
                $content = $this->editAccount($id);
                break;
            case 'edit_club':
                $content = $this->editClub($id);
                break;
            case 'delete_member':
                $content = $this->deleteMember($id);
                break;
            case 'delete_club':
                $content = $this->deleteClub($id);
                break;
            case 'set_clubs':
                $content = $this->setClubs($id);
                break;
            case 'post_set_clubs':
                $content = $this->postSetClubs($id);
                break;
            case 'manage_subscriptions':
                $content = $this->subscriptionMenu($id);
                break;
            case 'remove_subscription':
                $content = $this->removeSubscription(StaysailIO::getInt('subscription_id'));
                $content .= $this->subscriptionMenu($id);
                break;
            case 'add_subscription':
                $content .= $this->addSubscription($id, StaysailIO::getInt('entertainer_id'));
                $content .= $this->subscriptionMenu($id);
                break;

            /* Generic Updater */
            case 'update':
                $content = $this->update(StaysailIO::post('CLASS_NAME'), $id);
                break;

            /* Mailer */
            case 'mailent':
                $content = $this->getMailer();
                break;

            case 'send_mail':
                $content = $this->sendMail();
                break;

            /* Admin Edit */
            case 'admin':
                $content = $this->adminMenu();
                break;

            case 'edit_admin':
                $content = $this->editAdmin($id);
                break;

            case 'post_admin':
                $content = $this->postAdmin($id);
                break;

            case 'delete_admin':
                $content = $this->deleteAdmin($id);
                break;

            /* Financial */
            case 'capture':
                $content = $this->captureMenu();
                break;
            case 'complete_capture':
                $content = $this->completeCapture($id);
                break;
            case 'reports':
                $content = $this->reportMenu();
                break;
            case 'earnings':
                $content = $this->earningsMenu();
                break;
            case 'run_report':
                $content = $this->runReport($id);
                break;

            /* Settings */
            case 'settings':
                $content = $this->editSettings();
                break;
            case 'post_settings':
                $this->postSettings();
                $content = $this->editSettings();
                break;

            case 'entertainer_member_docs':
                $this->EntertainerMemberDocs();
                break;

            case 'add_referrer':
                $content = $this->addReferrer($id);
                break;

            case 'put_referral':
                $this->putReferral($id);
                break;

            case 'remove_referral':
                $content = $this->removeReferral(StaysailIO::getInt('remove_referrer'));
                $content .= $this->addReferrer($id);
                break;
            default:
                $content = "<h1>Please choose a menu item on the left</h1>";
        }

        //$header = new HeaderView();
        $footer = new FooterView();

        $containers = array(//new StaysailContainer('H', 'header', $header->getHTML()),
            new StaysailContainer('F', 'footer', $footer->getHTML()),
            new StaysailContainer('A', 'action', $this->getMenu()),
            new StaysailContainer('C', 'content', $content),
            new StaysailContainer('-', '', '&nbsp;'),
        );
        $layout = new StaysailLayout($map, $containers);
        return $layout->getHTML();
    }

    public function login()
    {
        $writer = new StaysailWriter();
        $writer->addHTML('<br/><br/><br/><br/>');

        if (StaysailIO::get('job') == 'login') {
            if ($this->authorize()) {
                return $this->getHTML();
            } else {
                $writer->h2("Username and/or password invalid");
            }
        }

        $form = new StaysailForm('admin');
        $form->setPostMethod()
            ->setJobAction(__CLASS__, 'login')
            ->setSubmit('Sign In')
            ->addField(StaysailForm::Line, 'User Name', 'username', 'required')
            ->addField(StaysailForm::Password, 'Password', 'password', 'required');
        $writer->draw($form);
        return $writer->getHTML();
    }

    private function authorize()
    {
        $username = trim(StaysailIO::post('username'));
        $password = md5(trim(StaysailIO::post('password')));

        $admins = $this->_framework->getSubset('Admin', new Filter(Filter::Match, array('username' => $username, 'password' => $password)));
        if (sizeof($admins)) {
            $Admin = $admins[0];
            StaysailIO::setSession('Admin.id', $Admin->id);
            $this->Admin = $Admin;
            return true;
        }

        return false;
    }


    private function getMenu()
    {
        $writer = new StaysailWriter();
        $writer->h2('Administration');
        $writer->addHTML('<br/><br/>');

        $last_header = '';

        foreach ($this->menu as $name => $label) {
            preg_match('/(.+):(.+)/', $name, $m);
            $header = $m[1];
            $job = $m[2];
            if (!$this->Admin->has($job)) {
                continue;
            }

            if ($header != $last_header) {
                $writer->h2($header);
                $last_header = $header;
            }
            $link = StaysailWriter::makeJobLink($label, __CLASS__, $job);

            $writer->p($link);

        }

        $writer->p(StaysailWriter::makeJobLink('Sign Out', __CLASS__, 'logout'));

        return $writer->getHTML();
    }

    private function bannedWords()
    {
        if (!$this->Admin->has('banned_words')) {
            return 'No Access!!';
        }

        $writer = new StaysailWriter();
        $writer->h1('Banned Words');

        $form = new StaysailForm('admin');
        $form->setPostMethod()
            ->setJobAction(__CLASS__, 'add_word')
            ->addField(StaysailForm::Line, 'New Word', 'name', 'required')
            ->setSubmit('Add New Word');
        $writer->draw($form);

        $filters = array(new Filter(Filter::Sort, 'name'));
        $word_list = $this->_framework->getSubset('Prohibited_Word', $filters);
        $table = new StaysailTable('admin');
        foreach ($word_list as $Prohibited_Word) {
            $row = array($Prohibited_Word->name,
                StaysailWriter::makeJobLink('Delete', __CLASS__, 'delete_word', $Prohibited_Word->id));
            $table->addRow($row);
        }
        $writer->draw($table);

        return $writer->getHTML();
    }

    private function deleteBannedWord($prohibited_word_id)
    {
        if (!$this->Admin->has('banned_words')) {
            return 'No Access!';
        }

        $Prohibited_Word = new Prohibited_Word($prohibited_word_id);
        $Prohibited_Word->delete_Job();
    }

    private function addBannedWord()
    {
        if (!$this->Admin->has('banned_words')) {
            return 'No Access!';
        }

        $name = StaysailIO::post('name');
        $Prohibited_Word = new Prohibited_Word();
        $Prohibited_Word->name = $name;
        $Prohibited_Word->save();
    }

    private function editBannerAds()
    {
        $writer = new StaysailWriter();
        $form = new StaysailForm('admin');
        $banner_ads = $this->_framework->getSubset('Banner_Ad', new Filter(Filter::Sort, 'sort'));
    }

    private function approveImages()
    {
        if (!$this->Admin->has('approve_images')) {
            return 'No Access!';
        }

        $writer = new StaysailWriter();
        $writer->h1('Approve Images');
        $status = StaysailIO::get('status');
        if (!$status) {
            $status = 'pending';
        }

        $statuses = array('pending', 'denied', 'approved');
        $html = '';
        foreach ($statuses as $s) {
            $html .= " [<a href=\"?mode=Administrator&job=approve_images&status={$s}\">{$s}</a>] ";
        }
        $writer->addHTML($html);

        $filter = new Filter(Filter::Match, array('admin_status' => $status, 'is_deleted' => 0));
        $pending_library = $this->_framework->getSubset('Library', $filter);

        $table = new StaysailTable('admin');
        foreach ($pending_library as $Library) {
            $info = '';
            if ($Library->admin_status == 'denied') {
                $deny = "<h2>DENIED</h2><p>Denied on: {$Library->status_time}<br/>
    					 By: {$Library->Admin->real_name}<br/>
    					 <strong>Reason:</strong> <i>{$Library->status_note}</i>";
            } else {
                $deny = StaysailWriter::makeJobLink('Deny', __CLASS__, 'deny_image', $Library->id);
            }

            $path = DATAROOT . "/private/library/{$Library->image}";
            if (is_file($path)) {
                list ($width, $height) = $Library->getNativeSize();
                $size = "<br/>{$width}x{$height}";
                if (strpos($Library->mime_type, 'video') !== false) {
                    $type = 'video/mp4';
                    $content = $Library->getWebVideoHtml();
                    $content = '<video controls width="320" height="240">
  								<source src="' . $content . '" type="' . $type . '">
  								your browser does not support this tag.
							</video>';
                } else {
                    $content = $Library->getWebHtml();
                }
                $row = array($content,
                    "<h2>{$size}</h2>\n" . $Library->getMetadataHTML(),
                    StaysailWriter::makeJobLink('Approve', __CLASS__, 'image_ok', $Library->id),
                    $deny,
                );
                $table->addRow($row);
            }
        }
        $writer->draw($table);
        return $writer->getHTML();
    }

    private function markImageOK($library_id)
    {
        if (!$this->Admin->has('approve_images')) {
            return 'No Access!';
        }

        $Library = new Library($library_id);
        $Library->admin_status = 'approved';
        $Library->status_time = StaysailIO::now();
        $Library->Admin_id = $this->Admin->id;
        $Library->save();
    }

    private function denyImage($library_id)
    {
        if (!$this->Admin->has('approve_images')) {
            return 'No Access!';
        }

        $writer = new StaysailWriter();
        $writer->h1('Deny Image');
        $Library = new Library($library_id);
        $form = new StaysailForm('admin');
        $form->setPostMethod()
            ->setJobAction(__CLASS__, 'post_deny_image', $library_id)
            ->addField(StaysailForm::Text, 'Reason for Denial', 'status_note', 'required')
            ->setSubmit('Deny Image');
        $writer->draw($form);
        $writer->p(StaysailWriter::makeJobLink('Never Mind', __CLASS__, 'approve_images'));
        $img = StaysailWriter::makeImage($Library->getFullSizeURL(), $Library->name);

        $writer->addHTML($img);
        return $writer->getHTML();
    }

    private function postDenyImage($library_id)
    {
        if (!$this->Admin->has('approve_images')) {
            return 'No Access!';
        }

        $Library = new Library($library_id);
        $Library->admin_status = 'denied';
        $Library->status_time = StaysailIO::now();
        $Library->Admin_id = $this->Admin->id;
        $Library->status_note = StaysailIO::post('status_note');
        $Library->save();

        // Notify the owner
        $name = $Library->name ? "({$Library->name})" : '';
        $message = <<<__END__
One of your images {$name} was deemed inappropriate for this site for the following reason:

{$Library->status_note}

We apologize for the inconvenience.
__END__;

        $Private_Message = new Private_Message();
        $Private_Message->name = 'One of your images was denied';
        $Private_Message->from_Admin_id = $this->Admin->id;
        $Private_Message->to_Member_id = $Library->Member->id;
        $Private_Message->send_time = StaysailIO::now();
        $Private_Message->content = $message;
        $Private_Message->save();
    }

    private function approveReviews()
    {
        if (!$this->Admin->has('reviews')) {
            return 'No Access!';
        }

        $writer = new StaysailWriter();
        $writer->h1('Flagged Reviews');

        $filter = new Filter(Filter::Match, array('admin_status' => 'flagged'));
        $reviews = $this->_framework->getSubset('Review', $filter);

        $table = new StaysailTable('admin');
        foreach ($reviews as $Review) {
            $row = array("<h2>{$Review->name}</h2><p>{$Review->content}</p>",
                StaysailWriter::makeJobLink('Approve', __CLASS__, 'review_ok', $Review->id),
                StaysailWriter::makeJobLink('Remove', __CLASS__, 'deny_review', $Review->id),
            );
            $table->addRow($row);
        }
        $writer->draw($table);
        return $writer->getHTML();
    }

    private function markReviewOK($review_id)
    {
        if (!$this->Admin->has('reviews')) {
            return 'No Access!';
        }

        $Review = new Review($review_id);
        $Review->admin_status = 'approved';
        $Review->status_time = StaysailIO::now();
        $Review->Admin_id = $this->Admin->id;
        $Review->save();
    }

    private function denyReview($review_id)
    {
        if (!$this->Admin->has('reviews')) {
            return 'No Access!';
        }

        $writer = new StaysailWriter();
        $writer->h1('Remove Review');
        $Review = new Review($review_id);
        $form = new StaysailForm('admin');
        $form->setPostMethod()
            ->setJobAction(__CLASS__, 'post_deny_review', $review_id)
            ->addField(StaysailForm::Text, 'Reason for Removal', 'status_note', 'required')
            ->setSubmit('Remove Review');
        $writer->draw($form);
        $writer->p(StaysailWriter::makeJobLink('Never Mind', __CLASS__, 'reviews'));
        $writer->p("<hr />" . $Review->content);
        return $writer->getHTML();
    }

    private function postDenyReview($review_id)
    {
        if (!$this->Admin->has('reviews')) {
            return 'No Access!';
        }

        $Review = new Review($review_id);
        $Review->admin_status = 'denied';
        $Review->status_time = StaysailIO::now();
        $Review->Admin_id = $this->Admin->id;
        $Review->status_note = StaysailIO::post('status_note');
        $Review->save();
    }

    private function selectorFor($account)
    {
        if (!$this->Admin->has($account)) {
            return 'No Access!';
        }
        $tableClass = $account == 'entertainer' ?
            'tbEntertainers' : '';
        $class = ucwords($account);
        $writer = new StaysailWriter();
        $writer->h1("Manage {$class}s");
        $writer->p("<form>Find: <input type=\"text\" name=\"q\" id=\"q\" onfocus=\"startAdminSearch()\" /></form>");

        $filters = array(
            new Filter(Filter::Match, array('is_deleted' => 0)),
            new Filter(Filter::Sort,  $account == 'entertainer'  ? 'order_list ASC' : 'id DESC')
        );
        $collection = $this->_framework->getSubset($class, $filters);

        $table = new StaysailTable('admin '. $tableClass);

        foreach ($collection as $entity) {
            if ($class != 'Club') {
                $Member = $entity->Member;
                if ($Member == null) {continue;}
                if (!$Member->id || $Member->is_deleted) {
                    continue;
                }
                $row = array($entity->name,
                    $Member->last_name,
                    $Member->first_name,
                    $Member->phone,
                    StaysailWriter::makeJobLink('Edit', __CLASS__, 'edit_member', $Member->id),
//	    					 StaysailWriter::makeJobLink('Manage Data', __CLASS__, 'manage_data', $Member->id),
                    StaysailWriter::makeJobLink('Manage Data', __CLASS__, 'edit_account', $Member->id),
                    StaysailWriter::makeJobLink('Delete', __CLASS__, 'delete_member', $Member->id, '', 'Are you sure?'),
                );
            } else {
                if ($entity->is_deleted) {
                    continue;
                }
                $Member = $entity->getAdminMember();
                $email = $name = '--';
                if ($Member) {
                    $email = $Member->email;
                    $name = $Member->getRealFullName();
                }
                $row = array($entity->account_number,
                    $entity->name, $entity->city, $name, $email,
                    StaysailWriter::makeJobLink('Edit', __CLASS__, 'edit_club', $entity->id),
                    StaysailWriter::makeJobLink('Delete', __CLASS__, 'delete_club', $entity->id, '', 'Are you sure?'),
                );
            }
            $table->addRow($row, null, 'data-id="'.$entity->id.'"' );
        }
        $writer->draw($table);

        return $writer->getHTML();
    }

    private function entertainerOrder($account)
    {
        $entertainerOrder = StaysailIO::post('orderData');
        foreach ($entertainerOrder as $key => $entertainerId) {
            $entertainer = new Entertainer($entertainerId);
            $entertainer->order_list = $key + 1;
            $entertainer->save();
        }
        
        return true;
    }

    private function deleteMember($member_id)
    {
        $Member = new Member($member_id);
        $type = strtolower($Member->getAccountType());

        $photos = $Member->getPhotos();
        $videos = $Member->getVideos();
        foreach ($photos as $photo) {
            $photoName = DATAROOT . '/private/library/' . $photo->image;
            if (file_exists($photoName)) {
                unlink($photoName);
            }
        }
        foreach ($videos as $video) {
            $videoName = DATAROOT . '/private/library/' . $video->image;
            if (file_exists($videoName)) {
                unlink($videoName);
            }
        }
        $filter = new Filter(Filter::Match, array('Member_id' => $member_id));
        $libraries = $this->_framework->getSubset('Library', $filter);
        foreach ($libraries as $library) {
            $lib = new Library($library->id);
            $lib->delete_Job();
        }

        $filter = new Filter(Filter::Match, array('Member_id' => $member_id));
        $posts = $this->_framework->getSubset('Post', $filter);
        foreach ($posts as $post) {
            $pos = new Post($post->id);
            $pos->delete_Job();
        }

        $Fan = $Member->getAccountOfType('Fan');
        $Entertainer = $Member->getAccountOfType('Entertainer');
        if ($Fan) {
            $avatar = DATAROOT . "/private/avatars/avatar{$Member->id}";
            foreach (['png', 'jpg', 'jpeg'] as $format) {
                if (file_exists($avatar . '.' . $format)) {
                    unlink($avatar . '.' . $format);
                };
            }
            $displayPhoto = DATAROOT . "/private/avatars/displayPhoto{$Member->id}";
            foreach (['png', 'jpg', 'jpeg'] as $format) {
                if (file_exists($displayPhoto . '.' . $format)) {
                    unlink($displayPhoto . '.' . $format);
                };
            }
            $Fan->delete_Job();
        } elseif ($Entertainer) {
            $entertainerAvatar = DATAROOT . "/private/avatars/entertainerAvatar{$Member->id}";

            foreach (['png', 'jpg', 'jpeg'] as $format) {
                if (file_exists($entertainerAvatar . '.' . $format)) {
                    unlink($entertainerAvatar . '.' . $format);
                };
            }
            $avatar = DATAROOT . "/private/avatars/avatar{$Member->id}";
            foreach (['png', 'jpg', 'jpeg'] as $format) {
                if (file_exists($avatar . '.' . $format)) {
                    unlink($avatar . '.' . $format);
                };
            }
            $entertainerDisplayPhoto = DATAROOT . "/private/avatars/entertainerDisplayPhoto{$Member->id}";
            foreach (['png', 'jpg', 'jpeg'] as $format) {
                if (file_exists($entertainerDisplayPhoto . '.' . $format)) {
                    unlink($entertainerDisplayPhoto . '.' . $format);
                };
            }

            $Entertainer->delete_Job();
        }
        $Member->delete_Job();

        return $this->selectorFor($type);
    }

    private function deleteClub($club_id)
    {
        $Club = new Club($club_id);
        $Club->delete_Job();
        return $this->selectorFor('club');
    }

    private function editMember($member_id)
    {
        $Member = new Member($member_id);
        $type = $Member->getAccountType();
        if (!$this->Admin->has(strtolower($type))) {
            return 'No Access';
        }

        $writer = new StaysailWriter();
        $writer->h1("Edit {$type} Member");
        $writer->p(StaysailWriter::makeJobLink("Edit {$type} Data", __CLASS__, 'edit_account', $member_id));
        /*if ($type == 'Entertainer') {
            $writer->p(StaysailWriter::makeJobLink("Set Clubs", __CLASS__, 'set_clubs', $member_id));
        }*/
        if ($type == 'Fan') {
            $writer->p(StaysailWriter::makeJobLink("Manage Subscriptions", __CLASS__, 'manage_subscriptions', $member_id));
        }

        $form = new AdminForm($Member);
        $writer->draw($form);

//        if ($type == 'Entertainer') {
//
//            $existingCollaborators = $this->_framework->getAllIdsRowsByField('Entertainer_Collaborator',
//                'Member_id', $Member->id);
//            foreach ($existingCollaborators as $existingCollaboratorId) {
//                $existingCollaborator = new Entertainer_Collaborator($existingCollaboratorId[0]);
//
//                 $writer->span($existingCollaborator->stage_name.StaysailWriter::makeJobLink(Icon::show(Icon::FOLDER, Icon::SIZE_LARGE) , __CLASS__, 'view_collaborator_docs', $existingCollaborator->id), 'view_collaborator_docs');
//
//            }
//        }

        return $writer->getHTML();
    }

    private function viewDocuments($member_id)
    {
        $fileField = StaysailIO::get('fileField');
        $Member = new Member($member_id);
        $type = $Member->getAccountType();
        if (!$this->Admin->has(strtolower($type))) {
            return 'No Access';
        }

        $writer = new StaysailWriter();
        $writer->h1("Edit {$type} Member");
        $writer->p(StaysailWriter::makeJobLink("Edit {$type} Data", __CLASS__, 'edit_account', $member_id));
        /*if ($type == 'Entertainer') {
            $writer->p(StaysailWriter::makeJobLink("Set Clubs", __CLASS__, 'set_clubs', $member_id));
        }*/
        if ($type == 'Fan') {
            $writer->p(StaysailWriter::makeJobLink("Manage Subscriptions", __CLASS__, 'manage_subscriptions', $member_id));
        }
        $memberDocs = $this->_framework->getRowByField('Member_Docs', 'Member_id', $member_id);
        $memberDocs = new Member_Docs(isset($memberDocs['id']) ? $memberDocs['id'] : null);

        if (!$memberDocs) {
            $writer->addHTML('<h2 class="_hedging">No documents have been uploaded yet</h2>');
        }

        $approvedField = $fileField . '_approved';
        if ($memberDocs && !$memberDocs->$approvedField && StaysailIO::post('approveDocuments')) {
            $memberDocs->$approvedField = 1;
            $memberDocs->save();
            $documentName = $memberDocs->getFileNames()[$fileField];
            $writer->addHTML('<h2 class="_hedging" style="color: green">' . $documentName . ' were approved!</h2>');
        }

        foreach ($memberDocs->getFileNames() as $fileField => $fileName) {
//            if (!$memberDocs->$fileField) {
//                $html = "<div class='gallery_item'><p class=\"post_desc\">$fileName</p>
//                            <span>No Image Uploaded</span>
//                         </div>";
//            } else {
            $documentURL = $memberDocs->getDocumentURL($fileField);
            if (!$memberDocs->$fileField) {
                $img = "<div class='gallery_item'>
                                <p class=\"post_desc\">$fileName</p>
                                <span>No Image Uploaded</span>
                            </div>";
            } else {
                $img = "<img width='250px' height='120px' src=\"{$documentURL}\"/>";
            }
            $approved = $memberDocs && $memberDocs->{$fileField . '_approved'};
            $form = !$approved ?
                "<div class='_row'>
                             <form method='POST' action='?mode=Administrator&job=approve_documents&id=" . $member_id . "&fileField=$fileField'>
                                <input type='hidden' name='approveDocuments' value='1'>
                                <div class='_text_center'>
                                    <button type='submit' class='_btn_success'>Approve $fileName</button>
                                </div>
                            </form>
                        </div>" : '';

            $html = "<div class='gallery_item'>
                               <p class=\"post_desc\">$fileName " . ($approved ? "<span style=\"color: green\">(Approved)</span>" : '') . "</p>
                               <a href=\"{$documentURL}\" target='_blank'>
                                   <div class='roll-cover'>
                                       {$img}
                                   </div>
                               </a>
                         </div>
                         $form
                        <br>
                        ";
//            }
            $writer->addHTML($html);

        }
        // Add buttons
        $buttonHtml = "<div>
                            <button type=\"button\" onclick='arhiveFile({$member_id})'>Download files</button>
                            <button type=\"button\" onclick='deleteFile({$member_id})'>Delete files</button>
                        </div>
            ";
        $writer->addHTML($buttonHtml);
        return $writer->getHTML();
    }

    private function viewApprovedCollaboratorDocuments()
    {
        $writer = new StaysailWriter();
        $writer->h1("Approved 2257");
        $options = '<option> </option>';
        $images = '';
        $collaboratorsByEntertainer = [];
       $approvedCollaborators = $this->_framework->getAllIdsRowsByField('Entertainer_Collaborator', 'approved', 1);
       foreach ($approvedCollaborators as $approvedCollaboratorId) {
           $existingCollaborator = new Entertainer_Collaborator($approvedCollaboratorId[0]);
            if (!isset($collaboratorsByEntertainer[$existingCollaborator->Entertainer->id])) {
                $collaboratorsByEntertainer[$existingCollaborator->Entertainer->id] = [
                    'name' => $existingCollaborator->Entertainer->name,
                    '2257' => []
                ];
            }
           $fileName = $existingCollaborator->id . '_completed_2257.jpg';
           $documentURL = $existingCollaborator->getDocumentURL($fileName);
           $img = '';
           if (file_exists($existingCollaborator->getFilePath($fileName))) {
               $img = "<img height=\"120\" src=\"{$documentURL}\"/>";
           }

           $collaboratorsByEntertainer[$existingCollaborator->Entertainer->id]['2257'][] =
               "<p>{$existingCollaborator->stage_name}</p><div class='gallery_item'>
                           <a href=\"{$documentURL}\" target='_blank'>
                               <div class='roll-cover'>
                                   {$img}
                               </div>
                           </a>
                     </div>
                     <br>
                    ";
       }
       foreach ($collaboratorsByEntertainer as $key => $item) {
           $options .= " <option value='{$key}'>{$item['name']}</option>";
           $images .= "<div class='approved_2257_div' style='display: none' id='{$key}'>";
            foreach ($item['2257'] as $img) {
                $images .= $img;
            }
            $images .= '</div>';
       }
        $writer->addHTML("<select id='select_approved_2257'>
            {$options}
        </select>
        <br> {$images}");

        return $writer->getHTML();
    }

    private function approveCollaboratorDocuments($existingCollaboratorId)
    {
        $entertainerCollaborator = new Entertainer_Collaborator($existingCollaboratorId);
        if (!$entertainerCollaborator->id) {
            return 'Wrong link';
        }
        $Member = new Member($entertainerCollaborator->Member_id);
        $type = $Member->getAccountType();
        if (!$this->Admin->has(strtolower($type))) {
            return 'No Access';
        }
        $entertainerCollaborator->approved = 1;
        $entertainerCollaborator->save();

        header("Location:?mode=Administrator&job=view_collaborator_docs&id=" . $entertainerCollaborator->id);
        exit;
    }

    private function viewCollaboratorDocuments($existingCollaboratorId)
    {
        $fileField = StaysailIO::get('fileField');

        $entertainerCollaborator = new Entertainer_Collaborator($existingCollaboratorId);
        if (!$entertainerCollaborator->id) {
            return 'Wrong link';
        }
        $Member = new Member($entertainerCollaborator->Member_id);
        $type = $Member->getAccountType();
        if (!$this->Admin->has(strtolower($type))) {
            return 'No Access';
        }


        $writer = new StaysailWriter();
        $writer->h1("View Collaborator docs");


        $writer->p(StaysailWriter::makeJobLink("Edit {$type} Data", __CLASS__, 'edit_account', $Member->id));
        if ($type != 'Entertainer') {
            return 'Wrong link';
        }

        if (!$entertainerCollaborator->approved) {
            $form = new StaysailForm();
            $form->setPostMethod()
                ->setJobAction(__CLASS__, 'approve_collaborator_docs&id=' . $entertainerCollaborator->id)
                ->setSubmit('Approve');
            $writer->draw($form);
        } else {
            $writer->addHTML('<h2 class="_hedging" style="color: green">2257 is approved</h2>');
        }


        foreach ($entertainerCollaborator->getFileNames() as $fileField) {
            $fileName = $entertainerCollaborator->id . '_' . $fileField . '.jpg';
            $documentURL = $entertainerCollaborator->getDocumentURL($fileName);
            $img = '';
            if (file_exists($entertainerCollaborator->getFilePath($fileName))) {
                $img = "<img height=\"120\" src=\"{$documentURL}\"/>";
            }

            $html = "<div class='gallery_item'>
                           <a href=\"{$documentURL}\" target='_blank'>
                               <div class='roll-cover'>
                                   {$img}
                               </div>
                           </a>
                     </div>
                     <br>
                    ";
            $writer->addHTML($html);
        }
        return $writer->getHTML();
    }

    private function editAccount($member_id)
    {
        $Member = new Member($member_id);
        $type = $Member->getAccountType();
        $account = $Member->getAccountOfType($type);
        if (!$this->Admin->has(strtolower($type))) {
            return 'No Access';
        }

        $writer = new StaysailWriter();
        $writer->h1("Edit {$type} for {$Member->first_name} {$Member->last_name} ({$Member->name})");
        $writer->span(StaysailWriter::makeJobLink("Edit Member Data", __CLASS__, 'edit_member', $member_id), 'admin_edit_member');
        $writer->span(StaysailWriter::makeJobLink(Icon::show(Icon::FOLDER, Icon::SIZE_LARGE), __CLASS__, 'view_documents', $member_id), 'admin_view_documents');
        if ($type == 'Entertainer'){
            $writer->p(StaysailWriter::makeJobLink("Add Referral", __CLASS__, 'add_referrer', $member_id));
        }
        $form = new AdminForm($account);
        $writer->draw($form);

//        if ($type == 'Entertainer') {

        $existingCollaborators = $this->_framework->getAllIdsRowsByField('Entertainer_Collaborator',
            'Member_id', $Member->id);
        foreach ($existingCollaborators as $existingCollaboratorId) {
            $existingCollaborator = new Entertainer_Collaborator($existingCollaboratorId[0]);

            $writer->span($existingCollaborator->stage_name . StaysailWriter::makeJobLink(Icon::show(Icon::FOLDER_GREEN, Icon::SIZE_LARGE), __CLASS__, 'view_collaborator_docs', $existingCollaborator->id), 'view_collaborator_docs');

        }
//        }

        return $writer->getHTML();
    }

    private function editClub($club_id)
    {
        if (!$this->Admin->has('club')) {
            return 'No Access';
        }

        $Club = new Club($club_id);

        $writer = new StaysailWriter();
        $writer->h1("Edit Club");
        $form = new AdminForm($Club);
        $writer->draw($form);

        return $writer->getHTML();
    }

    private function update($class, $id)
    {
        $writer = new StaysailWriter();

        $obj = new $class($id);
        $fields = array_keys($obj->fields());

        if ($class == 'Fan') {
            $active = $obj->active;
            // Has this fan been newly-activated?
            if (!$active and StaysailIO::post('active')) {
                $Member = $obj->Member;
                $Member->active_time = date('Y-m-d H:i:s');
                $Member->expireInDays(30);
                $Member->save();
//                $SMSSender = new SMSSender($Member, 'LocalCityScene');
//                $message = "Congratulations! LSF has activated your membership for a 30 day free trial. Sign in to see your Entertainer at Localcityscene.info.";
//                $SMSSender->send($message);
            }
        }

        $password = null;
        foreach ($fields as $fieldname) {
            $new_value = StaysailIO::post($fieldname);
            if ($fieldname == 'encoded_password' and $new_value) {
                $password = $new_value;
            }
            if ($fieldname == 'video_access') {
                $new_value = $new_value ? 1 : 0;
            }
            if ($fieldname == 'private') {
                $new_value = $new_value ? 1 : 0;
            }
            if ($fieldname == 'email_verified') {
                $new_value = $new_value ? 1 : 0;
            }
            if ($fieldname == 'is_active') {
                $new_value = $new_value ? 1 : 0;
            }
            if ($fieldname == 'is_deleted') {
                $new_value = $new_value ? 1 : 0;
            }
            if ($fieldname == 'allow_custom_pricing') {
                $new_value = $new_value ? 1 : 0;
            }
            if ($fieldname == 'seems_like') {
                $new_value = $new_value ? 1 : 0;
            }
            if ($fieldname == 'most_popular') {
                $new_value = $new_value ? 1 : 0;
            }
            if ($fieldname == 'fan_url' && $new_value) {
                $new_value = str_replace(' ', '_', trim($new_value));
                $filters = array(new Filter(Filter::Where, "id != $id"), new Filter(Filter::Match, array('fan_url' => $new_value)));
                $fanUrlError = $this->_framework->getSingle('Entertainer', $filters);
                if ($fanUrlError) {
                    $errorMsg = "Fan Url exist.";
                    $writer = new StaysailWriter('box-form');
                    $writer->h1("Sorry...", 'join')
                        ->p($errorMsg)
                        ->p(StaysailWriter::makeJobLink('Wish to join?', __CLASS__, 'join'))
                        ->p(StaysailWriter::makeJobLink('Forgot your password?', __CLASS__, 'forgot_pw'));
                    return $writer->getHTML();
                }
            }

            if (preg_match('/date/', $fieldname)) {
                $new_value = date('Y-m-d', strtotime($new_value));
            }

//    		print "Type of {$fieldname}:" . $obj->typeOf($fieldname) . "<br>\n";
//    		if ($obj->typeOf($fieldname) == StaysailEntity::Boolean) {$new_value = $new_value ? 1 : 0;}
//    		print "{$fieldname} => {$new_value}<br/>\n";
            $obj->$fieldname = $new_value;
        }
        if ($password !== null) {
            $obj->setPassword($password);
        }
        $obj->save();

        $writer->h1("{$class} ({$obj->name}) has been updated");
        return $writer->getHTML();
    }

    private function getMailer()
    {
        $writer = new StaysailWriter();
        $writer->h1("Send Mail");

        if (StaysailIO::post('name')) {
            $defaults = $_POST;
        } else {
            $defaults = array();
        }
        $filters = array(
            new Filter(Filter::Sort, "id DESC"),
        );
        $members = $this->_framework->getSubset('Member', $filters);
        $entertainerOptions = '';
        $fanOptions = '';
        foreach ($members as $member){
            if ($member->email){
                if($member->getAccountType() == 'Entertainer'){
                    $entertainerOptions .= "<option value='$member->id'>$member->email</option>";
                }
                elseif ($member->getAccountType() == 'Fan'){
                    $fanOptions .= "<option value='$member->id'>$member->email</option>";
                }

            }
        }

        $optionsEntertainer = '<label>Entertainers
                                <select class="all_members" name="individual_members[]" multiple disabled>';
        $optionsEntertainer .= $entertainerOptions;
        $optionsEntertainer .= '</select></label>';
        $optionsFan = '<label>Fans
                        <select class="all_members" name="individual_members[]" multiple disabled>';
        $optionsFan .= $fanOptions;
        $optionsFan .= '</select></label>';
        $allMembers = '<div class="all_members_div">';
        $allMembers .= $optionsEntertainer.$optionsFan;
        $allMembers .= '</div>';
        $form = new StaysailForm('admin');
        $form->setPostMethod()
            ->setDefaults($defaults)
            ->setJobAction(__CLASS__, 'send_mail')
            ->addField(StaysailForm::Line, 'Subject', 'name', 'required')
//            ->addField(StaysailForm::Text, 'Message', 'content', 'richtext')
            ->addHTML('<textarea id="editor1" class="ckeditor" name="content"></textarea>')
//            ->addHTML('<textarea class="" name="content"></textarea>')
            ->addField(StaysailForm::Checkbox, 'Send to', 'individuals', 'individual_mail', array('Member' => 'Members'))
            ->addHTML("$allMembers")
            ->addField(StaysailForm::Checkbox, 'Send to', 'recipients', '', array('Entertainer' => 'Entertainers', 'Fan' => 'Fans'))
            ->addField(StaysailForm::Checkbox, 'Send to', 'delivery', '', array('internal' => 'LocalStripFan Private Message', 'external' => 'Regular Email'))
            ->setSubmit('Send Message');
        $writer->draw($form);
        return $writer->getHTML();
    }

    private function sendMail()
    {
        $writer = new StaysailWriter();
        $name = StaysailIO::post('name');
        $content = StaysailIO::post('content');
        $delivery = StaysailIO::post('delivery');
        $recipients = StaysailIO::post('recipients');
        $individuals = StaysailIO::post('individuals');
        $memberIds = StaysailIO::post('individual_members');
        if (!is_array($delivery)) {
            $delivery = array();
        }
        if (!is_array($individuals)) {
            $individuals = array();
        }
        $members = array();
        if (!sizeof($individuals)){
            if (!$name or !$content or !sizeof($delivery) or !sizeof($recipients)) {
                $writer->h1('Oops!');
                $writer->p("Please make sure that the subject and the message are filled out, and that you have selected at least one delivery method (Private Message or Email), and that you have selected at least one recipient type:");
                $writer->addHTML($this->getMailer());
                return $writer->getHTML();
            }

            // Send to all types
            if (in_array('Entertainer', $recipients)) {
                $entertainers = $this->_framework->getSubset('Entertainer');
                foreach ($entertainers as $Entertainer)
                {
                    $members[] = $Entertainer->Member;
                }
            }
            if (in_array('Fan', $recipients)) {
                $fans = $this->_framework->getSubset('Fan');
                foreach ($fans as $Fan)
                {
                    $members[] = $Fan->Member;
                }
            }
        }

        if (in_array('Member', $individuals)) {
            $filter = array(
                new Filter(Filter::IN, array('id' => $memberIds)),
            );
            $members = $this->_framework->getSubset('Member', $filter);
        }

        $optout_count = 0;
        $reply = "support@yourfanslive.com";
        foreach ($members as $Member)
        {
            if (in_array('internal', $delivery)) {
                $Private_Message = new Private_Message();
                $Private_Message->update(array('name' => $name, 'content' => strip_tags($content), 'send_time' => StaysailIO::now(),
                    'deleted' => 0, 'from_Admin_id' => $this->Admin->id, 'to_Member_id' => $Member->id));
                $Private_Message->save();
            }

            if (in_array('external', $delivery) || in_array('Member', $individuals)) {
                if (!$Member->optedOutOfEmail()) {
                    $email = $Member->email;
                    $url = MAIN_URL;
                    $uri_email = urlencode($email);
                    $optout = <<<__END__
			    	<br/><br/>
    				<hr/>
    				<p>If you do not wish to continue to receive emails from us, you may opt out at any time by
    					clicking <a href="{$url}/optout/?email={$uri_email}">here.</a></p>
__END__;

                    $MailSend = new MailSend($Member);
                    $MailSend->send($email, $name, $content);
//                    mail($email, $name, $content . $optout, "Content-Type:text/html\nReply-to:{$reply}\nFrom:{$reply}");
                } else {
                    $optout_count++;
                }
            }
        }
        $writer->h1('Done!');
        $writer->p("Sent message to " . sizeof($members));
        if ($optout_count) {
            $writer->p("({$optout_count} opted out of the external email)");
        }
        return $writer->getHTML();

        // TODO:send email
    }

    private function adminMenu()
    {
        if (!$this->Admin->has('admin')) {
            return 'No Access!';
        }

        $writer = new StaysailWriter();
        $writer->h1("Manage Administrators");
        $writer->p(StaysailWriter::makeJobLink('Add Administrator', __CLASS__, 'edit_admin', 0));

        $writer->p("<form>Find: <input type=\"text\" name=\"q\" id=\"q\" onfocus=\"startAdminSearch()\" /></form>");


        $collection = $this->_framework->getSubset('Admin', new Filter(Filter::Sort, 'name'));
        $table = new StaysailTable('admin');
        foreach ($collection as $Admin)
        {
            $delete = $this->Admin->id != $Admin->id
                ? StaysailWriter::makeJobLink('Delete', __CLASS__, 'delete_admin', $Admin->id)
                : '';

            $row = array($Admin->username, $Admin->real_name, $Admin->email,
                StaysailWriter::makeJobLink('Edit', __CLASS__, 'edit_admin', $Admin->id),
                $delete,
            );
            $table->addRow($row);
        }
        $writer->draw($table);
        return $writer->getHTML();
    }

    private function editAdmin($admin_id)
    {
        $writer = new StaysailWriter();
        if ($admin_id) {
            $writer->h1('Edit Administrator');
        } else {
            $writer->h1('Add Administrator');
        }
        $Admin = new Admin($admin_id);

        $form = new StaysailForm();
        $form->setPostMethod()
            ->setDefaults($Admin->info())
            ->setAction("?mode=Administrator&job=post_admin&id={$admin_id}")
            ->setSubmit('Update Administrator')
            ->addField(StaysailForm::Line, 'Real Name', 'real_name', 'required')
            ->addField(StaysailForm::Line, 'User Name', 'username', 'required')
            ->addField(StaysailForm::Password, 'Password', 'newpass')
            ->addField(StaysailForm::Line, 'Email', 'email', 'required');

        foreach ($this->menu as $priv => $label)
        {
            $priv = preg_replace('/(.*:)/', '', $priv);
            $on = $Admin->has($priv);
            if ($on) {
                $form->setDefaults(array($priv => 1));
            }
            $form->addField(StaysailForm::Boolean, $label, $priv);
        }

        $writer->draw($form);
        return $writer->getHTML();
    }

    private function postAdmin($admin_id)
    {
        $Admin = new Admin($admin_id);
        $Admin->updateFrom(array('real_name', 'username', 'email'));
        if (StaysailIO::post('newpass')) {
            $password = md5(StaysailIO::post('newpass'));
            $Admin->password = $password;
        }
        $Admin->save();

        $this->_framework->query("DELETE FROM Admin_Privilege WHERE Admin_id = {$Admin->id}");

        foreach ($this->menu as $priv => $label) {
            $priv = preg_replace('/(.*:)/', '', $priv);
            if (StaysailIO::post($priv)) {
                $Admin_Privilege = new Admin_Privilege();
                $Admin_Privilege->update(array('Admin_id' => $Admin->id, 'name' => $priv));
                $Admin_Privilege->save();
            }
        }

        return $this->editAdmin($Admin->id);
    }

    private function deleteAdmin($admin_id)
    {
        $Admin = new Admin($admin_id);
        $Admin->delete_Job();
        return $this->adminMenu();
    }

    private function captureMenu()
    {
        $writer = new StaysailWriter();
        $filters = array(new Filter(Filter::Sort, 'id DESC'), new Filter(Filter::Match, array('payment_captured' => 0)));
        $statuses = $this->_framework->getSubset('Fan_WebShow_Status', $filters);
        $table = new StaysailTable('admin');

        $table->setColumnHeaders(array('Age (Hours)', 'Order Time', 'Authorization Amount', 'Member Name', 'Minutes Authorized',
            'Minutes Used', 'Price Per Minute', 'Capture Amount', 'CAPTURE'));
        foreach ($statuses as $Fan_WebShow_Status) {
            if (!$Fan_WebShow_Status->last_poll_time) {
                continue;
            }
            $age = number_format((time() - strtotime($Fan_WebShow_Status->last_poll_time)) / 3600, 1);
            $minutes_used = $Fan_WebShow_Status->getMinutesUsed();
            if ($age < 4) {
                $age = "<div style=\"color:red\">{$age}</div>";
            }
            $capture_amount = $minutes_used * $Fan_WebShow_Status->WebShow->channel_price;
            $capture_link = StaysailWriter::makeJobLink('Capture', __CLASS__, 'complete_capture', $Fan_WebShow_Status->id);
            $table->addRow(array($age,
                $Fan_WebShow_Status->Order->payment_time,
                $Fan_WebShow_Status->Order->payment_amount,
                $Fan_WebShow_Status->Order->Member->getRealFullName(),
                $Fan_WebShow_Status->minutes_purchased,
                $minutes_used,
                $Fan_WebShow_Status->WebShow->channel_price,
                $capture_amount,
                $capture_link,
            ));
        }
        $writer->draw($table);
        return $writer->getHTML();
    }

    private function completeCapture($id)
    {
        $writer = new StaysailWriter();

        $Fan_WebShow_Status = new Fan_WebShow_Status($id);
        $capture_amount = $Fan_WebShow_Status->getMinutesUsed() * $Fan_WebShow_Status->WebShow->channel_price;

        $gateway = new PayTechTrustPaymentGateway();
        $gateway->setOrder($Fan_WebShow_Status->Order);
        $response = $gateway->capture($capture_amount);
        if ($response) {
            $writer->draw($Fan_WebShow_Status->Order);
            $Fan_WebShow_Status->payment_captured = 1;
            $Fan_WebShow_Status->save();
        } else {
            $writer->h1('Capture has failed');
            $writer->p("Here's the gateway's response:");
            $writer->addHTML("<pre>" . print_r($gateway->getLastResponse(), true) . "</pre>");
        }
        return $writer->getHTML();
    }

    private function reportMenu()
    {
        $writer = new StaysailWriter();
        $writer->h1('Reports');

        $table = new StaysailTable('admin');
        $table->setColumnHeaders(array('Report Name', 'Show in Browser', 'Excel'));

        if ($handle = opendir('../private/reports')) {
            while (false !== ($entry = readdir($handle))) {
                if (preg_match('/report\.(.+).php/', $entry, $m)) {
                    $classname = $m[1];
                    require "../private/reports/{$entry}";
                    $Report = new $classname();
                    $html_link = StaysailWriter::makeJobLink('HTML', __CLASS__, 'run_report', $classname);
                    $csv_link = StaysailWriter::makeJobLink('CSV', __CLASS__, 'run_report', $classname);
                    $table->addRow(array($Report->getName(), $html_link, $csv_link));
                }
            }
        }

        $writer->draw($table);
        return $writer->getHTML();
    }

    private function earningsMenu()
    {
        $writer = new StaysailWriter();
        $writer->h1('Earnings');
        $earnings = $this->_framework->getEarnings();

        $table = new StaysailTable('admin');
        $table->setColumnHeaders(array('Name', 'Earning'));
        foreach ($earnings as $earning) {
            $table->addRow(array($earning[1], $earning[2]));
        }
        $writer->draw($table);
        return $writer->getHTML();
    }

    private function runReport($classname)
    {
        $writer = new StaysailWriter();

        StaysailIO::cleanse($classname, StaysailIO::Filename);
        $filepath = "../private/reports/report.{$classname}.php";
        if (file_exists($filepath)) {
            require $filepath;
            $Report = new $classname();
            $writer->h1($Report->getName());
            $writer->draw($Report);
        }

        return $writer->getHTML();
    }

    private function editSettings()
    {
        $writer = new StaysailWriter();
        $form = new StaysailForm();
        $form->setPostMethod()
            ->setJobAction(__CLASS__, 'post_settings')
            ->setSubmit('Save Settings');
        $defaults = array();
        foreach ($this->setting_names as $label => $name) {
            $value = $this->_framework->getSetting($name);
            $defaults[$name] = $value;
            $form->setDefaults(array($name => $value))
                ->addField(StaysailForm::Line, $label, $name);
        }
        $writer->h1('Global Settings')
            ->draw($form);
        return $writer->getHTML();
    }

    private function postSettings()
    {
        foreach ($this->setting_names as $name) {
            $value = StaysailIO::post($name);
            $this->_framework->setSetting($name, $value);
        }
    }

    private function setClubs($member_id)
    {
        if (!$this->Admin->has('entertainer')) {
            return 'No Access';
        }
        $writer = new StaysailWriter();
        $Member = new Member($member_id);
        $Entertainer = $Member->getAccountOfType('Entertainer');

        $writer->H1("Set Clubs for {$Entertainer->stage_name}");

        $clubs = $Entertainer->getClubs();
        $club_ids = array();
        foreach ($clubs as $Club) {
            $club_ids[] = $Club->id;
        }

        $form = new StaysailForm();
        $form->setPostMethod()
            ->setJobAction(__CLASS__, 'post_set_clubs', $member_id)
            ->setSubmit('Save Club Assignments')
            ->setDefaults(array('club_id' => $club_ids));

        $filters = array(new Filter(Filter::Sort, 'name'), new Filter(Filter::Match, array('is_deleted' => 0)));
        $collection = $this->_framework->getSubset('Club', $filters);
        $options = array();
        foreach ($collection as $Club) {
            $number = $Club->account_number ? " #{$Club->account_number}" : '';
            $options[$Club->id] = "{$Club->name} ({$Club->city}, {$Club->state}){$number}";
        }
        $form->addField(StaysailForm::Checkbox, 'Club Assignments', 'club_id', '', $options);
        $writer->draw($form);

        return $writer->getHTML();
    }

    private function postSetClubs($member_id)
    {
        if (!$this->Admin->has('entertainer')) {
            return 'No Access';
        }

        $club_ids = StaysailIO::post('club_id');
        $Member = new Member($member_id);
        $Entertainer = $Member->getAccountOfType('Entertainer');
        $Entertainer->assignClubsByID($club_ids);
        return $this->setClubs($member_id);
    }

    private function subscriptionMenu($member_id)
    {
        if (!$this->Admin->has('fan')) {
            return 'No Access';
        }
        $writer = new StaysailWriter();
        $Member = new Member($member_id);
        $Fan = $Member->getAccountOfType('Fan');

        $writer->H1("Manage Subscriptions for <strong>{$Fan->name}</strong>");
        $table = new StaysailTable('admin');
        $subscriptions = $Fan->getActiveSubscriptions();


        foreach ($subscriptions as $Fan_Subscription) {
            $remove_link = StaysailWriter::makeJobLink("Unsubscribe", __CLASS__, 'remove_subscription', "{$member_id}&subscription_id={$Fan_Subscription->id}", '', 'Are you sure?');
            $row = array('Entertainer' => $Fan_Subscription->Entertainer->name,
                'Unsubscribe' => $remove_link,
            );
            $table->addRow($row);
        }
        $writer->draw($table);

        $writer->H1("Add Subscription");

        $filters = [
            new Filter(Filter::Sort, 'name'),
            new Filter(Filter::Match, ['is_deleted' => 0]),
            new Filter(Filter::IsNotNull, 'Member_id'),
            new Filter(Filter::Where, "name != ''"),
            //new Filter(Filter::Where, "Fan_id != " . $Fan->id)
        ];

      //  $options = $this->_framework->getOptions('Entertainer', $filters);


//        $fanId = $Fan->id;
//        $query = "
//    SELECT *
//    FROM Entertainer
//    WHERE id NOT IN (
//        SELECT Entertainer_id
//        FROM Fan_Subscription
//        WHERE Fan_id = :fanId
//    )
//    AND is_deleted = '0'
//    AND Member_id IS NOT NULL
//    AND name != ''
//";
//        $params = [':fanId' => $fanId];




        $sql= "
    SELECT *
    FROM Entertainer
    WHERE id NOT IN (
        SELECT Entertainer_id
        FROM Fan_Subscription
        WHERE Fan_id = {$Fan->id}
    )
    AND is_deleted = '0'
    AND Member_id IS NOT NULL
    AND name != ''
    AND is_active = '1'
    ORDER BY name;
";
        $this->_framework->query($sql);
        $options = [];
        while ($row = $this->_framework->getNextRow())
        {
            $options[$row['id']] = $row['name'];
        }



        $form = new StaysailForm();
        $form->setSubmit('Subscribe to Entertainer');
        $form->setGetMethod();
        $form->setDefaults(array('mode' => __CLASS__, 'job' => 'add_subscription', 'id' => $member_id));
        $form->addField(StaysailForm::Hidden, '', 'mode');
        $form->addField(StaysailForm::Hidden, '', 'job');
        $form->addField(StaysailForm::Hidden, '', 'id');
        $form->addField(StaysailForm::Select, 'Entertainer', 'entertainer_id', '', $options);

        $writer->draw($form);

        return $writer->getHTML();
    }

    private function removeSubscription($subscription_id)
    {
        if (!$this->Admin->has('fan')) {
            return 'No Access';
        }

        $Fan_Subscription = new Fan_Subscription($subscription_id);
        $writer = new StaysailWriter();
        $writer->H1("Unsubscribed from <strong>{$Fan_Subscription->Entertainer->name}</strong>");

        $Fan_Subscription->active = 0;
        $Fan_Subscription->save();

        return $writer->getHTML();
    }

    private function addSubscription($member_id, $entertainer_id)
    {
        if (!$this->Admin->has('fan')) {
            return 'No Access';
        }
        $writer = new StaysailWriter();
        $Member = new Member($member_id);

        $Fan = $Member->getAccountOfType('Fan');
        $Entertainer = new Entertainer($entertainer_id);
        $Entertainer_member = new Member($Entertainer->Member_id);

        $Fan->subscribeTo($Entertainer);

        $nameFan = $Fan->name;
        $messageGmail = "Congratulations! You have a new subscriber with name '$nameFan'";
        $subject = 'New subscriber';
        $MailSend = new MailSend($Entertainer_member);
        $MailSend->send($Entertainer_member->email, $subject, $messageGmail, 0, false);

        $writer->H1("<strong>{$Fan->name}</strong> subscribed to <strong>{$Entertainer->name}</strong>");
        return $writer->getHTML();
    }

    public function EntertainerMemberDocs()
    {
        $filters = array(new Filter(Filter::Sort, 'id'));
        $Entertainers = $this->_framework->getSubset('Entertainer', $filters);
        foreach ($Entertainers as $Entertainer) {
            $memberDocs = $this->_framework->getRowByField('Member_Docs', 'Member_id', $Entertainer->Member_id);
            $memberDocs = new Member_Docs(isset($memberDocs['id']) ? $memberDocs['id'] : null);
            $fields = array('doc_type', 'release_form_ref_name');
            $memberDocs->updateFrom($fields);
            $memberDocs->Member_id = $Entertainer->Member_id;
            $memberDocs->Entertainer_id = $Entertainer->id;
            $memberDocs->doc_type = 1;
            $memberDocs->save();
        }
    }

    private function addReferrer($id)
    {
        if (!$this->Admin->has('fan')) {
            return 'No Access';
        }
        $writer = new StaysailWriter();
        $Member = new Member($id);
        $Entertainer = $Member->getAccountOfType('Entertainer');
        $writer->H1("Manage Referrals for <strong> {$Entertainer->name} </strong>");
        $table = new StaysailTable('admin');

        $filters = array(new Filter(Filter::Sort, 'name'), new Filter(Filter::Match, array('referrer_id' => $Entertainer->id)), new Filter(Filter::Match, array('is_deleted' => 0)), new Filter(Filter::Where, "Member_id != $id"));
        $Referrals = $this->_framework->getOptions('Entertainer', $filters);

        foreach ($Referrals as $EntertainerId => $Referral) {
            $remove_link = StaysailWriter::makeJobLink("Remove Referral", __CLASS__, 'remove_referral', "{$id}&entertainer_id={$EntertainerId}", '', 'Are you sure?');
            $row = array('Referral' => $Referral,
                'Remove Referral' => $remove_link,
            );
            $table->addRow($row);
        }
        $writer->draw($table);
        $writer->H1("Add Referrals");
        $filters = array(new Filter(Filter::Sort, 'name'), new Filter(Filter::Match, array('is_deleted' => 0)), new Filter(Filter::Where, "Member_id != $id"), new Filter(Filter::Where, "(referrer_id IS NULL OR referrer_id=0)"));
        $Entertainers = $this->_framework->getOptions('Entertainer', $filters);
        $options = '<option value="" selected disabled hidden>Choose here</option>';
        foreach ($Entertainers as $key => $Creator) {
            $options .= "<option value='{$key}'>{$Creator}</option>";
        }
        $form = new StaysailForm();
        $form->setSubmit('Add to Referral');
        $form->setPostMethod();
        $form->setJobAction(__CLASS__, 'put_referral', $id);
        $form->addHTML("<select name='referral_id'>
                            $options
                        </select>");
        $writer->draw($form);

        return $writer->getHTML();
    }

    private function putReferral($id)
    {
        $Member = new Member($id);
        $referrerEntertainer = $Member->getAccountOfType('Entertainer');
        $referralId = StaysailIO::post('referral_id');
        $Entertainer = new Entertainer($referralId);
        $Entertainer->referrer_id = $referrerEntertainer->id;
        $Entertainer->save();

        return header('Location: ' . $_SERVER['HTTP_REFERER']);
    }

    private function removeReferral()
    {
        $id = StaysailIO::get('id');
        $entertainerId = StaysailIO::get('entertainer_id');
        $Entertainer = new Entertainer($entertainerId);
        $Entertainer->referrer_id = 0;
        $Entertainer->save();

        return header("Location:?mode=Administrator&job=add_referrer&id=$id");
    }
}
