<?php

namespace DevCoding\Pleasing\Config;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class FauxKernel extends Kernel
{
  public function registerBundles(): iterable
  {
    throw new \LogicException(get_class($this).' is not a real Symfony Kernel');
  }

  public function registerContainerConfiguration(LoaderInterface $loader)
  {
    throw new \LogicException(get_class($this).' is not a real Symfony Kernel');
  }
}
