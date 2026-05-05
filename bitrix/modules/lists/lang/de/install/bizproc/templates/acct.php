<?
$MESS["LIBTA_NAME"] = "Kurzbezeichnung";
$MESS["LIBTA_TYPE"] = "Typ";
$MESS["LIBTA_TYPE_ADV"] = "Werbung";
$MESS["LIBTA_TYPE_EX"] = "Bewirtung";
$MESS["LIBTA_TYPE_C"] = "Bürobedarf";
$MESS["LIBTA_TYPE_D"] = "Sonstiges";
$MESS["LIBTA_CREATED_BY"] = "Erstellt von";
$MESS["LIBTA_DATE_CREATE"] = "Erstellt am";
$MESS["LIBTA_FILE"] = "Datei (Kopie der Rechnung)";
$MESS["LIBTA_NUM_DATE"] = "Rechnungsnummer und -datum";
$MESS["LIBTA_SUM"] = "Betrag";
$MESS["LIBTA_PAID"] = "Bereits bezahlt";
$MESS["LIBTA_PAID_NO"] = "Nein";
$MESS["LIBTA_PAID_YES"] = "Ja";
$MESS["LIBTA_BDT"] = "Kostenstelle";
$MESS["LIBTA_DATE_PAY"] = "Zahlungsdatum (wird vom Buchhalter ausgefüllt)";
$MESS["LIBTA_NUM_PP"] = "Zahlungsanweisung (wird vom Buchhalter ausgefüllt)";
$MESS["LIBTA_DOCS"] = "Kopien von Dokumenten";
$MESS["LIBTA_DOCS_YES"] = "Vorhanden";
$MESS["LIBTA_DOCS_NO"] = "Nicht vorhanden";
$MESS["LIBTA_APPROVED"] = "Genehmigt";
$MESS["LIBTA_APPROVED_R"] = "Abgelehnt";
$MESS["LIBTA_APPROVED_N"] = "Wird noch entschieden";
$MESS["LIBTA_APPROVED_Y"] = "Genehmigt";
$MESS["LIBTA_T_PBP"] = "Regelmäßiger Workflow";
$MESS["LIBTA_T_SPA1"] = "Zugriffsrechte definieren: Ersteller";
$MESS["LIBTA_T_PDA1"] = "Dokument veröffentlichen";
$MESS["LIBTA_STATE1"] = "In der Phase der Genehmigung";
$MESS["LIBTA_T_SSTA1"] = "Status: In der Phase der Genehmigung";
$MESS["LIBTA_T_ASFA1"] = "Dokumentfeld \"Genehmigt\" definieren";
$MESS["LIBTA_T_SVWA1"] = "Variable für Vorgesetzten";
$MESS["LIBTA_T_WHILEA1"] = "Genehmigungsablauf";
$MESS["LIBTA_T_SA0"] = "Vorgehensweise";
$MESS["LIBTA_T_IFELSEA1"] = "Beim Vorgesetzten";
$MESS["LIBTA_T_IFELSEBA1"] = "Ja";
$MESS["LIBTA_T_ASFA2"] = "Dokumentfeld \"Genehmigt\" definieren";
$MESS["LIBTA_T_IFELSEBA2"] = "Nein";
$MESS["LIBTA_T_GUAX1"] = "Vorgesetzten auswählen";
$MESS["LIBTA_T_SVWA2"] = "Variable für Vorgesetzten";
$MESS["LIBTA_T_SPAX1"] = "Zugriffsrechte definieren: Vorgesetzter (Lesen)";
$MESS["LIBTA_SMA_MESSAGE_1"] = "Antrag auf die Bezahlung einer Rechnung
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Betrag: {=Document:PROPERTY_SUM}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_T_SMA_MESSAGE_1"] = "Nachricht: Antrag auf Genehmigung einer Rechnung";
$MESS["LIBTA_XMA_MESSAGES_1"] = "Unternehmensportal: Rechnung zur Genehmigung";
$MESS["LIBTA_XMA_MESSAGET_1"] = "Antrag auf Genehmigung einer Rechnung

Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}
Kostenstelle: {=Document:PROPERTY_BDT}

{=Variable:Link}{=Document:ID}/


