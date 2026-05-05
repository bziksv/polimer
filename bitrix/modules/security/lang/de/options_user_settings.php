<?php
$MESS["SECT_OTP_PUSH_DESC"] = "Installieren Sie die <b>Bitrix24 mobile App</b>. Sie wird zur Bestätigung Ihres Logins in Bitrix24 verwendet.";
$MESS["SEC_OTP_CONNECTED"] = "Angebunden";
$MESS["SEC_OTP_CONNECT_DEVICE"] = "Dongle anbinden";
$MESS["SEC_OTP_CONNECT_DEVICE_TITLE"] = "Dongle anbinden";
$MESS["SEC_OTP_CONNECT_DONE"] = "Fertig";
$MESS["SEC_OTP_CONNECT_MOBILE"] = "Mobiles Gerät anbinden";
$MESS["SEC_OTP_CONNECT_MOBILE_ENTER_CODE"] = "Code eingeben";
$MESS["SEC_OTP_CONNECT_MOBILE_ENTER_NEXT_CODE"] = "Nächsten Code eingeben";
$MESS["SEC_OTP_CONNECT_MOBILE_INPUT_DESCRIPTION"] = "Nachdem der Code erfolgreich eingescannt oder manuell eingegeben wurde, zeigt Ihr mobiles Gerät einen Code an, den Sie unten eingeben müssen.";
$MESS["SEC_OTP_CONNECT_MOBILE_INPUT_NEXT_DESCRIPTION"] = "Der OTP-Algorithmus verlangt zwei Codes für Authentifizierung. Generieren Sie bitte den nächsten Code und geben Sie ihn unten ein.";
$MESS["SEC_OTP_CONNECT_MOBILE_MANUAL_INPUT"] = "Um Daten manuell einzugeben, müssen Sie die Website-Adresse, Ihre E-Mail oder Login, den Geheimcode auf dem Bild angeben und dann den Schlüsseltyp auswählen.";
$MESS["SEC_OTP_CONNECT_MOBILE_MANUAL_INPUT_HOTP"] = "Zähler-basiert";
$MESS["SEC_OTP_CONNECT_MOBILE_MANUAL_INPUT_TOTP"] = "Zeit-basiert";
$MESS["SEC_OTP_CONNECT_MOBILE_SCAN_QR"] = "Halten Sie Ihr mobiles Gerät vor dem Bildschirm und warten Sie, bis die Anwendung den Code eingescannt hat.";
$MESS["SEC_OTP_CONNECT_MOBILE_STEP_1"] = "Downloaden Sie die mobile App Bitrix OTP für Ihr Telefon auf <a href=\" https://itunes.apple.com/de/app/bitrix24-otp/id929604673?l=de \" target=\"_new\">AppStore</a> oder auf <a href=\"https://play.google.com/store/apps/details?id=com.bitrixsoft.otp\" target=\"_new\">GooglePlay</a>";
$MESS["SEC_OTP_CONNECT_MOBILE_STEP_2"] = "Starten Sie die Anwendung und klicken Sie auf <b>Konfigurieren</b>";
$MESS["SEC_OTP_CONNECT_MOBILE_STEP_3"] = "Wählen Sie, wie Sie die Daten eingeben möchten: mit dem QR-Code oder manuell";
$MESS["SEC_OTP_CONNECT_MOBILE_TITLE"] = "Mobiles Gerät anbinden";
$MESS["SEC_OTP_CONNECT_NEW_DEVICE"] = "Neuen Dongle anbinden";
$MESS["SEC_OTP_CONNECT_NEW_MOBILE"] = "Neues mobiles Gerät anbinden";
$MESS["SEC_OTP_DEACTIVATE_UNTIL"] = "Deaktiviert bis #DATE#";
$MESS["SEC_OTP_DESCRIPTION_ABOUT"] = "Einmalpasswort (OTP) wurde als Teil der OATH-Initiative entwickelt.<br>
OTP basiert auf HMAC und SHA-1/SHA-256/SHA-512. Zurzeit werden zwei Algorithmen der Codegenerierung unterstützt:
<ul><li>Zähler-basiert (HMAC-basiertes Einmalpasswort, HOTP) ist beschrieben in <a href=\"https://tools.ietf.org/html/rfc4226\" target=\"_blank\">RFC4226</a></li>
<li>Zeit-basiert (Zeit-basiertes Einmalpasswort, TOTP) ist beschrieben in <a href=\"https://tools.ietf.org/html/rfc6238\" target=\"_blank\">RFC6238</a></li></ul>
Um den OTP-Wert zu kalkulieren, nimmt der Algorithmus zwei Eingabeparameter: einen Geheimschlüssel (Ursprungswert) und einen aktuellen Zählerwert (die Anzahl erforderlicher Zyklen der Generierung oder die aktuelle Zeit, abhängig vom Algorithmus). Der Ursprungswert wird auf dem Gerät sowie auf der Website gespeichert, sobald das Gerät initialisiert wurde. Wenn HOTP benutzt wird, wird Zähler im Gerät bei jeder OTP-Generierung erhöht, während der Server-Zähler bei jeder erfolgreichen OTP-Authentifizierung geändert wird. Bei Nutzung von TOTP werden keine Zähler im Gerät gespeichert, der Server kontrolliert alle möglichen Zeitänderungen am Gerät bei jeder erfolgreichen OTP-Authentifizierung.<br>
Jedes OTP-Gerät in der Partie enthält eine verschlüsselte Datei mit Ursprungswerten (Geheimschlüssel), die Datei ist verbunden mit der Seriennummer des jeweiligen Geräts, welche auf dem Gerät selbst zu finden ist.<br>
Wird die Synchronisierung der Gerät- und Server-Zähler gestört, kann die Synchronisierung wiederhergestellt werden, indem die Serverwerte denen auf dem Gerät gleichgesetzt werden. Dafür muss der Administrator (oder ein Nutzer mit entsprechenden Zugriffsrechten) zwei nacheinander folgende OTPs generieren und diese auf der Website eingeben.<br>
Sie können die mobile App in AppStore und GooglePlay finden.
";
$MESS["SEC_OTP_DESCRIPTION_ABOUT_TITLE"] = "Beschreibung";
$MESS["SEC_OTP_DESCRIPTION_ACTIVATION"] = "Ein Enmalcode für Zwei-Faktor-Authentifizierung kann mit einem speziellen Gerät (Dongle) oder mit einer kostenlosen Anwendung (Bitrix OTP) bekommen werden, welche jeder Nutzer auf seinem mobilen Gerät installiert haben soll.<br>
Um einen Dongle zu aktivieren, muss der Administrator das Nutzerprofil öffnen und dort zwei Passwörter, generiert durch Dongle, eins nach dem anderen eingeben.<br>
Um einen Einmalcode auf einem mobilen Gerät erhalten zu können, müssen Nutzer die App herunterladen und starten, sodass sie den QR-Code auf der Seite der Einstellungen in ihrem Nutzerprofil einscannen oder Account-Daten manuell eingeben können.
";
$MESS["SEC_OTP_DESCRIPTION_ACTIVATION_TITLE"] = "Aktivierung";
$MESS["SEC_OTP_DESCRIPTION_INTRO_INTRANET"] = "Derzeit  verwendet ein Nutzer einen Login und ein Passwort, um sich auf Ihrer Website anzumelden. Allerding gibt es diverse Schadprogramme, mit denen Kriminelle auf einen Computer eindringen und dort Daten stehlen können, wenn bspw. ein Nutzer sein Passwort speichert.<br>
<b>Zwei-Faktor-Authentifizierung</b> ist eine empfohlene Option, um sich gegen Hacker-Software zu schützen. Jedes Mal, wenn Nutzer sich im System einloggen, müssen sie zwei Verifizierungsschritte machen. Zuerst müssen sie Login und Passwort eingeben. Dann geben sie einen einmaligen Sicherheitscode ein, den sie auf ihre mobilen Geräte gesendet bekommen. Selbst wenn Login und Passwort gestohlen werden, können Kriminelle diese Daten nicht missbrauchen, weil sie den Sicherheitscode nicht wissen.
";
$MESS["SEC_OTP_DESCRIPTION_INTRO_SITE"] = "Derzeit  verwendet ein Nutzer einen Login und ein Passwort, um sich auf Ihrer Website anzumelden. Allerding gibt es diverse Schadprogramme, mit denen Kriminelle auf einen Computer eindringen und dort Daten stehlen können, wenn bspw. ein Nutzer sein Passwort speichert.<br>
<b>Zwei-Faktor-Authentifizierung</b> ist eine empfohlene Option, um sich gegen Hacker-Software zu schützen. Jedes Mal, wenn Nutzer sich im System einloggen, müssen sie zwei Verifizierungsschritte machen. Zuerst müssen sie Login und Passwort eingeben. Dann geben sie einen einmaligen Sicherheitscode ein, den sie auf ihre mobilen Geräte gesendet bekommen. Selbst wenn Login und Passwort gestohlen werden, können Kriminelle diese Daten nicht missbrauchen, weil sie den Sicherheitscode nicht wissen.
";
$MESS["SEC_OTP_DESCRIPTION_INTRO_TITLE"] = "Einmalpasswort";
$MESS["SEC_OTP_DESCRIPTION_USING"] = "Wenn Zwei-Faktor-Authentifizierung aktiviert ist, müssen Nutzer zwei Schritte der Verifizierung beim Einloggen machen. <br>
Zuerst geben sie wie immer ihre E-Mail und das Passwort ein. <br>
Dann geben sie einen einmaligen Sicherheitscode ein, den sie auf ihre mobile Geräte zugesendet oder mithilfe eines speziellen Dongles bekommen.
";
$MESS["SEC_OTP_DESCRIPTION_USING_STEP_0"] = "Schritt 1";
$MESS["SEC_OTP_DESCRIPTION_USING_STEP_1"] = "Schritt 2";
$MESS["SEC_OTP_DESCRIPTION_USING_TITLE"] = "Wie benutzt man Einmalpasswörter";
$MESS["SEC_OTP_DISABLE"] = "Deaktivieren";
$MESS["SEC_OTP_ENABLE"] = "Aktivieren";
$MESS["SEC_OTP_ERROR_TITLE"] = "Fehler beim Speichern.";
$MESS["SEC_OTP_INIT"] = "Initialisierung";
$MESS["SEC_OTP_MANDATORY_ALMOST_EXPIRED"] = "Die Frist für die Einstellung der Zwei-Faktor-Authentifizierung durch den Nutzer endet am #DATE#.";
$MESS["SEC_OTP_MANDATORY_DEFFER"] = "Verlängern";
$MESS["SEC_OTP_MANDATORY_DISABLED"] = "Erforderliche Zwei-Faktor-Authentifizierung ist deaktiviert.";
$MESS["SEC_OTP_MANDATORY_ENABLE"] = "Aktivierung der Zwei-Faktor-Authentifizierung erfordern in";
$MESS["SEC_OTP_MANDATORY_ENABLE_DEFAULT"] = "Aktivierung der Zwei-Faktor-Authentifizierung erfordern";
$MESS["SEC_OTP_MANDATORY_EXPIRED"] = "Die Frist für die Einstellung der Zwei-Faktor-Authentifizierung durch den Nutzer ist jetzt abgelaufen.";
$MESS["SEC_OTP_MOBILE_INPUT_METHODS_SEPARATOR"] = "oder";
$MESS["SEC_OTP_MOBILE_MANUAL_INPUT"] = "Code manuell eingeben";
$MESS["SEC_OTP_MOBILE_SCAN_QR"] = "QR-Code einscannen";
$MESS["SEC_OTP_NEW_ACCESS_DENIED"] = "Zugriff auf die Verwaltung von Zwei-Faktor-Authentifizierung wurde verweigert.";
$MESS["SEC_OTP_NEW_SWITCH_ON"] = "Zwei-Faktor-Authentifizierung aktivieren";
$MESS["SEC_OTP_NO_DAYS"] = "Für immer";
$MESS["SEC_OTP_PASS1"] = "Der erste Wert des Passworts (drücken Sie die Taste auf dem Gerät)";
$MESS["SEC_OTP_PASS2"] = "Weiterer Wert des Passworts (drücken Sie erneut die Taste auf dem Gerät)";
$MESS["SEC_OTP_PUSH_ERROR"] = "Die mobile App konnte nicht verbunden werden. Stellen Sie sicher, dass die Zeiteinstellungen auf Ihrem Telefon korrekt sind, und versuchen Sie es erneut.";
$MESS["SEC_OTP_PUSH_SAVING"] = "Einstellungen werden gespeichert";
$MESS["SEC_OTP_PUSH_STEP1"] = "Stellen Sie sicher, dass die Uhrzeit und Zeitzone auf Ihrem Telefon korrekt sind, sonst können Sie  nicht verbinden.";
$MESS["SEC_OTP_PUSH_STEP2"] = "Scann Sie den QR-Code mit Ihrem Gerät.";
$MESS["SEC_OTP_PUSH_STEP3"] = "Folgen Sie dem Link, den Sie auf Ihrem Telefon sehen.";
$MESS["SEC_OTP_PUSH_STEP4"] = "Installieren Sie die mobile App.";
$MESS["SEC_OTP_PUSH_SUCCESS"] = "Die App wurde verbunden.";
$MESS["SEC_OTP_RECOVERY_CODES_BUTTON"] = "Reserve-Codes";
$MESS["SEC_OTP_RECOVERY_CODES_DESCRIPTION"] = "Kopieren Sie die Reserve-Codes, die Sie benötigen können, wenn Sie Ihr mobiles Gerät verloren haben oder einen Code via App aus irgendeinem Grund nicht erhalten können.";
$MESS["SEC_OTP_RECOVERY_CODES_NOTE"] = "Ein Code kann nur einmal benutzt werden. Hinweis: Bereits benutzte Codes können von der Liste gestrichen werden.";
$MESS["SEC_OTP_RECOVERY_CODES_PRINT"] = "Drucken";
$MESS["SEC_OTP_RECOVERY_CODES_REGENERATE"] = "Neue Codes generieren";
$MESS["SEC_OTP_RECOVERY_CODES_REGENERATE_DESCRIPTION"] = "Werden die Reserve-Codes knapp?<br/>
Erstellen Sie neue. <br/><br/>
Wenn Sie neue Codes erstellen, <br/>machen Sie dadurch die vorher erstellten ungültig.
";
$MESS["SEC_OTP_RECOVERY_CODES_SAVE_FILE"] = "Als Text-Datei speichern";
$MESS["SEC_OTP_RECOVERY_CODES_TITLE"] = "Reserve-Codes";
$MESS["SEC_OTP_RECOVERY_CODES_WARNING"] = "Bewahren Sie diese sorgfältig auf, bspw. in Ihrer Brieftasche. Jeder Code kann nur einmal benutzt werden.";
$MESS["SEC_OTP_SECRET_KEY"] = "Geheimes Kennwort (wird mit dem Gerät mitgeliefert)";
$MESS["SEC_OTP_START_TIMESTAMP"] = "Ursprünglicher Zeitwert (Unixzeit, bspw. 946684800 für den 01.01.2000; standardmäßig ist 0)";
$MESS["SEC_OTP_STATUS"] = "Aktueller Status";
$MESS["SEC_OTP_STATUS_ON"] = "Aktiviert";
$MESS["SEC_OTP_SYNC_NOW"] = "Synchronisieren";
$MESS["SEC_OTP_TYPE"] = "Algorithmus der Passwortgenerierung";
$MESS["SEC_OTP_UNKNOWN_ERROR"] = "Unerwarteter Fehler. Versuchen Sie bitte später erneut.";
$MESS["SEC_OTP_WAITING_INSTALL"] = "Warten auf Installation der App";
$MESS["SEC_OTP_WARNING_RECOVERY_CODES"] = "Zwei-Faktor Authentifizierung wurde aktiviert, aber Sie haben keine Reserve-Codes erstellt. Diese können aber erforderlich sein, wenn Sie Ihr mobiles Gerät verlieren oder den Code via App aus irgendeinem Grund nicht erhalten können.";
$MESS["security_TAB"] = "One-Time-Password";
$MESS["security_TAB_TITLE"] = "Erweiterte Authentifizierung mit One-Time-Password konfigurieren";
