Dompdf
======

[![Build Status](https://travis-ci.org/dompdf/dompdf.png?branch=master)](https://travis-ci.org/dompdf/dompdf)
[![Latest Stable Version](https://poser.pugx.org/dompdf/dompdf/v/stable.png)](https://packagist.org/packages/dompdf/dompdf)
[![Total Downloads](https://poser.pugx.org/dompdf/dompdf/downloads.png)](https://packagist.org/packages/dompdf/dompdf)
[![Latest Unstable Version](https://poser.pugx.org/dompdf/dompdf/v/unstable.png)](https://packagist.org/packages/dompdf/dompdf)
[![License](https://poser.pugx.org/dompdf/dompdf/license.png)](https://packagist.org/packages/dompdf/dompdf)
 
**Dompdf is an HTML to PDF converter**

At its heart, dompdf is (mostly) a [CSS 2.1](http://www.w3.org/TR/CSS2/) compliant
HTML layout and rendering engine written in PHP. It is a style-driven renderer:
it will download and read external stylesheets, inline style tags, and the style
attributes of individual HTML elements. It also supports most presentational
HTML attributes.

*This document applies to the latest stable code which may not reflect the current 
release. For released code please
[navigate to the appropriate tag](https://github.com/dompdf/dompdf/tags).*

----

**Check out the [demo](http://pxd.me/dompdf/www/examples.php) and ask any
question on [StackOverflow](http://stackoverflow.com/questions/tagged/dompdf) or
on the [Google Groups](http://groups.google.com/group/dompdf).**

Follow us on [![Twitter](http://twitter-badges.s3.amazonaws.com/twitter-a.png)](http://www.twitter.com/dompdf) or 
[![Follow us on Google+](https://ssl.gstatic.com/images/icons/gplus-16.png)](https://plus.google.com/108710008521858993320?prsrc=3).

---



## Features

 * Handles most CSS 2.1 and a few CSS3 properties, including @import, @media &
   @page rules
 * Supports most presentational HTML 4.0 attributes
 * Supports external stylesheets, either local or through http/ftp (via
   fopen-wrappers)
 * Supports complex tables, including row & column spans, separate & collapsed
   border models, individual cell styling
 * Image support (gif, png (8, 24 and 32 bit with alpha channel), bmp & jpeg)
 * No dependencies on external PDF libraries, thanks to the R&OS PDF class
 * Inline PHP support
 * Basic SVG support
 
## Requirements

 * PHP version 5.3.0 or higher
 * DOM extension
 * GD extension
 * MBString extension
 * php-font-lib
 * php-svg-lib

### Recommendations

 * OPcache (OPcache, XCache, APC, etc.): improves performance
 * IMagick or GMagick extension: improves image processing performance

Visit the wiki for more information:
https://github.com/dompdf/dompdf/wiki/Requirements

## About Fonts & Character Encoding

PDF documents internally support the following fonts: Helvetica, Times-Roman,
Courier, Zapf-Dingbats, & Symbol. These fonts only support Windows ANSI
encoding. In order for a PDF to display characters that are not available in
Windows ANSI you must supply an external font. Dompdf will embed any referenced
font in the PDF so long as it has been pre-loaded or is accessible to dompdf and
reference in CSS @font-face rules. See the
[font overview](https://github.com/dompdf/dompdf/wiki/About-Fonts-and-Character-Encoding)
for more information on how to use fonts.

The [DejaVu TrueType fonts](http://dejavu-fonts.org) have been pre-installed
to give dompdf decent Unicode character coverage by default. To use the DejaVu
fonts reference the font in your stylesheet, e.g. `body { font-family: DejaVu
Sans; }` (for DejaVu Sans). The following DejaVu 2.34 fonts are available:
DejaVu Sans, DejaVu Serif, and DejaVu Sans Mono.

## Easy Installation

### Install with composer

To install with [Composer](https://getcomposer.org/), simply require the
latest version of this package.

```bash
composer require dompdf/dompdf
```

Make sure that the autoload file from Composer is loaded.

```php
// somewhere early in your project's loading, require the Composer autoloader
// see: http://getcomposer.org/doc/00-intro.md
require 'vendor/autoload.php';

```

### Download and install

Download an archive of dompdf and extract it into the directory where dompdf
will reside
 * You can download stable copies of dompdf from
   https://github.com/dompdf/dompdf/releases
 * Or download a nightly (the latest, unreleased code) from
   http://eclecticgeek.com/dompdf

Require dompdf, libraries, and helper functions in your PHP:

```php
// include autoloader
require_once 'dompdf/autoload.inc.php';
```

### Install with git

From the command line, switch to the directory where dompdf will reside and run
the following commands:

```sh
git clone https://github.com/dompdf/dompdf.git
cd dompdf

git clone https://github.com/PhenX/php-font-lib.git lib/php-font-lib
cd lib/php-font-lib
git checkout 0.4
cd ..

git clone https://github.com/PhenX/php-svg-lib.git php-svg-lib
cd php-svg-lib
git checkout v0.1
```

Require dompdf, libraries, and helper functions in your PHP:

```php
// include autoloader
require_once 'dompdf/autoload.inc.php';
```

## Quick Start

Just pass your HTML in to dompdf and stream the output:

```php
// reference the Dompdf namespace
use Dompdf\Dompdf;

// instantiate and use the dompdf class
$dompdf = new Dompdf();
$dompdf->loadHtml('hello world');

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream();
```

### Setting Options

Set options during dompdf instantiation:

```php
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'Courier');
$dompdf = new Dompdf($options);
```

or at run time

```php
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->set_option('defaultFont', 'Courier');
```

See [Dompdf\Options](src/Options.php) for a list of available options.


## Limitations (Known Issues)

 * Dompdf is not particularly tolerant to poorly-formed HTML input. To avoid
   any unexpected rendering issues you should either enable the built-in HTML5
   parser at runtime (`$dompdf->set_option('isHtml5ParserEnabled', true);`) 
   or run your HTML through a HTML validator/cleaner (such as
   [Tidy](http://tidy.sourceforge.net) or the
   [W3C Markup Validation Service](http://validator.w3.org)).
 * Large files or large tables can take a while to render.
 * CSS float is in development and may not produce the desired result

---

[![Donate button](https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif)](http://goo.gl/DSvWf)

*If you find this project useful, please consider making a donation. Any funds donated will be used to help further development on this project.)*
