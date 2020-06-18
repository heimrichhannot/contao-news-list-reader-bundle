# Changelog
All notable changes to this project will be documented in this file.

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
