<?php

namespace DevCoding\Pleasing\Asset;

use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

interface AssetInterface extends SelfCheckingResourceInterface
{
  public function getContent(): string;
}