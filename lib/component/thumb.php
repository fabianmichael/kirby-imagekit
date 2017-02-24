<?php

namespace Kirby\Plugins\ImageKit\Component;

use Asset;
use F;
use Header;
use Kirby\Component\Thumb as ThumbComponent;
use Kirby\Plugins\ImageKit\LazyThumb;
use Kirby\Plugins\ImageKit\ComplainingThumb;
use Kirby\Plugins\ImageKit\ProxyAsset;
use Kirby\Plugins\ImageKit\Optimizer;


/**
 * Replacement for Kirby’s built-in `thumb` component with
 * asynchronous thumb creation and image optimization
 * capabilities.
 */
class Thumb extends ThumbComponent {
  
  public function defaults() {
    
    return array_merge(parent::defaults(), [
      'imagekit.lazy'               => true,
      'imagekit.complain'           => true,
      'imagekit.widget'             => true,
      'imagekit.widget.step'        => 5,
      'imagekit.widget.discover'    => true,

      'imagekit.optimize'           => false,
      'imagekit.engine'             => null,
      
      'imagekit.license'            => '',

      // Used for the development of the plugin, currently
      // not officialy documented.
      'imagekit.debug'              => false,
    ]);
    
  }
  
  public function configure() {    
    parent::configure();
    
    // Register route to catch non-existing files within the
    // thumbs directory.
    $base = ltrim(substr($this->kirby->roots->thumbs(), strlen($this->kirby->roots->index())), DS);
    
    // Setup optimizer if enabled.
    if($this->kirby->option('imagekit.optimize')) {
      optimizer::register();
    }

    $this->kirby->set('route', [
      'pattern' => "{$base}/(:all)", // $base = 'thumbs' by default
      'action'  => function ($path) {
        
        if($this->kirby->option('imagekit.complain')) {
          complainingthumb::enableSendError();
          complainingthumb::setErrorFormat('image');
        }
        
        // Try to load a jobfile for given thumb url and
        // execute if exists
        $thumb = lazythumb::process($path);
        
        if($thumb) {
          // Serve the image, if everything went fine :-D
          
          $root = $thumb->result->root();

          // Make sure, we’re sending a 200 status, telling
          // the browser that everything’s okay.
          header::status(200);

          // Don’t tell anyone that this image was just
          // created by PHP ;-)
          header_remove('X-Powered-By');

          header('Last-Modified: '  . gmdate('D, d M Y H:i:s', f::modified($root)) . ' GMT');
          header('Content-Type: '   . f::mime($root));
          header('Content-Length: ' . f::size($root));

          // Send file and stop script execution
          readfile($root);
          
          exit;

        } else {
          // Show a 404 error, if the job could not be
          // found or executed
          return site()->errorPage();
        }
        
      },
    ]);
  }  
  
  public function create($file, $params) {
    
    if (!$this->kirby->option('imagekit.lazy') || (isset($params['imagekit.lazy']) && !$params['imagekit.lazy'])) {
      return parent::create($file, $params);
    }
    
    if(!$file->isWebsafe()) return $file;
    
    // Instead of a Thumb, a Job will be created for later
    // execution
    $thumb = new LazyThumb($file, $params);
    
    if($thumb->result instanceof ProxyAsset) {
      // If the thumb is yet to be generated, use the
      // virtual asset, created by the LazyThumb class
      $asset = $thumb->result;
    } else {
      // Otherwise, create a new asset from the returned
      // media object.
      $asset = new Asset($thumb->result);
    }
    
    // store a reference to the original file
    $asset->original($file);
    
    return $asset;
  }
  
}
