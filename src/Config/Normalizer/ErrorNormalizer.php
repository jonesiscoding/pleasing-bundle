<?php

namespace DevCoding\Pleasing\Config\Normalizer;

use DevCoding\Pleasing\Config\AssetConfig;
use DevCoding\Pleasing\Config\AssetContainer;

class ErrorNormalizer implements NormalizerInterface
{
  public function normalize(AssetContainer $AssetContainer)
  {
    foreach($AssetContainer as $item)
    {
      /** @var $item AssetConfig */
      if (false !== strpos($item->output, 'error/'))
      {
        $item->alwaysExport = true;
        $item->alwaysWarm   = true;
      }
    }
  }
}
