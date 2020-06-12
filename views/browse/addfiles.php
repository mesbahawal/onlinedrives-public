<?php
use humhub\modules\onlinedrives\models\forms\AddFilesForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\ErrorHandler;
use yii\web\HttpException;
use yii\widgets\ActiveForm;

use Sabre\DAV;

include_once __DIR__ . '/../../models/dbconnect.php';
include __DIR__ . '/../../vendor/autoload.php';

$db = dbconnect();

try {
    $DB_open = $db->open();
}
catch (Exception $exception) {
    die("Database Connection Error.");
}

// General vars
$now = time();
$home_url = Url::base(true);
$folder_is_empty = 0;

$bundle = \humhub\modules\onlinedrives\assets\Assets::register($this);

// Declare vars
$app_user_id = '';
$get_sciebo_path = '';
$if_shared = '';
$app_password = '';
$drive_path = '';
$get_gd_folder_id = '';
$get_gd_folder_name = '';


/**
 * Get params
 */

// Read username
$username = '';
if (isset(Yii::$app->user->identity->username)) {
    $username = Yii::$app->user->identity->username;
}

// Space ID
$space_id = '';
if (!empty($_GET['cguid'])) {
    $space_id = $_GET['cguid'];
}
if (!empty($_GET['app_detail_id'])) {
    $app_detail_id =  $_GET['app_detail_id'];
}

if (!empty($_GET['cguid'])) {
    $guid = 'cguid=' . $_GET['cguid']; // Get param, important for paths
}

// Sciebo params
if (!empty($_GET['sciebo_path'])) {
    $get_sciebo_path = $_GET['sciebo_path'];
}
elseif (!empty($_POST['sciebo_path'])) {
    $get_sciebo_path = $_POST['sciebo_path'];
}

// Google Drive params
if (!empty($_GET['gd_folder_id'])) {
    $get_gd_folder_id = $_GET['gd_folder_id'];
}
if (!empty($_GET['gd_folder_name'])) {
    $get_gd_folder_name = $_GET['gd_folder_name'];
}

// Rework
$get_sciebo_path = str_replace(' ', '%20', $get_sciebo_path);

$all_folders = array();
$all_files = array();
$all = 0; // Counter for all folders and files
$afo = 0; // All folders counter
$afi = 0; // All files counter

// Success and error message
if (isset($_REQUEST['success_msg'])) {
    $success_msg = $_REQUEST['success_msg'];
}
else {
    $success_msg = '';
}
if (isset($_REQUEST['error_msg'])) {
    $error_msg = $_REQUEST['error_msg'];
}
else {
    $error_msg = '';
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



function getGoogleClient($db, $space_id, $home_url, $guid, $loginuser) {
    $now = time();

    $logged_username =  $loginuser;
    $client = false ;
    // Check for database entries for Google Drive and this space
    $sql = $db->createCommand('SELECT * FROM onlinedrives_app_detail
        WHERE space_id = :space_id AND drive_name = :drive_name AND if_shared NOT IN (\'D\') AND user_id = :user_id', [
        ':space_id' => $space_id,
        ':drive_name' => 'gd',
        ':user_id' => $logged_username,
    ])->queryAll();

    if (count($sql) > 0) {
        foreach ($sql as $value) {
            $app_password = $value['app_password'];
            $path_to_json = 'protected/modules/onlinedrives/upload_dir/google_client/'.$app_password.'.json';

            if (file_exists($path_to_json)) {
                $client = new Google_Client();
                $client->setApplicationName('ResearchHub');
                $client->addScope(Google_Service_Drive::DRIVE);
                $client->setAuthConfig($path_to_json);
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
                            // Disable tupels which has the if_shared value 'T'
                            $sql = $db->createCommand('UPDATE onlinedrives_app_detail SET if_shared = \'D\'
                                        WHERE space_id = :space_id
                                        AND user_id = :user_id
                                        AND drive_name = :drive_name
                                        AND create_date < :now_minus_some_seconds
                                        AND if_shared IN (\'T\')', [
                                ':space_id' => $space_id,
                                ':user_id' => $logged_username,
                                ':drive_name' => 'gd',
                                ':now_minus_some_seconds' => $now - 3,
                            ])->execute();

                            if (file_exists($path_to_json)) {
                                $content = file_get_contents($path_to_json);
                                if (strpos($content, 'research-hub.social') !== false) {
                                    $authUrl = $client->createAuthUrl();
                                    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL)) or die();
                                }
                            }
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

                            if (file_put_contents($tokenPath, json_encode($client->getAccessToken()))) {
                                $sql = $db->createCommand('UPDATE onlinedrives_app_detail SET if_shared = \'N\'
                                            WHERE app_password = :app_password', [
                                    ':app_password' => $app_password,
                                ])->execute();
                            }
                            else {
                                return false;
                            }
                        }
                    }
                }
            }
            else {
                // Disable tupels which has the if_shared value 'T'
                $sql = $db->createCommand('UPDATE onlinedrives_app_detail SET if_shared = \'D\'
                    WHERE space_id = :space_id
                    AND user_id = :user_id
                    AND drive_name = :drive_name
                    AND create_date < :now_minus_some_seconds
                    AND if_shared IN (\'T\')', [
                    ':space_id' => $space_id,
                    ':user_id' => $logged_username,
                    ':drive_name' => 'gd',
                    ':now_minus_some_seconds' => $now - 3,
                ])->execute();
            }
        }
        return $client;
    }
    else {
        return false;
    }
}


