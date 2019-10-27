<?php

use humhub\modules\admin\models\forms\FileSettingsForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

use Sabre\DAV;
use humhub\modules\onlinedrives\widgets\FileListContextMenu;
use humhub\modules\onlinedrives\widgets\FolderView;

use humhub\modules\onlinedrives\models\forms\LoginForm;
use humhub\modules\onlinedrives\models\forms\LoginFormGDClient;
use humhub\modules\onlinedrives\models\forms\CreateFileForm;
use humhub\modules\onlinedrives\models\forms\UploadFileForm;
use humhub\modules\onlinedrives\models\forms\DeleteFileForm;

// DB connection
include_once __DIR__ . '/../../models/dbconnect.php';
$db = dbconnect();

// General vars
$now = time();
$home_url = Url::base(true);

// Declare vars
$all_folders = array();
$all_files = array();
$all = 0; // Counter of all folders and files
$afo = 0; // Counter of all folders
$afi = 0; // Counter of all files

// Read username
$username = '';
if (isset(Yii::$app->user->identity->username)) {
    $username = Yii::$app->user->identity->username;
}

// Get GUID
if (!empty($_GET['cguid'])) {
    $guid = 'cguid=' . $_GET['cguid']; // Get param, important for paths
}

// Read success/error message
$success_msg = '';
$error_msg = '';
if (isset($_REQUEST['success_msg'])) {
    $success_msg = $_REQUEST['success_msg'];
}
elseif (isset($_REQUEST['error_msg'])) {
    $error_msg = $_REQUEST['error_msg'];
}


/**
 * Functions
 */
function month_name_to_number($number) {
    switch ($number) {
        case 'Jan': return 1; break;
        case 'Feb': return 2; break;
        case 'Mar': return 3; break;
        case 'Apr': return 4; break;
        case 'May': return 5; break;
        case 'Jun': return 6; break;
        case 'Jul': return 7; break;
        case 'Aug': return 8; break;
        case 'Sep': return 9; break;
        case 'Oct': return 10; break;
        case 'Nov': return 11; break;
        case 'Dec': return 12; break;
    }
}

// Sorting
// https://www.php.net/manual/de/array.sorting.php
function cmp_name_asc($a, $b) {
    return strcmp(strtolower($a['name']), strtolower($b['name']));
}
function cmp_name_desc($a, $b) {
    return strcmp(strtolower($b['name']), strtolower($a['name']));
}
function cmp_modified_time_asc($a, $b) {
    return strcmp(strtolower($a['modified_time']), strtolower($b['modified_time']));
}
function cmp_modified_time_desc($a, $b) {
    return strcmp(strtolower($b['modified_time']), strtolower($a['modified_time']));
}
function cmp_created_time_asc($a, $b) {
    return strcmp(strtolower($a['created_time']), strtolower($b['created_time']));
}
function cmp_created_time_desc($a, $b) {
    return strcmp(strtolower($b['created_time']), strtolower($a['created_time']));
}

function getScieboClient($user_id, $pw) {
    $settings = array(
        'baseUri' => 'https://uni-siegen.sciebo.de/remote.php/dav/',
        'userName' => $user_id,
        'password' => $pw,
    );
    $client = new Sabre\DAV\Client($settings);

    return $client;
}

function getScieboFiles($client, $app_user_id, $drive_path) {
    $folder_content = false;
    $home_url = Url::base(true);

    if (!empty($_GET['cguid'])) {
        $guid = 'cguid=' . $_GET['cguid']; // Get param, important for paths
    }

    try {
        $folder_content = $client->propFind('https://uni-siegen.sciebo.de/remote.php/dav/files/'.$app_user_id.'/'.$drive_path, array(
            '{http://owncloud.org/ns}fileid', // ID
            '{DAV:}getetag', //TODO doesn't work
            '{DAV:}creationdate', //TODO doesn't work
            '{DAV:}getlastmodified',
            '{DAV:}getcontenttype',
            '{DAV:}getcontentlength',
            '{DAV:}getcontentname', //TODO doesn't work
            '{http://owncloud.org/ns}favorite',
            '{http://owncloud.org/ns}share-types',
            '{http://owncloud.org/ns}owner-display-name',
            '{http://owncloud.org/ns}comments-count',
        ), 1);

        return $folder_content;
    }
    catch ( Sabre\HTTP\ClientHttpException $e) {
        Yii::warning("Sciebo Connection Unseccessful");
    }
}

// Create necessary folders for Google Drive client JSON files
$path_client = 'protected/modules/onlinedrives/upload_dir/google_client';
$path_tokens = 'protected/modules/onlinedrives/upload_dir/google_client/tokens';
if (!file_exists($path_client)) {
    mkdir($path_client, 0700);
}
if (!file_exists($path_tokens)) {
    mkdir($path_tokens, 0700);
}

function getGoogleClient($db, $space_id, $home_url, $guid) {

    $client = false ;
    // Check for database entries for Google Drive and this space
    $sql = $db->createCommand('SELECT * FROM onlinedrives_app_detail WHERE space_id = :space_id AND drive_name = :drive_name AND if_shared NOT IN (\'D\')', [
        ':space_id' => $space_id,
        ':drive_name' => 'gd',
    ])->queryAll();

    if (count($sql)>0) {


            foreach ($sql as $value) {
            $app_password = $value['app_password'];
            $client = new Google_Client();
            $client->setApplicationName('HumHub');
            $client->addScope(Google_Service_Drive::DRIVE);
            $client->setAuthConfig('protected/modules/onlinedrives/upload_dir/google_client/'.$app_password.'.json');
            $client->setAccessType('offline'); // Offline access
            $client->setPrompt('select_account consent');
            $client->setRedirectUri($home_url.'/index.php?r=onlinedrives%2Fbrowse&'.$guid);

            $tokenPath = 'protected/modules/onlinedrives/upload_dir/google_client/tokens/'.$app_password.'.json';
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
                    // Hier Code übergeben
                    if (isset($_GET['code'])) {
                        $code = $_GET['code'];

                        $accessToken = $client->fetchAccessTokenWithAuthCode($code);
                        $client->setAccessToken($accessToken);

                        // Check to see if there was an error
                        if (array_key_exists('error', $accessToken)) {
                            //throw new Exception(join(', ', $accessToken));
                            return false;
                        }

                        // Save the token to a file
                        if (!file_exists(dirname($tokenPath))) {
                            mkdir(dirname($tokenPath), 0700, true);
                        }

                        if(file_put_contents($tokenPath, json_encode($client->getAccessToken()))){

                            $sql = $db->createCommand('UPDATE onlinedrives_app_detail SET if_shared = \'N\' WHERE app_password = :app_password', [
                                ':app_password' => $app_password,
                            ])->execute();
                        }
                        else{
                            return false;
                        }
                    }
                }
            }
        }
        return $client;
    }
    else {
        return false;
    }
}


/**
 * Get params
 */

// Space ID
$space_id = '';
if (!empty($_GET['cguid'])) {
    $space_id = $_GET['cguid'];
}

// Declare vars
$get_drive_key = '';
$get_sciebo_path = '';
$get_gd_folder_id = '';
$get_gd_folder_name = '';

// Both
if (!empty($_GET['dk'])) {
    $get_drive_key = $_GET['dk'];
}

if ($get_drive_key != '') {
    // Sciebo params
    if (!empty($_GET['sciebo_path'])) {
        $get_sciebo_path = $_GET['sciebo_path'];
    }
    elseif (!empty($_POST['sciebo_path'])) {
        $get_sciebo_path = $_POST['sciebo_path'];
    }

    // Rework
    $get_sciebo_path = str_replace(' ', '%20', $get_sciebo_path);

    // Google Drive params
    if (!empty($_GET['gd_folder_id']) && !empty($_GET['gd_folder_name'])) {
        $get_gd_folder_id = $_GET['gd_folder_id'];
        $get_gd_folder_name = $_GET['gd_folder_name'];
    }
    elseif (!empty($_POST['gd_folder_id']) && !empty($_POST['gd_folder_name'])) {
        $get_gd_folder_id = $_POST['gd_folder_id'];
        $get_gd_folder_name = $_POST['gd_folder_name'];
    }
}

// Order-by param
if (!empty($_GET['order_by'])) {
    $order_by = $_GET['order_by'];
}
else {
    $order_by = 'name_asc';
}

/* @var $folder humhub\modules\onlinedrives\models\Folder */
/* @var $contentContainer humhub\components\View */
/* @var $canWrite boolean */

$bundle = \humhub\modules\onlinedrives\assets\Assets::register($this);

$this->registerJsConfig('onlinedrives', [
    'text' => [
        'confirm.delete' => Yii::t('OnlinedrivesModule.base', 'Do you really want to delete this {number} item(s) with all subcontent?'),
        'confirm.delete.header' => Yii::t('OnlinedrivesModule.base', '<strong>Confirm</strong> delete file'),
        'confirm.delete.confirmText' => Yii::t('OnlinedrivesModule.base', 'Delete')
    ],
    'showUrlModal' => [
        'head' => Yii::t('OnlinedrivesModule.base', '<strong>File</strong> url'),
        'headFile' => Yii::t('OnlinedrivesModule.base', '<strong>File</strong> download url'),
        'headFolder' => Yii::t('OnlinedrivesModule.base', '<strong>Folder</strong> url'),
        'info' => Yii::t('base', 'Copy to clipboard'),
        'buttonClose' => Yii::t('base', 'Close'),
    ]
]);


/**
 * Google Drive client
 */
require_once __DIR__ . '/../../vendor/autoload.php';

$session = Yii::$app->session;

// Get the API client and construct the service object
$gd_client = getGoogleClient($db, $space_id, $home_url, $guid);
if ($gd_client !== false) {
    $gd_service = new Google_Service_Drive($gd_client);
}


/**
 * Access check
 */

$k = 0;
$n = 0;

$arr_app_user_detail = array();
$arr_app_user_detail_with_no_share = array();
$check = 0;

// Count Sciebo login entries
$count_sciebo_accounts = 0;
$sql = $db->createCommand('SELECT * FROM onlinedrives_app_detail WHERE space_id = :space_id AND drive_name = :drive_name', [
    ':space_id' => $space_id,
    ':drive_name' => 'sciebo',
])->queryAll();
if (count($sql) > 0) {
    $count_sciebo_accounts = count($sql);
}

