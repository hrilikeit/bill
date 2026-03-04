<?php

final class Library extends StaysailEntity
{
    // Data model properties
    public $name = parent::Line;
    public $sort = parent::Int;

    public $Member = parent::AssignOne;
    public $File_Type = parent::AssignOne;
    public $md5 = parent::Line;
    public $placement = parent::Enum;
    public $is_public = parent::Boolean;
    public $mime_type = parent::Line;
    public $size = parent::Int;
    public $video_time = parent::Int;
    public $price = parent::Int;
    public $description = parent::Text;
    public $gallery_id = parent::Text;
    public $keywords = parent::Line;
    public $image = parent::File;
    public $is_deleted = parent::Boolean;
    public $metadata = parent::Text;

    // Administrator Review
    public $admin_status = parent::Enum;
    public $status_note = parent::Line;
    public $status_time = parent::Time;
    public $Admin = parent::AssignOne;

    // Metadata properties
    //protected $_sort          = 'name ASC';
    //protected $_name_template = '{name}';

    // Library access codes, for diagnosing access issues:
    const ACCESS_IS_PUBLIC = 1;
    const ACCESS_IS_OWNER = 2;
    const ACCESS_IS_WEB = 3;
    const ACCESS_IS_SUBSCRIBER = 4;
    const ACCESS_IS_PURCHASED = 5;
    const ACCESS_IS_CLUB = 6;
    const ACCESS_SMALL_VERSION_ONLY = 7;

