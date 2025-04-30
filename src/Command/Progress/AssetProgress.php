<?php

namespace DevCoding\Pleasing\Command\Progress;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class AssetProgress
{
  const FORMAT = ' %current:3s% / %max:3s% [%bar%] %percent:3s%% - %elapsed:7s% - %message%';

  private OutputInterface $Output;
  private ProgressBar     $ProgressBar;

  public function __construct(OutputInterface $output, $max = 0, $message = '')
  {
    ProgressBar::setFormatDefinition(self::class, self::FORMAT);

    $this->Output      = $output;
    $this->ProgressBar = new ProgressBar($this->Output, $max);

    $this->ProgressBar->setFormat(self::class);
    $this->ProgressBar->setMessage($message);
    $this->ProgressBar->display();
  }

  /**
   * Passes through methods that are not extended.
   *
   * @return $this|mixed|null
   */
  public function __call($name, $arguments)
  {
    $result = call_user_func_array([$this->ProgressBar, $name], $arguments);

    if(0 === strpos($name, 'set'))
    {
      // Setter
      return $this;
    }
    elseif (0 !== strpos($name, 'get'))
    {
      // Not a getter
      if(!isset($result) && !is_null($result))
      {
        // No result, not even null
        return $this;
      }
    }

    // For everything else, return the result
    return $result;
  }

  /**
   * Starts the progress output.
   *
   * @param int|null $max Number of steps to complete the bar (0 if indeterminate), null to leave unchanged
   *
   * @return $this
   */
  public function start(?int $max = null): AssetProgress
  {
    $this->ProgressBar->start($max);

    return $this;
  }

  /**
   * Advances the progress output X steps.
   *
   * @param int $step Number of steps to advance
   *
   * @return $this
   */
  public function advance(int $step = 1): AssetProgress
  {
    $this->ProgressBar->advance($step);

    return $this;
  }

  /**
   * Removes the progress bar from the current line.
   *
   * This is useful if you wish to write some output
   * while a progress bar is running.
   * Call display() to show the progress bar again.
   *
   * @return $this
   */
  public function clear(): AssetProgress
  {
    $this->ProgressBar->clear();

    return $this;
  }

  /**
   * Outputs the current progress string.
   *
   * @return $this
   */
  public function display(): AssetProgress
  {
    $this->ProgressBar->display();

    return $this;
  }

  /**
   * Associates a text with a named placeholder.
   *
   * The text is displayed when the progress bar is rendered but only
   * when the corresponding placeholder is part of the custom format line
   * (by wrapping the name with %).
   *
   * @param string $message The text to associate with the placeholder
   * @param string $format
   *
   * @return AssetProgress
   */
  public function setMessage(string $message, string $format = 'info'): AssetProgress
  {
    $this->ProgressBar->setMessage(sprintf('<%s>%s</%s>', $format, $message, $format));

    return $this;
  }

  /**
   * Finishes the progress output, adding an optional message in the given format.
   *
   * @param string|null $message
   * @param string      $format
   *
   * @return $this
   */
  public function finish(?string $message = '', string $format = 'success'): AssetProgress
  {
    if (!empty($message))
    {
      $this->setMessage($message, $format);
    }

    $this->ProgressBar->finish();
    $this->Output->writeln('');

    return $this;
  }
}
