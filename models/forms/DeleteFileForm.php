<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\onlinedrives\models\forms;

use Yii;
use yii\base\Model;
use Sabre\DAV;

include __DIR__."/../../vendor/autoload.php";

class DeleteFileForm extends \yii\base\Model
{
    public $cloud;
    public $delete_file_id;

    public function rules()
    {
        return [
            [['cloud'], 'required'],
            [['delete_file_id'], 'required']
        ];
    }

    public function getScieboClient($userid,$password) {
    	
	    $settings = array(
	        'baseUri' => 'https://uni-siegen.sciebo.de/remote.php/dav/',
	        'userName' => $userid,
	        'password' => $password,
	    );
	    $client = new \Sabre\DAV\Client($settings);



	    return $client;
	}
}
