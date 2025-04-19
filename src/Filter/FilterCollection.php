<?php

namespace DevCoding\Pleasing\Filter;

use DevCoding\Pleasing\Config\FilterConfig;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class FilterCollection implements PsrContainerInterface, \Countable
{
  /** @var FilterConfig[]  */
  protected array $config;

  /** @var FilterInterface[] */
  protected array $filters = array();

  public function __construct(array $configs)
  {
    $this->config = $configs;
  }

  /**
   * @return FilterInterface[]
   */
  public function all(): array
  {
    foreach($this->config as $name => $config)
    {
      if (!isset($this->filters[$name]))
      {
        $this->filters[$name] = $config->build();
      }
    }

    return $this->filters;
  }

  public function count(): int
  {
    return count($this->config);
  }

  /**
   * @param \SplFileInfo|string $file
   *
   * @return FilterCollection
   */
  public function for($file): FilterCollection
  {
    $output = clone $this;
    $file   = $file instanceof \SplFileInfo ? $file : new \SplFileInfo($file);
    $name   = $file->getFilename();
    foreach($output->config as $id => $config)
    {
      if (!isset($config->apply_to) || !preg_match('#' . $config->apply_to . '#i', $name))
      {
        unset($output->config[$id], $output->filters[$id]);
      }
    }

    return $output;
  }

  /**
   * @param string $id
   *
   * @return FilterInterface
   */
  public function get(string $id): FilterInterface
  {
    if (!$this->has($id))
    {
      throw new \InvalidArgumentException("Filter '$id' does not exist.");
    }

    if (!isset($this->filters[$id]))
    {
      $this->filters[$id] = $this->config[$id]->build();
    }

    return $this->filters[$id];
  }

  public function has(string $id): bool
  {
    return isset($this->config[$id]);
  }
}
