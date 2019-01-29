# IPSblink
Lässt beliebige Aktoren blinken
Es werden immer die zu schaltenden Variablen unter der Aktor-Instanz verlinkt.


### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [PHP-Befehlsreferenz](#5-php-befehlsreferenz)
6. [Fehlermeldungen](#6-fehler)


### 1. Funktionsumfang

* Nur eingeschaltete Aktoren benutzen
* Letzter Zustand
* Blinkanzahl
* Verweildauer im Zustand "Ein"
* Verweildauer im Zustand "Aus"
* Konfiguration der Zielvariablen via Listenauswahl


### 2. Voraussetzungen

- IP-Symcon ab Version 4.2


### 3. Software-Installation

Über den Modul-Store bzw. Module-Control folgende URL hinzufügen:  
`https://github.com/Astyc84/Astyc84Misc`  


### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'IPSblink'-Modul unter dem Hersteller 'Astic84' aufgeführt.  

__Konfigurationsseite__:

Name                  				| Beschreibung
----------------------------------- | ---------------------------------
Nur eingeschaltete Aktoren benutzen | Es werden nur Aktoren benutzt, die gerade eingeschaltet sind (z.B. für eine Lichtklingel)
Letzter Zustand						| Soll der Aktor beim letzten Umschalten ein- oder ausgeschaltet bleiben bzw. den vorherigen Zustand wieder annehmen
Blinkanzahl 						| Einstellbare Anzahl von 0-2147483647 (Sollte reichen) :-)
Verweildauer im Zustand "Ein"		| Der Aktor bleibt für die Anzahl der Sekunden im Zustand "Ein"
Verweildauer im Zustand "Aus"		| Der Aktor bleibt für die Anzahl der Sekunden im Zustand "Aus"
Geräte        						| Diese Liste beinhaltet die Variablen, welche bei Auslösung blinken sollen.


### 5. PHP-Befehlsreferenz
`IPSblink_Blink(integer $InstanzID);`  
Die verlinkten Aktoren fangen mit der hinterlegten Konfiguration der Instanz an zu blinken.  
Die Funktion liefert keinerlei Rückgabewert.

`IPSblink_Stop(integer $InstanzID);`  
Die verlinkten Aktoren hören auf zu blinken und nehmen ggf. einen definierten Zustand wieder an.  
Die Funktion liefert keinerlei Rückgabewert.    

`IPSblink_SetOn(int $count);`
Wird nur für interne Zwecke (Event) benutzt.
  
`IPSblink_SetOff(int $count);`
Wird nur für interne Zwecke (Event) benutzt.


### 6. Fehlermeldungen

Sollte es zu Fehlermeldungen kommen sollten diese am besten über das Forum:  
`https://www.symcon.de/forum/threads/36154-Blinkmodul`  
ausgetauscht werden.