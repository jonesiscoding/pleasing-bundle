<?php

namespace DevCoding\Pleasing\Asset;

use DevCoding\Pleasing\Exception\NotFoundException;
use DevCoding\Pleasing\Locators\LocatorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Config\Resource\FileResource;

class InputCollection implements PsrContainerInterface, \Countable, \IteratorAggregate
{
  const IMAGES = ['gif', 'jpg', 'png', 'svg', 'tif'];
  const FONTS  = ['otf', 'eot', 'svg', 'ttf', 'woff', 'woff2'];
  const CSS    = ['css'];
  const SASS   = ['scss', 'sass'];
  const LESS   = ['less'];

  protected array            $data = [];
  protected \ArrayIterator   $iterator;
  protected LocatorInterface $Locator;
  protected string           $content;
  protected array            $resources;

  /**
   * @param array            $data
   * @param LocatorInterface $locator
   */
  public function __construct(array $data, LocatorInterface $locator)
  {
    $this->data    = $data;
    $this->Locator = $locator;
  }

  /**
   * @param string $id
   *
   * @return AssetInterface
   */
  public function get(string $id): AssetInterface
  {
    if ($this->has($id))
    {
      if (!$this->data[$id] instanceof AssetInterface)
      {
        $this->data[$id] = $this->Locator->locate($this->data[$id]);
      }

      return $this->data[$id];
    }

    throw new NotFoundException($id);
  }

  /**
   * @return string
   */
  public function getContent(): string
  {
    if (!isset($this->content))
    {
      $parts = [];
      foreach(array_keys($this->data) as $id)
      {
        if ($Asset = $this->get($id))
        {
          $parts[] = $Asset->getContent();
        }
      }

      $this->content = implode("\n", $parts);
    }

    return $this->content;
  }

  public function has($id): bool
  {
    return isset($this->data[$id]);
  }

  /**
   * @return \ArrayIterator
   */
  public function getIterator(): \ArrayIterator
  {
    if (!isset($this->iterator))
    {
      $this->iterator = new \ArrayIterator($this->data);
    }

    return $this->iterator;
  }

  /**
   * @return FileResource[]
   */
  public function getResources(): array
  {
    if (!isset($this->resources))
    {
      $this->resources = [];

      foreach($this->getIterator() as $asset)
      {
        if ($asset instanceof FileAsset)
        {
          $this->resources[$asset->getPathname()] = $asset;

          if ($asset instanceof ComplexFileAsset)
          {
            foreach($asset->getImports() as $import)
            {
              $this->resources[$import->getPathname()] = $import;
            }
          }
        }
      }
    }

    return $this->resources;
  }

  public function count(): int
  {
    return $this->getIterator()->count();
  }

  protected function build($id): InputCollection
  {
    if ($this->has($id))
    {
      if (!$this->data[$id] instanceof AssetInterface)
      {
        $this->data[$id] = $this->Locator->locate($this->data[$id]);
      }
    }

    return $this;
  }
}