Aufgaben innerhalb der Workflows:
{=Variable:TasksLink}";
$MESS["LIBTA_T_XMA_MESSAGES_1"] = "Nachricht: Genehmigung einer Rechnung";
$MESS["LIBTA_AAQN1"] = "Genehmigung einer Rechnung \"{=Document:NAME}\"";
$MESS["LIBTA_AAQD1"] = "Sie müssen die Rechnung entweder genehmigen oder ablehnen

Name: {=Document:NAME}
Erstellt am: {=Document:DATE_CREATE}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}
Kostenstelle: {=Document:PROPERTY_BDT}
Datei: {=Variable:Domain}{=Document:PROPERTY_FILE}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_T_AAQN1"] = "Bezahlung der Rechnung genehmigen";
$MESS["LIBTA_STATE2"] = "Genehmigt ({=Variable:Approver_printable})";
$MESS["LIBTA_T_SSTA2"] = "Status: Genehmigt";
$MESS["LIBTA_STATE3"] = "Nicht genehmigt ({=Variable:Approver_printable})";
$MESS["LIBTA_T_SSTA3"] = "Status: Nicht genehmigt";
$MESS["LIBTA_T_ASFA3"] = "Dokumentfeld \"Genehmigt\" definieren";
$MESS["LIBTA_T_IFELSEA2"] = "Die Rechnung ist genehmigt";
$MESS["LIBTA_T_IFELSEBA3"] = "Ja";
$MESS["LIBTA_SMA_MESSAGE_2"] = "Rechnung genehmigen

Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_T_SMA_MESSAGE_2"] = "Nachricht: Die Rechnung ist genehmigt";
$MESS["LIBTA_T_SPAX2"] = "Zugriffsrechte definieren: Finanzdirektion";
$MESS["LIBTA_SMA_MESSAGE_3"] = "Antrag auf Bezahlung einer Rechnung

Genehmigt: {=Variable:Approver_printable}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}

{=Variable:Link}{=Document:ID}/

Aufgaben:
{=Variable:TasksLink}";
$MESS["LIBTA_T_SMA_MESSAGE_3"] = "Nachricht: Antrag auf Genehmigung einer Rechnung";
$MESS["LIBTA_XMA_MESSAGES_2"] = "Unternehmensportal: Bezahlung der Rechnung genehmigen";
$MESS["LIBTA_XMA_MESSAGET_2"] = "Antrag auf Bezahlung einer Rechnung

Genehmigt: {=Variable:Approver_printable}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}
Kostenstelle: {=Document:PROPERTY_BDT}

{=Variable:Link}{=Document:ID}/

Aufgaben:
{=Variable:TasksLink}";
$MESS["LIBTA_T_XMA_MESSAGES_2"] = "Nachricht: Genehmigung der Bezahlung";
$MESS["LIBTA_STATE4"] = "Bezahlung der Rechnung genehmigen";
$MESS["LIBTA_T_SSTA4"] = "Status: Genehmigung der Rechnungsbezahlung";
$MESS["LIBTA_AAQN2"] = "Bezahlung der Rechnung genehmigen \"{=Document:NAME}\"";
$MESS["LIBTA_AAQD2"] = "Sie müssen die Bezahlung der Rechnung entweder genehmigen oder ablehnen

Genehmigt: {=Variable:Approver_printable}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}
Kostenstelle: {=Document:PROPERTY_BDT}
Datei: {=Variable:Domain}{=Document:PROPERTY_FILE}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_T_AAQN2"] = "Bestätigung über Bezahlung der Rechnung";
$MESS["LIBTA_T_SVWA3"] = "Änderung von Variablen";
$MESS["LIBTA_STATE5"] = "Die Bezahlung wurde genehmigt.";
$MESS["LIBTA_T_SSTA5"] = "Status: Bezahlung genehmigt";
$MESS["LIBTA_SMA_MESSAGE_4"] = "Bezahlung der Rechnung ist genehmigt.

Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_T_SMA_MESSAGE_4"] = "Nachricht: Bezahlung wurde genehmigt.";
$MESS["LIBTA_T_SPAX3"] = "Zugriffsrechte definieren: Buchhaltung";
$MESS["LIBTA_SMA_MESSAGE_5"] = "Antrag auf Bezahlung einer Rechnung

