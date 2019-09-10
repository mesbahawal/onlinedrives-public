<?php

namespace humhub\modules\onlinedrives;

use humhub\modules\onlinedrives\models\File;
use humhub\modules\onlinedrives\models\Folder;
use Yii;
use yii\base\Event;


/**
 * onlinedrives Events
 *
 * @author luke
 */
class Events
{

    public static function onSpaceMenuInit($event)
    {

        if ($event->sender->space !== null && $event->sender->space->isModuleEnabled('onlinedrives')) {
            $event->sender->addItem([
                'label' => Yii::t('OnlinedrivesModule.base', 'OnlineDrives'),
                'group' => 'modules',
                'url' => $event->sender->space->createUrl('/onlinedrives/browse'),
                'icon' => '<i class="fa fa-files-o"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'onlinedrives')
            ]);
        }
    }

    /**
     * Callback to validate module database records.
     *
     * @param Event $event
     */
    public static function onIntegrityCheck($event)
    {
        $integrityController = $event->sender;
        $integrityController->showTestHeadline("OnlineDrive Module (" . File::find()->count() . " entries)");

        foreach (File::find()->all() as $file) {
            /* @var $file \humhub\modules\onlinedrives\models\File */

            // If parent_folder_id is 0 or null its an old root child which is not merged yet.
            if (!empty($file->parent_folder_id) && empty($file->parentFolder)) {
                if ($integrityController->showFix("Deleting onlinedrive id " . $file->id . " without existing parent!")) {
                    $file->delete();
                }
            }
        }

        $integrityController->showTestHeadline("OnlineDrive Module (" . File::find()->count() . " entries)");

        foreach (Folder::find()->all() as $folder) {
            /* @var $file \humhub\modules\onlinedrives\models\File */

            // If parent_folder_id is 0 or null its either an old root child which is not merged yet or an root directory.
            if (!empty($folder->parent_folder_id) && empty($folder->parentFolder)) {
                if ($integrityController->showFix("Deleting cfile folder id " . $folder->id . " without existing parent!")) {
                    $folder->delete();
                }
            }
        }
    }

    public static function onProfileMenuInit($event)
    {
        if ($event->sender->user !== null && $event->sender->user->isModuleEnabled('onlinedrives')) {
            $event->sender->addItem([
                'label' => Yii::t('OnlinedrivesModule.base', 'OnlineDrives'),
                'url' => $event->sender->user->createUrl('/onlinedrives/browse'),
                'icon' => '<i class="fa fa-files-o"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'onlinedrives')
            ]);
        }
    }

}
