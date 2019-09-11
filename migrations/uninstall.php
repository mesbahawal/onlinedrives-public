<?php
use yii\db\Migration;

class uninstall extends Migration
{

    public function up()
    {
        $this->dropTable('onlinedrives_app_detail');
        $this->dropTable('onlinedrives_app_drive_path_detail');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }
}
