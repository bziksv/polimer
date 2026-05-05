<?
$MESS ['STATISTIC_ACTIVITY_EXCEEDING_DESC'] = "#ACTIVITY_TIME_LIMIT# - Zeitintervall zum Testen
#ACTIVITY_HITS# - Anzahl Hits während des Zeitintervalls zum Testen
#ACTIVITY_HITS_LIMIT# - Maximale Anzahl Hits während des Zeitintervalls zum Testen
#ACTIVITY_EXCEEDING# - Überschrittene Anzahl Hits
#CURRENT_TIME# - Zeit der Blockierung (Serverzeit)
#DELAY_TIME# - Dauer der Blockierung
#USER_AGENT# - UserAgent
#SESSION_ID# - Session ID
#SESSION_LINK# - Link zur Session
#SERACHER_ID# - Suchmaschinen ID
#SEARCHER_NAME# - Suchmaschine
#SEARCHER_LINK# - Link zur Hitliste der Suchmaschine
#VISITOR_ID# - User ID
#VISITOR_LINK# - Link zum Userprofil
#STOPLIST_LINK# - Link zum Hinzufügen eines Users zur Sperrliste";
$MESS ['STATISTIC_DAILY_REPORT_DESC'] = "#EMAIL_TO# - E-Mail des Seitenadministrators
#SERVER_TIME# - Serverzeit während des Berichtversandes
#HTML_HEADER# - Öffnen des HTML-Tags + CSS Styles
#HTML_COMMON# - Tabelle der Seitenbesuche (Hits, Sessions, Hosts, Gäste, Events) (HTML)
#HTML_ADV# - Tabelle der Werbekampagnen (TOP 10) (HTML)
#HTML_EVENTS# - Tabelle der Eventtypen (TOP 10) (HTML)
#HTML_REFERERS# - Tabelle der verweisenden Seiten (TOP 10) (HTML)
#HTML_PHRASES# - Tabelle der Suchbegriffe (TOP 10) (HTML)
#HTML_SEARCHERS# - Tabelle der Seitenindexierung (TOP 10) (HTML)
#HTML_FOOTER# - Schließen des HTML-Tags";
$MESS ['STATISTIC_DAILY_REPORT_MESSAGE'] = "#HTML_HEADER#
<font class='h2'>Zusammengefasste Statistik der Seite <font color='#A52929'>#SITE_NAME#</font><br>
Daten für <font color='#0D716F'>#SERVER_TIME#</font></font>
<br><br>
<a class='tablebodylink' href='http://#SERVER_NAME#/bitrix/admin/stat_list.php?lang=#LANGUAGE_ID#'>http://#SERVER_NAME#/bitrix/admin/stat_list.php?lang=#LANGUAGE_ID#</a>
<br>
<hr><br>
#HTML_COMMON#
<br>
#HTML_ADV#
<br>
#HTML_REFERERS#
<br>
#HTML_PHRASES#
<br>
#HTML_SEARCHERS#
<br>
#HTML_EVENTS#
<br>
<hr>
<a class='tablebodylink' href='http://#SERVER_NAME#/bitrix/admin/stat_list.php?lang=#LANGUAGE_ID#'>http://#SERVER_NAME#/bitrix/admin/stat_list.php?lang=#LANGUAGE_ID#</a>
#HTML_FOOTER#";
$MESS ['STATISTIC_ACTIVITY_EXCEEDING_MESSAGE'] = "Auf der Seite #SERVER_NAME# hat ein Besucher die eingestellte Aktivitätsgrenze überschritten.

Angefangen mit #CURRENT_TIME# wird der Besucher für #DELAY_TIME# Sek. blockiert.

Aktivität    - #ACTIVITY_HITS# Hits nach #ACTIVITY_TIME_LIMIT# Sek. (Grenze - #ACTIVITY_HITS_LIMIT#)
Besucher         - #VISITOR_ID#
Session      - #SESSION_ID#
Suchmaschine - [#SERACHER_ID#] #SEARCHER_NAME#
UserAgent    - #USER_AGENT#

>===============================================================================================
Um den Besucher in die Sperrliste einzutragen, benutzen Sie folgenden Link:
http://#SERVER_NAME##STOPLIST_LINK#
Um die Session des Besuchers anzusehen, benutzen Sie folgenden Link:
http://#SERVER_NAME##SESSION_LINK#
Um den Besucherprofil anzusehen, benutzen Sie folgenden Link:
http://#SERVER_NAME##VISITOR_LINK#
Um die Hit-Statistik der Suchmaschine anzusehen, benutzen Sie folgenden Link:
http://#SERVER_NAME##SEARCHER_LINK#";
$MESS ['STATISTIC_ACTIVITY_EXCEEDING_SUBJECT'] = "#SERVER_NAME#: Überschrittene Aktivitätsgrenze";
$MESS ['STATISTIC_DAILY_REPORT_SUBJECT'] = "#SERVER_NAME#: Seitenstatistik (#SERVER_TIME#)";
$MESS ['STATISTIC_DAILY_REPORT_NAME'] = "Tägliche Seitenstatistik";
$MESS ['STATISTIC_ACTIVITY_EXCEEDING_NAME'] = "Überschreitung der Aktivitätsgrenze";
?>