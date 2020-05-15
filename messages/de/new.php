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
    'lang' => 'de',

    'Folder is successfully created in Sciebo.' => 'Ordner wurde erfolgreich in Sciebo erstellt.',
    'File is successfully created in Sciebo.' => 'Datei wurde erfolgreich in Sciebo erstellt.',
    'Folder is successfully created in Google Drive.' => 'Ordner wurde erfolgreich in Google Drive erstellt.',
    'File is successfully created in Google Drive.' => 'Datei wurde erfolgreich in Google Drive erstellt.',
    'Unsharing was successful.' => 'Die Freigabe wurde erfolgreich aufgehoben.',
    'All drives' => 'Alle Drives',
    'AppID' => 'App-ID',
    'Password' => 'Passwort',
    'Send' => 'Absenden',
    'Create folder' => 'Ordner erstellen',
    'Create file' => 'Datei erstellen',
    'Upload file' => 'Datei hochladen',
    'Folder is <b>empty.</b>' => 'Ordner ist <b>leer.</b>',
    'Properties' => 'Eigenschaften',
    'Last modified time' => 'Letzte Änderung',
    'just now' => 'gerade eben',
    'a few seconds ago' => 'vor einigen Sekunden',
    '{diff,plural,=1{1 }minute other{# minutes}} ago' => 'vor {diff,plural,=1{1 Minute} other{# Minuten}}',
    '{diff,plural,=1{1 hour} other{# hours}} ago' => 'vor {diff,plural,=1{1 Stunde} other{# Stunden}}',
    '{diff,plural,=1{1 day} other{# days}} ago' => 'vor {diff,plural,=1{1 Tag} other{# Tage}}',
    '{diff,plural,=1{1 week} other{# weeks}} ago' => 'vor {diff,plural,=1{1 Woche} other{# Wochen}}',
    '{diff,plural,=1{1 month} other{# months}} ago' => 'vor {diff,plural,=1{1 Monat} other{# Monaten}}',
    'Not known' => 'Nicht bekannt',
    'Rename' => 'Umbenennen',
    'Move' => 'Verschieben',
    'Copy' => 'Kopieren',
    'Delete' => 'Löschen',
    'Confirm' => 'Bestätigen',
    'File is successfully uploaded in Sciebo.' => 'Datei wurde erfolgreich in Sciebo hochgeladen.',
    'The permission for this folder is missing.' => 'Die Berechtigung für diesen Ordner fehlt.',
    'File was not uploaded because the permission is missing.' => 'Die Datei wurde nicht hochgeladen, weil die Berechtigung dazu fehlt.',
    'Close' => 'Schließen',
    'Already app user exist.' => 'App-User existiert bereits.',
    'Cloud storage is added successfully.' => 'Der Cloud-Service wurde erfolgreich hinzugefügt.',
    'JSON file' => 'JSON-Datei',
    'Google Drive client add failed.' => 'Der Google-Drive-Client wurde nicht hinzugefügt.',
    'Google Drive client add failed, because your JSON file is invalid.' => 'Ihre JSON-Datei ist ungültig. Bitte erstellen Sie eine neue Datei wie in der Konfigurationsanleitung beschrieben.',
    'An error has occurred during registration. Please try it again.' => 'Bei der Registration ist ein Fehler aufgetreten. Bitte versuche es noch einmal.',
    'Change permissions' => 'Berechtigungen ändern',
    'Unshare' => 'Freigabe aufheben',
    '<b>No data</b> shared.' => 'Es wurden noch <b>keine Daten geteilt.</b>',
    'Connected drives' => 'Verbundene Drives',
    'Select files' => 'Dateien auswählen',
    'Disable' => 'Deaktivieren',
    'Select all' => 'Alle auswählen',
    'User permissions' => 'Berechtigungen der Benutzer',
    'Save' => 'Speichern',
    '' => '',

    // Guide headings
    'guide_h' => 'Konfigurationsanleitung für den Cloudzugang ',
    'sciebo_guide_h' => 'Wie Sie sich mit Ihrem Sciebo-Konto verbinden',
    'gd_guide_h' => 'Wie Sie sich mit Ihrem Google-Drive-Konto verbinden',

    // Guide for Sciebo
    'sciebo_guide_txt1' => 'Schritt 1: Öffnen Sie <a class="u" href="https://sciebo.de/de/login/index.html" target="_blank">https://sciebo.de/de/login/index.html</a> und klicken Sie auf den Link Ihrer Universität/Hochschule.<br />Loggen Sie sich ein. Klicken Sie auf Ihren Namen und im sich öffnenden Menü auf „Einstellungen“ (siehe oranger Kreis im Screenshot).',
    'sciebo_guide_txt2' => 'Schritt 2: Die Überischt über Ihre Einstellungen wird geöffnet. Klicken Sie auf „Sicherheit“ (siehe orange Box im Screenshot).',
    'sciebo_guide_txt3' => 'Schritt 3: Unter Ihren Sicherheits-Einstellungen befindet sich ganz unten der Punkt „App-Passwörter / Token“ (siehe oranger Kreis im Screenshot).<br />
    	An dieser Stelle ist zu betonen, dass der hier vergebene App-Passcode keinen Vollzugriff auf Ihr Konto ermöglicht, sondern nur gezielt Rechte vergeben werden können. Diese Rechte können im weiteren Verlauf der Einrichtung angepasst werden.<br />
    	Um nun für HumHub einen Passcode zu erstellen, geben Sie unten bspw. „HumHub“ als Name ein und klicken dann auf „Neuen App-Passcode erstellen“.',
    'sciebo_guide_txt4' => 'Schritt 4: Die so neu erstellten App-Zugangsdaten werden jetzt angezeigt und können verwendet werden. Kopieren Sie die Werte für Benutzernamen und Passwort/Token nacheinander in das Zugangsdaten-Formular in OnlineDrives.',
    'sciebo_guide_txt5' => '<p>Schritt 5: Das Zugangsdaten-Formular ist im nächsten Screenshot zu sehen und lässt sich über das Burger-Icon ([class=glyphicon glyphicon-menu-hamburger][/class]) oben rechts aufrufen.</p>
        <p>Während dieses Schrittes sollten Sie die Seite mit den App-Zugangsdaten am besten geöffnet lassen, da die Zugangsdaten nach einem Reload der Seite nicht mehr sichtbar sind. Sie können sich aber jederzeit neue App-Zugangsdaten erstellen, sowie die alten löschen.</p>
        <p>Im Formular kann man dann den Dienst (in diesem Fall Sciebo) anklicken und die entsprechenden Zugangsdaten eintragen.</p>',
    'sciebo_guide_txt6' => 'Schritt 6: Nach erfolgter Verbindung erscheint im obigen Bereich des Moduls ein Hinweis über die verbundenen Dienste.<br />
    	Über „Dateien auswählen“ können Sie nun Verzeichnisse und Dateien Ihres Sciebo-Kontos auswählen, die Sie mit den Mitgliedern dieses Spaces teilen möchten. Mit einem Klick auf „Deaktivieren“ können Sie die Verbindung zu jeder Zeit wieder beenden.',
    'sciebo_guide_txt7' => 'Nach einem Klick auf „Select files“ erscheint die unten dargestellte Übersicht, in der Sie Dateien und Ordner an- oder abwählen können, sowie anderen Nutzern des Spaces die Berechtigung erteilen können auch selbst Dateien und Ordner in die von Ihnen freigegebenen Ordner hochzuladen. Dabei werden die Daten synchronisiert, nicht nur kopiert. Die Auswahl kann aber jederzeit angepasst werden.<br />
    	Mit einem Klick auf „Speichern“ werden dann die Einstellungen übernommen.',
    'sciebo_guide_txt8' => 'Schritt 7: Im folgenden Screenshot sehen Sie eine beispielhafte Liste geteilter Dateien nach dem Speichern des Formulars aus Schritt 6. Diese Liste ist ausschließlich für die Mitglieder dieses Spaces einsehbar.',

    // Guide for Google Drive
    'gd_guide_txt1' => 'Schritt 1: Bitte gehen Sie auf <a class="u" href="https://console.developers.google.com" target="_blank">https://console.developers.google.com</a> und loggen Sie sich dort mit den Zugangsdaten Ihres Google-Kontos ein.',
    'gd_guide_txt2' => 'Schritt 2: Nun müssen Sie ein Projekt auswählen, in welchem Sie den Zugang zu Research-Hub nutzen möchten, oder ein neues erstellen. Dazu klicken sie oben links auf Projekt auswählen bzw. den Namen des aktuellen Projekts.',
    'gd_guide_txt3' => 'Schritt 3: Im nächsten Fenster wählen Sie dann eines der bereits vorhandenen Projekte aus und machen dann bei Schritt 5 weiter.',
    'gd_guide_txt4' => 'Um ein neues Projekt zu erstellen, klicken Sie auf „Neues Projekt“.',
    'gd_guide_txt5' => 'Schritt 4: Geben Sie einen Projektnamen (z. B. OnlineDrives) ein und klicken Sie auf „Erstellen“. Ihr Projekt wird nun erstellt.',
    'gd_guide_txt6' => 'Schritt 5: Wird nach der Auswahl oder dem Erstellen des Projekts in der Mitte folgende Meldung angezeigt, dann klicken Sie oben auf „APIs und Dienste aktivieren“. Sollten Sie die Google-Drive-API bereits aktiviert haben, können Sie direkt zu Schritt 9 springen.',
    'gd_guide_txt7' => 'Schritt 6: In der API-Bibliothek scrollen Sie zur Schaltfläche „Google Drive API“ und klicken darauf.',
    'gd_guide_txt8' => 'Schritt 7: Anschließend klicken Sie auf „Aktivieren“.',
    'gd_guide_txt9' => 'Schritt 8: Klicken Sie auf der linken Seite auf „Google APIs“ um zurück zum Dashboard zu gelangen.',
    'gd_guide_txt10' => 'Schritt 9: Klicken Sie im Dashboard auf der linken Seite auf „Anmeldedaten“.',
    'gd_guide_txt11' => 'Schritt 10: Klicken Sie dann auf „Zustimmungsbildschirm konfigurieren“.',
    'gd_guide_txt12' => 'Schritt 11: Im nächsten Fenster wählen Sie „Extern“ aus und klicken auf erstellen.',
    'gd_guide_txt13' => 'Schritt 12: Nun öffnet sich ein Formular, in welchem Sie zwei Punkte anpassen müssen:
    <ul><li>Unter „Name der Anwendung“ vergeben Sie bitte einen Namen (z. B. OnlineDrives).</li>
    <li>Unter „Autorisierte Domains“ geben Sie bitte „research-hub.social“ ein und drücken die Enter-Taste.</li></ul>',
    'gd_guide_txt14' => 'Anschließend speichern Sie die Eingaben, indem Sie ganz unten auf „Speichern“ klicken.',
    'gd_guide_txt15' => 'Schritt 13: Klicken Sie erneut auf „Anmeldedaten“.',
    'gd_guide_txt16' => 'Schritt 14: Klicken Sie auf „Anmeldedaten erstellen“ und wählen Sie „OAuth-Client-ID“.',
    'gd_guide_txt17' => 'Schritt 15: Wählen Sie nun als Anwendungstyp „Webanwendung“ aus. Geben Sie weiter unten einen Namen an (z. B. OnlineDrives). Unter „Autorisierte Weiterleitungs-URIs“ geben Sie <i>„'.$home_url.'/index.php?r=onlinedrives%2Fbrowse&cguid='.$guid.'“</i> ein und bestätigen die Eingabe mit der Enter-Taste. Zuletzt klicken Sie ganz unten auf „Erstellen“.',
    'gd_guide_txt18' => 'Schritt 16: Ihnen werden nun Ihre Zugangsdaten angezeigt. Diese bestätigen Sie mit OK.',
    'gd_guide_txt19' => 'Schritt 17: Klicken Sie nun auf das Download-Symbol ganz rechts neben dem gerade angelegten Client. Damit laden Sie die Informationen als .json-Datei herunter, die Sie auf Ihrem PC speichern.',
    'gd_guide_txt20' => 'Schritt 18: Durch den Upload der .json-Datei in Research-Hub wird eine Verbindung zu Ihren Google-Drive-Dateien hergestellt. Klicken Sie dazu auf das Burger-Menu oben links.',
    'gd_guide_txt21' => 'Schritt 19: Klicken Sie im sich öffnenden Dialagfenster auf das Google-Drive-Symbol. Geben Sie anschließend eine App-ID ein, diese können Sie frei wählen. Wählen Sie dann Ihre .json-Datei aus und klicken Sie auf „Abesenden“.',
    'gd_guide_txt22' => 'Schritt 20: Sie werden zum Login-Bildschirm für Google weitergeleitet. Bitte loggen Sie sich ein. Falls Sie bereits eingeloggt sind, können Sie an dieser Stelle Ihr gewünschtes Konto anklicken.',
    'gd_guide_txt23' => 'Schritt 21: Klicken Sie unten links auf „Erweitert“.',
    'gd_guide_txt24' => 'Schritt 22: Klicken Sie unten links auf „OnlineDrives öffnen (unsicher)“.',
    'gd_guide_txt25' => 'Schritt 23: Klicken Sie unten links auf „Zulassen“.',
    'gd_guide_txt26' => 'Schritt 24: Ihr Google-Drive-Konto ist nun mit Research-Hub verbunden.',
    'gd_guide_txt27' => 'Schritt 25: Klicken Sie auf „Verbundene Drives“, um Ihr soeben verbundenes Google-Drive-Konto anzuzeigen.',
);
?>