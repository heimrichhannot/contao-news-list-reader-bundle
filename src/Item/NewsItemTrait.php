<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\NewsListReaderBundle\Item;

use Contao\ArticleModel;
use Contao\CommentsModel;
use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\Date;
use Contao\MemberModel;
use Contao\ModuleLoader;
use Contao\NewsArchiveModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\UserModel;
use HeimrichHannot\ListBundle\Manager\ListManagerInterface;
use Psr\Log\LogLevel;

trait NewsItemTrait
{
    /**
     * Compile css class.
     */
    public function getCssClass(): string
    {
        $values = [$this->cssClass];

        // list reader item has custom cssClass like first, last, even,odd
        if (property_exists($this, '_cssClass')) {
            $values[] = $this->_cssClass;
        }

        if ($this->featured) {
            $values[] = 'featured';
        }

        return implode(' ', $values);
    }

    /**
     * Compile the headline link.
     */
    public function getLinkHeadline(): string
    {
        // Internal link
        if ('external' !== $this->source) {
            return sprintf('<a href="%s" title="%s" itemprop="url">%s%s</a>', $this->getDetailsUrl(), StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $this->headline), true), $this->headline, '');
        }

        // External link
        return sprintf('<a href="%s" title="%s"%s itemprop="url">%s</a>', $this->getExternalUrl(), \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['open'], $this->getExternalUrl())), ($this->target ? ' target="_blank"' : ''), $this->headline);
    }

    /**
     * Compile the more link.
     */
    public function getMore(): string
    {
        // Internal link
        if ('external' !== $this->source) {
            return sprintf('<a href="%s" title="%s" itemprop="url">%s%s</a>', $this->getDetailsUrl(), StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $this->headline), true), $GLOBALS['TL_LANG']['MSC']['more'], '<span class="invisible"> '.$this->headline.'</span>');
        }

        // External link
        return sprintf('<a href="%s" title="%s"%s itemprop="url">%s</a>', $this->getExternalUrl(), \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['open'], $this->getExternalUrl())), ($this->target ? ' target="_blank"' : ''), $GLOBALS['TL_LANG']['MSC']['more']);
    }

    /**
     * Get the news archive data.
     *
     * @return array
     */
    public function getArchive(): ?array
    {
        /**
         * @var NewsArchiveModel
         */
        $archiveModel = $this->getManager()->getFramework()->getAdapter(NewsArchiveModel::class);

        if (null === ($archive = $archiveModel->findByPk($this->pid))) {
            return null;
        }

        return $archive->row();
    }

    /**
     * Get details url and add archive.
     */
    public function getDetailsUrlWithArchive(): ?string
    {
        $url = $this->getDetailsUrl();

        // Add the current archive parameter (news archive)
        if (System::getContainer()->get('huh.request')->query->has('month')) {
            $url .= '?month='.System::getContainer()->get('huh.request')->query->get('month');
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDetailsUrl(bool $external = true, bool $isCanonical = false): string
    {
        switch ($this->source) {
            // Link to an external page
            case 'external':
                return $external ? $this->getExternalUrl() : '';
            // Link to an internal page
            case 'internal':
                return $this->getInternalUrl();
            // Link to an article
            case 'article':
                return $this->getArticleUrl();
        }

        return $this->getDefaultUrl($isCanonical) ?: '';
    }

    /**
     * Get the external news url source = 'external'.
     */
    public function getExternalUrl(): ?string
    {
        if ('mailto:' == substr($this->url, 0, 7)) {
            $url = StringUtil::encodeEmail($this->url);
        } else {
            $url = ampersand($this->url);
        }

        return $url ?: null;
    }

    /**
     * Get the internal news url source = 'internal'.
     */
    public function getInternalUrl(): ?string
    {
        $url = '';

        /**
         * @var PageModel
         */
        $pageModel = $this->getManager()->getFramework()->getAdapter(PageModel::class);

        if (null !== ($target = $pageModel->findByPk($this->jumpTo))) {
            $url = ampersand($target->getFrontendUrl());
        }

        return $url ?: null;
    }

    /**
     * Get the article news url source = 'article'.
     */
    public function getArticleUrl(): ?string
    {
        $url = '';

        /**
         * @var NewsArchiveModel
         * @var PageModel        $pageModel
         */
        $pageModel = $this->getManager()->getFramework()->getAdapter(PageModel::class);
        $articleModel = $this->getManager()->getFramework()->getAdapter(ArticleModel::class);

        if (null !== ($article = $articleModel->findByPk($this->articleId, ['eager' => true])) && null !== ($parentPage = $pageModel->findByPk($article->pid))) {
            $url = ampersand($parentPage->getFrontendUrl('/articles/'.($article->alias ?: $article->id)));
        }

        return $url ?: null;
    }

    /**
     * Get the default news url source = 'default'.
     */
    public function getDefaultUrl(bool $isCanonical = false): ?string
    {
        if (!$isCanonical && $this->getManager() instanceof ListManagerInterface && $this->getManager()->getListConfig()->addDetails) {
            $url = $this->_detailsUrl;
        } else {
            // news is relocated -> return relocation url
            if ('none' !== $this->relocate && '' !== ($relocateUrl = ampersand(Controller::replaceInsertTags($this->relocateUrl), true))) {
                $url = $relocateUrl;

                return $url;
            }

            // news archive
            /**
             * @var NewsArchiveModel
             * @var PageModel        $pageModel
             */
            $pageModel = $this->getManager()->getFramework()->getAdapter(PageModel::class);
            $archiveModel = $this->getManager()->getFramework()->getAdapter(NewsArchiveModel::class);

            if (null === ($archive = $archiveModel->findByPk($this->pid))) {
                return null;
            }

            $page = $pageModel->findByPk($archive->jumpTo);

            if (null === $page) {
                $url = ampersand(System::getContainer()->get('request_stack')->getCurrentRequest()->getRequestUri(), true);
            } else {
                $url = ampersand($page->getFrontendUrl((Config::get('useAutoItem') ? '/' : '/items/').($this->alias ?: $this->id)));
            }

            // primary category (if no page is selected in list config)

            // guess the primary category (TODO: make configurable)
            $primary = $this->getRawValue('huhCategories_primary') ?: $this->getRawValue('categories_primary');

            if ($primary) {
                $value = \System::getContainer()->get(\HeimrichHannot\CategoriesBundle\Manager\CategoryManager::class)->getOverridablePropertyWithoutContext(
                    'jumpTo', $primary
                );

                if (null !== ($page = $pageModel->findByPk($value))) {
                    $url = ampersand($page->getFrontendUrl((Config::get('useAutoItem') ? '/' : '/items/').($this->alias ?: $this->id)));
                }
            }
        }

        return $url ?: null;
    }

    /**
     * Get news date DateTime.
     */
    public function getDatetime(): string
    {
        return Date::parse('Y-m-d\TH:i:sP', $this->date);
    }

    /**
     * Get news date timestamp.
     */
    public function getTimestamp(): string
    {
        return $this->date;
    }

    /**
     * Get the author.
     */
    public function getAuthor(): ?string
    {
        /** @var UserModel $adapter */
        $adapter = $this->getManager()->getFramework()->getAdapter(UserModel::class);

        if (null !== ($user = $adapter->findByPk($this->author))) {
            return $GLOBALS['TL_LANG']['MSC']['by'].' '.$user->name;
        }

        return null;
    }

    /**
     * Compile comment count.
     */
    public function getCommentCount(): ?string
    {
        $total = $this->getNumberOfComments();

        return $total > 0 ? sprintf($GLOBALS['TL_LANG']['MSC']['commentCount'], $total) : '';
    }

    /**
     * Get number of comments.
     */
    public function getNumberOfComments(): ?int
    {
        if ($this->noComments || !\in_array('comments', ModuleLoader::getActive(), true) || 'default' != $this->source) {
            return null;
        }

        $total = CommentsModel::countPublishedBySourceAndParent($this->getDataContainer(), $this->id);

        return $total;
    }

    /**
     * Get formatted meta date.
     */
    public function getDate(): string
    {
        global $objPage;

        return Date::parse($objPage->dateFormat, $this->getRawValue('date'));
    }

    /**
     * Get all enclosures.
     */
    public function getEnclosures(): ?array
    {
        if (true === $this->addEnclosure) {
            return null;
        }

        $template = new \stdClass();
        Controller::addEnclosuresToTemplate($template, $this->getRaw());

        return $template->enclosure;
    }

    /**
     * Compile the news text.
     */
    public function getText(): string
    {
        $strText = '';

        /**
         * @var ContentModel
         */
        $adapter = $this->getManager()->getFramework()->getAdapter(ContentModel::class);

        if (null !== ($elements = $adapter->findPublishedByPidAndTable($this->id, $this->getDataContainer()))) {
            foreach ($elements as $element) {
                try {
                    $strText .= Controller::getContentElement($element->id);
                } catch (\ErrorException $e) {
                }
            }
        }

        return $strText;
    }

    /**
     * Check if the news has text.
     */
    public function hasText(): bool
    {
        // Display the "read more" button for external/article links
        if ('default' !== $this->source) {
            return true;
        }

        /** @var ContentModel $adapter */
        $adapter = $this->getManager()->getFramework()->getAdapter(ContentModel::class);

        return $adapter->countPublishedByPidAndTable($this->id, $this->getDataContainer()) > 0;
    }

    /**
     * Check if the news has teaser text.
     */
    public function hasTeaser(): bool
    {
        return !empty($this->teaser);
    }

    /**
     * Compile the teaser text.
     */
    public function getTeaser(): string
    {
        return StringUtil::encodeEmail(StringUtil::toHtml5($this->teaser));
    }

    /**
     * Compile the newsHeadline.
     */
    public function getNewsHeadline(): string
    {
        return Controller::replaceInsertTags($this->headline);
    }

    /**
     * Compile the news SubHeadline.
     */
    public function getNewsSubHeadline(): string
    {
        return $this->subheadline;
    }

    /**
     * Check if the news has a subHeadline.
     */
    public function hasSubHeadline(): bool
    {
        return '' !== $this->subheadline;
    }

    /**
     * get article writers from member table.
     */
    public function getWriters()
    {
        $ids = StringUtil::deserialize($this->writers, true);

        if (empty($ids)) {
            return;
        }

        if (null === ($members = System::getContainer()->get('contao.framework')->getAdapter(MemberModel::class)->findMultipleByIds($ids))) {
            return;
        }

        if ($members->count() < 1) {
            return;
        }

        $writers = [];

        foreach ($members as $member) {
            $writers[] = $member;
        }

        try {
            $writerNames = $this->getWritersNames(',', null, $writers);
        } catch (\Exception $exception) {
            System::getContainer()->get('monolog.logger.contao')->log(LogLevel::ERROR, $exception->getMessage());
        }

        return ['writers' => $writers, 'writersNames' => $writerNames];
    }

    /**
     * Get list of already seen news for current or given page.
     *
     * @param null $pageId Set pageId or null for current page
     *
     * @return array|null List of news for current or given page id
     */
    public function getSeen($pageId = null)
    {
        if (null === $pageId) {
            global $objPage;
            $pageId = $objPage->id;
        }

        $pages = System::getContainer()->get('session')->get(static::SESSION_SEEN_NEWS);

        if (!\is_array($pages) || !isset($pages[$pageId])) {
            return null;
        }

        return \is_array($pages[$pageId]) ? $pages[$pageId] : null;
    }

    /**
     * Provide a helper function that returns the writer names separated with given delimiter.
     *
     * @param string      $delimiter The delimiter
     * @param string|null $format    The writer name format string (default: ##firstname## ##lastname##)
     *
     * @throws \Exception
     *
     * @return string The writers separated by the delimiter string
     */
    protected function getWritersNames($delimiter, $format, $writers)
    {
        if (null === $format) {
            $format = '##firstname## ##lastname##';
        }

        $names = [];

        foreach ($writers as $writer) {
            $names[] = trim(StringUtil::parseSimpleTokens($format, $writer->row()));
        }

        return implode($delimiter, $names);
    }
}
