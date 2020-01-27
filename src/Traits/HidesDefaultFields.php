<?php

namespace Netflex\Pages\Traits;

trait HidesDefaultFields
{
  public static function bootHidesDefaultFields()
  {
    $defaults = [
      'published',
      'start',
      'stop'
    ];

    static::retrieved(function ($model) use ($defaults) {
      $model->hidden = array_merge($model->hidden, $defaults);
    });

    static::created(function ($model) use ($defaults) {
      $model->hidden = array_merge($model->hidden, $defaults);
    });
  }
}
