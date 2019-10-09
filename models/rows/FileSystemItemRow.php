<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\models\rows;

abstract class FileSystemItemRow extends AbstractFileSystemItemRow
{
    /**
     * @var \humhub\modules\onlinedrives\models\FileSystemItem
     */
    public $item;

    /**
     * @inheritdoc
     */
    public function isSelectable()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isSocialActionsAvailable()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getColumns()
    {
        return self::DEFAULT_COLUMNS;
    }

    /**
     * @inheritdoc
     */
    public function getParentFolderId()
    {
        return $this->item->parent_folder_id;
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->item->getItemType();
    }

    /**
     * @inheritdoc
     */
    public function getItemId()
    {
        return $this->item->getItemId();
    }

    /**
     * @inheritdoc
     */
    public function getLinkUrl()
    {
        return $this->item->getUrl();
    }

    /**
     * @inheritdoc
     */
    public function getEditUrl()
    {
        return $this->item->getEditUrl();
    }

    /**
     * @inheritdoc
     */
    public function getModel()
    {
        return $this->item;
    }

    /**
     * @inheritdoc
     */
    public function getDisplayUrl()
    {
        return $this->item->getFullUrl();
    }

    /**
     * @inheritdoc
     */
    public function getWallUrl()
    {
        return $this->item->content->container->createUrl(null, ['contentId' => $this->item->content->id]);
    }

    /**
     * @inheritdoc
     */
    public function getMoveUrl()
    {
        return $this->item->content->container->createUrl('/onlinedrives/move', ['fid' => $this->getParentFolderId()]);
    }

    /**
     * @inheritdoc
     */
    public function getIconClass()
    {
        return $this->item->getIcon();
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->item->getTitle();
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return $this->item->getSize();
    }

    /**
     * @inheritdoc
     */
    public function getCreator()
    {
        return $this->item->getCreator();
    }

    /**
     * @inheritdoc
     */
    public function getEditor()
    {
        return $this->item->getEditor();
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->item->content->updated_at;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->item->getDescription();
    }

    /**
     * @inheritdoc
     */
    public function getVisibilityIcon()
    {
        return $this->item->content->isPublic() ? 'fa-unlock-alt': 'fa-lock';
    }

    /**
     * @return string
     */
    public function getVisibilityTitle()
    {
        return $this->item->getVisibilityTitle();
    }
}
?>