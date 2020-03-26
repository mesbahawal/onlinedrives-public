<?php

use yii\helpers\Url;

// std vars
$home_url = Url::base(true);

// default vars
$guid = '';

// get param: cguid
// important for paths
if (!empty($_GET['cguid'])) {
    $guid = $_GET['cguid'];
}

return array (
    'lang' => 'en',

    'Folder is successfully created in Sciebo.' => 'Folder is successfully created in Sciebo.',
    'File is successfully created in Sciebo.' => 'File is successfully created in Sciebo.',
    'Folder is successfully created in Google Drive.' => 'Folder is successfully created in Google Drive.',
    'File is successfully created in Google Drive.' => 'File is successfully created in Google Drive.',
    'Unsharing was successful.' => 'Unsharing was successful.',
    'All drives' => 'All drives',
    'AppID' => 'App ID',
    'Password' => 'Password',
    'Send' => 'Send',
    'Create folder' => 'Create folder',
    'Create file' => 'Create file',
    'Upload file' => 'Upload file',
    'Folder is <b>empty.</b>' => 'Folder is <b>empty.</b>',
    'Properties' => 'Properties',
    'Last modified time' => 'Last modified time',
    'just now' => 'just now',
    '{%diff} seconds ago' => '{%diff} seconds ago',
    '{diff,plural,=1{1 }minute other{# minutes}} ago' => '{diff,plural,=1{1 }minute other{# minutes}} ago',
    '{diff,plural,=1{1 hour} other{# hours}} ago' => '{diff,plural,=1{1 hour} other{# hours}} ago',
    '{diff,plural,=1{1 day} other{# days}} ago' => '{diff,plural,=1{1 day} other{# days}} ago',
    '{diff,plural,=1{1 week} other{# weeks}} ago' => '{diff,plural,=1{1 week} other{# weeks}} ago',
    '{diff,plural,=1{1 month} other{# months}} ago' => '{diff,plural,=1{1 month} other{# months}} ago',
    'Not known' => 'Not known',
    'Rename' => 'Rename',
    'Move' => 'Move',
    'Copy' => 'Copy',
    'Delete' => 'Delete',
    'Confirm' => 'Confirm',
    'File is successfully uploaded in Sciebo.' => 'File is successfully uploaded in Sciebo.',
    'The permission for this folder is missing.' => 'The permission for this folder is missing.',
    'File was not uploaded because the permission is missing.' => 'File was not uploaded because the permission is missing.',
    'Close' => 'Close',
    'Already app user exist.' => 'Already app user exist.',
    'Cloud storage is added successfully.' => 'Cloud storage is added successfully.',
    'JSON file' => 'JSON file',
    'Google Drive client add failed.' => 'Google Drive client add failed.',
    'Google Drive client add failed, because your JSON file is invalid.' => 'Your JSON file is invalid. Please create a new one following the guideline.',
    'An error has occurred during registration. Please try it again.' => 'An error has occurred during registration. Please try it again.',
    'Change permissions' => 'Change permissions',
    'Unshare' => 'Unshare',
    '<b>No data</b> shared.' => '<b>No files</b> shared.',
    'Connected drives' => 'Connected drives',
    'Select files' => 'Select files',
    'User permissions' => 'User permissions',
    'Disable' => 'Disable',
    'Select all' => 'Select all',
    'Save' => 'Save',
    '' => '',

    // Guide headings
    'guide_h' => 'Configuration Guide for Cloud Access',
    'sciebo_guide_h' => 'How to connect with your Sciebo account',
    'gd_guide_h' => 'How to connect with your Google Drive account',

    // Guide for Sciebo
    'sciebo_guide_txt1' => 'Step1: Open <a class="u" href="https://sciebo.de/de/login/index.html" target="_blank">https://sciebo.de/de/login/index.html</a> and click on the link of your university.<br />Log in. Click on your name and on “Settings” in the menu that opens (see orange circle in the screenshot).',
    'sciebo_guide_txt2' => 'Step 2: The overview of your settings will open. Click on “Security” (see orange box in the screenshot).',
    'sciebo_guide_txt3' => 'Step 3: At the bottom of your security settings you will see the item “App passwords / tokens” (see orange circle in the screenshot). In the “App name” space, create a passcode for HumHub by entering “HumHub” (as the example below) and then click “Create new app passcode”.<br />
    Note: The app passcode assigned here isn’t the login credential of your Sciebo account. It is a passcode which allows Research-Hub to connect to your Sciebo account. You can set specific rights to which content gets accessable from Research-Hub. These rights can be adjusted in the further course of the setup.',
    'sciebo_guide_txt4' => 'Step 4: The new app access data created in this way is now displayed and can be used. Copy the values for user name and password/token one after the other.',
    'sciebo_guide_txt5' => 'Step 5: Paste them into the access data form in OnlineDrives (it can be accessed) via the burger icon ([class=glyphicon glyphicon-menu-hamburger][/class]) in the upper right corner).</p>
        <p>During this step, it is best to leave the page with the app access data open, as the access data is no longer visible after reloading the page. However, you can create new app access data at any time, as well as delete the old ones.</p>
        <p>In the form you can then click on the service (Sciebo in this case) and enter the corresponding access data.</p>',
    'sciebo_guide_txt6' => 'Step 6: After a successful connection, a note about the connected services appears in the upper part of the module. (See green box: “Cloud storage is added sccuessfully”.) With “Select files” (orange box) you can now select directories and files (from your Sciebo account) that you want to share with the members of this space. By clicking on “Disable” you can terminate the connection at any time.',
    'sciebo_guide_txt7' => 'After clicking on “Select files”, you will have the overview of your complete Sciebo account. You can select or deselect files/folders and give other users of the Space permission to upload files to the directories you have shared. In doing so, the data is synchronized, not just copied. But the selection can be adjusted at any time.<br />
        With a click on “Save” the settings are then applied.',
    'sciebo_guide_txt8' => 'Step 7: The following screenshot shows an example list of shared files after saving the form from step 6. This list is only visible to the members of this space.',

    // Guide for Google Drive
    'gd_guide_txt1' => 'Step 1: Please go to <a class="u" href="https://console.developers.google.com" target="_blank">https://console.developers.google.com</a> and log in there with the access data of your Google account.',
    'gd_guide_txt2' => 'Step 2: Now you have to select a project in which you want to use the access to Research-Hub or create a new one. To do so, click on “Select a project” or the name of the current project in the upper left corner.',
    'gd_guide_txt3' => 'Step 3: In the next window, select one of the existing projects and continue with step 5.',
    'gd_guide_txt4' => 'To create a new project, click on “New Project”.',
    'gd_guide_txt5' => 'Step 4: Enter a project name (e.g. OnlineDrives) and click on “Create”. Your project will now be created.',
    'gd_guide_txt6' => 'Step 5: If the following message appears in the middle after you select or create the project, click “Enable APIs and Services” at the top. If you’ve already activated the Google Drive API, you can go directly to step 9.',
    'gd_guide_txt7' => 'Step 6: In the Api library, scroll to the “Google Drive API” button and click it.',
    'gd_guide_txt8' => 'Step 7: Then click on “Enable”.',
    'gd_guide_txt9' => 'Step 8: Click on “Google APIs” on the left side to get back to the dashboard.',
    'gd_guide_txt10' => 'Step 9: In the dashboard on the left side, click on “Credendtials”.',
    'gd_guide_txt11' => 'Step 10: Then click on “Configure Consent Screen”.',
    'gd_guide_txt12' => 'Step 11: In the next window select “External” and click on create.',
    'gd_guide_txt13' => 'Step 12: Now a form opens in which you have to adjust two points:
    <ul><li>Please assign a name under “Application name” (e.g. OnlineDrives).</li>
    <li>By “Authorized domains” please enter “research-hub.social” and confirm with the  enter key.</li></ul>',
    'gd_guide_txt14' => 'Then save the entries by clicking on “Save” at the very bottom.',
    'gd_guide_txt15' => 'Step 13: Click on “Credentials” again.',
    'gd_guide_txt16' => 'Step 14: Click on “Create Credentials” and select “OAuth client ID”.',
    'gd_guide_txt17' => 'Step 15: Now select “Web application” as the application type. Enter a name below (e.g. OnlineDrives). Under “Authorized redirect URIs” enter <i>“'.$home_url.'/index.php?r=onlinedrives%2Fbrowse&cguid='.$guid.'”</i> and confirm with the enter key. Finally, click “Create” at the bottom of the page.',
    'gd_guide_txt18' => 'Step 16: Your access data will now be displayed. Confirm these with OK.',
    'gd_guide_txt19' => 'Step 17: Now click on the download icon to the far right of the client you just created. This will download the information as a .json file, which you can save on your PC.',
    'gd_guide_txt20' => 'Step 18: Uploading the .json file to Research-Hub will establish a connection to your Google Drive files. Click on the Burger menu in the upper left corner.',
    'gd_guide_txt21' => 'Step 19: Click on the Google Drive symbol in the dialag window that opens. Then enter an app ID, which you can choose freely. Then select your .json file and click on “Send”.',
    'gd_guide_txt22' => 'Step 20: You will be redirected to the login screen for Google. Please log in. If you are already logged in, you can click on your desired account here.',
    'gd_guide_txt23' => 'Step 21: Click on “Advanced” at the bottom left.',
    'gd_guide_txt24' => 'Step 22: Click on “Go to research-hub.social (unsafe)” at the bottom left.',
    'gd_guide_txt25' => 'Step 23: Click on “Allow” at the bottom left.',
    'gd_guide_txt26' => 'Step 24: Your Google Drive account is now connected to Research-Hub.',
    'gd_guide_txt27' => 'Step 25: Click “Connected drives” to view your Google Drive account that you just connected.',
);
?>