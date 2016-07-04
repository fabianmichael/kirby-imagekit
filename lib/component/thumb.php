<?php

namespace Kirby\Plugins\ImageKit\Component;

use Asset;
use F;
use Kirby\Component\Thumb as ThumbComponent;
use Kirby\Plugins\ImageKit\LazyThumb;
use Kirby\Plugins\ImageKit\ProxyAsset;


class Thumb extends ThumbComponent {
  
  public function defaults() {
    return array_merge(parent::defaults(), [
      'imagekit.lazy'        => true,
      'imagekit.widget'      => true,
      'imagekit.widget.step' => 5,
      'imagekit.license'     => 'BETA',
    ]);
  }
  
  public function configure() {
    parent::configure();
    
    // Register route to catch non-existing files
    $base = ltrim(substr($this->kirby->roots->thumbs(), strlen($this->kirby->roots->index())), DS);
    
    $this->kirby->set('route', [
      'pattern' => "{$base}/(:all)", // $base = 'thumbs' by default
      'action'  => function ($path) {
        
        // Try to load a jobfile for given thumb url and execute if exists
        $thumb = lazythumb::process($path);
        
        if ($thumb) {
          f::show($thumb->result->root());
        } else {
          // Show a 404 error, if the job could not not be found or executed
          return site()->errorPage();
        }
        
      },
    ]);
  }  
  
  public function create($file, $params) {
    
    if (!$this->kirby->option('imagekit.lazy')) {
      return parent::create($file, $params);
    }
    
    if(!$file->isWebsafe()) return $file;
    
    // Instead of a Thumb, a Job will be created for later exevution
    $thumb = new LazyThumb($file, $params);
    
    if($thumb->result instanceof ProxyAsset) {
      // If the thumb is yet to be generated, use the virtual asset, created by the LazyThumb class
      $asset = $thumb->result;
    } else {
      // Otherwise, create a new asset from the returned media object.
      $asset = new Asset($thumb->result);
    }
    
    // store a reference to the original file
    $asset->original($file);
    
    return $asset;
  }
  
}
