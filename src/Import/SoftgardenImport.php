<?php

/**
 * Softgarden Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\SoftgardenBundle\Import;

use \Exception;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Input;
use Contao\Message;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use numero2\SoftgardenBundle\API\SoftgardenAPI;
use numero2\SoftgardenBundle\Event\SoftgardenEvents;
use numero2\SoftgardenBundle\Event\SoftgardenParseEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;


class SoftgardenImport {


    /**
     * @var int
     */
    public const STATUS_ERROR = 0;
    public const STATUS_NEW = 1;
    public const STATUS_UPDATE = 2;


    /**
     * @var \Contao\CoreBundle\Framework\ContaoFramework
     */
    private $framework;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var \Contao\CoreBundle\Routing\ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var SoftgardenAPI
     */
    private $api;


    public function __construct( ContaoFramework $framework, Connection $connection, RequestStack $requestStack, ScopeMatcher $scopeMatcher, LoggerInterface $logger, TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher, SoftgardenAPI $api ) {

        $this->framework = $framework;
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
        $this->api = $api;

        $this->framework->initialize();
    }


    /**
     * Imports all available advertisements for the current archive
     *
     * @throws \Exception
     */
    public function importCurrentArchive(): void {

        $id = Input::get('id');

        // check that we're in the backend
        if( !($this->requestStack->getCurrentRequest() && $this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest()) && $id) ) {
            return;
        }

        $archive = null;
        $archive = NewsArchiveModel::findOneById($id);

        if( $archive && $archive->softgarden_enable ) {

            $this->importAdvertisementsForArchive($archive, false);
            Controller::redirect(Controller::getReferer());

        } else {

            throw new Exception('News archive ID ' . $id . ' is not configured for use with softgarden');
        }
    }


    /**
     * Imports all available advertisements for all configured archives
     */
    public function importAllArchives(): void {

        $archive = null;
        $archive = NewsArchiveModel::findBy(["softgarden_enable!=''"],null);

        if( $archive ) {

            while( $archive->next() ) {

                if( $archive->softgarden_enable ) {
                    $this->importAdvertisementsForArchive($archive->current());
                }
            }
        }
    }


    /**
     * Imports all available advertisements for the given archive
     *
     * @param \Contao\NewsArchiveModel $archive
     * @param bool $silent Indicated wether to show messages in the backend or not
     */
    private function importAdvertisementsForArchive( NewsArchiveModel $archive, bool $silent=true ): void {

        $this->api->setClientID($archive->softgarden_client_id);

        $ads = [];
        $ads = $this->api->getJobs($archive->softgarden_channel);

        $results  = [
            self::STATUS_ERROR => 0
        ,   self::STATUS_NEW => 0
        ,   self::STATUS_UPDATE => 0
        ];

        if( $ads ) {

            // initially hide all job listings in current archive to make sure
            // deleted listings are not shown anymore
            $this->connection->prepare("UPDATE ".NewsModel::getTable()." SET published = '0' WHERE softgarden_id != '0' AND published = '1' AND pid = :pid")
                ->execute(['pid'=> $archive->id]);

            foreach( $ads as $ad ) {

                $status = $this->importAdvertisement($ad, $archive);
                $results[(int)$status]++;
            }
        }

        // add message for backend
        if( !$silent ) {

            if( empty($ads) ) {

                Message::addError(
                    $this->translator->trans('ERR.general', [], 'contao_default')
                );

            } else {

                if( $results[self::STATUS_ERROR] !== 0 ) {

                    Message::addError(
                        $this->translator->trans('softgarden.msg.import_error', [], 'contao_default')
                    );
                }

                if( $results[self::STATUS_NEW] || $results[self::STATUS_UPDATE] ) {

                    Message::addInfo(sprintf(
                        $this->translator->trans('softgarden.msg.import_success', [], 'contao_default')
                    ,   $results[self::STATUS_NEW]
                    ,   $results[self::STATUS_UPDATE]
                    ));
                }
            }

        } else {

            if( empty($ads) ) {

                $this->logger->log(LogLevel::ERROR, 'Could not import job advertisements for news archive ID ' .$archive->id, ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);

            } else {

                if( $results[self::STATUS_ERROR] !== 0 ) {

                    $this->logger->log(LogLevel::ERROR, 'Failed to import ' .$results[self::STATUS_ERROR]. ' job advertisements for news archive ID ' .$archive->id, ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]);
                }

                if( $results[self::STATUS_NEW] || $results[self::STATUS_UPDATE] ) {

                    $this->logger->log(LogLevel::INFO, 'Successfully imported job advertisements for news archive ID ' .$archive->id. ' (' .$results[self::STATUS_NEW]. ' new / ' .$results[self::STATUS_UPDATE]. ' updated)', ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]);
                }
            }
        }
    }


    /**
     * Imports the position into the given archive
     *
     * @param object $position
     * @param \Contao\NewsArchiveModel $archive
     *
     * @return int Status code
     */
    private function importAdvertisement( object $position, NewsArchiveModel $archive ): int {

        // find existing news...
        $news = null;
        $news = NewsModel::findOneBy(['pid=?','softgarden_id=?'],[$archive->id,$position->jobDbId]);

        //... or create a new one
        if( !$news ) {

            $news = new NewsModel();

            $news->pid = $archive->id;
            $news->softgarden_id = $position->jobDbId;
            $news->tstamp = $position->postingLastUpdatedDate/1000;
            $news->author = $archive->softgarden_default_author;
            $news->source = 'default';
            $news->published = false;
        }

        $isUpdate = (bool) $news->id;

        // set / update metadata
        $news->headline = $position->externalPostingName;
        $news->alias = $news->softgarden_id.'-'.StringUtil::standardize($news->headline);
        $news->date = $news->time = $position->postingLastUpdatedDate/1000;

        // set content
        if( !empty($position->jobAdText) ) {

            // make sure we have an id to work with
            if( !$news->id ) {
                $news->save();
            }

            $news->teaser = '<p>' . strip_tags(StringUtil::substrHtml($position->jobAdText,200)) . '…</p>';

            // find existing Content Element...
            $content = null;
            $content = ContentModel::findBy(['ptable=?','pid=?','type=?'], [NewsModel::getTable(), $news->id, 'text'], ['order' => 'sorting ASC']);

            // ... or create a new one
            if( !$content ) {

                $content = new ContentModel();
                $content->ptable = NewsModel::getTable();
                $content->pid = $news->id;
                $content->sorting = 128;
            }

            $content->tstamp = time();
            $content->type = 'text';

            $content->text = $position->jobAdText;

            $content->save();
        }

        $news->published = '1';

        // additional parsing
        $event = new SoftgardenParseEvent($position, $news, $this->api);
        $this->eventDispatcher->dispatch($event, SoftgardenEvents::IMPORT_ADVERTISEMENT);
        $news = $event->getNews();

        $news->save();

        return $isUpdate ? self::STATUS_UPDATE : self::STATUS_NEW;
    }
}
