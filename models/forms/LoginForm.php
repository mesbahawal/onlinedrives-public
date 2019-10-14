<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\models\forms;

use Yii;
use yii\base\Model;

class LoginForm extends \yii\base\Model
{
    public $selected_cloud_login;
    public $app_id;
    public $password;
    public $upload_gd_client_secret_file;
    public $image_src_filename;
    public $image_web_filename;

    public function rules()
    {
        return [
            [['selected_cloud_login'], 'required'],
            [['app_id', 'password'], 'string'],
            [['upload_gd_client_secret_file'], 'file', 'extensions' => 'json'],
            [['image_src_filename', 'image_web_filename'], 'string', 'max' => 255],
            // ['selected_cloud_login', 'validateInput'],
            // ['app_id', 'validateInput'],
            // ['password', 'validateInput'],
        ];
    }

    public function validateInput()
    {
        $val_selected_cloud_login = $this->selected_cloud_login;
        $val_app_id = $this->app_id;
        $val_password = $this->password;

        if (empty($val_selected_cloud_login) ) {
            $this->addError('selected_cloud_login', 'Incorrect selected_cloud_login.');
        }

        if (empty($val_app_id)) {
            $this->addError('app_id', 'Incorrect app_id.');
        }

        if (empty($val_password) ) {
            $this->addError('password', 'Incorrect password.');
        }
    }
}
?>