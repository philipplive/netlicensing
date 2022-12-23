# NetLicensing API

Mit diesem Plugin kann direkt die NetLicensing API von https://netlicensing.io/ angesprochen werden.

## Einbinden in Wordpress
```
$nl = NetLicensingSystem::getInstance();

// User Anlegen
$nl->createLicensee('PMWE4TSJY','UserID123',true);

// Shop-URL von bestehendem User abfragen
$nl->getLicenseeById('UserID123')->getShopURL();
```

### Widgets
Folgende Widgets werden aktuell angeboten:
* Öffnungszeiten inkl. Feiertage

## Installation

Um das Plugin zu installieren, erstellen Sie bitte folgenden Ordner "/wp-content/plugins/**netlicensing**" und extrahieren den Zip-Download von GitHub direkt hinein. Das Plugin kann nun im WordPress Backend aktiviert werden. Im Hauptmenu erscheint nun ein entsprechender Menupunkt "NetLicensing".

Um nun die Schnittstelle nutzen zu können, muss im Plugin ein API-Token hinterlegt werden. Der API-Token hierfür kann im NetLicensing-Backend generiert werden. 