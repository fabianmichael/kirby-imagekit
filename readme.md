<img src="https://fabianmichael.de/shared/imagekit-logo-github.png" alt="Imagekit Logo" width="120" height="120" />

# ImageKit for Kirby CMS

ImageKit provides an asynchronous thumbnail API for [Kirby](http://getkirby.com).

**NOTE:** This is not be a free plugin. In order to use it on a production server, you need to buy a license. For details on ImageKit’s license model, scroll down to the [License](#license) section of this document.

Current version: `1.0.0`

***

## Key Features

- **Image-resizing on demand:** Kirby’s built-in thumbnail engine resizes images on-the-fly while executing the code in your template files. On image-heavy pages, the first page-load can take very long or even exceed the maximum execution time of PHP. ImageKit resizes images only on-demand as soon as they are requested by the client.
- **Security:** A lot of thumbnail libraries for PHP still offer the generation of resized images through URL parameters (e.g. `thumbnail.php?file=ambrosia.jpg&width=500`), which is a potential vector for DoS attacks. ImageKit only generates the thumbnails whose are defined in your page templates.
- **Widget:** Pre-Generate your thumbnails right from the panel with a single click.
- **Discovery Feature:** The widget scans you whole site for new thumbnails, before creating them.
- **Error-Handling:** ImageKit let’s you know, when errors occur during thumbnail creation *(experimental)*.
- **Self-Hosted:** Unlike many other image-resizing-services, ImageKit just sits in Kirby’s plugin directory, so you have everything under control without depending on external providers. No monthly fees. No visitor data is exposed to external companies. tl;dr: No bullshit!

The plugin will be extended by a responsive image component in the future with support for lazy-loading and placeholders (like on <https://fabianmichael.de>). Those features will be released as a separate plugin. You can get a discount, when you buy both plugins as a bundle.

<img src="https://shared.fabianmichael.de/imagekit-widget-v2.gif" alt="ImageKit’s Dashboard Widget" width="460" height="231" />

## Download and Installation

### Requirements

-	PHP 5.4.0+
-	Kirby 2.3.0+
- Tested on Apache 2 with mod_rewrite (but it should also work with other servers like nginx)

### Kirby CLI

If you’re using the [Kirby CLI](https://github.com/getkirby/cli), you need to `cd` to the root directory of your Kirby installation and run the following command:

```
kirby plugin:install fabianmichael/kirby-imagekit
```

This will download and copy *ImageKit* into `site/plugins/imagekit`.

### Git Submodule

To install this plugin as a git submodule, execute the following command from the root of your kirby project:

```
$ git submodule add https://github.com/fabianmichael/kirby-imagekit.git site/plugins/imagekit
```

### Copy and Paste

1. [Download](https://github.com/fabianmichael/kirby-imagekit/archive/master.zip) the contents of this repository as ZIP-file.
2. Rename the extracted folder to `imagekit` and copy it into the `site/plugins/` directory in your Kirby project.

## Usage

Just use it like the built-in thumbnail API of Kirby. You can learn more about Kirby’s image processing capabilities in the [Kirby Docs](https://getkirby.com/docs/templates/thumbnails).

Due to the fact that thumbs created by ImageKit remain *virtual* until the the actual thumb file has been requested by a visitor of your website, some API methods will trigger instant creation of a thumbnail. You should avoid to call methods like `size()`, `base64()` or `modified()` on your thumb, whenever possible, because they only work after the actual thumbnail has been created. However, you can safely use methods like `width()`, `height()` and `ratio()` because dimensions are calculated prior to thumbnail creation.

If you don’t want to let the first visitors of your site need to wait for images to appear, all thumbnails on your site can be generated from ImageKit’s dashboard widget in advance.

## How it works

Rather than doing the expensive task of image conversion on page load (default behavior of Kirby’s built-in thumbs API), thumbnails are stored as a »job« instead as the API is called by your template code. So they will only be generated, when a particular image size is requested by the browser. ImageKit also comes with a widget, so you can trigger creation of all thumbnails right from the panel.

### Discovery mode

If the `imagekit.widget.discover` *(automatic indexing)* option is active, the widget will not only scan your thumbs folder for pending thumbnails, but will also make a HTTP request to every single page of your Kirby installation to execute every page‘s template code once. This feature also works with pagination and/or prev- and next links. Just make sure, that the pagination links have `rel` attributes of either `'next'` or `'prev'`. This way, ImageKit can scan through paginated pages.

```
<link href="<?= $pagination->prevPageURL() ?>" rel="prev">
<link href="<?= $pagination->nextPageURL() ?>" rel="next">
<a href="<?= $pagination->prevPageURL() ?>" rel="prev">Previous page</a>
<a href="<?= $pagination->nextPageURL() ?>" rel="next">Next page</a>
```

This currently works by using PHP’s DOM interface (`DOMDocument`), so if your HTML contains a lot of errors, this might fail. If you are experiencing any trouble with this feature, please report a bug so we can make it works with your project.

## Configuration

| Option              | Default value | Description                                                                                                                                                                                                                                                     |
|:--------------------|:--------------|:----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| imagekit.license    | `'BETA'`      | Enter your license code here, once your site goes live.<br>→ [Buy a license](http://sites.fastspring.com/fabianmichael/product/imagekit)                                                                                                                                                                       |
| imagekit.lazy       | `true`        | Set to `false` to temporary disable asynchronous thumbnail generation. This will restore the default behavior of Kirby.                                                                                                                                         |
| imagekit.complain   | `true`        | If enabled, ImageKit will try to return a placeholder showing an error symbol whenever thumbnail creation fails. If you don’t like this behavior, you can turn this feature off and ImageKit will fail silently.
| imagekit.widget     | `true`        | Enables the dashboard widget.                                                                                                                                                                                                                                   |
| imagekit.widget.step | `5`           | Sets how many pending thumbnails will be generated by the widget in one step. If thumbnail generation exceeds the max execution time on your server, you should set this to a lower value. If your server is blazingly fast, you can safely increase the value. |
| imagekit.widget.discover | `true`   | If enabled, the widget scans your whole site before creating thumbnails. If this feature is not compatible with your setup, disable it. It can also take very long on large site, every single page has to be rendered in order to get all pending thumbnails. In order to do this, the plugin will flush your site cache before running. |

## Troubleshooting

**Thumbnail creation always fails …**
: This can happen because of several reasons. First, make sure that your thumbs folder is writable for Kirby. If you’re using the GD Library driver, make sure that PHP’s memory limit is set to a high-enough value. Increasing the memory limit allows GD to process larger source files. Or if you favor ImageMagick (I do), make sure that the path to the `convert` executable is correctly configured.

**The Discovery Feature does not work with my site:**
: Discovery works by creating a sitemap of your entire site and then sends an HTTP request to every of those URLs to trigger rendering of every single page. When doing so, ImageKit sees everything from a logged-in user’s perspective. It tries it’s best to find pagination on pages, but it cannot create thumbnails whose are – for example – only available on a search results page, where entries are only displayed when a certain keyword was entered into a form.

## License

ImageKit can be evaluated as long as you want on how many private servers you want. To deploy ImageKit on any public server, you need to [buy a license](http://sites.fastspring.com/fabianmichael/product/imagekit). See `license.md` for terms and conditions.

However, even with a valid license code, it is discouraged to use it in any project, that promotes racism, sexism, homophobia, animal abuse or any other form of hate-speech.

## Credits

ImageKit is developed and maintained by [Fabian Michael](https://fabianmichael.de), a graphic designer & web developer from Germany.
