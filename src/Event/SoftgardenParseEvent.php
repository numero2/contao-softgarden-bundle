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

use Contao\NewsModel;
use Symfony\Contracts\EventDispatcher\Event;
use numero2\SoftgardenBundle\API\SoftgardenAPI;


class SoftgardenParseEvent {


    /**
     * @var object
     */
    private $position;

    /**
     * @var Contao\NewsModel;
     */
    private $news;

    /**
     * @var SoftgardenAPI;
     */
    private $api;


    public function __construct( object $position, NewsModel $news, SoftgardenAPI $api ) {

        $this->position = $position;
        $this->news = $news;
        $this->api = $api;
    }


    public function getPosition(): object {
        return $this->position;
    }


    public function getNews(): NewsModel {
        return $this->news;
    }


    public function getAPI(): SoftgardenAPI {
        return $this->api;
    }
}
