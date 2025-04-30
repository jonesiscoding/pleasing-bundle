<?php

namespace DevCoding\Pleasing\Asset;

interface AssetInterface
{
  /**
   * Must return the final contents of this asset as a string
   *
   * @return string
   */
  public function getContent(): string;
}
