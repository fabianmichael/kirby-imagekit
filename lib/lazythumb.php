<?php 

namespace Kirby\Plugins\ImageKit;

use Asset;
use Dir;
use Error;
use Exception;
use F;
use File;
use Media;
use Str;
use Thumb;
// Honestly, who in the PHP team is responsible for naming things?!?
use RecursiveIteratorIterator as Walker;
use RecursiveDirectoryIterator as DirWalker;


/**
 * Extended version of Kirby’s thumb class for
 * creating “lazy” thumbnails.
 */
class LazyThumb extends Thumb {
  
  const JOBFILE_SUFFIX     = '-imagekitjob.php';
  
  public function __construct($source, $params = []) {
    
    $this->source      = $this->result = is_a($source, 'Media') ? $source : new Media($source);
    $this->options     = array_merge(static::$defaults, $this->params($params));
    $this->destination = $this->destination();

    // don't create the thumbnail if it's not necessary
    if($this->isObsolete()) return;

    // don't create the thumbnail if it exists
    if(!$this->isThere()) {

      // try to create the thumb folder if it is not there yet
      dir::make(dirname($this->destination->root));

      // check for a valid image
      if(!$this->source->exists() || $this->source->type() != 'image') {
        throw new Error('The given image is invalid', static::ERROR_INVALID_IMAGE);
      }

      // check for a valid driver
      if(!array_key_exists($this->options['driver'], static::$drivers)) {
        throw new Error('Invalid thumbnail driver', static::ERROR_INVALID_DRIVER);
      }

      // create a jobfile for the thumbnail
      $this->create();

      // create a virtual asset, on which methods like width()
      // and height() can be called on.
      $this->result = new ProxyAsset(new Media($this->destination->root, $this->destination->url));
      $this->result->original($this->source);
      $this->result->options($this->options);
          
    } else {
      
      // create the result object
      $this->result = new Media($this->destination->root, $this->destination->url);
    }
    
    return $this;
  }   
  
  protected function create() { 
    
    $root = static::jobfile($this->destination->root);
    
    if(f::exists($root)) return;  
    
    if(is_a($this->source, 'File')) {
      // Source file belongs to a page
      $pageid = $this->source->page()->id();
      $dir    = null;
    } else {
      // Source file is an outlaw, hiding somewhere else in
      // the file tree
      $pageid = null;
      $dir    = substr($this->source->root(), str::length(kirby()->roots->index));
      $dir    = pathinfo(ltrim($dir , DS), PATHINFO_DIRNAME);
    }

    $options = [
      'imagekit.version' => imagekit()->version(),
      'source'     => [
        'filename' => $this->source->filename(),
        'dir'      => $dir,
        'page'     => $pageid,
      ],
      'options'    => $this->options,
    ];
    
    // Remove `destination` option before export, because
    // closures cannot be exported and this option is a
    // closure by default.
    unset($options['options']['destination']);
    
    $export = "<?php\nreturn " . var_export($options, true) . ';';
    
    f::write($root, $export);
  }
  
  
  // =====  API Methods ========================================================
  
  public static function process($path) {
    
    $thumbs = kirby()->roots->thumbs();
    if(!str::startsWith($path, $thumbs)) {
      $path = $thumbs . DS . $path;
    }
    
    $jobfile = static::jobfile($path);
    
    if(!$thumbinfo = @include($jobfile)) {
      // Abort, if there is no matching jobfile for the
      // requested thumb.
      return false;
    }
    
    // This option is a closure by default, which cannot be
    // restored. Currently, we can only restore it by
    // overriding it the the default option of the thumb
    // class. So this does not work, when a custom 
    // 'destination' option was set for this particular
    // thumbnail or the option has been changed between
    // jobfile creation and execution of this method.
    $thumbinfo['options']['destination'] = thumb::$defaults['destination'];

    if (!is_null($thumbinfo['source']['page'])) {
      // Try to relocate the image and get it’s association
      // with the original parent page or site
      if ($thumbinfo['source']['page'] === '') {
        // Image was uploaded to the "content" directory
        $image = site()->image($thumbinfo['source']['filename']);
      } else {
        // Image belongs to a specific page
        $image = page($thumbinfo['source']['page'])->image($thumbinfo['source']['filename']);
      }
      
      if(!$image) {
        // If source image does not exist any more, remove
        // the jobfile.
        f::remove($jobfile);
        return false;
      }
    } else {
      // If the image does not belong to a specific page,
      // just use an `Asset` as source.
      $image = new Asset($thumbinfo['source']['dir'] . DS . $thumbinfo['source']['filename']);
      
      if(!$image->exists()) {
        f::remove($jobfile);
        return false;
      }
    }
    
    // override url and root of the thumbs directory to the
    // current values. This prevents ImageKit from failing
    // after your Kirby installation has been moved.
    $thumbinfo['options']['ul']   = kirby()->urls->thumbs();
    $thumbinfo['options']['root'] = kirby()->roots->thumbs();
    
    // Finally execute job file by creating a thumb
    $thumb = new ComplainingThumb($image, $thumbinfo['options']);
    
    if(!kirby()->option('imagekit.debug') && f::exists($thumb->destination()->root)) {
      // Delete job file if thumbnail has been generated
      // successfully and we’re not in debug mode.
      f::remove($jobfile);
    }
    
    return $thumb;
  } 

