<?php

use yii\db\Migration;

/**
 * Class m200807_112151_initial
 */
class m200807_112151_initial extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->createTable('onlinedrives_file', array(
            'id' => 'pk',
            'parent_folder_id' => 'int(11) NULL',
            'description' => 'varchar(1000) DEFAULT NULL'
        ), '');

        $this->createTable('onlinedrives_folder', array(
            'id' => 'pk',
            'parent_folder_id' => 'int(11) NULL',
            'title' => 'varchar(255) NOT NULL',
            'description' => 'varchar(1000) DEFAULT NULL',
            'type' => 'varchar(255) DEFAULT NULL'
        ), '');
		
		$this->createTable('onlinedrives_app_detail', array(
            'id' => 'int(11) NOT NULL AUTO_INCREMENT',
            'space_id' => 'varchar(255) NOT NULL',
            'user_id' => 'varchar(255) NOT NULL',
            'email' => 'varchar(255) NOT NULL',
            'drive_name' => 'varchar(255) NOT NULL',
            'app_user_id' => 'varchar(255) NOT NULL',
            'app_password' => 'varchar(255) NOT NULL',
            'create_date' => 'int(11) NOT NULL',
            'if_shared' => 'varchar(1) NOT NULL DEFAULT \'N\'',
            'PRIMARY KEY (`id`),
             KEY `id` (`id`)'
        ), '');

        $this->createTable('onlinedrives_app_drive_path_detail', array(
            'id' => 'int(11) NOT NULL AUTO_INCREMENT',
            'drive_path' => 'varchar(255) DEFAULT NULL',
            'fileid' => 'varchar(255) DEFAULT NULL',
            'permission' => 'varchar(255) DEFAULT NULL',
            'onlinedrives_app_detail_id' => 'int(11) NOT NULL',
            'drive_key' => 'varchar(128) NOT NULL',
            'share_status' => 'varchar(10) NOT NULL DEFAULT \'Y\'',
            'create_date' => 'timestamp NOT NULL DEFAULT current_timestamp()',
            'update_date' => 'timestamp NOT NULL DEFAULT current_timestamp()',
            'parent_id' => 'varchar(255) DEFAULT NULL',
            'content_id' => 'int(11) DEFAULT NULL',
            'mime_type' => 'varchar(255) DEFAULT NULL',
            'PRIMARY KEY (`drive_key`),  KEY `id` (`id`)'
        ), '');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200807_112151_initial cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200807_112151_initial cannot be reverted.\n";

        return false;
    }
    */
}
