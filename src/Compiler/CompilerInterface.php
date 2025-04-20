<?php

namespace DevCoding\Pleasing\Compiler;

use DevCoding\Pleasing\Config\AssetConfig;

interface CompilerInterface
{
  public function compile(AssetConfig $Config): AssetConfig;
}
