<?php include("head.inc"); ?>
<div id="toc">
<h2>On this page:</h2>
<ul>
<?php echo li_arrow() ?><a href="#usage">Usage</a></li>
<ul><?php echo li_arrow() ?><a href="#web">Invoking via the web</a></li>
    <?php echo li_arrow() ?><a href="#cli">Invoking via the command line</a></li>
    <?php echo li_arrow() ?><a href="#class">Using the dompdf class directly</a></li>
    <?php echo li_arrow() ?><a href="#dompdf_reference">dompdf class reference</a></li>
</ul>
<?php echo li_arrow() ?><a href="#inline">Inline PHP support</a></li>
<ul><?php echo li_arrow() ?><a href="#canvas_reference">canvas class reference</a></li>
    <!-- <?php echo li_arrow() ?><a href="#font_metrics_summary">font_metrics class reference</a></li>-->
</ul>

</ul>
</div>

<a name="usage"> </a>
<h2>Usage</h2>

<p>The dompdf.php script included in the distribution can be used both from
the <a href="#cli">command line</a> or via a <a href="#web">web browser</a>.
Alternatively, the dompdf class can be used <a href="#class">directly</a>.</p>

<a name="web"> </a>
<h3>Invoking dompdf via the web</h3>

<p>The dompdf.php script is not intended to be an interactive page.  It
receives input parameters via $_GET and can stream a PDF directly to the
browser.  This makes it possible to embed links to the script in a page that
look like static PDF links, but are actually dynamically generated.  This
method is also useful as a redirection target.</p>

<p>dompdf.php accepts the following $_GET variables:</p>

<table>
<tr><td class="bar1" colspan="3">&nbsp;</td></tr>
<tr>
<td class="input">input_file</td>  <td>required</td>

<td class="description">a rawurlencoded() path to the HTML file to process.  Remote files
(http/ftp) are supported if fopen wrappers are enabled.</td>
</tr>

<tr>
<td class="input">paper</td>  <td>optional</td>

<td class="description">the paper size.  Defaults to 'letter' (unless the default has been
changed in dompdf_config.inc.php).  See include/pdf_adapter.cls.php, or
invoke dompdf.php on the command line with the -l switch for accepted paper
sizes.</td>
</tr>

<tr>
<td class="input">orientation</td>  <td>optional</td>
<td class="description">'portrait' or 'landscape'.  Defaults to 'portrait'.</td>
</tr>

<tr>
<td class="input">base_path</td>  <td>optional</td>

<td class="description">the base path to use when resolving relative links (images or CSS
files).  Defaults to the directory containing the file being accessed.
(This option is useful for pointing dompdf at your CSS files even though the
HTML file may be elsewhere.)</td>
</tr>

<tr>
<td class="input">output_file</td>  <td>optional</td>

<td class="description">the rawurlencoded() name of the output file. Defaults to 'dompdf_out.pdf'.</td>
</tr>

<tr>
<td class="input">save_file</td>  <td>optional</td>

<td class="description">If present (i.e. <code>isset($_GET["save_file"]) == true');</code>),
output_file is saved locally, Otherwise the file is streamed directly to the client.</td>
</tr>

<tr><td class="bar2" colspan="3">&nbsp;</td></tr>
</table>


<p>One technique for generating dynamic PDFs is to generate dynamic HTML as you
normally would, except instead of displaying the output to the browser, you
use output buffering and write the output to a temporary file.  Once this
file is saved, you redirect to the dompdf.php script.  If you use a
templating engine like Smarty, you can simply do:</p>


<pre>
&lt;?php
$tmpfile = tempnam("/tmp", "dompdf_");
file_put_contents($tmpfile, $smarty->fetch()); // Replace $smarty->fetch()
                                                // with your HTML string

$url = "dompdf.php?input_file=" . rawurlencode($tmpfile) .
       "&amp;paper=letter&amp;output_file=" . rawurlencode("My Fancy PDF.pdf");

header("Location: http://" . $_SERVER["HTTP_HOST"] . "/$url");
?&gt;
</pre>

<p>If you use any stylesheets, you may need to provide the
<code>base_path</code> option to tell dompdf where to look for them, as they
are not likely relative to /tmp ;).</p>


<a name="cli"> </a>
<h3>Invoking dompdf via the command line</h3>

<p>You can execute dompdf.php using the following command:</p>

<pre>$ php -f dompdf.php -- [options]</pre>

<p>(If you find yourself using only the cli interface, you can add
<code>#!/usr/bin/php</code> as the first line of dompdf.php to invoke dompdf.php
directly.)</p>

<p>dompdf.php is invoked as follows:</p>

<table>
<tr><td class="bar1" colspan="2">&nbsp;</td></tr>
<tr>
<td colspan="2" class="input">$ ./dompdf.php [options] html_file</td>
</tr>

<tr>
<td colspan="2"><code>html_file</code> can be a filename, a url if
fopen_wrappers are enabled, or the '-' character to read from standard input.</td>
</tr>

<tr>
<td class="input">-h</td>
<td class="description">Show a brief help message</td>
</tr>

<tr>
<td class="input">-l</td>
<td class="description">list available paper sizes</td>
</tr>

<tr>
<td class="input">-p size</td>
<td class="description">paper size; something like 'letter', 'A4', 'legal', etc. Thee default is 'letter'</td>
</tr>

<tr>
<td class="input">-o orientation</td>
<td class="description">either 'portrait' or 'landscape'.  Default is 'portrait'.</td>
</tr>

<tr>
<td class="input">-b path</td>

<td class="description">the base path to use when resolving relative links
(images or CSS files). Default is the directory of html_file.</td>
</tr>

<tr>
<td class="input">-f file</td>
<td class="description">the output filename.  Default is the input <code>[html_file].pdf</code>.</td>
</tr>

