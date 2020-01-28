<?php

namespace Bifrost\Database\Eloquent\Concerns;

use Exception;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

trait AttributesResolverDefaults
{

  /**
   * @return string
   * @throws Exception
   */
  protected function attributeResolverUuid(): string
  {
    return Uuid::uuid4()->toString();;
  }

  /**
   * @return Carbon
   */
  protected function attributeResolverNow(): Carbon
  {
    return Carbon::now();
  }

}
