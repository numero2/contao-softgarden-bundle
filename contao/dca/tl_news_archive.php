<?php

/**
 * Softgarden Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\BackendUser;
use Contao\DataContainer;


$GLOBALS['TL_DCA']['tl_news_archive']['palettes']['__selector__'][] = 'softgarden_enable';
$GLOBALS['TL_DCA']['tl_news_archive']['subpalettes']['softgarden_enable'] = 'softgarden_client_id,softgarden_channel,softgarden_language,softgarden_default_author';


$GLOBALS['TL_DCA']['tl_news_archive']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_news_archive']['fields']
,   [
        'softgarden_enable' => [
            'exclude'      => true
        ,   'filter'       => true
        ,   'inputType'    => 'checkbox'
        ,   'eval'         => ['submitOnChange'=>true]
        ,   'sql'          => "char(1) NOT NULL default ''"
        ]
    ,   'softgarden_client_id' => [
            'exclude'      => true
        ,   'inputType'    => 'text'
        ,   'eval'         => ['mandatory'=>true, 'tl_class'=>'w50', 'decodeEntities'=>true, 'hideInput'=>true, 'submitOnChange'=>true]
        ,   'sql'          => "varchar(255) NOT NULL default ''"
        ]
    ,   'softgarden_channel' => [
            'exclude'      => true
        ,   'inputType'    => 'select'
        ,   'eval'         => ['mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50']
        ,   'sql'          => "varchar(13) NOT NULL default ''"
        ]
    ,   'softgarden_language' => [
            'exclude'      => true
        ,   'inputType'    => 'select'
        ,   'eval'         => ['mandatory'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50']
        ,   'sql'          => "varchar(5) NOT NULL default ''"
        ]
    ,   'softgarden_default_author' => [
            'exclude'      => true
        ,   'default'      => BackendUser::getInstance()->id
        ,   'flag'         => DataContainer::SORT_ASC
        ,   'inputType'    => 'select'
        ,   'foreignKey'   => 'tl_user.name'
        ,   'eval'         => ['mandatory'=>true, 'doNotCopy'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50']
        ,   'sql'          => "int(10) unsigned NOT NULL default 0"
        ]
    ]
);