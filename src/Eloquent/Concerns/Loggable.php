<?php

namespace Bifrost\Database\Eloquent\Concerns;

use Carbon\Carbon;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

trait Loggable
{

  /**
   * @var bool
   */
  public bool $loggable = true;

  /**
   * @var null|string
   */
  protected ?string $logConnection = null;

  /**
   * @var null|string
   */
  protected ?string $logTable = null;

  /**
   * Create the event listeners for created, updated and deleted events
   */
  public static function bootLoggable()
  {
    static::created(function($model){
      $model->createLogRegistry('created');
    });

    static::updated(function($model){
      $model->createLogRegistry('updated');
    });

    static::deleted(function ($model) {
      $model->createLogRegistry('deleted');
    });
  }

  /**
   * @param string $action
   * @return void
   */
  public function createLogRegistry(string $action): void
  {
    if ($this->loggable === false) {
      return;
    }

    $query = !blank($this->logConnection)
      ? DB::connection($this->logConnection)->table($this->logTable ?? 'entities_logs')
      : DB::table($this->logTable ?? 'entities_logs');

    $query
      ->insert([
        'id' => Uuid::generate(4),
        'created_at' => Carbon::now(),
        'subject_type' => parent::getMorphClass(),
        'subject_id' => parent::getKey(),
        'causer_id' => optional(Auth::user())->id,
        'causer_ip' => request()->getClientIp(),
        'json_data' => json_encode([
          'current'=> $this->getLogRegistryAttributes($action),
          'before'=> $action !== 'created' ? $this->getOriginal() : [],
        ]),
      ]);
  }

  /**
   * @param string $action
   * @return array
   */
  public function getLogRegistryAttributes(string $action): array
  {
    $attributes = [];
    if ($action !== 'deleted') {
      foreach ($this->getAttributes() as $key => $value) {
        if (in_array($key, $this->schemalessAttributes)) {
          $attributes[$key] = json_decode($value);
          continue;
        }

        $attributes[$key] = is_object($value) ? (string) $value : $value;
      }
    }

    return $attributes;
  }
}
