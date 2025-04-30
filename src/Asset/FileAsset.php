<?php

namespace DevCoding\Pleasing\Asset;

use DevCoding\Pleasing\Filter\FilterCollection;
use DevCoding\Pleasing\Filter\FilterInterface;
use DevCoding\Pleasing\Handler\HandlerInterface;

class FileAsset implements AssetInterface
{
  protected string           $path;
  protected FilterCollection $Filters;
  protected string           $content;
  protected string           $cache;

  /**
   * @param string                $path
   * @param FilterCollection|null $Filters
   */
  public function __construct(string $path, ?FilterCollection $Filters = null)
  {
    // We want to write this to a temporary cache location...
    // That way each load/dump isn't done from scratch, individual files in an asset can be cached

    $this->path    = $path;
    $this->Filters = $Filters;
  }

  public function withContent(string $content): FileAsset
  {
    $asset          = clone $this;
    $asset->content = $content;

    return $asset;
  }

  public function __clone()
  {
    $this->content = null;
  }

  public function getPathname(): string
  {
    return $this->path;
  }

  public function __toString(): string
  {
    return $this->path;
  }

  public function getContent(): string
  {
    if (!isset($this->content))
    {
      $this->content = file_get_contents($this->getPathname());

      foreach($this->Filters->all() as $filter)
      {
        $this->loadFilter($filter);
      }

      foreach($this->Filters->all() as $filter)
      {
        $this->applyFilter($filter);
      }
    }

    return $this->content;
  }

  public function setContent(string $content): FileAsset
  {
    $this->content = $content;

    return $this;
  }

  public function applyFilter(FilterInterface $Filter)
  {
    if (!$Filter instanceof HandlerInterface || $Filter->handles($this->getPathname()))
    {
      $asset = $this->withContent($this->getContent());
      $Filter->apply($asset);

      $this->setContent($asset->content);
    }
  }

  public function loadFilter(FilterInterface $Filter)
  {
    if (method_exists($Filter, 'load'))
    {
      $asset = $this->withContent($this->content);

      $Filter->load($asset);

      $this->setContent($asset->content);
    }
  }
}
