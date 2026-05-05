<?php

$MESS["WF_STATUS_CHANGE_DESC"] = "#ID# - ID
#ADMIN_EMAIL# - E-Mails der Dokumenten-Workflow-Administratoren (mit dem Komma getrennt)
#BCC# - Verborgene Kopie (alle User, die mit ihrem aktuellen Status Seiten geändert haben oder ändern können)
#PREV_STATUS_ID# - ID des letzten Seitenstatus
#PREV_STATUS_TITLE# - Name des letzten Seitenstatus
#STATUS_ID# - Status ID
#STATUS_TITLE# - Statusname
#DATE_ENTER# - Erstellungsdatum
#ENTERED_BY_ID# - ID des Users, der die Seite erstellt hat
#ENTERED_BY_NAME# - Name des Users, der die Seite erstellt hat
#ENTERED_BY_EMAIL# - E-Mail des Users, der die Seite erstellt hat
#DATE_MODIFY# - Änderungsdatum
#MODIFIED_BY_ID# - ID des Users, der die Seite geändert hat
#MODIFIED_BY_NAME# - Name des Users, der die Seite geändert hat
#FILENAME# - Voller Dateiname
#TITLE# - Dateiname
#BODY_HTML# - Seiteninhalt im HTML Format
#BODY_TEXT# -  Seiteninhalt im TEXT Format
#BODY# -  Seiteninhalt, der in der Datenbank gespeichert ist
#BODY_TYPE# - Typ des Seiteninhalts
#COMMENTS# - Kommentar
";
$MESS["WF_NEW_DOCUMENT_DESC"] = "#ID# - ID
#ADMIN_EMAIL# - E-Mails der Dokumenten-Workflow-Administratoren (mit dem Komma getrennt)
#BCC# - Verborgene Kopie (alle User, die mit ihrem aktuellen Status Seiten geändert haben oder ändern können)
#PREV_STATUS_ID# - ID des letzten Seitentstatus
#PREV_STATUS_TITLE# - Name des letzten Seitenstatus
#STATUS_ID# - Status ID
#STATUS_TITLE# - Statusname
#DATE_ENTER# - Erstellungsdatum
#ENTERED_BY_ID# - ID des Users, der die Seite erstellt hat
#ENTERED_BY_NAME# - Name des Users, der die Seite erstellt hat
#ENTERED_BY_EMAIL# - E-Mail des Users, der die Seite erstellt hat
#DATE_MODIFY# - Änderungsdatum
#MODIFIED_BY_ID# - ID des Users, der die Seite geändert hat
#MODIFIED_BY_NAME# - Name des Users, der die Seite geändert hat
#FILENAME# - Voller Dateiname
#TITLE# - Dateiname
#BODY_HTML# - Seiteninhalt im HTML Format
#BODY_TEXT# -  Seiteninhalt im TEXT Format
#BODY# -  Seiteninhalt, der in der Datenbank gespeichert ist
#BODY_TYPE# - Typ des  Seiteninhalts
#COMMENTS# - Kommentar
";
$MESS["WF_NEW_IBLOCK_ELEMENT_DESC"] = "#ID# - ID
#IBLOCK_ID# - Informationsblock ID
#IBLOCK_TYPE# - Informationsblocktyp
#SECTION_ID# - Bereichs ID
#ADMIN_EMAIL# - E-Mails der Dokumenten-Workflow-Administratoren
#BCC# - Verborgene Kopie (alle User, die mit ihrem aktuellen Status Elemente geändert haben oder ändern können)
#STATUS_ID# - Status ID
#STATUS_TITLE# - Statusname
#DATE_CREATE# - Erstellungsdatum
#CREATED_BY_ID# - ID des Users, der das Element erstellt hat
#CREATED_BY_NAME# - Name des Users, der das Element erstellt hat
#CREATED_BY_EMAIL# - E-Mail des Users, der das Element erstellt hat
#NAME# - Elementname
#PREVIEW_HTML# - Anzeige im HTML Format
#PREVIEW_TEXT# - Anzeige im TEXT Format
#PREVIEW# - Anzeige, die in der Datenbank gespeichert ist
#PREVIEW_TYPE# - Anzeigentyp (text | html)
#DETAIL_HTML# - Vollständige Elementbeschreibung im HTML Format
#DETAIL_TEXT# - Vollständige Elementbeschreibung im TEXT Format
#DETAIL# - Vollständige Elementbeschreibung, die in der Datenbank gespeichert ist
#DETAIL_TYPE# - Typ der vollständigen Beschreibung
#COMMENTS# - Kommentar
";
$MESS["WF_IBLOCK_STATUS_CHANGE_DESC"] = "#ID# - ID
#IBLOCK_ID# - Informationsblock ID
#IBLOCK_TYPE# - Informationsblocktyp
#SECTION_ID# - Bereichs ID
#ADMIN_EMAIL# - E-Mails der Dokumenten-Workflow-Administratoren
#BCC# - Verborgene Kopie (alle User, die mit ihrem aktuellen Status Elemente geändert haben oder ändern können)
#STATUS_ID# - Status ID
#STATUS_TITLE# - Statusname
#DATE_CREATE# - Erstellungsdatum
#CREATED_BY_ID# - ID des Users, der das Element erstellt hat
#CREATED_BY_NAME# - Name des Users, der das Element erstellt hat
#CREATED_BY_EMAIL# - E-Mail des Users, der das Element erstellt hat
#NAME# - Elementname
#PREVIEW_HTML# - Anzeige im HTML Format
#PREVIEW_TEXT# - Anzeige im TEXT Format
#PREVIEW# - Anzeige, die in der Datenbank gespeichert ist
#PREVIEW_TYPE# - Anzeigentyp (text | html)
#DETAIL_HTML# - Vollständige Elementbeschreibung im HTML Format
#DETAIL_TEXT# - Vollständige Elementbeschreibung im TEXT Format
#DETAIL# - Vollständige Elementbeschreibung, die in der Datenbank gespeichert ist
#DETAIL_TYPE# - Typ der vollständigen Beschreibung
#COMMENTS# - Kommentar
";
$MESS["WF_STATUS_CHANGE_SUBJECT"] = "#SITE_NAME#: Änderung des Seitenstatus Nr. #ID#";
$MESS["WF_IBLOCK_STATUS_CHANGE_SUBJECT"] = "#SITE_NAME#: Änderung des Elementstatus Nr. #ID# des Informationsblocks Nr. #IBLOCK_ID# (Typ - #IBLOCK_TYPE#)";
$MESS["WF_IBLOCK_STATUS_CHANGE_MESSAGE"] = "#SITE_NAME#: Änderung des Elementstatus Nr. #ID# des Informationsblocks Nr. #IBLOCK_ID# (Typ - #IBLOCK_TYPE#)^
---------------------------------------------------------------------------

