<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
namespace humhub\modules\onlinedrives\models\forms;

use humhub\modules\onlinedrives\models\FileSystemItem;
use humhub\modules\onlinedrives\models\Folder;
use humhub\modules\onlinedrives\models\File;
use Yii;

class MoveForm extends SelectionForm
{
    /**
     * @var Folder root folder of this contentcontainer
     */
    public $root;

    /**
     * @var Folder the source folder of the selection
     */
    public $sourceFolder;

    /**
     * @var Folder the id of destination folder id
     */
    public $destId;

    /**
     * @var Folder the destination of the move event
     */
    public $destination;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->contentContainer = $this->root->content->container;
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['destId', 'required'],
            ['destId', 'integer'],
            ['destId', 'validateDestination']
        ];
    }

    /**
     * @param $model MoveForm
     * @param $attribute
     */
    public function validateDestination($attribute)
    {
        $this->destination = Folder::findOne($this->destId);

        if (!$this->destination) {
            $this->addError($attribute, Yii::t('OnlinedrivesModule.base', 'Destination folder not found!'));
            return;
        }

        if ($this->sourceFolder->id == $this->destination->id) {
            $this->addError($attribute, Yii::t('OnlinedrivesModule.base', 'Moving to the same folder is not valid.'));
            return;
        }

        if ($this->destination->isAllPostedFiles() || $this->destination->content->container->id !== $this->contentContainer->id) {
            $this->addError($attribute, Yii::t('OnlinedrivesModule.base', 'Moving to this folder is invalid.'));
            return;
        }
    }

    /**
     * @return string move action url
     */
    public function getMoveUrl()
    {
        return $this->sourceFolder->createUrl('/onlinedrives/move');
    }

    /**
     * Executes the actual move of the selection files from source into target.
     * @return bool
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $result = true;

        foreach ($this->selection as $selectedItemId) {
            $item = FileSystemItem::getItemById($selectedItemId);

            if (!$this->destination->moveItem($item)) {
                $this->addItemErrors($item);
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @param FileSystemItem $item
     */
    public function addItemErrors(FileSystemItem $item)
    {
        foreach ($item->errors as $key => $error) {
            $this->addErrors([$key => $error]);
        }
    }


}
