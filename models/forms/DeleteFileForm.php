<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\models\forms;

use Exception;
use Google_Client;
use Google_Service_Drive;
use Yii;
use yii\base\Model;
use Sabre\DAV;

include __DIR__ . '/../../vendor/autoload.php';

class DeleteFileForm extends \yii\base\Model
{
    public $cloud;
    public $delete_file_id;
    public $dk;

    public function rules()
    {
        return [
            [['cloud', 'delete_file_id', 'dk'], 'required'],
        ];
    }

    public function getScieboClient($userid, $password)
    {
        $settings = array(
            'baseUri' => 'https://uni-siegen.sciebo.de/remote.php/dav/',
            'userName' => $userid,
            'password' => $password,
	    );
	    $client = new \Sabre\DAV\Client($settings);

	    return $client;
	}

    function getGoogleClient($db, $dk, $home_url, $guid)
    {
        // Check for database entry for Google Drive and this space
        $sql = $db->createCommand('SELECT onlinedrives_app_detail_id FROM onlinedrives_app_drive_path_detail
            WHERE drive_key = :drive_key', [
            ':drive_key' => $dk,
        ])->queryAll();
        foreach ($sql as $value) {
            $app_detail_id = $value['onlinedrives_app_detail_id'];
        }
        $sql = $db->createCommand('SELECT app_password FROM onlinedrives_app_detail
            WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id', [
            ':onlinedrives_app_detail_id' => $app_detail_id,
        ])->queryAll();

        $client = new Google_Client();
        $client->setApplicationName('HumHub');
        $client->addScope(Google_Service_Drive::DRIVE);
        $client->setAuthConfig('protected/modules/onlinedrives/'.$app_password.'.json');
        $client->setAccessType('offline'); // Offline access
        $client->setPrompt('select_account consent');
        $client->setRedirectUri($home_url.'/index.php?r=onlinedrives%2Fbrowse&'.$guid);

        $tokenPath = 'protected/modules/onlinedrives/'.$app_password.'.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }
        // If there is no previous token or it's expired
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            }
            else {
                // Request authorization from the user
                if (!isset($_GET['code'])) {
                    $authUrl = $client->createAuthUrl();
                    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL)) or die();
                }
                //Hier Code übergeben
                if (isset($_GET['code'])) {
                    $code = $_GET['code'];

                    $accessToken = $client->fetchAccessTokenWithAuthCode($code);
                    $client->setAccessToken($accessToken);

                    // Check to see if there was an error
                    if (array_key_exists('error', $accessToken)) {
                        throw new Exception(join(', ', $accessToken));
                    }

                    // Save the token to a file
                    if (!file_exists(dirname($tokenPath))) {
                        mkdir(dirname($tokenPath), 0700, true);
                    }
                    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
                }
            }
        }

        return $client;
    }
}
?>