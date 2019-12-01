<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\controllers;

use Google_Service_Drive;
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

                    if ($image->saveAs($path)) {

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
                        $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Cloud storage is added successfully.');
                    }
                    else{
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

                $get_dk = '';
                if (!empty($_GET['dk'])) {
                    $get_dk = $_GET['dk'];
                }

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
                }

                if (!empty($app_user_id)) {
                    if ($cloud == 'sciebo') {
                        // Rework
                        $get_sciebo_path = str_replace(' ', '%20', $get_sciebo_path);

                        // Check if access for drive path is given
                        if (strpos($get_sciebo_path, $drive_path) !== false) {
                            if ($image->saveAs($path)) {
                                $content = file_get_contents($path);
                                $path_to_dir = 'https://uni-siegen.sciebo.de/remote.php/dav/files/'.$app_user_id.'/'.$get_sciebo_path.$realname;

                                $client = $model_u->getScieboClient($app_user_id, $app_password);

                                if ($client->request('PUT', $path_to_dir, $content)) {
                                    unlink($path);

                                    // Success msg
                                    $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'File is successfuly uploaded in Sciebo.');
                                }
                            }
                        }
                        else {
                            // Error msg
                            $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'The permission for this folder is missing.');
                        }
                    }
                    elseif ($cloud == 'gd') {
                        //TODO xx
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
        // Delete file
        elseif ($model_gd_delete->load(Yii::$app->request->post()) && $model_gd_delete->validate()) {
            // Get params
            $get_dk = ''; $permission =''; $user_id='';
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
                $permission_pos = strpos($permission, 'D'); // permission = D , means Un-share, not Delete.

                if (isset(Yii::$app->user->identity->username)) {
                    $username = Yii::$app->user->identity->username;
                }
                else {
                    $username = '';

                    $home_url = Url::base(true);

                    return $this->redirect($home_url);
                }

                // Sciebo delete function
                if ($cloud == 'sciebo' && ($username == $user_id || $permission_pos !== false)) {

                        $db->createCommand('UPDATE onlinedrives_app_drive_path_detail 
                                                SET share_status = "D",update_date = CURRENT_TIMESTAMP
                                                WHERE drive_key = :drive_key
                                                AND share_status=\'Y\' ', [
                                                ':drive_key' => $get_dk,
                        ])->execute();

                        // Success message
                        $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Deletion from Sciebo was successful.');

                }
                // Google Drive delete function
                elseif ($cloud == 'gd' && ($username == $user_id || $permission_pos !== false)) {
                    // Get the API client and construct the service object

                    $db->createCommand('UPDATE onlinedrives_app_drive_path_detail 
                                                SET share_status = "D",update_date = CURRENT_TIMESTAMP
                                                WHERE drive_key = :drive_key
                                                AND share_status=\'Y\' ', [
                        ':drive_key' => $get_dk,
                    ])->execute();

                    // Success message
                    $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Deletion from Google Drive was successful.');
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

                    $arr_drive_path = $model_addfiles->drive_path;
                    $arr_fileid =  $model_addfiles->fileid;
                    $app_detail_id =  $model_addfiles->app_detail_id;
                    $permission =  $model_addfiles->permission;

                    if(isset($_GET['sciebo_path'])){
                        $get_sciebo_path = $_GET['sciebo_path'];
                        $str_drive_path_check = ' AND ';
                    }// TODO: XXX
                    else{
                        $get_sciebo_path = '';
                        $str_drive_path_check = '';
                    }

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


                            if ($permission[$key] <> '') {
                                $permission_items = implode('|', $permission[$key]);
                            }
                            else {
                                $permission_items = '';
                            }

                            $drive_path = urldecode($val[0]);

                            // Check path is already exist in share

                            $sql = $db->createCommand('SELECT d.`id` AS d_id, p.`id` AS p_id, d.*,p.* 
                                FROM onlinedrives_app_detail d, onlinedrives_app_drive_path_detail p
                                WHERE d.`id` = p.`onlinedrives_app_detail_id`
                                AND d.`space_id` = :space_id
                                AND d.`user_id` = :username
                                AND p.`drive_path` = :drive_path
                                AND d.`id` = :app_detail_id
                                AND p.`share_status`=\'Y\'', [
                                ':space_id' => $space_id,
                                ':username' => $username,
                                ':drive_path' => $drive_path,
                                ':app_detail_id' => $app_detail_id,
                            ])->queryAll();

                            if (count($sql) == 0) { // if there is no such drive path then create new row. else update existing row.

                                $db->createCommand('INSERT INTO `onlinedrives_app_drive_path_detail`
                                    (`drive_path`, `fileid`, `permission`, `onlinedrives_app_detail_id`, `drive_key`) 
                                    VALUES (:drive_path, :fileid, :permission, :onlinedrives_app_detail_id, :drive_key)', [
                                    ':drive_path' => $drive_path,
                                    ':fileid' => $val_fileid,
                                    ':permission' => $permission_items,
                                    ':onlinedrives_app_detail_id' => $app_detail_id,
                                    ':drive_key' => md5(microtime()),
                                ])->execute();
                            }
                            else{ // Update existing row which are selected from the addfiles-form checkbox list.

                                foreach($sql as $values){
                                    $drive_path_detail_id = $values['p_id'];
                                    $db->createCommand('UPDATE onlinedrives_app_drive_path_detail 
                                                        SET `drive_path`=:drive_path, 
                                                        `permission`=:permission, 
                                                        `onlinedrives_app_detail_id`=:onlinedrives_app_detail_id,
                                                        `update_date` = CURRENT_TIMESTAMP
                                                        WHERE id = :drive_path_detail_id', [
                                        ':drive_path_detail_id' => $drive_path_detail_id,
                                        ':drive_path' => $drive_path,
                                        ':permission' => $permission_items,
                                        ':onlinedrives_app_detail_id' => $app_detail_id,
                                    ])->execute();
                                }
                            }
                        }
                        next($arr_drive_path);
                    } // DB insert done


                    // get not in list
                    $not_in_list = array();
                    $arr_drive_path = $model_addfiles->drive_path;

                    for ($i = 0; $i < count($arr_drive_path); $i++) {
                        $key = key($arr_drive_path);
                        $val = $arr_drive_path[$key];
                        if ($val <> '') {

                            $drive_path = urldecode($val[0]);

                            // Check path is already exist in share

                            $sql = $db->createCommand('SELECT d.`id` AS d_id, p.`id` AS p_id, d.*,p.* 
                                FROM onlinedrives_app_detail d, onlinedrives_app_drive_path_detail p
                                WHERE d.`id` = p.`onlinedrives_app_detail_id`
                                AND d.`space_id` = :space_id
                                AND d.`user_id` = :username
                                AND p.`drive_path` = :drive_path
                                AND d.`id` = :app_detail_id
                                AND p.`share_status`=\'Y\'', [
                                ':space_id' => $space_id,
                                ':username' => $username,
                                ':drive_path' => $drive_path,
                                ':app_detail_id' => $app_detail_id,
                            ])->queryAll();

                            if (count($sql) > 0) { // if there is drive path then push in array of ids where status needs to update.

                                array_push($not_in_list,$sql[0]['p_id']);
                            }
                        }
                        next($arr_drive_path);
                    }


                    //print_r($not_in_list);

                    $not_in = implode(',', $not_in_list);

                    //echo $get_sciebo_path."--not in (".$not_in.")--";

                    $sql = $db->createCommand('SELECT * FROM `onlinedrives_app_drive_path_detail` 
                        WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id AND share_status = "Y"', [
                        ':onlinedrives_app_detail_id' => $app_detail_id,
                    ])->queryAll();

                    if (count($sql) > 0) {
                         // Update app_detail table and set status = Y for id=$app_detail_id

                         $db->createCommand('UPDATE onlinedrives_app_detail SET if_shared = "Y" WHERE id = :onlinedrives_app_detail_id', [
                             ':onlinedrives_app_detail_id' => $app_detail_id,
                         ])->execute();

                         // Update other drives paths to disabled which are unchecked from the addfiles-Form

                        // Step-1: Select all rows with same owner (onlinedrives_app_detail_id of table- onlinedrives_app_drive_path_detail)
                        // Step-1: also check if they are children of same parent drive path.

                        // Step-2: Update share_status=D for unchecked values.
                        if($get_sciebo_path!='') {
                            $regular_exp1 = '^' . $get_sciebo_path . '.[a-zA-Z0-9!@#$+%&*_.-]*/$';
                            $regular_exp2 = '^' . $get_sciebo_path . '.[a-zA-Z0-9!@#$+%&*_.-]*.[.]+.[a-zA-Z0-9]*$';
                            if(!empty($not_in)){
                                $id_not_in_str = 'AND id NOT IN ('.$not_in.')';
                            }
                            else{
                                $id_not_in_str = '';
                            }

                            //echo $regular_exp1.'<br>'.$regular_exp2.'<br>'.$id_not_in; die();

                            /*$qry = "UPDATE onlinedrives_app_drive_path_detail SET share_status ='D'
                                                WHERE onlinedrives_app_detail_id = ".$app_detail_id."
                                                AND (drive_path REGEXP '".$regular_exp1."' OR drive_path REGEXP '".$regular_exp2."' )
                                                AND share_status='Y' ".$id_not_in_str;

                            echo $qry;*/

                            $db->createCommand('UPDATE onlinedrives_app_drive_path_detail 
                                                SET share_status = "D",update_date = CURRENT_TIMESTAMP
                                                WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id
                                                AND (drive_path REGEXP :regex1 OR drive_path REGEXP :regex2)
                                                AND share_status=\'Y\' '.$id_not_in_str, [
                                ':onlinedrives_app_detail_id' => $app_detail_id,
                                ':regex1' => $regular_exp1,
                                ':regex2' => $regular_exp2,
                            ])->execute();
                        }
                        else{
                            if(!empty($not_in)){
                                $id_not_in_str = 'AND id NOT IN ('.$not_in.')';
                            }
                            else{
                                $id_not_in_str = '';
                            }

                            $qry_drive_path = ' AND (LENGTH(`drive_path`) - LENGTH(REPLACE(`drive_path`, \'/\', \'\')))=1 AND drive_path REGEXP \'/$\' ';

                            /*$qry = 'UPDATE onlinedrives_app_drive_path_detail SET share_status = "D"
                                                WHERE onlinedrives_app_detail_id = '.$app_detail_id.'
                                                AND share_status=\'Y\' '.$qry_drive_path.$id_not_in_str;

                            echo $qry;*/

                            $db->createCommand('UPDATE onlinedrives_app_drive_path_detail 
                                                SET share_status = "D",update_date = CURRENT_TIMESTAMP
                                                WHERE onlinedrives_app_detail_id = :onlinedrives_app_detail_id
                                                AND share_status=\'Y\' '.$qry_drive_path.$id_not_in_str, [
                                ':onlinedrives_app_detail_id' => $app_detail_id,
                            ])->execute();

                        }
                    }

                    $redirect_url = $home_url.'/index.php?r=onlinedrives%2Fbrowse&'.$guid;

                    (new yii\web\Controller('1', 'onlinedrives'))->redirect($redirect_url);

                    return $this->render('index', [
                        'contentContainer' => $this->contentContainer,
                        'folder' => $currentFolder,
                        'canWrite' => $this->canWrite(),
                    ]);

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