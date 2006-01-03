dompdf - PHP5 HTML to PDF converter
===================================

http://www.digitaljunkies.ca/dompdf
Copyright (c) 2004-2005 Benj Carson
R&OS PDF class (class.pdf.php) Copyright (c) 2001-04 Wayne Munro

Send bug reports, patches, feature requests, complaints & hate mail (no spam
thanks) to <benjcarson@digitaljunkies.ca>

##### See INSTALL for installation instructions. #####


Table of Contents:

1. Overview
2. Features
3. Requirements
4. Limitations (Known Issues)
5. Usage
6. Inline PHP Support

Overview
--------

dompdf is an HTML to PDF converter.  At its heart, dompdf is (mostly)
CSS2.1 compliant HTML layout and rendering engine written in PHP.  It is
a style-driven renderer: it will download and read external stylesheets,
inline style tags, and the style attributes of individual HTML elements.  It
also supports most presentational HTML attributes.

PDF rendering is currently provided either by PDFLib (www.pdflib.com)
or by a bundled version the R&amp;OS CPDF class written by Wayne Munro
(www.ros.co.nz/pdf).  (Some performance related changes have been made
to the R&amp;OS class, however).  In order to use PDFLib with dompdf,
the PDFLib PECL extension is required.  Using PDFLib improves
performance and reduces the memory requirements of dompdf somewhat,
while the R&amp;OS CPDF class, though slightly slower, eliminates any
dependencies on external PDF libraries.

dompdf was entered in the Zend PHP 5 Contest and placed 20th overall.

Please note that dompdf works only with PHP 5.  There are no plans for
a PHP 4 port.  If your web host does not offer PHP 4, I suggest either pestering
them, or setting up your own PHP 5 box and using it to run dompdf.  Your scripts
on your web host can redirect PDF requests to your PHP 5 box.

This package should contain:

dompdf.php                 PDF Generating script
dompdf_config.inc.php      Main configuration file
load_font.php              Font loading utility script
HACKING                    Notes on messing with the code
INSTALL                    Installation instructions
LICENSE.LGPL               GNU Lesser General Public License
NEWS                       Release news
README                     This file
TODO                       Things I'm working on
include/                   PHP class & include files
lib/                       R&OS PDF class, fonts, default CSS file
www/                       Demonstration webpage
www/test/                  Some test HTML pages


For the impatient:

Once you have installed dompdf, point your browser at the www/ directory
for HTML documentation and a quick demonstration.


Features
--------

* handles most CSS2.1 properties, including @import, @media & @page rules

* supports most presentational HTML 4.0 attributes

* supports external stylesheets, either local or through http/ftp (via
  fopen-wrappers)

* supports complex tables, including row & column spans, separate &
  collapsed border models, individual cell styling, (no nested tables yet
  however)

* image support (gif, png & jpeg)

* no dependencies on external PDF libraries, thanks to the R&OS PDF class

* inline PHP support.  See below for details.


Requirements
------------

* PHP 5.0.0+

* Some fonts.  PDFs internally support Helvetica, Times-Roman, Courier &
  Zapf-Dingbats, but if you wish to use other fonts you will need to install
  some fonts.  dompdf supports the same fonts as the underlying R&OS PDF
  class: Type 1 (.pfb with the corresponding .afm) and TrueType (.ttf).  At
  the minimum, you should probably have the Microsoft core fonts (now
  available at: http://corefonts.sourceforge.net/).  See the INSTALL file
  for font installation instructions.


Limitations (Known Issues)
--------------------------

* tables can not be nested

* not particularly tolerant to poorly-formed HTML input (using Tidy first
  may help).

* large files can take a while to render

* ordered lists are currently not supported

Usage
-----

The included dompdf.php script can be used both from the command line or via
a web browser.  Alternatively, the dompdf class can be used directly.

Invoking dompdf via the web:

The dompdf.php script is not intended to be an interactive page.  It
receives input parameters via $_GET and can stream a PDF directly to the
browser.  This makes it possible to embed links to the script in a page that
look like static PDF links, but are actually dynamically generated.  This
method is also useful as a redirection target.

dompdf.php accepts the following $_GET variables:

input_file   required    a rawurlencoded() path to the HTML file to
                         process.  Remote files (http/ftp) are supported if
                         fopen wrappers are enabled.

paper        optional    the paper size.  Defaults to 'letter' (unless the
                         default has been changed in dompdf_config.inc.php).
                         See include/cpdf_adapter.cls.php, or invoke
                         dompdf.php on the command line with the -l switch
                         for accepted paper sizes.

orientation  optional    'portrait' or 'landscape'.  Defaults to 'portrait'.

base_path    optional    the base path to use when resolving relative links 
                         (images or CSS files).  Defaults to the directory
                         containing the file being accessed.  (This option is
                         useful for pointing dompdf at your CSS files even
                         though the HTML file may be elsewhere.)

output_file  optional    the rawurlencoded() name of the output file.
                         Defaults to 'dompdf_out.pdf'.

save_file    optional    If present (i.e. isset($_GET["save_file"]) ==
                         true), output_file is saved locally,  Otherwise
                         the file is streamed directly to the client.


One technique for generating dynamic PDFs is to generate dynamic HTML as you
normally would, except instead of displaying the output to the browser, you
use output buffering and write the output to a temporary file.  Once this
file is saved, you redirect to the dompdf.php script.  If you use a
templating engine like Smarty, you can simply do:

<?php
$tmpfile = tempnam("/tmp", "dompdf_");
file_put_contents($tmp_file, $smarty->fetch());

$url = "dompdf.php?input_file=" . rawurlencode($tmpfile) . 
       "&paper=letter&output_file=" . rawurlencode("My Fancy PDF.pdf");

header("Location: http://" . $_SERVER["HTTP_HOST"] . "/$url");
?>

If you use any stylesheets, you may need to provide the base_path option to
tell dompdf where to look for them, as they are not likely relative to 
/tmp ;).