    const TRAILER_FOLDER_NAME = 'trailer';

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
        $this->setLibraryPath('image', DATAROOT . '/private/library');
        $this->setLibraryPath('video', DATAROOT . '/private/library');
    }

    public function admin_status_Options()
    {
        return array('pending' => 'pending', 'approved' => 'approved', 'denied' => 'denied',);
    }

    public function placement_Options()
    {
        return array('web' => 'web', 'sale' => 'sale', 'inactive' => 'inactive', 'subscribed' => 'subscribed');
    }

    public function delete_Job()
    {
        parent::delete();
    }

    public function copy_Job()
    {
        return $this->copy();
    }

    /**
     * Returns true if this Library belongs to the specified Member.  Make sure to check this
     * before you do any destructive editing of a Library.
     *
     * @param Member $Member
     */
    public function belongsTo(Member $Member)
    {
        if (!$this->Member) {
            return false;
        }
        return ($Member->id == $this->Member->id);
    }

    /**
     * Return image HTML with the thumbnail class
     *
     * @return string
     */
    public function getThumbnailHTML()
    {
        $url = $this->getThumbnailURL();
        $placement = "placement_{$this->placement}";
        $img = StaysailWriter::makeImage($url, $this->name, "thumbnail {$placement}");

        return $img;
    }

    /**
     * Return image HTML with the web class
     *
     * @return string
     */
    public function getWebHTML()
    {
        $url = "/img.php?id={$this->id}&w=1";
        $img = StaysailWriter::makeImage($url, $this->name, 'web');

        return $img;
    }

    public function getFullSizeURL()
    {
        $url = "/img.php?id={$this->id}";

        return $url;
    }

    public function getThumbnailURL()
    {
        $url = "/img.php?id={$this->id}&w=1";

        return $url;
    }

    /**
     * Return video HTML with the web class
     *
     * @return string
     */

    public function getWebVideoHTML($trailer = 0)
    {
        $url = "/video.php?id={$this->id}&w=1&trailer={$trailer}";

        return $url;
    }

    /**
     * Returns the access status of the specified Member for this Library item.
     *
     * Note that the passed parameter variable may be null.  In this case, hasAccess() determines
     * whether the Libray item is accessible without restriction.  If the parameter variable is an
     * instance of Member, hasAccess() uses several rules to determine the access of the provided Member.
     *
     * If the admin_status is 'denied', then the image will not be served at all.  Otherwise, access is allowed if:
     *     - An Admin session exists
     *     - The Library is specified as is_public
     *     - The specified Member matches the file owner (Library.Member)
     *     - The specified Fan Member has subscribed to the file owner AND the placement is specified as 'web'
     *     - The specified Fan Member has purchased the image
     *     - The specified Club_Admin Member is associated with the same club as the file
     *
     * @param Member | null
     * @return boolean
     */
    public function hasAccess($Member)
    {
        if (StaysailIO::session('Admin.id')) {
            return true;
        }
        if ($this->admin_status == 'denied') {
            return false;
        }

        // The Library is specified as is_public
        if ($this->is_public) {
            return self::ACCESS_IS_PUBLIC;
        }

        // The rest of the validations require a Member


        if ($Member && $this->Member) {
            // The specified Member matches the file owner
            if ($Member->id == $this->Member->id) {
                return self::ACCESS_IS_OWNER;
            }

            // The specified Fan Member has subscribed to the file owner AND placement is specified as 'web'
            $Fan = $Member->getAccountOfType('Fan');
            $Entertainer = $this->Member->getAccountOfType('Entertainer');
            if ($Fan and $Entertainer) {
                if ($Fan->isSubscribedTo($Entertainer) and $this->placement == 'web') {
                    return self::ACCESS_IS_SUBSCRIBER;
                }

                // The specified Fan Member has purchased the image
                if ($Fan->hasPurchased($this)) {
                    return self::ACCESS_IS_PURCHASED;
                }

                return self::ACCESS_SMALL_VERSION_ONLY;
            }

            // The specified Club_Admin Member is associated with the same club as the file
            $Club_Admin = $Member->getAccountOfType('Club_Admin');
            if ($Club_Admin) {
                $member_club_id = $Club_Admin->Club->id;
                $owner_club_id = $this->Member->Club->id;
                if ($member_club_id == $owner_club_id) {
                    return self::ACCESS_IS_CLUB;
                }
            }
        }

        // If no access conditions are true, then access is prohibited.
        return false;
    }

    /**
     * Write the image data, with the appropriate header.  Since you want to make sure no
     * extra output is generated, the caller will usually want to exit immediately:
     *
     * $Library->toBrowser();
     * exit;
     *
     * @param boolean $web
     */
    public function toBrowser($web = false)
    {
        $path = DATAROOT . "/private/library/{$this->image}";

        if ($this->image and file_exists($path)) {
            $content = file_get_contents($path);

            if ($web) {
                $size = getimagesize($path);
                if ($size[0] > 360) {
                    $this->toBrowserResized(360);
                    return;
                }
            }

            header("Content-type:{$this->mime_type}");
            print $content;
        } else {
            header("Status:404 Not Found");
        }
    }

    public function toBrowserVideo($web = false, $trailer = false)
    {
        $videoPath = $trailer ? self::TRAILER_FOLDER_NAME . '/' : '';
        $path = DATAROOT . "/private/library/{$videoPath}{$this->image}";
        if ($this->image && file_exists($path)) {
            //$content = file_get_contents($path);
            //header("Content-type:{$this->mime_type}");
            include "VideoStream.php";
            $stream = new VideoStream($path);
            $stream->start();

            $file = $path;
            $fp = @fopen($file, 'rb');

            $size = filesize($file); // File size
            $length = $size;           // Content length
            $start = 0;               // Start byte
            $end = $size - 1;       // End byte

            header('Content-type: video/mp4');
            header("Accept-Ranges: 0-$length");
            if (isset($_SERVER['HTTP_RANGE'])) {

                $c_start = $start;
                $c_end = $end;

                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if (strpos($range, ',') !== false) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                if ($range == '-') {
                    $c_start = $size - substr($range, 1);
                } else {
                    $range = explode('-', $range);
                    $c_start = $range[0];
                    $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
                }
                $c_end = ($c_end > $end) ? $end : $c_end;
                if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                $start = $c_start;
                $end = $c_end;
                $length = $end - $start + 1;
                fseek($fp, $start);
                header('HTTP/1.1 206 Partial Content');
            }
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: " . $length);


            $buffer = 1024 * 8;
            while (!feof($fp) && ($p = ftell($fp)) <= $end) {

                if ($p + $buffer > $end) {
                    $buffer = $end - $p + 1;
                }
                set_time_limit(0);
                echo fread($fp, $buffer);
                flush();
            }

            fclose($fp);
            //print $content;
        } else {
            header("Status:404 Not Found");
        }
    }

    public function setAsAvatarUsing($x, $y, $hw)
    {
        if (!$this->Member or !$this->Member->id) {
            return false;
        }
        if (!$x or !$y or !$hw) {
            return false;
        }

        $member_id = $this->Member->id;

        $path = DATAROOT . "/private/library/{$this->image}";
        $size = GetImageSize($path);

        $factor = $size[0] / 360; // Crop factor of image
        $x *= $factor;
        $y *= $factor;
        $hw *= $factor;

        switch ($size['mime']) {
            case 'image/jpeg':
                $src_img = ImageCreateFromJPEG($path);
                break;

            case 'image/png':
                $src_img = ImageCreateFromPNG($path);
                break;
        }

        $cropped = ImageCreateTrueColor($hw, $hw);
        ImageCopyResampled($cropped, $src_img, 0, 0, $x, $y, $hw, $hw, $hw, $hw);


//		$save_path = DATAROOT . "/private/avatars/entertainerAvatar{$member_id}.jpg";
        $save_path = DATAROOT . "/private/avatars/entertainerAvatar{$member_id}.png";
        ImageJPEG($cropped, $save_path);
    }

    public function toBrowserResized($new_width)
    {
        $path = DATAROOT . "/private/library/{$this->image}";
        $size = GetImageSize($path);
        $new_height = (int)(($new_width / $size[0]) * $size[1]);
        $smaller = ImageCreateTrueColor($new_width, $new_height);

        switch ($size['mime']) {
            case 'image/jpeg':
                $src_img = ImageCreateFromJPEG($path);
                break;

            case 'image/png':
                $src_img = ImageCreateFromPNG($path);
                break;
        }
        ImageCopyResampled($smaller, $src_img, 0, 0, 0, 0, $new_width, $new_height, $size[0], $size[1]);

        if ($this->placement == 'sale' and $this->Member->id != StaysailIO::session('Member.id')) {
            // Add watermark if it's an image for sale
            $stamp = ImageCreateTrueColor(180, 70);
            ImageFilledRectangle($stamp, 0, 0, 180, 70, 0xFFFFFF);
            ImageString($stamp, 5, 10, 30, 'YourFansLive.com', 0x000000);

            // Set the margins for the stamp and get the height/width of the stamp image
            $marge_right = 10;
            $marge_bottom = 10;
            $sx = imagesx($stamp);
            $sy = imagesy($stamp);
            // Merge the stamp onto our photo with an opacity (transparency) of 50%
            ImageCopyMerge($smaller, $stamp, imagesx($smaller) - $sx - $marge_right, imagesy($smaller) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp), 50);
        }

        header("Content-type:image/jpeg");
        ImageJPEG($smaller);
        ImageDestroy($smaller);
    }

    /**
     * Get the size like this:
     *
     * list ($width, $height) = $Library->getNativeSize();
     *
     * @return array
     */
    public function getNativeSize()
    {
        $path = DATAROOT . "/private/library/{$this->image}";
        $size = GetImageSize($path);
        return $size;
    }

    public function rotate($angle)
    {
        $path = DATAROOT . "/private/library/{$this->image}";
        $size = GetImageSize($path);

        switch ($size['mime']) {
            case 'image/jpeg':
                $src_img = ImageCreateFromJPEG($path);
                break;

            case 'image/png':
                $src_img = ImageCreateFromPNG($path);
                break;
        }
        $rotated = ImageRotate($src_img, $angle, 0);
        ImageJPEG($rotated, $path);
        ImageDestroy($rotated);
    }

    public function setInactive()
    {
        $this->placement = 'inactive';
        $this->save();
    }

    public function getGalleryEntry(Member $Member)
    {
        $writer = new StaysailWriter('gallery_entry');
        $access = $this->hasAccess($Member);
        if ($access == self::ACCESS_IS_PURCHASED or $access == self::ACCESS_IS_SUBSCRIBER) {
            $link = StaysailWriter::makeLink('Download', $this->getFullSizeURL());
        } else {
            $buy_url = "?mode=FanModule&focus=FanCheckout&type=Library&id={$this->id}";
            $link = StaysailWriter::makeLink('Buy This Image', $buy_url);
        }
        $writer->addHTML($this->getWebHTML());
        $writer->p($link);
        return $writer->getHTML();
    }

    public static function getMetadataTypes()
    {
        return array('full_name', 'username', 'stage_name', 'DOB');
    }

    public function setMetadata(array $person_data)
    {
        $data = Library::getMetadataTypes();

        $xml = '';
        for ($i = 0; $i < sizeof($person_data); $i++) {
            $person = $person_data[$i];
            $xml .= '<person>';
            foreach ($data as $type) {
                if (isset($person[$type])) {
                    $xml .= "<{$type}>{$person[$type]}</{$type}>";
                }
            }
            $xml .= '</person>';
        }

        $this->metadata = $xml;
        $this->save();
    }

    public function getMetadata(): array
    {
        $data = Library::getMetadataTypes();
        $metadata = [];

        if (!is_string($this->metadata)) {
            $this->metadata = '';
        }

        $people = explode('</person>', $this->metadata);

        foreach ($people as $personXml) {
            $person = [];

            foreach ($data as $type) {
                if (preg_match("/<{$type}>(.+)<\/{$type}>/", $personXml, $matches)) {
                    $person[$type] = $matches[1];
                }
            }

            $metadata[] = $person;
        }

        return $metadata;
    }

    public function getMetadataHTML()
    {
        $html = '';
        $metadata = $this->getMetadata();

        foreach ($metadata as $person) {
            $html .= '<p>';
            foreach ($person as $key => $value) {
                $key = ucwords(str_replace('_', ' ', $key));
                $html .= "<strong>{$key}</strong> : {$value}<br/>";
            }
            $html .= '</p>';
        }
        return $html;
    }

    public function getRotateControls($mode)
    {
        $html = '';
        $html .= "<a href=\"?mode={$mode}&job=rotate&dir=90&id={$this->id}\"><img src=\"/site_img/icons/rotate-left.png\" border=\"0\" alt=\"Left\"></a> ";
        $html .= "<a href=\"?mode={$mode}&job=rotate&dir=180&id={$this->id}\"><img src=\"/site_img/icons/rotate-flip.png\" border=\"0\" alt=\"Flip\"></a> ";
        $html .= "<a href=\"?mode={$mode}&job=rotate&dir=270&id={$this->id}\"><img src=\"/site_img/icons/rotate-right.png\" border=\"0\" alt=\"Right\"></a>";
        return $html;
    }

    public function getLibraryPost()
    {
        return $this->id ? $this->_framework->getRowByField('Post', 'Library_id', $this->id) : null;
    }

    public function getGalleryPhotos($limit = 2)
    {
        return $this->gallery_id ? $this->_framework->getAllIdsRowsByField('Library', 'gallery_id', $this->gallery_id, $limit) : null;
    }


    public function makeSliderWithLinks()
    {
        $content = '<div class="splide">
                        <div class="splide__track">
                                <ul class="splide__list">';
        foreach ($this->getGalleryPhotos(100) as $sliderPhoto) {
            $Library = new Library($sliderPhoto[0]);
            $content .= '<li class="splide__slide">' . StaysailWriter::makeLink($Library->getWebHTML('mySlides'), $Library->getFullSizeURL(), '', null, '_blank') . '</li>';
        }
        $content .= '</ul></div></div>';
        return $content;
    }
}
