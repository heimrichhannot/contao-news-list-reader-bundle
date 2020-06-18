<?php

/**
 * Hooks
 */
foreach ($GLOBALS['TL_HOOKS']['getSearchablePages'] as &$callback) {
    if (($callback[0] == 'News' || $callback[0] == 'HeimrichHannot\NewsPlus\NewsPlus') && $callback[1] == 'getSearchablePages') {
        $callback = [\HeimrichHannot\NewsListReaderBundle\EventListener\SearchListener::class, 'getSearchablePages'];
    }
}
