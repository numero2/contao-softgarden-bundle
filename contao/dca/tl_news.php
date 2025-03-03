<?php

/**
 * Softgarden Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


$GLOBALS['TL_DCA']['tl_news']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_news']['fields']
,   [
        'softgarden_id' => [
            'eval' => ['doNotCopy'=>true]
        ,   'sql' => "int(10) unsigned NOT NULL default 0"
        ]
    ]
);