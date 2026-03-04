<?php

class MostPopularView
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
        $writer = new StaysailWriter('most-popular-view');
        $id = $this->Member->id;
        $entertainers = $this->Member->getPopularEntertainers();
        $allEntertainers = '';
        $entertainersGroup =  array_chunk($entertainers, 3);
        foreach ($entertainersGroup as $entertainerGroup) {
            $allEntertainers .= "<div class='swiper-slide'>";
            foreach ($entertainerGroup as $entertainer) {
                $seemsLikeMember = new Member($entertainer->Member_id);
                $avatarUrl = $seemsLikeMember->getAvatarURL();
                $displayUrl = $seemsLikeMember->getEntertainerDisplayPhotoURL();
                $entertainerAvatar = DATAROOT . "/private/avatars/entertainerAvatar{$entertainer->Member_id}";

                foreach (['png', 'jpg', 'jpeg'] as $format) {
                    if (file_exists($entertainerAvatar . '.' . $format)) {
                        $avatarUrl = $seemsLikeMember->getEntertainerAvatarURL();
                    };
                }

                $avatar = DATAROOT . "/private/avatars/avatar{$entertainer->Member_id}";
                foreach (['png', 'jpg', 'jpeg'] as $format) {
                    if (file_exists($avatar . '.' . $format)) {
                        $avatarUrl = $seemsLikeMember->getAvatarURL();
                    }
                }

                $entertainerDisplayPhoto = DATAROOT . "/private/avatars/entertainerDisplayPhoto{$entertainer->Member_id}";
                foreach (['png', 'jpg', 'jpeg'] as $format) {
                    if (file_exists($entertainerDisplayPhoto . '.' . $format)) {
                        $displayUrl = $seemsLikeMember->getEntertainerDisplayPhotoURL();
                    }
                }

                if ($this->Member->getAccountOfType('Fan')) {
                    if (!$entertainer->stage_name){
                        $sgName = $entertainer->name;
                    }
                    else{
                        $sgName = $entertainer->stage_name;
                    }
                    $name = "<a target='_blank' class='friend-name-a' href='?mode=EntertainerProfile&entertainer_id=$entertainer->id'>
                            <div class='card_name'>$sgName</div>
                         </a>";
                } else {
                    $name = "<div class='card_name'>$sgName</div>";
                }

                $allEntertainers .= "<div class='swiper-slide-item'>
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
            $allEntertainers .= "</div>";
        }
        $writer->start('swiper_container');
        $writer->addHTML("<div class='swiper mySwiper'>
                            <div class='cards_head'>
                              <div class='cards_head-title'><b>Most Popular</b></div>
                              <div class='cards_head-btn'>
                                <button class='button-prev'> < </button>
                                <button class='button-next'> > </button>
                              </div>
                        
                            </div>
                            
                            <div class='swiper-wrapper'>
                              $allEntertainers
                            </div>
                            <div class='swiper-pagination'></div>
                          </div>");
        $writer->end('swiper_container');

        return $writer->getHTML();
    }
}