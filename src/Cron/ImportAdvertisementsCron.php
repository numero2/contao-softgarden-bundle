<?php

/**
 * Softgarden Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\SoftgardenBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use numero2\SoftgardenBundle\Import\SoftgardenImport;


#[AsCronJob('daily')]
class ImportAdvertisementsCron {


    /**
     * @var SoftgardenImport
     */
    private $importer;


    public function __construct( SoftgardenImport $importer ) {

        $this->importer = $importer;
    }


    public function __invoke(): void {

        $this->importer->importAllArchives();
    }
}