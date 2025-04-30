<?php

namespace DevCoding\Pleasing\Processor;

use DevCoding\Pleasing\Asset\FileAsset;
use DevCoding\Pleasing\Asset\InputCollection;
use DevCoding\Pleasing\Config\AssetConfig;
use DevCoding\Pleasing\Config\Config;
use DevCoding\Pleasing\Locators\LocatorInterface;

abstract class FileProcessor implements ProcessorInterface
{
  protected Config $Config;
  protected LocatorInterface $Locator;

  /**
   * @param Config           $Config
   * @param LocatorInterface $Locator
   */
  public function __construct(Config $Config, LocatorInterface $Locator)
  {
    $this->Config  = $Config;
    $this->Locator = $Locator;
  }

  /**
   * @param AssetConfig $Asset
   * @param bool        $forceGen
   *
   * @return AssetConfig
   */
  public function process(AssetConfig $Asset, bool $forceGen = true): AssetConfig
  {
    if ($forceGen || !$Asset->isFresh())
    {
      $inputs = $Asset->inputs;
      if (!empty($inputs))
      {
        $collection = new InputCollection($inputs, $this->Locator);
        $content    = $collection->getContent();

        if (!empty($content))
        {
          if ($filters = $Asset->filters)
          {
            $FilterCollection = $this->Config->filters;
            $FilterAsset      = (new FileAsset($Asset->output))->withContent($content);
            foreach($filters as $filterId)
            {
              $Filter = $FilterCollection->get($filterId);
              $FilterAsset->loadFilter($Filter);
              $FilterAsset->applyFilter($Filter);
            }

            $content = $FilterAsset->getContent();
          }

          $Asset->write($content, $collection->getResources());
        }
      }
    }

    return $Asset;
  }
}
