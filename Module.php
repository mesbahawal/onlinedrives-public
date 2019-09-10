<?php

namespace humhub\modules\onlinedrives;

use humhub\modules\onlinedrives\models\rows\FileSystemItemRow;
use humhub\modules\onlinedrives\models\rows\SpecialFolderRow;
use Yii;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\onlinedrives\models\Folder;
use humhub\modules\onlinedrives\models\File;
use yii\helpers\Url;

class Module extends ContentContainerModule
{

    public $resourcesPath = 'resources';

    /**
     * @var string sort name as 'name', 'size', 'updated_at'
     * @see FileSystemItemRow::ORDER_MAPPING
     */
    public $defaultSort = FileSystemItemRow::ORDER_TYPE_NAME;
    public $defaultOrder = 'ASC';

    /**
     * @var string sort name as 'name', 'size', 'updated_at'
     * @see FileSystemItemRow::ORDER_MAPPING
     */
    public $defaultPostedFilesSort = FileSystemItemRow::ORDER_TYPE_UPDATED_AT;
    public $defaultPostedFilesOrder = 'ASC';

    /**
     * @var string default order of file entries e.g.:  'file.updated_at ASC' or 'title ASC'
     */
    public $defaultFolderOrder = 'title ASC';

    const ORDER_NAME = 'name';
    const ORDER_UPDATED_AT = 'updated_at';
    const ORDER_SIZE = 'size';

    const UPLOAD_BEHAVIOUR_INDEX = 0;
    const UPLOAD_BEHAVIOUR_REPLACE = 1;

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            Space::className(),
            User::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPermissions($contentContainer = null)
    {
        if ($contentContainer instanceof Space) {
            return [
                new permissions\WriteAccess(),
                new permissions\ManageFiles(),
            ];
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function disable()
    {
        foreach (Folder::find()->all() as $key => $folder) {
            $folder->delete();
        }
        foreach (File::find()->all() as $key => $file) {
            $file->delete();
        }
        parent::disable();
    }

    /**
     * @inheritdoc
     */
    public function disableContentContainer(ContentContainerActiveRecord $container)
    {

        foreach (Folder::find()->contentContainer($container)->all() as $folder) {
            $folder->delete();
        }

        foreach (File::find()->contentContainer($container)->all() as $file) {
            $file->delete();
        }
        parent::disableContentContainer($container);
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerName(ContentContainerActiveRecord $container)
    {
        return Yii::t('OnlinedrivesModule.base', 'OnlineDrives');
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        if ($container instanceof Space) {
            return Yii::t('OnlinedrivesModule.base', 'Adds OnlineDrives module to this space.');
        } elseif ($container instanceof User) {
            return Yii::t('OnlinedrivesModule.base', 'Adds OnlineDrives module to your profile.');
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to([
                    '/onlinedrives/config'
        ]);
    }

    /**
     * Determines ZIP Support is enabled or not
     * 
     * @return boolean is ZIP support enabled
     */
    public function isZipSupportEnabled()
    {
        return !$this->settings->get('disableZipSupport', false);
    }

    /**
     * Determines ZIP Support is enabled or not
     *
     * @return boolean is ZIP support enabled
     */
    public function getUploadBehaviour()
    {
        return $this->settings->get('uploadBehaviour', self::UPLOAD_BEHAVIOUR_INDEX);
    }

}
