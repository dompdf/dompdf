[![Latest Stable Version](https://poser.pugx.org/dompdf/dompdf/v/stable.png)](https://packagist.org/packages/dompdf/dompdf) [![Total Downloads](https://poser.pugx.org/dompdf/dompdf/downloads.png)](https://packagist.org/packages/dompdf/dompdf) [![Latest Unstable Version](https://poser.pugx.org/dompdf/dompdf/v/unstable.png)](https://packagist.org/packages/dompdf/dompdf) [![License](https://poser.pugx.org/dompdf/dompdf/license.png)](https://packagist.org/packages/dompdf/dompdf)

**dompdf is an HTML to PDF converter**.
At its heart, dompdf is (mostly) [CSS 2.1](http://www.w3.org/TR/CSS2/) compliant
HTML layout and rendering engine written in PHP. It is a style-driven renderer:
it will download and read external stylesheets, inline style tags, and the style
attributes of individual HTML elements. It also supports most presentational
HTML attributes.

----

**Check out the [Demo](http://pxd.me/dompdf/www/examples.php) and ask any
question on [StackOverflow](http://stackoverflow.com/questions/tagged/dompdf) or
on the [Google Groups](http://groups.google.com/group/dompdf)**

----

[![Follow us on Twitter](http://twitter-badges.s3.amazonaws.com/twitter-a.png)](http://www.twitter.com/dompdf)
[![Follow us on Google+](https://ssl.gstatic.com/images/icons/gplus-32.png)](https://plus.google.com/108710008521858993320?prsrc=3)

Features
========
 * handles most CSS 2.1 and a few CSS3 properties, including @import, @media &
   @page rules
 * supports most presentational HTML 4.0 attributes
 * supports external stylesheets, either local or through http/ftp (via
   fopen-wrappers)
 * supports complex tables, including row & column spans, separate & collapsed
   border models, individual cell styling
 * image support (gif, png (8, 24 and 32 bit with alpha channel), bmp & jpeg)
 * no dependencies on external PDF libraries, thanks to the R&OS PDF class
 * inline PHP support
 
Requirements
============
 * PHP 5.0+ (5.3+ recommended)
 * DOM extension
 * GD extension

Recommendations
============
 * MBString extension: provides internationalization support. This extension is
   *not* enabled by default. dompdf has limited internationalization support
   when this extension is not enabled.
 * opcache (OPcache, XCache, APC, etc.): improves performance

About Fonts & Character Encoding
============
PDF documents internally support the following fonts: Helvetica, Times-Roman,
Courier, Zapf-Dingbats, & Symbol. These fonts only support Windows ANSI
encoding. In order for a PDF to display characters that are not available in
Windows ANSI you must supply an external font. dompdf will embed any referenced
font in the PDF so long as it has been pre-loaded or is accessible to dompdf and
reference in CSS @font-face rules. See the
[font overview](https://github.com/dompdf/dompdf/wiki/About-Fonts-and-Character-Encoding)
for more information on how to use fonts.

The [DejaVu TrueType fonts](http://dejavu-fonts.org) have been pre-installed to
give dompdf decent Unicode character coverage by default. To use the DejaVu
fonts reference the font in your stylesheet, e.g. `body { font-family: DejaVu
Sans; }` (for DejaVu Sans).

Easy Installation
============
Install with git
---
From the command line switch to the directory where dompdf will reside and run
the following commands:

```sh
git clone https://github.com/dompdf/dompdf.git .
git submodule init
git submodule update
```

Install with composer
---
To install with Composer, simply add the requirement to your `composer.json`
file:

```json
{
  "require" : {
    "dompdf/dompdf" : "0.6.*"
  }
}
```

And run Composer to update your dependencies:

```bash
$ curl -sS http://getcomposer.org/installer | php
$ php composer.phar update
```
    
Before you can use the Composer installation of DOMPDF in your application you
must disable dompdf's default auto-loader, include the Composer autoloader, and
load the dompdf configuration file:

```php
// somewhere early in your project's loading, require the Composer autoloader
// see: http://getcomposer.org/doc/00-intro.md
require 'vendor/autoload.php';

// disable DOMPDF's internal autoloader if you are using Composer
define('DOMPDF_ENABLE_AUTOLOAD', false);

// include DOMPDF's default configuration
require_once '/path/to/vendor/dompdf/dompdf/dompdf_config.inc.php';
```

Download and install
---
Download an archive of dompdf and extract it into the directory where dompdf
will reside
 * You can download stable copies of dompdf from
   https://github.com/dompdf/dompdf/tags
 * Or download a nightly (the latest, unreleased code) from
   http://eclecticgeek.com/dompdf

Limitations (Known Issues)
==========================
 * not particularly tolerant to poorly-formed HTML input. To avoid any
   unexpected rendering issues you should either enable the built-in HTML5
   parser (via the `DOMPDF_ENABLE_HTML5PARSER` configuration constant) or run
   your HTML through a HTML validator/cleaner (such as Tidy).
 * large files or large tables can take a while to render
 * CSS float is not supported (but is in the works, enable it through the
   `DOMPDF_ENABLE_CSS_FLOAT` configuration constant).
 
If you find this project useful, please consider making a donation.
(Any funds donated will be used to help further development on this project.)	
[![Donate button](https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif)](http://goo.gl/DSvWf)