<tr>
<td class="input">-v</td>
<td class="description">verbose: display html parsing warnings and file not found errors.</td>
</tr>

<tr>
<td class="input">-d</td>

<td class="description">very verbose: display oodles of debugging output;
every frame in the tree is printed to stdout.</td>
</tr>
<tr><td class="bar2" colspan="2">&nbsp;</td></tr>

</table>

<p>Examples:</p>

<pre>
$ php -f dompdf.php -- my_resume.html
$ ./dompdf.php -b /var/www/ ./web_stuff/index.html
$ echo '&lt;html&gt;&lt;body&gt;Hello world!&lt;/body&gt;&lt;/html&gt;' | ./dompdf.php -
</pre>


<a name="class"> </a>
<h3>Using the dompdf class directly</h3>

<p>Using the dompdf class directly is fairly straightforward:
<pre>
&lt;?php
require_once("dompdf_config.inc.php");

$html =
  '&lt;html&gt;&lt;body&gt;'.
  '&lt;p&gt;Put your html here, or generate it with your favourite '.
  'templating system.&lt;/p&gt;'.
  '&lt;/body&gt;&lt;/html&gt;';

$dompdf = new DOMPDF();
$dompdf-&gt;load_html($html);
$dompdf-&gt;render();
$dompdf-&gt;stream("sample.pdf");

?&gt;
</pre></p>

<p>Below is a summary of the methods available in the dompdf class.  For complete details,
see the <a href="http://www.digitaljunkies.ca/dompdf/doc/">API
documentation</a> for the class interface definition.</p>

<a name="dompdf_reference"> </a>
<h3>DOMPDF Class Reference</h3>
<ul class="method-summary">
<?php echo li_arrow() ?><span class="method-result">DOMPDF</span> <a href="#method__construct">__construct</a>()</li>
<?php echo li_arrow() ?><span class="method-result">string</span> <a href="#methodget_base_path">get_base_path</a>()</li>
<?php echo li_arrow() ?><span class="method-result">string</span> <a href="#methodget_host">get_host</a>()</li>
<?php echo li_arrow() ?><span class="method-result">string</span> <a href="#methodget_protocol">get_protocol</a>()</li>
<?php echo li_arrow() ?><span class="method-result">Frame_Tree</span> <a href="#methodget_tree">get_tree</a>()</li>
<?php echo li_arrow() ?><span class="method-result">void</span> <a href="#methodload_html">load_html</a>(<span class="var-type">string</span> <span class="var-name">$str</span>)</li>
<?php echo li_arrow() ?><span class="method-result">void</span> <a href="#methodload_html_file">load_html_file</a>(<span class="var-type">string</span> <span class="var-name">$file</span>)</li>
<?php echo li_arrow() ?><span class="method-result">string</span> <a href="#methodoutput">output</a>()</li>
<?php echo li_arrow() ?><span class="method-result">void</span> <a href="#methodrender">render</a>()</li>
<?php echo li_arrow() ?><span class="method-result">void</span> <a href="#methodset_base_path">set_base_path</a>(<span class="var-type">string</span> <span class="var-name">$path</span>)</li>
<?php echo li_arrow() ?><span class="method-result">void</span> <a href="#methodset_host">set_host</a>(<span class="var-type">string</span> <span class="var-name">$host</span>)</li>
<?php echo li_arrow() ?><span class="method-result">void</span> <a href="#methodset_paper">set_paper</a>(<span class="var-type">string</span> <span class="var-name">$size</span>, [<span class="var-type">string</span> <span class="var-name">$orientation</span> = "portrait"])</li>
<?php echo li_arrow() ?><span class="method-result">void</span> <a href="#methodset_protocol">set_protocol</a>(<span class="var-type">string</span> <span class="var-name">$proto</span>)</li>
<?php echo li_arrow() ?><span class="method-result">void</span> <a href="#methodstream">stream</a>(<span class="var-type">string</span> <span class="var-type">$filename</span>, [<span class="var-type">mixed</span> <span class="var-name">$options</span> = null])</li>
</ul>

<div class="method-definition" style="background-position: 10px bottom;">
<a name="method__construct" id="__construct"><!-- --></a>
<div class="method-header">
  <span class="method-title">Constructor __construct</span> (line <span class="line-number">163</span>)
</div>
<p class="short-description">Class constructor</p>
  <div class="method-signature">
    <span class="method-result">DOMPDF</span>
    <span class="method-name">__construct</span>()
  </div>
</div>

<div class="method-definition"  style="background-position: 302px bottom;">
<a name="methodget_base_path" id="get_base_path"><!-- --></a>
<div class="method-header">
  <span class="method-title">get_base_path</span> (line <span class="line-number">227</span>)
</div>
<p class="short-description">Returns the base path</p>
  <div class="method-signature">
    <span class="method-result">string</span>
    <span class="method-name">get_base_path</span>()
  </div>
</div>

<div class="method-definition" style="background-position: 710px bottom;">
<a name="methodget_canvas" id="get_canvas"><!-- --></a>
<div class="method-header">
    <span class="method-title">get_canvas</span> (line <span class="line-number">234</span>)
</div>
<p class="short-description">Return the underlying Canvas instance (e.g. CPDF_Adapter, GD_Adapter)</p>
  <div class="method-signature">
    <span class="method-result">Canvas</span>
    <span class="method-name">get_canvas</span>()
  </div>

</div>

<div class="method-definition"  style="background-position: 252px bottom;">
<a name="methodget_host" id="get_host"><!-- --></a>
  <div class="method-header">
    <span class="method-title">get_host</span> (line <span class="line-number">220</span>)
  </div>
