<?php

namespace Netflex\Pages\Providers;

use Artesaos\SEOTools\SEOTools;
use Artesaos\SEOTools\SEOMeta;
use Artesaos\SEOTools\OpenGraph;
use Artesaos\SEOTools\TwitterCards;
use Artesaos\SEOTools\JsonLd;

use Artesaos\SEOTools\Contracts\SEOTools as SEOToolsContract;
use Artesaos\SEOTools\Contracts\MetaTags as MetaTagsContract;
use Artesaos\SEOTools\Contracts\TwitterCards as TwitterCardsContract;
use Artesaos\SEOTools\Contracts\OpenGraph as OpenGraphContract;
use Artesaos\SEOTools\Contracts\JsonLd as JsonLdContract;

use Artesaos\SEOTools\Providers\SEOToolsServiceProvider as ServiceProvider;
use Illuminate\Config\Repository as Config;
use Netflex\Foundation\Variable;

class SEOToolsServiceProvider extends ServiceProvider
{

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton('seotools.metatags', function ($app) {
      return new SEOMeta(new Config([
        'defaults'       => [
          'title'        => Variable::get('site_meta_title') ?? false,
          'titleBefore'  => false,
          'description'  => Variable::get('site_meta_description') ?? false,
          'separator'    => ' - ',
          'keywords'     => explode(',', Variable::get('site_meta_keywords') ?? ''),
          'canonical'    => false,
          'robots'       => false,
        ],

        'webmaster_tags' => [
          'google'    => null,
          'bing'      => null,
          'alexa'     => null,
          'pinterest' => null,
          'yandex'    => null,
        ],

        'add_notranslate_class' => false,
      ]));
    });

    $this->app->singleton('seotools.opengraph', function ($app) {
      return new OpenGraph([
        'defaults' => [
          'title'       => Variable::get('site_meta_title') ?? false,
          'description' => Variable::get('site_meta_description') ?? false,
          'url'         => false,
          'type'        => false,
          'site_name'   => false,
          'images'      => [],
        ]
      ]);
    });

    $this->app->singleton('seotools.twitter', function ($app) {
      return new TwitterCards([
        'defaults' => [
          'card'        => 'summary',
          'site'        => false,
        ],
      ]);
    });

    $this->app->singleton('seotools.json-ld', function ($app) {
      return new JsonLd([
        'title'       => Variable::get('site_meta_title') ?? false,
        'description' => Variable::get('site_meta_description') ?? false,
        'url'         => false, // Set null for using Url::current(), set false to total remove
        'type'        => 'WebPage',
        'images'      => [],
      ]);
    });

    $this->app->singleton('seotools', function () {
      return new SEOTools();
    });

    $this->app->bind(MetaTagsContract::class, 'seotools.metatags');
    $this->app->bind(OpenGraphContract::class, 'seotools.opengraph');
    $this->app->bind(TwitterCardsContract::class, 'seotools.twitter');
    $this->app->bind(JsonLdContract::class, 'seotools.json-ld');
    $this->app->bind(SEOToolsContract::class, 'seotools');
  }

  /**
   * {@inheritdoc}
   */
  public function provides()
  {
    return [
      SEOToolsContract::class,
      MetaTagsContract::class,
      TwitterCardsContract::class,
      OpenGraphContract::class,
      JsonLdContract::class,
      'seotools',
      'seotools.metatags',
      'seotools.opengraph',
      'seotools.twitter',
      'seotools.json-ld',
    ];
  }
}
