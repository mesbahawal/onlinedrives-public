<?php

namespace humhub\modules\onlinedrives\widgets;

use humhub\modules\onlinedrives\models\Folder;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\widgets\JsWidget;

/**
 * Widget for rendering the file list bar.
 */
class FolderView extends JsWidget
{
    /**
     * @inheritdoc
     */
    public $jsWidget = 'onlinedrives.FolderView';

    /**
     * @inheritdoc
     */
    public $id = 'onlinedrives-folderView';

    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var Folder
     */
    public $folder;

    /**
     * @inheritdoc
     */
    public $init = true;

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return [
            'fid' => $this->folder->id,
            'upload-url' => $this->folder->createUrl('/onlinedrives/upload'),
            'reload-file-list-url' => $this->folder->createUrl('/onlinedrives/browse/file-list'),
            'delete-url' => $this->folder->createUrl('/onlinedrives/delete'),
            'zip-upload-url' => $this->folder->createUrl('/onlinedrives/zip/upload'),
            'download-archive-url' => $this->folder->createUrl('/onlinedrives/zip/download'),
            'move-url' => $this->folder->createUrl('/onlinedrives/move'),
            'import-url' => $this->folder->createUrl('/onlinedrives/upload/import'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function run() {        
        return $this->render('folderView', [
            'folder' => $this->folder,
            'options' => $this->getOptions(),
            'contentContainer' => $this->contentContainer
        ]);
    }
}
