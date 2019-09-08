<?php

namespace humhub\modules\onlinedrives\widgets;

use humhub\modules\onlinedrives\models\ZipImportHandler;
use humhub\modules\onlinedrives\permissions\ManageFiles;
use humhub\modules\onlinedrives\permissions\WriteAccess;
use Yii;
use humhub\modules\file\handler\FileHandlerCollection;

/**
 * Widget for rendering the file list menu.
 */
class FileListMenu extends \yii\base\Widget
{
    /**
     * Current folder model instance.
     * @var \humhub\modules\onlinedrives\models\Folder
     */
    public $folder;

    /**
     * @var \humhub\modules\content\components\ContentContainerActiveRecord Current content container.
     */
    public $contentContainer;

    /**
     * @var integer FileList item count.
     */
    public $itemCount;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $fileHandlerImport = FileHandlerCollection::getByType(FileHandlerCollection::TYPE_IMPORT);
        array_unshift($fileHandlerImport, new ZipImportHandler());

        $fileHandlerCreate = FileHandlerCollection::getByType(FileHandlerCollection::TYPE_CREATE);
        $canUpload = $this->contentContainer->can(WriteAccess::class);

        return $this->render('fileListMenu', [
                    'folder' => $this->folder,
                    'contentContainer' => $this->contentContainer,
                    'canUpload' => $canUpload,
                    'fileHandlers' => array_merge($fileHandlerCreate, $fileHandlerImport),
        ]);
    }
}
