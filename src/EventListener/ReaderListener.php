<?php

/*
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\NewsListReaderBundle\EventListener;

use Contao\Date;
use Contao\StringUtil;
use Contao\Validator;
use HeimrichHannot\HeadBundle\HeadTag\MetaTag;
use HeimrichHannot\HeadBundle\Manager\HtmlHeadTagManager;
use HeimrichHannot\NewsListReaderBundle\Item\NewsItemTrait;
use HeimrichHannot\NewsListReaderBundle\Item\NewsReaderItem;
use HeimrichHannot\ReaderBundle\Event\ReaderBeforeRenderEvent;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ReaderListener implements EventSubscriberInterface
{
    private HtmlHeadTagManager $headTagManager;
    private Utils              $utils;
    private RequestStack       $requestStack;

    public function __construct(HtmlHeadTagManager $headTagManager, Utils $utils, RequestStack $requestStack)
    {
        $this->headTagManager = $headTagManager;
        $this->utils = $utils;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [ReaderBeforeRenderEvent::NAME => 'onReaderBeforeRenderEvent'];
    }

    public function onReaderBeforeRenderEvent(ReaderBeforeRenderEvent $event): void
    {
        /** @var NewsReaderItem|NewsItemTrait $item */
        $item = $event->getItem();

        if (!($item instanceof NewsReaderItem) || !\in_array(NewsItemTrait::class, class_uses($item))) {
            return;
        }

        $objPage = $this->utils->request()->getCurrentPageModel();
        $article = $item->getRaw();

        // Title
        $title = ($article['pageTitle'] ?? false) ?: ($article['headline'] ? $this->headTagManager->inputEncodedToPlainText($article['headline']) : '');

        if (!empty(trim($title))) {
            $this->headTagManager->setTitleTag($title);
            $this->headTagManager->addMetaTag(new MetaTag('og:title', $title));
        } else {
            $title = '';
        }

        // Description
        $description = $article['metaDescription'] ?? ($article['description'] ?? ($article['teaser'] ?? ''));

        if (!empty(trim($description))) {
            $description = str_replace("\n", ' ', $this->headTagManager->inputEncodedToPlainText($description));
            $this->headTagManager->addMetaTag(new MetaTag('og:description', $description));
            $this->headTagManager->addMetaTag(new MetaTag('description', $description));
        } else {
            $description = '';
        }

        // Other meta tags
        $this->headTagManager->addMetaTag(new MetaTag('robots', $article['robots'] ?: ($objPage->robots ?: 'index,follow')));
        $this->headTagManager->addMetaTag(new MetaTag('date', Date::parse('c', $article['date'])));
        $this->headTagManager->addMetaTag(new MetaTag('og:type', 'article'));
        $this->headTagManager->addMetaTag(new MetaTag('og:url', $item->getArticleUrl() ?? ($this->requestStack->getCurrentRequest()->getUri())));

        $imagePath = null;

        if ($article['addImage'] ?? false) {
            $image = $item->getFormattedValue('singleSRC');

            if (Validator::isBinaryUuid($image)) {
                $imagePath = $this->utils->request()->getBaseUrl().'/'.$this->utils->file()->getPathFromUuid($image);
                $this->headTagManager->addMetaTag(new MetaTag('og:image', $imagePath));
            }
        }

        if (!empty($keywords = StringUtil::deserialize($article['metaKeywords'] ?? '', true))) {
            // keywords should be delimited by comma with space(see https://github.com/contao/core-bundle/issues/1078)
            $this->headTagManager->addMetaTag(new MetaTag('keywords', implode(', ', $keywords)));
        }

        // twitter card
        if ($article['twitterCard'] ?? false) {
            $this->headTagManager->addMetaTag(new MetaTag('twitter:card', $article['twitterCard']));

            if ($article['twitterCreator'] ?? false) {
                $this->headTagManager->addMetaTag(new MetaTag('twitter:creator', $article['twitterCreator']));
            }

            if ($title) {
                $this->headTagManager->addMetaTag(new MetaTag('twitter:title', $title));
            }

            if ($description) {
                $this->headTagManager->addMetaTag(new MetaTag('twitter:description', $description));
            }

            if ($imagePath) {
                $this->headTagManager->addMetaTag(new MetaTag('twitter:image', $imagePath));

                if ($article['alt'] ?? false) {
                    $this->headTagManager->addMetaTag(new MetaTag('twitter:image:alt', $article['alt']));
                }
            }

            if ($article['addYoutube'] ?? false) {
                $this->headTagManager->addMetaTag(new MetaTag('twitter:player', 'https://www.youtube.com/embed/'.$article['addYoutube']));
                $this->headTagManager->addMetaTag(new MetaTag('twitter:player:width', '480'));
                $this->headTagManager->addMetaTag(new MetaTag('twitter:player:height', '300'));
            }
        }
    }
}
