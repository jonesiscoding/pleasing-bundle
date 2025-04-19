<?php

namespace DevCoding\Pleasing\Filter;

use DevCoding\Pleasing\Asset\AssetInterface;

interface FilterInterface
{
  public function apply(AssetInterface $Asset);
}
