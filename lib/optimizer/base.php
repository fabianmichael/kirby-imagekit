<?php

namespace Kirby\Plugins\ImageKit\Optimizer;

use Exception;
use F;
use ReflectionClass;


/**
 * Interface for the optimizer class. An optimizer is
 * created for a specific thumbnail by calling the
 * `create($thumb)` method.
 */
interface BaseInterface {
  /**
   * Should implement at least some basic checks to make
   * sure, that the optimizer is working. This method should
   * check for executables being in place,
   * for PHP extensions, operating system etc.
   * 
   * @return boolean Return `true`, if the optimizer is
   *                 available, otherwise `false`.
   */
  public static function available();
}


/**
 * The base class for ImageKit’s optimizers.
 */
abstract class Base implements BaseInterface {

  /**
   * Defines the file types, this optimizer can handle.
   * 
   * @var string|array Either string with a value of '*' or
   * an array containing a list of mime types.
   */
  public static $selector = '*';

  /**
   * Defines the priority of this optimizer’s operations.
   * @var array An array of two elements, where each can be
   *            either a positive integer, zero or false.
   *            The first value is the priority of
   *            pre-operations, the second one of
   *            post-operations. Priority is not set static
   *            to make synamic changed possible.
   */
  public $priority = [10, 10];

  /**
   * Kirby’s instance.
   * 
   * @var Kirby
   */
  public static $kirby;

  /**
   * Thumbnail will be set by create method.
   * 
   * @var Thumb
   */
  protected $thumb;

  /**
   * Constructor of this optimizer
   * 
   * @param Thumb An instance of the Thumb class.
   */
  protected function __construct($thumb) {
    $this->thumb = $thumb;
  }

  /**
   * Returns an array of all available setting variables of
   * this optimizer.
   * 
   * @return array
   */
  public static function defaults() {
    return [];
  }

  /**
   * Called after defaults have been added to the global
   * Kirby instance. Can be used for further operations
   * that need all options to be in place.
   */
  public static function configure($kirby) {
    static::$kirby = $kirby;
  }

  /**
   * Use this method to create an instance of given
   * optimizer.
   */
  public static function create($thumb) {
    if(!static::matches($thumb)) {
      // Don’t create optimizer for given thumb, if it
      // cannot handle it’s file type.
      return null;
    } else {
      return new static($thumb);
    }
  }

  /**
   * Operations to performed before thumbnail creation. This
   * can be used to modify parameters on the passed $thumb
   * object.
   * 
   * @param Thumb $thumb
   */
  public function pre() {
    // Do some crazy stuff here in a subclass …
  }

  /**
   * Operations to be performed after the thumbnai has been
   * created by the thumbs driver.
   * 
   * @param Thumb $thumb
   */
  public function post() {
    // Do some crazy stuff here in a subclass …
  }

  /**
   * Returns the priority of this optimizer.
   * 
   * @param  string $which Must be either 'pre' or 'post'.
   * @return int|boolean The priority of either 'pre' or
   *                     'post' operations or false, if this
   *                     optimizer does not have a pre/post
   *                     operation defined.
   */
  public function priority($which) {
    switch($which) {
      case 'pre':
        return $this->priority[0];

      case 'post':
        return $this->priority[1];

      default: 
        throw new Exception('`$which` parameter must have a value of either `"pre"` or `"post"`.');
    }
  }

  /**
   * Returns true, checks the extension of a thumb
   * destination file against the mime types, this optimizer
   * can handle. Additional checks are not performed.
   * 
   * @param  Thumb $thumb
   * @return bool `true`, if Optimizer can handle given
   *               thumb, otherwise `false`.
   */
  public static function matches($thumb) {
    $mime = f::extensionToMime(f::extension($thumb->destination->root));
    return in_array($mime, static::$selector);
  }

  /**
   * Returns the name of the optimizer class in lowercase
   * without namespace.
   *
   * @return string The optimizer’s class name without
   *                namespace.
   */
  public static function name() {
    return strtolower(str_replace(__NAMESPACE__ . '\\', '', get_called_class()));
  }


  /* =====  Utility Functions  ============================================== */

  /**
   * Returns a temporary filename, based on the original
   * filename of the thumbnail destination.
   * 
   * @param  string $extension Optionally change the
   *                           extension of the temporary
   *                           file by providing this
   *                           parameter.
   * @return string            The full path to the
   *                           temporary file.
   */
  protected function getTemporaryFilename($extension = null) {
    $parts = pathinfo($this->thumb->destination->root);

    if (!$extension) {
      $extension = $parts['extension'];
    }

    // Add a unique suffix
    $suffix = '-' . uniqid();

    return $parts['dirname'] . DS . $parts['filename'] . $suffix . '.' . $extension;
  }

  /**
   * Compares the filesize of $target and $alternative and
   * only keeps the smallest of both files. If $alternative
   * is smaller than $target, $target will be replaced by
   * $alternative.
   *
   * @param string $target      Full path to target file.
   * @param string $alternative Full path to alternative file.
   * 
   */
  protected function keepSmallestFile($target, $alternative) {
    if(f::size($alternative) <= f::size($target)) {
      f::remove($target);
      f::move($alternative, $target);
    } else {
      f::remove($alternative);
    }
  }

  /**
   * Checks the driver of a given thumb is given driver id.
   *
   * @param  string $driver Name of the thumbnail engine,
   *                        (i.e. 'im' or 'gd').
   * @return boolean
   */
  protected function isDriver($driver) {
    return (
      (isset($this->thumb->options['driver']) && $this->thumb->options['driver'] === $driver) ||
      (static::$kirby->option('imagekit.driver') === $driver)
    );
  }

  /**
   * Tries to get the value of an option from given Thumb
   * object. If not set, returns the global value of this
   * option.
   * 
   * @param  Thumb $thumb An instance of the Thumb class
   *                      from Kirby’s toolkit.
   * @param  string $key  The option key.
   * @return mixed        Either local or global value of
   *                      the option.
   */
  protected function option($key, $default = null) {
    if(isset($this->thumb->options[$key])) {
      return $this->thumb->options[$key];
    } else {
      return static::$kirby->option($key, $default);
    }
  }
  
}
