<?php

use humhub\modules\file\libs\FileHelper;
use humhub\libs\Html;


/* @var $previewImage \humhub\modules\file\converter\PreviewImage */
/* @var $onlineDrive \humhub\modules\onlinedrives\models\File */
/* @var $file \humhub\modules\file\models\File */
/* @var $fileSize integer */
/* @var $folderUrl string*/

?>

<div class="pull-left">
    <?php if ($previewImage->applyFile($file)): ?>
        <?= $previewImage->renderGalleryLink(['style' => 'padding-right:12px']); ?>
    <?php else: ?>
        <i class="fa <?= $onlineDrive->getIcon(); ?> fa-fw" style="font-size:40px"></i>
    <?php endif; ?>
</div>

<strong><?= FileHelper::createLink($file, null, ['style' => 'text-decoration: underline']); ?></strong><br />
<small><?= Yii::t('OnlinedrivesModule.base', 'Size: {size}', ['size' => Yii::$app->formatter->asShortSize($fileSize, 1)]); ?></small><br />

<?php if (!empty($onlineDrive->description)): ?>
    <br />
    <?= Html::encode($onlineDrive->description); ?>
    <br />
<?php endif; ?>

<br />

<?= Html::a(Yii::t('OnlinedrivesModule.base', 'Open file folder'), $folderUrl, ['class' => 'btn btn-sm btn-default', 'data-ui-loader' => '']); ?>

<div class="clearfix"></div>
