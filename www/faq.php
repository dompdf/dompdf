<?php include("head.inc"); ?>
<a name="FAQ"> </a>
<h2>Frequently Asked Questions</h2>

<ol>
<li><a href="#hello_world">Is there a 'hello world' script for dompdf?</a></li>

<li><a href="#save">How do I save a PDF to disk?</a></li>

<li><a href="#dom">I'm getting the following error: <br/>
 Fatal error: DOMPDF_autoload() [function.require]: Failed opening required
 '/var/www/dompdf/include/domdocument.cls.php'
 (include_path='.:') in
 /var/www/dompdf/dompdf_config.inc.php
 on line 146</a></li>

<li><a href="#exec_time">I'm getting the following error: <br/> Fatal error:
  Maximum execution time of 30 seconds exceeded in /var/www/dompdf/dompdf.php
  on line XXX</a></li>

<li><a href="#no_block_parent">I'm getting the following error:<br/>
Fatal error: Uncaught exception 'DOMPDF_Exception' with message 'No
block-level parent found. Not good.' in
C:\Program Files\Apache\htdocs\dompdf\include\inline_positioner.cls.php:68
...
</a></li>

<li><a href="#tables">I have a big table and it's broken!</a></li>

<li><a href="#footers">Is there a way to add headers and footers?</a></li>

<li><a href="#page_break">How do I insert page breaks?</a></li>

<li><a href="#zend_optimizer">I'm getting the following error:<br/>
Cannot access undefined property for object with
overloaded property access in
/var/www/dompdf/include/frame_tree.cls.php on line 160
</a></li>

<li><a href="#new_window">How can I make PDFs open in the browser window instead of
opening the download dialog?</a></li>

<li><a href="#centre">How do I centre a table, paragraph or div?</li>
</ol>

<div class="divider1">&nbsp;</div>
<div class="answers">
<a name="hello_world"> </a>
<h3>Is there a 'hello world' script for dompdf?</h3>

<p>Here's a hello world script:
<pre>
&lt;?php
require_once("dompdf_config.inc.php");
$html =
    '&lt;html&gt;&lt;body&gt;'.
    '&lt;p&gt;Hello World!&lt;/p&gt;'.
    '&lt;/body&gt;&lt;/html&gt;';

$dompdf = new DOMPDF();
$dompdf-&gt;load_html($html);

$dompdf-&gt;render();
$dompdf-&gt;stream("hello_world.pdf");

?&gt;
</pre>

<p>Put this script in the same directory as
dompdf_config.inc.php.  You'll have to change the paths in
dompdf_config.inc.php to match your installation.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider2" style="background-position: 25% 0%">&nbsp;</div>

<a name="save"> </a>
<h3>How do I save a PDF to disk?</h3>

<p>If you are using the included <a href="usage.php#web">dompdf.php</a> script you
can pass it the "save_file" option in conjunction with the "output_file" option.</p>

<p>If you are using the DOMPDF class, you can save the generated PDF
by calling <code>$dompdf-&gt;output()</code>:</p>

<pre>
require_once("dompdf_config.inc.php");
$html = 
    '&lt;html&gt;&lt;body&gt;'.
    '&lt;p&gt;Foo&lt;/p&gt;'.
    '&lt;/body&gt;&lt;/html&gt;';

$dompdf = new DOMPDF();
$dompdf-&gt;load_html($html);

$dompdf-&gt;render();

// The next call will store the entire PDF as a string in $pdf

$pdf = $dompdf-&gt;output();  

// You can now write $pdf to disk, store it in a database or stream it
// to the client.

file_put_contents("saved_pdf.pdf", $pdf);
</pre>

<p>Note that typically <code>dompdf-&gt;stream()</code> and
<code>dompdf-&gt;output()</code> are mutually exclusive.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider1" style="background-position: 721px 0%">&nbsp;</div>


<a name="dom"> </a>
<h3>I'm getting the following error: <br/>
 Fatal error: DOMPDF_autoload() [function.require]: Failed opening required
 '/var/www/dompdf/include/domdocument.cls.php'
 (include_path='.:') in
 /var/www/dompdf/dompdf_config.inc.php
 on line 146</h3>

<p>This error occurs when the version of PHP that you are using does not have
the DOM extension enabled.  You can check which extensions are enabled by
examning the output of <code>phpinfo()</code>.</p>

<p>There are a couple of ways that the DOM extension could have been
disabled.  DOM uses libxml, so if libxml is not present on your server
then the DOM extension will not work.  Alternatively, if PHP was compiled
with the '--disable-dom' switch or the '--disable-xml' switch, DOM support
will also be removed.  You can check which switches were used to compile
PHP with <code>phpinfo()</code>.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider1" style="background-position: 239px 0%">&nbsp;</div>

<a name="exec_time"> </a>
<h3>I'm getting the following error: <br/> Fatal error:
  Maximum execution time of 30 seconds exceeded in /var/www/dompdf/dompdf.php
  on line XXX</h3>

<p>Nested tables are not supported yet (v0.4.3) and can cause dompdf to enter an
endless loop, thus giving rise to this error.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider1" style="background-position: 300px 0%">&nbsp;</div>

<a name="no_block_parent"> </a>
<h3>I'm getting the following error:<br/>
Fatal error: Uncaught exception 'DOMPDF_Exception' with message 'No
block-level parent found. Not good.' in
C:\Program Files\Apache\htdocs\dompdf\include\inline_positioner.cls.php:68
...</h3>

