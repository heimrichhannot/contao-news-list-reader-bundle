<?php

/*
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\NewsListReaderBundle\Item;

use Contao\System;
use HeimrichHannot\ReaderBundle\Item\DefaultItem;

class NewsReaderItem extends DefaultItem
{
    use NewsItemTrait;
    const SESSION_SEEN_NEWS = 'SESSION_SEEN_NEWS';

    public function parse(): string
    {
        $this->addSeen();

        return parent::parse();
    }

    /**
     * Add news to list of already seen for current page.
     */
    protected function addSeen()
    {
        global $objPage;

        $pages = System::getContainer()->get('session')->get(static::SESSION_SEEN_NEWS);

        if (!\is_array($pages)) {
            $pages = [];
        }

        $pages[$objPage->id][$this->id] = $this->id;

        System::getContainer()->get('session')->set(static::SESSION_SEEN_NEWS, $pages);
    }
}
