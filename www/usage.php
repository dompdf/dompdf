<?php include("head.inc"); ?>
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
changed in dompdf_config.inc.php).  See include/cpdf_adapter.cls.php, or
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

<p>See the <a href="http://www.digitaljunkies.ca/dompdf/doc/">API documentation</a> for
the class interface definition.</p>

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
CPDF_Adapter.  Using this object, you can write and draw directly on the
current page.  Using the <code>CPDF_Adapter::open_object()</code>,
<code>CPDF_Adapter::close_object()</code> and
<code>CPDF_Adapter::add_object()</code> methods, you can create text and
drawing objects that appear on every page of your PDF (useful for headers &amp;
footers).</p>

<p>The following variables are defined for you during the second pass of PHP
execution:</p>
<pre>
  $pdf         the current instance of CPDF_Adapter
  $PAGE_NUM    the current page number
  $PAGE_COUNT  the total number of pages in the document
</pre>

<p>For more complete documentation of the CPDF_Adapter API, see the <a
href="http://www.digitaljunkies.ca/dompdf/doc/">API documentation</a>.</p>

<?php include("foot.inc"); ?>
