<?php

namespace Netflex\Pages;

use Netflex\Pages\AbstractPage;

class Page extends AbstractPage
{
  protected static function makeQueryBuilder($appends = [])
  {
    $builder = parent::makeQueryBuilder($appends);

    return $builder->where('type', static::TYPE_PAGE);
  }

  public static function all()
  {
    return once(function () {
      return parent::all()
        ->whereIn('type', [
          static::TYPE_DOMAIN,
          static::TYPE_EXTERNAL,
          static::TYPE_FOLDER,
          static::TYPE_INTERNAL,
          static::TYPE_PAGE,
        ]);
    });
  }
}
