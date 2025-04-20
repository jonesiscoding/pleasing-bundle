<?php

namespace DevCoding\Pleasing\Parsers;

use DevCoding\Pleasing\Handler\SassHandler;

/**
 * Parses less, sass, scss and css files for locally imported dependencies.
 */
class SassParser extends SassHandler implements ParserInterface
{
  /** @var array Array of dependencies, keyed by the previously parsed parent file paths  */
  protected array $parsed = [];

  /**
   * Returns TRUE for .sass, .css, and .scss files (in any case), indicating that this parser
   * handles those file types.
   *
   * @param string $filePath
   *
   * @return bool
   */
  public static function handles(string $filePath): bool
  {
    return preg_match('#\.(scss|sass|css)$#i', $filePath);
  }

  /**
   * Parses a single CSS file for @import/@use/@forward lines, excluding those for non-local URLs.
   *
   * @param string $filePath
   *
   * @return string[] Array of imported, used, and forwarded dependencies.
   */
  public function parse(string $filePath): array
  {
    if (!in_array($filePath, $this->parsed))
    {
      $this->parsed[$filePath] = [];

      // Get Contents & Parse
      $lines = explode(PHP_EOL, file_get_contents($filePath));
      foreach($lines as $line)
      {
        // Exclude things that are commented out.
        if (strlen($line) > 0 && "//" != substr($line, 0, 2))
        {
          if (preg_match("#^\s*@(import|use|forward)\s*(url|reference|inline)?[\(\"';\s]+?(~?([^'\";\s]+))[\)\"';\s]+?.*$#", $line, $matches))
          {
            // Exclude Remote Imports
            if ("url" != $matches[2])
            {
              if (0 !== strpos($matches[3], 'sass:'))
              {
                $this->parsed[$filePath][] = $matches[3];
              }
            }
          }
        }
      }

      $this->parsed[$filePath] = array_unique($this->parsed[$filePath]);
    }

    return $this->parsed[$filePath];
  }
}
