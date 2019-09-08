<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\widgets;

use humhub\modules\file\converter\PreviewImage;
use humhub\modules\onlinedrives\models\File;
use humhub\libs\MimeHelper;

/**
 * @inheritdoc
 */
class WallEntryFile extends \humhub\modules\content\widgets\WallEntry
{
    /**
     * @inheritdoc
     */
    public $editRoute = "/onlinedrives/edit/file";

    /**
     * @inheritdoc
     */
    public $editMode = self::EDIT_MODE_MODAL;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $onlineDrive = $this->contentObject;

        $folderUrl = '#';
        if ($onlineDrive->parentFolder !== null) {
            $folderUrl = $onlineDrive->parentFolder->getUrl();
        }

        return $this->render('wallEntryFile', [
            'onlineDrive' => $onlineDrive,
            'fileSize' => $onlineDrive->getSize(),
            'file' => $onlineDrive->baseFile,
            'previewImage' => new PreviewImage(),
            'folderUrl' => $folderUrl,
        ]);
    }

    /**
     * Returns the edit url to edit the content (if supported)
     *
     * @return string url
     */
    public function getEditUrl()
    {
        if (empty(parent::getEditUrl())) {
            return "";
        }

        if ($this->contentObject instanceof File) {
            return $this->contentObject->content->container->createUrl($this->editRoute, ['id' => $this->contentObject->getItemId(), 'fromWall' => true]);
    }

        return "";
    }

}
