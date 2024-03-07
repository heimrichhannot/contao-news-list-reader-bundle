# Changelog
All notable changes to this project will be documented in this file.

## [1.3.1] - 2024-03-07
- Fixed: duplicated content on multilingual occasions

## [1.3.0] - 2023-02-23
- Changed: refactored head bundle integration to event listener and head bundle version 1.11.0
- Changed: raised minimum contao and php version
- Fixed: missing head bundle dependency

## [1.2.4] - 2022-09-28
- Fixed: do not start a session in list view

## [1.2.3] - 2022-09-28
- Fixed: compatiblity with reader bundle

## [1.2.2] - 2022-05-31
- Added: license file
- Fixed: warning in php 8

## [1.2.1] - 2022-02-14

- Fixed: array index issues in php 8+

## [1.2.0] - 2021-10-27
- Added: php8 support

## [1.1.6] - 2021-08-10
- Changed: `NewsItemTrait::getDetailsUrl()` return type is now optionally a string

## [1.1.5] - 2020-11-06
- fixed url encode issue in `SearchListener`

## [1.1.4] - 2020-09-18
- added aria-label to enclosure link at news_full.html.twig

## [1.1.3] - 2020-09-18
- added aria-label to linkHeadline and more

## [1.1.2] - 2020-06-23
- fixed `SearchListener` (sprintf issue)

## [1.1.1] - 2020-06-18
- fixed canonical link generation

## [1.1.0] - 2020-06-18
- added `getSearchablePages` hook taking into account a jumpTo defined in a primary category in
  heimrichhannot/contao-categories-bundle and codefog/contao-news_categories (-> news_plus legacy)
- removed urlCache in `NewsItemTrait` -> doesn't make sense if different `jumpToDetails` are set
- added support for defining an alternative jumpTo in the primary category assigned to the news
- added php-cs-fixer template

## [1.0.4] - 2020-05-27
- fixed robots issue

## [1.0.3] - 2020-05-13
- fixed date issue

## [1.0.2] - 2019-11-25
- fixed missing url in og:url

## [1.0.1] - 2019-11-25
- fixed wrong url in image meta tags

## [1.0.0] - 2019-11-13
- fixed optional list bundle dependency

## [0.1.0] - 2019-11-04
- initial version
