<?php include("head.inc"); ?>

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

<p>Please note that dompdf works only with PHP 5. There are no plans for
a PHP 4 port. If your web host does not offer PHP 4, I suggest either pestering
them, or setting up your own PHP 5 box and using it to run dompdf.  Your scripts
on your web host can redirect PDF requests to your PHP 5 box.</p>

<?php include("foot.inc"); ?>
