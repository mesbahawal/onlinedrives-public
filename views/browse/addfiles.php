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

// General vars
$now = time();
$home_url = Url::base(true);

$bundle = \humhub\modules\onlinedrives\assets\Assets::register($this);

if (!empty($_GET['app_detail_id'])) {
    $app_detail_id =  $_GET['app_detail_id'];
}

if (!empty($_GET['cguid'])) {
    $guid = 'cguid=' . $_GET['cguid']; // Get param, important for paths
}

$app_user_id = '';
$get_sciebo_path = '';
$if_shared = '';
$app_password = '';
$drive_path = '';
$get_gd_folder_id = '';
$get_gd_folder_name = '';

// Sciebo params
if (!empty($_GET['sciebo_path'])) {
    $get_sciebo_path = $_GET['sciebo_path'];
}
elseif (!empty($_POST['sciebo_path'])) {
    $get_sciebo_path = $_POST['sciebo_path'];
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

echo Html::beginForm(null, null, ['data-target' => '#globalModal', 'id' => 'onlinedrives-form']);
?>

<div id="onlinedrives-container" class="panel panel-default onlinedrives-content main_div_container">

    <div class="panel-body">

        <!-- Breadcrumb navigation -->
        <div style="border: 1px solid #f0f0f0; border-radius: 10px; padding: 10px; background-color: #f5f5f5;">
            <?php
            $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2faddfiles&'.$guid ;
            if ($cloud == 'sciebo') {
                $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Faddfiles&'.$guid.'&app_detail_id='.$app_detail_id.'&sciebo_path=';
            }
            echo '<a href="'.$ref.'">' . Yii::t('OnlinedrivesModule.new', 'Location:') . '</a>';

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
                                        FROM onlinedrives_app_detail d LEFT OUTER JOIN onlinedrives_app_drive_path_detail p
                                        ON d.id=p.onlinedrives_app_detail_id
                                        WHERE drive_key = :drive_key', [':drive_key' => $get_dk])->queryAll();
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
                    $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Faddfiles&'.$guid.'&app_detail_id='.$app_detail_id.'&sciebo_path=' . urlencode($path);
                    $navi .= ' / <a href="'.$ref.'">'.$name.'</a>';
                } while ($temp != '');

                // Output rest of Sciebo navigation
                echo $navi;
            }
            // Output GD navigation
            elseif ($get_gd_folder_id != '') {
                // Build GD icon for navigation
                $ref = 'https://accounts.google.com/ServiceLogin';
                $src = 'protected/modules/onlinedrives/resources/gd20.png';

                // Output GD icon in navigation
                echo ' /
                <a href="'.$ref.'" target="_blank">
                    <img src="'.$src.'" style="position: relative; top: -2px;" title="Google Drive" />
                </a>';

                // Build rest of GD navigation
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
                    $ref = $home_url.'/index.php?r=onlinedrives%2Fbrowse&%2Faddfiles&'.$guid.'&gd_folder_id='.$id.'&gd_folder_name='.$name;
                    $navi = ' / <a href="'.$ref.'">'.$name.'</a>'.$navi;

                    // Change search name for next loop
                    $check_id = $parents[0]; // Change parent folder ID to check
                    if ($check_id != '0AESKNHa25CPzUk9PVA') { // Means root
                        $file = $gd_service->files->get($check_id);
                        $check_name = $file->getName(); // Change folder name to check
                    }
                } while ($check_id != '0AESKNHa25CPzUk9PVA'); // Means root

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

    if ($count_sciebo_files > 0) {
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
                    User permissions
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
                                    document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[0].checked = true;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[1].checked = true;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[2].checked = true;

                                    if (document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[3]) {
                                        document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[3].checked = true;
                                    }';
                                    ?>
                                }
                                else {
                                    <?php
                                    echo 'document.getElementsByName(\'AddFilesForm[drive_path]['.$i.'][]\')[0].checked = false;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[0].checked = false;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[1].checked = false;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[2].checked = false;

                                    if (document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[3]) {
                                        document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[3].checked = false;
                                    }';
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
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked = true;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[1].checked = true;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[2].checked = true;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[3].checked = true;';
                                    ?>
                                }
                                else {
                                    <?php
                                    echo 'document.getElementsByName(\'AddFilesForm[drive_path]['.$checkbox_index.'][]\')[0].checked = false;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked = false;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[1].checked = false;
                                    document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[2].checked = false;';
                                    ?>
                                }
                            <?php
                            }
                            ?>
                    " />
                    Select all
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

            if ($cloud == 'sciebo' ||                    // Sciebo
                $cloud == 'gd' &&                        // Google Drive
                ($parents[0] == '0AESKNHa25CPzUk9PVA' || // Root ID of Google Drive
                    $parents[0] == '' ||                 // Shared files of Google Drive
                    $get_gd_folder_id != '')             // Folder ID of Google Drive
            ) {
                $no++;

                // Modified time (folders)
                $diff = $now - $modified_time;
                if ($diff < 2) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'just now');
                }
                elseif ($diff < 60) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Sekunden', ['diff' => $diff]);
                }
                elseif ($diff < 3600) { // 60s * 60m
                    $diff = floor($diff / 60);
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Minute', ['diff' => $diff]);
                }
                elseif ($diff < 86400) { // 60s * 60m * 24h
                    $diff = floor($diff / 3600); // 60s * 60m
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Stunde', ['diff' => $diff]);
                }
                elseif ($diff < 604800) { // 60s * 60m * 24h * 7d
                    $diff = floor($diff / 86400); // 60s * 60m * 24h
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Tag', ['diff' => $diff]);
                }
                elseif ($diff < 2678400) { // 60s * 60m * 24h * 31d
                    $diff = floor($diff / 604800); // 60s * 60m * 24h * 7d
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Woche', ['diff' => $diff]);
                }
                else {
                    $diff = floor($diff / 2678400); // 60s * 60m * 24h * 31d
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
                $time_title = 'Modified time: '.$modified_time_txt_exact . "\n" .
                'Creation time: '.$created_time_txt . "\n";

                // Check which boxes are selected
                $permission = '';
                $sql = $db->createCommand('SELECT * FROM onlinedrives_app_drive_path_detail
                    WHERE drive_path = :drive_path AND onlinedrives_app_detail_id = :app_detail_id', [
                    ':drive_path' => $path,
                    ':app_detail_id' => $app_detail_id,
                ])->queryAll();
                foreach ($sql as $value) {
                    $permission = $value['permission'];
                }

                if ($permission != '') {
                    $pos_rename = strpos($permission, 'Rn');
                    $pos_move = strpos($permission, 'Mv');
                    $pos_del = strpos($permission, 'D');
                    $pos_upl = strpos($permission, 'U');
                    if ($pos_rename !== false || $pos_move !== false || $pos_del !== false || $pos_upl !== false) {
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
                        $path_chunk = str_replace($sciebo_path_to_replace, '', $path);
                        if ($pos_read === true) {
                            $model_addfiles->drive_path[] = urlencode($path_chunk);
                        }
                        echo $form_addfiles->field($model_addfiles, 'drive_path['.$i.']')->checkboxList([
                            urlencode($path_chunk) => '',
                        ], [
                            'onchange' => 'checked = document.getElementsByName(\'AddFilesForm[drive_path]['.$i.'][]\')[0].checked;

                                if (checked == true) {
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[0].checked = true;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[1].checked = true;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[2].checked = true;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[3].checked = true;
                                }
                                else {
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[0].checked = false;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[1].checked = false;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[2].checked = false;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[3].checked = false;
                                }'
                        ]);
                    echo '</td>

                    <td>';
                        $span_folder_icon = '<span class="glyphicon glyphicon-folder-close" style="margin-right: 10px;"></span>';
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
                            $url = $home_url.'/index.php?r=onlinedrives%2Fbrowse%2Faddfiles&'.$guid.'&gd_folder_id='.$id.'&gd_folder_name='.$name;
                            echo $span_fav_icon.'<a href="'.$url.'">'.$span_folder_icon.' '.$name.'</a>';
                        }
                    echo '</td>

                    <td>';
                        if ($pos_rename !== false && $pos_move !== false && $pos_del !== false && $pos_upl !== false) {
                            $model_addfiles->permission[] = ['Rn', 'Mv', 'D', 'U'];
                        }
                        elseif ($pos_rename !== false && $pos_move !== false && $pos_del !== false) {
                            $model_addfiles->permission[] = ['Rn', 'Mv', 'D'];
                        }
                        elseif ($pos_rename !== false && $pos_move !== false && $pos_upl !== false) {
                            $model_addfiles->permission[] = ['Rn', 'Mv', 'U'];
                        }
                        elseif ($pos_rename !== false && $pos_del !== false && $pos_upl !== false) {
                            $model_addfiles->permission[] = ['Rn', 'D', 'U'];
                        }
                        elseif ($pos_move !== false && $pos_del !== false && $pos_upl !== false) {
                            $model_addfiles->permission[] = ['Mv', 'D', 'U'];
                        }
                        elseif ($pos_rename !== false && $pos_move !== false) {
                            $model_addfiles->permission[] = ['Rn', 'Mv'];
                        }
                        elseif ($pos_rename !== false && $pos_upl !== false) {
                            $model_addfiles->permission[] = ['Rn', 'U'];
                        }
                        elseif ($pos_rename !== false && $pos_del !== false) {
                            $model_addfiles->permission[] = ['Rn', 'D'];
                        }
                        elseif ($pos_del !== false && $pos_upl !== false) {
                            $model_addfiles->permission[] = ['D', 'U'];
                        }
                        elseif ($pos_move !== false && $pos_del !== false) {
                            $model_addfiles->permission[] = ['Mv', 'D'];
                        }
                        elseif ($pos_move !== false && $pos_upl !== false) {
                            $model_addfiles->permission[] = ['Mv', 'U'];
                        }
                        elseif ($pos_rename !== false) {
                            $model_addfiles->permission[] = ['Rn'];
                        }
                        elseif ($pos_move !== false) {
                            $model_addfiles->permission[] = ['Mv'];
                        }
                        elseif ($pos_del !== false) {
                            $model_addfiles->permission[] = ['D'];
                        }
                        elseif ($pos_upl !== false) {
                            $model_addfiles->permission[] = ['U'];
                        }
                        echo $form_addfiles->field($model_addfiles, 'permission['.$i.']')->checkboxList([
                            'Rn' => 'Rename',
                            'Mv' => 'Move',
                            // 'C' => 'Copy',
                            'D' => 'Delete',
                            'U' => 'Upload',
                        ], [
                            'onchange' => 'checked_0 = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[0].checked;
                                checked_1 = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[1].checked;
                                checked_2 = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[2].checked;
                                checked_3 = document.getElementsByName(\'AddFilesForm[permission]['.$i.'][]\')[3].checked;

                                if (checked_0 == true || checked_1 == true || checked_2 == true || checked_3 == true) {
                                    document.getElementsByName(\'AddFilesForm[drive_path]['.$i.'][]\')[0].checked = true;
                                }
                                else {
                                    document.getElementsByName(\'AddFilesForm[drive_path]['.$i.'][]\')[0].checked = false;
                                }'
                        ]);
                    echo '</td>

                    <td>
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

            if ($cloud == 'sciebo' ||                    // Sciebo
                $cloud == 'gd' &&                        // Google Drive
                ($parents[0] == '0AESKNHa25CPzUk9PVA' || // Root ID of Google Drive
                    $parents[0] == '' ||                 // Shared files of Google Drive
                    $get_gd_folder_id != '')             // Folder ID of Google Drive
            ) {
                $no++;

                // Modified time (files)
                $diff = $now - $modified_time;
                if ($diff < 2) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'just now');
                }
                elseif ($diff < 60) {
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Sekunde', ['diff' => $diff]);
                }
                elseif ($diff < 3600) { // 60s * 60m
                    $diff = floor($diff / 60);
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Minute', ['diff' => $diff]);
                }
                elseif ($diff < 86400) { // 60s * 60m * 24h
                    $diff = floor($diff / 3600); // 60s * 60m
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Stunde', ['diff' => $diff]);
                }
                elseif ($diff < 604800) { // 60s * 60m * 24h * 7d
                    $diff = floor($diff / 86400); // 60s * 60m * 24h
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Tag', ['diff' => $diff]);
                }
                elseif ($diff < 2678400) { // 60s * 60m * 24h * 31d
                    $diff = floor($diff / 604800); // 60s * 60m * 24h * 7d
                    $modified_time_txt = Yii::t('OnlinedrivesModule.new', 'vor {diff} Woche', ['diff' => $diff]);
                }
                else {
                    $diff = floor($diff / 2678400); // 60s * 60m * 24h * 31d
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
                }
                // echo $mime_type_icon;

                $img = '<img src="protected/modules/onlinedrives/resources/type/'.$icon.'.png" alt="'.'" title="'.'" style="margin-right: 10px;" />';

                // Check which boxes are selected
                $permission = '';
                $sql = $db->createCommand('SELECT * FROM onlinedrives_app_drive_path_detail
                    WHERE drive_path = :drive_path AND onlinedrives_app_detail_id = :app_detail_id', [
                    ':drive_path' => $path,
                    ':app_detail_id' => $app_detail_id,
                ])->queryAll();
                foreach ($sql as $value) {
                    $permission = $value['permission'];
                }

                if ($permission != '') {
                    $pos_rename = strpos($permission, 'Rn');
                    $pos_move = strpos($permission, 'Mv');
                    $pos_del = strpos($permission, 'D');
                    if ($pos_rename !== false || $pos_move !== false || $pos_del !== false) {
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
                        $path_chunk = str_replace($sciebo_path_to_replace, '', $path);
                        if ($pos_read === true) {
                            $model_addfiles->drive_path[] = urlencode($path_chunk);
                        }
                        echo $form_addfiles->field($model_addfiles, 'drive_path['.$checkbox_index.']')->checkboxList([
                            urlencode($path_chunk) => '',
                        ], [
                            'onchange' => 'checked = document.getElementsByName(\'AddFilesForm[drive_path]['.$checkbox_index.'][]\')[0].checked;

                                if (checked == true) {
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked = true;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[1].checked = true;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[2].checked = true;
                                }
                                else {
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[0].checked = false;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[1].checked = false;
                                    checked = document.getElementsByName(\'AddFilesForm[permission]['.$checkbox_index.'][]\')[2].checked = false;
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
                            $model_addfiles->permission[] = ['Rn', 'Mv', 'D'];
                        }
                        elseif ($pos_rename !== false && $pos_move !== false) {
                            $model_addfiles->permission[] = ['Rn', 'Mv'];
                        }
                        elseif ($pos_rename !== false && $pos_del !== false) {
                            $model_addfiles->permission[] = ['Rn', 'D'];
                        }
                        elseif ($pos_move !== false && $pos_del !== false) {
                            $model_addfiles->permission[] = ['Mv', 'D'];
                        }
                        elseif ($pos_rename !== false) {
                            $model_addfiles->permission[] = ['Rn'];
                        }
                        elseif ($pos_move !== false) {
                            $model_addfiles->permission[] = ['Mv'];
                        }
                        elseif ($pos_del !== false) {
                            $model_addfiles->permission[] = ['D'];
                        }

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
                    echo '</td>

                    <td>
                    </td>
                </tr>';
            }
        }
        ?>
        </thead>
    </table>

    <div id="create_btn_login" class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?php
                if ($count_sciebo_files > 0) {
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