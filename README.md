**dompdf is an HTML to PDF converter**. At its heart, dompdf is (mostly) 
[CSS 2.1](http://www.w3.org/TR/CSS2/) compliant HTML 
layout and rendering engine written in PHP. It is a style-driven renderer: it will 
download and read external stylesheets, inline style tags, and the style attributes 
of individual HTML elements. It also supports most presentational HTML attributes.

----

**Check out the [Demo](http://pxd.me/dompdf/www/examples.php) and ask any question on 
[StackOverflow](http://stackoverflow.com/questions/tagged/dompdf) or on the
[Google Groups](http://groups.google.com/group/dompdf)**

----

[![Follow us on Twitter](http://twitter-badges.s3.amazonaws.com/twitter-a.png)](http://www.twitter.com/dompdf)
[![Follow us on Google+](https://ssl.gstatic.com/images/icons/gplus-32.png)](https://plus.google.com/108710008521858993320?prsrc=3)

Features
========
 * handles most CSS 2.1 and a few CSS3 properties, including @import, @media & @page rules
 * supports most presentational HTML 4.0 attributes
 * supports external stylesheets, either local or through http/ftp (via fopen-wrappers)
 * supports complex tables, including row & column spans, separate & collapsed border models, individual cell styling
 * image support (gif, png (8, 24 and 32 bit with alpha channel), bmp & jpeg)
 * no dependencies on external PDF libraries, thanks to the R&OS PDF class
 * inline PHP support
 
Requirements
============
 * PHP 5.0+ (5.3 recommended)
 * MBString extension
 * DOM extension (bundled with PHP 5)
 * Some fonts. PDFs internally support Helvetica, Times-Roman, Courier & Zapf-Dingbats, but if you wish to use other fonts you will need to install some fonts. dompdf supports the same fonts as the underlying R&OS PDF class: Type 1 (.pfb with the corresponding .afm) and TrueType (.ttf). At the minimum, you should probably have the Microsoft core fonts. See the font installation instructions.

Limitations (Known Issues)
==========================
 * not particularly tolerant to poorly-formed HTML input (using Tidy first may help).
 * large files or large tables can take a while to render
 * CSS float is not supported (but is in the works).
 * If you find this project useful, please consider making a donation.

(Any funds donated will be used to help further development on this project.)	
[![Donate button](https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif)](http://goo.gl/DSvWf)
