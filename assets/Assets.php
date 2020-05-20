<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\assets;

use yii\web\AssetBundle;

class Assets extends AssetBundle {
    public $publishOptions = [
        'forceCopy' => false
    ];
    
    public $css = [
        'css/onlinedrives.css',
        'css/directorylist.css',
        'css/newstyles.css',
        'css/owner_shared_comments.css',
    ];

    public $jsOptions = [
        'position' => \yii\web\View::POS_BEGIN
    ];

    public $js = [
        'js/humhub.onlinedrives.js',
        'js/tableScripts.js',
    ];

    public function init() {
        $this->sourcePath = dirname(dirname(__FILE__)) . '/resources';
        parent::init();
    }

}
