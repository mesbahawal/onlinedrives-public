<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\widgets;

use humhub\modules\onlinedrives\models\File;
use humhub\modules\onlinedrives\models\Folder;
use humhub\modules\onlinedrives\models\rows\AbstractFileSystemItemRow;
use humhub\widgets\JsWidget;
use Yii;

/**
 * @inheritdoc
 */
class FileSystemItem extends JsWidget
{
    /**
     * @inheritdoc
     */
    public $jsWidget = 'onlinedrives.FileItem';

    /**
     * @var AbstractFileSystemItemRow
     */
    public $row;

    /**
     * @var boolean
     */
    public $itemsSelectable = true;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->row->showSelect = $this->itemsSelectable;

        return $this->render('fileSystemItem', [
            'row' => $this->row,
            'options' => $this->getOptions()
        ]);
    }

    public function getData() {
        return [
            'onlinedrives-item' => $this->row->getItemId(),
            'onlinedrives-type' => $this->row->getType(),
            'onlinedrives-url' => $this->row->getUrl(),
            'onlinedrives-editable' => $this->row->canEdit(),
            'onlinedrives-url-full' => $this->row->getDisplayUrl(),
            'onlinedrives-wall-url' => $this->row->getWallUrl(),
            'onlinedrives-edit-url' => ($this->row->canEdit()) ? $this->row->getEditUrl() : '',
            'onlinedrives-move-url' => ($this->row->canEdit()) ? $this->row->getMoveUrl() : '',
        ];
    }
}