// Get information of app user detail
if (!empty($_GET['app_detail_id'])) {
    $sql = $db->createCommand('SELECT * FROM onlinedrives_app_detail WHERE id = :id', [
        ':id' => $app_detail_id,
    ])->queryAll();
    foreach ($sql as $value) {
        $app_user_id = $value['app_user_id'];
        $app_password = $value['app_password'];
        $cloud = $value['drive_name'];
        $if_shared = $value['if_shared'];
        $user_id = $value['user_id'];
        $app_detail_id = $value['id'];
    }
}


/**
 * Google Drive client
 */
$session = Yii::$app->session;

// Check for database entries for Google Drive and this space

$sql = $db->createCommand('SELECT * FROM onlinedrives_app_detail
        WHERE space_id = :space_id AND drive_name = :drive_name AND if_shared NOT IN (\'D\') AND user_id = :user_id', [
    ':space_id' => $space_id,
    ':drive_name' => 'gd',
    ':user_id' => $username,
])->queryAll();

if (count($sql) > 0) {
// Get the API client and construct the service object
    $gd_client = getGoogleClient($db, $space_id, $home_url, $guid, $username);
    if ($gd_client !== false) {
        $gd_service = new Google_Service_Drive($gd_client);

        // Get root ID
        // https://stackoverflow.com/questions/36763941/how-can-i-list-files-dirs-in-root-directory-with-google-drive-api-v3
        $gd_root_id = $gd_service->files->get('root')->getId();
    }
}else{
    $gd_client = false;
}


/**
 * Load Google Drive content
 */
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
}


echo Html::beginForm(null, null, ['data-target' => '#globalModal', 'id' => 'onlinedrives-form']);
?>

<div id="onlinedrives-container" class="panel panel-default onlinedrives-content main_div_container">

    <div class="panel-body">

        <!-- Breadcrumb navigation -->
        <div style="border: 1px solid #f0f0f0; border-radius: 10px; padding: 10px; background-color: #f5f5f5;">
            <?php
            $src = $bundle->baseUrl . '/images/sciebo20.png';
            $ref = 'https://uni-siegen.sciebo.de/login';
            $tooltip_sciebo_redirection = Yii::t('OnlinedrivesModule.new','Redirect to Sciebo web portal.');
            echo '
            <!-- Tooltip -->
            <span style="margin-top: 0px; display:inline-block;" class="tt" data-toggle="tooltip" data-placement="top" data-original-title="'.$tooltip_sciebo_redirection.'">
                <a href="'.$ref.'" target="_blank" data-target="globalModal">
                    <img src="'.$src.'" style="position: relative; top: 0px;" />
                </a>
                <span class="glyphicon glyphicon-menu-right" style="margin-top: 5px;"></span>
            </span>';

            $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2faddfiles&'.$guid.'&app_detail_id='.$app_detail_id;
            echo ' <a href="'.$ref.'">'.$app_user_id.' <span class="glyphicon glyphicon-home" ></span></a>';

            // Output Sciebo navigation
            if ($get_sciebo_path != '') {
                // Output Sciebo icon in navigation


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
                    $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Faddfiles&'.$guid.'&app_detail_id='.$app_detail_id.'&sciebo_path=' . urlencode($path);
                    $navi .= ' <span class="glyphicon glyphicon-menu-right"></span> <a href="'.$ref.'">'.$name.'</a>';
                } while ($temp != '');

                // Output rest of Sciebo navigation
                echo $navi;
            }
            // Output Google Drive navigation
            elseif ($get_gd_folder_id != '') {
                // Build Google Drive icon for navigation
                $ref = 'https://accounts.google.com/ServiceLogin';
                $src = $bundle->baseUrl .'/images/gd20.png';

                // Output Google Drive icon in navigation with tooltip
                $tooltip_gd_redirection = Yii::t('OnlinedrivesModule.new','Redirect to Google Drive web portal.');
                echo '
                <!-- Tooltip -->
                <span style="margin-top: 0px; display:inline-block;" class="tt" data-toggle="tooltip" data-placement="top" data-original-title="'.$tooltip_gd_redirection.'">
                    <a href="'.$ref.'" target="_blank" data-target="globalModal">
                        <img src="'.$src.'" style="position: relative; top: 0px;" />
                    </a>
                    <span class="glyphicon glyphicon-menu-right" style="margin-top: 5px;"></span>
                </span>';



                // Build rest of Googel Drive navigation
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
                    $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Faddfiles&'.$guid.'&app_detail_id='.$app_detail_id.'&gd_folder_id='.$id.'&gd_folder_name='.$name;
                    $navi = ' / <a href="'.$ref.'">'.$name.'</a>'.$navi;

                    // Change search name for next loop
                    $check_id = $parents[0]; // Change parent folder ID to check
                    if ($check_id != $gd_root_id) { // Means root
                        $file = $gd_service->files->get($check_id);
                        $check_name = $file->getName(); // Change folder name to check
                    }
                } while ($check_id != $gd_root_id); // Means root

                // Output rest of GD navigation
                echo $navi;
            }
            ?>
        </div>

