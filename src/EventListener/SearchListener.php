<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\NewsListReaderBundle\EventListener;

use Contao\Database;
use Contao\Model;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\System;

class SearchListener
{
    public function getSearchablePages($arrPages, $intRoot = 0, $blnIsSitemap = false)
    {
        $arrRoot = [];

        if ($intRoot > 0) {
            $arrRoot = Database::getInstance()->getChildRecords($intRoot, 'tl_page');
        }

        $arrProcessed = [];
        $time = \Date::floorToMinute();

        // Get all news archives
        $objArchive = \NewsArchiveModel::findByProtected('');

        // Walk through each archive
        if (null !== $objArchive) {
            while ($objArchive->next()) {
                // Skip news archives without target page
                if (!$objArchive->jumpTo) {
                    continue;
                }

                // Skip news archives outside the root nodes
                if (!empty($arrRoot) && !\in_array($objArchive->jumpTo, $arrRoot)) {
                    continue;
                }

                // Get the URL of the jumpTo page
                if (!isset($arrProcessed[$objArchive->jumpTo])) {
                    $objParent = PageModel::findWithDetails($objArchive->jumpTo);

                    // The target page does not exist
                    if (null === $objParent) {
                        continue;
                    }

                    // The target page has not been published (see #5520)
                    if (!$objParent->published || ('' != $objParent->start && $objParent->start > $time) || ('' != $objParent->stop && $objParent->stop <= ($time + 60))) {
                        continue;
                    }

                    if ($blnIsSitemap) {
                        // The target page is protected (see #8416)
                        if ($objParent->protected) {
                            continue;
                        }

                        // The target page is exempt from the sitemap (see #6418)
                        if ('noindex,nofollow' == $objParent->robots) {
                            continue;
                        }
                    }

                    // Generate the URL
                    $arrProcessed[$objArchive->jumpTo] = $objParent->getAbsoluteUrl().(\Config::get('useAutoItem') ? '/%s' : '/items/%s');
                }

                $strUrl = $arrProcessed[$objArchive->jumpTo];

                // Get the items
                $objArticle = NewsModel::findPublishedDefaultByPid($objArchive->id);

                if (null !== $objArticle) {
                    while ($objArticle->next()) {
                        $arrPages[] = $this->getLink($objArticle, $strUrl);
                    }
                }
            }
        }

        return $arrPages;
    }

    /**
     * Adds support for overriding news archive's jump to in the primary news category assigned to the concrete news.
     *
     * @param Model  $objItem
     * @param string $strUrl
     * @param string $strBase
     *
     * @return string
     */
    protected function getLink($objItem, $strUrl, $strBase = '')
    {
        switch ($objItem->source) {
            // Link to an external page
            case 'external':
                return $objItem->url;

                break;

            // Link to an internal page
            case 'internal':
                if (null !== ($objTarget = $objItem->getRelated('jumpTo'))) {
                    /* @var \PageModel $objTarget */
                    return $objTarget->getAbsoluteUrl();
                }

                break;

            // Link to an article
            case 'article':
                if (null !== ($objArticle = \ArticleModel::findByPk($objItem->articleId, ['eager' => true]))
                    && null !== ($objPid = $objArticle->getRelated('pid'))
                ) {
                    /* @var \PageModel $objPid */
                    return ampersand(
                        $objPid->getAbsoluteUrl(
                            '/articles/'.((!\Config::get('disableAlias') && '' != $objArticle->alias) ? $objArticle->alias : $objArticle->id)
                        )
                    );
                }

                break;

            default:
                $intJumpTo = 0;

                // news category jump to override?

                // priority 2: codefog/contao-news_categories (news_plus legacy)
                if (class_exists('\NewsCategories\NewsCategories')) {
                    if ($objItem->primaryCategory && null !== ($objCategory = \NewsCategories\NewsCategoryModel::findPublishedByIdOrAlias($objItem->primaryCategory))) {
                        $intJumpTo = $objCategory->jumpToDetails ?: $intJumpTo;
                    }
                }

                // priority 1: heimrichhannot/contao-categories-bundle
                if (class_exists('\HeimrichHannot\CategoriesBundle\CategoriesBundle')) {
                    // guess the primary category (TODO: make configurable)
                    $primary = $objItem->huhCategories_primary ?: $objItem->categories_primary;

                    if ($primary) {
                        $intJumpTo = System::getContainer()->get(\HeimrichHannot\CategoriesBundle\Manager\CategoryManager::class)
                            ->getOverridablePropertyWithoutContext(
                                'jumpTo',
                                $primary
                            ) ?: $intJumpTo;
                    }
                }

                if (null !== ($objPage = \PageModel::findByPk($intJumpTo))) {
                    return $objPage->getAbsoluteUrl(((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/' : '/items/').((!\Config::get('disableAlias') && '' != $objItem->alias) ? $objItem->alias : $objItem->id));
                }

                break;
        }

        // Backwards compatibility (see #8329)
        if ('' != $strBase && !preg_match('#^https?://#', $strUrl)) {
            $strUrl = $strBase.$strUrl;
        }

        // Link to the default page
        return sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), (('' != $objItem->alias && !\Config::get('disableAlias')) ? $objItem->alias : $objItem->id));
    }
}
