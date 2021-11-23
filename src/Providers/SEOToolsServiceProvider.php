<?php

namespace Netflex\Pages\Providers;

use Netflex\Foundation\Variable;

use Apility\SEOTools\SEOTools;
use Apility\SEOTools\SEOMeta;
use Apility\SEOTools\OpenGraph;
use Apility\SEOTools\TwitterCards;
use Apility\SEOTools\JsonLd;
use Apility\SEOTools\JsonLdMulti;
use Apility\SEOTools\Contracts\SEOTools as SEOToolsContract;
use Apility\SEOTools\Contracts\MetaTags as MetaTagsContract;
use Apility\SEOTools\Contracts\TwitterCards as TwitterCardsContract;
use Apility\SEOTools\Contracts\OpenGraph as OpenGraphContract;
use Apility\SEOTools\Contracts\JsonLd as JsonLdContract;
use Apility\SEOTools\Contracts\JsonLdMulti as JsonLdMultiContract;
use Apility\SEOTools\Providers\SEOToolsServiceProvider as ServiceProvider;

use Illuminate\Support\Facades\Config;
use Illuminate\Config\Repository as ConfigRepository;

class SEOToolsServiceProvider extends ServiceProvider
{
  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register()
  {
    if (!Config::has('seotools')) {
      Config::set('seotools', []);
    }

    Config::set('seotools', array_replace_recursive([
      'meta' => [
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
      ],
      'opengraph' => [
        'defaults' => [
          'title'       => Variable::get('site_meta_title') ?? false,
          'description' => Variable::get('site_meta_description') ?? false,
          'url'         => false,
          'type'        => false,
          'site_name'   => false,
          'images'      => [],
        ]
        ],
        'twitter' => [
          'defaults' => [
            'card'        => 'summary',
            'site'        => false,
          ],
        ],
        'json-ld' => [
            'title'       => Variable::get('site_meta_title') ?? false,
            'description' => Variable::get('site_meta_description') ?? false,
            'url'         => false, // Set null for using Url::current(), set false to total remove
            'type'        => 'WebPage',
            'images'      => [],
        ]
    ], Config::get('seotools', [])));

    $this->app->singleton('seotools.metatags', function ($app) {
      return new SEOMeta(new ConfigRepository(Config::get('seotools.meta', [])));
    });

    $this->app->singleton('seotools.opengraph', function ($app) {
      return new OpenGraph(Config::get('seotools.opengraph', []));
    });

    $this->app->singleton('seotools.twitter', function ($app) {
      return new TwitterCards(Config::get('seotools.twitter', []));
    });

    $this->app->singleton('seotools.json-ld', function ($app) {
      return new JsonLd(Config::get('seotools.json-ld', []));
    });

    $this->app->singleton('seotools.json-ld-multi', function ($app) {
      return new JsonLdMulti(Config::get('seotools.json-ld', []));
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
      JsonLdMultiContract::class,
      'seotools',
      'seotools.metatags',
      'seotools.opengraph',
      'seotools.twitter',
      'seotools.json-ld',
      'seotools.json-ld-multi'
    ];
  }
}
