<?php
use yii\db\Schema;
use yii\db\Migration;

class m160810_174011_add_description extends Migration
{

    public function up()
    {
        $this->addColumn('onlinedrives_folder', 'description', $this->string(1000));
        $this->update('onlinedrives_folder', ['description' => '']);
        $this->addColumn('onlinedrives_file', 'description', $this->string(1000));
        $this->update('onlinedrives_file', ['description' => '']);
    }

    public function down()
    {
        echo "m160810_174011_add_description cannot be reverted.\n";
        
        return false;
    }
    
    /*
     * // Use safeUp/safeDown to run migration code within a transaction public function safeUp() { } public function safeDown() { }
     */
}
