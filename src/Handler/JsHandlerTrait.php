<?php

namespace DevCoding\Pleasing\Handler;

trait JsHandlerTrait
{
  /**
   * Returns TRUE for, .js files (in any case), indicating that this parser handles those file types.
   *
   * @param string $path
   *
   * @return bool
   */
  public static function handles(string $path): bool
  {
    return preg_match('#\.(js)$#i', $path);
  }
}