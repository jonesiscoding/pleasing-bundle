<?php

namespace DevCoding\Pleasing\Asset;


interface AssetInterface
{
  public function getContent(): string;

  public function isFresh(int $timestamp): bool;
}
