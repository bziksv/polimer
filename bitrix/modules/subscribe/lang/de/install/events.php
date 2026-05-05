<?php
$MESS['SUBSCRIBE_CONFIRM_DESC'] = "#ID# - Abonnement ID
#EMAIL# - E-Mail
#CONFIRM_CODE# - Bestätigungscode
#SUBSCR_SECTION# - Seite zum Bearbeiten des Abonnements (wird in den Einstellungen definiert)
#USER_NAME# - Name des Abonnenten (kann fehlen)
#DATE_SUBSCR# - Datum an dem die Adresse hinzugekommen/geändert wurde
";
$MESS['SUBSCRIBE_CONFIRM_SUBJECT'] = "#SITE_NAME#: Abonnementbestätigung";
$MESS['SUBSCRIBE_CONFIRM_NAME'] = "Bestätigung des Abonnements";
$MESS['SUBSCRIBE_CONFIRM_MESSAGE'] = "Informationsnachricht von #SITE_NAME#
---------------------------------------

Sehr geehrte Damen und Herren,

Sie haben diese Nachricht erhalten, weil Ihre Adresse für Nachrichten-Abonnements
vom #SERVER_NAME# eingetragen wurde.

Hier sind detailierte Informationen über Ihr Abonnement:

Abonnement E-Mail ................................... #EMAIL#
Datum........................................................ #DATE_SUBSCR#


Ihr Bestätigungscode: #CONFIRM_CODE#

Bitte klicken Sie auf den folgenden Link, um Ihr Abonnement zu bestätigen.
http://#SERVER_NAME##SUBSCR_SECTION#subscr_edit.php?ID=#ID#&CONFIRM_CODE=#CONFIRM_CODE#

Oder gehen Sie auf die Seite und tragen Ihren Bestätigungscode manuell ein:
http://#SERVER_NAME##SUBSCR_SECTION#subscr_edit.php?ID=#ID#

Sie werden keine weiteren Nachrichten erhalten, solange Sie uns Ihre Bestätigung nicht gesendet haben.

---------------------------------------------------------------------
Bitte speichern Sie diese Nachricht, weil sie Informationen für die 
Autorisierung enthält. Mit dem Bestätigungscode können Sie Ihre 
Abonnement-Parameter ändern oder sich abmelden.

Parameter ändern:
http://#SERVER_NAME##SUBSCR_SECTION#subscr_edit.php?ID=#ID#&CONFIRM_CODE=#CONFIRM_CODE#

Abmelden:
http://#SERVER_NAME##SUBSCR_SECTION#subscr_edit.php?ID=#ID#&CONFIRM_CODE=#CONFIRM_CODE#&action=unsubscribe
---------------------------------------------------------------------

Dies ist eine automatisch generierte Nachricht.
";
