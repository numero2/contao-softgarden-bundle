Contao Softgarden Bundle
=======================

[![](https://img.shields.io/packagist/v/numero2/contao-softgarden-bundle.svg?style=flat-square)](https://packagist.org/packages/numero2/contao-softgarden-bundle) [![License: LGPL v3](https://img.shields.io/badge/License-LGPL%20v3-blue.svg?style=flat-square)](http://www.gnu.org/licenses/lgpl-3.0)

About
--

Import job advertisements from [softgarden e-recruiting](https://softgarden.com/) as news into Contao.

System requirements
--

* [Contao 4.13](https://github.com/contao/contao) (or newer)

Installation
--

* Install via Contao Manager or Composer (`composer require numero2/contao-softgarden-bundle`)
* Run a database update via the Contao-Installtool or using the [contao:migrate](https://docs.contao.org/dev/reference/commands/) command.

Events
--

By default the bundle only imports basic information from softgarden that can be matched to the structure of Contao's news. If you need more data you can import them on your own using the `contao.softgarden_import_advertisement` event.

> [!IMPORTANT]
> This example shows how to import additional job information from softgarden.<br>
> **Note:** You must first define any custom fields in your own `contao/dca/tl_news.php` as they are not part of Contao's core.

```php
// src/EventListener/SoftgardenParseListener.php
namespace App\EventListener;

use numero2\SoftgardenBundle\API\SoftgardenCatalogTypes;
use numero2\SoftgardenBundle\Event\SoftgardenEvents;
use numero2\SoftgardenBundle\Event\SoftgardenParseEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;


#[AsEventListener(SoftgardenEvents::IMPORT_ADVERTISEMENT)]
class SoftgardenParseListener {

    public function __invoke( SoftgardenParseEvent $event ): void {

        $position = $event->getPosition();
        $news = $event->getNews();

        // add some additional data
        $news->job_company_name = $position->company_name??'';
        $news->job_location_street = $position->geo_name??'';
        $news->job_location_postal = $position->geo_zip??'';
        $news->job_location_city = $position->geo_city??'';
        $news->job_apply_link = $position->applyOnlineLink??'';

        $archive = $news->getRelated('pid');
        $api = $event->getAPI();

        // resolve catalog values
        $news->job_categegory = $api->resolveCatalogValue(SoftgardenCatalogTypes::jobCategory, $position->jobCategories[0], $archive->softgarden_language)??'';
        $news->job_audience = $api->resolveCatalogValue(SoftgardenCatalogTypes::audience, $position->audiences[0], $archive->softgarden_language)??'';
        $news->job_employmentType = $api->resolveCatalogValue(SoftgardenCatalogTypes::employmentType, $position->employmentTypes[0], $archive->softgarden_language)??'';
        $news->job_workTime = $api->resolveCatalogValue(SoftgardenCatalogTypes::workingHours, $position->workTimes[0], $archive->softgarden_language)??'';
        $news->job_industry = $api->resolveCatalogValue(SoftgardenCatalogTypes::positionIndustry, $position->industries[0], $archive->softgarden_language)??'';
        $news->job_experience = $api->resolveCatalogValue(SoftgardenCatalogTypes::workExperience, $position->workExperiences[0], $archive->softgarden_language)??'';
    }
}
```