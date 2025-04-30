<?php

namespace DevCoding\Pleasing\Command;

use ArrayObject;
use DevCoding\Pleasing\Command\Progress\AssetProgress;
use DevCoding\Pleasing\Config\MimeTypes;
use DevCoding\Pleasing\Pleasing;
use DevCoding\Pleasing\Config\Config;
use DevCoding\Pleasing\Config\AssetConfig;
use DevCoding\Pleasing\Config\AssetContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class AssetCommand extends Command
{
  protected InputInterface  $Input;
  protected OutputInterface $Output;
  protected \ArrayObject    $extensions;
  protected Filesystem      $Filesystem;
  protected AssetProgress   $Progress;

  /** @var Pleasing */
  protected Pleasing $Pleasing;
  /** @var Config */
  protected Config $Config;
  /** @var array */
  protected array $_manifest;
  /** @var array */
  protected array $dumped;
  /** @var array */
  protected array $errors = [];

  protected function initialize(InputInterface $Input, OutputInterface $Output)
  {
    $this->Input      = $Input;
    $this->Output     = $Output;
    $this->Filesystem = new Filesystem();

    $this->extensions = new \ArrayObject([
        'fonts'  => MimeTypes::fonts()->extensions(),
        'images' => MimeTypes::images()->extensions(),
        'code'   => MimeTypes::code()->extensions(),
        'styles' => MimeTypes::styles()->extensions(),
    ], ArrayObject::ARRAY_AS_PROPS);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
        ->setName('assets:export')
        ->setDescription('Dumps assets for production deployment.')
        ->addOption('purge-only', null, InputOption::VALUE_NONE, 'Only purge assets, do not create')
        ->addOption('force', null, InputOption::VALUE_NONE, 'Require full refresh of all assets; even cached assets.')
    ;
  }

  /**
   * @throws \Exception
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    if($this->Config->debug)
    {
      throw new \Exception('Debug mode is not supported');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $output->writeln('');
    if (!$this->isPurgeOnly())
    {
      $this->Progress = (new AssetProgress($output, 5))->setMessage('Populating Categories')->display();

      // Fonts
      $Export = $this->Config->assets->filterByAlwaysExport();
      $fonts  = $Export->filterByFonts();
      $this->Progress->advance();

      // Images
      $images = $Export->filterImages();
      $this->Progress->advance();

      // Styles
      $styles = $Export->filterStyles();
      $this->Progress->advance();

      // Code
      $code = $this->getCodeAssets();
      $this->Progress->advance();

      // Count it All
      $count = count($code) + count($fonts) + count($images) + count($styles);
      $this->Progress->finish('Categories Populated');
      $this->Progress = (new AssetProgress($output, $count))->setMessage('Dumping Assets');

      if ($fonts->count() > 0)
      {
        $this->Progress->setMessage('Dumping Fonts');

        foreach($fonts->getArrayCopy() as $Asset)
        {
          $this->executeAsset($Asset);
        }
      }

      if ($images->count() > 0)
      {
        $this->Progress->setMessage('Dumping Images');

        foreach($images->getArrayCopy() as $Asset)
        {
          $this->executeAsset($Asset);
        }
      }

      if (count($styles) > 0)
      {
        $this->Progress->setMessage('Dumping CSS');
        foreach($styles as $Asset)
        {
          $this->executeAsset($Asset);
        }
      }

      if (count($code) > 0)
      {
        $this->Progress->setMessage('Dumping Code');
        foreach($code as $Asset)
        {
          $this->executeAsset($Asset);
        }
      }
    }

    // Purge Orphaned Assets
    $type = ($this->Input->getOption('purge-only')) ? 'all' : 'orphaned';
    $this->Progress->finish('Asset Export Complete');
    // Purge Orphaned Assets
    $purge = $this->getPurgeable();
    if (count($purge) > 0)
    {
      $this->Progress = new AssetProgress($output, count($purge), 'Purging' . $type);

      foreach($purge as $file)
      {
        try
        {
          $this->Progress->setMessage($file);
          $this->Filesystem->remove($file);
          $this->Progress->advance();
        }
        catch(\Exception $e)
        {
          $this->Progress->setMessage($file, 'error')->advance();
          $this->errors[$file] = $e->getMessage();
        }
      }

      $this->Progress->finish(sprintf("Purge of %s Complete", $type));
    }

    $output->writeln('');
    if (!empty($this->errors))
    {
      foreach($this->errors as $error)
      {
        $output->writeln(sprintf('<error>%s</error>', $error));
      }

      $output->writeln('');

      return Command::FAILURE;
    }

    return Command::SUCCESS;
  }

  /**
   * Dumps the given asset file, while providing user feedback based on verbosity
   * @param AssetConfig $Asset
   *
   * @return void
   */
  protected function executeAsset(AssetConfig $Asset)
  {
    $this->Progress->setMessage($Asset->output);

    try
    {
      $this->dump($Asset);
      $this->Progress->advance();
    }
    catch(\Exception $exception)
    {
      $this->Progress->setMessage($Asset->output, 'error')->advance();
      $this->errors[$Asset->output] = $exception->getMessage();
    }
  }

  /**
   * @param AssetConfig $Asset
   *
   * @return true
   * @throws \Exception
   */
  protected function dump(AssetConfig $Asset): bool
  {
    $out = $this->getPublicPath($Asset);

    // Remove the old file, if it exists
    $this->Filesystem->remove($out);
    // Make the directory, if it doesn't exist
    $this->Filesystem->mkdir(dirname($out));

    if (!$Asset->isFresh() || $this->Input->getOption('force'))
    {
      // Refresh the asset if not fresh or if forcing all to refresh
      $Asset = $this->Pleasing->refresh($Asset);
    }

    if (!@copy($Asset->getPath(), $out))
    {
      throw new IOException(sprintf('Copy Failed: "%s" => "%s"', $Asset->getPath(), $out));
    }

    if (!is_readable($out))
    {
      throw new IOException(sprintf('File Missing: "%s" => "%s"', $Asset->getPath(), $out));
    }

    $this->dumped[] = $out;

    return true;
  }

  /**
   * @return AssetContainer
   */
  protected function getCodeAssets(): AssetContainer
  {
    return $this->Config->assets->filterByExtension($this->extensions['code']);
  }

  /**
   * @return bool
   */
  protected function isPurgeOnly(): bool
  {
    return $this->Input->getOption('purge-only');
  }



  // region //////////////////////////////////////////////// Config Parameters

  /**
   * @param AssetConfig $Asset
   *
   * @return string
   */
  protected function getManifestUrl(AssetConfig $Asset): ?string
  {
    if(!isset($this->_manifest))
    {
      $this->_manifest = $this->Pleasing->readManifest();

      if(empty($this->_manifest))
      {
        $this->Pleasing->writeManifest();

        $this->_manifest = $this->Pleasing->readManifest();
      }
    }

    return $this->_manifest[ $Asset->output ] ?? null;
  }

  /**
   * @param AssetConfig $Asset
   *
   * @return string
   */
  protected function getPublicPath(AssetConfig $Asset): string
  {
    if (str_starts_with($Asset->output, 'error/'))
    {
      $suffix = $Asset->output;
    }
    else
    {
      $suffix = $this->getManifestUrl($Asset) ?? $Asset->output;
    }

    return sprintf('%s/%s', $this->Config->directories->public, $suffix);
  }

  // region //////////////////////////////////////////////// File Management Methods

  protected function getPurgeable(): array
  {
    $unlink = [];
    $files  = [];
    $public = $this->Config->directories->public;
    $all    = $this->getAssetDirectories();
    foreach ($all as $aType)
    {
      $dir = $public . '/' . $aType;
      if(is_dir($dir))
      {
        $RII = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach($RII as $file)
        {
          if ($file->isDir())
          {
            continue;
          }

          $files[] = $file->getPathname();
        }

        $unlink = array_merge($unlink, $this->getPurgeableFiles($files, $aType));
      }
    }

    return array_filter($unlink, function($file) { return !in_array($file, $this->dumped); });
  }

  protected function getAssetDirectories(): array
  {
    $dirs = [];
    foreach($this->Config->assets->getArrayCopy() as $AssetConfig)
    {
      $dirs[dirname($AssetConfig->output)] = true;
    }

    return array_keys($dirs);
  }

  protected function getPurgeableFiles($files, $type): array
  {
    $unlink = [];
    foreach($files as $filename)
    {
      if ($this->isPurgeable($filename, $type))
      {
        $unlink[] = $filename;
      }
    }

    return $unlink;
  }

  /**
   * @param $path
   * @param $subDir
   *
   * @return bool
   */
  protected function isPurgeable($path, $subDir): bool
  {
    // Only purge if in the correct directory
    $dir = $this->Config->directories->public.'/'.$subDir;
    if (0 === strpos($path, $dir))
    {
      // Only purge if .gitignore recognizes the file
      if ($this->isIgnored($path))
      {
        // Determine the type
        if (preg_match('#([a-zA-Z]+)/([a-zA-Z]+)#', $subDir, $matches))
        {
          $type = $matches[2];
        }
        else
        {
          $type = $subDir;
        }

        // Make sure the type matches
        switch($type)
        {
          case 'images':
          case 'extra/images':
            return $this->isImage($path);
          case 'fonts':
          case 'extra/fonts':
          case 'css/fonts':
            return $this->isFont($path);
          case 'error':
            return true;
          default:
            $realType = false !== strpos($type, '/') ? basename($type) : $type;

            return $this->getExtension($path) === $realType;
        }
      }
    }

    return false;
  }

  protected function isFont($filename): bool
  {
    return in_array($this->getExtension($filename), $this->extensions->fonts);
  }

  protected function isImage($filename): bool
  {
    return in_array($this->getExtension($filename), $this->extensions->images);
  }

  protected function isIgnored($file): bool
  {
    $Git = Process::fromShellCommandline(sprintf('git check-ignore "%s"', $file));
    $Git->run();

    return $Git->isSuccessful() && !empty($Git->getOutput());
  }

  /**
   * Gets the extension from a filename, for use in situations where a full path is not available.
   *
   * @param string $filename The filename to determine the extension of.
   *
   * @return  string         The extension.  If no extension can be found, then returns an empty string.
   */
  public function getExtension(string $filename): string
  {
    if (false !== $pos = strrpos($filename, '.'))
    {
      return substr($filename, $pos);
    }

    return '';
  }

  // endregion ///////////////////////////////////////////// End File Management Methods
}
