<?php

namespace DevCoding\Pleasing\Processor;

use DevCoding\Pleasing\Config\AssetConfig;

interface ProcessorInterface
{
  public function process(AssetConfig $Config): AssetConfig;
}
