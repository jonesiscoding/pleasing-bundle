<?php

namespace DevCoding\Pleasing\Config;

use DevCoding\Html\Element\MetaElement;
use DevCoding\Html\Element\ScriptElement;
use DevCoding\Html\Element\StyleElement;
use DevCoding\Pleasing\Base\AbstractContainer;

class ElementContainer extends AbstractContainer
{
  public function add($element): ElementContainer
  {
    $Attr = $element->getAttributes();
    if ($Attr->has('id'))
    {
      $key = $Attr->getId();
    }
    else
    {
      $tag = strtolower($element->getTag());
      if (in_array($tag, ['html', 'body', 'head', 'foot']))
      {
        $key = $tag;
      }
      elseif ($Attr->has('name'))
      {
        $name = str_replace([' ', '_', '-'], '', strtolower($Attr->getName()));
        $key  = sprintf('%s_%s', $tag, $name);
      }
      else
      {
        $key = sprintf('%s_%s', $tag, $this->getIterator()->count());
      }
    }

    $this->getIterator()->offsetSet($key, $element);

    return $this;
  }

  // region //////////////////////////////////////////////// Filtering Methods

  public function getScript(): ElementContainer
  {
    return $this->filterByTag(['script']);
  }

  public function getStyle(): ElementContainer
  {
    return $this->filterByTag(['style']);
  }

  public function getMeta(): ElementContainer
  {
    return $this->filterByTag(['meta']);
  }

  public function getBody(): ElementContainer
  {
    return $this->filterByTag(['body']);
  }

  /**
   * @param string $parent
   *
   * @return ElementContainer
   */
  public function filterByParent(string $parent): ElementContainer
  {
    $filtered  = [];
    $lowParent = strtolower($parent);

    $copy = $this->getIterator()->getArrayCopy();
    foreach($copy as $key => $element)
    {
      if ($element instanceof ScriptElement)
      {
        if ($lowParent === $element->getParent())
        {
          $filtered[$key] = $element;
        }
      }
      elseif ($element instanceof StyleElement || $element instanceof MetaElement)
      {
        if ('head' === $lowParent)
        {
          $filtered[$key] = $element;
        }
      }
    }

    return $this->with($filtered);
  }

  /**
   * Returns a new AssetCollection containing only the assets with an output file that match the given tags.
   * If the $exclude argument is true, the collection will contain only assets that DO NOT match.
   *
   * @param array|string $tags    The array of tags to check
   * @param bool         $exclude Reverse the filter, returning only assets without the given tags.
   *
   * @return ElementContainer   The new filtered AssetCollection object
   */
  public function filterByTag($tags, bool $exclude = false): ElementContainer
  {
    $tags     = is_array($tags) ? $tags : [$tags];
    $filtered = [];

    $copy = $this->getIterator()->getArrayCopy();
    foreach($copy as $key => $element)
    {
      $lowTag = strtolower($element->getTag());
      if ($exclude && false === in_array($lowTag, $tags))
      {
        $filtered[$key] = $element;
      }
      elseif (!$exclude && in_array($lowTag, $tags))
      {
        $filtered[$key] = $element;
      }
    }

    return $this->with($filtered);
  }

  // endregion ///////////////////////////////////////////// End Filtering Methods
}
