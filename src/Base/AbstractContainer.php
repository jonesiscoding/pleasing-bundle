<?php

namespace DevCoding\Pleasing\Base;

use DevCoding\Pleasing\Exception\NotFoundException;
use Psr\Container\ContainerInterface as PsrContainerInterface;

abstract class AbstractContainer implements \Countable, \IteratorAggregate, PsrContainerInterface
{
  /** @var \ArrayIterator
   * @noinspection PhpMissingFieldTypeInspection
   */
  private $iterator;

  /**
   * @var array $data
   */
  private array $data;

  abstract public function add($datum): AbstractContainer;

  /**
   * @param array $data
   */
  public function __construct(array $data = [])
  {
    foreach($data as $datum)
    {
      $this->add($datum);
    }
  }

  public function count(): int
  {
    return $this->getIterator()->count();
  }

  public function getArrayCopy(): array
  {
    return $this->getIterator()->getArrayCopy();
  }

  /**
   * @return \ArrayIterator
   */
  public function getIterator(): \Traversable
  {
    if (!isset($this->iterator))
    {
      $this->iterator = new \ArrayIterator($this->data);
    }

    return $this->iterator;
  }

  /**
   * @param string $id
   *
   * @return mixed
   */
  public function get(string $id)
  {
    try
    {
      return $this->getIterator()->offsetGet($id);
    }
    catch(\Throwable $e)
    {
      throw new NotFoundException(sprintf('The key %s was not found in this %s', $id, get_class($this)));
    }
  }

  public function has(string $id): bool
  {
    return $this->getIterator()->offsetExists($id);
  }

  /**
   * Returns an AbstractContainer with the given data by cloning this instance, replacing the data, and resetting the
   * iterator.
   *
   * @param array $data
   *
   * @return AbstractContainer
   */
  protected function with(array $data): AbstractContainer
  {
    $clone           = clone $this;
    $clone->data     = $data;
    $clone->iterator = null;

    return $clone;
  }
}
