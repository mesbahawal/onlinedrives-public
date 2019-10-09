<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\models\forms;

use Yii;
use yii\base\Model;
use Sabre\DAV\Client;

include __DIR__ . '/../../vendor/autoload.php';

class UploadFileForm extends \yii\base\Model
{
    public $selected_cloud_u;
    public $upload;
    public $image_src_filename;
    public $image_web_filename;
    public $sciebo_path;
 
    public function rules()
    {
        return [
            [['selected_cloud_u'], 'required'],
            [['upload'], 'file', 'extensions' => 'txt, docx, doc, odt, rtf, xslx, xsl, ods, pptx, ppt, pps, odp, pdf, jpg, jpeg, gif, png, tif, tiff, wav, mp3, mp4, mx12, mx18, zip'],
			[['image_src_filename', 'image_web_filename'], 'string', 'max' => 255],
            ['sciebo_path', 'string'],
        ];
    }

    public function getScieboClient($userid,$password) {

        $settings = array(
            'baseUri' => 'https://uni-siegen.sciebo.de/remote.php/dav/',
            'userName' => $userid,
            'password' => $password,
        );
        $client = new \Sabre\DAV\Client($settings);

        return $client;
    }
}
?>