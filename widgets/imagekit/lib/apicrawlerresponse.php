<?php

namespace Kirby\Plugins\ImageKit\Widget;

use Exception;
use DOMDocument;
use Kirby;
use Response;
use Kirby\Plugins\ImageKit\LazyThumb;
use Url;
use V;

class APICrawlerResponse extends \Kirby\Component\Response {

  public function __construct(Kirby $kirby) {
    parent::__construct($kirby);

    // Register listeners for redirects
    header_register_callback([$this,'detectRedirectRequest']);
    register_shutdown_function([$this,'detectRedirectRequest']);
  }

  public function detectRedirectRequest() {
    // Redirects should be ignored by the widget, so
    // override a redirect and just return a valid json
    // response.
    $redirect = in_array(http_response_code(), [301, 302, 303, 304, 307]);
    $sent     = headers_sent();

    if($redirect && !$sent) {
      header_remove('location');
      echo Response::success(true, [
        'links'  => [],
        'status' => LazyThumb::status(),
      ]);
      exit;
    }

  }

  public function make($response) {
    
    // Try to generate response by calling Kirby’s native
    // respionse component.
    $html = parent::make($response);

    if(!class_exists('\DOMDocument')) {
      throw new Exception('The discovery feature of ImageKit needs PHP with the <strong>libxml</strong> extension to run.');
    }

    $links = [];

    try {
      $doc = new DOMDocument();
      libxml_use_internal_errors(true);
      $doc->loadHTML($html);
      libxml_clear_errors();
      
      $elements = array_merge(
        iterator_to_array($doc->getElementsByTagName('a')),
        iterator_to_array($doc->getElementsByTagName('link'))
      );
      
      foreach($elements as $elm) {
        $rel  = $elm->getAttribute('rel');
        if($rel === 'next' || $rel === 'prev') {
          $href = $elm->getAttribute('href');
          if(v::url($href) && url::host($href) === url::host()) {
            // Only add, if href is either a URL on the same
            // domain as the API call was made to, as links
            // could possibly link to sth. like `#page2` or
            // `javascript:;` on AJAX-powered websites.
            $links[] = $href;
          }
        }
      }

    } catch(Exception $e) {
      return Response::error($e->getMessage(), 500);
    }

    return Response::success(true, [
      'links'  => array_unique($links),
      'status' => LazyThumb::status(),
    ]);

  }

}
