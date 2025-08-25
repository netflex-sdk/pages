<?php

namespace Netflex\Pages;

use Illuminate\Support\Traits\Macroable;

use Netflex\Support\Accessors;

/**
 * @property-read int $structure_id
 * @property-read int $entry_id
 * @property-read int $revision_id
 */
class PreviewRequest
{
  use Accessors;
  use Macroable;

  /** @var array */
  protected $attributes = [];

  public function __construct(JwtPayload $payload)
  {
    $this->attributes = $payload->toArray();
  }

  public function get($attribute, $default = null)
  {
    return $this->__get($attribute) ?? $default;
  }

  /**
   * Undocumented function
   *
   * @return null
   */
  public function user()
  {
    return null;
  }
}
