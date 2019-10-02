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

class AddFilesForm extends \yii\base\Model
{
    public $drive_path;
    public $permission;
    public $app_detail_id;

    public function rules()
    {
        return [
            [['drive_path'], 'required', 'message' => 'Please choose a drive path.'],
            ['permission', 'string'],
            ['app_detail_id', 'string'],
        ];
    }
}
?>