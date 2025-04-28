<?php

namespace DevCoding\Pleasing\DependencyInjection\Asset;

/**
 * Interface requiring a class to define a method by which all assets are loaded.
 * Works in combination with AssetSubscriberInterface.
 */
interface AssetLoaderInterface
{
  /**
   * Must load all assets returned by getSubscribedAssets.
   *
   * @return AssetLoaderInterface
   */
  public function loadAssets(): AssetLoaderInterface;
}
