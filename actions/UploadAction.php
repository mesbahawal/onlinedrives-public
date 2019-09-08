<?php

namespace humhub\modules\onlinedrives\actions;

use humhub\modules\onlinedrives\models\File;
use humhub\modules\onlinedrives\models\FileSystemItem;
use humhub\modules\onlinedrives\Module;
use humhub\modules\file\libs\FileHelper;
use Yii;
use yii\web\UploadedFile;

/**
 * Class UploadAction
 *
 */
class UploadAction extends \humhub\modules\file\actions\UploadAction
{
    /**
     * @var \humhub\modules\onlinedrives\controllers\BrowseController
     */
    public $controller;

    public function run()
    {
        $result = parent::run();
        $result['fileList'] = $this->controller->renderFileList();
        return $result;
    }

    protected function handleFileUpload(UploadedFile $uploadedFile, $hideInStream = false)
    {
        $folder = $this->controller->getCurrentFolder();

        $file = $folder->addUploadedFile($uploadedFile, Yii::$app->getModule('onlinedrives')->getUploadBehaviour());

        if($file->hasErrors()) {
            return $this->getValidationErrorResponse($file);
        }

        if($file->baseFile->hasErrors()) {
            return $this->getErrorResponse($file->baseFile);
        }

        return array_merge(['error' => false], FileHelper::getFileInfos($file->baseFile));
    }

    protected function getValidationErrorResponse(FileSystemItem $file)
    {
        $errorMessage = Yii::t('FileModule.actions_UploadAction', 'File {fileName} could not be uploaded!', ['fileName' => $file->baseFile->name]);

        if(!empty($file->hasErrors())) {
            $errorMessage = array_values($file->getErrors())[0];
        }

        return [
            'error' => true,
            'errors' => $errorMessage,
            'name' => $file->baseFile->name,
            'size' => $file->baseFile->size
        ];
    }
}
