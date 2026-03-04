<?php

class FriendsView
{
    private $Member;
    private $Entertainer;
    protected $framework;

    public function __construct(Member $Member)
    {
        $this->Member = $Member;
        $this->framework = StaysailIO::engage();
        if (StaysailIO::session('Entertainer.id')) {
            $this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
        } else {
            $this->Entertainer = $this->Member->getAccountOfType('Entertainer');
        }
    }

    public function getHTML()
    {
        $writer = new StaysailWriter('friends-view');
        $filter = new Filter(Filter::Match, array('referrer_id' => $this->Entertainer->id));
        $friends = $this->framework->getSubset('Entertainer', $filter);
        $allFriends = '';
        $friendsGroup =  array_chunk($friends, 3);
        foreach ($friendsGroup as $friendGroup) {

            $allFriends .= "<div class='swiper-slide'>";
            foreach ($friendGroup as $friend) {

                $friendMember = new Member($friend->Member_id);
                $avatarUrl = $friendMember->getAvatarURL();
                $displayUrl = $friendMember->getEntertainerDisplayPhotoURL();
                $entertainerAvatar = DATAROOT . "/private/avatars/entertainerAvatar{$friend->Member_id}";

                foreach (['png', 'jpg', 'jpeg'] as $format) {
                    if (file_exists($entertainerAvatar . '.' . $format)) {
                        $avatarUrl = $friendMember->getEntertainerAvatarURL();
                    };
                }

                $avatar = DATAROOT . "/private/avatars/avatar{$friend->Member_id}";
                foreach (['png', 'jpg', 'jpeg'] as $format) {
                    if (file_exists($avatar . '.' . $format)) {
                        $avatarUrl = $friendMember->getAvatarURL();
                    }
                }

                $entertainerDisplayPhoto = DATAROOT . "/private/avatars/entertainerDisplayPhoto{$friend->Member_id}";
                foreach (['png', 'jpg', 'jpeg'] as $format) {
                    if (file_exists($entertainerDisplayPhoto . '.' . $format)) {
                        $displayUrl = $friendMember->getEntertainerDisplayPhotoURL();
                    }
                }

                if ($this->Member->getAccountOfType('Fan')) {
                    $name = "<a target='_blank' class='friend-name-a' href='?mode=EntertainerProfile&entertainer_id=$friend->id'>
                            <div class='card_name'>$friend->stage_name</div>
                         </a>";
                } else {
                    $name = "<div class='card_name'>$friend->stage_name</div>";
                }

                $allFriends .= "<div class='swiper-slide-item'>
                                <img class='card_background' src='$displayUrl' alt=''>
                                <div class='user_img-content'>
                                  <img src='$avatarUrl' alt=''>
                                </div>
                                <div class='card_name-content'>
                                  $name
                                </div>
                            </div>
                            ";
            }
            $allFriends .= "</div>";
        }
        $writer->start('swiper_container');
        $writer->addHTML("<div class='swiper mySwiper'>
                            <div class='cards_head'>
                              <div class='cards_head-title'><b>$this->Entertainer Friends</b></div>
                              <div class='cards_head-btn'>
                                <button class='button-prev'> < </button>
                                <button class='button-next'> > </button>
                              </div>
                        
                            </div>
                            
                            <div class='swiper-wrapper'>
                              $allFriends
                            </div>
                            <div class='swiper-pagination'></div>
                          </div>");
        $writer->end('swiper_container');


        return $writer->getHTML();
    }
}