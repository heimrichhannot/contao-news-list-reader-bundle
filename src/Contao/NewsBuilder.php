<?php

namespace HeimrichHannot\NewsListReaderBundle\Contao;

use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\ModuleNews;
use Contao\NewsModel;
use Contao\Template;
use HeimrichHannot\NewsListReaderBundle\Exception\ParseTemplateInteropException;

class NewsBuilder extends ModuleNews
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
    }

    protected function compile()
    {
    }

    public function fetchPreparedNewsData(NewsModel $model): array
    {
        $hookCache = $GLOBALS['TL_HOOKS']['parseTemplate'];

        $GLOBALS['TL_HOOKS']['parseTemplate'][] = [self::class, 'templateInterop'];

        $template = null;
        try {
            $this->parseArticle($model);
        } catch (ParseTemplateInteropException $e) {
            $template = $e->template;
        }

        $GLOBALS['TL_HOOKS']['parseTemplate'] = $hookCache;

        if (!$template) {
            return [];
        }

        $contextFactory = new ContextFactory();
        return $contextFactory->fromContaoTemplate($template);
    }

    public function templateInterop(Template $template): void
    {
        throw new ParseTemplateInteropException($template);
    }
}