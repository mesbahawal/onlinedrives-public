<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\onlinedrives\controllers;

use Yii;
use humhub\modules\onlinedrives\widgets\FileList;
use yii\helpers\Url;
use yii\web\HttpException;
use yii\web\Controller;
use humhub\modules\onlinedrives\permissions\ManageFiles;
use humhub\modules\onlinedrives\permissions\WriteAccess;

use humhub\modules\onlinedrives\models\forms\LoginForm;
use humhub\modules\onlinedrives\models\forms\CreateFileForm;
use humhub\modules\onlinedrives\models\forms\UploadFileForm;
use humhub\modules\onlinedrives\models\forms\DeleteFileForm;

use app\models\UploadForm;
use yii\web\UploadedFile;



class BrowseController extends BaseController
{
    public function actionIndex()
    {
        // Sciebo params
        $get_sciebo_path = '';
        $home_url = Url::base('http');
        if (!empty($_GET['cguid'])) { $guid = "cguid=".$_GET['cguid']; } // Get param, important for paths

        if (!empty($_GET['sciebo_path'])) {
            $get_sciebo_path = $_GET['sciebo_path'];
        }
        elseif (!empty($_POST['sciebo_path'])) {
            $get_sciebo_path = $_POST['sciebo_path'];
        }

        $currentFolder = $this->getCurrentFolder();
        if (!$currentFolder->content->canView())
        {
            throw new HttpException(403);
        }

        $model_login = new LoginForm();
        $model = new CreateFileForm();
        $model_u = new UploadFileForm();
        $model_gd_delete = new DeleteFileForm();

        if ($model_login->load(Yii::$app->request->post()))
        {
            include_once(__DIR__.'/../models/dbconnect.php');
            $db = dbconnect();

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
            $db->open();

            $db->createCommand('INSERT INTO onlinedrives_app_detail (space_id, user_id, email, drive_name, app_user_id, app_password, drive_key, create_date) VALUES (:space_id, :user_id, :email, :drive_name, :app_user_id, :app_password, :drive_key, :create_date)', [
                ':space_id' => $space_id,
                ':user_id' => $username,
                ':email' => $email,
                ':drive_name' => $drive_name,
                ':app_user_id' => $app_user_id,
                ':app_password' => $app_password,
                ':drive_key' => md5(time()),
                ':create_date' => time(),
            ])->execute();

            // valid data received in $model_login
            return $this->render('index', [
                    'contentContainer' => $this->contentContainer,
                    'folder' => $currentFolder,
                    'canWrite' => $this->canWrite(),
                    'model' => $model_login]);
        }

        if ($model_u->load(Yii::$app->request->post()))
        {
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

                include_once(__DIR__.'/../models/dbconnect.php');
                $db = dbconnect();
                $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.* 
                                FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p
                                ON d.id=p.onlinedrives_app_detail_id
                                WHERE drive_key = :drive_key', [':drive_key' => $get_dk])->queryAll();
                foreach ($sql as $value) {
                    $drive_path = $value['drive_path'];
                    $app_user_id = $value['app_user_id'];
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

                                $client = $model_u->getScieboClient();

                                if ($client->request('PUT', $path_to_dir, $content)) {
                                    unlink($path);

                                    // Success msg
                                    $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Datei wurde erfolgreich in Sciebo hochgeladen.');
                                }
                            }
                        }
                        else {
                            // Error msg
                            $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Die Berechtigung für diesen Ordner fehlt.');
                        }
                    }
                    elseif ($cloud == 'gd') {
                        //xx
                    }
                }
                else {
                    // Error msg
                    $_REQUEST['error_msg'] = Yii::t('OnlinedrivesModule.new', 'Datei wurde nicht hochgeladen, weil die Berechtigung dazu fehlt.');
                }
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate())
        {
            // valid data received in $model_gd_create
            return $this->render('index', [
                    'contentContainer' => $this->contentContainer,
                    'folder' => $currentFolder,
                    'canWrite' => $this->canWrite(),
                    'model' => $model]);
        }
        // Delete file
        elseif ($model_gd_delete->load(Yii::$app->request->post()) && $model_gd_delete->validate())
        {
                $get_dk = '';
                if (!empty($_GET['dk'])) {
                    $get_dk = $_GET['dk'];
                }

                $gd_client = getGoogleClient($home_url, $guid);
                $gd_service = new Google_Service_Drive($gd_client);

                include_once(__DIR__.'/../models/dbconnect.php');
                $db = dbconnect();
                $sql = $db->createCommand('SELECT d.id AS uid, p.id AS pid, d.*, p.* 
                                FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p
                                ON d.id=p.onlinedrives_app_detail_id
                                WHERE drive_key = :drive_key', [':drive_key' => $get_dk])->queryAll();
                foreach ($sql as $value) {
                    $drive_path = $value['drive_path'];
                    $app_user_id = $value['app_user_id'];
                }

            if (!empty($model_gd_delete->delete_file_id)) {
                $cloud = $model_gd_delete->cloud;
                $delete_file_id = $model_gd_delete->delete_file_id;

                // Sciebo delete function
                if ($cloud == 'sciebo') {
                    $delete_file_id = str_replace(' ', '%20', $delete_file_id);

                    // http://sabre.io/dav/davclient
                    // Will do a DELETE request with a condition
                    $path_to_dir = 'https://uni-siegen.sciebo.de/remote.php/dav/files/'.$app_user_id.'/'.$get_sciebo_path.$delete_file_id;

                    $client = $model_gd_delete->getScieboClient();

                    // Rework
                    $path_to_dir = str_replace(' ', '%20', $path_to_dir);

                    if ($client->request('DELETE', $path_to_dir, null)) {
                        // Success msg
                        $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Löschung aus Sciebo war erfolgreich.');
                    }
                }
                // GD delete function
                elseif ($cloud == 'gd') {
                    $gd_service->files->delete($delete_file_id);

                    // Success msg
                    $_REQUEST['success_msg'] = Yii::t('OnlinedrivesModule.new', 'Löschung aus Google Drive war erfolgreich.');
                }
            }

            // valid data received in $model_gd_delete
            return $this->render('index', [
                    'contentContainer' => $this->contentContainer,
                    'folder' => $currentFolder,
                    'canWrite' => $this->canWrite(),
                    'model_gd_delete' => $model_gd_delete]);
        }
        else
        {
            // either the page is initially displayed or there is some validation error
            return $this->render('index', [
                    'contentContainer' => $this->contentContainer,
                    'folder' => $currentFolder,
                    'canWrite' => $this->canWrite()]);
        }
    }

    public function actionDownloader()
    {
        $home_url = Url::base('http');

        // Sciebo params

        $currentFolder = $this->getCurrentFolder();
        if (!$currentFolder->content->canView())
        {
            throw new HttpException(403);
        }

        if(isset(Yii::$app->user->identity->username)) {
            $username = Yii::$app->user->identity->username;
            //$email = Yii::$app->user->identity->email;

            return $this->render('downloader', [
                'contentContainer' => $this->contentContainer,
                'folder' => $currentFolder,
                'canWrite' => $this->canWrite(),
            ]);
        }
        else{

            header("Location: http://localhost/humhub-uni/");
        }

        // either the page is initially displayed or there is some validation error

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
                    'foldersOrder' => $foldersOrder
        ]);
    }

}
