<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\models\forms;

use Yii;
use yii\base\Model;

class CreateFileForm extends \yii\base\Model
{
    public $selected_cloud;
    public $create;
    public $new_folder_name;
    public $new_file_name;
    public $new_file_type;
    public $post_stream_cr_folder;
    public $post_stream_cr_file;

    public function rules()
    {
        return [
            [['selected_cloud'], 'required'],
            ['create', 'string'],
            ['new_folder_name', 'string'],
            ['post_stream_cr_folder', 'string'],
            ['post_stream_cr_file', 'string'],
            ['new_file_name', 'string'],
            ['new_file_type', 'string'],
        ];
    }
}
?>