UFPDF, Unicode Free PDF generator
Version:  0.1
          based on FPDF 1.52 by Olivier PLATHEY
Date:     2004-09-01
Author:   Steven Wittens <steven@acko.net>
License:  GPL

UFPDF is a modification of FPDF to support Unicode through UTF-8.
All text passed to UFPDF must be UTF-8 encoded.
Only the basic multilingual plane (BMP) is supported.

Consult FPDF's documentation for how to use (U)FPDF.
http://www.fpdf.org/

A modified version of Mark Heath's TTF 2 PT1 converter, ttf2ufm,
is included in /ttf2ufm-src. The /tools folder contains a compiled
Windows binary. TTF 2 UFM is identical to TTF 2 PT1 except that it
also generates a .ufm file for usage with makefontuni.php.
http://ttf2pt1.sourceforge.net/

Setting up a Truetype font for usage with UFPDF:
  1) Generate the font's .ufm metrics file by processing it with the provided 
     ttf2ufm program (modified ttf2pt1). For example:
     $ ttf2ufm -a -F myfont.ttf
  2) Run makefontuni.php with the .ttf and .ufm filenames as argument:
     $ php -q makefontuni.php myfont.ttf myfont.ufm
  3) Copy the resulting .php, .z and .ctg.z file to the (U)FPDF font directory.

You can then load the font in UFPDF using:
  $pdf->AddFont('MyFont', '', 'myfont.php');

FPDF's support for Type 1 fonts was not included in UFPDF. These fonts will
not work.