// DB check
if ($username <> '' && !isset($_GET['op'])) {
    // Load Sciebo entries
    $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.* 
        FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p ON d.id = p.onlinedrives_app_detail_id
        WHERE d.space_id = :space_id AND d.drive_name = :drive_name', [
        ':space_id' => $space_id,
        ':drive_name' => 'sciebo',
    ])->queryAll();
    foreach ($sql as $value) {
        $drive_path = $value['drive_path'];
        $app_user_id = $value['app_user_id'];
        $app_password = $value['app_password'];
        $drive_key = $value['drive_key'];
        $uid = $value['uid'];
        $pid = $value['pid'];
        $if_shared = $value['if_shared'];
        $share_status = $value['share_status'];
        $user_id = $value['user_id'];

        if ($if_shared == 'Y' && $share_status == 'Y') {
            $arr_app_user_detail[$k]['drive_path'] = $drive_path;
            $arr_app_user_detail[$k]['app_user_id'] = $app_user_id;
            $arr_app_user_detail[$k]['app_password'] = $app_password;
            $arr_app_user_detail[$k]['drive_key'] = $drive_key;
            $arr_app_user_detail[$k]['user_id'] = $user_id;
            $k++;
        }
        else {
            $arr_app_user_detail_with_no_share[$n]['drive_path'] = $drive_path;
            $arr_app_user_detail_with_no_share[$n]['app_user_id'] = $app_user_id;
            $arr_app_user_detail_with_no_share[$n]['app_password'] = $app_password;
            $arr_app_user_detail_with_no_share[$n]['drive_key'] = $drive_key;
            $arr_app_user_detail_with_no_share[$n]['user_id'] = $user_id;
            $arr_app_user_detail_with_no_share[$n]['if_shared'] = $if_shared;
            $arr_app_user_detail_with_no_share[$n]['share_status'] = $share_status;
            $n++;
        }
    }

    // Load Google Drive entries
    $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.* 
        FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p ON d.id = p.onlinedrives_app_detail_id
        WHERE d.space_id = :space_id AND d.drive_name = :drive_name', [
            ':space_id' => $space_id,
            ':drive_name' => 'gd',
        ])->queryAll();
    foreach ($sql as $value) {
        $drive_path = $value['drive_path'];
        $app_user_id = $value['app_user_id'];
        $app_password = $value['app_password'];
        $drive_key = $value['drive_key'];
        $uid = $value['uid'];
        $pid = $value['pid'];
        $if_shared = $value['if_shared'];
        $share_status = $value['share_status'];
        $user_id = $value['user_id'];

        if ($gd_client !== false) {
            $arr_app_user_detail[$k]['drive_path'] = $drive_path;
            $arr_app_user_detail[$k]['app_user_id'] = $app_user_id;
            $arr_app_user_detail[$k]['app_password'] = $app_password;
            $arr_app_user_detail[$k]['drive_key'] = $drive_key;
            $arr_app_user_detail[$k]['user_id'] = $user_id;
            $k++;
        }
        else {
            $arr_app_user_detail_with_no_share[$n]['drive_path'] = $drive_path;
            $arr_app_user_detail_with_no_share[$n]['app_user_id'] = $app_user_id;
            $arr_app_user_detail_with_no_share[$n]['app_password'] = $app_password;
            $arr_app_user_detail_with_no_share[$n]['drive_key'] = $drive_key;
            $arr_app_user_detail_with_no_share[$n]['user_id'] = $user_id;
            $arr_app_user_detail_with_no_share[$n]['if_shared'] = $if_shared;
            $arr_app_user_detail_with_no_share[$n]['share_status'] = $share_status;
            $n++;
        }
    }
}
// Disable app detail ID
elseif ($username <> '' && isset($_GET['op']) && isset($_GET['app_detail_id'])) {
    if ($_GET['op'] == 'disable' && $_GET['app_detail_id'] != '') {
        $app_detail_id = $_GET['app_detail_id'];

        // Before update check user id and authority;

        $sql = $db->createCommand('SELECT * FROM onlinedrives_app_detail
            WHERE id = :app_detail_id AND user_id = :user_id AND if_shared NOT IN (\'D\')', [
            ':app_detail_id' => $app_detail_id,
            ':user_id' => $username,
        ])->queryAll();

        if (count($sql) > 0) {
            $sql = $db->createCommand('UPDATE onlinedrives_app_detail SET if_shared = \'D\' WHERE id = :app_detail_id', [
                ':app_detail_id' => $app_detail_id,
            ])->execute();

            $redirect_url = $home_url.'/index.php?r=onlinedrives%2Fbrowse&'.$guid;

            (new yii\web\Controller('1', 'onlinedrives'))->redirect($redirect_url);
        }
        else {
            $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Un-authorized Action!');
        }
    }
}
else {
    (new yii\web\Controller('1','onlinedrives'))->redirect($home_url);
    //return $this->redirect($home_url);
}


/**
 * Sciebo client
 */
if (count($arr_app_user_detail) > 0) { // Start of Sciebo according to the database table rows

for ($j = 0; $j < count($arr_app_user_detail); $j++) { // Start of for loop (j)

$drive_path = $arr_app_user_detail[$j]['drive_path'];
$app_user_id = $arr_app_user_detail[$j]['app_user_id'];
$app_password = $arr_app_user_detail[$j]['app_password'];
$drive_key = $arr_app_user_detail[$j]['drive_key'];

// Set Sciebo path to replace with user ID
$sciebo_path_to_replace = '/remote.php/dav/files/'.$app_user_id.'/';

if ($drive_path != '' || $drive_path != '/' || // For Sciebo
    $gd_service !== false                      // For Google Drive
) {
    $check = 1;
    if ($drive_path == '/') {
        $drive_path = '';
    }

    // Get the API client and construct the service object
    $sciebo_client = getScieboClient($app_user_id, $app_password);
}


/**
 * Check (1) start
 */

if ($check == 1) {


/**
 * Rework form
 */

// Create folder, create file
if (!empty($model->new_folder_name) || !empty($model->new_file_name)) {
    $cloud = $model->selected_cloud;
    $do = $model->create;
    // Folder name
    if (!empty($model->new_folder_name)) {
        $name = $model->new_folder_name;
    }
    // File name
    else {
        $name = $model->new_file_name;
    }

    // Check for validate name
    $name = trim($name);
    if (substr($name, 0, 1) != '.') {
        // Sciebo
        if ($cloud == 'sciebo') {
            if ($do == 'create_folder') {
                // http://sabre.io/dav/davclient
                $db_app_user_id = '';
                if ($get_drive_key != '') {
                    $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.* 
                        FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p
                        ON d.id = p.onlinedrives_app_detail_id
                        WHERE drive_key = :drive_key', [
                        ':drive_key' => $get_drive_key,
                    ])->queryAll();

                    foreach ($sql as $value) {
                        $db_app_user_id = $value['app_user_id'];
                        $db_drive_key = $value['drive_key'];
                    }
                }
                if ($get_drive_key == '' || ($db_app_user_id == $app_user_id && $drive_key == $get_drive_key)) {
                    $sciebo_content = getScieboFiles($sciebo_client, $app_user_id, $get_sciebo_path);
                }

                $upload_file_content = date('F j, Y, g:i a');
                $upload_list = str_replace(' ', '%20', $name);
                $path_to_dir = 'https://uni-siegen.sciebo.de/remote.php/dav/files/'.$app_user_id.'/'.$get_sciebo_path.$upload_list;

                $keys = array_keys($sciebo_content);

                foreach ($keys as $values) {
                    if (str_replace($sciebo_path_to_replace, '', $values) === $upload_list)                 {
                        echo '<br />File already exist! Please rename.'; //TODO No output at the moment?
                        return false;
                    }

                    $response = $sciebo_client->request('MKCOL', $path_to_dir); // For creating folder only
                    //$response = $client->request('MKCOL', $path_to_dir, $upload_file_content); // For creating files

                    // Success msg
                    $success_msg = Yii::t('OnlinedrivesModule.new', 'Ordner wurde erfolgreich in Sciebo erstellt.');
                }
            }
            elseif ($do == 'create_file') {
                // Check for correct type
                $pos = strrpos($name, '.');
                $type = substr($name, $pos);
                if ($type == '.txt' || $type == '.docx' || $type == '.xlsx' || $type == '.pptx' || $type == '.odt') {
                    $name = str_replace(' ', '%20', $name);

                    $path_to_dir = 'https://uni-siegen.sciebo.de/remote.php/dav/files/'.$app_user_id.'/'.$get_sciebo_path.'/'.$name;
                    //$content = 'New contents';
                    $content = '';
                    $response = $sciebo_client->request('PUT', $path_to_dir, $content);

                    // Success msg
                    $success_msg = Yii::t('OnlinedrivesModule.new', 'Datei wurde erfolgreich in Sciebo erstellt.');
                }
            }
        }
        // Google Drive
        elseif ($cloud == 'gd') {
            if ($do == 'upload_file') {
            // TODO Google Drive UPLOAD file function
/*
            $content = file_get_contents('files/'.$upload);

            $file_metadata = new Google_Service_Drive_DriveFile(array(
                'data' => $content,
                'name' => '22.txt',
                'mimeType' => 'text/plain'));

            // Parent folder ID.
            if ($get_gd_folder_id == '') {
            	$gd_parent_id = '0AESKNHa25CPzUk9PVA';
            }
            else {
            	$gd_parent_id = $get_gd_folder_id;
            }
            $file_metadata->setParents(array($gd_parent_id));

            $file = $gd_service->files->create($file_metadata, array(
                'fields' => 'id'));
*/
            }
            elseif ($do == 'create_folder') {
                $file_metadata = new Google_Service_Drive_DriveFile(array(
                    'name' => $name,
                    'mimeType' => 'application/vnd.google-apps.folder'));

                // Parent folder ID
                if ($get_gd_folder_id == '') {
                	$gd_parent_id = '0AESKNHa25CPzUk9PVA';
                }
                else {
                	$gd_parent_id = $get_gd_folder_id;
                }
                $file_metadata->setParents(array($gd_parent_id));

                $file = $gd_service->files->create($file_metadata, array('fields' => 'id'));

                // Success msg
                $success_msg = Yii::t('OnlinedrivesModule.new', 'Ordner wurde erfolgreich in Google Drive erstellt.');
            }
            elseif ($do == 'create_file') {
                // https://stackoverflow.com/questions/26919709/google-drive-php-api-insert-file-to-drive

                // Check for correct type
                $pos = strrpos($name, '.');
                $type = substr($name, $pos);
                if ($type == '.txt' || $type == '.docx' || $type == '.xlsx' || $type == '.pptx' || $type == '.odt') {
                    $file_metadata = new Google_Service_Drive_DriveFile(array(
                    'name' => $name,
                    'title' => 'My document',           // Doesn't work?
                    'description' => 'A test document', // Works
                    'mimeType' => 'text/plain'));

                    // Parent folder ID.
                    if ($get_gd_folder_id == '') {
                    	$gd_parent_id = '0AESKNHa25CPzUk9PVA';
                    }
                    else {
                    	$gd_parent_id = $get_gd_folder_id;
                    }
                    $file_metadata->setParents(array($gd_parent_id));

                    $file = $gd_service->files->create($file_metadata, array('fields' => 'id'));

                    // Success msg
                    $success_msg = Yii::t('OnlinedrivesModule.new', 'Datei wurde erfolgreich in Google Drive erstellt.');
                }
            }
        }
    }
}


/**
 * Get Sciebo files
 */

if (!empty($get_sciebo_path)) {
    $drive_path = $get_sciebo_path;
}

$db_app_user_id = '';
$sciebo_content = array();
$count_sciebo_files = 0;
if ($get_gd_folder_id == '') {
    if ($get_drive_key != '') {
        $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.*
            FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p ON d.id = p.onlinedrives_app_detail_id
            WHERE drive_key = :drive_key', [
            ':drive_key' => $get_drive_key,
        ])->queryAll();
        foreach ($sql as $value) {
            $db_app_user_id = $value['app_user_id'];
            $db_drive_key = $value['drive_key'];
        }
    }

    if ($get_drive_key == '' || ($db_app_user_id == $app_user_id && $drive_key == $get_drive_key)) {
        $sciebo_content = getScieboFiles($sciebo_client, $app_user_id, $drive_path);
    }

    if (isset($sciebo_content)) {
        $count_sciebo_files = count($sciebo_content);
    }
    else {
        $count_sciebo_files = 0;
    }
}

if ($count_sciebo_files > 0) {
    $keys = array_keys($sciebo_content);
    foreach ($keys as $values) {
        /*
        -if root dir is selected to share, then we have to put '/' in the table
        -if sub-folder is selected, then we have to put 'subfolder/' in the table, no '/' in the beginig
        -for sharing files follow the same rule of subfolder
        */
        if ($drive_path == '/') {
        	$drive_path = '';
        }
        $base_dir = '/remote.php/dav/files/'.$app_user_id.'/'.$drive_path; // Base directory (landing directory of shared folder)

        if ($values == $base_dir || (!empty($get_sciebo_path) && $values != $base_dir) || $drive_path == '') {
        	// ID
        	$id = $sciebo_content[$values]['{http://owncloud.org/ns}fileid'];

            // Path
            $path = str_replace($sciebo_path_to_replace, '', $values);

            // Download link
            //$download_link = 'https://uni-siegen.sciebo.de/remote.php/webdav/'.$path;
            //http://localhost/humhub-1.3.14/index.php?r=onlinedrives%2Fbrowse%2Fdownloader&cguid=1da6ad03-ba87-429e-8797-46fa193a27be
            //$download_link = $home_url.'/protected/modules/onlinedrives/views/browse/downloader.php?file='.urlencode($path);
            $download_link = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Fdownloader&'.$guid.'&dk='.$drive_key.'&file='.urlencode($path);

            // Mime type
            // Type (folder or file)
            // Open link
            if (substr($values, -1) == '/') {
                $temp = substr($values, 0, -1);
                $pos = strrpos($temp, '/');
                $sciebo_file_name = substr($temp, $pos + 1);

                // URL decode
                $sciebo_file_name = urldecode($sciebo_file_name);

                $mime_type = ''; // In Sciebo folder seems to have no mime type
                $type = 'folder';

                // Open link
                $open_link = 'https://uni-siegen.sciebo.de/apps/files/?dir=%2F'.$path.'&fileid='.$id;
            }
            else {
                $pos = strrpos($values, '/');
                $sciebo_file_name = substr($values, $pos + 1);

                // URL decode
                $sciebo_file_name = urldecode($sciebo_file_name);

                $mime_type = $sciebo_content[$values]['{DAV:}getcontenttype'];
                $type = 'file';

                // Open link
                $open_link = 'https://uni-siegen.sciebo.de/apps/onlyoffice/'.$id.'?filePath=%2F'.$path;
            }

/*
            $parent = '';
            if ($path != $get_sciebo_path) {
                if (substr($path, -1) == '/') { $temp_path = substr($path, 0, -1); }
                else { $temp_path = $path; }
                $pos = strrpos($temp_path, '/');
                $parent = substr($temp_path, 0, $pos + 1);
            }
            else { $temp_path = $path; }
*/

            // Output check beause of ". entry" of current folder
            if ($path != $get_sciebo_path) {
                // Modified time
                $temp = $sciebo_content[$values]['{DAV:}getlastmodified'];
                $temp_d = substr($temp, 5, 2);
                $temp_mon = month_name_to_number(substr($temp, 8, 3));
                $temp_y = substr($temp, 12, 4);
                $temp_h = substr($temp, 17, 2);
                $temp_min = substr($temp, 20, 2);
                $temp_s = substr($temp, 23, 2);
                $modified_time = mktime($temp_h, $temp_min, $temp_s, $temp_mon, $temp_d, $temp_y);
                $modified_time += 60*60*2; // European time zone

                // Favorite
                $fav = $sciebo_content[$values]['{http://owncloud.org/ns}favorite'];

                // Owner, shared, comments
                $file_owner = $sciebo_content[$values]['{http://owncloud.org/ns}owner-display-name'];
                $file_shared = $sciebo_content[$values]['{http://owncloud.org/ns}share-types'];
                $file_comment = $sciebo_content[$values]['{http://owncloud.org/ns}comments-count'];

                // Folder list
                if ($type == 'folder') {
                    $all_folders[$afo]['cloud'] = 'sciebo';
                    $all_folders[$afo]['cloud_name'] = 'Sciebo';
                    $all_folders[$afo]['id'] = $id;
                    $all_folders[$afo]['path'] = $path;
                    $all_folders[$afo]['name'] = $sciebo_file_name;
                    $all_folders[$afo]['mime_type'] = $mime_type;
                    $all_folders[$afo]['type'] = $type;
                    $all_folders[$afo]['created_time'] = '';              // TODO Sciebo hasn't? (creationdate seems not to work.)
                    $all_folders[$afo]['modified_time'] = $modified_time;
                    $all_folders[$afo]['icon_link'] = '';                 // Sciebo hasn't?
                    $all_folders[$afo]['thumbnail_link'] = '';            // Sciebo hasn't?
                    $all_folders[$afo]['web_content_link'] = '';          // Sciebo hasn't?
                    $all_folders[$afo]['web_view_link'] = $open_link;
                    $all_folders[$afo]['download_link'] = $download_link;
                    $all_folders[$afo]['parents'] = '';                   // TODO Sciebo hasn't?
                    $all_folders[$afo]['fav'] = $fav;
                    $all_folders[$afo]['file_owner'] = $file_owner;
                    $all_folders[$afo]['file_shared'] = $file_shared;
                    $all_folders[$afo]['file_comment'] = $file_comment;
                    $all_folders[$afo]['drive_key'] = $drive_key;
                    $afo++;
                }
                // File list
                else {
                    $all_files[$afi]['cloud'] = 'sciebo';
                    $all_files[$afi]['cloud_name'] = 'Sciebo';
                    $all_files[$afi]['id'] = $id;
                    $all_files[$afi]['path'] = $values;
                    $all_files[$afi]['name'] = $sciebo_file_name;
                    $all_files[$afi]['mime_type'] = $mime_type;
                    $all_files[$afi]['type'] = $type;
                    $all_files[$afi]['created_time'] = '';              // TODO Sciebo hasn't? (creationdate seems not to work.)
                    $all_files[$afi]['modified_time'] = $modified_time;
                    $all_files[$afi]['icon_link'] = '';                 // Sciebo hasn't?
                    $all_files[$afi]['thumbnail_link'] = '';            // Sciebo hasn't?
                    $all_files[$afi]['web_content_link'] = '';          // Sciebo hasn't?
                    $all_files[$afi]['web_view_link'] = $open_link;
                    $all_files[$afi]['download_link'] = $download_link;
                    $all_files[$afi]['parents'] = '';                   // TODO Sciebo hasn't?
                    $all_files[$afi]['fav'] = $fav;
                    $all_files[$afi]['file_owner'] = $file_owner;
                    $all_files[$afi]['file_shared'] = $file_shared;
                    $all_files[$afi]['file_comment'] = $file_comment;
                    $all_files[$afi]['drive_key'] = $drive_key;
                    $afi++;
                }
                $all++;
            }
        }
    }
}


