# ADR 0001 — GPX parsing via SimpleXML

- **Status:** Accepted
- **Datum:** 2026-04-25
- **Context:** MVP-004 (GPX Upload/Preview)

## Beslissing

We parsen geüploade `.gpx` bestanden met PHP's ingebouwde `simplexml_load_string` in `App\Services\Gpx\GpxParser`, met `LIBXML_NONET` ter bescherming tegen XXE.

## Alternatieven overwogen

1. **Externe library** (bv. `php-coords/gpx-parser`, `sibyx/phpgpx`)
   - **Pro:** geteste edge cases, kant-en-klare DTO's.
   - **Con:** extra dependency voor een eenvoudige format; veel libraries onderhouden slecht of vereisen specifieke GPX 1.0/1.1 strictness; CLAUDE.md verbiedt nieuwe packages zonder approval.
2. **Regex / handmatig string-parsing**
   - **Pro:** geen XML-DOM overhead.
   - **Con:** brittle, geen namespace-afhandeling, foutgevoelig — onaanvaardbaar voor user-uploads.
3. **`XMLReader` (streaming)**
   - **Pro:** geheugenefficiënt voor grote bestanden.
   - **Con:** complexere code; voor de 10 MB upload limiet is geheugen geen reëel probleem (ruwweg 100k trkpts = enkele MB intern).
4. **`DOMDocument` + XPath**
   - **Pro:** krachtigere querying.
   - **Con:** verbose voor onze beperkte querybehoefte; SimpleXML met `xpath()` dekt onze use cases (zie `//*[local-name()="trkpt"]`).

## Gevolgen

- Geen externe dependency.
- Namespace-onafhankelijke querying via `local-name()` in xpath, dus zowel GPX 1.0 (zonder namespace) als 1.1 (met `http://www.topografix.com/GPX/1/1`) worden ondersteund.
- Voor grotere bestanden of ander formaat (FIT, TCX) kunnen we later naar `XMLReader` of een dedicated library switchen — `GpxParser` heeft een nauwe interface (`parseFile`, `parseString` → `GpxParseResult`), dus de impact blijft beperkt.

## Mitigaties / opmerkingen

- XXE-bescherming via `LIBXML_NONET` (en `libxml_use_internal_errors(true)` om PHP-warnings niet te lekken).
- Limiet 10 MB op upload (`StoreRouteRequest::rules()`).
- Bij parse-fouten gooit de service `InvalidGpxException`; de controller mapt dat naar HTTP 422.
