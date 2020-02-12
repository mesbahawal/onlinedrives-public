<?php
return array (
    'lang' => 'en',

    'Folder is successfully created in Sciebo.' => 'Folder is successfully created in Sciebo.',
    'File is successfully created in Sciebo.' => 'File is successfully created in Sciebo.',
    'Folder is successfully created in Google Drive.' => 'Folder is successfully created in Google Drive.',
    'File is successfully created in Google Drive.' => 'File is successfully created in Google Drive.',
    'Unsharing was successful.' => 'Unsharing was successful.',
    'All drives' => 'All drives',
    'AppID' => 'AppID',
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
    'sciebo_guide_txt1' => 'Step1: Open <a class="u" href="https://sciebo.de/de/login/index.html" target="_blank">https://sciebo.de/de/login/index.html</a> and click on the link of your university/college.<br />Log in. Click on your name and on “Settings” in the menu that opens (see orange circle in the screenshot).',
    'sciebo_guide_txt2' => 'Step 2: The overview of your settings will open. Click on “Security” (see orange box in the screenshot).',
    'sciebo_guide_txt3' => 'Step 3: At the bottom of your security settings is the item “App passwords / tokens” (see orange circle in the screenshot).<br />
        At this point, it should be emphasized that the app passcode assigned here does not allow full access to your account, but only specific rights can be assigned. These rights can be adjusted in the further course of the setup.<br />
        To create a passcode for HumHub, enter “HumHub” as the name below and click “Create new app passcode”.',
    'sciebo_guide_txt4' => '<p>Step 4: The new app access data created in this way is now displayed and can be used. Copy the values for user name and password/token one after the other into the access data form in OnlineDrives. This is shown in the next screenshot and can be accessed via the small gear wheel in the upper right corner.</p>
        <p>During this step, it is best to leave the page with the app access data open, as the access data is no longer visible after reloading the page. However, you can create new app access data at any time, as well as delete the old ones.</p>
        <p>In the form you can then click on the service (Sciebo or Google Drive) and enter the corresponding access data.</p>',
    'sciebo_guide_txt5' => 'Step 5: After a successful connection, a note about the connected services appears in the upper part of the module.<br />
        With “Select files” you can now select directories and files of your Sciebo account that you want to share with the members of this space. By clicking on “Disable” you can terminate the connection at any time.',
    'sciebo_guide_txt6' => 'After clicking on “Select files” the overview shown below will appear, where you can select or deselect files and folders and give other users of the Space permission to upload files and folders to the folders you have shared. In doing so, the data is synchronized, not just copied. But the selection can be adjusted at any time.<br />
        With a click on “Save” the settings are then applied.',
    'sciebo_guide_txt7' => 'Step 6: The following screenshot shows an example list of shared files after saving the form from step 5. This list is only visible to the members of this space.',
    'sciebo_guide_txt8' => '',

    // Guide for Google Drive
    'gd_guide_txt1' => 'Step 1: Please go on <a class="u" href="https://console.developers.google.com" target="_blank">https://console.developers.google.com</a> and log in with your Google account access data.',
    'gd_guide_txt2' => 'Step 2: If you have not yet created a project in your account, you will see view A after you log in. Click on “Select a project” to select or change a project. If a project already exists, you will see view B after your login. If you are already in your desired project, continue with step 5.',
    'gd_guide_txt3' => 'Step 3: To create a new project, click on “New Project”.',
    'gd_guide_txt4' => 'Step 4: Select a project name (e.g. OnlineDrives) and click on “Create”. Your project is now created.',
    'gd_guide_txt5' => 'Step 5: Click on “Enable APIs and services” when prompted. Otherwise continue with step 10.',
    'gd_guide_txt6' => 'Step 6: Scroll to the “Google Drive API” button and click it.',
    'gd_guide_txt7' => 'Step 7: Click on “Enable”.',
    'gd_guide_txt8' => 'Step 8: Click on “Credentials” on the left side.',
    'gd_guide_txt9' => 'Step 9: Click on “Credentials in APIs & Services”',
    'gd_guide_txt10' => 'Step 10: Click on “Configure Consent Screen”.',
    'gd_guide_txt11' => 'Step 11: Click on “External”.',
    'gd_guide_txt12' => 'Step 12: Select an application name (e.g. OnlineDrives). Enter “research-hub.social” under “Authorized domains” and confirm with Enter. Then click on “Save”.',
    'gd_guide_txt13' => 'Step 13: Click on “Credentials” again.',
    'gd_guide_txt14' => 'Step 14: Click on “Create Credentials” and select “OAuth client ID”.',
    'gd_guide_txt15' => 'Step 15: Select “Web application” as the application type. Enter a name (for example, OnlineDrives). Under “Authorized redirect URIs” enter “http://research-hub.social” and confirm with Enter. Then click on “Create”.',
    'gd_guide_txt16' => 'Step 16: You will now see your access data. Click on OK.',
    'gd_guide_txt17' => 'Step 17: Now click on the download icon on the far right behind the Client ID you just created.',
);
?>