/**
 * Check (1) end
 */
}
}// end of for loop (j)

/**
 * end of sciebo according to the DB table rows
 */
}




/**
 * Body
 */
echo Html::beginForm(null, null, ['data-target' => '#globalModal', 'id' => 'onlinedrives-form']);
?>

    <div id="onlinedrives-container" class="panel panel-default onlinedrives-content main_div_container">

        <div class="panel-body">

            <?php
            /*
            echo FolderView::widget([
                'contentContainer' => $contentContainer,
                'folder' => $folder,
            ])
            */
            ?>

<!-- Breadcrumb navigation -->
<div class="box">
    <?php
    // Output start of navigation
    $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse&'.$guid;
    echo '<a href="'.$ref.'">' . Yii::t('OnlinedrivesModule.new', 'All drives') . '</a>';

    // Output Sciebo navigation
    if ($get_sciebo_path != '') {
        // Output Sciebo icon in navigation
        $ref = 'https://uni-siegen.sciebo.de/login';
        $src = 'protected/modules/onlinedrives/resources/sciebo20.png';
        echo ' /
        <a href="'.$ref.'" target="_blank">
            <img src="'.$src.'" style="position: relative; top: -2px;" title="Sciebo" />
        </a>';
/*
        // Test%201A/ins/
        // Check breadcrumb for shared location
        $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.*
            FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p ON d.id=p.onlinedrives_app_detail_id
            WHERE drive_key = :drive_key', [
            ':drive_key' => $get_dk,
        ])->queryAll();
        foreach ($sql as $value) {
            $drive_path = $value['drive_path'];
            $app_user_id = $value['app_user_id'];
        }
*/
        // Build rest of Sciebo navigation
        $navi = '';
        $path = '';
        $temp = $get_sciebo_path;
        do {
            // Read out Sciebo folder name
            $pos = strpos($temp, '/');
            $name = substr($temp, 0, $pos);
            // Update Sciebo path
            $path .= $name.'/';
            // Change temp var
            $temp = substr($temp, $pos + 1);

            // Decode name for output
            $name = urldecode($name);

            // Build output
            $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse&'.$guid.'&sciebo_path='.$path.'&dk='.$get_drive_key;
            $navi .= ' / <a href="'.$ref.'">'.$name.'</a>';
        } while ($temp != '');

        // Output rest of Sciebo navigation
        echo $navi;
    }
    // Output Google Drive navigation
    elseif ($get_gd_folder_id != '') {
        // Build Google Drive icon for navigation
        $ref = 'https://accounts.google.com/ServiceLogin';
        $src = 'protected/modules/onlinedrives/resources/gd20.png';

        // Output Google Drive icon in navigation
        echo ' /
        <a href="'.$ref.'" target="_blank">
            <img src="'.$src.'" style="position: relative; top: -2px;" title="Google Drive" />
        </a>';

        // Build rest of Google Drive navigation
        $navi = '';
        $check_id = $get_gd_folder_id;
        $check_name = $get_gd_folder_name;
        do {
            // Send query
            $params = array(
                'q' => 'name="'.$check_name.'"',
                'fields' => 'nextPageToken, files(*)',
                'orderBy' => 'folder, name',
            );
            $results = $gd_service->files->listFiles($params);

            // Read query results
            foreach ($results->getFiles() as $file) {
                $id = $file->getId(); // Read folder ID
                if ($id == $check_id) {
                    $name = $file->getName(); // Read folder name
                    $parents = $file->getParents(); // Read parent folder ID
                    break;
                }
            }

            // Build output
            $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse&'.$guid.'&gd_folder_id='.$id.'&gd_folder_name='.$name.'&dk='.$drive_key;
            $navi = ' / <a href="'.$ref.'">'.$name.'</a>'.$navi;

            // Change search name for next loop
            $check_id = $parents[0]; // Change parent folder ID to check
            if ($check_id != '0AESKNHa25CPzUk9PVA') { // Means root
                $file = $gd_service->files->get($check_id);
                $check_name = $file->getName(); // Change folder name to check
            }
        } while ($check_id != '0AESKNHa25CPzUk9PVA'); // Means root

        // Output rest of Google Drive navigation
        echo $navi;
    }
    ?>

    <!-- Login menu icon -->
    <span id="login_menu_icon" class="glyphicon glyphicon-menu-hamburger" onclick="getElementById('login_menu').style.display = 'block';"></span>

    <!-- Plus menu icon -->
    <?php
    // Check (2)
    if ($check == 1) {
        echo '<span id="plus_menu_icon" class="glyphicon glyphicon-plus" onclick="getElementById(\'plus_menu\').style.display = \'block\';"></span>';
    }
    ?>
</div>


<?php
/**
 * Sciebo data who didn't share
 */
$arr_app_user_admin = array();
$adm = 0;

if ($username <> '') {
    $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.*
        FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p ON d.id = p.onlinedrives_app_detail_id
        WHERE d.space_id = :space_id AND d.user_id = :user_id
        GROUP BY d.app_user_id', [
        ':space_id' => $space_id,
        ':user_id' => $username,
    ])->queryAll();
    foreach ($sql as $value) {
        $drive_path = $value['drive_path'];
        $app_user_id = $value['app_user_id'];
        $app_password = $value['app_password'];
        $drive_key = $value['drive_key'];
        $uid = $value['uid'];
        $pid = $value['pid'];
        $if_shared = $value['if_shared'];
        $share_status = $value['share_status'];
        $user_id = $value['user_id'];
        $drive_name = $value['drive_name'];

        $arr_app_user_admin[$adm]['drive_path'] = $drive_path;
        $arr_app_user_admin[$adm]['app_user_id'] = $app_user_id;
        $arr_app_user_admin[$adm]['app_password'] = $app_password;
        $arr_app_user_admin[$adm]['drive_key'] = $drive_key;
        $arr_app_user_admin[$adm]['user_id'] = $user_id;
        $arr_app_user_admin[$adm]['if_shared'] = $if_shared;
        $arr_app_user_admin[$adm]['share_status'] = $share_status;
        $arr_app_user_admin[$adm]['uid'] = $uid;
        $arr_app_user_admin[$adm]['pid'] = $pid;
        $arr_app_user_admin[$adm]['drive_name'] = $drive_name;
        $adm++;
    }
}