<?php
if ($app_user_id <> '') {
    // Set Sciebo path to replace with user ID
    $sciebo_path_to_replace = '/remote.php/dav/files/'.$app_user_id.'/';

    if (!empty($get_sciebo_path)) {
        $drive_path = $get_sciebo_path;
    }

    if ($drive_path != '' || $drive_path != '/') {
        $check = 1;

        if ($drive_path == '/') {
            $drive_path = '';
        }

        // Get the API client and construct the service object
        $sciebo_client = getScieboClient($app_user_id, $app_password);
    }

    $sciebo_content = getScieboFiles($sciebo_client, $app_user_id, $drive_path);

    if (isset($sciebo_content)) {
        $count_sciebo_files = count($sciebo_content);
    }
    else {
        $count_sciebo_files = 0;
    }

    // Check if folder is empty
    if ($count_sciebo_files == 0 && $count_gd_files == 0) {
        $folder_is_empty = 1;
        echo '<div style="margin-top: 25px; text-align: center; font-size: 20px;">' .
            Yii::t('OnlinedrivesModule.new', 'Folder is <b>empty.</b>') .
        '</div>';
    }
    // Rework Sciebo content
    elseif ($count_sciebo_files > 0) {
        $keys = array_keys($sciebo_content);
        foreach ($keys as $values) {
            /*
            -if root dir is selected to share, then we have to put '/' in the table
            -if sub-folder is selected, then we have to put 'subfolder/' in the table, no '/' in the beginning
            -for sharing files follow the same rule of subfolder
            */
            // $drive_path = '';
            $base_dir = '/remote.php/dav/files/'.$app_user_id.'/'.$drive_path; // Base directory (landing directory of shared folder)

            if ($values == $base_dir || (!empty($get_sciebo_path) && $values != $base_dir) || $drive_path == '') {
                // ID
                $id = $sciebo_content[$values]['{http://owncloud.org/ns}fileid'];

                // Path
                $path = str_replace($sciebo_path_to_replace, '', $values);

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
                    $modified_time += 7200; // European time zone (60s * 60m * 2h)

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

                        $all_folders[$afo]['parents'] = '';                   // TODO Sciebo hasn't?
                        $all_folders[$afo]['fav'] = $fav;
                        $all_folders[$afo]['file_owner'] = $file_owner;
                        $all_folders[$afo]['file_shared'] = $file_shared;
                        $all_folders[$afo]['file_comment'] = $file_comment;

                        $afo++;
                    }
                    // File list
                    else {
                        $all_files[$afi]['cloud'] = 'sciebo';
                        $all_files[$afi]['cloud_name'] = 'Sciebo';
                        $all_files[$afi]['id'] = $id;
                        $all_files[$afi]['path'] = $path;
                        $all_files[$afi]['name'] = $sciebo_file_name;
                        $all_files[$afi]['mime_type'] = $mime_type;
                        $all_files[$afi]['type'] = $type;
                        $all_files[$afi]['created_time'] = '';              // TODO Sciebo hasn't? (creationdate seems not to work.)
                        $all_files[$afi]['modified_time'] = $modified_time;
                        $all_files[$afi]['icon_link'] = '';                 // Sciebo hasn't?
                        $all_files[$afi]['thumbnail_link'] = '';            // Sciebo hasn't?
                        $all_files[$afi]['web_content_link'] = '';          // Sciebo hasn't?
                        $all_files[$afi]['web_view_link'] = $open_link;

                        $all_files[$afi]['parents'] = '';                   // TODO Sciebo hasn't?
                        $all_files[$afi]['fav'] = $fav;
                        $all_files[$afi]['file_owner'] = $file_owner;
                        $all_files[$afi]['file_shared'] = $file_shared;
                        $all_files[$afi]['file_comment'] = $file_comment;

                        $afi++;
                    }
                    $all++;
                }
            }
        }
    }
    // Rework Google Drive content
    elseif ($count_gd_files > 0) {
        foreach ($gd_results->getFiles() as $file) {
            // Read folder/file ID
            $gd_file_id = $file->getId();

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

            $parents = $file->getParents();
            if ($parents[0] == $gd_root_id || // Root ID of Google Drive
                $parents[0] == '' ||          // Shared files of Google Drive
                $get_gd_folder_id != ''       // Folder ID of Google Drive
            ) {
                // Folder list, file list
                if ($type == 'folder') {
                    $all_folders[$afo]['cloud'] = 'gd';
                    $all_folders[$afo]['cloud_name'] = 'Google Drive';
                    $all_folders[$afo]['id'] = $file->getId();                           // Only Google Drive at the moment
                    $all_folders[$afo]['path'] = '';
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

    if ($check == 1) {
        $count_all_folders = count($all_folders);
        $count_all_files = count($all_files);
        $count_all = $count_all_folders + $count_all_files;
    }

    $model_addfiles = new AddFilesForm();
    $form_addfiles = ActiveForm::begin([
        'id' => 'addfiles_form',
        'method' => 'post',
        'options' => ['class' => 'form-horizontal'],
    ]);

    // If folder is not empty
    if ($folder_is_empty == 0) {
    ?>

    <table id="table" class="table table-responsive">
        <thead>
            <tr>
                <td>
                </td>

                <td style="padding: 10px;" colspan="1">
                    Name
                </td>

                <td>

                        <?php echo Yii::t('OnlinedrivesModule.new', 'User permissions'); ?>

                </td>

                <td>
                    <div id="create_btn_login" class="form-group">
                        <div class="col-lg-offset-1 col-lg-11">
                            <?php echo ''; ?>
                        </div>
                    </div>
                </td>
            </tr>
        </thead>

        <tr>
            <td colspan="4">
                <label>
                    <input type="checkbox"
                        onchange="
                            var checked = this.checked;
                            <?php
                            for ($i = 0; $i < $count_all_folders; $i++) {
                            ?>
                                if (checked == true) {
                                    <?php
                                    echo 'document.getElementsByName(\'AddFilesForm[drive_path]['.$i.'][]\')[0].checked = true;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[0].checked = true;';
                                    ?>
                                }
                                else {
                                    <?php
                                    echo 'document.getElementsByName(\'AddFilesForm[drive_path]['.$i.'][]\')[0].checked = false;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[0].checked = false;';
                                    ?>
                                }
                            <?php
                            }

                            for ($i = 0; $i < $count_all_files; $i++) {
                                $checkbox_index = $i + $count_all_folders;
                                ?>
                                if (checked == true) {
                                    <?php
                                    echo 'document.getElementsByName(\'AddFilesForm[drive_path]['.$checkbox_index.'][]\')[0].checked = true;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked = true;';
                                    ?>
                                }
                                else {
                                    <?php                                    
                                    echo 'document.getElementsByName(\'AddFilesForm[drive_path]['.$checkbox_index.'][]\')[0].checked = false;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked = false;';
                                    ?>
                                }
                            <?php
                            }
                            ?>
                    " />
                    <?php echo Yii::t('OnlinedrivesModule.new', 'Select all'); ?>
                </label>
            </td>
        </tr>

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

            if ($cloud == 'sciebo' ||          // Sciebo
                $cloud == 'gd' &&              // Google Drive
                ($parents[0] == $gd_root_id || // Root ID of Google Drive
                    $parents[0] == '' ||       // Shared files of Google Drive
                    $get_gd_folder_id != '')   // Folder ID of Google Drive
            ) {
                $no++;

                // Modified time (folders)
                $diff = $now - $modified_time;
                if ($diff < 2) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'just now');
                }
                elseif ($diff < 60) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{%diff} seconds ago', ['diff' => $diff]);
                }
                elseif ($diff < 3600) { // 60s * 60m
                    $diff = floor($diff / 60);
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 }minute other{# minutes}} ago', ['diff' => $diff]);
                }
                elseif ($diff < 86400) { // 60s * 60m * 24h
                    $diff = floor($diff / 3600); // 60s * 60m
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 hour} other{# hours}} ago', ['diff' => $diff]);
                }
                elseif ($diff < 604800) { // 60s * 60m * 24h * 7d
                    $diff = floor($diff / 86400); // 60s * 60m * 24h
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 day} other{# days}} ago', ['diff' => $diff]);
                }
                elseif ($diff < 2678400) { // 60s * 60m * 24h * 31d
                    $diff = floor($diff / 604800); // 60s * 60m * 24h * 7d
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 week} other{# weeks}} ago', ['diff' => $diff]);
                }
                else {
                    $diff = floor($diff / 2678400); // 60s * 60m * 24h * 31d
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 month} other{# months}} ago', ['diff' => $diff]);
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
                $time_title = 'Modified time: '.$modified_time_txt_exact . "\n" .
                'Creation time: '.$created_time_txt . "\n";

                // In case of Google Drive we use 'ID' instead of 'path'
                if ($cloud == 'gd') {
                	$path = $id;
                }

                // Check which boxes are selected
                $permission = '';

                // Get shared content rows from database
                $sql = $db->createCommand('SELECT * FROM onlinedrives_app_drive_path_detail
                    WHERE (drive_path = :drive_path AND fileid = :fileid)
                    AND onlinedrives_app_detail_id = :app_detail_id 
                    AND share_status = \'Y\' ', [
                    ':drive_path' => $path,
                    ':fileid' => $id,
                    ':app_detail_id' => $app_detail_id,
                ])->queryAll();
                foreach ($sql as $value) {
                    $permission = $value['permission'];
                }

                //echo "permission=".$permission."<br>";

                if ($permission != '') {
                    $pos_rename = strpos($permission, 'Rn');
                    $pos_move = strpos($permission, 'Mv');
                    $pos_del = strpos($permission, 'D');
                    $pos_upl = strpos($permission, 'U');
                    $pos_read = strpos($permission, 'Rd');

                    if ($pos_read !== false || $pos_rename !== false || $pos_move !== false || $pos_del !== false || $pos_upl !== false) {
                        $pos_read = true;
                    }
                    else {
                        $pos_read = false;
                    }
                }
                else {
                    $pos_rename = false;
                    $pos_move = false;
                    $pos_del = false;
                    $pos_upl = false;
                    $pos_read = false;
                }

                // Output all folders
                echo '<tr id="tr'.$no.'" style="border-top: 1px solid #ddd; color: #555;">
                    <td class="shownone">'.
                        $type.
                    '</td>

                    <td>';
                        // Rework
                        if ($cloud == 'sciebo') {
                            $path_chunk = str_replace($sciebo_path_to_replace, '', $path);
                            $has_share = 'N';

                            if ($pos_read === true) {
                                //echo "I am true";
                                $has_share = 'Y';
                                $model_addfiles->drive_path[$i] = urlencode($path_chunk);

                            }
                        }
                        elseif ($cloud == 'gd') {
                            $path_chunk = $id;
                            $has_share = 'N';

                            if ($pos_read === true) {
                                //echo "I am true";
                                $has_share = 'Y';
                                $model_addfiles->drive_path[$i] = $id;
                            }
                        }

                        // File ID
                        echo Html::ActiveHiddenInput($model_addfiles, 'fileid['.$i.']', array('value' => $id));

                        // mime type input
                        echo Html::ActiveHiddenInput($model_addfiles, 'mime_type['.$i.']',  array('value' => $type));

                        // Output
                        echo $form_addfiles->field($model_addfiles, 'drive_path['.$i.']')->checkboxList([
                            urlencode($path_chunk) => '',
                        ], [
                            'onchange' => 'checked = document.getElementsByName(\'AddFilesForm[drive_path]['.$i.'][]\')[0].checked;

                                if (checked != true) {
                                    document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[0].checked = false;
                                }
                                '
                        ]);
                    echo '
                    <td>';

                    // Tooltip message
                    $tooltip_upload = Yii::t('OnlinedrivesModule.new', 'Please select the checkbox to allow other members of this space to upload files inside the folder on the left or to create a new folder / file.');

                    // Check if has child
                    $path_regex = '^'.$path.'.[a-zA-Z0-9!@#$+%&*_.-]*'; //'^Test%201A/.[a-zA-Z0-9!@#$+%&*_.-]*'
                    $sql = $db->createCommand('SELECT * FROM onlinedrives_app_drive_path_detail
                        WHERE onlinedrives_app_detail_id = :app_detail_id AND drive_path REGEXP :drive_path AND share_status = \'Y\' ', [
                        ':app_detail_id' => $app_detail_id,
                        ':drive_path' => $path_regex,
                    ])->queryAll();

                    /*
                    if ($has_share == 'Y') {
                        $span_folder_icon = '<span class="glyphicon glyphicon-folder-close" style="margin-right: 10px; color: #0b93d5"></span>';
                    }
                    else
                    */
                    if(count($sql) > 0){
                        $span_folder_icon = '<span class="glyphicon glyphicon-folder-close" style="margin-right: 10px; color: #cacaca"></span>';
                    }
                    else {
                        $span_folder_icon = '<span class="glyphicon glyphicon-folder-close" style="margin-right: 10px;"></span>';
                    }
                        if ($fav <> 0) {
                            $span_fav_icon = '<span class="glyphicon glyphicon-star fav_brown"></span>';
                        }
                        else {
                            $span_fav_icon = '<span class="glyphicon glyphicon-star fav_default"></span>';
                        }

                        if ($cloud == 'sciebo') {
                            $path = urlencode($path);
                            $url = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Faddfiles&'.$guid.'&app_detail_id='.$app_detail_id.'&sciebo_path='.$path;
                            echo $span_fav_icon.'<a href="'.$url.'">'.$span_folder_icon.' '.$name.'</a>';
                        }
                        elseif ($cloud == 'gd') {
                            $url = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Faddfiles&'.$guid.'&app_detail_id='.$app_detail_id.'&gd_folder_id='.$id.'&gd_folder_name='.$name;
                            echo $span_fav_icon.'<a href="'.$url.'">'.$span_folder_icon.' '.$name.'</a>';
                        }
                    echo '</td>

                    <td><div><!-- Tooltip -->
                    <span style="margin-top: 0px; margin-bottom: 0px; display:inline-block;" class="tt" data-toggle="tooltip" data-placement="top" 
                    data-original-title="'.$tooltip_upload.'">
                    <i data-target="globalModal"></i>
                    ';
                        if ($pos_rename !== false && $pos_move !== false && $pos_del !== false && $pos_upl !== false) {
                            $model_addfiles->permission[$i] = ['Rn', 'Mv', 'D', 'U'];
                        }
                        elseif ($pos_rename !== false && $pos_move !== false && $pos_del !== false) {
                            $model_addfiles->permission[$i] = ['Rn', 'Mv', 'D'];
                        }
                        elseif ($pos_rename !== false && $pos_move !== false && $pos_upl !== false) {
                            $model_addfiles->permission[$i] = ['Rn', 'Mv', 'U'];
                        }
                        elseif ($pos_rename !== false && $pos_del !== false && $pos_upl !== false) {
                            $model_addfiles->permission[$i] = ['Rn', 'D', 'U'];
                        }
                        elseif ($pos_move !== false && $pos_del !== false && $pos_upl !== false) {
                            $model_addfiles->permission[$i] = ['Mv', 'D', 'U'];
                        }
                        elseif ($pos_rename !== false && $pos_move !== false) {
                            $model_addfiles->permission[$i] = ['Rn', 'Mv'];
                        }
                        elseif ($pos_rename !== false && $pos_upl !== false) {
                            $model_addfiles->permission[$i] = ['Rn', 'U'];
                        }
                        elseif ($pos_rename !== false && $pos_del !== false) {
                            $model_addfiles->permission[$i] = ['Rn', 'D'];
                        }
                        elseif ($pos_del !== false && $pos_upl !== false) {
                            $model_addfiles->permission[$i] = ['D', 'U'];
                        }
                        elseif ($pos_move !== false && $pos_del !== false) {
                            $model_addfiles->permission[$i] = ['Mv', 'D'];
                        }
                        elseif ($pos_move !== false && $pos_upl !== false) {
                            $model_addfiles->permission[$i] = ['Mv', 'U'];
                        }
                        elseif ($pos_rename !== false) {
                            $model_addfiles->permission[$i] = ['Rn'];
                        }
                        elseif ($pos_move !== false) {
                            $model_addfiles->permission[$i] = ['Mv'];
                        }
                        elseif ($pos_del !== false) {
                            $model_addfiles->permission[$i] = ['D'];
                        }
                        elseif ($pos_upl !== false) {
                            $model_addfiles->permission[$i] = ['U'];
                        }
                        echo $form_addfiles->field($model_addfiles, 'permission['.$i.']')->checkboxList([
                            // 'Rn' => 'Rename',
                            // 'Mv' => 'Move',
                            // 'C' => 'Copy',
                            // 'D' => 'Delete',
                            'U' => 'Upload / Create',
                        ], [
                            'onchange' => 'checked_0 = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[0].checked;

                                if (checked_0 == true) {
                                    document.getElementsByName(\'AddFilesForm[drive_path]['.$i.'][]\')[0].checked = true;
                                }
                                '
                        ] );
                    echo '</span></div>

                    </td>
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

            $parents = $all_files[$i]['parents'];
            $fav = $all_files[$i]['fav'];
            $file_owner = $all_files[$i]['file_owner'];
            $file_shared = $all_files[$i]['file_shared'];
            $file_comment = $all_files[$i]['file_comment'];

            $created_time_txt = $created_time;
            $modified_time_txt = $modified_time;

            $checkbox_index = $i + $count_all_folders;

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

            if ($cloud == 'sciebo' ||          // Sciebo
                $cloud == 'gd' &&              // Google Drive
                ($parents[0] == $gd_root_id || // Root ID of Google Drive
                    $parents[0] == '' ||       // Shared files of Google Drive
                    $get_gd_folder_id != '')   // Folder ID of Google Drive
            ) {
                $no++;

                // Modified time (files)
                $diff = $now - $modified_time;
                if ($diff < 2) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'just now');
                }
                elseif ($diff < 60) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{%diff} seconds ago', ['diff' => $diff]);
                }
                elseif ($diff < 3600) { // 60s * 60m
                    $diff = floor($diff / 60);
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 }minute other{# minutes}} ago', ['diff' => $diff]);
                }
                elseif ($diff < 86400) { // 60s * 60m * 24h
                    $diff = floor($diff / 3600); // 60s * 60m
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 hour} other{# hours}} ago', ['diff' => $diff]);
                }
                elseif ($diff < 604800) { // 60s * 60m * 24h * 7d
                    $diff = floor($diff / 86400); // 60s * 60m * 24h
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 day} other{# days}} ago', ['diff' => $diff]);
                }
                elseif ($diff < 2678400) { // 60s * 60m * 24h * 31d
                    $diff = floor($diff / 604800); // 60s * 60m * 24h * 7d
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 week} other{# weeks}} ago', ['diff' => $diff]);
                }
                else {
                    $diff = floor($diff / 2678400); // 60s * 60m * 24h * 31d
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', '{diff,plural,=1{1 month} other{# months}} ago', ['diff' => $diff]);
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
                $info = 'ID: '.$id . "\n" .
                'Mime-Type: '.$mime_type . "\n" .
                'Nr.: '.$no . "\n" .
                'Parents-Anzahl: '.$count_parents . "\n" .
                'Parent-ID-Liste: '.$parent_id_list;

                // Time title
                $time_title = 'Modified time: '.$modified_time_txt_exact . "\n" .
                'Creation time: '.$created_time_txt . "\n";

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
                    case 'msword':
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
                    case 'text':
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
                    case 'quicktime':
                        $icon = 'vid'; break;
                    case 'zip':
                        $icon = 'zip'; break;
                    case 'x-ms-dos-executable':
                    case 'exe':
                        $icon = 'exe'; break;
                    case 'mx12':
                    case 'mx18':
                        $icon = ''; break;
                    case 'x-msdos-program':
                    case 'dll':
                        $icon = 'dll'; break;
                    case 'octet-stream':
                    case 'tlb':
                        $icon = 'tlb'; break;
                    default:
                        $icon = 'unknown'; break;
                }
                // echo $mime_type_icon;

                $img = '<img src="protected/modules/onlinedrives/resources/images/type/'.$icon.'.png" alt="'.'" title="'.'" style="margin-right: 10px;" />';

                // In case of Google Drive we use 'ID' instead of 'path'
                if ($cloud == 'gd') {
                	$path = $id;
                }

                // Check which boxes are selected
                $permission = '';

                //echo 'name='.$name.'--path='.$path.'--permission=';

                $sql = $db->createCommand('SELECT * FROM onlinedrives_app_drive_path_detail
                    WHERE drive_path = :drive_path AND onlinedrives_app_detail_id = :app_detail_id AND share_status = \'Y\'', [
                    ':drive_path' => $path,
                    ':app_detail_id' => $app_detail_id,
                ])->queryAll();
                foreach ($sql as $value) {
                    $permission = $value['permission'];
                }

                //echo $permission."<br>";

                if ($permission != '') {
                    $pos_rename = strpos($permission, 'Rn');
                    $pos_move = strpos($permission, 'Mv');
                    $pos_del = strpos($permission, 'D');
                    $pos_read = strpos($permission, 'Rd');
                    if ($pos_rename !== false || $pos_move !== false || $pos_del !== false || $pos_read !== false) {
                        $pos_read = true;
                    }
                    else {
                        $pos_read = false;
                    }
                }
                else {
                    $pos_rename = false;
                    $pos_move = false;
                    $pos_del = false;
                    $pos_upl = false;
                    $pos_read = false;
                }

                // Output all files
                echo '<tr id="tr'.$no.'" style="border-top: 1px solid #ddd; color: #555;">
                    <td class="shownone" >'.
                        $type.
                    '</td>

                    <td>';
                        // Rework
                        if ($cloud == 'sciebo') {
                            $path_chunk = str_replace($sciebo_path_to_replace, '', $path);
                            $has_share = 'N';

                            if ($pos_read === true) {
                                //echo "I am true";
                                $has_share = 'Y';
                                $model_addfiles->drive_path[$checkbox_index] = urlencode($path_chunk);
                            }
                        }
                        elseif ($cloud == 'gd') {
                            $path_chunk = $id;
                            $has_share = 'N';

                            if ($pos_read === true) {
                                //echo "I am true";
                                $has_share = 'Y';
                                $model_addfiles->drive_path[$checkbox_index] = $id;
                            }
                        }

                        // File ID
                        echo Html::ActiveHiddenInput($model_addfiles, 'fileid['.$checkbox_index.']', array('value' => $id));

                        // mime type input
                        echo Html::ActiveHiddenInput($model_addfiles, 'mime_type['.$checkbox_index.']',  array('value' => $type));


                // Output
                        echo $form_addfiles->field($model_addfiles, 'drive_path['.$checkbox_index.']')->checkboxList([
                            urlencode($path_chunk) => '',
                        ], [
                            'onchange' => 'checked = document.getElementsByName(\'AddFilesForm[drive_path]['.$checkbox_index.'][]\')[0].checked;

                                if (checked == true) {
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked = true;
                                }
                                else {
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked = false;
                                }'
                        ]);
                    echo '</td>

                    <td>';
                        if ($fav <> 0) {
                            echo '<span class="glyphicon glyphicon-star fav_brown"></span>';
                        }
                        else {
                            echo '<span class="glyphicon glyphicon-star fav_default"></span>';
                        }
                        echo '<a href="'.$web_view_link.'" target="_blank">'.$img.' '.$name.'</a>
                    </td>

                    <td>';
                        if ($pos_rename !== false && $pos_move !== false && $pos_del !== false) {
                            $model_addfiles->permission[$checkbox_index] = ['Rn', 'Mv', 'D'];
                        }
                        elseif ($pos_rename !== false && $pos_move !== false) {
                            $model_addfiles->permission[$checkbox_index] = ['Rn', 'Mv'];
                        }
                        elseif ($pos_rename !== false && $pos_del !== false) {
                            $model_addfiles->permission[$checkbox_index] = ['Rn', 'D'];
                        }
                        elseif ($pos_move !== false && $pos_del !== false) {
                            $model_addfiles->permission[$checkbox_index] = ['Mv', 'D'];
                        }
                        elseif ($pos_rename !== false) {
                            $model_addfiles->permission[$checkbox_index] = ['Rn'];
                        }
                        elseif ($pos_move !== false) {
                            $model_addfiles->permission[$checkbox_index] = ['Mv'];
                        }
                        elseif ($pos_del !== false) {
                            $model_addfiles->permission[$checkbox_index] = ['D'];
                        }
                        elseif ($pos_read !== false) {
                            $model_addfiles->permission[$checkbox_index] = ['Rd'];
                        }

                        echo '<div style="visibility: hidden;">';
                        echo $form_addfiles->field($model_addfiles, 'permission['.$checkbox_index.']')->checkboxList([
                            'Rd' => 'Read',
                        ], [
                            'onchange' => 'checked_0 = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked;

                                if (checked_0 == true) {
                                    document.getElementsByName(\'AddFilesForm[drive_path]['.$checkbox_index.'][]\')[0].checked = true;
                                }
                                else {
                                    document.getElementsByName(\'AddFilesForm[drive_path]['.$checkbox_index.'][]\')[0].checked = false;
                                }'
                        ]);
                        echo '</div>';

                        /*
                        echo $form_addfiles->field($model_addfiles, 'permission['.$checkbox_index.']')->checkboxList([
                            'Rn' => 'Rename',
                            'Mv' => 'Move',
                            // 'C' => 'Copy',
                            'D' => 'Delete',
                        ], [
                        'onchange' => 'checked_0 = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked;
                            checked_1 = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[1].checked;
                            checked_2 = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[2].checked;

                            if (checked_0 == true || checked_1 == true || checked_2 == true) {
                                document.getElementsByName(\'AddFilesForm[drive_path]['.$checkbox_index.'][]\')[0].checked = true;
                            }
                            else {
                                document.getElementsByName(\'AddFilesForm[drive_path]['.$checkbox_index.'][]\')[0].checked = false;
                            }'
                        ]);
                        */
                    echo '</td>

                    <td>
                    </td>
                </tr>';
            }
        }
        ?>
        </thead>
    </table>

    <?php
    } // If folder is not empty
    ?>

    <div id="create_btn_login" class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?php
            if ($count_sciebo_files > 0 || $count_gd_files > 0) {
                echo Html::ActiveHiddenInput($model_addfiles, 'app_detail_id', array('value' => $app_detail_id));
                echo Html::submitButton(Yii::t('OnlinedrivesModule.new', 'Save'), ['class' => 'btn btn-primary']);
            }
            ?>
        </div>
    </div>

    <?php
    $form_addfiles = ActiveForm::end();
}
?>
    </div>
</div>
<?php echo Html::endForm(); ?>