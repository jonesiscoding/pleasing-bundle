<?php

namespace DevCoding\Pleasing\DependencyInjection\Compiler;

use DevCoding\Pleasing\Config\Normalizer\ErrorNormalizer;
use DevCoding\Pleasing\Processor\SassProcessor;
use DevCoding\Pleasing\Config\Autowire;
use DevCoding\Pleasing\Config\Config;
use DevCoding\Pleasing\Pleasing;
use DevCoding\Pleasing\Twig\PleasingExtension;
use DevCoding\Pleasing\DependencyInjection\Asset\AssetSubscriberInterface;
use DevCoding\Pleasing\DependencyInjection\Asset\AssetLoaderInterface;
use DevCoding\Pleasing\DependencyInjection\Locator\GlobFileLocator;
use DevCoding\Pleasing\DependencyInjection\CacheWarmer\PleasingCacheWarmer;
use DevCoding\Pleasing\Processor\FontAssetProcessor;
use DevCoding\Pleasing\Processor\ImageAssetProcessor;
use DevCoding\Pleasing\Config\Normalizer\TwigNormalizer;
use DevCoding\Pleasing\Locators\SassLocator;
use DevCoding\Pleasing\Parsers\SassParser;
use DevCoding\Pleasing\VersionStrategy\PleasingVersionStrategy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use DevCoding\Pleasing\DependencyInjection\Loader\YamlFileLoader;

class PleasingPass implements CompilerPassInterface
{
  const TAG_NORMALIZER = 'pleasing.normalizer';
  const TAG_PROCESSOR  = 'pleasing.processor';
  const TAG_PARSER     = 'pleasing.parser';
  const TAG_LOCATOR    = 'pleasing.locator';
  const TAG_SUBSCRIBER = 'asset.subscriber';

  /** @var Reference */
  private Reference $config;

  /**
   * @param ContainerBuilder $container
   *
   * @return void
   * @throws \Exception
   */
  protected function load(ContainerBuilder $container)
  {
    // Create a YAML File Loader
    $projDir = $container->getParameter('kernel.project_dir');
    $env     = $container->getParameter('kernel.environment');
    $paths   = [sprintf('%s/config/packages', $projDir), sprintf('%s/config/packages/%s', $projDir, $env)];
    $locator = new GlobFileLocator($paths);
    $loader  = new YamlFileLoader($container, $locator);

    // Load everything in the paths listed above, including env specific files (prod/dev)
    foreach($locator->glob('pleasing.{yaml,yml}') as $file)
    {
      $loader->load($file);
    }
  }

  /**
   * @param ContainerBuilder $container
   *
   * @return void
   * @throws \Exception
   */
  public function process(ContainerBuilder $container)
  {
    $this->load($container);
    $config = $container->getParameter('pleasing');

    //
    // Asset Subscriber Handling
    //

    // Get List of Classes
    $classes = $this->getAssetSubscriberClasses($container);

    // Add Each Class to Container as Resource so if class changes, container rebuilds in dev mode
    foreach($classes as $class)
    {
      $container->addObjectResource($class);
    }

    // Set the 'always_export' key to TRUE so that these assets are exported
    $subs = $this->getAssetsFromSubscribers($this->getAssetSubscriberClasses($container));
    $outs = array_map(function($v) { return $v['output'] ?? null; }, $config[Config::ASSETS]);
    foreach($subs as $sub)
    {
      if ($key = array_search($sub, $outs))
      {
        $config[Config::ASSETS][$key]['always_export'] = true;
      }
    }

    //
    // Autowire Handling
    //
    if($config[config::AUTO] ?? null)
    {
      $config = (new Autowire($container->getParameter('kernel.debug')))->autowire($config);
    }

    //
    // Service Registration
    //

    $container->register(PleasingVersionStrategy::class, PleasingVersionStrategy::class)
              ->addArgument($config[Config::MANIFEST])
              ->addArgument($config[Config::PUBLIC])
              ->addArgument($config[Config::DEBUG])
    ;
    $container->setAlias('pleasing.version_strategy', PleasingVersionStrategy::class);

    // Add Normalizers
    $normalizers = $this->registerNormalizers($container);

    // Make the config an object to allow for changes to propagate
    $container->register(Config::class, Config::class)
              ->addArgument($config)
              ->addArgument($normalizers)
    ;

    $this->config = new Reference(Config::class);

    // Add Parsers (which parse files for includes)
    $parsers = $this->registerParsers($container);

    // Add Locators
    $locators = $this->registerLocators($container, $parsers);

    // Add Compilers
    $processors = $this->registerProcessors($container, $locators);

    if ($container->getParameter('kernel.debug'))
    {
      $container->setParameter('asset.request_context.base_path', '/pleasing/');
    }
    else
    {
      $container->setParameter('asset.request_context.base_path', '/');
    }

    // The main pleasing class
    $container
        ->register(Pleasing::class, Pleasing::class)
        ->addArgument($this->config)
        ->addMethodCall('setProcessors', [$processors])
        ->setPublic(true)
    ;
    $container->setAlias('pleasing', Pleasing::class);
    $PleasingRef = new Reference(Pleasing::class);

    $container->register('cache_warmer.pleasing', PleasingCacheWarmer::class)
              ->addArgument($PleasingRef)
              ->addArgument(new Reference('twig.loader'))
              ->addArgument('%kernel.project_dir%')
              ->addTag('kernel.cache_warmer')
    ;

    $container->register(PleasingExtension::class, PleasingExtension::class)
              ->setDecoratedService('twig.extension.assets', 'twig.extension.assets.inner')
              ->addArgument($this->config)
              ->addArgument(new Reference('assets.packages'))
              ->addArgument(new Reference('twig.extension.assets.inner'))
              ->setPublic(false)
    ;

    $container->setAlias('twig.extension.pleasing', PleasingExtension::class);
  }