Bezahlung genehmigt: {=Variable:PaymentApprover_printable}
Rechnung genehmigt: {=Variable:Approver_printable}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}
Kostenstelle: {=Document:PROPERTY_BDT}

{=Variable:Link}{=Document:ID}/

Aufgaben:
{=Variable:TasksLink}";
$MESS["LIBTA_T_SMA_MESSAGE_5"] = "Nachricht: Rechnung zur Bezahlung";
$MESS["LIBTA_XMA_MESSAGES_3"] = "Unternehmensportal: Rechnung zur Bezahlung";
$MESS["LIBTA_XMA_MESSAGET_3"] = "Antrag auf Bezahlung einer Rechnung

Bezahlung genehmigt: {=Variable:PaymentApprover_printable}
Rechnung genehmigt: {=Variable:Approver_printable}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}
Kostenstelle: {=Document:PROPERTY_BDT}

{=Variable:Link}{=Document:ID}/

Aufgaben:
{=Variable:TasksLink}";
$MESS["LIBTA_T_XMA_MESSAGES_3"] = "Nachricht: Rechnung zur Bezahlung";
$MESS["LIBTA_STATE6"] = "Bezahlung wird erwartet";
$MESS["LIBTA_T_SSTA6"] = "Status: Bezahlung erwartet";
$MESS["LIBTA_T_ASFA4"] = "Dokumentänderung";
$MESS["LIBTA_STATE7"] = "Bereits bezahlt";
$MESS["LIBTA_T_SSTA7"] = "Status: Rechnung bezahlt";
$MESS["LIBTA_SMA_MESSAGE_6"] = "Die Rechnung wurde bezahlt. Rechnungsrelevante Dokumente sind erforderlich.

Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}

Wichtig! Alle Dokumente müssen innerhalb von 5 Tagen nach dem Datum der Rechnungsbezahlung vorgelegt werden.";
$MESS["LIBTA_T_SMA_MESSAGE_6"] = "Nachricht: Die Rechnung wurde bezahlt.";
$MESS["LIBTA_T_SPAX4"] = "Zugriffsrechte definieren: Dokumentsachbearbeitung";
$MESS["LIBTA_SMA_MESSAGE_7"] = "Alle rechnungsrelevanten Dokumente fertig zusammengestellt

Bezahlt am: {=Document:PROPERTY_DATE_PAY}
Zahlungsanweisung Nr.: {=Document:PROPERTY_NUM_PAY}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}

{=Variable:Link}{=Document:ID}/

Aufgaben:
{=Variable:TasksLink}";
$MESS["LIBTA_T_SMA_MESSAGE_7"] = "Nachricht: Alle Dokumente fertig zusammengestellt";
$MESS["LIBTA_T_ASFA5"] = "Dokumentänderung";
$MESS["LIBTA_STATE8"] = "Abgeschlossen";
$MESS["LIBTA_T_SSTA8"] = "Status: Rechnung abgeschlossen";
$MESS["LIBTA_SMA_MESSAGE_8"] = "Alle Dokumente liegen vor. Workflow bezüglich der Rechnung ist abgeschlossen.

Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_T_SMA_MESSAGE_8"] = "Nachricht: Dokumente liegen vor";
$MESS["LIBTA_STATE9"] = "Bezahlung wurde abgelehnt";
$MESS["LIBTA_T_SSTA9"] = "Status: Bezahlung abgelehnt";
$MESS["LIBTA_SMA_MESSAGE_9"] = "Bezahlung der Rechnung wurde abgelehnt

Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_T_SMA_MESSAGE_9"] = "Nachricht: Bezahlung wurde nicht genehmigt";
$MESS["LIBTA_T_IFELSEBA4"] = "Nein";
$MESS["LIBTA_SMA_MESSAGE_10"] = "Rechnung wurde nicht genehmigt

Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_T_SMA_MESSAGE_10"] = "Nachricht. Rechnung wurde nicht genehmigt";
$MESS["LIBTA_T_SPAX5"] = "Zugriffsrechte definieren: Endgenehmigung";
$MESS["LIBTA_V_BK"] = "Buchhaltung (Genehmigung)";
$MESS["LIBTA_V_MNG"] = "Geschäftsführung";
$MESS["LIBTA_V_APPRU"] = "Vorgesetzter";
$MESS["LIBTA_V_BKP"] = "Buchhaltung (Bezahlung)";
$MESS["LIBTA_V_BKD"] = "Buchhaltung (Dokumente)";
$MESS["LIBTA_V_MAPPR"] = "Geschäftsführung (Genehmigung)";
$MESS["LIBTA_V_LINK"] = "Link";
$MESS["LIBTA_V_TLINK"] = "Link zu den Aufgaben";
$MESS["LIBTA_V_PDATE"] = "Zahlungsdatum";
$MESS["LIBTA_V_PNUM"] = "Zahlungsanweisung";
$MESS["LIBTA_V_APPR"] = "Bezahlung genehmigt";
$MESS["LIBTA_BP_TITLE"] = "Rechnungen";
$MESS["LIBTA_RIA10_NAME"] = "Rechnung bezahlen \"{=Document:NAME}\"";
$MESS["LIBTA_RIA10_DESCR"] = "Rechnung bezahlen

