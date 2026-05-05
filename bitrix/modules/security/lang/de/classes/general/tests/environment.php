<?
$MESS["SECURITY_SITE_CHECKER_EnvironmentTest_NAME"] = "Überprüfung der Umgebung";
$MESS["SECURITY_SITE_CHECKER_SESSION_DIR"] = "Verzeichnis mit gespeicherten Sitzungsdateien ist für alle Nutzer des administrativen Bereichs verfügbar";
$MESS["SECURITY_SITE_CHECKER_SESSION_DIR_DETAIL"] = "Diese Sicherheitslücke kann genutzt werden, Sitzungsdaten zu lesen oder zu ändern, indem Scripts auf anderen virtuellen Servern ausgeführt werden.";
$MESS["SECURITY_SITE_CHECKER_SESSION_DIR_RECOMMENDATION"] = "Konfigurieren Sie die Zugriffsrechte korrekt oder ändern Sie das Verzeichnis. Eine andere Option ist es, Sitzungsdaten in der Datenbank zu speichern: <a href=\"/bitrix/admin/security_session.php\">Sitzungsschutz</a>.";
$MESS["SECURITY_SITE_CHECKER_SESSION_DIR_ADDITIONAL"] = "Verzeichnis zum Speichern von Sitzungsdaten: #DIR#<br>
Rechte: #PERMS#
";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION"] = "Das Verzeichnis mit gespeicherten Sitzungsdateien kann Daten über Sitzungen von verschiedenen Projekten enthalten.";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_DETAIL"] = "Das kann den Angreifern ermöglichen, mithilfe von Scripts auf anderen virtuellen Servern Sitzungsdaten zu sehen oder zu ändern.";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_RECOMMENDATION"] = "Ändern Sie das Verzeichnis oder speichern Sie die Sitzungen in der Datenbank: <a href=\"/bitrix/admin/security_session.php\"> Sitzungsschutz </a>.";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_ADDITIONAL_OWNER"] = "Grund: Dateibesitzer ist nicht der aktuelle Nutzer<br>
Datei: #FILE#<br>
UID des Dateibesitzers: #FILE_ONWER#<br>
UID des aktuellen Nutzers: #CURRENT_OWNER#<br>
";
$MESS["SECURITY_SITE_CHECKER_COLLECTIVE_SESSION_ADDITIONAL_SIGN"] = "Grund: Sitzungsdatei ist nicht mit der Unterschrift der Website unterschrieben<br>
Datei: #FILE#<br>
Unterschrift der aktuellen Website: #SIGN#<br>
Dateiinhalte: <pre>#FILE_CONTENT#</pre>
";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP"] = "PHP-Skripts werden im Verzeichnis mit hochgeladenen Dateien ausgeführt.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DETAIL"] = "Manche Entwickler nehmen die Filter der korrekten Dateinamen nicht ernst genug, was ein Angreifer nutzen kann, um einen vollen Zugriff auf Ihr Projekt zu bekommen.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_RECOMMENDATION"] = "Konfigurieren Sie Ihren Web-Server korrekt.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE"] = "PHP-Scripts mit doppelten Erweiterungen (z.B.: php.lala) werden im Verzeichnis mit hochgeladenen Dateien ausgeführt.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_DETAIL"] = "Manche Entwickler nehmen die Filter der korrekten Dateinamen nicht ernst genug, was ein Angreifer nutzen kann, um einen vollen Zugriff auf Ihr Projekt zu bekommen.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PHP_DOUBLE_RECOMMENDATION"] = "Konfigurieren Sie Ihren Web-Server korrekt.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY"] = "Python-Skripts werden im Verzeichnis mit hochgeladenen Dateien ausgeführt.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_DETAIL"] = "Manche Entwickler nehmen die Filter der korrekten Dateinamen nicht ernst genug, was ein Angreifer nutzen kann, um einen vollen Zugriff auf Ihr Projekt zu bekommen.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_EXECUTABLE_PY_RECOMMENDATION"] = "Konfigurieren Sie Ihren Web-Server korrekt.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS"] = "Apache soll nicht die .htaccess-Dateien im Verzeichnis mit hochgeladenen Dateien verarbeiten.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_DETAIL"] = "Manche Entwickler nehmen die Filter der korrekten Dateinamen nicht ernst genug, was ein Angreifer nutzen kann, um einen vollen Zugriff auf Ihr Projekt zu bekommen.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_HTACCESS_RECOMMENDATION"] = "Konfigurieren Sie Ihren Web-Server korrekt.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION"] = "Apache Content Negotiation ist aktiviert im Verzeichnis der hochgeladenen Dateien.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION_DETAIL"] = "Apache Content Negotiation wird nicht empfohlen, weil es XSS-Angriffe verursachen kann.";
$MESS["SECURITY_SITE_CHECKER_UPLOAD_NEGOTIATION_RECOMMENDATION"] = "Konfigurieren Sie Ihren Web-Server korrekt.";
$MESS["SECURITY_SITE_CHECKER_PHP_PRIVILEGED_USER"] = "PHP läuft als ein privilegierter Nutzer
";
$MESS["SECURITY_SITE_CHECKER_PHP_PRIVILEGED_USER_DETAIL"] = "Wenn PHP als ein privilegierter Nutzer läuft (bspw. Root), kann dies die Sicherheit Ihres Projektes gefährden
";
$MESS["SECURITY_SITE_CHECKER_PHP_PRIVILEGED_USER_RECOMMENDATION"] = "Konfigurieren Sie Ihren Server so, dass PHP als ein nicht privilegierter Nutzer läuft
";
$MESS["SECURITY_SITE_CHECKER_PHP_PRIVILEGED_USER_ADDITIONAL"] = "#UID#/#GID#
";
$MESS["SECURITY_SITE_CHECKER_BITRIX_TMP_DIR"] = "Temporäre Dateien werden im Root-Verzeichnis des Projektes gespeichert
";
$MESS["SECURITY_SITE_CHECKER_BITRIX_TMP_DIR_DETAIL"] = "Es wird nicht empfohlen, temporäre Dateien, welche via CTempFile erstellt werden, im Root-Verzeichnis abzuspeichern.";
$MESS["SECURITY_SITE_CHECKER_BITRIX_TMP_DIR_RECOMMENDATION"] = "Definieren Sie eine Konstante \"BX_TEMPORARY_FILES_DIRECTORY\" in \"bitrix/php_interface/dbconn.php\" und
geben Sie einen erforderlichen Pfad an.<br>
Machen Sie dabei Folgendes:<br>
1. Wählen Sie einen Namen für Ihr temporäres Verzeichnis und erstellen Sie es. Zum Beispiel \"/home/bitrix/tmp/www\":
<pre>
mkdir -p -m 700 /home/bitrix/tmp/www
</pre>
2. Definieren Sie die Konstante, damit das System weiß, dass Sie temporäre Dateien in diesem Ordner speichern
wollen:
<pre>
define(\"BX_TEMPORARY_FILES_DIRECTORY\", \"/home/bitrix/tmp/www\");
</pre>
";
$MESS["SECURITY_SITE_CHECKER_BITRIX_TMP_DIR_ADDITIONAL"] = "Aktuelles Verzeichnis: #DIR#";
?>