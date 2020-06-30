<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\controllers;

use Google_Service_Drive;
use humhub\modules\content\widgets\WallCreateContentForm;
use humhub\modules\post\models\Post;
use Yii;
use humhub\modules\onlinedrives\widgets\FileList;
use yii\helpers\Url;
use yii\web\HttpException;
use yii\web\Controller;
use humhub\modules\onlinedrives\permissions\ManageFiles;
use humhub\modules\onlinedrives\permissions\WriteAccess;

use humhub\modules\onlinedrives\models\forms\LoginForm;
use humhub\modules\onlinedrives\models\forms\LoginFormGDClient;
use humhub\modules\onlinedrives\models\forms\CreateFileForm;
use humhub\modules\onlinedrives\models\forms\UploadFileForm;
use humhub\modules\onlinedrives\models\forms\DeleteFileForm;
use humhub\modules\onlinedrives\models\forms\AddFilesForm;

use app\models\UploadForm;
use yii\web\UploadedFile;

class BrowseController extends BaseController
{

    public function postMsgStream($mime_type, $space_id, $drive_path, $drive_key, $drive_name){
        // initialize variables
        $post_target_content_name = '';
        $post_msg_url = '';
        $post_msg_description_start = '';
        $post_msg_description_end = '';
        $home_url = Url::base(true);

        if($drive_name == 'sciebo' && $mime_type=='folder'){
            // Folder content url
            if(Yii::$app->urlManager->enablePrettyUrl == true){
                $post_msg_url = $home_url.'/onlinedrives/browse/?cguid='.$space_id.'&sciebo_path='.$drive_path.'&dk='.$drive_key;
            }
            else{
                $post_msg_url = $home_url.'/index.php?r=onlinedrives%2Fbrowse&cguid='.$space_id.'&sciebo_path='.$drive_path.'&dk='.$drive_key;
            }

            // Folder content name
            $sub_path = substr($drive_path,0,-1);
            $list_foldernames = explode('/',$sub_path);
            $target_foldername = end($list_foldernames);
            $post_target_content_name = urldecode($target_foldername);
        }
        elseif ($drive_name == 'sciebo' && $mime_type=='file'){
            // File content url
            if(Yii::$app->urlManager->enablePrettyUrl == true){
                $post_msg_url = $home_url.'/onlinedrives/browse/?cguid='.$space_id;
            }
            else{
                $post_msg_url = $home_url.'/index.php?r=onlinedrives%2Fbrowse&cguid='.$space_id;
            }

            // File content name
            $list_foldernames = explode('/',$drive_path);
            $target_foldername = end($list_foldernames);
            $post_target_content_name = urldecode($target_foldername);
        }
        else if($drive_name == 'gd' && $mime_type=='folder'){
            // Folder content url
            if(Yii::$app->urlManager->enablePrettyUrl == true){
                $post_msg_url = $home_url.'/onlinedrives/browse/?cguid='.$space_id.'&gd_folder_id='.$drive_path.'&dk='.$drive_key;
            }
            else{
                $post_msg_url = $home_url.'/index.php?r=onlinedrives%2Fbrowse&cguid='.$space_id.'&gd_folder_id='.$drive_path.'&dk='.$drive_key;
            }

            // Folder content name
            $post_target_content_name = 'New Folder';
        }
        else if($drive_name == 'gd' && $mime_type=='file'){
            // Folder content url
            if(Yii::$app->urlManager->enablePrettyUrl == true){
                $post_msg_url = $home_url.'/onlinedrives/browse/?cguid='.$space_id.'&gd_folder_id='.$drive_path.'&dk='.$drive_key;
            }
            else{
                $post_msg_url = $home_url.'/index.php?r=onlinedrives%2Fbrowse&cguid='.$space_id.'&gd_folder_id='.$drive_path.'&dk='.$drive_key;
            }


            // Folder content name
            $post_target_content_name = 'New File';
        }

        // build post message
        $drive_name_desc = ($drive_name=='sciebo') ? 'Sciebo':'Google Drive';
        $post_msg_description_start = 'Published new '.$drive_name_desc.' '.$mime_type.' - ';
        $post_msg_description_end = ' in OnlineDrives module';
        $post_msg = $post_msg_description_start.' ['.$post_target_content_name.']('.$post_msg_url.' "'.$post_target_content_name.'")'.$post_msg_description_end;

        // Output New post msg to stream
        $post = new Post($this->contentContainer);
        $post->message = $post_msg;
        $post_result = WallCreateContentForm::create($post, $this->contentContainer);

        // stream post id
        $post_content_id = $post_result['id'];

        return $post_content_id;
    }

