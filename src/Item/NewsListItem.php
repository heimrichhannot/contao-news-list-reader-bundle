<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\NewsListReaderBundle\Item;

use HeimrichHannot\ListBundle\Item\DefaultItem;

class NewsListItem extends DefaultItem
{
    use NewsItemTrait;
    const SESSION_SEEN_NEWS = 'SESSION_SEEN_NEWS';
}
