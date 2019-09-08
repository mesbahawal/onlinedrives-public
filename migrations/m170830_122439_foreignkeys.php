<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;

class m170830_122439_foreignkeys extends Migration
{
    public function safeUp()
    {
        try {
            $this->addForeignKey('fk_onlinedrives_file_parent_folder', 'onlinedrives_file', 'parent_folder_id', 'onlinedrives_folder', 'id', 'SET NULL');
            $this->addForeignKey('fk_onlinedrives_folder_parent_folder', 'onlinedrives_folder', 'parent_folder_id', 'onlinedrives_folder', 'id', 'SET NULL');
        } catch(Exception $e) {
            Yii::error($e);
        }
    }

    public function safeDown()
    {
        echo "m170830_122437_foreignkeys.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170830_122432_foreignkeys cannot be reverted.\n";

        return false;
    }
    */
}