    public function actionIndex()
    {
        // Sciebo params
        $get_sciebo_path = '';

        $home_url = Url::base(true);
        if (!empty($_GET['cguid'])) {
            $guid = 'cguid=' . $_GET['cguid']; // Get param, important for paths
        }

        if (!empty($_GET['sciebo_path'])) {
            $get_sciebo_path = $_GET['sciebo_path'];
        }
        elseif (!empty($_POST['sciebo_path'])) {
            $get_sciebo_path = $_POST['sciebo_path'];
        }

        if (isset(Yii::$app->user->identity->username)) {
            $username = Yii::$app->user->identity->username;
        }
        else {
            $username = '';
            $home_url = Url::base(true);
            return $this->redirect($home_url);
        }

        $currentFolder = $this->getCurrentFolder();
        if (!$currentFolder->content->canView()) {
            throw new HttpException(403);
        }

        $model_login = new LoginForm();
        $model_login_gd_client = new LoginFormGDClient();
        $model = new CreateFileForm();
        $model_u = new UploadFileForm();
        $model_gd_delete = new DeleteFileForm();

        // Login Sciebo model
        if ($model_login->load(Yii::$app->request->post())) {
            if ($model_login->validate()) {
                // Get params
                $space_id = $_GET['cguid'];
                $drive_name = $model_login->selected_cloud_login;
                $app_user_id = $model_login->app_id;
                $app_password = $model_login->password;

                $username = Yii::$app->user->identity->username;
                $email = Yii::$app->user->identity->email;

                // DB connection
                // https://www.yiiframework.com/doc/guide/2.0/en/db-active-record
                // https://www.yiiframework.com/doc/guide/2.0/en/db-dao
                // https://www.yiiframework.com/doc/api/2.0/yii-db-connection
                // https://www.yiiframework.com/doc/guide/2.0/en/security-passwords
                include_once __DIR__ . '/../models/dbconnect.php';
                $db = dbconnect();
                $db->open();

                // Check path is already exist in share

                $sql = $db->createCommand('SELECT * FROM onlinedrives_app_detail 
                            WHERE app_user_id = :app_user_id AND space_id = :space_id AND if_shared <> \'D\'', [
                            ':app_user_id' => $app_user_id, ':space_id' => $space_id,
                ])->queryAll();

                if (count($sql) > 0) {
                    // Error message
                    $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Already app user exit.');
                }
                else {
                    if (!empty($app_user_id) && !empty($app_password)) {

                        $db->createCommand('INSERT INTO onlinedrives_app_detail (space_id, user_id, email, drive_name, app_user_id, app_password, create_date)
                            VALUES (:space_id, :user_id, :email, :drive_name, :app_user_id, :app_password, :create_date)', [
                            ':space_id' => $space_id,
                            ':user_id' => $username,
                            ':email' => $email,
                            ':drive_name' => $drive_name,
                            ':app_user_id' => $app_user_id,
                            ':app_password' => $app_password,
                            ':create_date' => time(),
                        ])->execute();

                        // Success message
                        $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Cloud storage is added successfully.');
                    }
                    else {
                        // Error message
                        $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'An error has occurred during registration. Please try it again.');
                    }
                }
            }
            else {
                $error = '';
                $errors = $model_login->errors;
                foreach ($errors as $error) {
                    $error = $error[0];
                }

            }

