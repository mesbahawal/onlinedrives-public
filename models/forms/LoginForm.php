<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\onlinedrives\models\forms;

use Yii;
use yii\base\Model;

class LoginForm extends \yii\base\Model
{
    public $selected_cloud_login;
    public $app_id;
    public $password;

    public function rules()
    {
        return [
            [['selected_cloud_login', 'app_id', 'password'], 'required'],
        ];
    }
}
?>