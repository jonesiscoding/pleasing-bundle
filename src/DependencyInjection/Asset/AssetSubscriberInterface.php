<?php

namespace DevCoding\Pleasing\DependencyInjection\Asset;

/**
 * Interface requiring a class to define any required assets by their configured output path.
 */
interface AssetSubscriberInterface
{
  /**
   * Returns an array of parameters required by such instance.
   *
   * @return array The required asset output paths
   */
  public static function getSubscribedAssets(): array;
}