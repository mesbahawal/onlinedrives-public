<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\onlinedrives\widgets;

use humhub\components\Widget;
use humhub\modules\onlinedrives\models\Folder;
use humhub\modules\onlinedrives\permissions\ManageFiles;
use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;

class FileSelectionMenu extends Widget
{
    /**
     * @var Folder
     */
    public $folder;

    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    public function run()
    {
        $deleteSelectionUrl = $this->folder->createUrl('/onlinedrives/delete');
        $moveSelectionUrl = $this->folder->createUrl('/onlinedrives/move', ['init' => 1]);

        $zipSelectionUrl = $this->folder->createUrl('/onlinedrives/zip/download');
        $makePrivateUrl = $this->folder->createUrl('/onlinedrives/edit/make-private');
        $makePublicUrl = $this->folder->createUrl('/onlinedrives/edit/make-public');

        $canWrite = $this->contentContainer->can(ManageFiles::class);

        return $this->render('fileSelectionMenu', [
            'deleteSelectionUrl' => $deleteSelectionUrl,
            'folder' => $this->folder,
            'moveSelectionUrl' => $moveSelectionUrl,
            'zipSelectionUrl' => $zipSelectionUrl,
            'canWrite' => $canWrite,
            'zipEnabled' =>  Yii::$app->getModule('onlinedrives')->isZipSupportEnabled(),
            'makePrivateUrl' => $makePrivateUrl,
            'makePublicUrl' => $makePublicUrl,
        ]);
    }
}
