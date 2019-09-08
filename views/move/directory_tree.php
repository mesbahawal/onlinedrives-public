<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\onlinedrives\models\Folder;

/* @var $this \humhub\components\View */
/* @var $root Folder */

$folderList = Folder::getFolderList($root);

?>

<div id="onlinedrives-directory-list" data-ui-widget="onlinedrives.DirectoryList" data-ui-init class="directory-list">
    <div class="selectable" data-id="<?= $root->id; ?>"><?= Yii::t('OnlinedrivesModule.base', '/ (root)'); ?></div>
    <ul>
        <?php  foreach (Folder::getFolderList($root) as $folder) :?>
            <?= $this->render('directory_tree_item', ['folder' => $folder]); ?>
        <?php endforeach ?>
    </ul>
</div>
