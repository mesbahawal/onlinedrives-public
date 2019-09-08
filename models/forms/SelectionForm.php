<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\onlinedrives\models\forms;

use Yii;

class SelectionForm extends \yii\base\Model
{
    /**
     * @var string[] filesystem ids of the selection
     */
    public $selection = [];

    /**
     * @var \humhub\modules\content\components\ContentContainerActiveRecord
     */
    public $contentContainer;

    public function init()
    {
        if(Yii::$app->request->post()) {
            $this->selection = Yii::$app->request->post('selection');
        }
    }

}
