<?php

namespace DevCoding\Pleasing\Filter;

use DevCoding\Pleasing\Asset\AssetInterface;
use DevCoding\Pleasing\Asset\FileAsset;

class AsseticFilter implements FilterInterface
{
  protected \Assetic\Filter\FilterInterface $filter;

  /**
   * @param \Assetic\Filter\FilterInterface $filter
   */
  public function __construct(\Assetic\Filter\FilterInterface $filter)
  {
    $this->filter = $filter;
  }

  public function load(AssetInterface $asset)
  {
    if ($asset instanceof FileAsset)
    {
      $assetic = $this->getAsseticFileAsset($asset);

      $this->filter->filterLoad($assetic);

      $asset->setContent($assetic->getContent());
    }
  }

  public function apply(AssetInterface $Asset)
  {
    if ($Asset instanceof FileAsset)
    {
      $assetic = $this->getAsseticFileAsset($Asset);

      $this->filter->filterDump($assetic);

      $Asset->setContent($assetic->getContent());
    }
  }

  protected function getAsseticFileAsset(FileAsset $asset): \Assetic\Asset\FileAsset
  {
    $assetic = new \Assetic\Asset\FileAsset($asset->getPathname());

    $assetic->setContent($asset->getContent());

    return $assetic;
  }
}
