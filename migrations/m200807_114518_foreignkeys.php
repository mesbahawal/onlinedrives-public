<?php

use yii\db\Migration;

/**
 * Class m200807_114518_foreignkeys
 */
class m200807_114518_foreignkeys extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        try {
            $this->addForeignKey('fk_onlinedrives_file_parent_folder', 'onlinedrives_file', 'parent_folder_id', 'onlinedrives_folder', 'id', 'SET NULL');
            $this->addForeignKey('fk_onlinedrives_folder_parent_folder', 'onlinedrives_folder', 'parent_folder_id', 'onlinedrives_folder', 'id', 'SET NULL');
        } catch(Exception $e) {
            Yii::error($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200807_114518_foreignkeys cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200807_114518_foreignkeys cannot be reverted.\n";

        return false;
    }
    */
}
