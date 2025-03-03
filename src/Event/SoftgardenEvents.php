<?php

/**
 * Softgarden Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\SoftgardenBundle\Event;


final class SoftgardenEvents {

    /**
     * The contao.tags_get_list is triggered whenever we need a list of tags.
     *
     * @see numero2\SoftgardenBundle\Event\SoftgardenParseEvent
     */
    public const IMPORT_ADVERTISEMENT = 'contao.softgarden_import_advertisement';
}
