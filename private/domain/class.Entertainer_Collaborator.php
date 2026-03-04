<?php

final class Entertainer_Collaborator extends StaysailEntity
{
    // Data model properties


    public $Member = parent::AssignOne;
    public $Entertainer = parent::AssignOne;

    public $stage_name = parent::Line;
    public $approved = parent::Int;

    public function getFileNames() {
        return [

            'photo_and_2nd_form_id_0',
            'photo_and_2nd_form_id_1',
            'headshot',
            'completed_2257',
        ];
    }

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
        $this->setLibraryPath('collaborators', DATAROOT . '/private/collaborators');
    }

    public function delete_Job() {$this->delete();}

    public function copy_Job() {return $this->copy();}

    public function getDocumentURL($type)
    {
        $url = "/collaborator.php?id={$this->id}&type={$type}";

        return $url;
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
    public function toBrowser($imageType, $web = false)
    {
        $path = DATAROOT . "/private/collaborators/{$this->Member_id}/{$imageType}";

        if ( file_exists($path)) {
            $content = file_get_contents($path);

            if ($web) {
                $size = getimagesize($path);
                if ($size[0] > 360) {
                    $this->toBrowserResized(360, $imageType);
                    return;
                }
            }

            header("Content-type:image/jpeg");
            print $content;
        } else {
            header("Status:404 Not Found");
        }
    }

    public function getFilePath($imageType) {
        return DATAROOT . "/private/collaborators/{$this->Member_id}/{$imageType}";
    }


    public function toBrowserResized($new_width, $imageType)
    {
        $path = $this->getFilePath($imageType);
        $size = GetImageSize($path);
        $new_height = (int)(($new_width/$size[0]) * $size[1]);
        $smaller = ImageCreateTrueColor($new_width, $new_height);

        switch ($size['mime'])
        {
            case 'image/jpeg':
                $src_img = ImageCreateFromJPEG($path);
                break;

            case 'image/png':
                $src_img = ImageCreateFromPNG($path);
                break;
        }
        ImageCopyResampled($smaller, $src_img, 0, 0, 0, 0, $new_width, $new_height, $size[0], $size[1] );
        header("Content-type:image/jpeg");
        ImageJPEG($smaller);
        ImageDestroy($smaller);
    }

}