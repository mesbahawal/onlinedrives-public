<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\models\rows;

use Yii;

class FolderRow extends FileSystemItemRow
{
    const DEFAULT_ORDER = 'title ASC';

    const ORDER_MAPPING = [
        self::ORDER_TYPE_NAME => 'title',
        self::ORDER_TYPE_UPDATED_AT => 'content.updated_at',
        self::ORDER_TYPE_SIZE => null,
    ];

    /**
     * @var \humhub\modules\onlinedrives\models\Folder
     */
    public $item;

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->item->getUrl();
    }

    /**
     * @inheritdoc
     */
    public function getBaseFile()
    {
        return null;
    }

    /**
     * @return boolean
     */
    public function canEdit()
    {
        return $this->item->content->canEdit();
    }
}
?>