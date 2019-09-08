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

class DeleteFileForm extends \yii\base\Model
{
    public $cloud;
    public $delete_file_id;

    public function rules()
    {
        return [
            [['cloud'], 'required'],
            [['delete_file_id'], 'required']
        ];
    }
}