            // Valid data received in $model_login
            return $this->render('index', [
                'contentContainer' => $this->contentContainer,
                'folder' => $currentFolder,
                'canWrite' => $this->canWrite(),
                'model' => $model_login,
            ]);
        }

        // Login GD model
        if ($model_login_gd_client->load(Yii::$app->request->post())) {
            // Get params
            $space_id = $_GET['cguid'];
            $app_user_id = $model_login_gd_client->gd_app_id;

            $username = Yii::$app->user->identity->username;
            $email = Yii::$app->user->identity->email;

            // DB connection
            include_once __DIR__ . '/../models/dbconnect.php';
            $db = dbconnect();
            $db->open();

            // Check path is already exist in share
            $sql = $db->createCommand('SELECT * FROM onlinedrives_app_detail WHERE app_user_id = :app_user_id AND if_shared <> \'D\'', [
                ':app_user_id' => $app_user_id,
            ])->queryAll();

            if (count($sql) > 0) {
                // Error message
                $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Already app user exist.');
            }
            else {
                // Should upload client_secret.json
                $image = \yii\web\UploadedFile::getInstance($model_login_gd_client, 'upload_gd_client_secret_file');
                if (!is_null($image)) {
                    $model_login_gd_client->image_src_filename = $image->name;
                    $tmp = explode('.', $image->name);
                    $ext = end($tmp);
                    $realname = $model_login_gd_client->image_src_filename;
                    $realname = str_replace(' ', '%20', $realname);

                    $random_string = Yii::$app->security->generateRandomString();
                    $model_login_gd_client->image_web_filename = $random_string . ".{$ext}";

                    Yii::$app->params['uploadPath'] = Yii::$app->basePath . '/modules/onlinedrives/upload_dir/google_client/';
                    $path = Yii::$app->params['uploadPath'] . $model_login_gd_client->image_web_filename;

                    if ($image->saveAs($path) && !empty($app_user_id) && !empty($random_string)) {
                        $content = file_get_contents($path);

                        // If uploaded JSON file is valid
                        if (strpos($content, 'research-hub.social') !== false) {
                            $db->createCommand('INSERT INTO onlinedrives_app_detail (space_id, user_id, email, drive_name, app_user_id, app_password, create_date, if_shared)
                                VALUES (:space_id, :user_id, :email, :drive_name, :app_user_id, :app_password, :create_date, :if_shared)', [
                                ':space_id' => $space_id,
                                ':user_id' => $username,
                                ':email' => $email,
                                ':drive_name' => 'gd',
                                ':app_user_id' => $app_user_id,
                                ':app_password' => $random_string,
                                ':create_date' => time(),
                                ':if_shared' => 'T',
                            ])->execute();

                            if (isset($_GET['code'])) {
                                unset($_GET['code']);
                            }

                            // Success message
                            //$_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Cloud storage is added successfully.');
                        }
                        // If uploaded JSON file is NOT valid
                        else {
                            // Unlink uploaded JSON file again
                            unlink($path);

                            // Error message
                            $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Google Drive client add failed, because your JSON file is invalid.');
                        }
                    }
                    // Error message
                    else {
                        $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Google Drive client add failed.');
                    }
                }
            }

            // Valid data received in $model_login
            return $this->render('index', [
                'contentContainer' => $this->contentContainer,
                'folder' => $currentFolder,
                'canWrite' => $this->canWrite(),
                'model' => $model_login_gd_client,
            ]);
        }

        // Upload model
        if ($model_u->load(Yii::$app->request->post())) {

            $image = \yii\web\UploadedFile::getInstance($model_u, 'upload');
            if (!is_null($image)) {
                $model_u->image_src_filename = $image->name;
                $tmp = explode('.', $image->name);
                $ext = end($tmp);
                $cloud = $model_u->selected_cloud_u;
                $realname = $model_u->image_src_filename;
                $realname = str_replace(' ', '%20', $realname);

                $model_u->image_web_filename = Yii::$app->security->generateRandomString() . ".{$ext}";

                Yii::$app->params['uploadPath'] = Yii::$app->basePath . '/modules/onlinedrives/upload_dir/';
                $path = Yii::$app->params['uploadPath'] . $model_u->image_web_filename;

                // initialize variables
                $get_dk = '';
                if (!empty($_GET['dk'])) {
                    $get_dk = $_GET['dk'];
                }
                $db_connected_user_id = '';
                $permission_pos = false;


                include_once __DIR__ . '/../models/dbconnect.php';
                $db = dbconnect();
                $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.*
                    FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p 
                    ON d.id = p.onlinedrives_app_detail_id
                    WHERE drive_key = :drive_key', [
                    ':drive_key' => $get_dk,
                ])->queryAll();
                foreach ($sql as $value) {
                    $db_connected_user_id = $value['user_id'];
                    $drive_path = $value['drive_path'];
                    $app_user_id = $value['app_user_id'];
                    $app_password = $value['app_password'];
                    $db_permission = $value['permission'];
                    $permission_pos = strpos($db_permission, 'U'); // 'U' is for both Upload file and Create Folder
                }

                if (!empty($app_user_id)) {
                    if ($cloud == 'sciebo') {
                        //initialize flags
                        $flag_same_file = '';

                        // Rework
                        $get_sciebo_path = str_replace(' ', '%20', $get_sciebo_path);

                        // check duplicate content
                        if ($get_sciebo_path != '' && strpos($get_sciebo_path, $drive_path) !== false) {
                            // Set Sciebo path to replace with user ID
                            $sciebo_path_to_replace = '/remote.php/dav/files/'.$app_user_id.'/';

                            $sciebo_client = $model_u->getScieboClient($app_user_id, $app_password);

                            $sciebo_content = $model_u->getScieboFiles($sciebo_client, $app_user_id, $get_sciebo_path);

                            $keys = array_keys((array)$sciebo_content);

                            foreach ($keys as $values) {
                                // echo '<br>Existing path='.str_replace($sciebo_path_to_replace, '', $values);

                                $existing_folder_path = str_replace($sciebo_path_to_replace, '', $values);

                                $new_folder_path = $get_sciebo_path.$realname;

                                 //echo '--new folder path='.$new_folder_path.'--'.$existing_folder_path.'<br>';

                                if ($existing_folder_path === $new_folder_path)                 {
                                    // set the below flag for checking if the file is same. Check before file 'PUT' request
                                    $flag_same_file = 'y';
                                    continue;
                                }

                                $flag_same_file .= $flag_same_file;
                            }

                        }
                        $check_duplicate_file = strpos($flag_same_file, 'y');

                        // Check if access for drive path is given
                        if ($get_sciebo_path != '' && strpos($get_sciebo_path, $drive_path) !== false) {

                            if ($check_duplicate_file !== false){
                                // Error msg
                                $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'File already exist. Please rename.');
                            }// check duplicate content
                            elseif( !($db_connected_user_id ==  $username || $permission_pos !== false) ){
                                // Error msg
                                $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Insufficient user privilege.');
                            }// check upload permission
                            else{
                                if ($image->saveAs($path)) {
                                    // get file content from local upload_dir
                                    $content = file_get_contents($path);

                                    // Upload target in sciebo
                                    $path_to_dir = 'https://uni-siegen.sciebo.de/remote.php/dav/files/'.$app_user_id.'/'.$get_sciebo_path.$realname;

                                    // set unlimited time limit and unlimited memory limit for this transfer
                                    set_time_limit(0);
                                    ini_set('memory_limit', '-1');

                                    // initialize client
                                    $client = $model_u->getScieboClient($app_user_id, $app_password);
                                    if ($client->request('PUT', $path_to_dir, $content)) {
                                        unlink($path);

                                        // Success msg
                                        $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'File is successfuly uploaded in Sciebo.');
                                    }else{
                                        // Error msg
                                        $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Failed to transfer file to Sciebo.');
                                    }
                                }
                                else{
                                    // Error msg
                                    $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Failed to transfer file to upload directory.');
                                }
                            }

                        }
                        else {
                            // Error msg
                            $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'The upload permission for this folder is missing.');
                        }
                    }
                    elseif ($cloud == 'gd') {
                        // TODO xx
                    }
                }
                else {
                    // Error msg
                    $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'File was not uploaded because the permission is missing.');
                }
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Valid data received in $model_gd_create
            return $this->render('index', [
                'contentContainer' => $this->contentContainer,
                'folder' => $currentFolder,
                'canWrite' => $this->canWrite(),
                'model' => $model,
            ]);
        }
        // Unshare file
        elseif ($model_gd_delete->load(Yii::$app->request->post()) && $model_gd_delete->validate()) {
            // Get params
            $get_dk = '';
            $permission = '';
            $user_id = '';
            if (!empty($_GET['dk'])) {
                $get_dk = $_GET['dk'];
            }
            elseif ($model_gd_delete->dk != '') {
                $get_dk = $model_gd_delete->dk;
            }

            // DB connection
            include_once __DIR__ . '/../models/dbconnect.php';
            $db = dbconnect();

            $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.*
                FROM onlinedrives_app_detail d
                LEFT OUTER JOIN onlinedrives_app_drive_path_detail p ON d.id = p.onlinedrives_app_detail_id
                WHERE drive_key = :drive_key', [
                ':drive_key' => $get_dk,
            ])->queryAll();
            foreach ($sql as $value) {
                $drive_path = $value['drive_path'];
                $app_user_id = $value['app_user_id'];
                $app_password = $value['app_password'];
                $permission = $value['permission'];
                $user_id = $value['user_id'];
            }

            if (!empty($model_gd_delete->delete_file_id)) {
                $cloud = $model_gd_delete->cloud;
                $delete_file_id = $model_gd_delete->delete_file_id;
                $permission_pos = strpos($permission, 'D'); // permission = D, means Unshare/Disable, not Delete

                if (isset(Yii::$app->user->identity->username)) {
                    $username = Yii::$app->user->identity->username;
                }
                else {
                    $username = '';
                    $home_url = Url::base(true);

                    return $this->redirect($home_url);
                }

                // Sciebo unshare function
                if ($cloud == 'sciebo' && ($username == $user_id || $permission_pos !== false)) {
                        $db->createCommand('UPDATE onlinedrives_app_drive_path_detail 
                            SET share_status = "D", update_date = CURRENT_TIMESTAMP
                            WHERE drive_key = :drive_key AND share_status = \'Y\' ', [
                            ':drive_key' => $get_dk,
                        ])->execute();

                        // Success message
                        $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Unsharing was successful.');

                }
                // Google Drive unshare function
                elseif ($cloud == 'gd' && ($username == $user_id || $permission_pos !== false)) {
                    // Get the API client and construct the service object

                    $db->createCommand('UPDATE onlinedrives_app_drive_path_detail 
                        SET share_status = "D", update_date = CURRENT_TIMESTAMP
                        WHERE drive_key = :drive_key AND share_status = \'Y\' ', [
                        ':drive_key' => $get_dk,
                    ])->execute();

                    // Success message
                    $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Unsharing was successful.');
                }
                else {
                    $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Insufficient user privilege.');
                }
            }

            // Valid data received in $model_gd_delete
            return $this->render('index', [
                'contentContainer' => $this->contentContainer,
                'folder' => $currentFolder,
                'canWrite' => $this->canWrite(),
                'model_gd_delete' => $model_gd_delete,
            ]);
        }
        else {
            // Either the page is initially displayed or there is some validation error
            return $this->render('index', [
                'contentContainer' => $this->contentContainer,
                'folder' => $currentFolder,
                'canWrite' => $this->canWrite(),
            ]);
        }
    }

    public function actionDownloader()
    {
        $home_url = Url::base(true);

        // Sciebo params

        $currentFolder = $this->getCurrentFolder();
        if (!$currentFolder->content->canView()) {
            throw new HttpException(403);
        }

        if (isset(Yii::$app->user->identity->username)) {
            $username = Yii::$app->user->identity->username;
            //$email = Yii::$app->user->identity->email;

            return $this->render('downloader', [
                'contentContainer' => $this->contentContainer,
                'folder' => $currentFolder,
                'canWrite' => $this->canWrite(),
            ]);
        }
        else {
            return $this->redirect($home_url);
        }

        // Either the page is initially displayed or there is some validation error
    }

    public function actionAddfiles()
    {
        $home_url = Url::base(true);

        // Sciebo params
        $get_sciebo_path = '';
        $app_detail_id = '';
        $space_id = '';

        if (!empty($_GET['cguid'])) {
            $space_id = $_GET['cguid'];
        }

        if (!empty($_GET['cguid'])) {
            $guid = 'cguid=' . $_GET['cguid']; // Get param, important for paths
        }

        if (!empty($_GET['app_detail_id'])) {
            $app_detail_id =  $_GET['app_detail_id'];
        }
        else {
            return $this->redirect($home_url);
        }

        if (!empty($_GET['sciebo_path'])) {
            $get_sciebo_path = $_GET['sciebo_path'];
        }
        elseif (!empty($_POST['sciebo_path'])) {
            $get_sciebo_path = $_POST['sciebo_path'];
        }

        $currentFolder = $this->getCurrentFolder();
        if (!$currentFolder->content->canView()) {
            throw new HttpException(403);
        }

        if (isset(Yii::$app->user->identity->username)) {
            $username = Yii::$app->user->identity->username;
            // $email = Yii::$app->user->identity->email;

            $model_addfiles = new AddFilesForm();

            if ($model_addfiles->load(Yii::$app->request->post())) {
                // DB connection
                include_once __DIR__ . '/../models/dbconnect.php';
                $db = dbconnect();
                $db->open();

                if ($model_addfiles->validate()) {
                    $i = 0;

                    // Try to create post

                    /*
                    $post = new Post($this->contentContainer);

                    $post->message = 'Published new OnlineDrives [content](http://localhost/humhub-uni/index.php?r=onlinedrives%2Fbrowse&cguid=fafecccc-4b3d-4c0a-a30d-51f7bdb88bc4&sciebo_path=Research-Hub-Share/&dk=61e5a68809fce9d6ac1153f10ffb6876 "content")';

                    WallCreateContentForm::create($post, $this->contentContainer);
                    */
                    // end new post

                    $arr_drive_path = $model_addfiles->drive_path;
                    $arr_fileid =  $model_addfiles->fileid;
                    $app_detail_id =  $model_addfiles->app_detail_id;
                    $permission =  $model_addfiles->permission;
                    $arr_mime_type =  $model_addfiles->mime_type;

                    // initialize variables
                    $post_content_id = '';
                    $drive_name = '';

                    if (isset($_GET['sciebo_path'])) {
                        $get_sciebo_path = $_GET['sciebo_path'];
                    } // TODO: XXX
                    else {
                        $get_sciebo_path = '';
                    }

                    // find drive name
                    $sql_drive_name = $db->createCommand('SELECT d.* 
                                FROM onlinedrives_app_detail d
                                WHERE d.`id` = :app_detail_id
                                ',
                        [':app_detail_id' => $app_detail_id,
                    ])->queryAll();

                    if(count($sql_drive_name)>0){
                        $drive_name = $sql_drive_name[0]["drive_name"];
                    }

                    for ($i = 0; $i < count($arr_drive_path); $i++) {
                        $key = key($arr_drive_path);
                        $val = $arr_drive_path[$key];
                        
                        if ($val <> '') {

                            // form input - fileid
							if ($arr_fileid[$key] <> '') {
                                $val_fileid = $arr_fileid[$key];
                            }
                            else {
                                $val_fileid = '';
                            }

                            // form input - user permission
                            if ($permission[$key] <> '') {
                                $permission_items = implode('|', $permission[$key]);
                            }
                            else {
                                $permission_items = 'Rd';
                            }

                            // form input - mime type
                            if ($arr_mime_type[$key] <> '') {
                                $mime_type = $arr_mime_type[$key];
                            }
                            else {
                                $mime_type = '';
                            }


                            $drive_path = urldecode($val[0]);

                            // Check if drive path does already exist in share

                            // Debug Query: if drive path does already exist in share
                              /*$qry_check = 'SELECT d.`id` AS d_id, p.`id` AS p_id, d.*,p.*
                                FROM onlinedrives_app_detail d, onlinedrives_app_drive_path_detail p
                                WHERE d.`id` = p.`onlinedrives_app_detail_id`
                                AND d.`space_id` = \''.$space_id.'\'
                                AND d.`user_id` = \''.$username.'\'
                                AND p.`fileid` = \''.$val_fileid.'\'
                                AND d.`id` = '.$app_detail_id.'
                                GROUP BY p.`fileid`';

                              echo "OK--".$qry_check."<br>";*/

                            $sql = $db->createCommand('SELECT d.`id` AS d_id, p.`id` AS p_id, d.*,p.* 
                                FROM onlinedrives_app_detail d, onlinedrives_app_drive_path_detail p
                                WHERE d.`id` = p.`onlinedrives_app_detail_id`
                                AND d.`space_id` = :space_id
                                AND d.`user_id` = :username
                                AND p.`fileid` = :fileid
                                AND d.`id` = :app_detail_id
                                GROUP BY p.`fileid`', [
                                ':space_id' => $space_id,
                                ':username' => $username,
                                ':fileid' => $val_fileid,
                                ':app_detail_id' => $app_detail_id,
                            ])->queryAll();

                            if (count($sql) == 0) { // If there is no such drive path then create new row, else update existing row
                                // New drive key
                                $new_dk = md5(microtime());

                                // stream post id - after post msg to stream
                                $post_content_id = $this->postMsgStream($mime_type, $space_id, $drive_path, $new_dk, $drive_name);

                                // Insert new drive content
                                $db->createCommand('INSERT INTO `onlinedrives_app_drive_path_detail`
                                    (`drive_path`, `fileid`, `permission`, `onlinedrives_app_detail_id`, `drive_key`, `content_id`, `mime_type`) 
                                    VALUES (:drive_path, :fileid, :permission, :onlinedrives_app_detail_id, :drive_key, :content_id, :mime_type)', [
                                    ':drive_path' => $drive_path,
                                    ':fileid' => $val_fileid,
                                    ':permission' => $permission_items,
                                    ':onlinedrives_app_detail_id' => $app_detail_id,
                                    ':drive_key' => $new_dk,
                                    ':content_id' => $post_content_id,
                                    ':mime_type' => $mime_type,
                                ])->execute();
                            }
                            else { // Update existing row which are selected from the addfiles-form checkbox list
                                foreach($sql as $values) {
                                    $drive_path_detail_id = $values['p_id'];
                                    $db_content_id = $values['content_id'];
                                    $db_drive_key = $values['drive_key'];

                                    // if there was no previous post then post msg to stream
                                    if($db_content_id > 0 && !empty($db_content_id)){
                                        // Verify post still exists or deleted?
                                        $sql = $db->createCommand('SELECT id FROM `content` WHERE id = :content_id', [
                                            ':content_id' => $db_content_id,
                                        ])->queryAll();

                                        if (count($sql) == 0){
                                            // stream post id - after posting new msg to stream (because the old post might be deleted)
                                            $post_content_id = $this->postMsgStream($mime_type, $space_id, $drive_path, $db_drive_key, $drive_name);
                                        }
                                        else{
                                            // stream post id - which is already exist
                                            $post_content_id = $db_content_id;
                                        }
                                    }
                                    else{
                                        // stream post id - after posting new msg to stream
                                        $post_content_id = $this->postMsgStream($mime_type, $space_id, $drive_path, $db_drive_key, $drive_name);
                                    }

                                    // Debug update query
                                    /*$updt_qry = 'UPDATE onlinedrives_app_drive_path_detail
                                        SET `drive_path` = \''.$drive_path.'\', 
                                        `permission` = \''.$permission_items.'\', 
                                        `onlinedrives_app_detail_id` = '.$app_detail_id.',
                                        `update_date` = CURRENT_TIMESTAMP,
                                        `fileid` = \''.$val_fileid.'\',
                                        `mime_type` = \''.$mime_type.'\',
                                        `content_id` = '.$post_content_id.',
                                        `share_status` = \'Y\'
                                        WHERE id = '.$drive_path_detail_id;

                                    echo $updt_qry."<br>";*/


                                    $db->createCommand('UPDATE onlinedrives_app_drive_path_detail
                                        SET `drive_path` = :drive_path, 
                                        `permission` = :permission, 
                                        `onlinedrives_app_detail_id` = :onlinedrives_app_detail_id,
                                        `update_date` = CURRENT_TIMESTAMP,
                                        `fileid` = :fileid,
                                        `mime_type` = :mime_type,
                                        `content_id` =:content_id,
                                        `share_status` = \'Y\'
                                        WHERE id = :drive_path_detail_id', [
                                        ':drive_path_detail_id' => $drive_path_detail_id,
                                        ':drive_path' => $drive_path,
                                        ':permission' => $permission_items,
                                        ':onlinedrives_app_detail_id' => $app_detail_id,
                                        ':fileid' => $val_fileid,
                                        ':mime_type' => $mime_type,
                                        ':content_id' => $post_content_id,
                                    ])->execute();
                                }
                            }
                        }
                        next($arr_drive_path);
                    } // DB insert done

                    // Get not in list
                    $not_in_list = array();
                    $arr_drive_path = $model_addfiles->drive_path;

                    //print_r( $arr_drive_path );

                    for ($i = 0; $i < count($arr_drive_path); $i++) {
                        $key = key($arr_drive_path);
                        $val = $arr_drive_path[$key];
                        if ($val <> '') {

                            if ($arr_fileid[$key] <> '') {
                                $val_fileid = $arr_fileid[$key];
                            }
                            else {
                                $val_fileid = '';
                            }
                            $drive_path = urldecode($val[0]);


                            // Check path is already exist in share

                            $sql = $db->createCommand('SELECT d.`id` AS d_id, p.`id` AS p_id, d.*,p.* 
                                FROM onlinedrives_app_detail d, onlinedrives_app_drive_path_detail p
                                WHERE d.`id` = p.`onlinedrives_app_detail_id`
                                AND d.`space_id` = :space_id
                                AND d.`user_id` = :username
                                AND p.`fileid` = :fileid
                                AND d.`id` = :app_detail_id
                                AND p.`share_status` = \'Y\'', [
                                ':space_id' => $space_id,
                                ':username' => $username,
                                ':fileid' => $val_fileid,
                                ':app_detail_id' => $app_detail_id,
                            ])->queryAll();

                            if (count($sql) > 0) { // If there is drive path then push in array of IDs where status needs to update
                                array_push($not_in_list, $sql[0]['p_id']);
                            }
                        }
                        next($arr_drive_path);
                    }

                    //print_r($not_in_list);

                    $not_in = implode(',', $not_in_list);

                    //echo "<br>Submitted from direcory name = ".$get_sciebo_path." and --not in (".$not_in.")--";



                    // For handling root directory contents:
                    // Firstly identify that from which sciebo path the Request submitted. if the sciebo path is null that means
                    // the request submitted from root folder and all the folder names from root location will have only one '/' in the end in the DB table.
                    // For identifying files in the root folder, have to check regular expression, which checks if there is no '/' in the  beginning
                    // of the file name and end with file name and file extension.

                    if ($get_sciebo_path != '') {
                        $regular_exp1 = '^' . $get_sciebo_path . '.[a-zA-Z0-9!@#$+%&*_.-]*/$';
                        $regular_exp2 = '^' . $get_sciebo_path . '.[a-zA-Z0-9!@#$+%&*_.-]*.[.]+.[a-zA-Z0-9]*$';

                        $qry_check_foldername = ' ( drive_path like \''.$get_sciebo_path.'%\' 
                                                    AND length(SUBSTRING(drive_path,LENGTH(\''.$get_sciebo_path.'\')+1)) - length(replace(SUBSTRING(drive_path,LENGTH(\''.$get_sciebo_path.'\')+1),\'/\',\'\')) = 1 
                                                    and  SUBSTRING(drive_path,LENGTH(\''.$get_sciebo_path.'\')+1) REGEXP \'/$\'
                                                    )';

                        $qry_check_filename = ' OR (
                                                    drive_path REGEXP \'^'.$get_sciebo_path.'\' 
                                                    AND SUBSTRING(drive_path,LENGTH(\''.$get_sciebo_path.'\')+1) NOT LIKE \'%/%\' 
                                                    )';

                        if (!empty($not_in)) {
                            $id_not_in_str = 'AND id NOT IN ('.$not_in.')';
                        }
                        else {
                            $id_not_in_str = '';
                        }

                        //echo '<br>If I am printed then I am inside any directory <br>';

                         //Debug query: which files are going to be unchecked from inside of any folder
                         /*$qry = "UPDATE onlinedrives_app_drive_path_detail SET share_status ='D'
                                WHERE onlinedrives_app_detail_id = ".$app_detail_id."
                                AND (".$qry_check_foldername.$qry_check_filename.")
                                AND share_status='Y' ".$id_not_in_str;
                        echo $qry;*/



                        $db->createCommand('UPDATE onlinedrives_app_drive_path_detail
                                SET share_status = "D", update_date = CURRENT_TIMESTAMP
                                WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id
                                AND ( '.$qry_check_foldername.$qry_check_filename.' )
                                AND share_status = \'Y\' '.$id_not_in_str, [
                            ':onlinedrives_app_detail_id' => $app_detail_id,
                        ])->execute();
                    }
                    else { // this section only handles files and folder shared from root directory of sciebo
                        if (!empty($not_in)) {
                            $id_not_in_str = ' AND id NOT IN ('.$not_in.')';
                        }
                        else {
                            $id_not_in_str = '';
                        }

                        // construct query to check if the path has only one '/' at the end to identify folders of root directory.
                        // And also to check if there is no '/' in the filename which means the file belongs to root directory.

                        $qry_filename_check = ' AND (drive_path NOT LIKE \'%/%\')'; // check expression for searching file name only from root directory.
                        $qry_foldername_check = ' AND (LENGTH(`drive_path`) - LENGTH(REPLACE(`drive_path`, \'/\', \'\'))) = 1 AND (drive_path REGEXP \'/$\')'; // This checks if the path has only one '/' at the end to identify folders of root directory.


                        /* Debug query:  select id for which file/folder to disable.
                         * $qry = 'SELECT * FROM `onlinedrives_app_drive_path_detail`
                                WHERE onlinedrives_app_detail_id = '.$app_detail_id.' AND share_status = \'Y\'  
                                '.$qry_foldername_check.$id_not_in_str.'
                                UNION
                                SELECT * FROM `onlinedrives_app_drive_path_detail`
                                WHERE onlinedrives_app_detail_id = '.$app_detail_id.' AND share_status = \'Y\'  
                                '.$qry_filename_check.$id_not_in_str;
                        echo '<br>'.$qry;*/

                        $sql = $db->createCommand('SELECT * FROM `onlinedrives_app_drive_path_detail`
                                                        WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id AND share_status = \'Y\'  
                                                        '.$qry_foldername_check.$id_not_in_str.'
                                                        UNION
                                                        SELECT * FROM `onlinedrives_app_drive_path_detail`
                                                        WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id AND share_status = \'Y\'  
                                                        '.$qry_filename_check.$id_not_in_str, [
                            ':onlinedrives_app_detail_id' => $app_detail_id,
                        ])->queryAll();

                        if (count($sql) > 0) {

                            foreach($sql as $values) {
                                $unchecked_path_id = $values['id'];

                                /* Debug the update query:
                                 * $update_qry = 'UPDATE onlinedrives_app_drive_path_detail
                                SET share_status = "D", update_date = CURRENT_TIMESTAMP
                                WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id
                                AND id = '.$unchecked_path_id;

                                echo '<br>Count='.count($sql).$update_qry;*/

                                $db->createCommand('UPDATE onlinedrives_app_drive_path_detail
                                SET share_status = "D", update_date = CURRENT_TIMESTAMP
                                WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id
                                AND id = :unchecked_path_id', [
                                    ':onlinedrives_app_detail_id' => $app_detail_id,
                                    ':unchecked_path_id' => $unchecked_path_id,
                                ])->execute();

                            }
                        }
                    }

                    $sql = $db->createCommand('SELECT * FROM `onlinedrives_app_drive_path_detail`
                        WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id AND share_status = "Y"', [
                        ':onlinedrives_app_detail_id' => $app_detail_id,
                    ])->queryAll();

                    if (count($sql) > 0) {
                         // Update app_detail table and set status = Y for id = $app_detail_id

                         $db->createCommand('UPDATE onlinedrives_app_detail SET if_shared = "Y" WHERE id = :onlinedrives_app_detail_id', [
                             ':onlinedrives_app_detail_id' => $app_detail_id,
                         ])->execute();

                    }

                    // Redirection Url
                    if(Yii::$app->urlManager->enablePrettyUrl == true){
                        $redirect_url = $home_url.'/onlinedrives/browse/?'.$guid;
                    }
                    else{
                        $redirect_url = $home_url.'/index.php?r=onlinedrives%2Fbrowse&'.$guid;
                    }

                    (new yii\web\Controller('1', 'onlinedrives'))->redirect($redirect_url);

                    return $this->render('index', [
                        'contentContainer' => $this->contentContainer,
                        'folder' => $currentFolder,
                        'canWrite' => $this->canWrite(),
                    ]);

                }
                else {
                    // Redirection Url
                    if(Yii::$app->urlManager->enablePrettyUrl == true){
                        $redirect_url = $home_url.'/onlinedrives/browse/?cguid='.$space_id;
                    }
                    else{
                        $redirect_url = $home_url.'/index.php?r=onlinedrives%2Fbrowse&cguid='.$space_id;
                    }
                    return $this->redirect($redirect_url);
                }
            }
            else {
                return $this->render('addfiles', [
                    'contentContainer' => $this->contentContainer,
                    'folder' => $currentFolder,
                    'canWrite' => $this->canWrite(),
                ]);
            }
        }
        else {
            return $this->redirect($home_url);
        }

        // Either the page is initially displayed or there is some validation error
    }

    public function actionFileList()
    {
        return $this->asJson(['output' => $this->renderFileList()]);
    }

    /**
     * Returns rendered file list.
     *
     * @param boolean $withItemCount true -> also calculate and return the item count.
     * @param array $filesOrder orderBy array appended to the files query
     * @param array $foldersOrder orderBy array appended to the folders query
     * @return array|string the rendered view or an array of the rendered view and the itemCount.
     */
    public function renderFileList($filesOrder = null, $foldersOrder = null)
    {
        return FileList::widget([
            'folder' => $this->getCurrentFolder(),
            'contentContainer' => $this->contentContainer,
            'filesOrder' => $filesOrder,
            'foldersOrder' => $foldersOrder,
        ]);
    }
}
?>