# ADR 0005 — Google Maps: deeplink met pins boven KML-export

- **Status:** Accepted
- **Datum:** 2026-09-20
- **Context:** #20 (route openen zonder GPX-viewer)

## Beslissing

Een route is te openen in Google Maps via een **deeplink naar de Maps URL-API**
(`https://www.google.com/maps/dir/?api=1`), opgebouwd uit start, eind en maximaal negen
tussenpunten die uit het GPX-spoor zijn afgeleid.

De pins worden **bij de upload berekend en opgeslagen** in `routes.map_waypoints`, naast de
metadata die er al zo in staat (`bbox`, `start_lat`, `waypoint_count`). De reductie gebeurt met
`WaypointReducer`, die de bestaande `LineSimplifier` (Douglas-Peucker) in een lus draait tot het
aantal punten onder de limiet zit. `GoogleMapsLinkBuilder` bouwt uit die punten de URL en doet
zelf geen IO.

## Alternatieven overwogen

1. **KML- of KMZ-export voor Google My Maps**
   - **Pro:** het volledige spoor blijft intact, geen limiet op het aantal punten.
   - **Con:** de gebruiker moet het bestand handmatig importeren in My Maps, en het resultaat is
     een weergave zonder navigatie. Dat lost het probleem niet op: wie wil rijden heeft juist
     navigatie nodig, niet nog een viewer.
2. **Google Maps JS API insluiten**
   - **Con:** vereist een billing-account vanaf het eerste request en vendor-lock, en de TOS
     verbiedt opslag van resultaten. ADR 0002 wees dit al af voor de kaartweergave; dezelfde
     bezwaren gelden hier.
3. **Kopieerbare coordinatenlijst**
   - **Pro:** triviaal te bouwen.
   - **Con:** de gebruiker moet zelf plakken en pinnen, wat precies het handwerk is dat de wens
     wilde wegnemen.
4. **Pins per request uit het GPX-bestand afleiden**
   - **Con:** `RouteResource` bedient ook de gepagineerde lijst-endpoint. Dat zou 25 keer
     `parseFile()` per request betekenen. Het GPX-bestand verandert nooit meer na de upload
     (`UpdateRouteRequest` accepteert alleen metadata), dus opslaan is zowel goedkoper als correct.

## Gevolgen

- **De link is een benadering.** Google berekent tussen de pins zelf een route over de weg. Met
  vormgetrouw gekozen pins ligt dat meestal dicht bij het spoor, maar bij een route over kleine
  wegen kan Google alsnog een snelweg kiezen. Het originele `.gpx` blijft daarom beschikbaar.
- **Reductie op richtingswissels, niet op afstand.** Douglas-Peucker zet de schaarse pins waar de
  route van richting verandert, in plaats van midden op een recht stuk. Dat houdt de afwijking zo
  klein mogelijk binnen de limiet die Google oplegt.
- **Bestaande routes hebben de kolom niet.** `routes:backfill-waypoints` vult die, idempotent, en
  slaat routes met een ontbrekend of ongeldig GPX-bestand over zonder te falen.
- **Geen API-key, geen billing, geen extra dependency.** De URL-API is een gewone link.

## Openstaande punten

- Google's documentatie stelt dat het aantal waypoints per platform verschilt: maximaal drie op
  mobiele browsers, negen daarbuiten. Daarom is het maximum een parameter op
  `GoogleMapsLinkBuilder::build()` en geen harde constante. Blijkt in de praktijk dat mobiele
  gebruikers pins kwijtraken, dan volstaat een lagere default; de opslag hoeft niet mee te
  veranderen.
- `travelmode=driving` ligt vast. Google's URL-API kent geen motorfiets-modus, dus een keuze
  tussen auto en motor is niet mogelijk.
