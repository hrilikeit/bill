<?php

class SeemsLikeView
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
        $id = $this->Member->id;
        $filter = array(new Filter(Filter::Match, array('seems_like' => 1),
            new Filter(Filter::Where, "Member_id != $id")),
            new Filter(Filter::IsNotNull, 'Member_id'),
            new Filter(Filter::Match, array('is_deleted' => 0)
            ));
        $seemsLikes = $this->framework->getSubset('Entertainer', $filter);
        $allSeemsLikes = '';
        $seemsLikesGroup =  array_chunk($seemsLikes, 3);
        foreach ($seemsLikesGroup as $seemsLikeGroup) {
            $allSeemsLikes .= "<div class='swiper-slide'>";
            foreach ($seemsLikeGroup as $seemsLike) {
                $seemsLikeMember = new Member($seemsLike->Member_id);
                $avatarUrl = $seemsLikeMember->getAvatarURL();
                $displayUrl = $seemsLikeMember->getEntertainerDisplayPhotoURL();
                $entertainerAvatar = DATAROOT . "/private/avatars/entertainerAvatar{$seemsLike->Member_id}";

                foreach (['png', 'jpg', 'jpeg'] as $format) {
                    if (file_exists($entertainerAvatar . '.' . $format)) {
                        $avatarUrl = $seemsLikeMember->getEntertainerAvatarURL();
                    };
                }

                $avatar = DATAROOT . "/private/avatars/avatar{$seemsLike->Member_id}";
                foreach (['png', 'jpg', 'jpeg'] as $format) {
                    if (file_exists($avatar . '.' . $format)) {
                        $avatarUrl = $seemsLikeMember->getAvatarURL();
                    }
                }

                $entertainerDisplayPhoto = DATAROOT . "/private/avatars/entertainerDisplayPhoto{$seemsLike->Member_id}";
                foreach (['png', 'jpg', 'jpeg'] as $format) {
                    if (file_exists($entertainerDisplayPhoto . '.' . $format)) {
                        $displayUrl = $seemsLikeMember->getEntertainerDisplayPhotoURL();
                    }
                }

                if ($this->Member->getAccountOfType('Fan')) {
                    $name = "<a target='_blank' class='friend-name-a' href='?mode=EntertainerProfile&entertainer_id=$seemsLike->id'>
                            <div class='card_name'>$seemsLike->stage_name</div>
                         </a>";
                } else {
                    $name = "<div class='card_name'>$seemsLike->stage_name</div>";
                }

                $allSeemsLikes .= "<div class='swiper-slide-item'>
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
            $allSeemsLikes .= "</div>";
        }
        $writer->start('swiper_container');
        $writer->addHTML("<div class='swiper mySwiper'>
                            <div class='cards_head'>
                              <div class='cards_head-title'><b>Like $this->Entertainer</b></div>
                              <div class='cards_head-btn'>
                                <button class='button-prev'> < </button>
                                <button class='button-next'> > </button>
                              </div>
                        
                            </div>
                            
                            <div class='swiper-wrapper'>
                              $allSeemsLikes
                            </div>
                            <div class='swiper-pagination'></div>
                          </div>");
        $writer->end('swiper_container');

        return $writer->getHTML();
    }
}