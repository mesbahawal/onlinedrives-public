<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\controllers;

use humhub\modules\onlinedrives\actions\UploadZipAction;
use Yii;
use yii\web\HttpException;
use humhub\modules\onlinedrives\libs\ZIPCreator;
use humhub\modules\onlinedrives\widgets\FileList;
use yii\web\UploadedFile;

/**
 * ZipController
 *
 * @author Sebastian Stumpf
 */
class ZipController extends BrowseController
{

    public function getAccessRules()
    {
        return [
            ['checkZipSupport']
        ];
    }

    public function actions()
    {
        return [
            'upload' => [
                'class' => UploadZipAction::class,
            ],
        ];
    }

    public function checkZipSupport($rule, $access)
    {
        if (!$this->module->isZipSupportEnabled()) {
            $access->code = 404;
            $access->reason = Yii::t('OnlinedrivesModule.base', 'ZIP support is not enabled.');
            return false;
        }

        return true;
    }

    /**
     * Action to download a zip of the selected items.
     */
    public function actionDownload()
    {
        $items = FileList::getSelectedItems();

        if (count($items) === 0) {
            // Fallback to current folder when not items are selected
            $items[] = $this->getCurrentFolder();
        }

        $zip = new ZIPCreator();
        foreach ($items as $item) {
            $zip->add($item);
        }
        $zip->close();

        return Yii::$app->response->sendFile($zip->getZipFile(), (count($items) == 1) ? $items[0]->title . '.zip' : 'files.zip');
    }
}
