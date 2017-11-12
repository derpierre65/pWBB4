# pWBB4
pWBB4 erstellt eine Verbindung von SA:MP zum WBB4 und kann viele Funktionen ausführen.

# Neue Funktionen?

Du möchtest eine neue Funktion bei der Include haben?
Erstelle eine neue Aufgabe und ich werde sie mir anschauen und eventuell in der nächsten Version implementieren.

# Einfache Installation in 6 Schritten

1. Verschiebe `install.php` auf dem Web Server vom WBB4 Forum (es muss nicht im selben Verzeichnis sein, wäre aber sinnvoll).
2. Führe `install.php` über deinen Browser aus.
3. Setz das Verzeichnis auf dass vom WBB4 (Beispiel: C:/xampp/htdocs/WBB4/ oder /var/www/htdocs/wbb4/).
4. (optional) Ändere den Sicherheitskey auf dein gewünschten Key.
5. (optional) Aktivere `Zugriff nur über eine IP aktivieren` um die Sicherheit zu erhöhen.
6. (optional) Trage die IP vom SA:MP Server ein.
7. Klicke auf `Weiter`.
8. `#define pWBB_CONNECT_KEY "deinkey"` vor dem Include von pWBB4 einfügen.
9. `#define pWBB_URL "Deine Forum URL"` vor dem Include von pWBB4 einfügen (Beispiele: 'github.com', 'community.woltlab.com' (nur die TLD/IP, ohne www oder http/https).
10. Installation abgeschlossen

# WSC 3.0/3.1 oder WSF 5.0/5.1

Um pWBB4 für WSC 3.0 bzw. 3.1 oder WSF 5.0 bzw. 5.1 zu verwenden muss in der Installation "Ich nutze WSC 3.0/3.1 oder WSF 5.0/5.1" ausgewählt werden.
Für vorhandene Installationen ist es empfehlenswert die `install.php` neu auszuführen, diese nutzen die API für WBB 4.0/4.1 bzw. WCF 2.0/2.1.
Außerdem muss die `pWBB4-WSF.inc` Include für Pawno verwendet werden.

# pWBB4 updaten

1. `samp.php` neu runterladen und mit der vorhandenen überschreiben.
2. Gleiche muss mit der `pawno/include/pWBB4.inc` getan werden.