<?php

namespace humhub\modules\onlinedrives\widgets;

/**
 * Widget for rendering the file list bar.
 */
class BreadcrumbBar extends \yii\base\Widget
{
    
    /**
     * @var \humhub\modules\onlinedrives\models\Folder current folder
     */
    public $folder;
    
    /**
     * Current content container.
     * @var \humhub\modules\content\components\ContentContainerActiveRecord 
     */
    public $contentContainer;

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('breadcrumbBar', [
            'folder' => $this->folder,
            'contentContainer' => $this->contentContainer
        ]);
    }

}
