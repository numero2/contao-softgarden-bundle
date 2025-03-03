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

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\NewsArchiveModel;
use numero2\SoftgardenBundle\API\SoftgardenAPI;
use Symfony\Contracts\Translation\TranslatorInterface;
use Contao\CoreBundle\Intl\Locales;


class NewsArchiveListener {


    /**
     * @var Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var Contao\CoreBundle\Intl\Locales
     */
    private $locales;

    /**
     * @var SoftgardenAPI
     */
    private $api;


    public function __construct( TranslatorInterface $translator, Locales $locales, SoftgardenAPI $api ) {

        $this->translator = $translator;
        $this->locales = $locales;
        $this->api = $api;
    }


    /**
     * Adds the softgarden configuration fields to the default palette
     *
     * @param Contao\DataContainer $dc
     *
     */
    #[AsCallback('tl_news_archive', target:'config.onload')]
    public function modifyPalettes( DataContainer $dc ): void {

        PaletteManipulator::create()
            ->addLegend('softgarden_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE)
            ->addField(['softgarden_enable'], 'softgarden_legend', PaletteManipulator::POSITION_APPEND )
            ->applyToPalette('default', $dc->table);
    }


    /**
     * Generates an array of channel options
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    #[AsCallback('tl_news_archive', target:'fields.softgarden_channel.options')]
    public function getChannelOptions( DataContainer $dc ): array {

        if( !$dc->activeRecord->softgarden_enable || !$dc->activeRecord->softgarden_client_id ) {
            return [];
        }

        $this->api->setClientID($dc->activeRecord->softgarden_client_id);

        $channels = [];
        $channels = $this->api->getChannels();

        return $channels;
    }


    /**
     * Generates an array of language options
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    #[AsCallback('tl_news_archive', target:'fields.softgarden_language.options')]
    public function getLanguageOptions( DataContainer $dc ): array {

        return $this->locales->getEnabledLocales(null, false);
    }
}
