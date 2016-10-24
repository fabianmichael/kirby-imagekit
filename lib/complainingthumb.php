<?php

namespace Kirby\Plugins\ImageKit;

use Response;
use Thumb;
use Str;


/**
 * An extended version of Kirby’s thumb class which is able
 * to throw an error, if thumbs are resized with the
 * GD Library and PHP’s memory limit is exceeded or if
 * thumbnail creation failed for another reason. 
 */
class ComplainingThumb extends Thumb {
  
  private static $_errorData      = [];
  private static $_errorFormat    = 'image';
  
  private static $_errorListening = false;
  private static $_creating       = false;
  private static $_errorReporting;
  private static $_displayErrors;
  private static $_targetDimensions;
  private static $_sendError      = false;

  private static $_reservedMemory;

  public static function setErrorFormat($format = null) {
    if (!is_null($format)) static::$_errorFormat = $format;
    static::$_errorFormat;
  }
  
  public static function enableSendError() {
    static::$_sendError = true;
  }
  
  public function create() {

    if(!static::$_sendError) {
      // Don’t setup a error handlers, if complaining is
      // not enabled and just return the result of create().
      return parent::create();
    }
    
    $this->prepareErrorHandler();
    $result = parent::create();
    $this->restoreErrorHandler();
   
    if(!file_exists($this->destination->root)) {
      
      $message = str::template('Thumbnail creation for "{file}" failed. Please ensure, that the thumbs directory is writable and your driver configuration is correct.', [
        'file'   => static::$_errorData['file'],
      ]);
      
      static::sendErrorResponse($message);
      
      exit;
    }
    
    return $result;
  }
  
  private function prepareErrorHandler() {

    // Reserve one additional megabyte of memory to make
    // catching of out-of-memory errors more reliable.
    // The variable’s content is deleted in the shutdown
    // function to have more available memory to prepare an
    // error.
    static::$_reservedMemory = str_repeat('#', 1024 * 1024);
    
    $dimensions = $this->source->dimensions();
    $filename   = str_replace(kirby()->roots()->index() . DS, '', $this->source->root());
    
    $asset = new ProxyAsset($this->destination->root);
    $asset->options($this->options);
    $asset->original($this->source);
    $targetDimensions = $asset->dimensions();
    
    static::$_errorData = [
      'file'         => $filename,
      'width'        => $dimensions->width,
      'height'       => $dimensions->height,
      'size'         => $this->source->size(),
      'targetWidth'  => $targetDimensions->width,
      'targetHeight' => $targetDimensions->height,
    ];
    
    static::$_displayErrors   = ini_get('display_errors');
    static::$_errorReporting  = error_reporting();
    
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    
    if (!static::$_errorListening) {
      // As of PHP 7.0 there is not way of de-registering a
      // shutdown callback. So we only register it once at
      // the first time, this method is called.
      static::$_errorListening = true;
      register_shutdown_function([__CLASS__, 'shutdownCallback']);
    }
    
    static::$_creating = true;
  }
  
  private function restoreErrorHandler() {

    // Delete the reserved memory, because if thumbnail
    // creation succeeded, it is not needed any more.
    static::$_reservedMemory = null;
    
    ini_set('display_errors', static::$_displayErrors);
    error_reporting(static::$_errorReporting);
    
    static::$_creating = false;
  }
  
  public static function shutdownCallback() {

    // Delete the reserved memory to have more memory for
    // preparing an error message.
    static::$_reservedMemory = null;

    $error = error_get_last();
    if(!$error) return;
    
    if($error['type'] == E_ERROR || $error['type'] === E_WARNING) {
      
      if(str::contains($error['message'], 'Allowed Memory Size')) {
        
        $message = str::template('Thumbnail creation for "{file}" failed, because source image is probably too large ({width} × {height} pixels / {size}) To fix this issue, increase the memory limit of PHP or upload a smaller version of this image.', [
            'file'   => static::$_errorData['file'],
            'width'  => number_format(static::$_errorData['width']),
            'height' => number_format(static::$_errorData['height']),
            'size'   => number_format(static::$_errorData['size'] / (1024 * 1024), 2) . " MB",
          ]);
          
        static::sendErrorResponse($message);
        
      } else {
        static::sendErrorResponse($error['message']);
      }
    }
    
  }
  