<p class="short-description">Returns the base hostname</p>
  <div class="method-signature">
    <span class="method-result">string</span>
    <span class="method-name">get_host</span>()
  </div>

</div>

<div class="method-definition" style="background-position: 498px bottom;">
<a name="methodget_protocol" id="get_protocol"><!-- --></a>
<div class="method-header">
  <span class="method-title">get_protocol</span> (line <span class="line-number">213</span>)
</div>
<p class="short-description">Returns the protocol in use</p>
  <div class="method-signature">
    <span class="method-result">string</span>
    <span class="method-name">get_protocol</span>()
  </div>

</div>

<div class="method-definition" style="background-position: 39px bottom;">
<a name="methodget_tree" id="get_tree"><!-- --></a>
<div class="method-header">
  <span class="method-title">get_tree</span> (line <span class="line-number">182</span>)
</div>
<p class="short-description">Returns the underlying Frame_Tree object</p>
  <div class="method-signature">
    <span class="method-result">Frame_Tree</span>
    <span class="method-name">get_tree</span>()
  </div>

</div>

<div class="method-definition" style="background-position: 653px bottom;">
<a name="methodload_html" id="load_html"><!-- --></a>
<div class="method-header">
  <span class="method-title">load_html</span> (line <span class="line-number">272</span>)
</div>
<p class="short-description">Loads an HTML string</p>
<p class="description"><p>Parse errors are stored in the global array _dompdf_warnings.</p></p>
  <div class="method-signature">
    <span class="method-result">void</span>
    <span class="method-name">load_html</span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$str</span>)
  </div>

   <ul class="parameters">
   <li>
     <span class="var-type">string</span>
     <span class="var-name">$str</span><span class="var-description">: HTML text to load</span></li>
   </ul>

</div>

<div class="method-definition" style="background-position: 479px bottom;">
<a name="methodload_html_file" id="load_html_file"><!-- --></a>
<div class="method-header">
  <span class="method-title">load_html_file</span> (line <span class="line-number">245</span>)
</div>
<p class="short-description">Loads an HTML file</p>
<p class="description"><p>Parse errors are stored in the global array _dompdf_warnings.</p></p>
  <div class="method-signature">
    <span class="method-result">void</span>
    <span class="method-name">load_html_file</span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$file</span>)
  </div>

  <ul class="parameters">
      <li>
    <span class="var-type">string</span>
    <span class="var-name">$file</span><span class="var-description">: a filename or url to load</span></li>
    </ul>

</div>

<div class="method-definition" style="background-position: 182px bottom;">
<a name="methodoutput" id="output"><!-- --></a>
<div class="method-header">
  <span class="method-title">output</span> (line <span class="line-number">451</span>)
</div>
<p class="short-description">Returns the PDF as a string</p>
  <div class="method-signature">
    <span class="method-result">string</span>
    <span class="method-name">output</span>()
  </div>

</div>

<div class="method-definition" style="background-position: 741px bottom;">

<a name="methodrender" id="render"><!-- --></a>
<div class="method-header">
  <span class="method-title">render</span> (line <span class="line-number">373</span>)
</div>
<p class="short-description">Renders the HTML to PDF</p>

  <div class="method-signature">
    <span class="method-result">void</span>
    <span class="method-name">render</span>()
  </div>
</div>

<div class="method-definition" style="background-position: 824px bottom;">
<a name="methodset_base_path" id="set_base_path"><!-- --></a>
<div class="method-header">
  <span class="method-title">set_base_path</span> (line <span class="line-number">206</span>)
</div>
<p class="short-description">Sets the base path</p>
  <div class="method-signature">
    <span class="method-result">void</span>
    <span class="method-name">set_base_path</span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$path</span>)
  </div>

  <ul class="parameters">
  <li>
    <span class="var-type">string</span>
    <span class="var-name">$path</span></li>
  </ul>

</div>

<div class="method-definition" style="background-position: 519px bottom;">

<a name="methodset_host" id="set_host"><!-- --></a>
<div class="method-header">
  <span class="method-title">set_host</span> (line <span class="line-number">199</span>)
</div>
 <p class="short-description">Sets the base hostname</p>

  <div class="method-signature">
    <span class="method-result">void</span>
    <span class="method-name">set_host</span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$host</span>)
  </div>

  <ul class="parameters">
  <li>
     <span class="var-type">string</span>
     <span class="var-name">$host</span></li>
     </ul>
</div>

<div class="method-definition"  style="background-position: 391px bottom;">
<a name="methodset_paper" id="set_paper"><!-- --></a>
<div class="method-header">
  <span class="method-title">set_paper</span> (line <span class="line-number">353</span>)
</div>
<p class="short-description">Sets the paper size &amp; orientation</p>

  <div class="method-signature">
    <span class="method-result">void</span>
    <span class="method-name">set_paper</span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$size</span>, [<span class="var-type">string</span>&nbsp;<span class="var-name">$orientation</span> = <span class="var-default">"portrait"</span>])
  </div>

  <ul class="parameters">
  <li>
    <span class="var-type">string</span>
    <span class="var-name">$size</span><span class="var-description">: 'letter', 'legal', 'A4', etc. See CPDF_Adapter::$PAPER_SIZES</span></li>
  <li>
    <span class="var-type">string</span>
    <span class="var-name">$orientation</span><span class="var-description">: 'portrait' or 'landscape'</span></li>
  </ul>
</div>

<div class="method-definition" style="background-position: 672px bottom;">
<a name="methodset_protocol" id="set_protocol"><!-- --></a>
<div class="method-header">
  <span class="method-title">set_protocol</span> (line <span class="line-number">192</span>)
