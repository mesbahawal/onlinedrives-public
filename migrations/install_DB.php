<?php
use yii\db\Schema;
use yii\db\Migration;

class install_DB extends Migration
{

    public function up()
    {
        $this->createTable('onlinedrives_app_detail', array(
            'id' => 'int(11) NOT NULL AUTO_INCREMENT',
            'space_id' => 'varchar(255) NOT NULL',
            'user_id' => 'varchar(255) NOT NULL',
            'email' => 'varchar(255) NOT NULL',
            'drive_name' => 'varchar(255) NOT NULL',
            'app_user_id' => 'varchar(255) NOT NULL',
            'app_password' => 'varchar(255) NOT NULL',
            'create_date' => 'int(11) NOT NULL',
            'if_shared' => 'varchar(1) NOT NULL DEFAULT \'N\''
        ), 'PRIMARY KEY (`app_user_id`), KEY `id` (`id`)');
        
        $this->createTable('onlinedrives_app_drive_path_detail', array(
            'id' => 'int(11) NOT NULL AUTO_INCREMENT',
            'drive_path' => 'varchar(255) DEFAULT NULL',
            'permission' => 'varchar(255) DEFAULT NULL',
            'onlinedrives_app_detail_id' => 'int(11) NOT NULL',
            'drive_key' => 'varchar(255) NOT NULL'
        ), 'PRIMARY KEY (`drive_key`), KEY `id` (`id`)');
    }

    public function down()
    {
        echo "DB_initial cannot be reverted.\n";
        
        return false;
    }
    
    /*
     * // Use safeUp/safeDown to run migration code within a transaction public function safeUp() { } public function safeDown() { }
     */
}
