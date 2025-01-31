<?php

namespace HeimrichHannot\NewsListReaderBundle\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\System;

class DcaDefinitions
{
    public static function addDefinitions(string $table)
    {
        $dca = &$GLOBALS['TL_DCA'][$table];

        PaletteManipulator::create()
            ->addField('useNewsExtension', 'extensions_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('default', $table);

        $dca['subpalettes']['useNewsExtension'] = 'news_imgSize';
        $dca['palettes']['__selector__'][] = 'useNewsExtension';

        $dca['fields']['useNewsExtension'] = [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'submitOnChange' => true,
            ],
            'sql' => "char(1) NOT NULL default ''",
        ];

        $dca['fields']['news_imgSize'] = [
            'exclude' => true,
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'options_callback' => static function () {
                return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
            },
            'sql' => "varchar(64) NOT NULL default ''"
        ];
    }
}