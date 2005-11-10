<?php include("head.inc");?>

<div id="toc">
<h2>On this page:</h2>
<ul>
<?php echo li_arrow() ?><a href="#requirements">Requirements</a></li>
<?php echo li_arrow() ?><a href="#installation">Installation</a></li>
<?php echo li_arrow() ?><a href="#fonts">Font Installation</a></li>
<ul>
<?php echo li_arrow() ?><a href="#all_platforms">Note for all platforms</a></li>
<?php echo li_arrow() ?><a href="#unix">Linux/Unix</a></li>
<ul><?php echo li_arrow() ?><a href="#load_font"><pre>load_font.php</pre></a></li></ul>
<?php echo li_arrow() ?><a href="#windows">Windows</a></li>
</ul>
<?php echo li_arrow() ?><a href="#hacking">Hacking</a></li>
</ul>
</div>

<a name="requirements"> </a>
<h2>Requirements</h2>

<ul>

<li style="list-style-image: url('images/star_03.gif');">PHP 5.0.0+
(although most later pre-5.0 snaps should work as well) with the DOM
extension enabled.</li>

<li style="list-style-image: url('images/star_05.gif');">Some fonts.  PDFs
internally support Helvetica, Times-Roman, Courier &amp; Zapf-Dingbats, but
if you wish to use other fonts you will need to install some fonts.  dompdf
supports the same fonts as the underlying PDF backends: Type 1 (.pfb
with the corresponding .afm) and TrueType (.ttf).  At the minimum, you
should probably have the Microsoft core fonts (now available at: <a
href="http://corefonts.sourceforge.net/">http://corefonts.sourceforge.net/</a>).
See <a href="#fonts">below</a> for font installation instructions.</li>

<li style="list-style-image: url('images/star_04.gif');">ttf2pt1 (available
at <a
href="http://ttf2pt1.sourceforge.net">http://ttf2pt1.sourceforge.net</a>) is
required to install new ttf fonts when using the CPDF backend.</li>

</ul>

<a name="installation"> </a>
<h2>Installation</h2>

<ol>
<li>Untar/unzip the source package in a directory accessible by your webserver.</li>

<li>Edit dompdf_config.inc.php to fit your installation.  If you leave
the DOMPDF_PDF_BACKEND setting at 'auto' dompdf will use PDFLib if it
is installed, otherwise it will use the bundled R&amp;OS CPDF class.</li>

<li><p>Give your webserver write permission on the path specified in
<code>DOMPDF_FONT_DIR</code> (lib/fonts by default).  Under *nix, ideally
you can make the webserver group the owner of this directory and give the
directory group write permissions.  For example, on Debian systems, apache
runs as the www-data user:</p>
<pre>
   $ chgrp www-data lib/fonts
   $ chmod g+w lib/fonts
</pre>
<p>If your user is not a member of the www-data group or you do not have
root priviledges, you can make the directory world writable and set the
sticky bit:</p>
<pre>   
   $ chmod 1777 lib/fonts
</pre>
</li>
</ol>

<a name="fonts"> </a>
<h2>Font Installation</h2>

<a name="all_platforms"> </a>
<h3>Note for all platforms</h3>

<p>PDFs include support by default for Helvetica, Times-Roman, Courier and
ZapfDingbats.  You do not need to install any font files if you wish to use
these fonts.  This has the advantage of reducing the size of the resulting
PDF, because additional fonts must be embedded in the PDF.</p>

<p>Also, if you have problems installing the font files, you can try and use
the distributed dompdf_font_family_cache.dist file in lib/fonts.  Copy this
file to lib/fonts/dompdf_font_family_cache and edit it directly to match the
files present in your lib/fonts directory.</p>

<a name="unix"> </a>
<h3>Linux/Unix</h3>

<p>The load_font.php utility installs and converts TrueType fonts for use with
dompdf.  Since CSS uses the concept of font families (i.e. the same face can
be rendered in differnt styles &amp; weights) dompdf needs to know which actual
font files belong to which font family and which style.  For example, the
Microsoft core font pack includes the files Verdana.ttf, Verdana_Italic.ttf,
Verdana_Bold.ttf and Verdana_Bold_Italic.ttf.  All four of these files need
to be present in the dompdf font directory (<code>DOMPDF_FONT_DIR</code>), and entries
need to be made in the dompdf_font_family_cache file.</p>

<p>Given the font family name and the path to the 'normal' font face file
(Verdana.ttf, in our example), load_font.php will search for the bold,
italic and bold italic font face files in the same directory as the
specified file.  It searches for files with the same base name followed by
'_Bold', 'B', or 'b' (similarly for italic and bold italic).  If it can not
find the correct files, you can specify them on the command line.</p>

<p>In addition to copying the files to the dompdf font directory, it also
generates .afm files.  The R&amp;OS CPDF class requires both the ttf file and an
afm file, which describes glyph metrics.  The afm file is generated using
the ttf2pt1 utlity (available at <a
href="http://ttf2pt1.sourceforge.net">http://ttf2pt1.sourceforge.net</a>).  
If you are using the PDFLib backend, you will not need to create afm
files for the fonts.</p>

<a name="load_font"> </a>
<p>load_font.php usage:</p>

<table>
<tr><td class="bar1" colspan="2">&nbsp;</td></tr>
<tr>
<td colspan="2" class="input">$ ./load_font.php font-family n_file [b_file] [i_file] [bi_file]</td>
</tr>

<tr>
<td class="input">font_family</td>
<td class="description">the name of the font, e.g. Verdana, 'Times New Roman', monospace, sans-serif.</td>
</tr>

<tr>
<td class="input">n_file</td>
<td class="description">the .pfb or .ttf file for the normal, non-bold, non-italic face of the font.</td>
</tr>

<tr>
<td class="input">{b|i|bi}_file</td>
<td class="description">the files for each of the respective (bold, italic, bold-italic) faces.</td>
</tr>

<tr><td class="bar2" colspan="2">&nbsp;</td></tr>
</table>

<p>Examples:</p>
<pre>
$ ./load_font.php silkscreen /usr/share/fonts/truetype/slkscr.ttf

$ ./load_font.php 'Times New Roman' /mnt/c_drive/WINDOWS/Fonts/times.ttf

$ php -f load_font.php -- sans-serif /home/dude_mcbacon/myfonts/Verdana.ttf \
                                     /home/dude_mcbacon/myfonts/V_Bold.ttf
</pre>

<a name="windows"> </a>
<h3>Windows</h3>

<p>(Note I don't have a windows test box at the moment, so these instructions
may not work...  If someone has tried this and has any suggestions for me,
please send me an email!)</p>

<p>Read the Linux/Unix section above first, as most of it applies.  The main
difference is the ttf2pt1 utility.  Fortunately, there is a windows version,
available at <a
href="http://gnuwin32.sourceforge.net/packages/ttf2pt1.htm">http://gnuwin32.sourceforge.net/packages/ttf2pt1.htm</a>.
You will have to edit your dompdf_config.inc.php file to point to the path
where you installed ttf2pt1.</p>

<p>You will also need the cli version of PHP in order to execute
load_font.php, however it's usage is the same (see the last example above).</p>



<?php include("foot.inc");?>