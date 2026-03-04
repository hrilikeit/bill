<?php
class SummaryView
{
	private $Member;
	private $Entertainer;

	public function __construct(Member $Member, $withoutSession = false)
	{
		$this->Member = $Member;
		if (!$withoutSession && StaysailIO::session('Entertainer.id')) {
			$this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
		} else {
			$this->Entertainer = $this->Member->getAccountOfType('Entertainer');
		}
	}

	public function getHTML()
	{
		$writer = new StaysailWriter('summary');
		//$writer->h1($this->Entertainer->name);
        $verifyImg = '';
        if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
            if ($this->Entertainer->getMemberDocs()->allIdDocsApproved()) {
                $verifyImg = "<img src='site_img/icons/43.png'>";
            }
            $notify = StaysailIO::session('notify_fans') ? '' : "<div class='fun_edit-btn'>
                                                                    <a href=\"?mode=EntertainerProfile&job=update_bio\" class=\"button\">Edit profile</a>
                                                                    <a class=\"button green\" href=\"?mode=EntertainerProfile&job=notify\">I'm online</a>
                                                                 </div>";
            $writer->addHTML("<div class='name_content-container'>
                                <div class='name_content'>
                                    <p>{$this->Member->name}</p>
                                    {$verifyImg}
                                </div>
                                {$notify}
                              </div>");
        }
        if ($this->Entertainer){
            if ($this->Member->getRole() == Member::ROLE_FAN) {
                $EntertainerMember = new Member($this->Entertainer->Member_id);
                if ($this->Entertainer->getMemberDocs()->allIdDocsApproved()) {
                    $verifyImg = "<img src='site_img/icons/43.png'>";
                }
                $writer->addHTML("<div class='name_content-container'>
                                    <div class='name_content'>
                                        <p>{$EntertainerMember->name}</p>
                                        {$verifyImg}
                                    </div>
                                  </div>
                                  <div class='profile-buttons'>
                                    <a href='javascript:void(0);' id='myBtnBio' class=\"button grey\">About {$this->Entertainer->name}</a>
                                    <a href=\"?mode=Purchase&job=purchase&type=Tip\" class=\"button green\">Tip</a>
                                  </div>");
            }

            $bioEntertainer =$this->Entertainer->bio;

            if (empty($bioEntertainer)){
            $writer->addHTML("<div class='modal' id='myModalBio'>
                            <div class='modal-content'>
                                <span class='close close_bio_modal'>&times;</span>
                                <h1><strong>".$this->Entertainer->stage_name." does not have a description yet.</strong></h1>
                            </div>
                          </div>"
                          );
                }
            $writer->addHTML("<div class='modal' id='myModalBio'>
                            <div class='modal-content'>
                            <h1><strong>".$this->Entertainer->stage_name." info</strong></h1>
                                <span class='close close_bio_modal'>&times;</span>
                                ".$this->Entertainer->bio."
                            </div>
                          </div>"
                           );
//		$writer->p($this->Entertainer->getStarRatingHTML());
//		$writer->addHTML($this->getConnections());
        }

		return $writer->getHTML();
	}

	public function getPhotoSummaryHTML()
	{
		$writer = new StaysailWriter('photo_summary');
		$entertainer_member = $this->Entertainer->Member;
        $avatar = $entertainer_member->getAvatarHTML(Member::AVATAR_LARGE);
        if ($this->Member->getRole() == Member::ROLE_ENTERTAINER){
            $avatar = "<div>
                            {$entertainer_member->getAvatarHTML(Member::AVATAR_LARGE)}
                            <form id='avatarForm' action='?mode=EntertainerProfile&job=upload_avatar' method='post' enctype='multipart/form-data'>
                                <input type='file' id='fileInput' name='image' style='display: none'>
                            </form>
                            <!-- <img src='https://t4.ftcdn.net/jpg/02/83/72/41/360_F_283724163_kIWm6DfeFN0zhm8Pc0xelROcxxbAiEFI.jpg' width='40' id='uploadIcon' onclick='uploadIcon()'> -->
                            <img src='https://t4.ftcdn.net/jpg/02/83/72/41/360_F_283724163_kIWm6DfeFN0zhm8Pc0xelROcxxbAiEFI.jpg' width='40' id='uploadIcon' onclick='uploadIcon()'>
                        </div>";
        }
		$writer->addHTML($avatar);

		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER and StaysailIO::get('h')) {
			$writer->addHTML(StaysailWriter::makeJobLink("Don't like your headshot?  Click here to do it again.", 'EntertainerGallery', 'crop_avatar', StaysailIO::get('h')));
		}

		return $writer->getHTML();
	}

	public function getPhotoLandscapeHTML()
	{
		$writer = new StaysailWriter('photo_landscape');
		$entertainer_member = $this->Entertainer->Member;
        if ($this->Member->getRole() == Member::ROLE_ENTERTAINER){
            $displayPhoto = "<div>
                            {$entertainer_member->getEntertainerDisplayPhotoHTML(Member::DISPLAY_LARGE)}
                            <form id='displayForm' action='?mode=EntertainerProfile&job=upload_display_photo' method='post' enctype='multipart/form-data'>
                                <input type='file' id='fileInputDisplay' name='displayPhoto' style='display: none'>
                            </form>
                            <img src='https://t4.ftcdn.net/jpg/02/83/72/41/360_F_283724163_kIWm6DfeFN0zhm8Pc0xelROcxxbAiEFI.jpg' width='40' id='uploadDisplay' onclick='uploadDisplay()'>
                        </div>";
        }
        else{
            $displayPhoto = $entertainer_member->getEntertainerDisplayPhotoHTML(Member::DISPLAY_LARGE);
        }

		$writer->addHTML($displayPhoto);

		/*if ($this->Member->getRole() == Member::ROLE_ENTERTAINER and StaysailIO::get('h')) {
			$writer->addHTML(StaysailWriter::makeJobLink("Don't like your headshot?  Click here to do it again.", 'EntertainerGallery', 'crop_avatar', StaysailIO::get('h')));
		}*/

		return $writer->getHTML();
	}
}