<p>This should be fixed in versions 0.4.1 and up.  The error was
caused by <code>parse_url()</code> thinking that the 'c' in 'c:\' was
a protocol.  Version 0.4.1 works around this issue.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider2" style="background-position: 130px 0%">&nbsp;</div>

<a name="tables"> </a>
<h3>I have a big table and it's broken!</h3>

<p>This is fixed in versions 0.4 and higher.  Previous versions did not support tables that spanned pages.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider1" style="background-position: 812px 0%">&nbsp;</div>

<a name="footers"> </a>
<h3>Is there a way to add headers and footers?</h3>

<p>Yes, you can add headers and footers using inline PHP.  Headers and
footers are added by accessing the PDF renderer directly using inline
PHP embedded in your HTML file.  This is similar to creating PDFs with
FPDF or ezPDF from R&amp;OS, in that you can draw lines, boxes and text
directly on the PDF.  Here are step by step instructions:</p>

<ol>
<li> Somewhere in your html file, near the top, open a script tag with a "text/php" type:
<pre>
  &lt;script type="text/php"&gt;
</pre>
</li>

<li> Check if the $pdf variable is set.  dompdf sets this variable when evaluating embedded PHP.
<pre>
  &lt;script type="text/php"&gt;
 
  if ( isset($pdf) ) {
</pre>
</li>

<li> Pick a font:
<pre>
  &lt;script type="text/php"&gt;
 
  if ( isset($pdf) ) {
  
    $font = Font_Metrics::get_font("verdana", "bold");
</pre>
</li>

<li> Use the CPDF_Adapter::page_text() method to set text that will be
displayed on every page:

<pre>
  &lt;script type="text/php"&gt;
 
  if ( isset($pdf) ) {
  
    $font = Font_Metrics::get_font("verdana", "bold");
    $pdf-&gt;page_text(72, 18, "Fancy Header", $font, 6, array(0,0,0));

  }
  &lt;/script&gt;
</pre>

In this example, the text will be displayed 72pt (1 in) from the left
edge of the page and 18pt (1/4 in) from the top of the page, in 6pt
font.  The last argument to page_text() is the colour which takes an
array of the form array(r,g,b) where each of r, g, and b are between
0.0 and 1.0.  </li>

<li> There are several other methods available.  See the API
documentation for the CPDF_Adapter class (<a
href="doc/">http://www.digitaljunkies.ca/dompdf/doc</a>) for more
details.  Also have a look at the demo_01.html file in the www/test/
directory.  It adds a header and footer using
PDF_Adapter->page_text().  It also adds text superimposed over the
rendered text using a PDF 'object'.  This object is added using
CPDF_Adapter->add_object().  See <a
href="usage.php#inline">usage.php</a> for more info on inline PHP.</li>
</ol>

<a href="#FAQ">[back to top]</a>
<div class="divider2" style="background-position: 12px 0%">&nbsp;</div>

<a name="page_break"> </a>
<h3>How do I insert page breaks?</h3>

<p>Page breaks can be inserted by applying the CSS properties 
<a href="http://www.w3.org/TR/CSS21/page.html#propdef-page-break-before">page-break-before</a>
and 
<a href="http://www.w3.org/TR/CSS21/page.html#propdef-page-break-after">page-break-after</a> to
any block level element.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider1" style="background-position: 44px 0%">&nbsp;</div>

<a name="zend_optimizer"> </a>
<h3>I'm getting the following error:<br/>
Cannot access undefined property for object with
overloaded property access in
/var/www/dompdf/include/frame_tree.cls.php on line 160</h3>

<p>This error is caused by an incompatibility with the Zend Optimizer.
Disable the optimizer when using dompdf.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider1" style="background-position: 991px 0%">&nbsp;</div>

<a name="new_window"> </a>
<h3>How can I make PDFs open in the browser window instead of
opening the download dialog?</h3>

<p>This is controlled by the "Attachment" header sent by dompdf when
it streams the PDF to the client.  You can modify the headers sent by
dompdf by passing additional options to the
<code>$dompdf->stream()</code> function:</p>

<pre>
require_once("dompdf_config.inc.php");
$html = 
    '&lt;html&gt;&lt;body&gt;'.
    '&lt;p&gt;Some text&lt;/p&gt;'.
    '&lt;/body&gt;&lt;/html&gt;';

$dompdf = new DOMPDF();
$dompdf-&gt;load_html($html);

$dompdf-&gt;render();
$domper-&gt;stream("my_pdf.pdf", array("Attachment" =&gt; 0));

</pre>

<p>See the <a href="usage.php#methodstream">class reference</a> for full details.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider2" style="background-position: 237px 0%">&nbsp;</div>

<a name="centre"> </a>
<h3>How do I centre a table, paragraph or div?</h3>

<p>You can centre any block level element (table, p, div, ul, etc.) by
using margins:</p>

<pre>
&lt;table style="margin-left: auto; margin-right: auto"&gt;
&lt;tr&gt;
&lt;td&gt; ... &lt;/td&gt;
&lt;/tr&gt;
&lt;/table&gt;
</pre>

<a href="#FAQ">[back to top]</a>
<div class="divider1" style="background-position: 884px 0%">&nbsp;</div>

</div> <?php include "foot.inc" ?>
