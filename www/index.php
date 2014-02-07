<?php include("head.inc"); ?>

<a name="overview"></a><h2>Overview</h2>

<p>dompdf is an HTML to PDF converter. At its heart, dompdf is (mostly) CSS2.1
compliant HTML layout and rendering engine written in PHP. It is a style-driven
renderer: it will download and read external stylesheets, inline style tags, and
the style attributes of individual HTML elements. It also supports most
presentational HTML attributes.</p>

<p>PDF rendering is currently provided either by PDFLib (<a
href="http://www.pdflib.com">www.pdflib.com</a>) or by a bundled version the
CPDF class, originally R&amp;OS CPDF written by Wayne Munro but customized by
the dompdf team to improve performance and add features. In order to use PDFLib
with dompdf the PDFLib PHP extension is required (available from PDFLib). Using
PDFLib improves performance and reduces the memory requirements of dompdf
somewhat, while the CPDF class, though slightly slower, eliminates any
dependencies on external PDF libraries.</p>

<?php include("foot.inc"); ?>
