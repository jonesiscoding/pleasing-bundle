<?php

namespace DevCoding\Pleasing\DependencyInjection\CacheWarmer;

use DevCoding\Pleasing\Pleasing;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms the cache by dumping all assets used in templates or configured for autowiring.
 *
 * Class PleasingCacheWarmer
 *
 * @author  Aaron M. Jones <am@jonesiscoding.com>
 * @package Common\PleasingBundle\Cache
 */
class PleasingCacheWarmer implements CacheWarmerInterface
{
  protected Pleasing $Pleasing;

  public function __construct(Pleasing $Pleasing)
  {
    $this->Pleasing = $Pleasing;
  }

  // region //////////////////////////////////////////////// Implemented Methods

  /**
   * @param string $cacheDir
   */
  public function warmUp(string $cacheDir)
  {
    $this->Pleasing->warm();
  }

  public function isOptional(): bool
  {
    return true;
  }

  // endregion ///////////////////////////////////////////// End Implemented Methods
}
