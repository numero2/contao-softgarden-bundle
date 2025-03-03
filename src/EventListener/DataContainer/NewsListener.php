<?php

/**
 * Softgarden Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\SoftgardenBundle\EventListener\DataContainer;

use Contao\ArrayUtil;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\NewsArchiveModel;


class NewsListener {


    /**
     * Adds an operation to manually start the import
     *
     * @param Contao\DataContainer $dc
     */
    #[AsCallback('tl_news', target:'config.onload')]
    public function addImportOperation( DataContainer $dc ): void {

        if( !$dc->id ) {
            return;
        }

        $archive = null;
        $archive = NewsArchiveModel::findOneById($dc->id);

        if( $archive && $archive->softgarden_enable ) {

            ArrayUtil::arrayInsert($GLOBALS['TL_DCA']['tl_news']['list']['global_operations'], 1, [
                'softgarden_import' => [
                    'label'     => &$GLOBALS['TL_LANG']['tl_news']['softgarden_import']
                ,   'href'      => 'key=softgarden_import'
                ,   'icon'      => 'bundles/softgarden/backend/img/import.svg'
                ]
            ]);
        }
    }
}
