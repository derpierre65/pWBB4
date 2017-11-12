# pWBB4
pWBB4 erstellt eine Verbindung von SA:MP zum WBB4 und kann viele Funktionen ausführen.

[Branch für WBB 5 bzw. WSC](https://github.com/derpierre65/pWBB4/tree/wsc)
[Branch für WBB 4 bzw. WCF](https://github.com/derpierre65/pWBB4/tree/master)

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

# pWBB4 updaten

1. `samp.php` neu runterladen und mit der vorhandenen überschreiben.
2. Gleiche muss mit der `pawno/include/pWBB4.inc` getan werden.