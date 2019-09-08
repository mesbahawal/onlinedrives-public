<?php

use humhub\commands\IntegrityController;
use humhub\modules\space\widgets\Menu;
use humhub\modules\user\widgets\ProfileMenu;

return [
    'id' => 'onlinedrives',
    'class' => 'humhub\modules\onlinedrives\Module',
    'namespace' => 'humhub\modules\onlinedrives',
    'events' => [
        [Menu::class, Menu::EVENT_INIT, ['humhub\modules\onlinedrives\Events', 'onSpaceMenuInit']],
        [ProfileMenu::class, ProfileMenu::EVENT_INIT, ['humhub\modules\onlinedrives\Events', 'onProfileMenuInit']],
        [IntegrityController::class, IntegrityController::EVENT_ON_RUN, ['humhub\modules\onlinedrives\Events', 'onIntegrityCheck']]
    ]
];
?>
