<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

use Sabre\DAV;

include_once(__DIR__.'/../../models/dbconnect.php');
include __DIR__.'/../../vendor/autoload.php';

function getScieboClient($app_user_id, $app_password)
{
    $settings = array(
        'baseUri' => 'https://uni-siegen.sciebo.de/remote.php/dav',
        'userName' => $app_user_id,
        'password' => $app_password,
    );

    $client = new Sabre\DAV\Client($settings);

    return $client;
}


function array_to_xml( $data, &$xml_data ) {
    foreach( $data as $key => $value ) {
        if( is_numeric($key) ){
            $key = 'item'.$key; //dealing with <0/>..<n/> issues
        }
        if( is_array($value) ) {
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml_data->addChild("$key",htmlspecialchars("$value"));
        }
     }
}
//variable initialization
$content_length='';
$content_type='';

// Checked if user is logged in HumHub
$username = Yii::$app->user->identity->username;
$email = Yii::$app->user->identity->email;
if (!empty($username) && !empty($email))
{
//    if (!empty($_GET['dk'])) {
        $drive_key = $_GET['dk'];
        $db = dbconnect();
        $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.* 
                                FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p
                                ON d.id=p.onlinedrives_app_detail_id
                                WHERE drive_key = :drive_key', [':drive_key' => $drive_key])->queryAll();

        foreach ($sql as $value) {
            $space_id = $value['space_id'];
            $user_id = $value['user_id'];
            $drive_path = $value['drive_path'];
            $app_user_id = $value['app_user_id'];
            $app_password = $value['app_password'];
        }

        // Read real space ID from space
        $sql = $db->createCommand('SELECT id FROM space WHERE guid = :space_id', [':space_id' => $space_id])->queryAll();
        foreach ($sql as $value) {
            $real_space_id = $value['id'];
        }

        // Read real user ID from user
        $sql = $db->createCommand('SELECT id FROM user WHERE username = :username', [':username' => $user_id])->queryAll();
        foreach ($sql as $value) {
            $real_user_id = $value['id'];
        }

        // Check if user has a membership
        $sql = $db->createCommand('SELECT * FROM space_membership WHERE space_id = :real_space_id && user_id = :real_user_id', [
            ':real_space_id' => $real_space_id,
            ':real_user_id' => $real_user_id,
        ])->queryAll();

        if (count($sql) == 1)
        {


            $client = getScieboClient($app_user_id, $app_password);

            $path = str_replace(' ', '%20', $_GET['file']);

            $download = $client->request('GET', 'https://uni-siegen.sciebo.de/remote.php/webdav/'.$path); // For downloading files


            // initializing or creating array
            $data = $download;

            // creating object of SimpleXMLElement
            $xml_data = new SimpleXMLElement('<?xml version="1.0"?><data></data>');

            // function call to convert array to xml
            array_to_xml($data,$xml_data);

            //saving generated xml file; 
            //print $xml_data->asXML(Yii::$app->basePath . '\modules\onlinedrives\upload_dir\new.xml');

            //print $xml_data->headers->{"content-type"}->item0;

             

            $temp = substr($path, 0);
            $pos = strrpos($temp, '/');
            $file_name = substr($temp, $pos);

            // Correct file name
            if (substr($file_name, 0, 1) == '/') {
                $file_name = substr($file_name, 1);
            }
			
			if(!empty($xml_data->headers->{"content-type"}) && !empty($xml_data)) {
			$content_type= $xml_data->headers->{"content-type"}->item0;
			}
			
			if(!empty($xml_data->headers->{"content-length"}) && !empty($xml_data)) {
			$content_length= $xml_data->headers->{"content-length"}->item0;
			}

            //$file_name = urldecode($file_name); //TODO 

			header('Content-Type: application/octet-stream');
            header('Content-type: '.$content_type);
            header('Content-Disposition: attachment; filename='.$file_name);
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: '.$content_length);
			flush(); // Flush system output buffer

            echo $download["body"];
			die();
        }
    //}
}

?>