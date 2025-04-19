<?php

namespace DevCoding\Pleasing\Config;

use DevCoding\Pleasing\Exception\NotFoundException;
use DevCoding\Pleasing\Filter\FilterCollection;
use Psr\Container\ContainerInterface;

class Config implements ContainerInterface
{
  const PUBLIC   = 'write_to';
  const CACHE    = 'cache_dir';
  const NODE     = 'node_dir';
  const VENDOR   = 'vendor_dir';
  const PROJECT  = 'project_dir';
  const AUTO     = 'autowire';
  const ASSETS   = 'assets';
  const DEBUG    = 'debug';
  const FILTERS  = 'filters';
  const MANIFEST = 'json_manifest_path';
  const RESOLVED = 'inputs_resolved';
  const WIRED    = 'autowire_complete';

  public Directories      $directories;
  public FilterCollection $filters;
  public array            $assets;
  public string           $manifest;
  public bool             $debug;
  protected bool          $resolved;

  /**
   * @param array $config
   */
  public function __construct(array $config)
  {
    $this->directories = new Directories($config);
    $this->filters     = new FilterCollection($this->mapFilters($config[Config::FILTERS] ?? []));
    $this->debug       = $config[Config::DEBUG]  ?? $_SERVER['APP_DEBUG'] ?? false;
    $this->assets      = $config[Config::ASSETS] ?? [];
    $this->manifest    = $config[Config::MANIFEST];
  }

  /**
   * @param string $id
   *
   * @return mixed
   */
  public function get(string $id)
  {
    if($this->has($id))
    {
      return $this->$id;
    }

    throw new NotFoundException(sprintf('The configuration key %s does not exist.', $id));
  }

  /**
   * @param string $id
   *
   * @return bool
   */
  public function has(string $id): bool
  {
    return property_exists($this, $id);
  }

  /**
   * @param array[] $filters
   *
   * @return FilterConfig[]
   */
  private function mapFilters(array $filters): array
  {
    return array_map(function($fc) use ($filters) { return new FilterConfig($fc); }, $filters);
  }
}
