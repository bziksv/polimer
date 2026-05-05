<?
$MESS["CLU_SLAVE_LIST_TITLE"] = "Slave Datenbank";
$MESS["CLU_SLAVE_LIST_ID"] = "ID";
$MESS["CLU_SLAVE_LIST_FLAG"] = "Stand";
$MESS["CLU_SLAVE_NOCONNECTION"] = "Keine Verbindung";
$MESS["CLU_SLAVE_UPTIME"] = "Betriebszeit";
$MESS["CLU_SLAVE_LIST_BEHIND"] = "Verzögerungszeit (Sek)";
$MESS["CLU_SLAVE_LIST_STATUS"] = "Status";
$MESS["CLU_SLAVE_LIST_NAME"] = "Name";
$MESS["CLU_SLAVE_LIST_DB_HOST"] = "Server";
$MESS["CLU_SLAVE_LIST_DB_NAME"] = "Datenbank";
$MESS["CLU_SLAVE_LIST_DB_LOGIN"] = "Nutzer";
$MESS["CLU_SLAVE_LIST_WEIGHT"] = "Umfang (%)";
$MESS["CLU_SLAVE_LIST_DESCRIPTION"] = "Beschreibung";
$MESS["CLU_SLAVE_LIST_ADD"] = "Slave Datenbank hinzufügen";
$MESS["CLU_SLAVE_LIST_ADD_TITLE"] = "Den Assistenten einer neuen Slave Datenbank starten";
$MESS["CLU_SLAVE_LIST_EDIT"] = "Bearbeiten";
$MESS["CLU_SLAVE_LIST_START_USING_DB"] = "Datenbank benutzen";
$MESS["CLU_SLAVE_LIST_SKIP_SQL_ERROR"] = "Fehler ignorieren";
$MESS["CLU_SLAVE_LIST_SKIP_SQL_ERROR_ALT"] = "Einen einzelnen SQL-Fehler ignorieren und mit der Replikation fortfahren";
$MESS["CLU_SLAVE_LIST_DELETE"] = "Löschen";
$MESS["CLU_SLAVE_LIST_DELETE_CONF"] = "Verbindung löschen?";
$MESS["CLU_SLAVE_LIST_PAUSE"] = "Pause";
$MESS["CLU_SLAVE_LIST_RESUME"] = "Wieder aufnehmen";
$MESS["CLU_SLAVE_LIST_REFRESH"] = "Aktualisieren";
$MESS["CLU_SLAVE_LIST_STOP"] = "Datenbank nicht mehr benutzen";
$MESS["CLU_SLAVE_BACKUP"] = "Datensicherung";
$MESS["CLU_MAIN_LOAD"] = "Minimale Belastung";
$MESS["CLU_SLAVE_LIST_NOTE"] = "<p>Datenbankreplikation ist die Erstellung und Verwaltung mehrerer Kopien von einer Datenbank, was zwei wichtige Funktionen hat:</p>
<p>
1) Belastungsverteilung zwischen einer Master-Datenbank und einer oder mehreren Slave-Datenbanken;<br>
2) Slave-Datenbanken als sofort einsetzbarer Ersatz.<br>
</p>
<p>Wichtig!<br>
- Für Replikation müssen nur autonome Server mit möglichst schneller Verbindung zwischen einander benutzt werden.<br>
- Der Prozess einer Replikation fängt mit dem Kopieren des Datenbankcontents an. Während dieser Zeit wird der öffentliche Bereich der Website nicht verfügbar, aber Administrativer Bereich bleibt zugänglich. Beliebige Datenänderungen, welche sich während der Replikation ereignen, können die korrekte Arbeitsweise der Website beeinflussen.<br>
</p>
<p>Handlungsanweisung bei der Replikation<br>
Schritt 1: Starten Sie den Assistenten, indem Sie auf \"Slave-Datenbank hinzufügen\" klicken. Der Assistent wird die Serverkonfiguration überprüfen und Ihnen vorschlagen, eine Slave-Datenbank hinzuzufügen.<br>
Schritt 2: Finden Sie in der Liste eine erforderliche Datenbank und wählen Sie im Aktionsmenü den Befehl \"Datenbank benutzen\" aus.<br>
Schritt 3: Folgen Sie dann des Anweisungen des Assistenten.<br>
</p>
";
$MESS["CLU_SLAVE_LIST_MASTER_ADD"] = "Master/Slave-Datenbank hinzufügen";
$MESS["CLU_SLAVE_LIST_MASTER_ADD_TITLE"] = "Den Assistenten für eine neue Master/Slave-Datenbank starten";
?>