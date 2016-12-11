<?php

namespace Kirby\Plugins\ImageKit\Widget;

use Response;
use Exception;

use Kirby\Plugins\ImageKit\LazyThumb;
use Kirby\Plugins\ImageKit\ComplainingThumb;

use Whoops\Handler\Handler;
use Whoops\Handler\CallbackHandler;


class API {
  
  public $kirby;
  
  public static function instance() {
    static $instance;
    return $instance ?: $instance = new static();
  }

  protected function __construct() {
    $self  = $this;
    
    $this->kirby = kirby();
    
    $this->kirby->set('route', [
      'pattern' => 'plugins/imagekit/widget/api/(:any)',
      'action'  => function($action) use ($self) {
        if($error = $this->authorize()) {
          return $error;
        }
        
        if(method_exists($self, $action)) {
          return $this->$action();
        } else {
          throw new Exception('Invalid plugin action. The action "' . html($action) . '" is not defined.');
        }
      },
    ]);
    
    if(isset($_SERVER['HTTP_X_IMAGEKIT_INDEXING'])) {
      // Handle indexing request (discovery feature).
      $this->handleCrawlerRequest();
    }
  }
  
  protected function authorize() {
    $user = kirby()->site()->user();
    if (!$user || !$user->hasPanelAccess()) {
      throw new Exception('Only logged-in users can use the ImageKit widget. Please reload this page to go to the login form.');
    }
  }
  
  protected function handleCrawlerRequest() {
    
    if($error = $this->authorize()) {
      return $error;
    }

    if($this->kirby->option('representations.accept')) {
      throw new Exception('ImageKit’s discover mode does currently not work, when the <code>representations.accept</code> setting is turned on. Please disable either this setting or disable <code>imagekit.widget.discover</code>.');
    } 

    kirby()->set('component', 'response', '\\kirby\\plugins\\imagekit\\widget\\apicrawlerresponse');
  }
  
  public function status() {
    return Response::success(true, lazythumb::status());
  }
  
  public function clear() {
    return Response::success(lazythumb::clear(), lazythumb::status());
  }
  
  public function create() {
    
    $pending = lazythumb::pending();
    $step    = kirby()->option('imagekit.widget.step');
    
    // Always complain when trying to create thumbs from the widget
    complainingthumb::enableSendError();
    complainingthumb::setErrorFormat('json');
    
    for($i = 0; $i < sizeof($pending) && $i < $step; $i++) {
      lazythumb::process($pending[$i]);
    }
    
    return Response::success(true, lazythumb::status());
  }
  
  public function index() {
    
    $index = [];
    
    $this->kirby->cache()->flush();
    
    $site        = site();
    $isMultilang = $site->multilang() && $site->languages()->count() > 1;
    
    if($isMultilang) {
      $languageCodes = [];
      foreach($site->languages() as $language) {
        $languageCodes[] = $language->code();
      }
    }
    
    foreach($site->index() as $page) {
      if($isMultilang) {
        foreach($languageCodes as $code) {
          $index[] = $page->url($code);
        }
      } else {
        $index[] = $page->url();
      }
    }
    
    return Response::success(true, $index);
  }
}
