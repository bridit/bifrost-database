<?php

namespace Bifrost\Database\Eloquent\Concerns;

use Symfony\Component\Translation\Exception\InvalidArgumentException;

trait HasStates
{

  /**
   * @var array
   */
  protected array $statesAttributes = [];

  /**
   * Check if states can be handle.
   *
   * @param string $key
   * @return bool
   */
  protected function hasStateBehaviour(string $key): bool
  {
    return in_array($key, $this->statesAttributes) && array_key_exists($key, $this->attributes);
  }

  /**
   * Check if transitions has been set.
   *
   * @param string $key
   * @param mixed $current
   * @return bool
   */
  protected function hasAllowedTransitions(string $key, $current): bool
  {
    return array_key_exists($key, $this->stateTransitionsMap) && array_key_exists($current, $this->stateTransitionsMap[$key]);
  }

  /**
   * Check if all state transitions can be performed.
   */
  protected function checkStatesTransition(): void
  {
    foreach ($this->statesAttributes as $attributeName)
    {
      $current = data_get($this->original, $attributeName, 'none');
      $new = $this->attributes[$attributeName];

      if (!$this->hasAllowedTransitions($attributeName, $current)) {
        continue;
      }

      if (!in_array($new, $this->stateTransitionsMap[$attributeName][$current])) {
        throw new InvalidArgumentException(sprintf('Transition from "%s" to "%s" not allowed on attribute "%s".', $current, $new, $attributeName));
      }
    }
  }

  /**
   * Check if transition can be done.
   *
   * @param string $key
   * @param mixed $current
   * @param mixed $new
   * @return bool
   */
  protected function checkAttributeStateTransition(string $key, $current, $new): bool
  {
    if (!$this->hasStateBehaviour($key) || !$this->hasAllowedTransitions($key, $current)) {
      return true;
    }

    return in_array($new, $this->stateTransitionsMap[$key][$current]);
  }

}
