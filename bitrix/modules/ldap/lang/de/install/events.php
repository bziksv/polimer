<?
$MESS ['LDAP_USER_CONFIRM_EVENT_NAME'] = "#SITE_NAME#: Registrierungsbestätigung";
$MESS ['LDAP_USER_CONFIRM_TYPE_DESC'] = "#USER_ID# - User ID
#EMAIL# - E-Mail
#LOGIN# - Loginname
#XML_ID# - Externe ID
#BACK_URL# - Rückkehrlink";
$MESS ['LDAP_USER_CONFIRM_EVENT_DESC'] = "Informationsnachricht von #SITE_NAME#
------------------------------------------
Hallo,

Sie haben diese Nachricht bekommen, weil Ihre E-Mail-Adresse, bei der Registrierung eines neuen Users, auf dem Server #SERVER_NAME# benutzt wurde.

Um die Registrierung zu bestätigen, müssen Sie sich auf der folgenden Seite Anmelden (Ihren Loginnamen und Passwort, die Sie im lokalen Netzwerk benutzen, eingeben):
http://#SERVER_NAME#/bitrix/admin/ldap_user_auth.php?ldap_user_id=#XML_ID#&back_url=#BACK_URL#

Dies ist eine automatisch generierte Nachricht.";
$MESS ['LDAP_USER_CONFIRM_TYPE_NAME'] = "Registrierungsbestätigung";
?>