</div>
<p class="short-description">Sets the protocol to use (http://, file://, ftp:// etc.)</p>

  <div class="method-signature">
    <span class="method-result">void</span>
    <span class="method-name">set_protocol</span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$proto</span>)
  </div>

  <ul class="parameters">
  <li>
    <span class="var-type">string</span>
    <span class="var-name">$proto</span></li>
  </ul>
</div>

<div class="method-definition" style="background-position: 146px bottom;">
<a name="methodstream" id="stream"><!-- --></a>
<div class="method-header">
  <span class="method-title">stream</span> (line <span class="line-number">441</span>)
</div>
<p class="short-description">Streams the PDF to the client</p>

<p class="description">

<p>The file will always open a download dialog.  The options parameter
controls the output headers.  Accepted headers
are:<br/><br/>

'Accept-Ranges' =&gt; 1 or 0 - if this is not set to 1, then this
header is not included, off by default. This header seems to have
caused some problems despite the fact that it is supposed to solve
them, so I am leaving it off by default.<br/><br/>

'compress' = &gt; 1 or 0 - apply content stream compression, this is
on (1) by default<br/><br/>

'Attachment' =&gt; 1 or 0 - if 1, force the browser to open a download
dialog, on (1) by default</p>

</p>

  <div class="method-signature">
    <span class="method-result">void</span>
    <span class="method-name">stream</span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$filename</span>, [<span class="var-type">array</span>&nbsp;<span class="var-name">$options</span> = <span class="var-default">null</span>])
  </div>

   <ul class="parameters">
       <li>
     <span class="var-type">string</span>
     <span class="var-name">$filename</span><span class="var-description">: the name of the streamed file</span>      </li>
       <li>
     <span class="var-type">array</span>
     <span class="var-name">$options</span><span class="var-description">: header options (see above)</span>      </li>
     </ul>
</div>

<a name="inline"> </a>
<h2>Inline PHP Support</h2>

<p>dompdf supports two varieties of inline PHP code.  All PHP evaluation is
controlled by the <code>DOMPDF_ENABLE_PHP</code> configuration option.  If it is set to
false, then no PHP code is executed.  Otherwise, PHP is evaluated in two
passes:</p>

<p>The first pass is useful for inserting dynamic data into your PDF.  You can
do this by embedding &lt;?php ?&gt; tags in your HTML file, as you would in a
normal .php file.  This code is evaluated prior to parsing the HTML, so you
can echo any text or markup and it will appear in the rendered PDF.</p>

<p>The second pass is useful for performing drawing operations on the
underlying PDF class directly.  You can do this by embedding PHP code within
&lt;script type="text/php"&gt; &lt;/script&gt; tags.  This code is evaluated
during the rendering phase and you have access to a few internal objects and
operations.  In particular, the <code>$pdf</code> variable is the current instance of
Canvas.  Using this object, you can write and draw directly on the
current page.  Using the <code>Canvas::open_object()</code>,
<code>Canvas::close_object()</code> and
<code>Canvas::add_object()</code> methods, you can create text and
drawing objects that appear on every page of your PDF (useful for headers &amp;
footers).</p>

<p>The following variables are defined for you during the second pass of PHP
execution:</p>
<pre>
  $pdf         the current instance of Canvas
  $PAGE_NUM    the current page number
  $PAGE_COUNT  the total number of pages in the document
</pre>

<p>See below for the full Canvas API.</p>

<a name="canvas_reference"> </a>
<h3>Canvas class reference</h3>

<ul class="method-summary">

<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#add_object" title="details" class="method-name">add_object</a> (<span class="var-type">int</span>&nbsp;<span class="var-name">$object</span>, [<span class="var-type">string</span>&nbsp;<span class="var-name">$where</span> = <span class="var-default">'all'</span>])</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#circle" title="details" class="method-name">circle</a>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$r</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>, [<span class="var-type">float</span>&nbsp;<span class="var-name">$width</span> = <span class="var-default">null</span>], [<span class="var-type">array</span>&nbsp;<span class="var-name">$style</span> = <span class="var-default">null</span>], [<span class="var-type">bool</span>&nbsp;<span class="var-name">$fill</span> = <span class="var-default">false</span>])</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#close_object" title="details" class="method-name">close_object</a>()</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#filled_rectangle" title="details" class="method-name">filled_rectangle</a>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$w</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$h</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>)</li>
<?php echo li_arrow(); ?><span class="method-result">float</span> <a href="#get_font_height" title="details" class="method-name">get_font_height</a>(<span class="var-type">string</span>&nbsp;<span class="var-name">$font</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$size</span>)</li>
<?php echo li_arrow(); ?><span class="method-result">float</span> <a href="#get_height" title="details" class="method-name">get_height</a>()</li>
<?php echo li_arrow(); ?><span class="method-result">int</span> <a href="#get_page_count" title="details" class="method-name">get_page_count</a>()</li>
<?php echo li_arrow(); ?><span class="method-result">int</span> <a href="#get_page_number" title="details" class="method-name">get_page_number</a>()</li>
<?php echo li_arrow(); ?><span class="method-result">float</span> <a href="#get_text_width" title="details" class="method-name">get_text_width</a>(<span class="var-type">string</span>&nbsp;<span class="var-name">$text</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$font</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$size</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$spacing</span>)</li>
<?php echo li_arrow(); ?><span class="method-result">float</span> <a href="#get_width" title="details" class="method-name">get_width</a>()</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#image" title="details" class="method-name">image</a>(<span class="var-type">string</span>&nbsp;<span class="var-name">$img_url</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$img_type</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$x</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y</span>, <span class="var-type">int</span>&nbsp;<span class="var-name">$w</span>, <span class="var-type">int</span>&nbsp;<span class="var-name">$h</span>)</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#line" title="details" class="method-name">line</a>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$x2</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y2</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$width</span>, [<span class="var-type">array</span>&nbsp;<span class="var-name">$style</span> = <span class="var-default">null</span>])</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#new_page" title="details" class="method-name">new_page</a>()</li>
<?php echo li_arrow(); ?><span class="method-result">int</span> <a href="#open_object" title="details" class="method-name">open_object</a>()</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#page_text" title="details" class="method-name">page_text</a>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$text</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$font</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$size</span>, [<span class="var-type">array</span>&nbsp;<span class="var-name">$color</span> = <span class="var-default">array(0,0,0)</span>], [<span class="var-type">float</span>&nbsp;<span class="var-name">$adjust</span>], [<span class="var-type">float</span>&nbsp;<span class="var-name">$angle</span>])</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#polygon" title="details" class="method-name">polygon</a>(<span class="var-type">array</span>&nbsp;<span class="var-name">$points</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>, [<span class="var-type">float</span>&nbsp;<span class="var-name">$width</span> = <span class="var-default">null</span>], [<span class="var-type">array</span>&nbsp;<span class="var-name">$style</span> = <span class="var-default">null</span>], [<span class="var-type">bool</span>&nbsp;<span class="var-name">$fill</span> = <span class="var-default">false</span>])</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#rectangle" title="details" class="method-name">rectangle</a>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$w</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$h</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$width</span>, [<span class="var-type">array</span>&nbsp;<span class="var-name">$style</span> = <span class="var-default">null</span>])</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#set_page_count" title="details" class="method-name">set_page_count</a>(<span class="var-type">int</span>&nbsp;<span class="var-name">$count</span>)</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#stop_object" title="details" class="method-name">stop_object</a>(<span class="var-type">int</span>&nbsp;<span class="var-name">$object</span>)</li>
<?php echo li_arrow(); ?><span class="method-result">void</span> <a href="#text" title="details" class="method-name">text</a>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$text</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$font</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$size</span>, [<span class="var-type">array</span>&nbsp;<span class="var-name">$color</span> = <span class="var-default">array(0,0,0)</span>], [<span class="var-type">float</span>&nbsp;<span class="var-name">$adjust</span>])</li>

