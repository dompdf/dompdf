<?php include("head.inc"); ?>
<a name="FAQ"> </a>
<h2>Frequently Asked Questions</h2>

<ol>
<li><a href="#hello_world">Is there a 'hello world' script for dompdf?</a></li>

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
<!-- ' -->
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
$dompdf->load_html($html);

$dompdf->render();
$dompdf->stream("hello_world.pdf");

?&gt;
</pre>

<p>Put this script in the same directory as
dompdf_config.inc.php.  You'll have to change the paths in
dompdf_config.inc.php to match your installation.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider2" style="background-position: 25% 0%">&nbsp;</div>

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
<div class="divider1" style="background-position: 73% 0%">&nbsp;</div>

<a name="exec_time"> </a>
<h3>I'm getting the following error: <br/> Fatal error:
  Maximum execution time of 30 seconds exceeded in /var/www/dompdf/dompdf.php
  on line XXX</h3>

<p>Nested tables are not supported yet (v0.3.2) and can cause dompdf to enter an
endless loop, thus giving rise to this error.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider2" style="background-position: 49% 0%">&nbsp;</div>

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
<div class="divider2" style="background-position: 49% 0%">&nbsp;</div>

<a name="tables"> </a>
<h3>I have a big table and it's broken!</h3>

<p>This is fixed in versions 0.4 and higher.  Previous versions did not support tables that spanned pages.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider1" style="background-position: 33% 0%">&nbsp;</div>

<a name="footers"> </a>
<h3>Is there a way to add headers and footers?</h3>

<p>Yes, you can add headers and footers using inline PHP.  Have a look
at the demo_01.html file in the www/test/ directory.  It adds a header
and footer using PDF_Adapter->page_text().  It also adds text
superimposed over the rendered text using a PDF 'object'.  This object
is added using CPDF_Adapter->add_object().  See <a
href="usage.php#inline">usage.php</a> for more info on inline PHP.</p>

<a href="#FAQ">[back to top]</a>
<div class="divider2" style="background-position: 49% 0%">&nbsp;</div>

</div>
<? include "foot.inc" ?>