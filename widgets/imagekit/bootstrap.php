<?php

namespace Kirby\Plugins\ImageKit\Widget;


load([
  'kirby\\plugins\\imagekit\\widget\\widget'              => 'lib' . DS . 'widget.php',
  'kirby\\plugins\\imagekit\\widget\\translations'        => 'lib' . DS . 'translations.php',
  'kirby\\plugins\\imagekit\\widget\\api'                 => 'lib' . DS . 'api.php',
  'kirby\\plugins\\imagekit\\widget\\apicrawlerresponse'  => 'lib' . DS . 'apicrawlerresponse.php',
], __DIR__);


// Initialize Widget and API
Widget::instance();
API::instance();
