# Maakplek #projectupdates fotogallerij

### Functions
- Scrape afbeeldingen van Maakplek Mattermost #projectupdates
- Proxy om om de CORS van de Mattermost server heen te bakken
- Gebaseerd op session token want kan geen personal tokens maken
- Cached afbeeldingen in lokale cache-folder om liever te zijn voor Mattermost server
- 1x per maand cache invalideren via PHP.

### Dependencies
- CURL
- PHP
- beetje internet
