<?php

use yii\db\Migration;

/**
 * Class m200807_114305_set_root_partent_null
 */
class m200807_114305_set_root_partent_null extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->update('onlinedrives_file', ['parent_folder_id' => new \yii\db\Expression('NULL')], ['parent_folder_id' => 0]);
        $this->update('onlinedrives_folder', ['parent_folder_id' => new \yii\db\Expression('NULL')], ['parent_folder_id' => 0]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200807_114305_set_root_partent_null cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200807_114305_set_root_partent_null cannot be reverted.\n";

        return false;
    }
    */
}