</ul>

<div class="method-definition" style="background-position: 518px bottom;">
<a name="methodadd_object" id="add_object"><!-- --></a>

	<div class="method-header">
		<span class="method-title">add_object</span>
	</div> 

<p class="short-description">Adds a specified 'object' to the document.</p>

<p class="description">$object int specifying an object created with <a
href="#open_object">Canvas::open_object()</a>.  $where can be one of:</p>

<ul>
  <li>'add' add to current page only</li>
  <li>'all' add to every page from the current one onwards</li>
  <li>'odd' add to all odd numbered pages from now on</li>
  <li>'even' add to all even numbered pages from now on</li>
  <li>'next' add the object to the next page only</li>
  <li>'nextodd' add to all odd numbered pages from the next one</li>
  <li>'nexteven' add to all even numbered pages from the next one</li>
</ul>


<ul class="tags">
  <li><span class="field">see:</span> <a href="doc/Cpdf/Cpdf.html#methodaddObject">Cpdf::addObject()</a></li>
</ul>
	
	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			add_object
		</span>
					(<span class="var-type">int</span>&nbsp;<span class="var-name">$object</span>, [<span class="var-type">string</span>&nbsp;<span class="var-name">$where</span> = <span class="var-default">'all'</span>])
	</div>

    <ul class="parameters">
      <li>
      <span class="var-type">int</span>
      <span class="var-name">$object</span></li>
      <li>
      <span class="var-type">string</span>
      <span class="var-name">$where</span></li>
    </ul>

</div>

<div class="method-definition" style="background-position: 10px bottom;">
<a name="methodcircle" id="circle"><!-- --></a>
<div class="method-header">
  <span class="method-title">circle</span> (line <span class="line-number">166</span>)
</div>

<p class="short-description">Draws a circle at $x,$y with radius $r</p>

<p class="description"><p>See 
<a href="doc/dompdf/Style.html#methodmunge_colour">Style::munge_colour()</a> for
the format of the colour array.  See 
<a href="doc/Cpdf/Cpdf.html#methodsetLineStyle">Cpdf::setLineStyle()</a> for a
description of the $style parameter (aka dash)</p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			circle
		</span>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$r</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>, [<span class="var-type">float</span>&nbsp;<span class="var-name">$width</span> = <span class="var-default">null</span>], [<span class="var-type">array</span>&nbsp;<span class="var-name">$style</span> = <span class="var-default">null</span>], [<span class="var-type">bool</span>&nbsp;<span class="var-name">$fill</span> = <span class="var-default">false</span>])
	</div>

	<ul class="parameters">
			<li>
		<span class="var-type">float</span>
		<span class="var-name">$x</span></li>
			<li>
		<span class="var-type">float</span>
		<span class="var-name">$y</span></li>
			<li>
		<span class="var-type">float</span>
		<span class="var-name">$r</span></li>
			<li>
		<span class="var-type">array</span>
		<span class="var-name">$color</span></li>
			<li>
		<span class="var-type">float</span>
		<span class="var-name">$width</span></li>
			<li>
		<span class="var-type">array</span>
		<span class="var-name">$style</span></li>
			<li>
		<span class="var-type">bool</span>
		<span class="var-name">$fill</span><span class="var-description">: Fills the circle if true</span></li>
	</ul>

</div>

<div class="method-definition" style="background-position: 639px bottom;">
<a name="methodclose_object" id="close_object"><!-- --></a>

	<div class="method-header">
		<span class="method-title">close_object</span>
	</div> 

