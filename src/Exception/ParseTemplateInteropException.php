<?php

namespace HeimrichHannot\NewsListReaderBundle\Exception;

use Contao\Template;

class ParseTemplateInteropException extends \Exception
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(public readonly Template $template)
    {
    }
}