Invoking dompdf via the command line:

You can execute dompdf.php using the following command:

$ php -f dompdf.php -- [options]

(If you find yourself using only the cli interface, you can add
#!/usr/bin/php as the first line of dompdf.php to invoke dompdf.php
directly.)

dompdf.php is invoked as follows:

$ ./dompdf.php [options] html_file
  
  html_file can be a filename, a url if fopen_wrappers are enabled, or the
  '-' character to read from standard input.

  -h             Show a brief help message

  -l             list available paper sizes

  -p size        paper size; something like 'letter', 'A4', 'legal', etc.  
                 Thee default is 'letter'

  -o orientation either 'portrait' or 'landscape'.  Default is 'portrait'.

  -b path        the base path to use when resolving relative links 
                 (images or CSS files). Default is the directory of
                 html_file.

  -f file        the output filename.  Default is the input [html_file].pdf.

  -v             verbose: display html parsing warnings and file not found 
                 errors.

  -d             very verbose: display oodles of debugging output; every 
                 frame in the tree is printed to stdout.

Examples:

$ php -f dompdf.php -- my_resume.html
$ php -f dompdf.php -- -b /var/www/ ./web_stuff/index.html
$ echo '<html><body>Hello world!</body>' | php -f dompdf.php -- -


Using the dompdf class directly:

See the API documentation for the interface definition.  The API
documentation is available at http://www.digitaljunkies.ca/dompdf/.  



Inline PHP Support
------------------

dompdf supports two varieties of inline PHP code.  All PHP evaluation is
controlled by the DOMPDF_ENABLE_PHP configuration option.  If it is set to
false, then no PHP code is executed.  Otherwise, PHP is evaluated in two
passes:

The first pass is useful for inserting dynamic data into your PDF.  You can
do this by embedding <?php ?> tags in your HTML file, as you would in a
normal .php file.  This code is evaluated prior to parsing the HTML, so you
can echo any text or markup and it will appear in the rendered PDF.

The second pass is useful for performing drawing operations on the
underlying PDF class directly.  You can do this by embedding PHP code within
<script type="text/php"> </script> tags.  This code is evaluated during the
rendering phase and you have access to a few internal objects and
operations.  In particular, the $pdf variable is the current instance of
CPDF_Adapter.  Using this object, you can write and draw directly on the
current page.  Using the CPDF_Adapter::open_object(),
CPDF_Adapter::close_object() and CPDF_Adapter::add_object() methods, you can
create text and drawing objects that appear on every page of your PDF
(useful for headers & footers).

The following variables are defined for you during the second pass of PHP
execution:

  $pdf         the current instance of CPDF_Adapter
  $PAGE_NUM    the current page number
  $PAGE_COUNT  the total number of pages in the document

For more complete documentation of the CPDF_Adapter API, see either
include/cpdf_adapter.cls.php and include/canvas.cls.php directly, or check
out the online documentation at http://www.digitaljunkies.ca/dompdf/doc

That's it!  Have fun!


Send questions, problems, bug reports, etc to:

Benj Carson <benjcarson@digitaljunkies.ca>