<p class="short-description">Closes the current 'object'.  All subsequent
drawing operations affect the page.  The closed object can now be added to
multiple pages using <a href="#add_object">add_object()</a>.</p>

	<ul class="tags">
      <li><span class="field">see:</span> <a href="doc/dompdf/CPDF_Adapter.html#methodopen_object">CPDF_Adapter::open_object()</a></li>
	</ul>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">close_object</span>()
	</div>

</div>

<div class="method-definition" style="background-position: 1013px bottom;">
<a name="methodfilled_rectangle" id="filled_rectangle"><!-- --></a>

	<div class="method-header">
		<span class="method-title">filled_rectangle</span> (line <span class="line-number">123</span>)
	</div>

<p class="short-description">Draws a filled rectangle at x1,y1 with width w and height h</p>

<p class="description"><p>See <a
href="doc/dompdf/Style.html#methodmunge_colour">Style::munge_colour()</a> for the
format of the colour array.</p></p>

  <div class="method-signature">
    <span class="method-result">void</span>
    <span class="method-name">
		filled_rectangle
		</span>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$w</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$h</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>)
  </div>

  <ul class="parameters">
    <li>
    <span class="var-type">float</span>
    <span class="var-name">$x1</span></li>
    <li>
    <span class="var-type">float</span>
    <span class="var-name">$y1</span></li>
    <li>
    <span class="var-type">float</span>
    <span class="var-name">$w</span></li>
    <li>
    <span class="var-type">float</span>
    <span class="var-name">$h</span></li>
    <li>
    <span class="var-type">array</span>
    <span class="var-name">$color</span></li>
  </ul>

</div>

<div class="method-definition" style="background-position: 338px bottom;">

<a name="methodget_font_height" id="get_font_height"><!-- --></a>

  <div class="method-header">
    <span class="method-title">get_font_height</span> (line <span class="line-number">216</span>)
   </div>

<p class="short-description">Calculates font height, in points.</p>

  <div class="method-signature">
    <span class="method-result">float</span>
    <span class="method-name">
      get_font_height
    </span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$font</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$size</span>)
  </div>

  <ul class="parameters">
    <li>
    <span class="var-type">string</span>
    <span class="var-name">$font</span></li>
    <li>
    <span class="var-type">float</span>
    <span class="var-name">$size</span></li>
  </ul>
</div>

<div class="method-definition" style="background-position: 751px bottom;">

<a name="methodget_height" id="get_height"><!-- --></a>

	<div class="method-header">
		<span class="method-title">get_height</span>
	</div> 

<p class="short-description">Returns the PDF's height in points.</p>

	<div class="method-signature">
		<span class="method-result">float</span>
		<span class="method-name">get_height</span>()
    </div>
</div>


<div class="method-definition" style="background-position: 647px bottom;">

<a name="methodget_page_count" id="get_page_count"><!-- --></a>

	<div class="method-header">
		<span class="method-title">get_page_count</span> (line <span class="line-number">69</span>)
	</div>

<p class="short-description">Returns the total number of pages.  Note this may
be inaccurate during rendering.  Use <a href="#methodpage_text">page_text()</a>
for headers and footers that use page numbers.</p>

	<div class="method-signature">
		<span class="method-result">int</span>
		<span class="method-name">
			get_page_count
		</span>()
	</div>

</div>

<div class="method-definition" style="background-position: 991px bottom;">

<a name="methodget_page_number" id="get_page_number"><!-- --></a>

	<div class="method-header">
		<span class="method-title">get_page_number</span> (line <span class="line-number">62</span>)
	</div>

<p class="short-description">Returns the current page number.</p>

	<div class="method-signature">
		<span class="method-result">int</span>
		<span class="method-name">
			get_page_number
		</span>()
	</div>

</div>


<div class="method-definition" style="background-position: 33px bottom;">
<a name="methodget_text_width" id="get_text_width"><!-- --></a>
	<div class="method-header">
		<span class="method-title">get_text_width</span> (line <span class="line-number">207</span>)
	</div>

<p class="short-description">Calculates text size, in points.</p>

	<div class="method-signature">
		<span class="method-result">float</span>
		<span class="method-name">
			get_text_width
		</span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$text</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$font</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$size</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$spacing</span>)
	</div>

    <ul class="parameters">
    		<li>
    	<span class="var-type">string</span>
    	<span class="var-name">$text</span><span class="var-description">: the text to be sized</span></li>
    		<li>
    	<span class="var-type">string</span>
    	<span class="var-name">$font</span><span class="var-description">: the desired font</span>, retrieved with <a href="doc/dompdf/Font_Metrics.html#get_font">Font_Metrics::get_font()</a></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$size</span><span class="var-description">: the desired font size</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$spacing</span><span class="var-description">: word spacing, if any</span></li>
    </ul>


</div>

<div class="method-definition" style="background-position: 978px bottom;">

<a name="methodget_width" id="get_width"><!-- --></a>

	<div class="method-header">
		<span class="method-title">get_width</span>
	</div> 

<p class="short-description">Returns the PDF's width in points</p>

	<div class="method-signature">
		<span class="method-result">float</span>
		<span class="method-name">get_width</span>()
	</div>

</div>

<div class="method-definition" style="background-position: 447px bottom;">

<a name="methodimage" id="image"><!-- --></a>

	<div class="method-header">
		<span class="method-title">image</span> (line <span class="line-number">181</span>)
	</div>

<p class="short-description">Add an image to the pdf.</p>

<p class="description">The image is placed at the specified x and y coordinates
with the given width and height.  The CPDF backend supports JPG, GIF and PNG.
The PDFLib backend supports JPG, GIF, PNG, TIFF and BMP images.  For both
backends, PNGs with binary transparency are supported but PNGs with an alpha
channel are not.</p>