  /**
   * Returns the path of a thumbnails jobfile. The jobfile
   * contains instructions about how to create the actual
   * thumbnail.
   *
   * @return string A thumbnail’s jobfile.
   */
  public static function jobfile($path) {
    return !str::endsWith($path, self::JOBFILE_SUFFIX) ? $path . self::JOBFILE_SUFFIX : $path;
  }
  
  /**
   * Returns all pending thumbnails, i.e. thumbnails that
   * have not been created yet. This works by looking for
   * jobfiles in the thumbs directory.
   *
   * @return array A list of all pending thumbnails
   */
  public static function pending() {
    
    $pending   = [];
    $iterator = new Walker(new DirWalker(kirby()->roots()->thumbs()), Walker::SELF_FIRST);
    
    foreach($iterator as $file) {
      $pathname = $file->getPathname();
      
      if(str::endsWith($pathname, self::JOBFILE_SUFFIX)) {
        $thumb = str::substr($pathname, 0, -str::length(self::JOBFILE_SUFFIX));
        if(!file_exists($thumb)) {
          $pending[] = $pathname;
        }
      }
    }
    
    return $pending;
  }

  /**
   * Walks the thumbs directory, searches for image files
   * and returns a list of those.
   *
   * @return array A list of all websafe image files within
   *               the thumbs directory.
   */
  public static function created() {
    
    $created = []; 
    $iterator = new Walker(new DirWalker(kirby()->roots()->thumbs()), Walker::SELF_FIRST);
    
    foreach($iterator as $file) {
      $pathname = $file->getPathname();
      if(in_array(pathinfo($pathname, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])) {
        $created[] = $pathname;
      }
    }
    
    return $created;
  }
  
  /**
   * Get the actual status of generated and pending thumbs
   * on your site.
   *
   * @return array An associative array containing stats
   *               about your thumbs folder.
   */
  public static function status() {
    return [
      'pending' => sizeof(static::pending()),
      'created' => sizeof(static::created()),
    ];
  }
  
  /**
   * Clears the entire thumbs directory.
   * 
   * @return boolen `true` if cleaning was successful,
   *                otherwise `false`.
   */
  public static function clear() {

    $root  = kirby()->roots()->thumbs();

    // Look for placeholder files used by many projects
    // when working with git. these files are used to add
    // empty directories to repositories. Files will be
    // re-created after zhe cache has been flushed. Although
    // these files are usually empty, it’s more secure to
    // read their contents before deleting them, just in case …
    $indexFile   = $root . DS . 'index.html';
    $index       = f::exists($indexFile) ? f::read($indexFile) : false;
    $gitkeepFile = $root . DS . '.gitkeep';
    $gitkeep     = f::exists($gitkeepFile) ? f::read($gitkeepFile) : false;

    $result = dir::clean($root);

    if($result) {
      // Only re-create if thumbs dir cleanup was successful

      if($index !== false) {
        // Re-create index.html if it existed before
        f::write($indexFile, $index);
      }
      if($gitkeep !== false) {
        // Re-create .gitkeep file, if it existed before
        f::write($gitkeepFile, $gitkeep);
      }
    }

    return $result;
  }
  
}
