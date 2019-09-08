<?php

use humhub\modules\onlinedrives\widgets\FileSelectionMenu;
use humhub\modules\file\widgets\FileHandlerButtonDropdown;
use humhub\modules\file\widgets\UploadButton;
use humhub\modules\file\widgets\UploadInput;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;

/* @var $folder \humhub\modules\onlinedrives\models\Folder */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
/* @var $canUpload boolean */
/* @var $zipEnabled boolean */
/* @var $fileHandlers humhub\modules\file\handler\BaseFileHandler[] */

$zipAllUrl = $contentContainer->createUrl('/onlinedrives/zip/download', ['fid' => $folder->id]);
$zipUploadUrl = $contentContainer->createUrl('/onlinedrives/zip/upload', ['fid' => $folder->id]);

$addFolderUrl = $contentContainer->createUrl('/onlinedrives/edit/folder', ['fid' => $folder->id]);
$editFolderUrl = $contentContainer->createUrl('/onlinedrives/edit/folder', ['id' => $folder->getItemId()]);

$uploadUrl = $contentContainer->createUrl('/onlinedrives/upload', ['fid' => $folder->id]);
?>

<div class="clearfix files-action-menu">
    <?= FileSelectionMenu::widget([
        'folder' => $folder,
        'contentContainer' => $contentContainer,
    ]);?>

    <?php if ($folder->parentFolder) : ?>
        <?= Button::back($folder->parentFolder->getUrl())->left()->setText('');  ?>
    <?php endif; ?>

    <!-- FileList main menu -->
    <?php if (!$folder->isAllPostedFiles()): ?>
        <div style="display:block;" class="pull-right">

            <!-- Directory dropdown -->
            <?php if ($canUpload): ?>
                <div class="btn-group">

                    <?php if (!$folder->isRoot()): ?>
                        <button id="directory-toggle" type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <span class="caret"></span><span class="sr-only"></span>
                        </button>
                        <ul id="folder-dropdown" class="dropdown-menu">
                            <li class="visible">
                               <?= ModalButton::asLink(Yii::t('OnlinedrivesModule.base', 'Edit directory'))->load($editFolderUrl)->icon('fa-pencil'); ?>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>    

            <!-- Upload Dropdown -->
            <?php if ($canUpload): ?>
                <?php  $uploadButton = UploadButton::widget([
                            'id' => 'onlinedrivesUploadFiles',
                            'progress' => '#onlinedrives_progress',
                            'url' => $uploadUrl,
                            'preview' => '#onlinedrives-folderView',
                            'tooltip' => false,
                            'cssButtonClass' => 'btn-success',
                            'label' => Yii::t('OnlinedrivesModule.base', 'Add file(s)'),
                            'dropZone' => '#onlinedrives-container'
                 ])  ?>
                <?= FileHandlerButtonDropdown::widget(['primaryButton' => $uploadButton, 'handlers' => $fileHandlers, 'cssButtonClass' => 'btn-success', 'pullRight' => true]); ?>

                <?= UploadInput::widget([
                    'id' => 'onlinedrivesUploadZipFile',
                    'progress' => '#onlinedrives_progress',
                    'url' => $zipUploadUrl,
                    'preview' => '#onlinedrives-folderView'
                ])  ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
