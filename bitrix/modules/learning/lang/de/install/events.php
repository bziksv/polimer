<?
$MESS["NEW_LEARNING_TEXT_ANSWER_NAME"] = "Der Text neuer Antwort";
$MESS["NEW_LEARNING_TEXT_ANSWER_DESC"] = "#ID# - die Ergebnis-ID
#ATTEMPT_ID# - Die Versuch-ID
#TEST_NAME# - Der Name des Tests
#USER# - Der Nutzer, der den Test macht
#DATE# - Datum und Zeit
#QUESTION_TEXT# - Die Frage
#ANSWER_TEXT# - Die Antwort
#EMAIL_FROM# - Die E-Mailadresse des Absenders
#EMAIL_TO# - Die E-Mailadresse des Empfängers
#MESSAGE_TITLE# - Der Betreff der E-Mail";
$MESS["NEW_LEARNING_TEXT_ANSWER_SUBJECT"] = "#SITE_NAME#: #COURSE_NAME#: #MESSAGE_TITLE#";
$MESS["NEW_LEARNING_TEXT_ANSWER_MESSAGE"] = "Die Informationsnachricht der Website #SITE_NAME#
------------------------------------------

Kurs:#COURSE_NAME#
Test:#TEST_NAME#

Nutzer: #USER#
Datum: #DATE#

Frage:
------------------------------------------
#QUESTION_TEXT#
------------------------------------------

Antwort:
------------------------------------------
#ANSWER_TEXT#
------------------------------------------

Um sich die Antwort anzuschauen und sie zu beantworten, folgen Sie dem Link:
http://#SERVER_NAME#/bitrix/admin/learn_test_result_edit.php?lang=de&ID=#ID#&ATTEMPT_ID=#ATTEMPT_ID#

Die Nachricht wurde automatisch erstellt";
?>