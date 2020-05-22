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

    public function getScieboFiles($client, $app_user_id, $drive_path) {
        $folder_content = false;
        try {
            $folder_content = $client->propFind('https://uni-siegen.sciebo.de/remote.php/dav/files/'.$app_user_id.'/'.$drive_path, array(
                '{http://owncloud.org/ns}fileid', // ID
                '{DAV:}getetag', //TODO doesn't work
                '{DAV:}creationdate', //TODO doesn't work
                '{DAV:}getlastmodified',
                '{DAV:}getcontenttype',
                '{DAV:}getcontentlength',
                '{DAV:}getcontentname', //TODO doesn't work
                '{http://owncloud.org/ns}favorite',
                '{http://owncloud.org/ns}share-types',
                '{http://owncloud.org/ns}owner-display-name',
                '{http://owncloud.org/ns}comments-count',
            ), 1);

            return $folder_content;
        }
        catch ( Sabre\HTTP\ClientHttpException $e) {
            Yii::warning($e);
        }
    }
}
?>