Jetzt haben die Elementfelder folgende Werte:

Name    - #NAME#
Status         - [#STATUS_ID#] #STATUS_TITLE#; vorheriger - [#PREV_STATUS_ID#] #PREV_STATUS_TITLE#
Erstellt       - #DATE_CREATE#; [#CREATED_BY_ID#] #CREATED_BY_NAME#
Geändert       - #DATE_MODIFY#; [#MODIFIED_BY_ID#] #MODIFIED_BY_NAME#

Anzeige (Typ - #PREVIEW_TYPE#):
---------------------------------------------------------------------------
#PREVIEW_TEXT#
---------------------------------------------------------------------------

Vollständige Beschreibung (Typ - #DETAIL_TYPE#):
---------------------------------------------------------------------------
#DETAIL_TEXT#
---------------------------------------------------------------------------

Kommentar:
---------------------------------------------------------------------------
#COMMENTS#
---------------------------------------------------------------------------

Um das Element anzusehen und zu ändern, besuchen Sie bitte den folgenden Link:
http://#SERVER_NAME#/bitrix/admin/iblock_element_edit.php?lang=de&WF=Y&PID=#ID#&type=#IBLOCK_TYPE#&IBLOCK_ID=#IBLOCK_ID#&filter_section=#SECTION_ID#

Dies ist eine automatisch generierte Nachricht.
";
$MESS["WF_NEW_DOCUMENT_SUBJECT"] = "#SITE_NAME#: Neue Seite wurde erstellt";
$MESS["WF_NEW_IBLOCK_ELEMENT_SUBJECT"] = "#SITE_NAME#: Neues Element des Informationsblocks Nr. #IBLOCK_ID# wurde erstellt (Typ - #IBLOCK_TYPE#)";
$MESS["WF_NEW_IBLOCK_ELEMENT_MESSAGE"] = "#SITE_NAME#: Neues Element des Informationsblocks Nr. #IBLOCK_ID# wurde erstellt (Typ - #IBLOCK_TYPE#)
---------------------------------------------------------------------------

Jetzt haben die Elementfelder folgende Werte:

Name    - #NAME#
Status         - [#STATUS_ID#] #STATUS_TITLE#
Erstellt       - #DATE_CREATE#; [#CREATED_BY_ID#] #CREATED_BY_NAME#

Anzeige (Typ - #PREVIEW_TYPE#):
---------------------------------------------------------------------------
#PREVIEW_TEXT#
---------------------------------------------------------------------------

Vollständige Beschreibung (Typ - #DETAIL_TYPE#):
---------------------------------------------------------------------------
#DETAIL_TEXT#
---------------------------------------------------------------------------

Kommentar:
---------------------------------------------------------------------------
#COMMENTS#
---------------------------------------------------------------------------

Um das Element anzusehen und zu ändern, besuchen Sie bitte den folgenden Link:
http://#SERVER_NAME#/bitrix/admin/iblock_element_edit.php?lang=de&WF=Y&PID=#ID#&type=#IBLOCK_TYPE#&IBLOCK_ID=#IBLOCK_ID#&filter_section=#SECTION_ID#

Dies ist eine automatisch generierte Nachricht.
";
$MESS["WF_STATUS_CHANGE_MESSAGE"] = "Der Status der Seite Nr. #ID# auf der Website  #SITE_NAME# wurde geändert.
---------------------------------------------------------------------------

Jetzt hat die Seite folgende Werte:

Dateiname      - #FILENAME#
Überschrift    - #TITLE#
Status         - [#STATUS_ID#] #STATUS_TITLE#; vorheriger - [#PREV_STATUS_ID#] #PREV_STATUS_TITLE#
Erstellt       - #DATE_ENTER#; [#ENTERED_BY_ID#] #ENTERED_BY_NAME#
Geändert       - #DATE_MODIFY#; [#MODIFIED_BY_ID#] #MODIFIED_BY_NAME#

Inhalt (Typ - #BODY_TYPE#):
---------------------------------------------------------------------------
#BODY_TEXT#
---------------------------------------------------------------------------

Kommentar:
---------------------------------------------------------------------------
#COMMENTS#
---------------------------------------------------------------------------

Um die Seite anzusehen und zu ändern, folgen Sie bitte den folgenden Link:
http://#SERVER_NAME#/bitrix/admin/workflow_edit.php?lang=de&ID=#ID#

Dies ist eine automatisch generierte Nachricht.
";
$MESS["WF_NEW_DOCUMENT_MESSAGE"] = "Eine neue Seite wurde auf der Website #SITE_NAME# erstellt.
---------------------------------------------------------------------------

Jetzt hat die Seite folgende Werte:

ID             - #ID#
Datei          - #FILENAME#
Überschrift    - #TITLE#
Status         - [#STATUS_ID#] #STATUS_TITLE#
Erstellt       - #DATE_ENTER#; [#ENTERED_BY_ID#] #ENTERED_BY_NAME#

Inhalt (Typ - #BODY_TYPE#):
---------------------------------------------------------------------------
#BODY_TEXT#
---------------------------------------------------------------------------

Kommentar:
---------------------------------------------------------------------------
#COMMENTS#
---------------------------------------------------------------------------

Um die Seite anzusehen und zu ändern, folgen Sie bitte den folgenden Link:
http://#SERVER_NAME#/bitrix/admin/workflow_edit.php?lang=de&ID=#ID#

Dies ist eine automatisch generierte Nachricht.
";
$MESS["WF_NEW_DOCUMENT_NAME"] = "Eine neue Seite wurde erstellt";
$MESS["WF_NEW_IBLOCK_ELEMENT_NAME"] = "Ein neues Informationsblock-Element wurde erstellt";
$MESS["WF_STATUS_CHANGE_NAME"] = "Geänderter Seitenstatus";
$MESS["WF_IBLOCK_STATUS_CHANGE_NAME"] = "Geänderter Status des Informationsblock-Elements";
