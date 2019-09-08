Module Drivemanager
=================
Module for managing drives inside spaces and on profiles.

## Description:
This module will allow you to manage files in a filesystem like structure.
- upload, download, move, delete files
- create, delete, move, edit folders
- automatically create/extract a folder structure from a ZIP file (optional, configurable)
- download files as a zip file (optional, configurable)
- see an overview of all files that were posted to the user/space stream
- see details of files like creator, editor, creation date, ...

## Setup Instructions:
After installation and activiation from the Marketplace you can deactivate the ZIP functionality if you want. In the profile and space views (.../space/manage/module | .../user/account/edit-modules) you can now activate the module for the designated space or user. A new navigation link will show up that leads you to the module view.

__Installation (German):__
Gdriver ist im Verzeichnis „protected/modules“ der HumHub-Installation abzulegen.

Die Dateien client_secret.json und token.json fehlen im Git Repository:
- Um client_secret.json individuell zu erstellen, sind folgende Schritte nötig:
    - https://console.developers.google.com/apis/credentials aufrufen.
    - Ein neues Projekt zu erstellen.
    - Anmeldedaten für OAuth-Client-ID erstellen
        - Webanwendung auswählen
        - Unter „Autorisierte Weiterleitungs-URIs“ sind folgende 2 URIs einzutragen:
            - Auf einem lokeln Test-System:
                - http://localhost/[PATH]/index.php?r=gdriver%2Fgdriver
                - http://localhost/[PATH]/index.php?r=gdriver%2Fspace
                - [PATH] könnte „humhub-1.2.1“ sein.
            - Auf einem produktiven Server mit entsprechender Domain statt localhost
            - Es ist darauf zu achten, unmittelbar nach der Eingabe Enter zu drücken, um sicherzustellen, dass die Eingabe gespeichert bleibt.
        - Anschließend öffnet sich das Dialogfenster „OAuth-Client“, welches die Client-ID und den Clientschlüssel bereithält. Es darf geschlossen werden.
        - Unter dem Punkt „OAuth 2.0-Client-IDs“ ist nun ein neuer Eintrag vorhanden, an dessen Ende sich ein Download-Symbol befindet, womit sich die benötigte JSON-Datei herunterladen lässt. Diese sollte in client_secret.json umbenannt und im Wurzelverzeichnis von Gdriver abgelegt werden.
- token.json wird automatisch erstellt, insofern client_secret.json bei Aufruf von Gdriver korrekt vorliegt. Dabei wird der Nutzer durch den Anmeldeprozess zu Google geführt, an dessen Ende token.json erstellt wird.
