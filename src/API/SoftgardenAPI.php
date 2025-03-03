<?php

/**
 * Softgarden Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\SoftgardenBundle\API;

use \Exception;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


enum SoftgardenCatalogTypes: string {
    case jobCategory = 'JOB_CATEGORY';
    case audience = 'AUDIENCE';
    case employmentType = 'EMPLOYMENT_TYPE';
    case workingHours = 'WORKING_HOURS';
    case positionIndustry = 'POSITION_INDUSTRY';
    case workExperience = 'WORK_EXPERIENCE';
};


class SoftgardenAPI {


    /**
     * @var string
     */
    const ENDPOINT = "api.softgarden.io";


    /**
     * @var Symfony\Contracts\HttpClient\HttpClientInterface
     */
    private $client;

    /**
     * @var Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $clientID;


    /**
     * @var array
     */
    private $responseCache = [];


    public function __construct( HttpClientInterface $client, LoggerInterface $logger ) {

        $this->client = $client;
        $this->logger = $logger;
    }


    /**
     * Settter for clientID
     *
     * @param string $clientID
     */
    public function setClientID( string $clientID ): void {

        $this->clientID = $clientID;
    }


    /**
     * Returns a list of available channels
     *
     * @return array
     */
    public function getChannels(): array {

        $response = null;
        $response = $this->send('/api/rest/v3/frontend/jobslist/channels');

        $channels = [];

        if( !empty($response) ) {

            foreach( $response as $channel ) {

                if( $channel->accessible ) {
                    $channels[ $channel->id ] = $channel->name;
                }
            }
        }

        return $channels;
    }


    /**
     * Returns a list of all available jobs for the given channel
     *
     * @param string $channel The channel identifier / name
     *
     * @return array|null
     */
    public function getJobs( string $channel ): ?array {

        $response = null;
        $response = $this->send('/api/rest/v3/frontend/jobslist/' . $channel);

        if( $response && $response->results ) {
            return $response->results;
        }

        return null;
    }


    /**
     * Resolves the value of the given catalog type
     *
     * @param SoftgardenCatalogTypes $type
     * @param string $value
     * @param string $locale Locale in ISO 639-1 format
     *
     * @return string|null
     */
    public function resolveCatalogValue( SoftgardenCatalogTypes $type, string $value, string $locale='en' ): ?string {

        $response = null;
        $response = $this->send('/api/rest/v3/frontend/catalogs/' . $type->value . '?locale=' . $locale, 'GET', true);

        if( $response && $response->{$value} ) {
            return $response->{$value};
        }

        return null;
    }


    /**
     * Sends a request to the softgarden API
     *
     * @param string $path
     * @param string $method
     * @param bool $cache If the response should be cached
     *
     * @return object|null
     */
    private function send( string $path='', string $method='GET', bool $cache=false ): ?object {

        // return result from cache if available
        if( $cache && isset($this->responseCache[$path]) ) {
            return $this->responseCache[$path];
        }

        $options = null;
        $options = new HttpOptions();

        $options->setAuthBasic($this->clientID, '');

        $options->setHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'numero2/contao-softgarden-bundle',
            'Content-Type' => 'application/json',
        ]);

        $uri = 'https://' . self::ENDPOINT . $path;

        $response = null;
        $response = $this->client->request($method, $uri, $options->toArray());

        try {

            if( $response->getStatusCode(false) !== 200 ) {
                //$this->logger->log(LogLevel::ERROR, 'Could not request job advertisements from softgarden ('.$uri.'), received status '.$response->getStatusCode(), ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);
                return null;
            }

        } catch( Exception $e ) {

            //$this->logger->log(LogLevel::ERROR, 'Could not request job advertisements from softgarden ('.$uri.'). '.$e->getMessage(), ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);
            return null;
        }

        $json = null;
        $json = json_decode($response->getContent(false));

        // write response to cache
        if( $cache && $json ) {
            $this->responseCache[$path] = $json;
        }

        return $json??null;
    }
}
