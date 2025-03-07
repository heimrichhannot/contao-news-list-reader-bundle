<?php

namespace HeimrichHannot\NewsListReaderBundle\EventListener;

use Contao\ContentModel;
use Contao\Controller;
use Contao\News;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use HeimrichHannot\ListBundle\Event\ListBeforeRenderItemEvent;
use HeimrichHannot\NewsListReaderBundle\Contao\NewsBuilder;
use HeimrichHannot\NewsListReaderBundle\Item\NewsItemTrait;
use HeimrichHannot\ReaderBundle\Event\ReaderBeforeRenderEvent;
use HeimrichHannot\UtilsBundle\StaticUtil\SUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(ListBeforeRenderItemEvent::NAME)]
#[AsEventListener(ReaderBeforeRenderEvent::NAME)]
class NewsItemListener
{
    public function __invoke(ListBeforeRenderItemEvent|ReaderBeforeRenderEvent $event): void
    {
        $config = $event instanceof ListBeforeRenderItemEvent ? $event->getListConfiguration()->getListConfigModel() : $event->getReaderConfig();

        if (!SUtils::class()::hasTrait($event->getItem(), NewsItemTrait::class) && !$config->useNewsExtension) {
            return;
        }

        $newsModel = NewsModel::findByPk($event->getTemplateData()['id']);
        if (!$newsModel) {
            return;
        }
        $newsModel->preventSaving(true);
        $newsModel->size = $config->news_imgSize ?? $newsModel->size;

        $newsData = (new NewsBuilder())->fetchPreparedNewsData($newsModel);

        $templateData = $event->getTemplateData();
        $templateData['hasText'] = $newsData['hasText'] ?? false;
        $templateData['text'] = $newsData['text'] ?? null;
        $templateData['figure'] = $newsData['figure'] ?? null;
        $event->setTemplateData($templateData);

    }
}