  /**
   * @param ContainerBuilder $container
   *
   * @return Reference[]
   */
  protected function registerNormalizers(ContainerBuilder $container): array
  {
    // Analyzes main layouts for required assets
    $container->register(TwigNormalizer::class, TwigNormalizer::class)
              ->addArgument(new Reference('twig.loader.filesystem'))
              ->addTag(self::TAG_NORMALIZER)
    ;

    $container->register(ErrorNormalizer::class, ErrorNormalizer::class)
              ->addTag(self::TAG_NORMALIZER)
    ;

    return $this->getReferencesForTag($container, self::TAG_NORMALIZER);
  }

  /**
   * Adds AsseticPleasingCompiler service configuration to the container, and returns an array of references for
   * all services tagged with pleasing.compiler.
   *
   * @param ContainerBuilder $container
   * @param Reference[]      $locators
   *
   * @return Reference[]
   */
  protected function registerProcessors(ContainerBuilder $container, $locators): array
  {
    $locators = $locators ?? $this->getReferencesForTag($container, self::TAG_LOCATOR);
    $container->register(SassProcessor::class, SassProcessor::class)
              ->addArgument($this->config)
              ->addArgument(new Reference(SassLocator::class))
              ->addTag(self::TAG_PROCESSOR)
    ;

    $container->register(FontAssetProcessor::class, FontAssetProcessor::class)
              ->addArgument($this->config)
              ->addTag(self::TAG_PROCESSOR)
    ;

    $container->register(ImageAssetProcessor::class, ImageAssetProcessor::class)
              ->addArgument($this->config)
              ->addTag(self::TAG_PROCESSOR)
    ;

    return $this->getReferencesForTag($container, self::TAG_PROCESSOR);
  }

  /**
   * Adds a PleasingSassParser to the container
   * @param ContainerBuilder $container
   *
   * @return Reference[]
   */
  protected function registerParsers(ContainerBuilder $container): array
  {
    $container->register(SassParser::class, SassParser::class)->addTag(self::TAG_PARSER);

    return $this->getReferencesForTag($container, self::TAG_PARSER);
  }

  /**
   * @param ContainerBuilder $container
   * @param Reference[]      $parsers
   *
   * @return Reference[]
   */
  protected function registerLocators(ContainerBuilder $container, ?array $parsers = null): array
  {
    $parsers = $parsers ?? $this->getReferencesForTag($container, self::TAG_PARSER);
    $container->register(SassLocator::class, SassLocator::class)
              ->addArgument($this->config)
              ->addArgument($parsers)
              ->addTag(self::TAG_LOCATOR);

    return $this->getReferencesForTag($container, self::TAG_LOCATOR);
  }

  // endregion ///////////////////////////////////////////// End Container Building

  // region //////////////////////////////////////////////// Helper Methods

  /**
   * Returns an array of asset outputs required in the given classes if they implement AssetSubscriberInterface
   *
   * @param string[] $classes
   *
   * @return string[]
   */
  protected function getAssetsFromSubscribers(array $classes): array
  {
    $result = [];
    foreach($classes as $class)
    {
      if (is_a($class, AssetSubscriberInterface::class, true))
      {
        foreach($class::getSubscribedAssets() as $asset)
        {
          $result[] = (string) $asset;
        }
      }
    }

    return array_keys(array_flip($result));
  }

  /**
   * Get services tagged with 'asset.subscriber' from the container, adds 'setAssets' to their configuration and
   * returns an array of fully qualified class names.
   *
   * @param ContainerBuilder $container
   *
   * @return string[]                     An array of classes from services tagged with 'asset.subscriber'.
   */
  protected function getAssetSubscriberClasses(ContainerBuilder $container): array
  {
    $classes        = [];
    $taggedServices = $container->findTaggedServiceIds(static::TAG_SUBSCRIBER);

    foreach($taggedServices as $id => $service)
    {
      $def = $container->getDefinition($id);
      $cls = class_exists($id) ? $id : $def->getClass();

      if (is_a($cls, AssetSubscriberInterface::class, true))
      {
        // This will call Pleasing::load for each asset in AssetSubscriberInterface::getSubscribedAssets
        if (is_a($cls, AssetLoaderInterface::class, true))
        {
          $def->addMethodCall('loadAssets');
        }
      }

      $classes[] = $cls;
    }

    return array_keys(array_flip($classes));
  }

  /**
   * Returns an array of Reference objects for each service found in the container with the given tag.
   *
   * @param ContainerBuilder $container
   * @param string           $tag
   *
   * @return Reference[]
   */
  protected function getReferencesForTag(ContainerBuilder $container, string $tag): array
  {
    $references     = [];
    $taggedServices = $container->findTaggedServiceIds($tag);
    foreach($taggedServices as $id => $service)
    {
      $references[] = new Reference($id);
    }

    return $references;
  }
}

