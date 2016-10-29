<?php

namespace Kirby\Plugins\ImageKit\Widget;

use Exception;
use DOMDocument;
use Response;
use Kirby\Plugins\ImageKit\LazyThumb;


class APICrawlerResponse extends \Kirby\Component\Response {

  public function make($response) {
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
        $rel = $elm->getAttribute('rel');
        if($rel === 'next' || $rel === 'prev') {
          $links[] = $elm->getAttribute('href');
        }
      }

    } catch(Exception $e) {
      return Response::error($e->getMessage(), 500);
    }
    //return response::error('There was an error parsing Bla', 500);
    return Response::success(true, [
      'links'  => array_unique($links),
      'status' => LazyThumb::status(),
    ]);
  }
}
