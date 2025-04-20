<?php

namespace DevCoding\Pleasing\Handler;

class SassHandler implements HandlerInterface
{
  /**
   * Returns TRUE for, .sass, .css, and .scss files (in any case), indicating that this parser
   * handles those file types.
   *
   * @param string $path
   *
   * @return bool
   */
  public static function handles(string $path): bool
  {
    return preg_match('#\.(scss|sass|css)$#i', $path);
  }
}