<p class="description">Use an absolute path or url if possible when specifying
the image file.  Relative paths are relative to the current working directory of
the webserver.</p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			image
		</span>(<span class="var-type">string</span>&nbsp;<span class="var-name">$img_url</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$img_type</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$x</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y</span>, <span class="var-type">int</span>&nbsp;<span class="var-name">$w</span>, <span class="var-type">int</span>&nbsp;<span class="var-name">$h</span>)
	</div>

    <ul class="parameters">
    		<li>
    	<span class="var-type">string</span>
    	<span class="var-name">$img_url</span><span class="var-description">: the path to the image</span></li>
    		<li>
    	<span class="var-type">string</span>
    	<span class="var-name">$img_type</span><span class="var-description">: the type (e.g. extension) of the image</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$x</span><span class="var-description">: x position</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$y</span><span class="var-description">: y position</span></li>
    		<li>
    	<span class="var-type">int</span>
    	<span class="var-name">$w</span><span class="var-description">: width (in pixels)</span></li>
    		<li>
    	<span class="var-type">int</span>
    	<span class="var-name">$h</span><span class="var-description">: height (in pixels)</span></li>
    </ul>

</div>

<div class="method-definition" style="background-position: 220px bottom;">

<a name="methodline" id="line"><!-- --></a>

	<div class="method-header">
		<span class="method-title">line</span> (line <span class="line-number">93</span>)
	</div>

<p class="short-description">Draws a line from x1,y1 to x2,y2</p>
<p class="description"><p>See <a href="doc/dompdf/Style.html#methodmunge_colour">Style::munge_colour()</a> for the format of the colour array.  See <a href="doc/Cpdf/Cpdf.html#methodsetLineStyle">Cpdf::setLineStyle()</a> for a description of the format of the  $style parameter (aka dash).</p></p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			line
		</span>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$x2</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y2</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$width</span>, [<span class="var-type">array</span>&nbsp;<span class="var-name">$style</span> = <span class="var-default">null</span>])
	</div>

    <ul class="parameters">
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$x1</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$y1</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$x2</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$y2</span></li>
    		<li>
    	<span class="var-type">array</span>
    	<span class="var-name">$color</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$width</span></li>
    		<li>
    	<span class="var-type">array</span>
    	<span class="var-name">$style</span></li>
   	</ul>

</div>

<div class="method-definition" style="background-position: 1333px bottom;">

<a name="methodnew_page" id="new_page"><!-- --></a>

	<div class="method-header">
		<span class="method-title">new_page</span> (line <span class="line-number">224</span>)
	</div>

<p class="short-description">Starts a new page</p>
<p class="description"><p>Subsequent drawing operations will appear on the new page.</p></p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			new_page
		</span>()
	</div>

</div>

<div class="method-definition" style="background-position: 1672px bottom;">

<a name="methodopen_object" id="open_object"><!-- --></a>
	<div class="method-header">
		<span class="method-title">open_object</span>
	</div>

<p class="short-description">Opens a new 'object'.</p>

<p class="description">While an object is open, all drawing actions are recored
in the object, as opposed to being drawn on the current page.  Objects can be
added later to a specific page or to several pages using <a
href="#add_object">add_object()</a>.</p>

<p class="description">The return value is an integer ID for the new object.</p>

	<ul class="tags">
				<li><span class="field">see:</span> <a href="doc/dompdf/CPDF_Adapter.html#methodadd_object">CPDF_Adapter::add_object()</a></li>
				<li><span class="field">see:</span> <a href="doc/dompdf/CPDF_Adapter.html#methodclose_object">CPDF_Adapter::close_object()</a></li>
	</ul>

	<div class="method-signature">
		<span class="method-result">int</span>
		<span class="method-name">open_object</span>()
    </div>
</div>

<div class="method-definition" style="background-position: 876px bottom;">

<a name="methodtext" id="page_text"><!-- --></a>

	<div class="method-header">
		<span class="method-title">page_text</span>
	</div>

<p class="short-description">Writes text at the specified x and y coordinates on every page</p>
<p class="description">The strings '{PAGE_NUM}' and '{PAGE_COUNT}' are
automatically replaced with their current values within $text.</p>

<p>$font can be retrieved with <a href="doc/dompdf/Font_Metrics.html#get_font">Font_Metrics::get_font()</a>.</p>

<p class="description">
See <a href="doc/dompdf/Style.html#methodmunge_colour">Style::munge_colour()</a> for the format of the colour array.</p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			page_text
		</span>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$text</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$font</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$size</span>, [<span class="var-type">array</span>&nbsp;<span class="var-name">$color</span> = <span class="var-default">array(0,0,0)</span>], [<span class="var-type">float</span>&nbsp;<span class="var-name">$adjust</span>])
	</div>

    <ul class="parameters">
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$x</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$y</span></li>
    		<li>
    	<span class="var-type">string</span>
    	<span class="var-name">$text</span><span class="var-description">: the text to write</span></li>
    		<li>
    	<span class="var-type">string</span>
    	<span class="var-name">$font</span><span class="var-description">: the font file to use</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$size</span><span class="var-description">: the font size, in points</span></li>
    		<li>
    	<span class="var-type">array</span>
    	<span class="var-name">$color</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$adjust</span><span class="var-description">: word spacing adjustment</span></li>
    </ul>

</div>

<div class="method-definition" style="background-position: 149px bottom;">

<a name="methodpolygon" id="polygon"><!-- --></a>

	<div class="method-header">
		<span class="method-title">polygon</span> (line <span class="line-number">149</span>)
	</div>

<p class="short-description">Draws a polygon</p>
<p class="description"><p>The polygon is formed by joining all the points stored in the $points  array.  $points has the following structure:</p>
<pre>
array(0 =&gt; x1,
      1 =&gt; y1,
      2 =&gt; x2,
      3 =&gt; y2,
      ...
     );
