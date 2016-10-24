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

  protected function registerErrorHandler() {
    
    $kirby   = $this->kirby;
    $handler = new CallbackHandler(function($exception, $inspector, $run) use($kirby) {
        die("(t)error!");
        echo response::json([
          'status'  => 'error',
          'code'    => $exception->getCode(),
          'message' => 'this is a message', // $exception->getMessage() . "bla"
        ], 500);
      return Handler::QUIT;
    });
    //error_log('11111111');
    // $this->kirby->errorHandling->whoops
    //   ->unregister()
    //   ->clearHandlers()
    //   ->pushHandler($handler)
    //   ->register();
    print_r($this->kirby->errorHandling->whoops->getHandlers());
    exit;
  }
  
  protected function __construct() {
    $self  = $this;
    
    $this->kirby = kirby();
    
    $this->kirby->set('route', [
      'pattern' => 'plugins/imagekit/widget/api/(:any)',
      'action'  => function($action) use ($self) {
        $this->registerErrorHandler();

        if($error = $this->authorize()) {
          return $error;
        }
        
        if(method_exists($self, $action)) {
          return $this->$action();
        } else {
          throw new APIException('Invalid plugin action. The action "' . html($action) . '" is not defined.');
        }
      },
    ]);
    
    if(isset($_SERVER['HTTP_X_IMAGEKIT_INDEXING'])) {
      $this->handleCrawlerRequest();
    }
  }
  
  protected function authorize() {
    $user = kirby()->site()->user();
    if (!$user || !$user->hasPanelAccess()) {
      throw new APIException('Only logged-in users can use the ImageKit widget. Please reload this page to get show the login form.');
      //return Response::error('', 401);
    }
  }
  
  protected function handleCrawlerRequest() {
    
    if($error = $this->authorize()) {
      return $error;
    }

    if($this->kirby->option('representations.accept')) {
      throw new APIException('ImageKit’s discover mode does currently not work, when the <code>representations.accept</code> setting is turned on. Please disable either this setting or disable <code>imagekit.widget.discover</code>.');
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
