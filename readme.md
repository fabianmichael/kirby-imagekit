# ImageKit for Kirby CMS

ImageKit provides an asynchronous thumbnail API for [Kirby](http://getkirby.com).

**WARNING:** This software is currently available as a beta version. Be careful, if you use it in production, as it may still contain some bugs.

**NOTE:** This is not be a free plugin. In order to use it on a production server, you will need to buy a license once the final version is released. I’m still thinking about how much to charge for a license, but it will be easily affordable for anyone who can afford a Kirby license.

Current version: `1.0.0-beta1`

***

## Key Features

- **Image-resizing on demand:** Kirby’s built-in thumbnail engine resizes images on-the-fly while executing the code in your template files. On image-heavy pages, the first page-load can take very long or even exceed the maximum execution time of PHP. ImageKit resizes images only on-demand as soon as they are requested by the client.
- **Security:** A lot of thumbnail libraries for PHP still offer the generation of resized images through URL parameters (e.g. `thumbnail.php?file=ambrosia.jpg&width=500`), which is a potential vector for DoS attacks. ImageKit only generates the thumbnails whose are defined in your page templates.
- **Self-Hosted:** Unlike many other image-resizing-services, ImageKit just sits in Kirby’s plugin directory, so you have everything under control without depending on external providers. No monthly fees. No visitor data is exposed to external companies. tl;dr: No bullshit!

The plugin will be extended by a responsive image component in the future with support for lazy-loading and placeholders (like on <https://fabianmichael.de>). These features will be released as a separate plugin, but you will only need one license to use ImageKit and the image component.

## Download and Installation

### Requirements

-	PHP 5.4.0+
-	Kirby 2.3.0+
- tested only on Apache 2 with mod_rewrite, but may also works with other server setups

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

## How it works

Rather than doing the expensive task of image conversion on page load (default behavior of Kirby’s built-in thumbs API), thumbnails are stored as a »job« instead as the API is called by your template code. So they will only be generated, when a particular image size is requested by a client.

## Configuration

| Option              | Default value | Description                                                                                                                                                                                                                                                     |
|:--------------------|:--------------|:----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| imagekit.lazy       | `true`        | Set to `false` to temporary disable asynchronous thumbnail generation. This will restore the default behavior of Kirby.                                                                                                                                         |
| imagekit.widget     | `true`        | Enables the dashboard widget.                                                                                                                                                                                                                                   |
| magekit.widget.step | `5`           | Sets how many pending thumbnails will be generated by the widget in one step. If thumbnail generation exceeds the max execution time on your server, you should set this to a lower value. If your server is blazingly fast, you can safely increase the value. |
| imagekit.license    | `'BETA'`      | Enter your license code here, once the final version of the plugin has been released.                                                                                                                                                                          |

## License

ImageKit can be evaluated as long as you want on how many private servers you want. To deploy ImageKit on any public server, you need to buy a license.

Licenses can be obtained, once the first stable version (i.e. 1.0.0) is released.

However, even with a valid license, it is discouraged to use it in any project, that promotes racism, sexism, homophobia, animal abuse or any other form of hate-speech.

## Credits

ImageKit is developed and maintained by [Fabian Michael](https://fabianmichael.de), a graphic designer & web developer from Germany.
