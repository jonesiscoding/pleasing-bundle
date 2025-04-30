<?php

namespace DevCoding\Pleasing\Processor;

use DevCoding\Pleasing\Asset\FileAsset;
use DevCoding\Pleasing\Asset\InputCollection;
use DevCoding\Pleasing\Handler\SassHandler;
use DevCoding\Pleasing\Locators\SassLocator;
use DevCoding\Pleasing\Config\Config;
use DevCoding\Pleasing\Config\AssetConfig;

class SassProcessor extends SassHandler implements ProcessorInterface
{
  protected Config $config;
  protected SassLocator $Locator;

  /**
   * @param Config           $_config
   * @param SassLocator|null $locator
   */
  public function __construct(Config $_config, ?SassLocator $locator = null)
  {
    $this->config  = $_config;
    $this->Locator = $locator ?? new SassLocator($this->config);
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
            $FilterCollection = $this->config->filters;
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
