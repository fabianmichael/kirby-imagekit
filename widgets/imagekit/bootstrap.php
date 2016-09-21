<?php

namespace Kirby\Plugins\ImageKit\Widget;

use Obj;
use Response;
use Tpl;
use Exception;

use Kirby\Plugins\ImageKit\LazyThumb;

load([
  'kirby\\plugins\\imagekit\\widget\\widget'       => 'lib' . DS . 'widget.php',
  'kirby\\plugins\\imagekit\\widget\\translations' => 'lib' . DS . 'translations.php',
  'kirby\\plugins\\imagekit\\widget\\api'          => 'lib' . DS . 'api.php',
], __DIR__);

// Initialize Widget and API

Widget::instance();
API::instance();
