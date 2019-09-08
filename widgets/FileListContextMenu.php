<?php

namespace humhub\modules\onlinedrives\widgets;

use humhub\modules\onlinedrives\permissions\ManageFiles;
use Yii;

/**
 * Widget for rendering the file list context menu.
 */
class FileListContextMenu extends \yii\base\Widget
{
    /**
     * Current folder model instance.
     * @var \humhub\modules\onlinedrives\models\Folder
     */
    public $folder;
    

    /**
     * @inheritdoc
     */
    public function run()
    {
        $canWrite = $this->folder->content->container->can(ManageFiles::class);

        return $this->render('fileListContextMenu', [
            'folder' => $this->folder,
            'canWrite' => $canWrite,
            'zipEnabled' => !Yii::$app->getModule('onlinedrives')->settings->get('disableZipSupport'),
        ]);
    }

}
