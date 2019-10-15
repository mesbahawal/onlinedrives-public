<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\models\forms;

use Yii;
use yii\base\Model;

class LoginFormGDClient extends \yii\base\Model
{
    public $gd_app_id;
    public $upload_gd_client_secret_file;
    public $image_src_filename;
    public $image_web_filename;

    public function rules()
    {
        return [
            [['gd_app_id'], 'string'],
            [['upload_gd_client_secret_file'], 'file', 'extensions' => 'json'],
            [['image_src_filename', 'image_web_filename'], 'string', 'max' => 255],
        ];
    }
}
?>