<?php

use humhub\modules\onlinedrives\widgets\BreadcrumbBar;
use humhub\modules\onlinedrives\widgets\FileListMenu;
use humhub\modules\onlinedrives\widgets\FileList;
use humhub\modules\onlinedrives\widgets\FileSelectionMenu;
use humhub\modules\file\widgets\UploadProgress;
use yii\helpers\Html;

/* @var $this humhub\components\View */
/* @var $contentContainer humhub\components\View */
/* @var $folder humhub\modules\onlinedrives\models\Folder */
?>

<?= Html::beginTag('div', $options) ?>

<?= BreadcrumbBar::widget(['folder' => $folder, 'contentContainer' => $contentContainer]) ?>

<?= UploadProgress::widget(['id' => 'onlinedrives_progress']) ?>

<?= FileListMenu::widget([
    'folder' => $folder,
    'contentContainer' => $contentContainer,
]) ?>

<div id="fileList">
    <?= FileList::widget([
        'folder' => $folder,
        'contentContainer' => $contentContainer,
    ])?>
</div>
<?= Html::endTag('div') ?>
