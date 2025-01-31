# Contao News List Reader Bundle

[![](https://img.shields.io/packagist/v/heimrichhannot/contao-news-list-reader-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-news-list-reader-bundle)
[![](https://img.shields.io/packagist/dt/heimrichhannot/contao-news-list-reader-bundle.svg)](https://packagist.org/packages/heimrichhannot/contao-news-list-reader-bundle)

This bundle enhance the news support of [list](https://github.com/heimrichhannot/contao-list-bundle) and [reader bundle](https://github.com/heimrichhannot/contao-reader-bundle). It applies news adjustments (from contao news bundle) to the list and reader items.


## Features
- list item class for news
- reader item class for news
- can be used with one or both of these bundles

## Usage

### Install

Install with composer or Contao Manager

```
composer require heimrichhannot/contao-news-list-reader-bundle
```
   
### Setup

1. Create or Edit a list or reader config and select NewsListItem/NewsReaderItem as item class
2. Select 'Use news extension' in the list or reader config
1. You can use one of the provided news_* templates

## Developers

### Custom Item classes

You can usee the `NewsItemTrait` to add news specific list/reader field to your custom item class.