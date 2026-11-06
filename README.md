# StuckMVC

Schlankes, selbst gebautes MVC-Framework für PHP mit Smarty-Templates. Es läuft in der Praxis auf
[stuck1a.de](https://www.stuck1a.de) (Repository *StucksSeiten*); dieses Repository enthält das Framework
(`core/`) samt einer minimalen Starter-Anwendung (`application/`).

- PHP **≥ 8.2**, Extensions `pdo` (`pdo_mysql` oder `pdo_sqlite`) und `mbstring`
- Template-Engine: Smarty 5, PSR-11 (Container), PSR-3 (Logger), PSR-16 (Cache)
- Abhängigkeiten via Composer (`vendor/` ist nicht versioniert)

> **Stand:** Dieses Repository wurde am 2026-11-06 aus dem Framework-Teil von StucksSeiten neu aufgesetzt.
> Der alte Stand von 2023 (v0.x mit Singletons, `cl`/`fnc`-Requests, Mapper/Facts) ist in der Git-Historie
> erhalten, aber **nicht kompatibel** zum aktuellen Aufbau.

## Schnellstart

```bash
composer install
cp configs.local.example.php configs.local.php   # Zugangsdaten eintragen, ggf. 'debug' => true
php -S localhost:8000 index.php                  # http://localhost:8000
```

Der eingebaute PHP-Webserver liefert statische Dateien aus `out/` direkt aus, alles andere geht an den Front-Controller.

## Aufbau

```
index.php                 Front-Controller
configs.php               Standard-Konfiguration (versioniert, KEINE Geheimnisse)
configs.local.php         Installations-spezifisch: Zugangsdaten, Debug (nicht versioniert)
core/                     Framework (Namespace Stuck1A\StuckMVC\Core, PSR-4)
  Kernel.php              Service-Registrierung und Request-Ablauf
  Container.php           Dependency-Injection-Container (PSR-11, Autowiring)
  Config/  Http/  Routing/  Controller/  View/  Widget/  Database/  Auth/  Cache/  Log/  Error/  Support/
application/              Deine Anwendung (Namespace Stuck1A\StuckMVC\Application, PSR-4)
  Controller/             Seiten- und Backend-Controller
  Model/                  Models (Active Record); enthalten: User + UserProvider als Login-Grundlage
  Widget/                 Widgets (Klasse + views/); enthalten: Breadcrumb
  Setup/, database/       Schema-Installer und SQL-Schema (MySQL/SQLite)
  views/                  Templates: pages/ (Inhaltsseiten), admin/ (Backend), classic/ (Theme)
out/                      Öffentliche Dateien (CSS, JS, Bilder, Downloads)
tmp/                      Cache, kompilierte Templates, Logs (nicht öffentlich)
bin/console               Kommandozeilen-Werkzeug
tests/                    PHPUnit-Tests des Frameworks
```

**Ablauf eines Requests:** `index.php` → `Kernel::handle()` → Route in der Routing-Tabelle suchen (sonst 404) →
bei POST: `ActionDispatcher` (CSRF-Prüfung, Action ausführen, Redirect) bzw. `WebhookController::receive()` →
bei GET: Controller der Route prüft die Berechtigung und rendert das Template → Antwort mit Security-Headern.

## Seiten, Controller, Actions

**Neue Inhaltsseite:** Template unter `application/views/pages/` ablegen – fertig. Aus
`pages/music/byartist.tpl` wird die Route `/music/byartist`, aus `pages/music/index.tpl` zusätzlich `/music`.
Verzeichnisse namens `inc` und Dateien `*.inc.tpl` werden übersprungen. Der Titel kommt aus dem
`{block 'title'}` des Templates oder aus `routing.pages` in der `configs.php`.

**Controller** sind optional und übernehmen Seiten per Attribut (der spezifischste Treffer gewinnt):

```php
#[Mapping('music')]                       // zuständig für /music und alles darunter
final class MusicController extends PageController
{
    protected function viewData(Request $request, Route $route): array
    {
        return ['albums' => ...];         // Variablen für das Template
    }
}
```

Backend-Seiten liegen unter `application/views/admin/` (Routen `/admin/...`) und sind nur für Administratoren
erreichbar (`BackendController`). Webhooks (z. B. Telegram) erben von `WebhookController` und prüfen den Absender selbst.

**Actions** (Formular-Verarbeitung): Nur explizit markierte Methoden sind aufrufbar, jedes Formular braucht `{csrf_field}`:

```php
#[Action('music.rate')]
public function rate(Request $request, Route $route): ?Response
{
    $this->flash('success', 'Danke!');
    return null;                          // null = zurück zur Seite (Post/Redirect/Get)
}
```

```smarty
<form method="post">
  {csrf_field}
  <input type="hidden" name="_action" value="music.rate">
</form>
```

## Widgets

Ein Widget ist eine Klasse `application/Widget/<Name>/<Name>Widget.php` mit Template in `views/`. Eingebunden wird
es mit `{include_widget name=breadcrumb}`; weitere Attribute kommen als `$params` an. Widgets werden zur Laufzeit
gerendert. Ein fehlerhaftes Widget bricht die Seite nicht ab: Der Fehler wird geloggt und im Debug-Modus angezeigt.

## Datenbank, Models & Login

```php
$user = User::findBy(['username' => 'admin']);
$user->email = 'neu@example.com';
$user->save();

User::query()->select('id', 'username')->where('role', 'admin')->orderBy('id', 'DESC')->get();

// beliebige Tabellen: Connection per Konstruktor-Injection holen (Stuck1A\StuckMVC\Core\Database\Connection)
$connection->table('log')->insert(['message' => 'Hallo']);
```

Alle Werte werden als gebundene Parameter übergeben, Spalten- und Tabellennamen werden validiert. `UPDATE`/`DELETE`
ohne Bedingung werden verweigert.

Die Authentifizierung (`Core\Auth`: Login-Drosselung, Session-Rotation, Idle-Timeout) arbeitet gegen das Interface
`UserProvider`; die Implementierung wird in `auth.provider` konfiguriert. Login-Seiten, Registrierung und
Passwort-Reset sind Sache der Anwendung (Beispiel: StucksSeiten).

## Template-Funktionen

| Funktion | Beschreibung |
|---|---|
| `{getRootLink}`, `{url path='music'}` | URLs (relativ zur Domain, ohne Host) |
| `{getImagesPath}`, `{getStylesPath}`, `{getScriptsPath}`, `{getDownloadsPath}` | Asset-Verzeichnisse |
| `{csrf_field}` | CSRF-Token für Formulare |
| `{include_widget name=...}` | Widget einbinden |
| `{emend old='..' new='..'}` | Durchgestrichener Text mit Korrektur |
| `$currentUser`, `$isAdmin`, `$currentRoute`, `$flashes`, `$debug` | Globale Template-Variablen |

Variablen werden automatisch HTML-escaped. Für vertrauenswürdiges HTML: `{$var nofilter}`.

## Konsole & Tests

```bash
php bin/console routes:list
php bin/console cache:clear
php bin/console db:install
php bin/console user:create admin --admin       # fragt das Passwort ab
php bin/console user:password admin

composer test                                   # PHPUnit
```

## Sicherheit

- Zugangsdaten nur in `configs.local.php`; `.htaccess` sperrt Konfiguration, Templates, `core/`, `application/`,
  `vendor/`, `tmp/`, `tests/`, `bin/` und versteckte Dateien.
- Formulare nur mit CSRF-Token, Actions nur über explizit markierte Methoden.
- Passwörter mit `password_hash()`, Login-Drosselung, Session-Rotation beim Login, Session-Cookies
  `HttpOnly`/`SameSite=Lax`/`Secure` (bei HTTPS), Abmeldung nach Inaktivität.
- Keine Fehlerdetails im Browser, solange `application.debug` aus ist.
- Security-Header (`nosniff`, `SAMEORIGIN`, Referrer-Policy, HSTS bei HTTPS), HTTPS-Zwang.

## Lizenz

Die Quelldateien tragen den Hinweis GPL v3 („FOR A SOMEWHAT FREER WORLD“); `composer.json` führt derzeit
`proprietary`. Eine `LICENSE`-Datei liegt noch nicht bei – das ist offen.
