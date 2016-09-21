<?php

namespace Kirby\Plugins\ImageKit\Optimizer;


/**
 * Lossy optimization by using `pngquant` for converting 
 * 24-bit PNGs to an 8-bit palette while preserving the
 * alpha-channel.
 *
 * See: https://pngquant.org/
 */
class PNGQuant extends Base {

  public static $selector    = ['image/png'];
  public $priority           = [100, 5];

  protected $targetFile;
  protected $tmpFile;
  
  public static function defaults() {
    return [
      'imagekit.pngquant.bin'            => null,
      'imagekit.pngquant.quality'        => null,
      'imagekit.pngquant.speed'          => 3,
      'imagekit.pngquant.posterize'      => false,
      'imagekit.pngquant.colors'         => false,
      'imagekit.pngquant.flags'          => '',
    ];
  }

  public static function available() {
    return !empty(static::$kirby->option('imagekit.pngquant.bin'));
  }

  public function pre() {
    $this->targetFile = $this->thumb->destination->root;

    if($this->isDriver('im')) {
      // Instruct imagemagick driver to write out a
      // temporary, uncrompressed PNM file, so our PNG will
      // not need to be encoded twice.
      $this->tmpFile = $this->getTemporaryFilename('pnm');
      $this->thumb->destination->root = $this->tmpFile;
    } else {
      // If driver is anything else but IM, we do not create
      // a temporary file.
      $this->tmpFile = null;
    }
  }


  public function post() {

    $command = [];

    $command[] = static::$kirby->option('imagekit.pngquant.bin');    

    // Quality
    $quality = $this->option('imagekit.pngquant.quality');
    if($quality !== null) {
      $command[] = "--quality $quality";
    }

    // Speed
    if($speed = $this->option('imagekit.pngquant.speed')) {
      $command[] = "--speed $speed";
    }

    // Posterize
    $posterize = $this->option('imagekit.pngquant.posterize');
    if($posterize !== false) { // need verbose check, 
                               // because posterize can have
                               // value of 0
      $command[] = "--posterize $posterize";
    }

    $copy = $this->option('imagekit.pngquant.copy');
    if($copy !== null) {
      $command[] = "--copy $copy";
    }

    if(is_null($this->tmpFile)) {
      // Only save optimized file, if it is smaller than the
      // original. This only makes sense, if input file a
      // PNG image. If input is PNM, the result should
      // always be saved.
      $command[] = '--skip-if-larger';
    }

    $flags = $this->option('imagekit.pngquant.flags');
    if(!empty($flags)) {
      $command[] = $flags;
    }

    // Force pngquant to override original file, if it was used
    // as input (i.e. when no temporary file was created).
    $command[] = '--force --output "' . $this->targetFile . '"';

    // Colors
    $colors = $this->option('imagekit.pngquant.colors');
    if($colors != false) {
      $command[] = $colors;
    }

    // Separator between options and input file as
    // recommended according by the pngquant docs.
    $command[] = '--';

    if(!is_null($this->tmpFile)) {
      // Use tmp file as input.
      $command[] = '"' . $this->tmpFile . '"';
    } else {
      // Use target file as input
      $command[] = '"' . $this->targetFile . '"';
    }

    exec(implode(' ', $command));

    if(!is_null($this->tmpFile)) {
      // Delete temporary file and restore destination path on thumb object.
      @unlink($this->tmpFile);
      $this->thumb->destination->root = $this->targetFile;
    }
  }

}
