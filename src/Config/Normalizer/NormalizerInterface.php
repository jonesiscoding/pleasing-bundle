<?php

namespace DevCoding\Pleasing\Config\Normalizer;

use DevCoding\Pleasing\Config\AssetContainer;

interface NormalizerInterface
{
  public function normalize(AssetContainer $asset);
}