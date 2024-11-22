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
        if (!SUtils::class()::hasTrait($event->getItem(), NewsItemTrait::class)) {
            return;
        }

        $newsModel = NewsModel::findByPk($event->getTemplateData()['id']);
        if (!$newsModel) {
            return;
        }

        $templateData = $event->getTemplateData();

        $templateData['hasText'] = $this->hasText($newsModel);
        $templateData['text'] = $this->text($newsModel);

        $event->setTemplateData($templateData);

    }

    private function hasText(NewsModel $model): object|bool
    {
        if ('default' !== $model->source) {
            return true;
        }

        return $this->getCallableWrapper(Template::once(static function () use ($model)
        {
            return ContentModel::countPublishedByPidAndTable($model->id, 'tl_news') > 0;
        }));
    }

    private function text(NewsModel $model): object
    {
        return $this->getCallableWrapper(Template::once(function () use ($model) {
            $strText = '';
            $objElement = ContentModel::findPublishedByPidAndTable($model->id, 'tl_news');

            // avoid duplicate content on multilingual occasions, see 1.3.1
            $ids = array_unique($objElement->fetchEach('id'));

            foreach ($ids as $id) {
                $strText .= Controller::getContentElement($id);
            }

            return $strText;
        }));
    }

    /**
     * Copy from Contao\CoreBundle\Twig\Interop\ContextFactory @ Contao 4.13.50
     */
    private function getCallableWrapper(callable $callable): object
    {
        return new class($callable) {
            /**
             * @var callable
             */
            private $callable;

            public function __construct(callable $callable)
            {
                $this->callable = $callable;
            }

            /**
             * Delegates call to callable, e.g. when in a Contao template context.
             *
             * @param mixed $args
             *
             * @return mixed
             */
            public function __invoke(...$args)
            {
                return ($this->callable)(...$args);
            }

            /**
             * Called when evaluating "{{ var }}" in a Twig template.
             */
            public function __toString(): string
            {
                return (string) $this();
            }

            /**
             * Called when evaluating "{{ var.invoke() }}" in a Twig template.
             * We do not cast to string here, so that other types (like arrays)
             * are supported as well.
             *
             * @param mixed $args
             *
             * @return mixed
             */
            public function invoke(...$args)
            {
                return $this(...$args);
            }
        };
    }
}