  public static function sendErrorResponse($message = '') {
    
    // Make sure, that the error message is non-cachable
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    if(static::$_errorFormat === 'image') {
      
      // The returned error image is an SVG file, because it
      // can be created just by joined a couple of XML tags
      // together and is supported by every modern browser,
      // starting with IE 9 (which is of course not
      // modern any more …).
      header('Content-Type: image/svg+xml');
      
      // Although this is technically not correct, we have
      // to send  status code 200, otherwise the image does
      // not show up in Firefox and Safari, although it
      // works with code 500 in Chrome and Edge. Also tested
      // in IE 10, IE 11
      http_response_code(200);
      
      $width  = static::$_errorData['targetWidth'];
      $height = static::$_errorData['targetHeight'];
      
      // Return an SVG File with the thumb’s dimensions
      // Icon Credit: fontawesome.io
      ?><svg version="1.1" xmlns="http://www.w3.org/2000/svg"
           xmlns:xlink="http://www.w3.org/1999/xlink"
           width="<?= $width ?>" height="<?= $height ?>"
           viewBox="0 0 <?= $width ?> <?= $height ?>"
           preserveAspectRatio="none">
           
        <symbol id="icon">
          <svg viewBox="0 0 48 44.52" width="48" height="44.52" preserveAspectRatio="xMinYMax meet">
            <path d="M27.425,36.789V31.705a0.854,0.854,0,0,0-.254-0.629,0.823,0.823,0,0,0-.6-0.254H21.431a0.823,0.823,0,0,0-.6.254,0.854,0.854,0,0,0-.254.629v5.084a0.854,0.854,0,0,0,.254.629,0.823,0.823,0,0,0,.6.254h5.137a0.823,0.823,0,0,0,.6-0.254A0.854,0.854,0,0,0,27.425,36.789ZM27.371,26.782L27.853,14.5a0.589,0.589,0,0,0-.268-0.508,1.034,1.034,0,0,0-.642-0.294H21.057a1.034,1.034,0,0,0-.642.294,0.64,0.64,0,0,0-.268.562L20.6,26.782a0.514,0.514,0,0,0,.268.441,1.152,1.152,0,0,0,.642.174h4.95a1.088,1.088,0,0,0,.629-0.174A0.6,0.6,0,0,0,27.371,26.782ZM27,1.793L47.545,39.464a3.192,3.192,0,0,1-.054,3.371,3.422,3.422,0,0,1-2.943,1.686H3.452A3.422,3.422,0,0,1,.509,42.835a3.192,3.192,0,0,1-.054-3.371L21,1.793A3.417,3.417,0,0,1,22.261.482a3.381,3.381,0,0,1,3.478,0A3.417,3.417,0,0,1,27,1.793Z" fill="#ffffff"/>
          </svg>
        </symbol>
        
        <rect width="100%" height="100%" fill="#F4999D" />

        <switch>
          <foreignObject width="100%" height="100%" requiredExtensions="http://www.w3.org/1999/xhtml">
            <body xmlns="http://www.w3.org/1999/xhtml" style="background:#F4999D;text-align:center;color:#fff;box-sizing:border-box;margin:0;padding:0;font-size:12px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Open Sans','Helvetica Neue',sans-serif">
              <p style="margin:0;word-wrap:break-word;position:absolute;top:50%;transform:translateY(-50%);width: 100%;box-sizing:border-box;padding: 0 1em;">
                <svg viewBox="0 0 48 44.52" width="24" height="22.125" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: 0 auto 12px;"><path d="M27.425,36.789V31.705a0.854,0.854,0,0,0-.254-0.629,0.823,0.823,0,0,0-.6-0.254H21.431a0.823,0.823,0,0,0-.6.254,0.854,0.854,0,0,0-.254.629v5.084a0.854,0.854,0,0,0,.254.629,0.823,0.823,0,0,0,.6.254h5.137a0.823,0.823,0,0,0,.6-0.254A0.854,0.854,0,0,0,27.425,36.789ZM27.371,26.782L27.853,14.5a0.589,0.589,0,0,0-.268-0.508,1.034,1.034,0,0,0-.642-0.294H21.057a1.034,1.034,0,0,0-.642.294,0.64,0.64,0,0,0-.268.562L20.6,26.782a0.514,0.514,0,0,0,.268.441,1.152,1.152,0,0,0,.642.174h4.95a1.088,1.088,0,0,0,.629-0.174A0.6,0.6,0,0,0,27.371,26.782ZM27,1.793L47.545,39.464a3.192,3.192,0,0,1-.054,3.371,3.422,3.422,0,0,1-2.943,1.686H3.452A3.422,3.422,0,0,1,.509,42.835a3.192,3.192,0,0,1-.054-3.371L21,1.793A3.417,3.417,0,0,1,22.261.482a3.381,3.381,0,0,1,3.478,0A3.417,3.417,0,0,1,27,1.793Z" fill="#ffffff"/></svg>
                <?= $message ?>
              </p>
            </body>
          </foreignObject>
          <!-- Fallback -->
        <use xlink:href="#icon" transform="translate(<?= number_format($width / 2 - 48 / 2,1,'.','') ?>, <?= number_format($height / 2 - 44.52 / 2,1,'.','') ?>)" />
        </switch>
      </svg><?php      

    } else {
      
      // If error format has been set to JSON, return JSON ;-)
      header('Content-Type: application/json; charset=utf-8');
      http_response_code(500);
      
      echo json_encode([
        'status'  => '',
        'code'    => 500,
        'message' => $message,
        'data'    => [
          'file' => static::$_errorData,
        ],
      ]);
      
    }
    
    exit;
  }
  
}