if (count($arr_app_user_admin) > 0) {
    //echo "Here implement Drive add form ".count($arr_app_user_detail_with_no_share);
    $logged_username =  Yii::$app->user->identity->username;
    $email = Yii::$app->user->identity->email;
    ?>

    <div class="box">
        <table id="table" class="table table-responsive">
            <thead>
                <?php
                for ($j = 0; $j < count($arr_app_user_admin); $j++) { // Start of for loop (j)
                    $drive_path = $arr_app_user_admin[$j]['drive_path'];
                    $app_user_id = $arr_app_user_admin[$j]['app_user_id'];
                    $app_password = $arr_app_user_admin[$j]['app_password'];
                    $drive_key = $arr_app_user_admin[$j]['drive_key'];
                    $username = $arr_app_user_admin[$j]['user_id'];
                    $if_shared = $arr_app_user_admin[$j]['if_shared'];
                    $share_status = $arr_app_user_admin[$j]['share_status'];
                    $uid = $arr_app_user_admin[$j]['uid'];
                    $pid = $arr_app_user_admin[$j]['pid'];
                    $drive_name = $arr_app_user_admin[$j]['drive_name'];

                    if ($username == $logged_username && $if_shared != 'D' && $if_shared != 'T') {
                    ?>
                        <!-- Table for selecting path -->
                        <tr>
                            <td class="valign_m">
                                <?php
                                // Output Sciebo icon in navigation
                                if ($drive_name == 'sciebo') {
                                    $ref = 'https://uni-siegen.sciebo.de/login';
                                }
                                elseif ($drive_name == 'gd') {
                                    $ref = 'https://accounts.google.com/ServiceLogin';
                                }
                                $src = 'protected/modules/onlinedrives/resources/'.$drive_name.'20.png';
                                echo '<a href="'.$ref.'" target="_blank">
                                    <img src="'.$src.'" style="position: relative; top: -2px;" title="Sciebo" />
                                </a>';
                                ?>

                                <b>
                                    <?php echo $app_user_id; ?>
                                </b>
                            </td>
                            <td>
                                <?php
                                echo '<a class="btn btn-success" href="'.$home_url.'/index.php?r=onlinedrives%2Fbrowse%2Faddfiles&'.$guid.'&sciebo_path=&app_detail_id='.$uid.'">'.
                                    'Add'.
                                '</a>';
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($if_shared == 'Y') {
                                    /*echo '<a class="btn btn-default" href="'.$home_url.'/index.php?r=onlinedrives%2Fbrowse%2Faddfiles&'.$guid.'&sciebo_path=&app_detail_id='.$uid.'">'.
                                        'Update'.
                                    '</a>';*/
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($if_shared != 'D') {
                                    echo '<a class="btn btn-danger" href="'.$home_url.'/index.php?r=onlinedrives%2Fbrowse%2Findex&'.$guid.'&op=disable&app_detail_id='.$uid.'">'.
                                        'Disable'.
                                    '</a>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php
                    }
                }
                ?>
            </thead>
        </table>
    </div>
<?php
}
?>


<!-- Login menu -->
<div id="login_menu">
<?php
/**
 * Form for login
 */
$model_login = new LoginForm();
$form_login = ActiveForm::begin([
    'id' => 'login_form',
    'method' => 'post',
    'options' => ['class' => 'form-horizontal'],
]);
?>

<!-- Cross icon (login menu) -->
<img src="protected/modules/onlinedrives/resources/cross.png" alt="X" title="<?php echo Yii::t('OnlinedrivesModule.new', 'Close'); ?>"
    style="position:absolute; right: 10px; width: 10px; height: 10px; cursor: pointer;"
    onclick="
        getElementById('login_menu').style.display = 'none';
        getElementById('select_sciebo_login').src = 'protected/modules/onlinedrives/resources/sciebo_gray50.png';
        getElementById('select_gd_login').src = 'protected/modules/onlinedrives/resources/gd_gray50.png';
" />

<?php
// Icons for cloud selection
echo $form_login->field($model_login, 'selected_cloud_login')->radioList([
    'sciebo' => '<img
        id="select_sciebo_login"
        class="upcr_icon"
        src="protected/modules/onlinedrives/resources/sciebo_gray50.png"
        alt="Sciebo"
        title="Sciebo"
        onclick="
            getElementById(\'line_gd_login\').className = \'line_icons shownone\';
            getElementById(\'select_gd_login\').src = \'protected/modules/onlinedrives/resources/gd_gray50.png\';
            this.src = \'protected/modules/onlinedrives/resources/sciebo50.png\';
            getElementById(\'line_sciebo_login\').className = \'line_icons showblock\';
            getElementById(\'form_sciebo_login\').className = \'showblock\';
            getElementById(\'form_gd_login\').className = \'shownone\';

            getElementById(\'create_btn_login\').className = \'showblock\';
            getElementById(\'create_btn_login_gd_client_upload\').className = \'shownone\';
    " />',
    'gd' => '<img
        id="select_gd_login"
        class="upcr_icon"
        src="protected/modules/onlinedrives/resources/gd_gray50.png"
        alt="Google Drive"
        title="Google Drive"
        onclick="
            getElementById(\'line_sciebo_login\').className = \'line_icons shownone\';
            getElementById(\'select_sciebo_login\').src = \'protected/modules/onlinedrives/resources/sciebo_gray50.png\';
            this.src = \'protected/modules/onlinedrives/resources/gd50.png\';
            getElementById(\'line_gd_login\').className = \'line_icons showblock\';
            getElementById(\'form_sciebo_login\').className = \'shownone\';
            getElementById(\'form_gd_login\').className = \'showblock\';

            getElementById(\'create_btn_login\').className = \'shownone\';
            getElementById(\'create_btn_login_gd_client_upload\').className = \'showblock\';
    " />',
], ['encode' => false]); // https://stackoverflow.com/questions/46094352/display-image-with-label-in-radiobutton-yii2

// Lines
echo '<div id="line_sciebo_login" class="line_icons shownone"></div>'.
'<div id="line_gd_login" class="line_icons shownone"></div><br/><span style="color: red" id="err_msg"></span>';
?>

<!-- Login Sciebo form -->
<div id="form_sciebo_login">
    <div id="app_id" style="
        position: relative;
        margin: 0;
        padding: 15px;
        padding-bottom: 0;
    ">
        <div class="upcr_label"><?php echo Yii::t('OnlinedrivesModule.new', 'AppID'); ?></div>
        <?php echo $form_login->field($model_login, 'app_id'); ?>
    </div>

    <div id="app_id" style="
        position: relative;
        margin: 0;
        padding: 15px;
        padding-bottom: 0;
    ">
        <div class="upcr_label"><?php echo Yii::t('OnlinedrivesModule.new', 'Password'); ?></div>
        <?php echo $form_login->field($model_login, 'password')->passwordInput(); ?>
    </div>
</div>

<!-- Send button Sciebo login-->
<div id="create_btn_login" class="form-group">
    <div class="col-lg-offset-1 col-lg-11">
        <?php
        echo Html::submitButton(Yii::t('OnlinedrivesModule.new', 'Send'), ['class' => 'btn btn-primary',
            'onclick' =>
                'var select_sciebo_login_src = getElementById(\'select_sciebo_login\').src;
                var src_sciebo_gray50 = \''.$home_url.'/protected/modules/onlinedrives/resources/sciebo_gray50.png\';
                var select_gd_login_src = getElementById(\'select_gd_login\').src;

                if (select_sciebo_login_src == \''.$home_url.'/protected/modules/onlinedrives/resources/sciebo_gray50.png\' &&
                    select_gd_login_src == \''.$home_url.'/protected/modules/onlinedrives/resources/gd_gray50.png\'
                ) {
                    document.getElementById(\'err_msg\').innerHTML = "Please select a cloud service";

                    return false;
                }

                if (select_sciebo_login_src == \''.$home_url.'/protected/modules/onlinedrives/resources/sciebo50.png\' &&
                    select_gd_login_src == \''.$home_url.'/protected/modules/onlinedrives/resources/gd_gray50.png\'
                ) {
    				var app_id = document.getElementById(\'loginform-app_id\').value;
                    if (app_id == \'\') {
                        document.getElementById(\'err_msg\').innerHTML = \'App Id Required!\';
                        document.getElementById(\'loginform-app_id\').focus();

                        return false;
                    }

                    var password = document.getElementById(\'loginform-password\').value;
                    if (password == \'\') {
                        document.getElementById(\'err_msg\').innerHTML = \'Password Required!\';
                        document.getElementById(\'loginform-password\').focus();

                        return false;
                    }
                }
            ']);
        ?>
    </div>
</div>

<?php ActiveForm::end(); ?>

<!-- Login Goolge Drive form -->
<div id="form_gd_login" class="shownone">
    <?php
    /**
     * Form for login Google Drive client
     */
    $model_login_gd_client_upload = new LoginFormGDClient();
    $form_login_gd_client_upload = ActiveForm::begin([
        'id' => 'login_form_gd_client_upload',
        'method' => 'post',
        'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
    ]);
    ?>

    <div id="app_id" style="
        position: relative;
        margin: 0;
        padding: 15px;
        padding-bottom: 0;
    ">
        <div class="upcr_label"><?php echo Yii::t('OnlinedrivesModule.new', 'AppID'); ?></div>
        <div style="
            margin-left: 15px;
            width: 119px;
        ">
            <?php echo $form_login_gd_client_upload->field($model_login_gd_client_upload, 'gd_app_id'); ?>
        </div>
    </div>

    <div id="app_id" style="
        position: relative;
        top: -10px;
        margin: 0;
        padding: 15px;
        padding-bottom: 0;
    ">
        <div class="upcr_label"><?php echo Yii::t('OnlinedrivesModule.new', 'JSON file'); ?></div>
        <?php echo $form_login_gd_client_upload->field($model_login_gd_client_upload, 'upload_gd_client_secret_file')->fileInput([]); ?>
    </div>
</div>

<!-- Send button Google Drive login -->
<div id="create_btn_login_gd_client_upload" class="form-group shownone" style="position: relative; top: -7px;">
    <div class="col-lg-offset-1 col-lg-11">
        <?php
        echo Html::submitButton(Yii::t('OnlinedrivesModule.new', 'Send'), ['class' => 'btn btn-primary',
            'onclick' =>
                'var select_sciebo_login_src = getElementById(\'select_sciebo_login\').src;
                var src_sciebo_gray50 = \''.$home_url.'/protected/modules/onlinedrives/resources/sciebo_gray50.png\';
                var select_gd_login_src = getElementById(\'select_gd_login\').src;

                if (select_sciebo_login_src == \''.$home_url.'/protected/modules/onlinedrives/resources/sciebo_gray50.png\' &&
                    select_gd_login_src == \''.$home_url.'/protected/modules/onlinedrives/resources/gd_gray50.png\'
                ) {
                    document.getElementById(\'err_msg\').innerHTML = "Please select a cloud service";

                    return false;
                }

                if (select_sciebo_login_src == \''.$home_url.'/protected/modules/onlinedrives/resources/sciebo50.png\' &&
                    select_gd_login_src == \''.$home_url.'/protected/modules/onlinedrives/resources/gd_gray50.png\'
                ) {
                    var gd_app_id = document.getElementById(\'loginformgdclient-gd_app_id\').value;
                    if (gd_app_id == \'\') {
                        document.getElementById(\'err_msg\').innerHTML = \'App Id Required!\';
                        document.getElementById(\'loginformgdclient-gd_app_id\').focus();

                        return false;
                    }

                    
                }
            ']);
        ?>
    </div>
</div>

</div>

<?php
ActiveForm::end();


/**
 * Check (3) start
 */
if ($check == 1) {
?>


<!-- Plus menu -->
<div id="plus_menu">


<?php
/**
 * Form for creating folder and creating file
 */
$model = new CreateFileForm();
$form = ActiveForm::begin([
    'id' => 'create_file_form',
    'method' => 'post',
    'options' => ['class' => 'form-horizontal'],
]);
?>

<!-- Cross icon (plus menu) -->
<img src="protected/modules/onlinedrives/resources/cross.png" alt="X" title="<?php echo Yii::t('OnlinedrivesModule.new', 'Close'); ?>"
    style="position:absolute; right: 10px; width: 10px; height: 10px; cursor: pointer;"
    onclick="
    	getElementById('plus_menu').style.display = 'none';
        getElementById('select_sciebo').src = 'protected/modules/onlinedrives/resources/sciebo_gray50.png';
        getElementById('select_gd').src = 'protected/modules/onlinedrives/resources/gd_gray50.png';
        getElementById('create_folder').className = 'upcr_btn btn-info btn-lg upcr_shaddow fa fa-folder-open fa-lg';
        getElementById('create_file').className = 'upcr_btn btn-info btn-lg upcr_shaddow fa fa-file fa-lg';
        getElementById('type_txt').src = 'protected/modules/onlinedrives/resources/type/gray/txt.png';
        getElementById('type_docx').src = 'protected/modules/onlinedrives/resources/type/gray/docx.png';
        getElementById('type_xlsx').src = 'protected/modules/onlinedrives/resources/type/gray/xlsx.png';
        getElementById('type_pptx').src = 'protected/modules/onlinedrives/resources/type/gray/pptx.png';
        getElementById('type_odt').src = 'protected/modules/onlinedrives/resources/type/gray/odt.png';
" />


<?php
// Icons for cloud selection
if ($get_sciebo_path == '' && $get_gd_folder_id == '') {
    echo $form->field($model, 'selected_cloud')->radioList([
        'sciebo' => '<img
            id="select_sciebo"
            class="upcr_icon"
            src="protected/modules/onlinedrives/resources/sciebo_gray50.png"
            alt="Sciebo"
            title="Sciebo"
            onclick="
                getElementById(\'line_gd\').className = \'line_icons shownone\';
                getElementById(\'select_gd\').src = \'protected/modules/onlinedrives/resources/gd_gray50.png\';
                this.src = \'protected/modules/onlinedrives/resources/sciebo50.png\';
                getElementById(\'line_sciebo\').className = \'line_icons showblock\';
                getElementById(\'uploadfileform-selected_cloud_u\').value = \'sciebo\';
        " />',
        'gd' => '<img
            id="select_gd"
            class="upcr_icon"
            src="protected/modules/onlinedrives/resources/gd_gray50.png"
            alt="Google Drive"
            title="Google Drive"
            onclick="
                getElementById(\'line_sciebo\').className = \'line_icons shownone\';
                getElementById(\'select_sciebo\').src = \'protected/modules/onlinedrives/resources/sciebo_gray50.png\';
                this.src = \'protected/modules/onlinedrives/resources/gd50.png\';
                getElementById(\'line_gd\').className = \'line_icons showblock\';
                getElementById(\'uploadfileform-selected_cloud_u\').value = \'gd\';
        " />',
    ], ['encode' => false]); // https://stackoverflow.com/questions/46094352/display-image-with-label-in-radiobutton-yii2

    // Lines
    echo '<div id="line_sciebo" class="line_icons shownone"></div>'.
    '<div id="line_gd" class="line_icons shownone"></div>';
}
elseif ($get_sciebo_path != '') {
    echo '<div class="shownone">'.
        $form->field($model, 'selected_cloud')->textInput(['value' => 'sciebo']).
    '</div>';
}
elseif ($get_gd_folder_id != '') {
    echo '<div class="shownone">'.
        $form->field($model, 'selected_cloud')->textInput(['value' => 'gd']).
    '</div>';
}

// Wrapper container
echo '<div class="rel" style="margin-bottom: 50px;">'.

	// Icons for creating folder and creating file
    $form->field($model, 'create')->radioList([
	    'create_folder' => '<div class="upcr_btn_div">
    	    <span id="create_folder" class="upcr_btn btn-info btn-lg upcr_shaddow fa fa-folder-open fa-lg" title="'.Yii::t('OnlinedrivesModule.new', 'Create folder').'"
        	    onclick="
                    getElementById(\'create_btn\').className = \'form-group showblock\';

                    getElementById(\'upload_file\').className = \'upcr_btn btn-info btn-lg upcr_shaddow fa fa-cloud-upload fa-lg\';
            	    this.className = \'upcr_btn btn-info btn-lg upcr_shaddow fa fa-folder-open fa-lg upcr_btn_active\';
                	getElementById(\'create_file\').className = \'upcr_btn btn-info btn-lg upcr_shaddow fa fa-file fa-lg\';
                	getElementById(\'create_folder_name\').classList.toggle(\'showblock\');
                	getElementById(\'create_file_name\').className = \'shownone\';
                    getElementById(\'createfileform-new_folder_name\').focus();
                    getElementById(\'type_txt\').src = \'protected/modules/onlinedrives/resources/type/gray/txt.png\';
                    getElementById(\'type_docx\').src = \'protected/modules/onlinedrives/resources/type/gray/docx.png\';
                    getElementById(\'type_xlsx\').src = \'protected/modules/onlinedrives/resources/type/gray/xlsx.png\';
                    getElementById(\'type_pptx\').src = \'protected/modules/onlinedrives/resources/type/gray/pptx.png\';
                    getElementById(\'type_odt\').src = \'protected/modules/onlinedrives/resources/type/gray/odt.png\';
	        "></span>
    	</div>',
	    'create_file' => '<div class="upcr_btn_div">
    	    <span id="create_file" class="upcr_btn btn-info btn-lg upcr_shaddow fa fa-file fa-lg" title="'.Yii::t('OnlinedrivesModule.new', 'Create file').'"
        	    onclick="
                    getElementById(\'create_btn\').className = \'form-group showblock\';

                    getElementById(\'upload_file\').className = \'upcr_btn btn-info btn-lg upcr_shaddow fa fa-cloud-upload fa-lg\';
            	    getElementById(\'create_folder\').className = \'upcr_btn btn-info btn-lg upcr_shaddow fa fa-folder-open fa-lg\';
                	this.className = \'upcr_btn btn-info btn-lg upcr_shaddow fa fa-file fa-lg upcr_btn_active\';
                	getElementById(\'create_folder_name\').className = \'shownone\';
                	getElementById(\'create_file_name\').classList.toggle(\'showblock\');
                    getElementById(\'createfileform-new_file_name\').focus();
	        "></span>
    	</div>',
	], ['encode' => false]); // https://stackoverflow.com/questions/46094352/display-image-with-label-in-radiobutton-yii2

echo '</div>';

        //echo $form->field($model, 'new_file_upload')->fileInput(); // https://forum.yiiframework.com/t/uploading-file-help-please/78531
        //TODO xx
?>

    <div id="create_folder_name" class="shownone"
        style="
            position: relative;
            margin: 0;
            padding: 15px;
            padding-bottom: 0;
    ">
	    <div class="upcr_label">Name</div>
        <?php echo $form->field($model, 'new_folder_name'); ?>
    </div>

    <div id="create_file_name" class="shownone"
        style="
            position: relative;
            margin: 0;
            padding: 15px;
            padding-bottom: 0;
    ">
        <?php
        echo $form->field($model, 'new_file_type')->radioList([
            'txt' => '<img
                id="type_txt"
                class="type_icon"
                src="protected/modules/onlinedrives/resources/type/gray/txt.png"
                alt="Text file"
                title="Text file"
                onclick="
                	type = \'.txt\';
                	name = getElementById(\'createfileform-new_file_name\').value;
                	pos = name.lastIndexOf(\'.\');
                	sub = name.substr(pos);
                	if (sub != type) {
                		if (sub == \'.docx\' || sub == \'.xlsx\' || sub == \'.pptx\' || sub == \'.odt\') {
                			name = name.substr(0, pos);
                			}
	                	getElementById(\'createfileform-new_file_name\').value = name + type;
                        getElementById(\'type_docx\').src = \'protected/modules/onlinedrives/resources/type/gray/docx.png\';
                        getElementById(\'type_xlsx\').src = \'protected/modules/onlinedrives/resources/type/gray/xlsx.png\';
                        getElementById(\'type_pptx\').src = \'protected/modules/onlinedrives/resources/type/gray/pptx.png\';
                        getElementById(\'type_odt\').src = \'protected/modules/onlinedrives/resources/type/gray/odt.png\';
                        this.src = \'protected/modules/onlinedrives/resources/type/txt.png\';
	                }
            " />',
            'docx' => '<img
                id="type_docx"
                class="type_icon"
                src="protected/modules/onlinedrives/resources/type/gray/docx.png"
                alt="Document"
                title="Document"
                onclick="
                	type = \'.docx\';
                	name = getElementById(\'createfileform-new_file_name\').value;
                	pos = name.lastIndexOf(\'.\');
                	sub = name.substr(pos);
                	if (sub != type) {
                		if (sub == \'.txt\' || sub == \'.xlsx\' || sub == \'.pptx\' || sub == \'.odt\') {
                			name = name.substr(0, pos);
                		}
	                	getElementById(\'createfileform-new_file_name\').value = name + type;
                        getElementById(\'type_txt\').src = \'protected/modules/onlinedrives/resources/type/gray/txt.png\';
                        getElementById(\'type_xlsx\').src = \'protected/modules/onlinedrives/resources/type/gray/xlsx.png\';
                        getElementById(\'type_pptx\').src = \'protected/modules/onlinedrives/resources/type/gray/pptx.png\';
                        getElementById(\'type_odt\').src = \'protected/modules/onlinedrives/resources/type/gray/odt.png\';
                        this.src = \'protected/modules/onlinedrives/resources/type/docx.png\';
	                }
            " />',
            'xlsx' => '<img
                id="type_xlsx"
                class="type_icon"
                src="protected/modules/onlinedrives/resources/type/gray/xlsx.png"
                alt="Table"
                title="Table"
                onclick="
                	type = \'.xlsx\';
                	name = getElementById(\'createfileform-new_file_name\').value;
                	pos = name.lastIndexOf(\'.\');
                	sub = name.substr(pos);
                	if (sub != type) {
                		if (sub == \'.txt\' || sub == \'.docx\' || sub == \'.pptx\' || sub == \'.odt\') {
                			name = name.substr(0, pos);
                		}
	                	getElementById(\'createfileform-new_file_name\').value = name + type;
                        getElementById(\'type_txt\').src = \'protected/modules/onlinedrives/resources/type/gray/txt.png\';
                        getElementById(\'type_docx\').src = \'protected/modules/onlinedrives/resources/type/gray/docx.png\';
                        getElementById(\'type_pptx\').src = \'protected/modules/onlinedrives/resources/type/gray/pptx.png\';
                        getElementById(\'type_odt\').src = \'protected/modules/onlinedrives/resources/type/gray/odt.png\';
                        this.src = \'protected/modules/onlinedrives/resources/type/xlsx.png\';
	                }
            " />',
            'pptx' => '<img
                id="type_pptx"
                class="type_icon"
                src="protected/modules/onlinedrives/resources/type/gray/pptx.png"
                alt="Presentation"
                title="Presentation"
                onclick="
                	type = \'.pptx\';
                	name = getElementById(\'createfileform-new_file_name\').value;
                	pos = name.lastIndexOf(\'.\');
                	sub = name.substr(pos);
                	if (sub != type) {
                		if (sub == \'.txt\' || sub == \'.docx\' || sub == \'.xlsx\' || sub == \'.odt\') {
                			name = name.substr(0, pos);
                		}
	                	getElementById(\'createfileform-new_file_name\').value = name + type;
                        getElementById(\'type_txt\').src = \'protected/modules/onlinedrives/resources/type/gray/txt.png\';
                        getElementById(\'type_docx\').src = \'protected/modules/onlinedrives/resources/type/gray/docx.png\';
                        getElementById(\'type_xlsx\').src = \'protected/modules/onlinedrives/resources/type/gray/xlsx.png\';
                        getElementById(\'type_odt\').src = \'protected/modules/onlinedrives/resources/type/gray/odt.png\';
                        this.src = \'protected/modules/onlinedrives/resources/type/pptx.png\';
	                }
            " />',
            'odt' => '<img
                id="type_odt"
                class="type_icon"
                src="protected/modules/onlinedrives/resources/type/gray/odt.png"
                alt="OpenDocument"
                title="OpenDocument"
                onclick="
                	type = \'.odt\';
                	name = getElementById(\'createfileform-new_file_name\').value;
                	pos = name.lastIndexOf(\'.\');
                	sub = name.substr(pos);
                	if (sub != type) {
                		if (sub == \'.txt\' || sub == \'.docx\' || sub == \'.xlsx\' || sub == \'.pptx\') {
                			name = name.substr(0, pos);
                		}
	                	getElementById(\'createfileform-new_file_name\').value = name + type;
                        getElementById(\'type_txt\').src = \'protected/modules/onlinedrives/resources/type/gray/txt.png\';
                        getElementById(\'type_docx\').src = \'protected/modules/onlinedrives/resources/type/gray/docx.png\';
                        getElementById(\'type_xlsx\').src = \'protected/modules/onlinedrives/resources/type/gray/xlsx.png\';
                        getElementById(\'type_pptx\').src = \'protected/modules/onlinedrives/resources/type/gray/pptx.png\';
                        this.src = \'protected/modules/onlinedrives/resources/type/odt.png\';
	                }
            " />',
        ], ['encode' => false]);
        ?>

	    <div class="upcr_label">Name</div>
        <?php echo $form->field($model, 'new_file_name'); ?>
    </div>

    <div id="create_btn" class="form-group shownone">
        <div class="col-lg-offset-1 col-lg-11">
            <?php
            if ($get_gd_folder_id != '') {
                echo Html::hiddenInput('gd_folder_id', $get_gd_folder_id);
                echo Html::hiddenInput('gd_folder_name', $get_gd_folder_name);
            }
            ?>

            <!-- Send button -->
            <?php echo Html::submitButton(Yii::t('OnlinedrivesModule.new', 'Send'), ['class' => 'btn btn-primary']); ?>
        </div>
    </div>
<?php
ActiveForm::end();


/**
 * Form for uploading files
 */
$model_u = new UploadFileForm();
$form_u = ActiveForm::begin([
    'id' => 'upload_file_form',
    'method' => 'post',
    'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
]);

    // Find out correct CSS form button
    if ($get_sciebo_path == '' && $get_gd_folder_id == '') {
        $css = 'upload_btn_div';
    }
    else {
        $css = 'upload_btn_div_inside_folder';
    }

    // Icon for uploading file
    echo '<div class="'.$css.'">' .
        $form_u->field($model_u, 'upload')->fileInput(['onchange' => 'this.form.submit()']) .
        '<label for="uploadfileform-upload">
            <span id="upload_file" class="upcr_btn btn-info btn-lg upcr_shaddow fa fa-cloud-upload fa-lg"
                title="' . Yii::t('OnlinedrivesModule.new', 'Create file') . '"
                onclick="'.
                    // Outcommented because of double opening of select windows
                    // $(\'#uploadfileform-upload\').trigger(\'click\');
                    'this.className = \'upcr_btn btn-info btn-lg upcr_shaddow fa fa-cloud-upload fa-lg upcr_btn_active\';
                    getElementById(\'create_folder\').className = \'upcr_btn btn-info btn-lg upcr_shaddow fa fa-folder-open fa-lg\';
                    getElementById(\'create_file\').className = \'upcr_btn btn-info btn-lg upcr_shaddow fa fa-file fa-lg\';
                    getElementById(\'create_folder_name\').className = \'shownone\';
                    getElementById(\'create_file_name\').className = \'shownone\';
                    getElementById(\'type_txt\').src = \'protected/modules/onlinedrives/resources/type/gray/txt.png\';
                    getElementById(\'type_docx\').src = \'protected/modules/onlinedrives/resources/type/gray/docx.png\';
                    getElementById(\'type_xlsx\').src = \'protected/modules/onlinedrives/resources/type/gray/xlsx.png\';
                    getElementById(\'type_pptx\').src = \'protected/modules/onlinedrives/resources/type/gray/pptx.png\';
                    getElementById(\'type_odt\').src = \'protected/modules/onlinedrives/resources/type/gray/odt.png\';

                    getElementById(\'create_btn\').className = \'form-group shownone\';
            ">
            </span>
        </label>
    </div>';
?>

    <div class="form-group shownone">
        <div class="col-lg-offset-1 col-lg-11">
            <?php
            if ($get_sciebo_path != '') {
                echo Html::hiddenInput('sciebo_path', $get_sciebo_path);
            }
            elseif ($get_gd_folder_id != '') {
                echo Html::hiddenInput('gd_folder_id', $get_gd_folder_id);
                echo Html::hiddenInput('gd_folder_name', $get_gd_folder_name);
            }

            // Selected cloud (uploading)
            if ($get_sciebo_path == '' && $get_gd_folder_id == '') {
                echo $form_u->field($model_u, 'selected_cloud_u');
            }
            elseif ($get_sciebo_path != '') {
                echo $form->field($model_u, 'selected_cloud_u')->textInput(['value' => 'sciebo']);
            }
            elseif ($get_gd_folder_id != '') {
                echo $form->field($model_u, 'selected_cloud_u')->textInput(['value' => 'gd']);
            }

            // Send button
            echo Html::submitButton('Send_u', ['class' => 'btn btn-primary']);
            ?>
        </div>
    </div>

<?php ActiveForm::end() ?>


</div>


<?php
/**
 * Get Google Drive files
 */

// Print the data for up to all files

if (1 == 1) { // Only for temporary undisplaying Google Drive folders/files

if ($get_gd_folder_id != '') {
    $optParams = array(
        // 'pageSize' => 10,
        'q' => 'parents="'.$get_gd_folder_id.'"',
        'fields' => 'nextPageToken, files(*)',
        'orderBy' => 'folder, name',
    );
}
else {
    $optParams = array(
        //'pageSize' => 10,
        'fields' => 'nextPageToken, files(*)',
        'orderBy' => 'folder, name',
    );
}

$gd_results = array();
$count_gd_files = 0;
if ($get_sciebo_path == '' && isset($gd_service) && $gd_service !== false) {
    $gd_results = $gd_service->files->listFiles($optParams);
    $count_gd_files = count($gd_results->getFiles());
} //TODO $gd_service XX YY

if ($count_gd_files != 0) {
    foreach ($gd_results->getFiles() as $file) {
    	// Read folder/file ID
    	$gd_file_id = $file->getId();

        // Check for database entry for Google Drive and this space
        $sql = $db->createCommand('SELECT id, app_password FROM onlinedrives_app_detail
            WHERE space_id = :space_id AND drive_name = :drive_name', [
            ':space_id' => $space_id,
            ':drive_name' => 'gd',
        ])->queryAll();

        foreach ($sql as $value) {
            $app_detail_id = $value['id'];
            $app_password = $value['app_password'];

            $sql = $db->createCommand('SELECT drive_key FROM onlinedrives_app_drive_path_detail
                WHERE drive_path = :gd_file_id AND onlinedrives_app_detail_id = :onlinedrives_app_detail_id', [
                ':gd_file_id' => $gd_file_id,
                ':onlinedrives_app_detail_id' => $app_detail_id,
            ])->queryAll();
            if (count($sql) == 1) {
                foreach ($sql as $value) {
                    $drive_key = $value['drive_key'];
                }

                // Mime type, type (folder/file)
                $mime_type = $file->getMimeType(); // Only Google Drive at the moment
                if (substr($mime_type, -6) == 'folder') {
                	$type = 'folder';
                }
                else {
            	   $type = 'file';
                }

                // Created time
                $temp = $file->getCreatedTime();
                $temp_d = substr($temp, 8, 2);
                $temp_mon = substr($temp, 5, 2);
                $temp_y = substr($temp, 0, 4);
                $temp_h = substr($temp, 11, 2);
                $temp_min = substr($temp, 14, 2);
                $temp_s = substr($temp, 17, 2);
                $created_time = mktime($temp_h, $temp_min, $temp_s, $temp_mon, $temp_d, $temp_y);
                $created_time += 7200; // 60m * 60m * 2h, European time zone

                // Modified time
                $temp = $file->getModifiedTime();
                $temp_d = substr($temp, 8, 2);
                $temp_mon = substr($temp, 5, 2);
                $temp_y = substr($temp, 0, 4);
                $temp_h = substr($temp, 11, 2);
                $temp_min = substr($temp, 14, 2);
                $temp_s = substr($temp, 17, 2);
                $modified_time = mktime($temp_h, $temp_min, $temp_s, $temp_mon, $temp_d, $temp_y);
                $modified_time += 7200; // 60m * 60m * 2h, European time zone

                // Folder list, file list
                if ($type == 'folder') {
                    $all_folders[$afo]['cloud'] = 'gd';
                    $all_folders[$afo]['cloud_name'] = 'Google Drive';
                    $all_folders[$afo]['id'] = $file->getId();                           // Only Google Drive at the moment
                    $all_folders[$afo]['path'] = "";
                    $all_folders[$afo]['name'] = $file->getName();
                    $all_folders[$afo]['mime_type'] = $mime_type;
                    $all_folders[$afo]['type'] = $type;
                    $all_folders[$afo]['created_time'] = $created_time;                  // TODO Only Google Drive at the moment!
                    $all_folders[$afo]['modified_time'] = $modified_time;
                    $all_folders[$afo]['icon_link'] = $file->getIconLink();              // Only Google Drive at the moment
                    $all_folders[$afo]['thumbnail_link'] = $file->getThumbnailLink();    // Only Google Drive at the moment
                    $all_folders[$afo]['web_content_link'] = $file->getWebContentLink(); // Only Google Drive at the moment
                    $all_folders[$afo]['web_view_link'] = $file->getWebViewLink();       // Only Google Drive at the moment
                    $all_folders[$afo]['download_link'] = '';
                    $all_folders[$afo]['parents'] = $file->getParents();                 // TODO Only Google Drive at the moment!
                    $all_folders[$afo]['fav'] = 0;
                    $all_folders[$afo]['file_owner'] = '';
                    $all_folders[$afo]['file_shared'] = array();
                    $all_folders[$afo]['file_comment'] = '';
                    $all_folders[$afo]['drive_key'] = '';
                    $afo++;
                }
                else {
                    $all_files[$afi]['cloud'] = 'gd';
                    $all_files[$afi]['cloud_name'] = 'Google Drive';
                    $all_files[$afi]['id'] = $file->getId();                           // Only Google Drive at the moment
                    $all_files[$afi]['path'] = '';
                    $all_files[$afi]['name'] = $file->getName();
                    $all_files[$afi]['mime_type'] = $mime_type;
                    $all_files[$afi]['type'] = $type;
                    $all_files[$afi]['created_time'] = $created_time;                  // TODO Only Google Drive at the moment!
                    $all_files[$afi]['modified_time'] = $modified_time;
                    $all_files[$afi]['icon_link'] = $file->getIconLink();              // Only Google Drive at the moment
                    $all_files[$afi]['thumbnail_link'] = $file->getThumbnailLink();    // Only Google Drive at the moment
                    $all_files[$afi]['web_content_link'] = $file->getWebContentLink(); // Only Google Drive at the moment
                    $all_files[$afi]['web_view_link'] = $file->getWebViewLink();       // Only Google Drive at the moment
                    $all_files[$afi]['download_link'] = '';
                    $all_files[$afi]['parents'] = $file->getParents();                 // TODO Only Google Drive at the moment!
                    $all_files[$afi]['fav'] = 0;
                    $all_files[$afi]['file_owner'] = '';
                    $all_files[$afi]['file_shared'] = array();
                    $all_files[$afi]['file_comment'] = '';
                    $all_files[$afi]['drive_key'] = '';
                    $afi++;
                }
                $all++;
            }
        }
    }
}

} // Only for temporary undisplaying Google Drive folders/files

/**
 * Check (3) end
 */
}


// Output success/error message
if ($success_msg != '') {
    echo '<div id="success_msg" class="infbox green">'.$success_msg.'</div>';
}
elseif ($error_msg != '') {
    echo '<div id="error_msg" class="infbox red">'.$error_msg.'</div>';
}


/**
 * Check (4) start
 */
if ($check == 1) {


/**
 * Output table
 */

$count_all_folders = count($all_folders);
$count_all_files = count($all_files);
$count_all = $count_all_folders + $count_all_files;

/*
if ($count_all == $all) { echo '$count_all and $all are both '.$all; }
else { echo '$count_all('.$count_all.') and $all('.$all.') are different'; }
*/

if ($count_all_folders == 0 && $count_all_files == 0) {
    echo '<div style="font-size: 20px;">'.Yii::t('OnlinedrivesModule.new', 'Folder is <b>empty.</b>').'</div>';
}
else {
    switch ($order_by) {
        case 'name_asc':
            usort($all_folders, 'cmp_name_asc');
            usort($all_files, 'cmp_name_asc');
            break;
        case 'name_desc':
            usort($all_folders, 'cmp_name_desc');
            usort($all_files, 'cmp_name_desc');
            break;
        case 'modified_time_asc':
            usort($all_folders, 'cmp_modified_time_asc');
            usort($all_files, 'cmp_modified_time_asc');
            break;
        case 'modified_time_desc':
            usort($all_folders, 'cmp_modified_time_desc');
            usort($all_files, 'cmp_modified_time_desc');
            break;
        case 'created_time_asc':
            usort($all_folders, 'cmp_created_time_asc');
            usort($all_files, 'cmp_created_time_asc');
            break;
        case 'created_time_desc':
            usort($all_folders, 'cmp_created_time_desc');
            usort($all_files, 'cmp_created_time_desc');
            break;
    }
}
?>

<table id="table" class="table table-responsive">
    <thead>
      <tr>
        <!-- 0 Type -->
        <th class="shownone"></th>
        <!-- 1 Favorite -->
        <th width="54"></th>
        <!-- 2 Name -->
        <th onclick="change_order_icon('name', 2, 'T');">
        	<span class="rel" style="cursor: pointer;">
        		Name
        		<span id="col_name" class="abs glyphicon glyphicon-chevron-up"></span>
        	</span>
        </th>
        <!-- 3 Last modified unix time -->
        <th class="shownone"></th>
        <!-- 4 Last modified readable time -->
        <th onclick="change_order_icon('modified', 4, 'N');">
        	<span class="rel" style="cursor: pointer;">
        		<?php echo Yii::t('OnlinedrivesModule.new', 'Last modified time'); ?>
        		<span id="col_modified"></span>
        	</span>
        </th>
        <!-- 5 Properties -->
        <th>
            <?php echo Yii::t('OnlinedrivesModule.new', 'Properties'); ?>
        </th>
        <!-- 6 Info, only for development -->
        <!-- <th></th> -->
        <!-- 7 Service icon -->
        <th onclick="change_order_icon('service', 7, 'T');">
            <span class="rel" style="cursor: pointer;">
                Service
                <span id="col_service"></span>
            </span>
        </th>
        <!-- 8 Open link / download link -->
        <th></th>
        <!-- 9 -->
        <th></th>
        <!--
        <th onclick="sortTable(1,'number')"><span style="cursor: pointer;">Size (B)</span></th>
        <th onclick="sortTable(2,'text')"><span style="cursor: pointer;">Owner</span></th>
        <th onclick="sortTable(3,'number')"><span style="cursor: pointer;">Shared</span></th>
        <th onclick="sortTable(4,'number')"><span style="cursor: pointer;">Comment</span></th>
        -->
      </tr>
    </thead>
    <tbody>
        <?php
        $no = 0;
        // Rework of folders array
        for ($i = 0; $i < $count_all_folders; $i++) {
            $cloud = $all_folders[$i]['cloud'];
            $cloud_name = $all_folders[$i]['cloud_name'];
            $id = $all_folders[$i]['id'];
            $path = $all_folders[$i]['path'];
            $name = $all_folders[$i]['name'];
            $mime_type = $all_folders[$i]['mime_type'];
            $type = $all_folders[$i]['type'];
            $created_time = $all_folders[$i]['created_time'];
            $modified_time = $all_folders[$i]['modified_time'];
            $icon_link = $all_folders[$i]['icon_link'];
            $thumbnail_link = $all_folders[$i]['thumbnail_link'];
            $web_content_link = $all_folders[$i]['web_content_link'];
            $web_view_link = $all_folders[$i]['web_view_link'];
            $parents = $all_folders[$i]['parents'];
            $fav = $all_folders[$i]['fav'];
            $file_owner = $all_folders[$i]['file_owner'];
            $file_shared = $all_folders[$i]['file_shared'];
            $file_comment = $all_folders[$i]['file_comment'];
            $drive_key = $all_folders[$i]['drive_key'];

            $created_time_txt = $created_time;
            $modified_time_txt = $modified_time;

            // Parents list
            $count_parents = 0;
            $parent_id_list = '';
            if ($cloud == 'gd') {
                if (is_array($parents)) {
                    $count_parents = sizeof($parents);
                    $parent_id_list = '';
                    for ($i2 = 0; $i2 < $count_parents; $i2++) {
                        $parent_id_list .= $parents[$i2];
                        if ($i2 + 1 < $count_parents) {
                            $parent_id_list .= '<br />';
                        }
                    }
                }
            }

            if ($cloud == 'sciebo' ||                        // Sciebo
                $cloud == 'gd' &&                            // Google Drive
                    ($parents[0] == '0AESKNHa25CPzUk9PVA' || // Root ID of Google Drive
                    $parents[0] == '' ||                     // Shared files of Google Drive
                    $get_gd_folder_id != '')                 // Folder ID of Google Drive
            )
            {
                $no++;

                // Modified time (folders)
                $diff = $now - $modified_time;
                if ($diff < 2) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'just now');
                }
                elseif ($diff < 60) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Sekunden', ['diff' => $diff]);
                }
                elseif ($diff < 60 * 60) {
                    $diff = floor($diff / 60);
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Minute', ['diff' => $diff]);
                }
                elseif ($diff < 60 * 60 * 24) {
                    $diff = floor($diff / (60 * 60));
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Stunde', ['diff' => $diff]);
                }
                elseif ($diff < 60 * 60 * 24 * 7) {
                    $diff = floor($diff / (60 * 60 * 24));
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Tag', ['diff' => $diff]);
                }
                elseif ($diff < 60 * 60 * 24 * 31) {
                    $diff = floor($diff / (60 * 60 * 24 * 7));
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Woche', ['diff' => $diff]);
                }
                else {
                    $diff = floor($diff / (60 * 60 * 24 * 31));
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Monat', ['diff' => $diff]);
                }

                // Exact modified time
                $modified_time_txt_exact = strftime('%d.%m.%Y, %H:%M:%S', $modified_time);

                // Created time
                if ($cloud == 'sciebo') {
                    $created_time_txt = 'Not known';
                }
                elseif ($cloud == 'gd') {
                    $created_time_txt = strftime('%d.%m.%Y, %H:%M:%S', $created_time);
                }

                // Info
                $info = 'ID: '.$id . "\n" .
                    'Mime-Type: '.$mime_type . "\n" .
                    'Nr.: '.$no . "\n" .
                    'Parents-Anzahl: '.$count_parents . "\n" .
                    'Parent-ID-Liste: '.$parent_id_list;

                // Time title
                $time_title = 'Modified time: '.$modified_time_txt_exact."\n".
                    'Creation time: '.$created_time_txt."\n";

                // Output all folders
                echo '<tr id="tr'.$no.'" style="border-top: 1px solid #ddd; color: #555;">
                    <td class="shownone">'.$type.'</td>
                    <td style="padding: 5px;">
                        <span class="glyphicon glyphicon-folder-close"></span>';
                        if ($fav <> 0) { echo ' <span class="glyphicon glyphicon-star fav_brown"></span>'; }
                    echo '</td>
                    <td style="padding: 5px;">';
                        if ($cloud == 'sciebo') {
                            $path = urlencode($path);
                            $url = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Findex&'.$guid.'&sciebo_path='.$path.'&dk='.$drive_key;
                            echo '<a href="'.$url.'">'.$name.'</a>';
                        }
                        elseif ($cloud == 'gd') {
                            $url = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Findex&'.$guid.'&gd_folder_id='.$id.'&gd_folder_name='.$name.'&dk='.$drive_key;
                            echo '<a href="'.$url.'">'.$name.'</a>';
                        }
                    echo '</td>
                    <td class="shownone">'.$modified_time.'</td>
                    <td style="padding: 5px;">
                        <span title="'.$time_title.'">'.$modified_time_txt.'</span>
                    </td>'.
                    // Output owner, shared, comments (folders)
                    '<td>';
                        if ($cloud == 'sciebo') {
                        echo '<div>
                            <div class="col-sm-1 float-left">
                                <div class="round round-sm hollow">
                                    <span title="'.$file_owner.'">';
                                        $words = explode(',', $file_owner);
                                        $result1 = $words[0][0];
                                        if (count($words) > 1) { $result2 = $words[1][1]; }
                                        else { $result2 = ''; }
                                        echo '<b>'.$result2.$result1.'</b>'.
                                    '</span>
                                </div>
                            </div>';
                            if (is_array($file_shared) && count($file_shared) > 0) {
                                echo '<div id="ex2" class="float-left">
                                    <span class="fa-stack fa-1x has-badge" data-count="yes">
                                        <i class="fa fa-circle fa-stack-2x"></i>
                                        <i class="fa fa-share fa-stack-1x fa-inverse"></i>
                                    </span>
                                </div>';
                            }
                            if ($file_comment > 0) {
                                echo '<div id="ex2" class="float-left">
                                    <span class="fa-stack fa-1x has-badge" data-count="'.$file_comment.'">
                                        <i class="fa fa-circle fa-stack-2x"></i>
                                        <i class="fa fa-comments fa-stack-1x fa-inverse" aria-hidden="true"></i>
                                    </span>
                                </div>';
                            }
                            echo '</div>';
                        }
                    echo '</td>'.
                    // Output info (folders), only for development
                    /*
                    '<td>
                        <img src="protected/modules/onlinedrives/resources/info.png" alt="" title="'.$info.'" />
                    </td>'.
                    */
                    // Output service icon (folders)
                    '<td>
                        <img src="protected/modules/onlinedrives/resources/'.$cloud.'20.png" alt="" title="'.$cloud_name.'" />
                    </td>'.
                    // Output open link (folders)
                    '<td>
                        <a href="'.$web_view_link.'" target="_blank">
                            <span class="glyphicon glyphicon-new-window" style="font-size: 20px;"></span>
                        </a>
                    </td>'.
                    // Output more options icon (folders)
                    '<td style="position: relative; padding: 5px;">';

                        /**
                         * More options menu (folders)
                         */
                        if ($cloud == 'sciebo' || $cloud == 'gd' && $parents[0] != '') {
                            echo '<a href="#" onclick="';
                                for ($i2 = 0; $i2 < $count_all; $i2++) {
                                    echo 'if (getElementById(\'more'.$i2.'\')) {
                                        getElementById(\'more'.$i2.'\').className = \'shownone more_menu\';
                                        getElementById(\'tr'.$i2.'\').className = \'\';
                                    }';
                                }
                                echo 'getElementById(\'more'.$no.'\').className = \'showblock more_menu\';
                                getElementById(\'tr'.$no.'\').className = \'bgyellow\';
                                return false;
                            ">
                                <span class="glyphicon glyphicon-option-horizontal" style="font-size: 25px;"></span>
                            </a>'.

                            // Wrapper container
                            '<div id="more'.$no.'" class="shownone more_menu">'.

                                // Cross icon (more options menu)
                                '<img src="protected/modules/onlinedrives/resources/cross.png" alt="X" title="'.Yii::t('OnlinedrivesModule.new', 'Close').'"
                                    style="position: absolute; top: 5px; right: 5px; width: 10px; height: 10px; cursor: pointer;"
                                    onclick="
                                        getElementById(\'more'.$no.'\').className = \'shownone more_menu\';
                                        getElementById(\'tr'.$no.'\').className = \'\';
                                        getElementById(\'delete'.$no.'\').className = \'shownone\';
                                " />'.

                                // Rename function
                                '<a href="" class="more_a" alt="'.Yii::t('OnlinedrivesModule.new', 'Rename').'" title="'.Yii::t('OnlinedrivesModule.new', 'Rename').'">
                                    <span class="glyphicon glyphicon-pencil" style="font-size: 25px;"></span>
                                    <span class="more_txt">'.Yii::t('OnlinedrivesModule.new', 'Rename').'</span>
                                </a>'.

                                // Move function
                                '<a href="" class="more_a" alt="'.Yii::t('OnlinedrivesModule.new', 'Move').'" title="'.Yii::t('OnlinedrivesModule.new', 'Move').'">
                                    <span class="glyphicon glyphicon-move" style="font-size: 25px;"></span>
                                    <span class="more_txt">'.Yii::t('OnlinedrivesModule.new', 'Move').'</span>
                                </a>'.

                                // Copy function
                                '<a href="" class="more_a" alt="'.Yii::t('OnlinedrivesModule.new', 'Copy').'" title="'.Yii::t('OnlinedrivesModule.new', 'Copy').'">
                                    <span class="glyphicon glyphicon-duplicate" style="font-size: 25px;"></span>
                                    <span class="more_txt">'.Yii::t('OnlinedrivesModule.new', 'Copy').'</span>
                                </a>'.

                                // Delete function
                                '<a href="#" class="more_a" alt="'.Yii::t('OnlinedrivesModule.new', 'Delete').'" title="'.Yii::t('OnlinedrivesModule.new', 'Delete').'"
                                    onclick="
                                        getElementById(\'delete'.$no.'\').classList.toggle(\'showblock\');

                                        return false;
                                ">
                                    <span class="glyphicon glyphicon-floppy-remove" style="font-size: 25px;"></span>
                                    <span class="more_txt">'.Yii::t('OnlinedrivesModule.new', 'Delete').'</span>
                                </a>
                                <div id="delete'.$no.'" class="shownone">';
                                    // Sciebo delete function
                                    if ($cloud == 'sciebo') {
                                        $model_sciebo_delete = new DeleteFileForm();
                                        $form2 = ActiveForm::begin([
                                            'id' => 'sciebo_delete_file',
                                            'method' => 'post',
                                            'options' => ['class' => 'form-horizontal'],
                                        ]);

                                        echo Html::ActiveHiddenInput($model_sciebo_delete, 'cloud', array('value' => $cloud));
                                        echo Html::ActiveHiddenInput($model_sciebo_delete, 'delete_file_id', array('value' => $name));
                                        echo Html::ActiveHiddenInput($model_sciebo_delete, 'dk', array('value' => $drive_key));

                                        echo '<div class="form-group">
                                            <div class="col-lg-offset-1 col-lg-11 more_del_confirm">';
                                                echo Html::submitButton(Yii::t('OnlinedrivesModule.new', 'Confirm'), [
                                                    'class' => 'btn-danger',
                                                ]);
                                            echo '</div>
                                        </div>';

                                        ActiveForm::end();
                                    }
                                    // Google Drive delete function
                                    elseif ($cloud == 'gd' && $parents[0] != '') {
                                        $model_gd_delete = new DeleteFileForm();
                                        $form2 = ActiveForm::begin([
                                            'id' => 'gd_delete_file',
                                            'method' => 'post',
                                            'options' => ['class' => 'form-horizontal'],
                                        ]);

                                        echo Html::ActiveHiddenInput($model_gd_delete, 'cloud', array('value' => $cloud));
                                        echo Html::ActiveHiddenInput($model_gd_delete, 'delete_file_id', array('value' => $id));

                                        echo '<div class="form-group">
                                            <div class="col-lg-offset-1 col-lg-11 more_del_confirm">';
                                                echo Html::submitButton(Yii::t('OnlinedrivesModule.new', 'Confirm'), [
                                                    'class' => 'btn-danger',
                                                ]);
                                            echo '</div>
                                        </div>';

                                        ActiveForm::end();
                                    }

                                echo '</div>
                            </div>';
                        }
                    echo '</td>
                </tr>';
            }
        }

        // Rework of files array
        for ($i = 0; $i < $count_all_files; $i++) {
            $cloud = $all_files[$i]['cloud'];
            $cloud_name = $all_files[$i]['cloud_name'];
            $id = $all_files[$i]['id'];
            $path = $all_files[$i]['path'];
            $name = $all_files[$i]['name'];
            $mime_type = $all_files[$i]['mime_type'];
            $type = $all_files[$i]['type'];
            $created_time = $all_files[$i]['created_time'];
            $modified_time = $all_files[$i]['modified_time'];
            $icon_link = $all_files[$i]['icon_link'];
            $thumbnail_link = $all_files[$i]['thumbnail_link'];
            $web_content_link = $all_files[$i]['web_content_link'];
            $web_view_link = $all_files[$i]['web_view_link'];
            $download_link = $all_files[$i]['download_link'];
            $parents = $all_files[$i]['parents'];
            $fav = $all_files[$i]['fav'];
            $file_owner = $all_files[$i]['file_owner'];
            $file_shared = $all_files[$i]['file_shared'];
            $file_comment = $all_files[$i]['file_comment'];
            $drive_key = $all_files[$i]['drive_key'];

            $created_time_txt = $created_time;
            $modified_time_txt = $modified_time;

            // Parents list
            $count_parents = 0;
            $parent_id_list = '';
            if ($cloud == 'gd') {
                if (is_array($parents)) {
                    $count_parents = sizeof($parents);
                    $parent_id_list = '';
                    for ($i2 = 0; $i2 < $count_parents; $i2++) {
                        $parent_id_list .= $parents[$i2];
                        if ($i2 + 1 < $count_parents) {
                            $parent_id_list .= '<br />';
                        }
                    }
                }
            }

            if ($cloud == 'sciebo' ||                        // Sciebo
                $cloud == 'gd' &&                            // Google Drive
                    ($parents[0] == '0AESKNHa25CPzUk9PVA' || // Root ID of Google Drive
                    $parents[0] == '' ||                     // Shared files of Google Drive
                    $get_gd_folder_id != '')                 // Folder ID of Google Drive
                )
            {
                $no++;

                // Modified time (files)
                $diff = $now - $modified_time;
                if ($diff < 2) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'just now');
                }
                elseif ($diff < 60) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Sekunde', ['diff' => $diff]);
                }
                elseif ($diff < 60 * 60) {
                    $diff = floor($diff / 60);
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Minute', ['diff' => $diff]);
                }
                elseif ($diff < 60 * 60 * 24) {
                    $diff = floor($diff / (60 * 60));
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Stunde', ['diff' => $diff]);
                }
                elseif ($diff < 60 * 60 * 24 * 7) {
                    $diff = floor($diff / (60 * 60 * 24));
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Tag', ['diff' => $diff]);
                }
                elseif ($diff < 60 * 60 * 24 * 31) {
                    $diff = floor($diff / (60 * 60 * 24 * 7));
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Woche', ['diff' => $diff]);
                }
                else {
                    $diff = floor($diff / (60 * 60 * 24 * 31));
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Monat', ['diff' => $diff]);
                }

                // Exact modified time
                $modified_time_txt_exact = strftime('%d.%m.%Y, %H:%M:%S', $modified_time);

                // Created time
                if ($cloud == 'sciebo') {
                    $created_time_txt = Yii::t('OnlinedrivesModule.new', 'Not known');
                }
                elseif ($cloud == 'gd') {
                    $created_time_txt = strftime('%d.%m.%Y, %H:%M:%S', $created_time);
                }

                // Info
                $info = 'ID: '.$id."\n".
                    'Mime-Type: '.$mime_type."\n".
                    'Nr.: '.$no."\n".
                    'Parents-Anzahl: '.$count_parents."\n".
                    'Parent-ID-Liste: '.$parent_id_list;

                // Time title
                $time_title = 'Modified time: '.$modified_time_txt_exact."\n".
                    'Creation time: '.$created_time_txt."\n";


                /**
                 * Icon
                 */
/*
                // Standard icon
                $img = '<span class="glyphicon glyphicon-file file-color"></span>';

                // Requested Google Drive icon
                $img = '<img src="'.$icon_link.'" alt="" title="" />';
*/
                // Read type
                if ($mime_type != '') {
                    if (strpos($mime_type, '.') == false) {
                        $pos = strrpos($mime_type, '/');
                        $mime_type_icon = substr($mime_type, $pos + 1);
                    }
                    else {
                        $pos = strrpos($mime_type, '.');
                        $mime_type_icon = substr($mime_type, $pos + 1);
                    }
                }
    	        else {
	                $pos = strrpos($name, '.');
    	            $mime_type_icon = substr($name, $pos + 1);
    	        }
                switch ($mime_type_icon) {
                    case 'txt':
                        $icon = 'txt'; break;
                    case 'plain':
                    case 'document':
                    case 'docx':
                    case 'doc':
                    case 'rtf':
                        $icon = 'docx'; break;
                    case 'spreadsheet':
                    case 'xlsx':
                    case 'xls':
                    case 'ods':
                        $icon = 'xlsx'; break;
                    case 'presentation':
                    case 'pptx':
                    case 'ppt':
                    case 'pps':
                    case 'odp':
                        $icon = 'pptx'; break;
                    case 'odt':
                        $icon = 'odt'; break;
                    case 'pdf':
                        $icon = 'pdf'; break;
                    case 'jpg':
                    case 'jpeg':
                    case 'gif':
                    case 'png':
                    case 'tif':
                    case 'tiff':
                        $icon = 'img'; break;
                    case 'mpeg':
                    case 'wav':
                    case 'mp3':
                        $icon = 'sound'; break;
                    case 'mp4':
                        $icon = 'vid'; break;
                    case 'zip':
                        $icon = ''; break;
                    case 'mx12':
                    case 'mx18':
                        $icon = ''; break;
                    default:
                        $icon = ''; break;
                } //echo $mime_type_icon;
                $img = '<img src="protected/modules/onlinedrives/resources/type/'.$icon.'.png" alt="'.'" title="'.'" />';


                // Output all files
                echo '<tr id="tr'.$no.'" style="border-top: 1px solid #ddd; color: #555;">
                    <td class="shownone">'.$type.'</td>
                    <td style="padding: 5px;">
                        '.$img;
                        if ($fav <> 0) { echo ' <span class="glyphicon glyphicon-star fav_brown"></span>'; }
                    echo '</td>
                    <td style="padding: 5px;">
                        <a href="'.$web_view_link.'" target="_blank">'.$name.'</a>
                    </td>
                    <td class="shownone">'.$modified_time.'</td>
                    <td style="padding: 5px;">
                        <span title="'.$time_title.'">'.$modified_time_txt.'</span>
                    </td>'.
                    // Output owner, shared, comments (files)
                    '<td>';
                        if ($cloud == 'sciebo') {
                        echo '<div>
                            <div class="col-sm-1 float-left">
                                <div class="round round-sm hollow">
                                    <span title="'.$file_owner.'">';
                                        $words = explode(',', $file_owner);
                                        $result1 = $words[0][0];
                                        if (count($words) > 1) { $result2 = $words[1][1]; }
                                        else { $result2 = ''; }
                                        echo '<b>'.$result2.$result1.'</b>'.
                                    '</span>
                                </div>
                            </div>';
                            if (is_array($file_shared) && count($file_shared) > 0) {
                                echo '<div id="ex2" class="float-left">
                                    <span class="fa-stack fa-1x has-badge" data-count="yes">
                                        <i class="fa fa-circle fa-stack-2x"></i>
                                        <i class="fa fa-share fa-stack-1x fa-inverse"></i>
                                    </span>
                                </div>';
                            }
                            if ($file_comment > 0) {
                                echo '<div id="ex2" class="float-left">
                                    <span class="fa-stack fa-1x has-badge" data-count="'.$file_comment.'">
                                        <i class="fa fa-circle fa-stack-2x"></i>
                                        <i class="fa fa-comments fa-stack-1x fa-inverse" aria-hidden="true"></i>
                                    </span>
                                </div>';
                            }
                        echo '</div>';
                        }
                    echo '</td>'.
                    // Output info (files), only for development
                    /*
                    '<td>
                        <img src="protected/modules/onlinedrives/resources/info.png" alt="" title="'.$info.'" />
                    </td>'.
                    */
                    // Output service icon (files)
                    '<td>
                        <img src="protected/modules/onlinedrives/resources/'.$cloud.'20.png" alt="" title="'.$cloud_name.'" />
                    </td>'.
                    // Output download link (only in files)
                    '<td>';
                        if ($download_link != '') {
                            echo '<a href="'.$download_link.'" target="_blank">
                                <span class="glyphicon glyphicon-download-alt" style="font-size: 20px;"></span>
                            </a>';
                        }
                    echo '</td>'.
                    // Output more options icon (files)
                    '<td style="position: relative; padding: 5px;">';

                        /**
                         * More options menu (files)
                         */
                        if ($cloud == 'sciebo' || $cloud == 'gd' && $parents[0] != '') {
                            echo '<a href="#" onclick="';
                                for ($i2 = 0; $i2 < $count_all; $i2++) {
                                    echo 'if (getElementById(\'more'.$i2.'\')) {
                                        getElementById(\'more'.$i2.'\').className = \'shownone more_menu\';
                                        getElementById(\'tr'.$i2.'\').className = \'\';
                                    }';
                                }
                                echo 'getElementById(\'more'.$no.'\').className = \'showblock more_menu\';
                                getElementById(\'tr'.$no.'\').className = \'bgyellow\';
                                return false;
                            ">
                                <span class="glyphicon glyphicon-option-horizontal" style="font-size: 25px;"></span>
                            </a>'.

                            // Wrapper container
                            '<div id="more'.$no.'" class="shownone more_menu">'.

                                // Cross icon (more options menu)
                                '<img src="protected/modules/onlinedrives/resources/cross.png" alt="X" title="'.Yii::t('OnlinedrivesModule.new', 'Close').'"
                                    style="position: absolute; top: 5px; right: 5px; width: 10px; height: 10px; cursor: pointer;"
                                    onclick="
                                        getElementById(\'more'.$no.'\').className = \'shownone more_menu\';
                                        getElementById(\'tr'.$no.'\').className = \'\';
                                        getElementById(\'delete'.$no.'\').className = \'shownone\';
                                " />'.

                                // Rename function
                                '<a href="" class="more_a" alt="'.Yii::t('OnlinedrivesModule.new', 'Rename').'" title="'.Yii::t('OnlinedrivesModule.new', 'Rename').'">
                                    <span class="glyphicon glyphicon-pencil" style="font-size: 25px;"></span>
                                    <span class="more_txt">'.Yii::t('OnlinedrivesModule.new', 'Rename').'</span>
                                </a>'.

                                // Move function
                                '<a href="" class="more_a" alt="'.Yii::t('OnlinedrivesModule.new', 'Move').'" title="'.Yii::t('OnlinedrivesModule.new', 'Move').'">
                                    <span class="glyphicon glyphicon-move" style="font-size: 25px;"></span>
                                    <span class="more_txt">'.Yii::t('OnlinedrivesModule.new', 'Move').'</span>
                                </a>'.

                                // Copy function
                                '<a href="" class="more_a" alt="'.Yii::t('OnlinedrivesModule.new', 'Copy').'" title="'.Yii::t('OnlinedrivesModule.new', 'Copy').'">
                                    <span class="glyphicon glyphicon-duplicate" style="font-size: 25px;"></span>
                                    <span class="more_txt">'.Yii::t('OnlinedrivesModule.new', 'Copy').'</span>
                                </a>'.

                                // Delete function
                                '<a href="#" class="more_a" alt="'.Yii::t('OnlinedrivesModule.new', 'Delete').'" title="'.Yii::t('OnlinedrivesModule.new', 'Delete').'"
                                    onclick="
                                        getElementById(\'delete'.$no.'\').classList.toggle(\'showblock\');
                                        return false;
                                    "
                                >
                                    <span class="glyphicon glyphicon-floppy-remove" style="font-size: 25px;"></span>
                                    <span class="more_txt">'.Yii::t('OnlinedrivesModule.new', 'Delete').'</span>
                                </a>
                                <div id="delete'.$no.'" class="shownone">';
                                    // Sciebo delete function
                                    if ($cloud == 'sciebo') {
                                        $model_sciebo_delete = new DeleteFileForm();
                                        $form2 = ActiveForm::begin([
                                            'id' => 'gd_delete_file',
                                            'method' => 'post',
                                            'options' => ['class' => 'form-horizontal'],
                                        ]);
                                            echo Html::ActiveHiddenInput($model_sciebo_delete, 'cloud', array('value' => $cloud));
                                            echo Html::ActiveHiddenInput($model_sciebo_delete, 'delete_file_id', array('value' => $name));

                                            echo '<div class="form-group">
                                                <div class="col-lg-offset-1 col-lg-11 more_del_confirm">';
                                                    echo Html::submitButton(Yii::t('OnlinedrivesModule.new', 'Confirm'), ['class' => 'btn-danger']);
                                                echo '</div>
                                            </div>';

                                        ActiveForm::end();
                                    }
                                    // Google Drive delete function
                                    elseif ($cloud == 'gd' && $parents[0] != '') {
                                        $model_gd_delete = new DeleteFileForm();
                                        $form2 = ActiveForm::begin([
                                            'id' => 'gd_delete_file',
                                            'method' => 'post',
                                            'options' => ['class' => 'form-horizontal'],
                                        ]);
                                            echo Html::ActiveHiddenInput($model_gd_delete, 'cloud', array('value' => $cloud));
                                            echo Html::ActiveHiddenInput($model_gd_delete, 'delete_file_id', array('value' => $id));

                                            echo '<div class="form-group">
                                                <div class="col-lg-offset-1 col-lg-11 more_del_confirm">';
                                                    echo Html::submitButton(Yii::t('OnlinedrivesModule.new', 'Confirm'), ['class' => 'btn-danger']);
                                                echo '</div>
                                            </div>';

                                        ActiveForm::end();
                                    }

                                echo '</div>
                            </div>';
                        }
                    echo '</td>
                </tr>';
            }
        }
    ?>

    </tbody>
    </table>

<?php
/**
 * Check (4) end
 */
}


/**
 * Sciebo and Google Drive guide
 */
if (1 == 1) {
    $guide_h = '<span style="font-size: 20px; font-weight: bold;">Access configuration guide</span>';

    $sciebo_guide_link = '<a href="#sciebo_guide">How to connect with your Sciebo account</a>';
    $gd_guide_link = '<a href="#gd_guide">How to connect with your Google Drive account</a>';

    $sciebo_guide_a = '<a name="sciebo_guide"></a>';
    $gd_guide_a = '<a name="gd_guide"></a>';

	$sciebo_guide_h = '<h1><b>How to connect with your Sciebo account</b></h1>';
	$gd_guide_h = '<h1><b>How to connect with your Google Drive account</b></h1>';

	$sciebo_guide_txt1 = 'Go in Sciebo, click on your name, click on "Settings" / "Einstellungen" (orange circle):';
    $sciebo_guide_txt2 = 'Click on "Security" / "Sicherheit" (orange box):';
    $sciebo_guide_txt3 = 'Scroll down to "App passwords / tokens" / "App-Passwörter / Token" (orange circle). Here you can insert an app name. Please confirm after that:';
    $sciebo_guide_txt4 = 'Now new access data will be created and displayed. Copy them just now in the form above (which you\'ll see if you click on the burger menu in this module) because after refreshing the Sciebo page they won\t be longer visible:';
    $sciebo_guide_txt5 = 'Here you see the opened burger menu with fulfilled Sciebo login form:';
    $sciebo_guide_txt6 = 'After that you have a new box on your module homepage which makes it possible to share an existing folder or file of your Sciebo account with all the members in this space. Click on "Add":';
    $sciebo_guide_txt7 = 'You can click on "Add". Then you have the possibility to add every folder and file from your cloud storage you want. After that please click on "Share":';
    $sciebo_guide_txt8 = 'For example if there were selected all the folders and files in the foreign screenshot, then you would see the following list. This list will see all members of this space:';

    $count_guide_sciebo = 8;

    $gd_guide_txt1 = 'Please go on https://console.developers.google.com and log in with your Google account access data.';
    $gd_guide_txt2 = 'This could be your view after login:';
    $gd_guide_txt3 = 'Click on "Credentials" / "Anmeldedaten" on the left sight (3rd point).';
    $gd_guide_txt4 = 'Click on "" / "Anmeldedaten erstellen". Then click on "OAuth-Client-ID:';
    $gd_guide_txt5 = 'In the next view click on "" / "Webanwendungen" and choose a name of your OAuth-Client-ID. (This is not the shown name of your application.)';
    $gd_guide_txt6 = '';
    $gd_guide_txt7 = '';
    $gd_guide_txt8 = '';
    $gd_guide_txt9 = '';
    $gd_guide_txt10 = '';
    $gd_guide_txt11 = '';
    $gd_guide_txt12 = '';
    $gd_guide_txt13 = '';
    $gd_guide_txt14 = '';

    $count_guide_gd = 5;

    // Output box opening
    echo '<div class="box">'.

    // Outout guide heading
    '<p>'.
        $guide_h;

        $temp_class = '';
        if ($check == 1) {
            $temp_class = ' class="shownone"';
            echo '<span class="glyphicon glyphicon-chevron-down" style="margin-left: 10px; font-size: 15px; cursor: pointer;"
                onclick="
                    getElementById(\'guide\').classList.toggle(\'showblock\');

                    if (this.className == \'glyphicon glyphicon-chevron-down\') {
                        this.className = \'glyphicon glyphicon-chevron-up\';
                    }
                    else {
                        this.className = \'glyphicon glyphicon-chevron-down\';
                    }
            "></span>';
        }
    echo '</p>'.

    // Output hidden-able wrapper opening
    '<div id="guide"'.$temp_class.'>'.

    // Output Sciebo anchor
    $sciebo_guide_a.

    // Output guide links to anchors
    '<p><ul>'.
        '<li>'.$sciebo_guide_link.'</li>'.
        '<li>'.$gd_guide_link.'</li>'.
    '</ul></p><br />';

    // Output Sciebo guide
    echo
    '<p>'.$sciebo_guide_h.'</p><br />';
    for ($i = 1; $i <= $count_guide_sciebo; $i++) {
        $txt = 'sciebo_guide_txt'.$i;
        $pic = '<img src="protected/modules/onlinedrives/resources/guide/sciebo/'.$i.'.png" />';
    	echo '<p>Step '.$i.': ' . $$txt . '<p>'.
        '<p>' . $pic . '<p><br />';
    }

    echo '<br />'.

    // Output Google Drive anchor
    $gd_guide_a.

    // Output Google Drive output
	'<p>'.$gd_guide_h.'</p><br />';

    for ($i = 1; $i <= $count_guide_gd; $i++) {
        $txt = 'gd_guide_txt'.$i;
        $pic = '<img src="protected/modules/onlinedrives/resources/guide/gd/'.$i.'.png" />';
        echo '<p>Step '.$i.': ' . $$txt . '<p>'.
        '<p>' . $pic . '<p><br />';
    }

    // Output hidden-able wrapper ending
    echo '</div>'.

    // Output box ending
    '</div>';
}
?>

        </div>
    </div>

<?php
echo Html::endForm();
echo FileListContextMenu::widget(['folder' => $folder]);
?>