Bezahlung genehmigt: {=Variable:PaymentApprover_printable}
Rechnung genehmigt: {=Variable:Approver_printable}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Erstellt von: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}
Kostenstelle: {=Document:PROPERTY_BDT}
Datei: {=Variable:Domain}{=Document:PROPERTY_FILE}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_RIA10_R1"] = "Bezahlt am";
$MESS["LIBTA_RIA10_R2"] = "Zahlungsanweisung Nr.";
$MESS["LIBTA_T_RIA10"] = "Bezahlung der Rechnung";
$MESS["LIBTA_RRA15_NAME"] = "Rechnungsrelevante Dokumente fertig zusammenstellen \"{=Document:NAME}\"";
$MESS["LIBTA_RRA15_DESCR"] = "Alle rechnungsrelevante Dokumente müssen fertig zusammengestellt werden

Bezahlung genehmigt: {=Variable:PaymentApprover_printable}
Rechnung genehmigt: {=Variable:Approver_printable}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}
Kostenstelle: {=Document:PROPERTY_BDT}
Datei: {=Variable:Domain}{=Document:PROPERTY_FILE}

{=Variable:Link}{=Document:ID}/

Wichtig! Alle Dokumente müssen innerhalb von 5 Tagen nach dem Datum der Erbringung von Dienstleistungen vorgelegt werden.";
$MESS["LIBTA_RRA15_SM"] = "Dokumente fertig zusammenstellen";
$MESS["LIBTA_RRA15_TASKBUTTON"] = "Dokumente fertig zusammengestellt";
$MESS["LIBTA_T_RRA15"] = "Alle rechnungsrelevanten Dokumente";
$MESS["LIBTA_RRA17_NAME"] = "Eingang von allen rechnungsrelevanten Dokumenten bestätigen \"{=Document:NAME}\"";
$MESS["LIBTA_RRA17_DESCR"] = "Alle rechnungsrelevanten Dokumente liegen vor.

Bezahlt am: {=Document:PROPERTY_DATE_PAY}
Zahlungsanweisung Nr.: {=Document:PROPERTY_NUM_PAY}
Bezahlung genehmigt: {=Variable:PaymentApprover_printable}
Rechnung genehmigt: {=Variable:Approver_printable}
Erstellt von: {=Document:CREATED_BY_PRINTABLE}
Erstellt am: {=Document:DATE_CREATE}
Name: {=Document:NAME}
Typ: {=Document:PROPERTY_TYPE}
Rechnungsnummer und -datum: {=Document:PROPERTY_NUM_DATE}
Betrag: {=Document:PROPERTY_SUM}
Kostenstelle: {=Document:PROPERTY_BDT}
Datei: {=Variable:Domain}{=Document:PROPERTY_FILE}

{=Variable:Link}{=Document:ID}/";
$MESS["LIBTA_RRA17_BUTTON"] = "Alle Dokumente liegen vor";
$MESS["LIBTA_T_RRA17_NAME"] = "Alle Dokumente liegen vor";
$MESS["LIBTA_V_DOMAIN"] = "Domain";
?>