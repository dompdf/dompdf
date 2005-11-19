<?php include("head.inc"); ?>

<div id="toc">
<h2>On this page:</h2>
<ul>
<?php echo li_arrow() ?><a href="#overview">Overview</a></li>
<?php echo li_arrow() ?><a href="#features">Features</a></li>
<?php echo li_arrow() ?><a href="#limitations">Limitations</a></li>
<?php echo li_arrow() ?><a href="#hacking">Hacking</a></li>
</ul>
</div>

<a name="overview"> </a>
<h2>Overview</h2>

<p>dompdf is an HTML to PDF converter.  At its heart, dompdf is (mostly)
CSS2.1 compliant HTML layout and rendering engine written in PHP.  It is
a style-driven renderer: it will download and read external stylesheets,
inline style tags, and the style attributes of individual HTML elements.  It
also supports most presentational HTML attributes.</p>

<p>PDF rendering is currently provided either by PDFLib (<a
href="http://www.pdflib.com">www.pdflib.com</a>) or by a bundled
version the R&amp;OS CPDF class written by Wayne Munro (<a
href="http://www.ros.co.nz/pdf/">www.ros.co.nz/pdf</a>).  (Some
performance related changes have been made to the R&amp;OS class,
however).  In order to use PDFLib with dompdf, the PDFLib PECL
extension is required.  Using PDFLib improves performance and reduces
the memory requirements of dompdf somewhat, while the R&amp;OS CPDF class,
though slightly slower, eliminates any dependencies on external PDF
libraries.</p>

<p>dompdf was entered in the <a
href="http://www.zend.com/php5/contest/contest.php">Zend PHP 5
Contest</a> and placed 20th overall.</p>

<p>Please note that dompdf works only with PHP 5.  There are no plans for
a PHP 4 port.  If your web host does not offer PHP 4, I suggest either pestering
them, or setting up your own PHP 5 box and using it to run dompdf.  Your scripts
on your web host can redirect PDF requests to your PHP 5 box.</p>

<a name="features"> </a>
<h2>Features</h2>

<ul>

<li style="list-style-image: url('images/star_01.gif');">handles most
CSS2.1 properties, including @import, @media &amp; @page rules</li>

<li style="list-style-image: url('images/star_02.gif');">supports most
presentational HTML 4.0 attributes</li>

<li style="list-style-image: url('images/star_03.gif');">supports external
stylesheets, either on the local machine or through http/ftp (via
fopen-wrappers)</li>

<li style="list-style-image: url('images/star_04.gif');">supports complex
tables, including row &amp; column spans, separate &amp; collapsed border
models, individual cell styling, multi-page tables (no nested tables yet however)</li>

<li style="list-style-image: url('images/star_05.gif');">image
support (png, gif &amp; jpeg)</li>

<li style="list-style-image: url('images/star_01.gif');">no dependencies on
external PDF libraries, thanks to the R&amp;OS PDF class</li>

<li style="list-style-image: url('images/star_02.gif');">inline PHP
support.  See the section on <a href="usage.php#inline">inline PHP</a> for details.</li>
</ul>


<a name="limitations"> </a>
<h2>Limitations (Known Issues)</h2>

<ul>

<li style="list-style-image: url('images/star_04.gif');">tables can not be
nested</li>

<li style="list-style-image: url('images/star_02.gif');">ordered lists are
currently unsupported.</li>

<li style="list-style-image: url('images/star_03.gif');'">absolute &amp; relative
positioning and floats do not work, yet.</li>

<li style="list-style-image: url('images/star_04.gif');">not particularly
tolerant to poorly-formed HTML or CSS input (using Tidy first may help)</li>

<li style="list-style-image: url('images/star_03.gif');">large files can
take a while to render</li>


</ul>

<a name="hacking"> </a>
<h2>Hacking</h2>

<p>If you are interested in extending or modifying dompdf, please feel free
to contact me (Benj Carson) by email at <a style="white-space: nowrap"
href="mailto:dompdf%40digitaljunkies%2eca">dompdf at digitaljunkies.ca</a>.
Let me know what you'd like to try and I can perhaps point you to the
relevant sections of the source.  If you add some features, or fix
some bugs, please send me a patch and I'll include your changes in the main
distribution.</p>

<?php include("foot.inc"); ?>
