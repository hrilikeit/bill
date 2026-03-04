<?php

final class Member_Docs extends StaysailEntity
{
    // Data model properties 


    public $Member = parent::AssignOne;
    public $Entertainer = parent::AssignOne;

    public $doc_type = parent::Int;
    public $doc_first_page_file = parent::Line;
    public $doc_second_page_file = parent::Line;
    public $doc_and_face_file = parent::Line;
    public $release_form_ref_name = parent::Line;
    public $signed_form_file_1 = parent::Line;
    public $signed_form_file_2 = parent::Line;
    public $signed_form_file_3 = parent::Line;
    public $signed_form_file_1_approved = parent::Boolean;
    public $signed_form_file_2_approved = parent::Boolean;
    public $signed_form_file_3_approved = parent::Boolean;
    public $doc_first_page_file_approved = parent::Boolean;
    public $doc_second_page_file_approved = parent::Boolean;
    public $doc_and_face_file_approved = parent::Boolean;

    public function getFileNames() {
        return [
            /*'signed_form_file_1' => 'SIGNED RELEASE FORM PAGE 1',
            'signed_form_file_2' => 'SIGNED RELEASE FORM PAGE 2',
            'signed_form_file_3' => 'SIGNED RELEASE FORM PAGE 3',*/
            'doc_first_page_file' => 'DOCUMENT',
            'doc_second_page_file' => 'DOCUMENT 2ND PAGE',
            'doc_and_face_file' => 'PICTURE OF A PERSON HOLDING THE ID',
        ];
    }

    public function __construct($id = null)
    {
        parent::__construct(__CLASS__, $id);
        $this->setLibraryPath('documents', DATAROOT . '/private/documents');
    }

    public function delete_Job() {$this->delete();}

    public function copy_Job() {return $this->copy();}

    public function getDocumentURL($type)
    {
        $url = "/document.php?id={$this->id}&type={$type}";

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
        $path = DATAROOT . "/private/documents/{$this->Member_id}/{$this->id}{$imageType}.jpg";
       
        if ($this->$imageType and file_exists($path)) {
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

    public function toBrowserAdmin($imageType, $web = false)
    {
        $path = DATAROOT . "/private/documents/{$this->Member_id}/{$this->id}{$imageType}.jpg";
        if ($this->$imageType and file_exists($path)) {
            $content = file_get_contents($path);
            if ($web) {
                $size = getimagesize($path);
                    $this->toBrowserResized($size[0], $imageType);
                return;
            }
            header("Content-type:image/jpeg");
            print $content;
        } else {
            header("Status:404 Not Found");
        }
    }

    public function toBrowserResized($new_width, $imageType)
    {
        $path = DATAROOT . "/private/documents/{$this->Member_id}/{$this->id}{$imageType}.jpg";
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

    public function allIdDocsApproved() {
       return $this->doc_first_page_file_approved &&
           $this->doc_second_page_file_approved
        && $this->doc_and_face_file_approved
//        && $this->signed_form_file_1_approved
//        && $this->signed_form_file_2_approved
//        && $this->signed_form_file_3_approved
           ;
    }

    public static function hasMemberDocs($framework, $memberId) {
        $memberDocs = $framework->getRowByField('Member_Docs', 'Member_id', $memberId);
        return isset($memberDocs['id']);
    }
}