</pre>

<p>See <a href="doc/dompdf/Style.html#methodmunge_colour">Style::munge_colour()</a> for the format of the colour array.  See <a href="doc/Cpdf/Cpdf.html#methodsetLineStyle">Cpdf::setLineStyle()</a> for a description of the $style  parameter (aka dash)</p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			polygon
		</span>(<span class="var-type">array</span>&nbsp;<span class="var-name">$points</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>, [<span class="var-type">float</span>&nbsp;<span class="var-name">$width</span> = <span class="var-default">null</span>], [<span class="var-type">array</span>&nbsp;<span class="var-name">$style</span> = <span class="var-default">null</span>], [<span class="var-type">bool</span>&nbsp;<span class="var-name">$fill</span> = <span class="var-default">false</span>])
	</div>

    <ul class="parameters">
    		<li>
    	<span class="var-type">array</span>
    	<span class="var-name">$points</span></li>
    		<li>
    	<span class="var-type">array</span>
    	<span class="var-name">$color</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$width</span></li>
    		<li>
    	<span class="var-type">array</span>
    	<span class="var-name">$style</span></li>
    		<li>
    	<span class="var-type">bool</span>
    	<span class="var-name">$fill</span><span class="var-description">: Fills the polygon if true</span></li>
   	</ul>
</div>

<div class="method-definition" style="background-position: 5792px bottom;">

<a name="methodrectangle" id="rectangle"><!-- --></a>

	<div class="method-header">
		<span class="method-title">rectangle</span> (line <span class="line-number">110</span>)
	</div>

<p class="short-description">Draws a rectangle at x1,y1 with width w and height h</p>
<p class="description"><p>See <a href="doc/dompdf/Style.html#methodmunge_colour">Style::munge_colour()</a> for the format of the colour array.  See <a href="doc/Cpdf/Cpdf.html#methodsetLineStyle">Cpdf::setLineStyle()</a> for a description of the $style  parameter (aka dash)</p></p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			rectangle
		</span>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y1</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$w</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$h</span>, <span class="var-type">array</span>&nbsp;<span class="var-name">$color</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$width</span>, [<span class="var-type">array</span>&nbsp;<span class="var-name">$style</span> = <span class="var-default">null</span>])
	</div>

    <ul class="parameters">
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$x1</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$y1</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$w</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$h</span></li>
    		<li>
    	<span class="var-type">array</span>
    	<span class="var-name">$color</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$width</span></li>
    		<li>
    	<span class="var-type">array</span>
    	<span class="var-name">$style</span></li>
  	</ul>

</div>

<div class="method-definition" style="background-position: 713px bottom;">

<a name="methodset_page_count" id="set_page_count"><!-- --></a>

	<div class="method-header">
		<span class="method-title">set_page_count</span> (line <span class="line-number">76</span>)
	</div>

<p class="short-description">Sets the total number of pages</p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			set_page_count
		</span>(<span class="var-type">int</span>&nbsp;<span class="var-name">$count</span>)
	</div>

    <ul class="parameters">
    		<li>
    	<span class="var-type">int</span>
    	<span class="var-name">$count</span></li>
    </ul>

</div>

<div class="method-definition" style="background-position: 495px bottom;">
<a name="methodstop_object" id="stop_object"><!-- --></a>

	<div class="method-header">
		<span class="method-title">stop_object</span>
	</div> 

<p class="short-description">Stops the specified 'object' from appearing in the document.</p>

<p class="description">The object will stop being displayed on the page
following the current one.</p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			stop_object
		</span>
					(<span class="var-type">int</span>&nbsp;<span class="var-name">$object</span>)
    </div>

    <ul class="parameters">
    <li>
    <span class="var-type">int</span>
    <span class="var-name">$object</span></li>
    </ul>

</div>

<div class="method-definition" style="background-position: 1250px bottom;">

<a name="methodtext" id="text"><!-- --></a>

	<div class="method-header">
		<span class="method-title">text</span> (line <span class="line-number">196</span>)
	</div>

<p class="short-description">Writes text at the specified x and y coordinates</p>

<p class="description">$font can retrieved with <a href="doc/dompdf/Font_Metrics.html#get_font">Font_Metrics::get_font()</a>.</p>

<p class="description">See <a href="doc/dompdf/Style.html#methodmunge_colour">Style::munge_colour()</a> for the format of the colour array.</p>

	<div class="method-signature">
		<span class="method-result">void</span>
		<span class="method-name">
			text
		</span>(<span class="var-type">float</span>&nbsp;<span class="var-name">$x</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$y</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$text</span>, <span class="var-type">string</span>&nbsp;<span class="var-name">$font</span>, <span class="var-type">float</span>&nbsp;<span class="var-name">$size</span>, [<span class="var-type">array</span>&nbsp;<span class="var-name">$color</span> = <span class="var-default">array(0,0,0)</span>], [<span class="var-type">float</span>&nbsp;<span class="var-name">$adjust</span>])
	</div>

    <ul class="parameters">
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$x</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$y</span></li>
    		<li>
    	<span class="var-type">string</span>
    	<span class="var-name">$text</span><span class="var-description">: the text to write</span></li>
    		<li>
    	<span class="var-type">string</span>
    	<span class="var-name">$font</span><span class="var-description">: the font file to use</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$size</span><span class="var-description">: the font size, in points</span></li>
    		<li>
    	<span class="var-type">array</span>
    	<span class="var-name">$color</span></li>
    		<li>
    	<span class="var-type">float</span>
    	<span class="var-name">$adjust</span><span class="var-description">: word spacing adjustment</span></li>
    </ul>

</div>

<?php include("foot.inc"); ?>
