<?php include("head.inc"); ?>
<a name="overview"> </a>
<h2>Overview</h2>

<p>dompdf is an HTML to PDF converter.  At its heart, dompdf is (mostly)
CSS2.1 compliant HTML layout and rendering engine written in PHP.  It is
a style-driven renderer: it will download and read external stylesheets,
inline style tags, and the style attributes of individual HTML elements.  It
also supports most presentational HTML attributes.</p>

<p>PDF rendering is provided using a modified version the R&amp;OS PDF class
written by Wayne Munro, <a
href="http://www.ros.co.nz/pdf/">http://www.ros.co.nz/pdf/</a>.  (Some
performance related changes have been made to the original.)  Eventually
there will be support for alternative rendering backends (PDFlib and
ClibPDF, for example, or even image rendering with GD).  Using the R&amp;OS
PDF class, however, eliminates any dependencies on external PDF libraries. </p>

<p>dompdf is entered in the <a
href="http://www.zend.com/php5/contest/contest.php">Zend PHP 5 Contest</a>.
While the current release is only version 0.3, it is quite usable and has
been adopted by at least one enterprise PHP application.</p>

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
models, individual cell styling, (no nested tables yet however)</li>

<li style="list-style-image: url('images/star_05.gif');">limited image
support (png &amp; jpeg only)</li>

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

<li style="list-style-image: url('images/star_01.gif');">tables can not break
across pages</li>

<li style="list-style-image: url('images/star_02.gif');">ordered lists are
currently unsupported (they should be working in the next release, however)</li>

<li style="list-style-image: url('images/star_03.gif');'">absolute &amp; relative
positioning and floats do not work,yet.</li>
                                                                           
<li style="list-style-image: url('images/star_05.gif');">no GIF support</li>

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
