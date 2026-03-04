<?php

class PostView
{
    private $Member;
    private $Entertainer;

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
        $html = '';
        if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
            $photosCount = sizeof($this->Entertainer->getPhotos());
            $videosCount = sizeof($this->Entertainer->getVideos());
            $quick_links = StaysailWriter::makeJobLink(Icon::show(Icon::STATUS) . ' Status', 'EntertainerProfile');
            $quick_links .= StaysailWriter::makeJobLink(Icon::show(Icon::PHOTOS) . 'Photos' . "<span class='_action_count'>{$photosCount}</span>", 'EntertainerGallery');
            $quick_links .= StaysailWriter::makeJobLink(Icon::show(Icon::VIDEOS) . ' Videos' . "<span class='_action_count'>{$videosCount}</span>", 'EntertainerGallery', 'videos');
//            $quick_links .= StaysailWriter::makeJobLink(Icon::show(Icon::EVENT) . ' Events', 'EntertainerProfile', 'events');
            $quick_links .= StaysailWriter::makeJobLink(Icon::show(Icon::EVENT) . ' Events', '', '', '', 'event_class');
            $events = new EventsView($this->Member);

            $html .= <<<__END__
				<div class="quick_links">{$quick_links}</div>
				<div class="main_comment">
<!--				<form id="main_comment" method="post" action="?mode=EntertainerProfile&job=post&id=0">-->
				<form id="main_comment" method="post" action="?mode=EntertainerProfile&job=post" enctype="multipart/form-data">
                    <textarea id="post_text" class="text_name" maxlength="200" onfocus="if (this.touched === undefined) {this.value='';this.touched=1;}" name="name" placeholder="What do you want to tell us?"></textarea>
                    <div id="counter"></div>
                    <div class="post_button dollar_post_button">
                        <input multiple class="img_add" type="file" accept="image/png, image/jpg, image/jpeg" id="post_file_photo" name="image[]" style="display: none">
                        <input class="video_add" type="file" accept="video/mp4,video/x-m4v,video/*" id="post_file_video" name="video" style="display: none">
                        <div class="">
                            <button type="button" id="btn_video">
                                <img src="https://img.icons8.com/external-anggara-glyph-anggara-putra/256/external-addvideo-social-media-interface-anggara-glyph-anggara-putra.png" alt="" width="19">
                            </button>
                            <button type="button" id="btn_photo">
                                <img src="https://img.icons8.com/ios-glyphs/256/add-image.png" alt="" width="19">
                            </button>
                        </div>

                         <ul class="dropdown_menu d_none" id="dropdown_video">
<!--                            <li>-->
<!--                                <button type="button" class="dropdown_btn" onclick="">-->
<!--                                    Take Video <img src="https://img.icons8.com/ios/256/camera&#45;&#45;v3.png" alt="" width="19">-->
<!--                                </button>-->
<!--                            </li>-->
                            <li>
                                <button type="button" class="dropdown_btn" onclick="postFileVideo()">
                                    Choose File <img src="https://img.icons8.com/ios/256/folder-invoices.png" alt="" width="19">
                                </button>
                            </li>
                        </ul>
                        <ul class="dropdown_menu d_none" id="dropdown_photo">
<!--                            <li>-->
<!--                                <button type="button" class="dropdown_btn" onclick="">-->
<!--                                    Take Photo <img src="https://img.icons8.com/ios/256/camera&#45;&#45;v3.png" alt="" width="19">-->
<!--                                </button>-->
<!--                            </li>-->
                            <li>
                                <button type="button" class="dropdown_btn" onclick="postFilePhoto()">
                                    Choose File <img src="https://img.icons8.com/ios/256/folder-invoices.png" alt="" width="19">
                                </button>
                            </li>
                        </ul>
                        <ul class="dropdown_menu d_none" id="dropdown_photo">
<!--                            <li>-->
<!--                                <button type="button" class="dropdown_btn" onclick="">-->
<!--                                    Take Photo <img src="https://img.icons8.com/ios/256/camera&#45;&#45;v3.png" alt="" width="19">-->
<!--                                </button>-->
<!--                            </li>-->
                            <li>
                                <button type="button" class="dropdown_btn" onclick="postFilePhoto()">
                                    Choose File <img src="https://img.icons8.com/ios/256/folder-invoices.png" alt="" width="19">
                                </button>
                            </li>
                        </ul>

                        <div class="" style="display: flex">
                            <div class="dollar_post_container">
                                <div class="dollar_input" style="display: none">
                                    <input id="dollar_input_val" type="number" name="prices" placeholder="Dollar">
                                </div>
                                <select name="placement" id="dollar_select">
                                  <option value="web">&#127760</option>
                                  <option value="subscribed">&#128101</option>
                                  <option value="sale">&#128178</option>
<!--                                  <option value="shop">&#128722</option>-->
                                </select>
                            </div>
                            <a id="post_submit">Post</a>
<!--                            <a id="post_submit" onclick="document.getElementById('main_comment').submit();">Post</a>-->
                        </div>

                    </div>
					<div id ="post_myImg_multi">
					</div>
					<video id="videoID"  style="width: 150px">
						<source src="" type="video/mp4" />
					</video>
				</form>

				<br class="clear" />
				</div>
__END__;
        }

        $html .= '<div id="posts-bloke" data-entertainer="' . $this->Entertainer->id . '">';
//        $offset = 0;
//        $limit = 10;
////        $posts = $this->Entertainer->getPosts();
//        $posts = $this->Entertainer->getPostsPagination($offset, $limit);
//
//        foreach ($posts as $Post) {
//            $html .= $Post->getHTML();
//        }
        $html .= '</div>';
        $html .= '<div id="posts-pagination"></div>';

        return $html;
    }

    public function getClubHTML($Club)
    {
        $html = '';
        $posts = $Club->getPosts();
        foreach ($posts as $Post) {
            $html .= $Post->getHTML();
        }
        return $html;
    }
}