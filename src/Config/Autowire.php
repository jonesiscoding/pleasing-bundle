<?php

namespace DevCoding\Pleasing\Config;

use DevCoding\Pleasing\Locators\SassLocator;

/**
 * Guesses the configuration of a pleasing asset using the given info.
 */
class Autowire
{
  const RESOURCES = '/^(.*)\/Resources\/(public\/)?([a-zA-Z0-9_\-.\/]+)$/i';

  protected bool $debug;
  protected array  $extensions = ['css', 'js'];
  protected array  $excluded   = ['package.js', 'webpack.config.js', 'karma.conf.js', 'Gruntfile.js'];

  /**
   * @param bool $debug
   */
  public function __construct(bool $debug)
  {
    $this->debug = $debug;
  }

  /**
   * @param array $config
   *
   * @return array
   */
  public function autowire(array $config): array
  {
    $Directories = new Directories($config);
    $assets      = $config[Config::ASSETS] ?? [];
    $autowire    = $config->autowire       ?? [];
    $public      = $this->fromPublic($Directories->public);
    $outputs     = array_map(function($v) { return $v['output']; }, $assets);

    foreach($autowire as $extra)
    {
      foreach($this->fromInput($extra, !$this->debug) as $itemConfig)
      {
        if (!$key = array_search($itemConfig['output'], $outputs))
        {
          $key          = str_replace('/', '.', $itemConfig['output']);
          $assets[$key] = $itemConfig;
        }
        else
        {
          $assets[$key]['always_export'] = true;
        }
      }
    }

    foreach($public as $bundle)
    {
      $key          = str_replace('/', '.', $bundle['output']);
      $assets[$key] = $bundle;
    }

    $config[Config::ASSETS] = $assets;
    $config[Config::WIRED]  = true;

    return $config;
  }

  /**
   * @param string $pubDir
   *
   * @return array
   */
  private function fromPublic(string $pubDir): array
  {
    $public = [];
    $RII    = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pubDir.'/bundles'));
    foreach($RII as $file)
    {
      /** @var \SplFileInfo $file */
      if($file->isDir())
      {
        continue;
      }

      if (!in_array($file->getExtension(), $this->extensions))
      {
        continue;
      }

      if (in_array($file->getFilename(), $this->excluded))
      {
        continue;
      }

      if ($this->isNonMinifiedFileAvailable($file))
      {
        continue;
      }

      $public[] = [
          'inputs' => [$file->getPathname()],
          'output' => str_replace($pubDir.'/', '', $file->getPathname()),
      ];
    }

    return $public;
  }

  /**
   * @param \SplFileInfo $file
   *
   * @return bool
   */
  private function isNonMinifiedFileAvailable(\SplFileInfo $file): bool
  {
    $fileExt   = $file->getExtension();
    $isMinFile = false !== strpos($file->getFilename(), '.min.' . $fileExt);
    if ($isMinFile)
    {
      // Skip minified path if non-minified path exists
      $pattern = sprintf('#.%s$#', $fileExt);
      $nonMin  = preg_replace($pattern, '.min$0', $file->getPathname());

      return file_exists($nonMin);
    }

    return false;
  }

  /**
   * @param string|string[] $path Absolute input path(s) of the asset
   *
   * @return array        The pleasing configuration array
   */
  public function fromInput($path, $isMinify = false): array
  {
    $autowire   = [];
    $inputFiles = (is_dir($path)) ? glob($path . '/*.*') : [ $path ];

    foreach($inputFiles as $inputFile)
    {
      if(is_file($inputFile))
      {
        if(preg_match(self::RESOURCES, $inputFile, $matches))
        {
          $output   = $matches[ 3 ];
          $pInfo    = pathinfo($output);
          $ext      = $pInfo[ 'extension' ];
          $isPreCss = in_array($ext, ['scss', 'sass', 'less']);

          if(!$isPreCss || '_' !== substr($pInfo['filename'], 0, 1))
          {
            if($isPreCss)
            {
              $output = preg_replace('#^(' . $ext . '/)#', 'css/', $output);
              $output = preg_replace('#(/' . $ext . '/)#', '/css/', $output);
              $output = preg_replace('#(\.' . $ext . ')$#', '.css', $output);
            }

            $config = [
                'always_export' => true,
                'inputs'        => [$inputFile],
                'output'        => $output,
                'filters'       => $isMinify ? ['pleasing_minify'] : []
            ];

            $autowire[] = $config;
          }

        }
      }
    }

    return $autowire;
  }
}
