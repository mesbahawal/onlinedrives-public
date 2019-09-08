<?php

use humhub\modules\onlinedrives\models\File;
use humhub\modules\onlinedrives\models\rows\FileSystemItemRow;
use humhub\modules\file\libs\FileHelper;
use humhub\widgets\LinkPager;
use yii\helpers\Html;
use humhub\modules\onlinedrives\widgets\FileSystemItem;

/* @var $itemsInFolder boolean */
/* @var $itemsSelectable boolean */
/* @var $canWrite boolean */
/* @var $folder \humhub\modules\onlinedrives\models\Folder */
/* @var $rows \humhub\modules\onlinedrives\models\rows\AbstractFileSystemItemRow[] */
/* @var $sort string */
/* @var $order string*/
/* @var $pagination \yii\data\Pagination */

?>
<?php if ($itemsInFolder) : ?>
    <div class="table-responsive">
        <table id="bs-table" class="table table-hover">
            <thead>
            <tr>
                <?php if ($itemsSelectable): ?>
                    <th class="text-center" style="width:38px;">
                        <?= Html::checkbox('allchk', false, ['class' => 'allselect']); ?>
                    </th>
                <?php endif; ?>

                <th class="text-left" data-ui-sort="<?= FileSystemItemRow::ORDER_TYPE_NAME ?>"  <?= $sort === FileSystemItemRow::ORDER_TYPE_NAME ? 'data-ui-order="'.Html::encode($order).'"' : '' ?>>
                    <?= Yii::t('OnlinedrivesModule.base', 'Name'); ?>
                </th>


                <th class="hidden-xxs"></th>

                <th class="hidden-xs text-right" data-ui-sort="<?= FileSystemItemRow::ORDER_TYPE_SIZE ?>"  <?= $sort === FileSystemItemRow::ORDER_TYPE_SIZE ? 'data-ui-order="'.Html::encode($order).'"' : '' ?>><?= Yii::t('OnlinedrivesModule.base', 'Size'); ?></th>
                <th class="hidden-xxs text-right"  data-ui-sort="<?= FileSystemItemRow::ORDER_TYPE_UPDATED_AT ?>" <?= $sort === FileSystemItemRow::ORDER_TYPE_UPDATED_AT ? 'data-ui-order="'.Html::encode($order).'"' : '' ?>><?= Yii::t('OnlinedrivesModule.base', 'Updated'); ?></th>

                <?php if (!$folder->isAllPostedFiles()): // Files currently have no content object but the Post they may be connected to.  ?>
                    <th class="text-right"><?= Yii::t('OnlinedrivesModule.base', 'Likes/Comments'); ?></th>
                <?php endif; ?>

                <th class="hidden-xxs text-right"><?= Yii::t('OnlinedrivesModule.base', 'Creator'); ?></th>
            </tr>
            </thead>

            <?php foreach ($rows as $row) : ?>
                <?= FileSystemItem::widget([
                    'row' => $row,
                    'itemsSelectable' => $itemsSelectable
                ]); ?>
            <?php endforeach; ?>

        </table>
        <?php if ($pagination) : ?>
            <div class="text-center">
                <?= LinkPager::widget(['pagination' => $pagination]); ?>
            </div>
        <?php endif; ?>
    </div>
<?php else : ?>
    <br/>
    <div class="folderEmptyMessage">
        <div class="panel">
            <div class="panel-body">
                <p>
                    <strong><?= Yii::t('OnlinedrivesModule.base', 'This folder is empty.'); ?></strong>
                </p>
                <?php if ($folder->isAllPostedFiles()): ?>
                    <?= Yii::t('OnlinedrivesModule.base', 'Upload files to the stream to fill this folder.'); ?>
                <?php elseif ($canWrite): ?>
                    <?= Yii::t('OnlinedrivesModule.base', 'Upload files or create a subfolder with the buttons on the top.'); ?>
                <?php else: ?>
                    <?= Yii::t('OnlinedrivesModule.base', 'Unfortunately you have no permission to upload/edit files.'); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
