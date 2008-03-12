/*
 * True Type Font to Adobe Type 1 font converter 
 * By Mark Heath <mheath@netspace.net.au> 
 * Based on ttf2pfa by Andrew Weeks <ccsaw@bath.ac.uk> 
 * With help from Frank M. Siegert <fms@this.net> 
 *
 * see COPYRIGHT for full copyright notice
 *
***********************************************************************
 *
 * Orion Richardson <orionr@yahoo.com>
 *
 * Added bounding box calculations for Unicode glyphs in the .ufm file
 *
***********************************************************************
 *
 * Steven Wittens <steven@acko.net>
 *
 * Added generation of .ufm file
 *
***********************************************************************
 *
 * Sergey Babkin <babkin@users.sourceforge.net>, <sab123@hotmail.com>
 *
 * Added post-processing of resulting outline to correct the errors
 * both introduced during conversion and present in the original font,
 * autogeneration of hints (has yet to be improved though) and BlueValues,
 * scaling to 1000x1000 matrix, option to print the result on STDOUT,
 * support of Unicode to CP1251 conversion, optimization  of the
 * resulting font code by space (that improves the speed too). Excluded
 * the glyphs that are unaccessible through the encoding table from
 * the output file. Added the built-in Type1 assembler (taken from
 * the `t1utils' package).
 *
***********************************************************************
 *
 * Thomas Henlich <thenlich@rcs.urz.tu-dresden.de>
 *
 * Added generation of .afm file (font metrics)
 * Read encoding information from encoding description file
 * Fixed bug in error message about unknown language ('-l' option)
 * Added `:' after %%!PS-AdobeFont-1.0
 * changed unused entries in ISOLatin1Encoding[] from .notdef to c127,c128...
 *
***********************************************************************
 *
 * Thomas Henlich <thenlich@rcs.urz.tu-dresden.de>
 *
 * Added generation of .afm file (font metrics)
 *
***********************************************************************
 *
 * Bug Fixes: 
************************************************************************
 *
 * Sun, 21 Jun 1998 Thomas Henlich <thenlich@Rcs1.urz.tu-dresden.de> 
 * 1. "width" should be "short int" because otherwise: 
 *     characters with negative widths (e.g. -4) become *very* wide (65532) 
 * 2. the number of /CharStrings is numglyphs and not numglyphs+1 
 *
***********************************************************************
 *
 *
 *
 * The resultant font file produced by this program still needs to be ran
 * through t1asm (from the t1utils archive) to produce a completely valid
 * font. 
 *
 */
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <time.h>
#include <ctype.h>
#include <math.h>

#ifdef _GNU_SOURCE
#include <getopt.h>
#endif

#ifndef WINDOWS
#  include <unistd.h>
#  include <netinet/in.h>
#  define BITBUCKET "/dev/null"
#  include <sys/wait.h>
#else
#  define WINDOWS_FUNCTIONS /* ask to define functions - in one file only */
#  include "windows.h"
#  define BITBUCKET "NUL"
#  define snprintf _snprintf
#endif

#include "pt1.h"
#include "global.h"
#include "version.h"

/* globals */

/* table of front-ends */

extern struct frontsw ttf_sw;
extern struct frontsw bdf_sw;
#if defined(USE_FREETYPE)
  extern struct frontsw freetype_sw;
#endif

struct frontsw *frontswtab[] = {
  &bdf_sw,
#if defined(USE_FREETYPE) && defined(PREFER_FREETYPE)
  &freetype_sw,
#endif
  &ttf_sw,
#if defined(USE_FREETYPE) && !defined(PREFER_FREETYPE)
  &freetype_sw,
#endif
  NULL /* end of table */
};

struct frontsw *cursw=0; /* the active front end */
char *front_arg=""; /* optional argument */

/* options */
int      encode = 0;  /* encode the resulting file */
int      pfbflag = 0;  /* produce compressed file */
int      wantafm=0;  /* want to see .afm instead of .t1a on stdout */
int      correctvsize=0;  /* try to correct the vertical size of characters */
int      wantuid = 0;  /* user wants UniqueID entry in the font */
int      allglyphs = 0;  /* convert all glyphs, not only 256 of them */
int      warnlevel = 3;  /* the level of permitted warnings */
int      forcemap = 0; /* do mapping even on non-Unicode fonts */
/* options - maximal limits */
int      max_stemdepth = 128;  /* maximal depth of stem stack in interpreter (128 - limit from X11) */
/* options - debugging */
int      absolute = 0;  /* print out in absolute values */
int      reverse = 1;  /* reverse font to Type1 path directions */
/* options - suboptions of Outline Processing, defaults are set in table */
int      optimize;  /* enables space optimization */
int      smooth;  /* enable smoothing of outlines */
int      transform;  /* enables transformation to 1000x1000 matrix */
int      hints;  /* enables autogeneration of hints */
int      subhints;  /* enables autogeneration of substituted hints */
int      trybold;  /* try to guess whether the font is bold */
int      correctwidth;  /* try to correct the character width */
int      vectorize;  /* vectorize the bitmaps */
int      use_autotrace;  /* use the autotrace library on bitmap */
/* options - suboptions of File Generation, defaults are set in table */
int      gen_pfa;  /* generate the font file */
int      gen_afm;  /* generate the metrics file */
int      gen_ufm;  /* generate the unicode metrics file */
int      gen_dvienc;  /* generate the dvips encoding file */

/* not quite options to select a particular source encoding */
int      force_pid = -1; /* specific platform id */
int      force_eid = -1; /* specific encoding id */

/* structure to define the sub-option lists controlled by the
 * case: uppercase enables them, lowercase disables
 */
struct subo_case {
  char disbl; /* character to disable - enforced lowercase */
  char enbl;  /* character to enable - auto-set as toupper(disbl) */
  int *valp; /* pointer to the actual variable containing value */
  int  dflt; /* default value */
  char *descr; /* description */
};

int      debug = DEBUG;  /* debugging flag */

FILE    *null_file, *pfa_file, *afm_file, *ufm_file, *dvienc_file;
int      numglyphs;
struct font_metrics fontm;

/* non-globals */
static char    *strUID = 0;  /* user-supplied UniqueID */
static unsigned long numUID;  /* auto-generated UniqueID */

static int      ps_fmt_3 = 0;
static double   scale_factor, original_scale_factor;

static char  *glyph_rename[ENCTABSZ];

/* the names assigned if the original font
 * does not specify any
 */

static char    *Fmt3Encoding[256] = {
  "c0", "c1", "c2", "c3",
  "c4", "c5", "c6", "c7",
  "c8", "c9", "c10", "c11",
  "c12", "CR", "c14", "c15",
  "c16", "c17", "c18", "c19",
  "c20", "c21", "c22", "c23",
  "c24", "c25", "c26", "c27",
  "c28", "c29", "c30", "c31",
  "space", "exclam", "quotedbl", "numbersign",
  "dollar", "percent", "ampersand", "quotesingle",
  "parenleft", "parenright", "asterisk", "plus",
  "comma", "hyphen", "period", "slash",
  "zero", "one", "two", "three",
  "four", "five", "six", "seven",
  "eight", "nine", "colon", "semicolon",
  "less", "equal", "greater", "question",
  "at", "A", "B", "C",
  "D", "E", "F", "G",
  "H", "I", "J", "K",
  "L", "M", "N", "O",
  "P", "Q", "R", "S",
  "T", "U", "V", "W",
  "X", "Y", "Z", "bracketleft",
  "backslash", "bracketright", "asciicircum", "underscore",
  "grave", "a", "b", "c",
  "d", "e", "f", "g",
  "h", "i", "j", "k",
  "l", "m", "n", "o",
  "p", "q", "r", "s",
  "t", "u", "v", "w",
  "x", "y", "z", "braceleft",
  "bar", "braceright", "asciitilde", "c127",
  "c128", "c129", "quotesinglbase", "florin",
  "quotedblbase", "ellipsis", "dagger", "daggerdbl",
  "circumflex", "perthousand", "Scaron", "guilsinglleft",
  "OE", "c141", "c142", "c143",
  "c144", "quoteleft", "quoteright", "quotedblleft",
  "quotedblright", "bullet", "endash", "emdash",
  "tilde", "trademark", "scaron", "guilsinglright",
  "oe", "c157", "c158", "Ydieresis",
  "nbspace", "exclamdown", "cent", "sterling",
  "currency", "yen", "brokenbar", "section",
  "dieresis", "copyright", "ordfeminine", "guillemotleft",
  "logicalnot", "sfthyphen", "registered", "macron",
  "degree", "plusminus", "twosuperior", "threesuperior",
  "acute", "mu", "paragraph", "periodcentered",
  "cedilla", "onesuperior", "ordmasculine", "guillemotright",
  "onequarter", "onehalf", "threequarters", "questiondown",
  "Agrave", "Aacute", "Acircumflex", "Atilde",
  "Adieresis", "Aring", "AE", "Ccedilla",
  "Egrave", "Eacute", "Ecircumflex", "Edieresis",
  "Igrave", "Iacute", "Icircumflex", "Idieresis",
  "Eth", "Ntilde", "Ograve", "Oacute",
  "Ocircumflex", "Otilde", "Odieresis", "multiply",
  "Oslash", "Ugrave", "Uacute", "Ucircumflex",
  "Udieresis", "Yacute", "Thorn", "germandbls",
  "agrave", "aacute", "acircumflex", "atilde",
  "adieresis", "aring", "ae", "ccedilla",
  "egrave", "eacute", "ecircumflex", "edieresis",
  "igrave", "iacute", "icircumflex", "idieresis",
  "eth", "ntilde", "ograve", "oacute",
  "ocircumflex", "otilde", "odieresis", "divide",
  "oslash", "ugrave", "uacute", "ucircumflex",
  "udieresis", "yacute", "thorn", "ydieresis"
};

#ifdef notdef /* { */
/* This table is not used anywhere in the code
 * so it's ifdef-ed out by default but left in
 * the source code for reference purposes (and
 * possibly for future use)
 */

static char    *ISOLatin1Encoding[256] = {
  ".null", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", "CR", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  "space", "exclam", "quotedbl", "numbersign",
  "dollar", "percent", "ampersand", "quoteright",
  "parenleft", "parenright", "asterisk", "plus",
  "comma", "hyphen", "period", "slash",
  "zero", "one", "two", "three",
  "four", "five", "six", "seven",
  "eight", "nine", "colon", "semicolon",
  "less", "equal", "greater", "question",
  "at", "A", "B", "C",
  "D", "E", "F", "G",
  "H", "I", "J", "K",
  "L", "M", "N", "O",
  "P", "Q", "R", "S",
  "T", "U", "V", "W",
  "X", "Y", "Z", "bracketleft",
  "backslash", "bracketright", "asciicircum", "underscore",
  "grave", "a", "b", "c",
  "d", "e", "f", "g",
  "h", "i", "j", "k",
  "l", "m", "n", "o",
  "p", "q", "r", "s",
  "t", "u", "v", "w",
  "x", "y", "z", "braceleft",
  "bar", "braceright", "asciitilde", "c127",
  "c128", "c129", "quotesinglbase", "florin",
  "quotedblbase", "ellipsis", "dagger", "daggerdbl",
  "circumflex", "perthousand", "Scaron", "guilsinglleft",
  "OE", "c141", "c142", "c143",
  "c144", "quoteleft", "quoteright", "quotedblleft",
  "quotedblright", "bullet", "endash", "emdash",
  "tilde", "trademark", "scaron", "guilsinglright",
  "oe", "c157", "c158", "Ydieresis",
  "nbspace", "exclamdown", "cent", "sterling",
  "currency", "yen", "brokenbar", "section",
  "dieresis", "copyright", "ordfeminine", "guillemotleft",
  "logicalnot", "sfthyphen", "registered", "macron",
  "degree", "plusminus", "twosuperior", "threesuperior",
  "acute", "mu", "paragraph", "periodcentered",
  "cedilla", "onesuperior", "ordmasculine", "guillemotright",
  "onequarter", "onehalf", "threequarters", "questiondown",
  "Agrave", "Aacute", "Acircumflex", "Atilde",
  "Adieresis", "Aring", "AE", "Ccedilla",
  "Egrave", "Eacute", "Ecircumflex", "Edieresis",
  "Igrave", "Iacute", "Icircumflex", "Idieresis",
  "Eth", "Ntilde", "Ograve", "Oacute",
  "Ocircumflex", "Otilde", "Odieresis", "multiply",
  "Oslash", "Ugrave", "Uacute", "Ucircumflex",
  "Udieresis", "Yacute", "Thorn", "germandbls",
  "agrave", "aacute", "acircumflex", "atilde",
  "adieresis", "aring", "ae", "ccedilla",
  "egrave", "eacute", "ecircumflex", "edieresis",
  "igrave", "iacute", "icircumflex", "idieresis",
  "eth", "ntilde", "ograve", "oacute",
  "ocircumflex", "otilde", "odieresis", "divide",
  "oslash", "ugrave", "uacute", "ucircumflex",
  "udieresis", "yacute", "thorn", "ydieresis"
};

#endif /* } notdef */

static char    *adobe_StandardEncoding[256] = {
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  "space", "exclam", "quotedbl", "numbersign",
  "dollar", "percent", "ampersand", "quoteright",
  "parenleft", "parenright", "asterisk", "plus",
  "comma", "hyphen", "period", "slash",
  "zero", "one", "two", "three",
  "four", "five", "six", "seven",
  "eight", "nine", "colon", "semicolon",
  "less", "equal", "greater", "question",
  "at", "A", "B", "C", "D", "E", "F", "G",
  "H", "I", "J", "K", "L", "M", "N", "O",
  "P", "Q", "R", "S", "T", "U", "V", "W",
  "X", "Y", "Z", "bracketleft",
  "backslash", "bracketright", "asciicircum", "underscore",
  "quoteleft", "a", "b", "c", "d", "e", "f", "g",
  "h", "i", "j", "k", "l", "m", "n", "o",
  "p", "q", "r", "s", "t", "u", "v", "w",
  "x", "y", "z", "braceleft",
  "bar", "braceright", "asciitilde", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", "exclamdown", "cent", "sterling",
  "fraction", "yen", "florin", "section",
  "currency", "quotesingle", "quotedblleft", "guillemotleft",
  "guilsinglleft", "guilsinglright", "fi", "fl",
  ".notdef", "endash", "dagger", "daggerdbl",
  "periodcentered", ".notdef", "paragraph", "bullet",
  "quotesinglbase", "quotedblbase", "quotedblright", "guillemotright",
  "ellipsis", "perthousand", ".notdef", "questiondown",
  ".notdef", "grave", "acute", "circumflex",
  "tilde", "macron", "breve", "dotaccent",
  "dieresis", ".notdef", "ring", "cedilla",
  ".notdef", "hungarumlaut", "ogonek", "caron",
  "emdash", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", "AE", ".notdef", "ordfeminine",
  ".notdef", ".notdef", ".notdef", ".notdef",
  "Lslash", "Oslash", "OE", "ordmasculine",
  ".notdef", ".notdef", ".notdef", ".notdef",
  ".notdef", "ae", ".notdef", ".notdef",
  ".notdef", "dotlessi", ".notdef", ".notdef",
  "lslash", "oslash", "oe", "germandbls",
  ".notdef", ".notdef", ".notdef", ".notdef"
};

/*
 * Decription of the supported conversions from Unicode
 *
 * SB
 * Yes, I know that the compiled-in conversion is stupid but
 * it is simple to implement and allows not to worry about the
 * filesystem context. After all, the source is always available
 * and adding another language to it is easy.
 *
 * The language name is expected to be the same as the subdirectory name 
 * in the `encodings' directory (for possible future extensions). 
 * The primary use of the aliases is for guessing based on the current 
 * locale.
 */

#define MAXUNIALIAS 10
#define MAXUNITABLES 3

/* the character used as the language argument separator */
#define LANG_ARG_SEP '+'


/*
 * Types of language-related routines. Arguments are:
 * name is the glyph name
 * arg is the user-specified language-dependent argument
 *   which can for example select the subfont plane for Eastern fonts.
 *   If none is supplied by user then an empty string ("") is passed.
 *   If no language is specified by user and auto-guessing happens
 *   then NULL is passed.
 * when shows if the conversion by name was called before conversion by
 *   map or after (it's called twice)
 */

/* type of the Unicode map initialization routine */
typedef void uni_init_t(char *arg);

/* type of Unicode converter-by-name function
 * it's called for each glyph twice: one time for each glyph
 * before doing conversion by map and one time after
 */
typedef int uni_conv_t(char *name, char *arg, int when);
#define UNICONV_BYNAME_BEFORE 0
#define UNICONV_BYNAME_AFTER 1

struct uni_language {
  uni_init_t  *init[MAXUNITABLES]; /* map initialization routines */
  uni_conv_t  *convbyname; /* the name-based conversion function */
  char *name; /* the language name */
  char *descr; /* description */
  char *alias[MAXUNIALIAS]; /* aliases of the language name */
  int sample_upper; /* code of some uppercase character for correctvsize() */
};

/* the converter routines have an option of adding this suffix to the font name */
static char *uni_font_name_suffix = ""; /* empty by default */
/* this buffer may be used to store the suffix */
#define UNI_MAX_SUFFIX_LEN  100
static char uni_suffix_buf[UNI_MAX_SUFFIX_LEN+1];

/*
 * Prototypes of the conversion routines
 */

static uni_init_t unicode_latin1;
static uni_init_t unicode_latin2;
static uni_init_t unicode_latin4;
static uni_init_t unicode_latin5;
static uni_init_t unicode_cyrillic;
static uni_init_t unicode_adobestd;
static uni_init_t unicode_plane;
static uni_conv_t unicode_adobestd_byname;

static uni_init_t unicode_init_user;

/*
 * The order of descriptions is important: if we can't guess the
 * language we just call all the conversion routines in order until
 * we find one that understands this glyph.
 */
static struct uni_language uni_lang[]= {
  /* pseudo-language for all the languages using Latin1 */
  {
    { unicode_latin1 },
    0, /* no name-based mapping */
    "latin1",
    "works for most of the Western languages",
    { "en_", "de_", "fr_", "nl_", "no_", "da_", "it_" },
    'A'
  },
  { /* by Szalay Tamas <tomek@elender.hu> */
    { unicode_latin2 },
    0, /* no name-based mapping */
    "latin2",
    "works for Central European languages",
    { "hu_","pl_","cz_","si_","sk_" },
    'A'
  },
  { /* by Rièardas Èepas <rch@WriteMe.Com> */
    { unicode_latin4 }, 
    0, /* no name-based mapping */
    "latin4",
    "works for Baltic languages",
    { "lt_", "lv_" }, /* doubt about ee_ */
    'A'
  },
  { /* by Turgut Uyar <uyar@cs.itu.edu.tr> */
    { unicode_latin5 }, 
    0, /* no name-based mapping */
    "latin5",
    "for Turkish",
    { "tr_" },
    'A'
  },
  { /* by Zvezdan Petkovic <z.petkovic@computer.org> */
    { unicode_cyrillic, unicode_latin1 },
    0, /* no name-based mapping */
    "cyrillic",
    "in Windows encoding",
    { "bg_", "be_", "mk_", "ru_", "sr_", "su_", "uk_" },
    'A'
  },
  {
    { unicode_cyrillic, unicode_latin1 },
    0, /* no name-based mapping */
    "russian",
    "obsolete, use cyrillic instead",
    { 0 },
    'A'
  },
  {
    { unicode_cyrillic, unicode_latin1 },
    0, /* no name-based mapping */
    "bulgarian",
    "obsolete, use cyrillic instead",
    { 0 },
    'A'
  },
  {
    { unicode_adobestd },
    unicode_adobestd_byname,
    "adobestd",
    "Adobe Standard, expected by TeX",
    { NULL },
    'A'
  },
  {
    { unicode_plane },
    0, /* no name-based mapping */
    "plane",
    "one plane of Unicode or other multi-byte encoding as is",
    { NULL },
    0 /* no easy way to predict the capital letters */
  },
};

static struct uni_language uni_lang_user = {
  { unicode_init_user }, 
  0, /* no name-based mapping */
  0, /* no name */
  0, /* no description */
  { 0 },
  0 /* no sample */
};

static struct uni_language *uni_lang_selected=0; /* 0 means "unknown, try all" */
static int uni_sample='A'; /* sample of an uppercase character */
static char *uni_lang_arg=""; /* user-supplied language-dependent argument */

extern int      runt1asm(int);

/*
 * user-defined loadable maps
 */


/* The idea begind buckets is to avoid comparing every code with all ENCTABSZ codes in table.
 * All the 16-bit unicode space is divided between a number of equal-sized buckets.
 * Initially all the buckets are marked with 0. Then if any code in the bucket is
 * used it's marked with 1. Later during translation we check the code's bucket first
 * and it it's 0 then return failure right away. This may be useful for
 * Chinese fonts with many thousands of glyphs.
 */

#define BUCKET_ID_BITS  11
#define MARK_UNI_BUCKET(unicode) SET_BITMAP(uni_user_buckets, (unicode)>>(16-BUCKET_ID_BITS))
#define IS_UNI_BUCKET(unicode) IS_BITMAP(uni_user_buckets, (unicode)>>(16-BUCKET_ID_BITS))

static DEF_BITMAP(uni_user_buckets, 1<<BUCKET_ID_BITS);

static unsigned int unicode_map[ENCTABSZ]; /* font-encoding to unicode map */
static int enctabsz = 256; /* actual number of codes used */

static void
unicode_init_user(
     char *path
)
{
  FILE           *unicode_map_file;
#define UNIBFSZ  256
  char            buffer[UNIBFSZ];
  unsigned        code, unicode, curpos, unicode2;
  char           *arg, *p;
  int             enabled, found, sawplane;
  int             lineno, cnt, n, nchars;
  char            next;
  int             pid, eid, overid=0;

  /* check if we have an argument (plane name) */
  arg = strrchr(path, LANG_ARG_SEP);
  if(arg != 0) {
    *arg++ = 0;
    if( sscanf(arg, "pid=%d,eid=%d%n", &pid, &eid, &nchars) == 2 ) {
      force_pid = pid; force_eid = eid; overid = 1;
      WARNING_1 fprintf(stderr, "User override of the source encoding: pid=%d eid=%d\n", pid, eid);
      forcemap = 1;
      arg += nchars;
      if(*arg == ',')
        arg++;
    }
    if( *arg == 0 || strlen(arg) > UNI_MAX_SUFFIX_LEN-1) 
      arg = NULL;
    else {
      sprintf(uni_suffix_buf, "-%s", arg);
      uni_font_name_suffix = uni_suffix_buf;
    }
  } 

  /* now read in the encoding description file, if requested */
  if ((unicode_map_file = fopen(path, "r")) == NULL) {
    fprintf(stderr, "**** Cannot access map file '%s' ****\n", path);
    exit(1);
  }

  sawplane = 0;
  if(arg==NULL)
    enabled = found = 1;
  else
    enabled = found = 0;

  lineno=0; curpos=0;
  while (fgets (buffer, UNIBFSZ, unicode_map_file) != NULL) {
    char name[UNIBFSZ];

    lineno++;

    if(sscanf(buffer, "plane %s", name)==1) {
      sawplane = 1;
      if(arg == 0) {
        fprintf(stderr, "**** map file '%s' requires plane name\n", path);
        fprintf(stderr, "for example:\n");
        fprintf(stderr, "  ttf2pt1 -L %s%c[pid=N,eid=N,]%s ...\n", 
          path, LANG_ARG_SEP, name);
        fprintf(stderr, "to select plane '%s'\n", name);
        exit(1);
      }
      if( !strcmp(arg, name) ) {
        enabled = found = 1; 
        curpos = 0;
      } else {
        enabled = 0;
        if(found) /* no need to read further */
          break;
      }
      continue;
    }

    if(sscanf(buffer, "id %d %d", pid, eid)==2) {
      if( !overid /* only if the user has not overriden */
      && (enabled || !sawplane) ) { 
        force_pid = pid; force_eid = eid;
        forcemap = 1;
      }
      continue;
    }

    if( !enabled )
      continue; /* skip to the next plane */

    if( sscanf(buffer, "at %i", &curpos) == 1 ) {
      if(curpos > 255) {
        fprintf(stderr, "**** map file '%s' line %d: code over 255\n", path, lineno);
        exit(1);
      }
      if(ISDBG(EXTMAP)) fprintf(stderr, "=== at 0x%x\n", curpos);
      continue;
    }

    /* try the format of Roman Czyborra's files */
    if ( sscanf (buffer, " =%x U+%4x", &code, &unicode) == 2
    /* try the format of Linux locale charmap file */
    || sscanf (buffer, " <%*s /x%x <U%4x>", &code, &unicode) == 2 ) {
      if (code < ENCTABSZ) {
        if(code >= enctabsz) enctabsz=code+1;
        unicode_map[code] = unicode;
        glyph_rename[code] = NULL;
      }
    }
    /* try the format with glyph renaming */
    else if (sscanf (buffer, " !%x U+%4x %128s", &code,
      &unicode, name) == 3) {
      if (code < ENCTABSZ) {
        if(code >= enctabsz) enctabsz=code+1;
        unicode_map[code] = unicode;
        glyph_rename[code] = strdup(name);
      }
    }
    /* try the compact sequence format */
    else if( (n=sscanf(buffer, " %i%n", &unicode, &cnt)) == 1 ) {
      p = buffer;
      do {
        if(curpos > 255) {
          fprintf(stderr, "**** map file '%s' line %d: code over 255 for unicode 0x%x\n", 
            path, lineno, unicode);
          exit(1);
        }
        if(ISDBG(EXTMAP)) fprintf(stderr, "=== 0x%d -> 0x%x\n", curpos, unicode);
        unicode_map[curpos++] = unicode;
        p += cnt;
        if( sscanf(p, " %[,-]%n", &next,&cnt) == 1 ) {
          if(ISDBG(EXTMAP)) fprintf(stderr, "=== next: '%c'\n", next);
          p += cnt;
          if( next == '-' ) { /* range */
            if ( sscanf(p, " %i%n", &unicode2, &cnt) != 1 ) {
              fprintf(stderr, "**** map file '%s' line %d: missing end of range\n", path, lineno);
              exit(1);
            }
            p += cnt;
            if(ISDBG(EXTMAP)) fprintf(stderr, "=== range 0x%x to 0x%x\n", unicode, unicode2);
            for(unicode++; unicode <= unicode2; unicode++) {
              if(curpos > 255) {
                fprintf(stderr, "**** map file '%s' line %d: code over 255 in unicode range ...-0x%x\n", 
                  path, lineno, unicode2);
                exit(1);
              }
              if(ISDBG(EXTMAP)) fprintf(stderr, "=== 0x%x -> 0x%x\n", curpos, unicode);
              unicode_map[curpos++] = unicode;
            }
          }
        }
      } while ( sscanf(p, " %i%n", &unicode, &cnt) == 1 );
    }

  }

  fclose (unicode_map_file);

  if( !found ) {
    fprintf(stderr, "**** map file '%s' has no plane '%s'\n", path, arg);
    exit(1);
  }

  if(unicode_map['A'] == 'A')
    uni_sample = 'A'; /* seems to be compatible with Latin */
  else
    uni_sample = 0; /* don't make any assumptions */
}

/*
 * by Zvezdan Petkovic <z.petkovic@computer.org> 
 */
static void
unicode_cyrillic(
     char *arg
)
{
  int i;
  static unsigned int cyrillic_unicode_map[] = {
    0x0402, 0x0403, 0x201a, 0x0453, 0x201e, 0x2026, 0x2020, 0x2021,  /* 80 */
    0x20ac, 0x2030, 0x0409, 0x2039, 0x040a, 0x040c, 0x040b, 0x040f,  /* 88 */
    0x0452, 0x2018, 0x2019, 0x201c, 0x201d, 0x2022, 0x2013, 0x2014,  /* 90 */
    0x02dc, 0x2122, 0x0459, 0x203a, 0x045a, 0x045c, 0x045b, 0x045f,  /* 98 */
    0x00a0, 0x040e, 0x045e, 0x0408, 0x00a4, 0x0490, 0x00a6, 0x00a7,  /* A0 */
    0x0401, 0x00a9, 0x0404, 0x00ab, 0x00ac, 0x00ad, 0x00ae, 0x0407,  /* A8 */
    0x00b0, 0x00b1, 0x0406, 0x0456, 0x0491, 0x00b5, 0x00b6, 0x00b7,  /* B0 */
    0x0451, 0x2116, 0x0454, 0x00bb, 0x0458, 0x0405, 0x0455, 0x0457,  /* B8 */
  };

  for(i=0; i<=0x7F; i++)
    unicode_map[i] = i;

  for(i=0x80; i<=0xBF; i++)
    unicode_map[i] = cyrillic_unicode_map[i-0x80];

  for(i=0xC0; i<=0xFF; i++)
    unicode_map[i] = i+0x350;

}

static void
unicode_latin1(
     char *arg
)
{
  int i;
  static unsigned int latin1_unicode_map[] = {
    0x20ac,     -1, 0x201a, 0x0192, 0x201e, 0x2026, 0x2020, 0x2021,  /* 80 */
    0x02c6, 0x2030, 0x0160, 0x2039, 0x0152, 0x008d, 0x017d, 0x008f,  /* 88 */
    0x0090, 0x2018, 0x2019, 0x201c, 0x201d, 0x2022, 0x2013, 0x2014,  /* 90 */
    0x02dc, 0x2122, 0x0161, 0x203a, 0x0153, 0x009d, 0x017e, 0x0178,  /* 98 */
  };

  for(i=0; i<=0x7F; i++)
    unicode_map[i] = i;

  for(i=0x80; i<=0x9F; i++)
    unicode_map[i] = latin1_unicode_map[i-0x80];

  for(i=0xA0; i<=0xFF; i++)
    unicode_map[i] = i;
}

static void
unicode_adobestd(
     char *arg
)
{
  int i;
  static unsigned int adobestd_unicode_map[] = {
      -1, 0x00a1, 0x00a2, 0x00a3, 0x2215, 0x00a5, 0x0192, 0x00a7,  /* A0 */
    0x00a4, 0x0027, 0x201c, 0x00ab, 0x2039, 0x203a, 0xfb01, 0xfb02,  /* A8 */
      -1, 0x2013, 0x2020, 0x2021, 0x2219,     -1, 0x00b6, 0x2022,  /* B0 */
    0x201a, 0x201e, 0x201d, 0x00bb, 0x2026, 0x2030,     -1, 0x00bf,  /* B8 */
      -1, 0x0060, 0x00b4, 0x02c6, 0x02dc, 0x02c9, 0x02d8, 0x02d9,  /* C0 */
    0x00a8,     -1, 0x02da, 0x00b8,     -1, 0x02dd, 0x02db, 0x02c7,  /* C8 */
    0x2014,     -1,     -1,     -1,     -1,     -1,     -1,     -1,  /* D0 */
      -1,     -1,     -1,     -1,     -1,     -1,     -1,     -1,  /* D8 */
      -1, 0x00c6,     -1, 0x00aa,     -1,     -1,     -1,     -1,  /* E0 */
    0x0141, 0x00d8, 0x0152, 0x00ba,     -1,     -1,     -1,     -1,  /* E8 */
      -1, 0x00e6,     -1,     -1,     -1, 0x0131,     -1,     -1,  /* F0 */
    0x0142, 0x00f8, 0x0153, 0x00df,     -1,     -1,     -1,     -1,  /* F8 */
  };

  for(i=0; i<=0x7F; i++)
    unicode_map[i] = i;

  unicode_map[0x27] = 0x2019;
  unicode_map[0x60] = -1;

  /* 0x80 to 0x9F is a hole */

  for(i=0xA0; i<=0xFF; i++)
    unicode_map[i] = adobestd_unicode_map[i-0xA0];
}

/*
 * Not all of the Adobe glyphs are in the Unicode
 * standard maps, so the font creators have
 * different ideas about their codes. Because
 * of this we try to map based on the glyph
 * names instead of Unicode codes. If there are
 * no glyph names (ps_fmt_3!=0) we fall back
 * to the code-based scheme.
 */

static int
unicode_adobestd_byname(
     char *name,
     char *arg,
     int where
)
{
  int i;

  /* names always take precedence over codes */
  if(where == UNICONV_BYNAME_AFTER)
    return -1;

  for(i=32; i<256; i++) {
    if(!strcmp(name, adobe_StandardEncoding[i]))
      return i;
  }
  return -1;

}

static void
unicode_latin2(
     char *arg
)
{
  int i;
  static unsigned int latin2_unicode_map[] = {
    0x00a0, 0x0104, 0x02d8, 0x0141, 0x00a4, 0x013d, 0x015a, 0x00a7,  /* A0 */
    0x00a8, 0x0160, 0x015e, 0x0164, 0x0179, 0x00ad, 0x017d, 0x017b,  /* A8 */
    0x00b0, 0x0105, 0x02db, 0x0142, 0x00b4, 0x013e, 0x015b, 0x02c7,  /* B0 */
    0x00b8, 0x0161, 0x015f, 0x0165, 0x017a, 0x02dd, 0x017e, 0x017c,  /* B8 */
    0x0154, 0x00c1, 0x00c2, 0x0102, 0x00c4, 0x0139, 0x0106, 0x00c7,  /* C0 */
    0x010c, 0x00c9, 0x0118, 0x00cb, 0x011a, 0x00cd, 0x00ce, 0x010e,  /* C8 */
    0x0110, 0x0143, 0x0147, 0x00d3, 0x00d4, 0x0150, 0x00d6, 0x00d7,  /* D0 */
    0x0158, 0x016e, 0x00da, 0x0170, 0x00dc, 0x00dd, 0x0162, 0x00df,  /* D8 */
    0x0155, 0x00e1, 0x00e2, 0x0103, 0x00e4, 0x013a, 0x0107, 0x00e7,  /* E0 */
    0x010d, 0x00e9, 0x0119, 0x00eb, 0x011b, 0x00ed, 0x00ee, 0x010f,  /* E8 */
    0x0111, 0x0144, 0x0148, 0x00f3, 0x00f4, 0x0151, 0x00f6, 0x00f7,  /* F0 */
    0x0159, 0x016f, 0x00fa, 0x0171, 0x00fc, 0x00fd, 0x0163, 0x02d9,  /* F8 */
  };

  for(i=0; i<=0x7E; i++)
    unicode_map[i] = i;

  /* 7F-9F are unused */

  for(i=0xA0; i<=0xFF; i++)
    unicode_map[i] = latin2_unicode_map[i-0xA0];
}

static void
unicode_latin4(
     char *arg
)
{
  int i;
  static unsigned int latin4_unicode_map[] = {
    0x0080, 0x0081, 0x201a, 0x0192,     -1, 0x2026, 0x2020, 0x2021,  /* 80 */
    0x02c6, 0x2030,     -1, 0x2039, 0x0152, 0x008d, 0x008e, 0x008f,  /* 88 */
    0x201e, 0x201c, 0x2019,     -1, 0x201d, 0x2022, 0x2013, 0x2014,  /* 90 */
    0x02dc, 0x2122,     -1, 0x203a, 0x0153, 0x009d, 0x009e, 0x0178,  /* 98 */
    0x00a0, 0x0104, 0x0138, 0x0156, 0x00a4, 0x0128, 0x013b, 0x00a7,  /* A0 */
    0x00a8, 0x0160, 0x0112, 0x0122, 0x0166, 0x00ad, 0x017d, 0x00af,  /* A8 */
    0x00b0, 0x0105, 0x02db, 0x0157, 0x00b4, 0x0129, 0x013c, 0x02c7,  /* B0 */
    0x00b8, 0x0161, 0x0113, 0x0123, 0x0167, 0x014a, 0x017e, 0x014b,  /* B8 */
    0x0100, 0x00c1, 0x00c2, 0x00c3, 0x00c4, 0x00c5, 0x00c6, 0x012e,  /* C0 */
    0x010c, 0x00c9, 0x0118, 0x00cb, 0x0116, 0x00cd, 0x00ce, 0x012a,  /* C8 */
    0x0110, 0x0145, 0x014c, 0x0136, 0x00d4, 0x00d5, 0x00d6, 0x00d7,  /* D0 */
    0x00d8, 0x0172, 0x00da, 0x00db, 0x00dc, 0x0168, 0x016a, 0x00df,  /* D8 */
    0x0101, 0x00e1, 0x00e2, 0x00e3, 0x00e4, 0x00e5, 0x00e6, 0x012f,  /* E0 */
    0x010d, 0x00e9, 0x0119, 0x00eb, 0x0117, 0x00ed, 0x00ee, 0x012b,  /* E8 */
    0x0111, 0x0146, 0x014d, 0x0137, 0x00f4, 0x00f5, 0x00f6, 0x00f7,  /* F0 */
    0x00f8, 0x0173, 0x00fa, 0x00fb, 0x00fc, 0x0169, 0x016b, 0x02d9,  /* F8 */
  };

  for(i=0; i<=0x7F; i++)
    unicode_map[i] = i;

  for(i=0x80; i<=0xFF; i++)
    unicode_map[i] = latin4_unicode_map[i-0x80];

#if 0 /* for documentation purposes only */
  case 0x201e: return 0x90; /* these two quotes are a hack only */
  case 0x201c: return 0x91; /* these two quotes are a hack only */
  case 0x00A0: return 0xA0; /*  NO-BREAK SPACE */
  case 0x0104: return 0xA1; /*  LATIN CAPITAL LETTER A WITH OGONEK */
  case 0x0138: return 0xA2; /*  LATIN SMALL LETTER KRA */
  case 0x0156: return 0xA3; /*  LATIN CAPITAL LETTER R WITH CEDILLA */
  case 0x00A4: return 0xA4; /*  CURRENCY SIGN */
  case 0x0128: return 0xA5; /*  LATIN CAPITAL LETTER I WITH TILDE */
  case 0x013B: return 0xA6; /*  LATIN CAPITAL LETTER L WITH CEDILLA */
  case 0x00A7: return 0xA7; /*  SECTION SIGN */
  case 0x00A8: return 0xA8; /*  DIAERESIS */
  case 0x0160: return 0xA9; /*  LATIN CAPITAL LETTER S WITH CARON */
  case 0x0112: return 0xAA; /*  LATIN CAPITAL LETTER E WITH MACRON */
  case 0x0122: return 0xAB; /*  LATIN CAPITAL LETTER G WITH CEDILLA */
  case 0x0166: return 0xAC; /*  LATIN CAPITAL LETTER T WITH STROKE */
  case 0x00AD: return 0xAD; /*  SOFT HYPHEN */
  case 0x017D: return 0xAE; /*  LATIN CAPITAL LETTER Z WITH CARON */
  case 0x00AF: return 0xAF; /*  MACRON */
  case 0x00B0: return 0xB0; /*  DEGREE SIGN */
  case 0x0105: return 0xB1; /*  LATIN SMALL LETTER A WITH OGONEK */
  case 0x02DB: return 0xB2; /*  OGONEK */
  case 0x0157: return 0xB3; /*  LATIN SMALL LETTER R WITH CEDILLA */
  case 0x00B4: return 0xB4; /*  ACUTE ACCENT */
  case 0x0129: return 0xB5; /*  LATIN SMALL LETTER I WITH TILDE */
  case 0x013C: return 0xB6; /*  LATIN SMALL LETTER L WITH CEDILLA */
  case 0x02C7: return 0xB7; /*  CARON */
  case 0x00B8: return 0xB8; /*  CEDILLA */
  case 0x0161: return 0xB9; /*  LATIN SMALL LETTER S WITH CARON */
  case 0x0113: return 0xBA; /*  LATIN SMALL LETTER E WITH MACRON */
  case 0x0123: return 0xBB; /*  LATIN SMALL LETTER G WITH CEDILLA */
  case 0x0167: return 0xBC; /*  LATIN SMALL LETTER T WITH STROKE */
  case 0x014A: return 0xBD; /*  LATIN CAPITAL LETTER ENG */
  case 0x017E: return 0xBE; /*  LATIN SMALL LETTER Z WITH CARON */
  case 0x014B: return 0xBF; /*  LATIN SMALL LETTER ENG */
  case 0x0100: return 0xC0; /*  LATIN CAPITAL LETTER A WITH MACRON */
  case 0x00C1: return 0xC1; /*  LATIN CAPITAL LETTER A WITH ACUTE */
  case 0x00C2: return 0xC2; /*  LATIN CAPITAL LETTER A WITH CIRCUMFLEX */
  case 0x00C3: return 0xC3; /*  LATIN CAPITAL LETTER A WITH TILDE */
  case 0x00C4: return 0xC4; /*  LATIN CAPITAL LETTER A WITH DIAERESIS */
  case 0x00C5: return 0xC5; /*  LATIN CAPITAL LETTER A WITH RING ABOVE */
  case 0x00C6: return 0xC6; /*  LATIN CAPITAL LIGATURE AE */
  case 0x012E: return 0xC7; /*  LATIN CAPITAL LETTER I WITH OGONEK */
  case 0x010C: return 0xC8; /*  LATIN CAPITAL LETTER C WITH CARON */
  case 0x00C9: return 0xC9; /*  LATIN CAPITAL LETTER E WITH ACUTE */
  case 0x0118: return 0xCA; /*  LATIN CAPITAL LETTER E WITH OGONEK */
  case 0x00CB: return 0xCB; /*  LATIN CAPITAL LETTER E WITH DIAERESIS */
  case 0x0116: return 0xCC; /*  LATIN CAPITAL LETTER E WITH DOT ABOVE */
  case 0x00CD: return 0xCD; /*  LATIN CAPITAL LETTER I WITH ACUTE */
  case 0x00CE: return 0xCE; /*  LATIN CAPITAL LETTER I WITH CIRCUMFLEX */
  case 0x012A: return 0xCF; /*  LATIN CAPITAL LETTER I WITH MACRON */
  case 0x0110: return 0xD0; /*  LATIN CAPITAL LETTER D WITH STROKE */
  case 0x0145: return 0xD1; /*  LATIN CAPITAL LETTER N WITH CEDILLA */
  case 0x014C: return 0xD2; /*  LATIN CAPITAL LETTER O WITH MACRON */
  case 0x0136: return 0xD3; /*  LATIN CAPITAL LETTER K WITH CEDILLA */
  case 0x00D4: return 0xD4; /*  LATIN CAPITAL LETTER O WITH CIRCUMFLEX */
  case 0x00D5: return 0xD5; /*  LATIN CAPITAL LETTER O WITH TILDE */
  case 0x00D6: return 0xD6; /*  LATIN CAPITAL LETTER O WITH DIAERESIS */
  case 0x00D7: return 0xD7; /*  MULTIPLICATION SIGN */
  case 0x00D8: return 0xD8; /*  LATIN CAPITAL LETTER O WITH STROKE */
  case 0x0172: return 0xD9; /*  LATIN CAPITAL LETTER U WITH OGONEK */
  case 0x00DA: return 0xDA; /*  LATIN CAPITAL LETTER U WITH ACUTE */
  case 0x00DB: return 0xDB; /*  LATIN CAPITAL LETTER U WITH CIRCUMFLEX */
  case 0x00DC: return 0xDC; /*  LATIN CAPITAL LETTER U WITH DIAERESIS */
  case 0x0168: return 0xDD; /*  LATIN CAPITAL LETTER U WITH TILDE */
  case 0x016A: return 0xDE; /*  LATIN CAPITAL LETTER U WITH MACRON */
  case 0x00DF: return 0xDF; /*  LATIN SMALL LETTER SHARP S */
  case 0x0101: return 0xE0; /*  LATIN SMALL LETTER A WITH MACRON */
  case 0x00E1: return 0xE1; /*  LATIN SMALL LETTER A WITH ACUTE */
  case 0x00E2: return 0xE2; /*  LATIN SMALL LETTER A WITH CIRCUMFLEX */
  case 0x00E3: return 0xE3; /*  LATIN SMALL LETTER A WITH TILDE */
  case 0x00E4: return 0xE4; /*  LATIN SMALL LETTER A WITH DIAERESIS */
  case 0x00E5: return 0xE5; /*  LATIN SMALL LETTER A WITH RING ABOVE */
  case 0x00E6: return 0xE6; /*  LATIN SMALL LIGATURE AE */
  case 0x012F: return 0xE7; /*  LATIN SMALL LETTER I WITH OGONEK */
  case 0x010D: return 0xE8; /*  LATIN SMALL LETTER C WITH CARON */
  case 0x00E9: return 0xE9; /*  LATIN SMALL LETTER E WITH ACUTE */
  case 0x0119: return 0xEA; /*  LATIN SMALL LETTER E WITH OGONEK */
  case 0x00EB: return 0xEB; /*  LATIN SMALL LETTER E WITH DIAERESIS */
  case 0x0117: return 0xEC; /*  LATIN SMALL LETTER E WITH DOT ABOVE */
  case 0x00ED: return 0xED; /*  LATIN SMALL LETTER I WITH ACUTE */
  case 0x00EE: return 0xEE; /*  LATIN SMALL LETTER I WITH CIRCUMFLEX */
  case 0x012B: return 0xEF; /*  LATIN SMALL LETTER I WITH MACRON */
  case 0x0111: return 0xF0; /*  LATIN SMALL LETTER D WITH STROKE */
  case 0x0146: return 0xF1; /*  LATIN SMALL LETTER N WITH CEDILLA */
  case 0x014D: return 0xF2; /*  LATIN SMALL LETTER O WITH MACRON */
  case 0x0137: return 0xF3; /*  LATIN SMALL LETTER K WITH CEDILLA */
  case 0x00F4: return 0xF4; /*  LATIN SMALL LETTER O WITH CIRCUMFLEX */
  case 0x00F5: return 0xF5; /*  LATIN SMALL LETTER O WITH TILDE */
  case 0x00F6: return 0xF6; /*  LATIN SMALL LETTER O WITH DIAERESIS */
  case 0x00F7: return 0xF7; /*  DIVISION SIGN */
  case 0x00F8: return 0xF8; /*  LATIN SMALL LETTER O WITH STROKE */
  case 0x0173: return 0xF9; /*  LATIN SMALL LETTER U WITH OGONEK */
  case 0x00FA: return 0xFA; /*  LATIN SMALL LETTER U WITH ACUTE */
  case 0x00FB: return 0xFB; /*  LATIN SMALL LETTER U WITH CIRCUMFLEX */
  case 0x00FC: return 0xFC; /*  LATIN SMALL LETTER U WITH DIAERESIS */
  case 0x0169: return 0xFD; /*  LATIN SMALL LETTER U WITH TILDE */
  case 0x016B: return 0xFE; /*  LATIN SMALL LETTER U WITH MACRON */
  case 0x02D9: return 0xFF; /*  DOT ABOVE */
#endif
}

static void
unicode_latin5(
     char *arg
)
{
  int i;
  static unsigned int latin5_unicode_map1[] = {
    0x0080, 0x0081, 0x201a, 0x0192, 0x201e, 0x2026, 0x2020, 0x2021,  /* 80 */
    0x02c6, 0x2030, 0x0160, 0x2039, 0x0152, 0x008d, 0x008e, 0x008f,  /* 88 */
    0x0090, 0x2018, 0x2019, 0x201c, 0x201d, 0x2022, 0x2013, 0x2014,  /* 90 */
    0x02dc, 0x2122, 0x0161, 0x203a, 0x0153, 0x009d, 0x009e, 0x0178,  /* 98 */
  };
  static unsigned int latin5_unicode_map2[] = {
    0x011e, 0x00d1, 0x00d2, 0x00d3, 0x00d4, 0x00d5, 0x00d6, 0x00d7,  /* D0 */
    0x00d8, 0x00d9, 0x00da, 0x00db, 0x00dc, 0x0130, 0x015e, 0x00df,  /* D8 */
    0x00e0, 0x00e1, 0x00e2, 0x00e3, 0x00e4, 0x00e5, 0x00e6, 0x00e7,  /* E0 direct */
    0x00e8, 0x00e9, 0x00ea, 0x00eb, 0x00ec, 0x00ed, 0x00ee, 0x00ef,  /* E8 direct */
    0x011f, 0x00f1, 0x00f2, 0x00f3, 0x00f4, 0x00f5, 0x00f6, 0x00f7,  /* F0 */
    0x00f8, 0x00f9, 0x00fa, 0x00fb, 0x00fc, 0x0131, 0x015f, 0x00ff,  /* F8 */
  };

  for(i=0; i<=0x7F; i++)
    unicode_map[i] = i;

  for(i=0x80; i<=0x9F; i++)
    unicode_map[i] = latin5_unicode_map1[i-0x80];

  for(i=0xA0; i<=0xCF; i++)
    unicode_map[i] = i;

  for(i=0xD0; i<=0xFF; i++)
    unicode_map[i] = latin5_unicode_map2[i-0xD0];
}

/* a way to select one 256-character plane from Unicode 
 * or other multi-byte encoding
 */

static void
unicode_plane(
     char *arg
)
{
  static unsigned plane;
  int nchars;
  int c1, c2, i;

  if(uni_lang_selected == 0)
    return; /* don't participate in auto-guessing */

  plane = 0; force_pid = force_eid = -1;

  c1 = sscanf(arg, "pid=%d,eid=%d%n", &force_pid, &force_eid, &nchars);
  if(c1 == 2) {
    arg += nchars;
    if(*arg == ',')
      arg++;
  }
  if(arg[0] == '0' && (arg[1]=='x' || arg[1]=='X') ) {
    arg += 2;
    c2 = sscanf(arg, "%x", &plane);
  } else {
    c2 = sscanf(arg, "%d", &plane);
  }

  if( (c1!=2 && c1!=0) || (c1==0 && c2==0) ) {
    fprintf(stderr, "**** option -l plane expects one of the following formats:\n");
    fprintf(stderr, "  -l plane+0xNN - select hexadecimal number of plane of Unicode\n");
    fprintf(stderr, "  -l plane+NN - select decimal number of plane of Unicode\n");
    fprintf(stderr, "  -l plane+pid=N,eid=N - select plane 0 of specified encoding\n");
    fprintf(stderr, "  -l plane+pid=N,eid=N,0xNN - select hex plane of TTF encoding with this PID/EID\n");
    fprintf(stderr, "  -l plane+pid=N,eid=N,NN - select decimal plane of TTF encoding with this PID/EID\n");
    exit(1);
  }

  if(c2!=0) {
    if(strlen(arg) > sizeof(uni_suffix_buf)-2) {
      fprintf(stderr, "**** plane number is too large\n");
    }

    sprintf(uni_suffix_buf, "-%s", arg);
    uni_font_name_suffix = uni_suffix_buf;
  } else {
    uni_font_name_suffix = "";
  }

  plane <<= 8;
  for(i=0; i<=0xFF; i++)
    unicode_map[i] = plane | i;
}

/* look up the 8-bit code by unicode */

int
unicode_rev_lookup(
     int unival
)
{
  int res;

  if( ! IS_UNI_BUCKET(unival) )
    return -1;

  for (res = 0; res < enctabsz; res++)
    if (unicode_map[res] == unival)
      return res;
  return -1;
}

/* mark the buckets for quick lookup */

static void
unicode_prepare_buckets(
  void
)
{
  int i;

  memset(uni_user_buckets, 0, sizeof uni_user_buckets);
  for(i=0; i<enctabsz; i++) {
    if(unicode_map[i] != (unsigned) -1)
      MARK_UNI_BUCKET(unicode_map[i]);
  }
}

/*
 * When we print errors about bad names we want to print these names in
 * some decent-looking form
 */

static char *
nametoprint(
  unsigned char *s
)
{
  static char res[50];
  int c, i;

  for(i=0; ( c =* s )!=0 && i<sizeof(res)-8; s++) {
    if(c < ' ' || c > 126) {
      sprintf(res+i, "\\x%02X", c);
      i+=4;
    } else {
      res[i++] = c;
    }
  }
  if(*s != 0) {
    res[i++] = '.';
    res[i++] = '.';
    res[i++] = '.';
  }
  res[i++] = 0;
  return res;
}

/*
 * Scale the values according to the scale_factor
 */

double
fscale(
      double val
)
{
  return scale_factor * val;
}

int
iscale(
      int val
)
{
  return (int) (val > 0 ? scale_factor * val + 0.5
          : scale_factor * val - 0.5);
}

/*
 * Try to force fixed width of characters
 */

static void
alignwidths(void)
{
  int             i;
  int             n = 0, avg, max = 0, min = 3000, sum = 0, x;

  for (i = 0; i < numglyphs; i++) {
    if (glyph_list[i].flags & GF_USED) {
      x = glyph_list[i].width;

      if (x != 0) {
        if (x < min)
          min = x;
        if (x > max)
          max = x;

        sum += x;
        n++;
      }
    }
  }

  if (n == 0)
    return;

  avg = sum / n;

  WARNING_3 fprintf(stderr, "widths: max=%d avg=%d min=%d\n", max, avg, min);

  /* if less than 5% variation from average */
  /* force fixed width */
  if (20 * (avg - min) < avg && 20 * (max - avg) < avg) {
    for (i = 0; i < numglyphs; i++) {
      if (glyph_list[i].flags & GF_USED)
        glyph_list[i].width = avg;
    }
    fontm.is_fixed_pitch = 1;
  }
}

static void
convert_glyf(
  int  glyphno
)
{
  GLYPH          *g;
  int ncurves;

  g = &glyph_list[glyphno];


  g->scaledwidth = iscale(g->width);

  g->entries = 0;
  g->lastentry = 0;
  g->path = 0;
  if (g->ttf_pathlen != 0) {
    cursw->glpath(glyphno, glyph_list);
    g->lastentry = 0;

    if(ISDBG(BUILDG))
      dumppaths(g, NULL, NULL);

    assertpath(g->entries, __FILE__, __LINE__, g->name);

    fclosepaths(g);
    assertpath(g->entries, __FILE__, __LINE__, g->name);

    /* float processing */
    if(smooth) {
      ffixquadrants(g);
      assertpath(g->entries, __FILE__, __LINE__, g->name);

      fsplitzigzags(g);
      assertpath(g->entries, __FILE__, __LINE__, g->name);

      fforceconcise(g);
      assertpath(g->entries, __FILE__, __LINE__, g->name);

      fstraighten(g);
      assertpath(g->entries, __FILE__, __LINE__, g->name);
    }

    pathtoint(g); 
    /* all processing past this point expects integer path */
    assertpath(g->entries, __FILE__, __LINE__, g->name);

#if 0
    fixcontours(g);
    testfixcvdir(g);
#endif

    /* int processing */
    if (smooth) {
      smoothjoints(g);
      assertpath(g->entries, __FILE__, __LINE__, g->name);
    }

    ncurves = 0;
    {
      GENTRY *ge;
      for(ge = g->entries; ge; ge = ge->next)
        ncurves++;
    }
    if (ncurves > 200) {
      WARNING_3 fprintf(stderr,
      "** Glyph %s is too long, may display incorrectly\n",
        g->name);
    }
  } else {
    /* for buildstems */
    g->flags &= ~GF_FLOAT;
  }
}

static void
handle_gnames(void)
{
  int             i, n, found, c, type;

  /* get the names from the font file */
  ps_fmt_3 = cursw->glnames(glyph_list);

  /* check for names with wrong characters */
  for (n = 0; n < numglyphs; n++) {
    int             c;
    for (i = 0; (c = glyph_list[n].name[i]) != 0; i++) {
      if (!(isalnum(c) || c == '.' || c == '_' || c == '-') 
      || i==0 && isdigit(c)) { /* must not start with a digit */
        WARNING_3 fprintf(stderr, "Glyph %d %s (%s), ",
          n, isdigit(c) ? "name starts with a digit" : 
            "has bad characters in name",
          nametoprint(glyph_list[n].name));
        glyph_list[n].name = malloc(16);
        sprintf(glyph_list[n].name, "_b_%d", n);
        WARNING_3 fprintf(stderr, "changing to %s\n", glyph_list[n].name);
        break;
      }
    }
  }

  if( !ps_fmt_3 ) {
    /* check for duplicate names */
    for (n = 0; n < numglyphs; n++) {
      found = 0;
      for (i = 0; i < n && !found; i++) {
        if (strcmp(glyph_list[i].name, glyph_list[n].name) == 0) {
          if (( glyph_list[n].name = malloc(16) )==0) {
            fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
            exit(255);
          }
          sprintf(glyph_list[n].name, "_d_%d", n);

          /* if the font has no names in it (what the native parser
           * recognises as ps_fmt_3), FreeType returns all the 
           * names as .notdef, so don't complain in this case
           */
          if(strcmp(glyph_list[i].name, ".notdef")) {
            WARNING_3 fprintf(stderr,
              "Glyph %d has the same name as %d: (%s), changing to %s\n",
              n, i,
              glyph_list[i].name,
              glyph_list[n].name);
          }
          found = 1;
        }
      }
    }

  }

  /* start the encoding stuff */
  for (i = 0; i < ENCTABSZ; i++) {
    encoding[i] = -1;
  }

  /* do the 1st round of encoding by name */
  if(!ps_fmt_3 && uni_lang_selected && uni_lang_selected->convbyname) {
    for (n = 0; n < numglyphs; n++) {
      c = uni_lang_selected->convbyname(glyph_list[n].name, 
        uni_lang_arg, UNICONV_BYNAME_BEFORE);
      if(c>=0 && c<ENCTABSZ && encoding[c] == -1)
        encoding[c] = n;
    }
  }

  /* now do the encoding by table */
  if(uni_lang_selected) {
    for(i=0; i < MAXUNITABLES && uni_lang_selected->init[i]; i++) {
      for (n = 0; n < ENCTABSZ; n++)
        unicode_map[n] = -1;
      uni_lang_selected->init[i](uni_lang_arg);
      unicode_prepare_buckets();
      type = cursw->glenc(glyph_list, encoding, unicode_map);
      if( type == 0 )
        /* if we have an 8-bit encoding we don't need more tries */
        break;
    }
  } else {
    /* language is unknown, try the first table of each */
    for(i=0; i < sizeof uni_lang/(sizeof uni_lang[0]); i++) {
      if(uni_lang[i].init[0] == NULL)
        continue;
      for (n = 0; n < ENCTABSZ; n++)
        unicode_map[n] = -1;
      uni_lang[i].init[0](uni_lang_arg);
      unicode_prepare_buckets();
      type = cursw->glenc(glyph_list, encoding, unicode_map);
      if( type == 0 )
        /* if we have an 8-bit encoding we don't need more tries */
        break;
    }
  }

  if (ps_fmt_3) {
    /* get rid of the old names, they are all "UNKNOWN" anyawy */
    for (i = 0; i < numglyphs; i++) {
      glyph_list[i].name = 0;
    }
    if(type == 0) { 
      /* 8-bit - give 8859/1 names to the first 256 glyphs */
      for (i = 0; i < 256; i++) { /* here 256, not ENCTABSZ */
        if (encoding[i] > 0) {
          glyph_list[encoding[i]].name = Fmt3Encoding[i];
        }
      }
    } else if(type == 1) {
      /* Unicode - give 8859/1 names to the first 256 glyphs of Unicode */
      for (n = 0; n < 256; n++) { /* here 256, not ENCTABSZ */
        i = unicode_rev_lookup(n);
        if (i>=0 && encoding[i] > 0) {
          glyph_list[encoding[i]].name = Fmt3Encoding[i];
        }
      }
    } /* for other types of encodings just give generated names */
    /* assign unique names to the rest of the glyphs */
    for (i = 0; i < numglyphs; i++) {
      if (glyph_list[i].name == 0) {
        if (( glyph_list[i].name = malloc(16) )==0) {
          fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
          exit(255);
        }
        sprintf(glyph_list[i].name, "_d_%d", i);
      }
    }
  }

  /* do the 2nd round of encoding by name */
  if(uni_lang_selected && uni_lang_selected->convbyname) {
    for (n = 0; n < numglyphs; n++) {
      c = uni_lang_selected->convbyname(glyph_list[n].name, 
        uni_lang_arg, UNICONV_BYNAME_AFTER);
      if(c>=0 && c<ENCTABSZ && encoding[c] == -1)
        encoding[c] = n;
    }
  }
  /* all the encoding things are done */

  for (i = 0; i < ENCTABSZ; i++)
    if(encoding[i] == -1) {
      /* check whether this character might be a duplicate 
       * (in which case it would be missed by unicode_rev_lookup())
       */
      c = unicode_map[i];
      if((type != 0 || forcemap) && c != -1) {
        for(n = 0; n < i; n++) {
          if(unicode_map[n] == c) {
            encoding[i] = encoding[n];
          }
        }
      }
      if(encoding[i] == -1) /* still not found, defaults to .notdef */
        encoding[i] = 0;
    }

  for (i = 0; i < 256; i++) /* here 256, not ENCTABSZ */
    glyph_list[encoding[i]].char_no = i;

  /* enforce two special cases defined in TTF manual */
  if(numglyphs > 0)
    glyph_list[0].name = ".notdef";
  if(numglyphs > 1)
    glyph_list[1].name = ".null";

   for (i = 0; i < ENCTABSZ; i++) {
     if ((encoding[i] != 0) && glyph_rename[i]) {
         glyph_list[encoding[i]].name = glyph_rename[i];
     }
   }
   
}

/* duplicate a string with counter to a 0-terminated string,
 * and by the way filter out the characters that won't look good
 * in the Postscript strings or comments; limit the length
 * to a reasonable amount.
 */

char *
dupcnstring(
  unsigned char *s,
  int len
)
{
  char *res, *out;
  int i, c;
  static int warned=0;

  if(len > 255) {
    WARNING_1 fprintf(stderr, "Some font name strings are longer than 255 characters, cut down\n");
    len = 255;
  }

  if(( res = malloc(len+1) )==NULL) {
    fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
    exit(255);
  }

  out = res;
  for(i=0; i<len; i++) {
    c = s[i];
    if( c>=' ' && c!=127) {
      /* translate the inconvenient chacracters */
      if( c== '(' )
        c = '[';
      else if( c== ')' )
        c = ']';
      *out++ = c;
    } else if( c=='\n' || c=='\r' ) {
      WARNING_1 fprintf(stderr, "Some font name strings contain end of line or Unicode, cut down\n");
      *out = 0;
      return res;
    } else if(!warned) {
      warned=1;
      WARNING_1 fprintf(stderr, "Some font name strings are in Unicode, may not show properly\n");
    }
  }
  *out = 0;
  return res;
}

static void
usage(void)
{

#ifdef _GNU_SOURCE
#  define fplop(txt)  fputs(txt, stderr);
#else
#  define fplop(txt)
#endif

  fputs("Use:\n", stderr);
  fputs("ttf2pt1 [-<opts>] [-l language | -L file] <ttf-file> [<fontname>]\n", stderr);
  fputs("  or\n", stderr);
  fputs("ttf2pt1 [-<opts>] [-l language | -L file] <ttf-file> -\n", stderr);
  fputs("  or\n", stderr);
  fputs("ttf2pt1 [-<opts>] [-l language | -L file] <ttf-file> - | t1asm > <pfa-file>\n", stderr);

  fplop("\n");
  fplop("This build supports both short and long option names,\n");
  fplop("the long options are listed before corresponding short ones\n");

  fplop(" --all-glyphs\n");
  fputs("  -a - include all glyphs, even those not in the encoding table\n", stderr);
  fplop(" --pfb\n");
  fputs("  -b - produce a compressed .pfb file\n", stderr);
  fplop(" --debug dbg_suboptions\n");
  fputs("  -d dbg_suboptions - debugging options, run ttf2pt1 -d? for help\n", stderr);
  fplop(" --encode\n");
  fputs("  -e - produce a fully encoded .pfa file\n", stderr);
  fplop(" --force-unicode\n");
  fputs("  -F - force use of Unicode encoding even if other MS encoding detected\n", stderr); 
  fplop(" --generate suboptions\n");
  fputs("  -G suboptions - control the file generation, run ttf2pt1 -G? for help\n", stderr);
  fplop(" --language language\n");
  fputs("  -l language - convert Unicode to specified language, run ttf2pt1 -l? for list\n", stderr);
  fplop(" --language-map file\n");
  fputs("  -L file - convert Unicode according to encoding description file\n", stderr);
  fplop(" --limit <type>=<value>\n");
  fputs("  -m <type>=<value> - set maximal limit of given type to value, types:\n", stderr);
  fputs("      h - maximal hint stack depth in the PostScript interpreter\n", stderr);
  fplop(" --processing suboptions\n");
  fputs("  -O suboptions - control outline processing, run ttf2pt1 -O? for help\n", stderr);
  fplop(" --parser name\n");
  fputs("  -p name - use specific front-end parser, run ttf2pt1 -p? for list\n", stderr);
  fplop(" --uid id\n");
  fputs("  -u id - use this UniqueID, -u A means autogeneration\n", stderr);
  fplop(" --vertical-autoscale size\n");
  fputs("  -v size - scale the font to make uppercase letters >size/1000 high\n", stderr);
  fplop(" --version\n");
  fputs("  -V - print ttf2pt1 version number\n", stderr);
  fplop(" --warning number\n");
  fputs("  -W number - set the level of permitted warnings (0 - disable)\n", stderr);
  fputs("Obsolete options (will be removed in future releases):\n", stderr);
  fplop(" --afm\n");
  fputs("  -A - write the .afm file to STDOUT instead of the font, now -GA\n", stderr);
  fputs("  -f - don't try to guess the value of the ForceBold hint, now -Ob\n", stderr);
  fputs("  -h - disable autogeneration of hints, now -Oh\n", stderr);
  fputs("  -H - disable hint substitution, now -Ou\n", stderr);
  fputs("  -o - disable outline optimization, now -Oo\n", stderr);
  fputs("  -s - disable outline smoothing, now -Os\n", stderr);
  fputs("  -t - disable auto-scaling to 1000x1000 standard matrix, now -Ot\n", stderr);
  fputs("  -w - correct the glyph widths (use only for buggy fonts), now -OW\n", stderr);
  fputs("With no <fontname>, write to <ttf-file> with suffix replaced.\n", stderr);
  fputs("The last '-' means 'use STDOUT'.\n", stderr);

#undef fplop

}

static void
printversion(void)
{
  fprintf(stderr, "ttf2pt1 %s\n", TTF2PT1_VERSION);
}

/* initialize a table of suboptions */
static void
init_subo_tbl(
  struct subo_case *tbl
)
{
  int i;

  for(i=0; tbl[i].disbl != 0; i++) {
    tbl[i].disbl = tolower(tbl[i].disbl);
    tbl[i].enbl = toupper(tbl[i].disbl);
    *(tbl[i].valp) = tbl[i].dflt;
  }
}
  
/* print the default value of the suboptions */
static void
print_subo_dflt(
  FILE *f,
  struct subo_case *tbl
)
{
  int i;

  for(i=0; tbl[i].disbl != 0; i++) {
    if(tbl[i].dflt)
      putc(tbl[i].enbl, f);
    else
      putc(tbl[i].disbl, f);
  }
}
  
/* print the usage message for the suboptions */
static void
print_subo_usage(
  FILE *f,
  struct subo_case *tbl
)
{
  int i;

  fprintf(f,"The lowercase suboptions disable features, corresponding\n");
  fprintf(f,"uppercase suboptions enable them. The supported suboptions,\n");
  fprintf(f,"their default states and the features they control are:\n");
  for(i=0; tbl[i].disbl != 0; i++) {
    fprintf(f,"   %c/%c - [%s] %s\n", tbl[i].disbl, tbl[i].enbl,
      tbl[i].dflt ? "enabled" : "disabled", tbl[i].descr);
  }
}

/* find and set the entry according to suboption,
 * return the found entry (or if not found return NULL)
 */
struct subo_case *
set_subo(
  struct subo_case *tbl,
  int subopt
)
{
  int i;

  for(i=0; tbl[i].disbl != 0; i++) {
    if(subopt == tbl[i].disbl) {
      *(tbl[i].valp) = 0;
      return &tbl[i];
    } else if(subopt == tbl[i].enbl) {
      *(tbl[i].valp) = 1;
      return &tbl[i];
    } 
  }
  return NULL;
}

  
int
main(
     int argc,
     char **argv
)
{
  int             i, j;
  time_t          now;
  char            filename[4096];
  int             c,nchars,nmetrics;
  int             ws;
  int             forcebold= -1; /* -1 means "don't know" */
  char           *lang;
  int             oc;
  int             subid;
  char           *cmdline;
#ifdef _GNU_SOURCE
#  define ttf2pt1_getopt(a, b, c, d, e)  getopt_long(a, b, c, d, e)
  static struct option longopts[] = {
    { "afm", 0, NULL, 'A' },
    { "all-glyphs", 0, NULL, 'a' },
    { "pfb", 0, NULL, 'b' },
    { "debug", 1, NULL, 'd' },
    { "encode", 0, NULL, 'e' },
    { "force-unicode", 0, NULL, 'F' },
    { "generate", 1, NULL, 'G' },
    { "language", 1, NULL, 'l' },
    { "language-map", 1, NULL, 'L' },
    { "limit", 1, NULL, 'm' },
    { "processing", 1, NULL, 'O' },
    { "parser", 1, NULL, 'p' },
    { "uid", 1, NULL, 'u' },
    { "vertical-autoscale", 1, NULL, 'v' },
    { "version", 0, NULL, 'V' },
    { "warning", 1, NULL, 'W' },
    { NULL, 0, NULL, 0 }
  };
#else
#  define ttf2pt1_getopt(a, b, c, d, e)  getopt(a, b, c)
#endif
  /* table of Outline Processing (may think also as Optimization) options */
  static struct subo_case opotbl[] = {
    { 'b', 0/*auto-set*/, &trybold, 1, "guessing of the ForceBold hint" },
    { 'h', 0/*auto-set*/, &hints, 1, "autogeneration of hints" },
    { 'u', 0/*auto-set*/, &subhints, 1, "hint substitution technique" },
    { 'o', 0/*auto-set*/, &optimize, 1, "space optimization of font files" },
    { 's', 0/*auto-set*/, &smooth, 1, "smoothing and repair of outlines" },
    { 't', 0/*auto-set*/, &transform, 1, "auto-scaling to the standard matrix 1000x1000" },
    { 'w', 0/*auto-set*/, &correctwidth, 0, "correct the glyph widths (use only for buggy fonts)" },
    { 'v', 0/*auto-set*/, &vectorize, 0, "vectorize (trace) the bitmaps" },
#ifdef USE_AUTOTRACE
    { 'z', 0/*auto-set*/, &use_autotrace, 0, "use the autotrace library on bitmaps (works badly)" },
#endif /*USE_AUTOTRACE*/
    { 0, 0, 0, 0, 0} /* terminator */
  };
  /* table of the File Generation options */
  static struct subo_case fgotbl[] = {
    { 'f', 0/*auto-set*/, &gen_pfa, 1, "generate the font file (.t1a, .pfa or .pfb)" },
    { 'a', 0/*auto-set*/, &gen_afm, 1, "generate the Adobe metrics file (.afm)" },
    { 'u', 0/*auto-set*/, &gen_ufm, 1, "generate the Unicode metrics file (.ufm)" },
    { 'e', 0/*auto-set*/, &gen_dvienc, 0, "generate the dvips encoding file (.enc)" },
    { 0, 0, 0, 0, 0} /* terminator */
  };
  int *genlast = NULL;


  init_subo_tbl(opotbl); /* initialize sub-options of -O */
  init_subo_tbl(fgotbl); /* initialize sub-options of -G */

  /* save the command line for the record 
   * (we don't bother about escaping the shell special characters)
   */

  j = 0;
  for(i=1; i<argc; i++) {
    j += strlen(argv[i])+1;
  }
  if ((cmdline = malloc(j+1)) == NULL) {
    fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
    exit(255);
  }
  cmdline[0] = 0;
  for(i=1; i<argc; i++) {
    strcat(cmdline, argv[i]);
    strcat(cmdline, " ");
  }
  for(i=0; (j=cmdline[i])!=0; i++)
    if(j == '\n')
      cmdline[i] = ' ';


  while(( oc=ttf2pt1_getopt(argc, argv, "FaoebAsthHfwVv:p:l:d:u:L:m:W:O:G:",
      longopts, NULL) )!= -1) {
    switch(oc) {
    case 'W':
      if(sscanf(optarg, "%d", &warnlevel) < 1 || warnlevel < 0) {
        fprintf(stderr, "**** warning level must be a positive number\n");
        exit(1);
      }
      break;
    case 'F':
      forcemap = 1;
      break;
    case 'o':
      fputs("Warning: option -o is obsolete, use -Oo instead\n", stderr);
      optimize = 0;
      break;
    case 'e':
      encode = 1;
      break;
    case 'b':
      encode = pfbflag = 1;
      break;
    case 'A':
      fputs("Warning: option -A is obsolete, use -GA instead\n", stderr);
      wantafm = 1;
      break;
    case 'a':
      allglyphs = 1;
      break;
    case 's':
      fputs("Warning: option -s is obsolete, use -Os instead\n", stderr);
      smooth = 0;
      break;
    case 't':
      fputs("Warning: option -t is obsolete, use -Ot instead\n", stderr);
      transform = 0;
      break;
    case 'd':
      for(i=0; optarg[i]!=0; i++)
        switch(optarg[i]) {
        case 'a':
          absolute = 1;
          break;
        case 'r':
          reverse = 0;
          break;
        default:
          if (optarg[i] != '?')
            fprintf(stderr, "**** Unknown debugging option '%c' ****\n", optarg[i]);
          fputs("The recognized debugging options are:\n", stderr);
          fputs("  a - enable absolute coordinates\n", stderr);
          fputs("  r - do not reverse font outlines directions\n", stderr);
          exit(1);
          break;
        };
      break;
    case 'm':
    {
      char subopt;
      int val;

      if(sscanf(optarg, "%c=%d", &subopt, &val) !=2) {
        fprintf(stderr, "**** Misformatted maximal limit ****\n");
        fprintf(stderr, "spaces around the equal sign are not allowed\n");
        fprintf(stderr, "good examples: -mh=100 -m h=100\n");
        fprintf(stderr, "bad examples: -mh = 100 -mh= 100\n");
        exit(1);
        break;
      }
      switch(subopt) {
      case 'h':
        max_stemdepth = val;
        break;
      default:
        if (subopt != '?')
          fprintf(stderr, "**** Unknown limit type '%c' ****\n", subopt);
        fputs("The recognized limit types are:\n", stderr);
        fputs("  h - maximal hint stack depth in the PostScript interpreter\n", stderr);
        exit(1);
        break;
      }
      break;
    }
    case 'O':
    {
      char *p;
      for(p=optarg; *p != 0; p++) {
        if(set_subo(opotbl, *p) == NULL) { /* found no match */
          if (*p != '?')
            fprintf(stderr, "**** Unknown outline processing suboption '%c' ****\n", *p);
          fprintf(stderr,"The general form of the outline processing option is:\n");
          fprintf(stderr,"   -O suboptions\n");
          fprintf(stderr,"(To remember easily -O may be also thought of as \"optimization\").\n");
          print_subo_usage(stderr, opotbl);
          fprintf(stderr, "The default state corresponds to the option -O ");
          print_subo_dflt(stderr, opotbl);
          fprintf(stderr, "\n");
          exit(1);
        }
      }
      break;
    }
    case 'G':
    {
      char *p;
      struct subo_case *s;

      for(p=optarg; *p != 0; p++) {
        if(( s = set_subo(fgotbl, *p) )==NULL) { /* found no match */
          if (*p != '?')
            fprintf(stderr, "**** Unknown outline processing suboption '%c' ****\n", *p);
          fprintf(stderr,"The general form of the file generation option is:\n");
          fprintf(stderr,"   -G suboptions\n");
          print_subo_usage(stderr, fgotbl);
          fprintf(stderr, "The default state corresponds to the option -G ");
          print_subo_dflt(stderr, fgotbl);
          fprintf(stderr, "\n");
          fprintf(stderr, "If the result is written to STDOUT, the last specified enabling suboption of -G\n");
          fprintf(stderr, "selects the file to be written to STDOUT (the font file by default).\n");
          exit(1);
        }
        if( *(s->valp) )
          genlast = s->valp;
      }
      break;
    }
    case 'h':
      fputs("Warning: option -h is obsolete, use -Oh instead\n", stderr);
      hints = 0;
      break;
    case 'H':
      fputs("Warning: meaning of option -H has been changed to its opposite\n", stderr);
      fputs("Warning: option -H is obsolete, use -Ou instead\n", stderr);
      subhints = 0;
      break;
    case 'f':
      fputs("Warning: option -f is obsolete, use -Ob instead\n", stderr);
      trybold = 0;
      break;
    case 'w':
      fputs("Warning: option -w is obsolete, use -OW instead\n", stderr);
      correctwidth = 1;
      break;
    case 'u':
      if(wantuid) {
        fprintf(stderr, "**** UniqueID may be specified only once ****\n");
        exit(1);
      }
      wantuid = 1; 
      if(optarg[0]=='A' && optarg[1]==0)
        strUID=0; /* will be generated automatically */
      else {
        strUID=optarg;
        for(i=0; optarg[i]!=0; i++)
          if( !isdigit(optarg[i]) ) {
            fprintf(stderr, "**** UniqueID must be numeric or A for automatic ****\n");
            exit(1);
          }
      }
      break;
    case 'v':
      correctvsize = atoi(optarg);
      if(correctvsize <= 0 && correctvsize > 1000) {
        fprintf(stderr, "**** wrong vsize '%d', ignored ****\n", correctvsize);
        correctvsize=0;
      }
      break;
    case 'p':
      if(cursw!=0) {
        fprintf(stderr, "**** only one front-end parser be used ****\n");
        exit(1);
      }

      { /* separate parser from parser-specific argument */
        char *p = strchr(optarg, LANG_ARG_SEP);
        if(p != 0) {
          *p = 0;
          front_arg = p+1;
        } else
          front_arg = "";
      }
      for(i=0; frontswtab[i] != NULL; i++)
        if( !strcmp(frontswtab[i]->name, optarg) ) {
          cursw = frontswtab[i];
          break;
        }

      if(cursw==0) {
        if (strcmp(optarg, "?"))
          fprintf(stderr, "**** unknown front-end parser '%s' ****\n", optarg);
        fputs("the following front-ends are supported now:\n", stderr);
        for(i=0; frontswtab[i] != NULL; i++) {
          fprintf(stderr,"  %s (%s)\n   file suffixes: ", 
            frontswtab[i]->name,
            frontswtab[i]->descr ? frontswtab[i]->descr : "no description"
          );
          for(j=0; j<MAXSUFFIX; j++)
            if(frontswtab[i]->suffix[j])
              fprintf(stderr, "%s ", frontswtab[i]->suffix[j]);
          fprintf(stderr, "\n");
        }
        exit(1);
      }
      break;
    case 'l':
      if(uni_lang_selected!=0) {
        fprintf(stderr, "**** only one language option may be used ****\n");
        exit(1);
      }

      { /* separate language from language-specific argument */
        char *p = strchr(optarg, LANG_ARG_SEP);
        if(p != 0) {
          *p = 0;
          uni_lang_arg = p+1;
        } else
          uni_lang_arg = "";
      }
      for(i=0; i < sizeof uni_lang/(sizeof uni_lang[0]); i++)
        if( !strcmp(uni_lang[i].name, optarg) ) {
          uni_lang_selected = &uni_lang[i];
          uni_sample = uni_lang[i].sample_upper;
          break;
        }

      if(uni_lang_selected==0) {
        if (strcmp(optarg, "?"))
          fprintf(stderr, "**** unknown language '%s' ****\n", optarg);
        fputs("       the following languages are supported now:\n", stderr);
        for(i=0; i < sizeof uni_lang/(sizeof uni_lang[0]); i++)
          fprintf(stderr,"         %s (%s)\n", 
            uni_lang[i].name,
            uni_lang[i].descr ? uni_lang[i].descr : "no description"
          );
        exit(1);
      }
      break;
    case 'L':
      if(uni_lang_selected!=0) {
        fprintf(stderr, "**** only one language option may be used ****\n");
        exit(1);
      }
      uni_lang_selected = &uni_lang_user;
      uni_lang_arg = optarg;
      break;
    case 'V':
      printversion();
      exit(0);
      break;
    default:
      usage();
      exit(1);
      break;
    }
  }
  argc-=optind-1; /* the rest of code counts from argv[0] */
  argv+=optind-1;

  if (absolute && encode) {
    fprintf(stderr, "**** options -a and -e are incompatible ****\n");
    exit(1);
  }
        if ((argc != 2) && (argc != 3)) {
    usage();
    exit(1);
  }

  /* try to guess the language by the locale used */
  if(uni_lang_selected==0 && (lang=getenv("LANG"))!=0 ) {
    for(i=0; i < sizeof uni_lang/sizeof(struct uni_language); i++) {
      if( !strncmp(uni_lang[i].name, lang, strlen(uni_lang[i].name)) ) {
        uni_lang_selected = &uni_lang[i];
        goto got_a_language;
      }
    }
    /* no full name ? try aliases */
    for(i=0; i < sizeof uni_lang/sizeof(struct uni_language); i++) {
      for(c=0; c<MAXUNIALIAS; c++)
        if( uni_lang[i].alias[c]!=0
        && !strncmp(uni_lang[i].alias[c], lang, strlen(uni_lang[i].alias[c])) ) {
          uni_lang_selected = &uni_lang[i];
          goto got_a_language;
        }
    }
  got_a_language:
    if(uni_lang_selected!=0) {
      WARNING_1 fprintf(stderr, "Using language '%s' for Unicode fonts\n", uni_lang[i].name);
      uni_sample = uni_lang[i].sample_upper;
    }
  }

  /* try to guess the front-end parser by the file name suffix */
  if(cursw==0) {
    char *p = strrchr(argv[1], '.');
    char *s;

    if(p!=0 && (s = strdup(p+1))!=0) {
      for(p=s; *p; p++)
        *p = tolower(*p);
      p = s;

      for(i=0; frontswtab[i] != 0 && cursw == 0; i++) {
        for(j=0; j<MAXSUFFIX; j++)
          if(frontswtab[i]->suffix[j]
          && !strcmp(p, frontswtab[i]->suffix[j]) ) {
            cursw = frontswtab[i];
            WARNING_1 fprintf(stderr, "Auto-detected front-end parser '%s'\n",
              cursw->name);
            WARNING_1 fprintf(stderr, " (use ttf2pt1 -p? to get the full list of available front-ends)\n");
            break;
          }
      }
      free(s);
    }

    if(cursw==0) {
      cursw = frontswtab[0];
      WARNING_1 fprintf(stderr, "Can't detect front-end parser, using '%s' by default\n", 
        cursw->name);
      WARNING_1 fprintf(stderr, " (use ttf2pt1 -p? to get the full list of available front-ends)\n");
    }
  }

  /* open the input file */
  cursw->open(argv[1], front_arg);

  /* Get base name of output file (if not specified)
   * by removing (known) suffixes
   */
  if (argc == 2) {
    char *p;
    argv[2] = strdup (argv[1]);
    p = strrchr(argv[2], '.');
    if (p != NULL)
      for (j = 0; (j < MAXSUFFIX) && (cursw->suffix[j]); j++)
        if (!strcmp(p+1, cursw->suffix[j])) {
          *p = '\0';
          break;
        }
  }

  if ((null_file = fopen(BITBUCKET, "w")) == NULL) {
    fprintf(stderr, "**** Cannot open %s ****\n",
      BITBUCKET);
    exit(1);
  }

  if (argv[2][0] == '-' && argv[2][1] == 0) {
#ifdef WINDOWS
    if(encode) {
      fprintf(stderr, "**** can't write encoded file to stdout ***\n");
      exit(1);
    }
#endif /* WINDOWS */
    pfa_file = ufm_file = afm_file = dvienc_file = null_file;

    if(wantafm || genlast == &gen_afm) { /* print .afm instead of .pfa */
      afm_file=stdout;
    } else if(genlast == &gen_dvienc) { /* print .enc instead of .pfa */
      dvienc_file=stdout;
    } else {
      pfa_file=stdout;
    }
  } else {
#ifndef WINDOWS
    snprintf(filename, sizeof filename, "%s.%s", argv[2], encode ? (pfbflag ? "pfb" : "pfa") : "t1a" );
#else /* WINDOWS */
    snprintf(filename, sizeof filename, "%s.t1a", argv[2]);
#endif /* WINDOWS */
    if(gen_pfa) {
      if ((pfa_file = fopen(filename, "w+b")) == NULL) {
        fprintf(stderr, "**** Cannot create %s ****\n", filename);
        exit(1);
      } else {
        WARNING_2 fprintf(stderr, "Creating file %s\n", filename);
      }
    } else
      pfa_file = null_file;

    if(gen_ufm) {
      snprintf(filename, sizeof filename, "%s.ufm", argv[2]) ;
      if ((ufm_file = fopen(filename, "w+")) == NULL) {
        fprintf(stderr, "**** Cannot create %s ****\n", filename);
        exit(1);
      }
    } else
      ufm_file = null_file;

    if(gen_afm) {
      snprintf(filename, sizeof filename, "%s.afm", argv[2]) ;
      if ((afm_file = fopen(filename, "w+")) == NULL) {
        fprintf(stderr, "**** Cannot create %s ****\n", filename);
        exit(1);
      }
    } else
      afm_file = null_file;

    if(gen_dvienc) {
      snprintf(filename, sizeof filename, "%s.enc", argv[2]) ;
      if ((dvienc_file = fopen(filename, "w+")) == NULL) {
        fprintf(stderr, "**** Cannot create %s ****\n", filename);
        exit(1);
      }
    } else
      dvienc_file = null_file;
  }

  /*
   * Now check whether we want a fully encoded .pfa file
   */
#ifndef WINDOWS
  if (encode && pfa_file != null_file) {
    int             p[2];
    extern FILE    *ifp, *ofp;  /* from t1asm.c */

    ifp=stdin;
    ofp=stdout;

    if (pipe(p) < 0) {
      perror("**** Cannot create pipe ****\n");
      exit(1);
    }
    ofp = pfa_file;
    ifp = fdopen(p[0], "r");
    if (ifp == NULL) {
      perror("**** Cannot use pipe for reading ****\n");
      exit(1);
    }
    pfa_file = fdopen(p[1], "w");
    if (pfa_file == NULL) {
      perror("**** Cannot use pipe for writing ****\n");
      exit(1);
    }
    switch (fork()) {
    case -1:
      perror("**** Cannot fork the assembler process ****\n");
      exit(1);
    case 0:  /* child */
      fclose(pfa_file);
      exit(runt1asm(pfbflag));
    default: /* parent */
      fclose(ifp); fclose(ofp);
    }
  }
#endif /* WINDOWS */

  numglyphs = cursw->nglyphs();

  WARNING_3 fprintf(stderr, "numglyphs = %d\n", numglyphs);

  glyph_list = (GLYPH *) calloc(numglyphs,  sizeof(GLYPH));

  /* initialize non-0 fields */
  for (i = 0; i < numglyphs; i++) {
    int j;
    GLYPH *g;

    g = &glyph_list[i];
    g->char_no = -1;
    for (j = 0; j < GLYPH_MAX_ENCODINGS; j++ ) {
      g->orig_code[j] = -1;
    }
    g->name = "UNKNOWN";
    g->flags = GF_FLOAT; /* we start with float representation */
  }

  handle_gnames();

  cursw->glmetrics(glyph_list);
  cursw->fnmetrics(&fontm);
 
  original_scale_factor = 1000.0 / (double) fontm.units_per_em;

  if(transform == 0)
    scale_factor = 1.0; /* don't transform */
  else
    scale_factor = original_scale_factor;

  if(correctvsize && uni_sample!=0) { /* only for known languages */
    /* try to adjust the scale factor to make a typical
     * uppercase character of hight at least (correctvsize), this
     * may improve the appearance of the font but also
     * make it weird, use with caution
     */
    int ysz;

    ysz = iscale(glyph_list[encoding[uni_sample]].yMax);
    if( ysz<correctvsize ) {
      scale_factor *= (double)correctvsize / ysz;
    }
  }

  if(allglyphs) {
    for (i = 0; i < numglyphs; i++) {
      glyph_list[i].flags |= GF_USED;
    }
  } else {
    for (i = 0; i < ENCTABSZ; i++) {
      glyph_list[encoding[i]].flags |= GF_USED;
    }

    /* also always include .notdef */
    for (i = 0; i < numglyphs; i++) 
      if(!strcmp(glyph_list[i].name, ".notdef")) {
        glyph_list[i].flags |= GF_USED;
        break;
      }
  }

  for (i = 0; i < numglyphs; i++) {
    if (glyph_list[i].flags & GF_USED) {
      DBG_TO_GLYPH(&glyph_list[i]);
      convert_glyf(i);
      DBG_FROM_GLYPH(&glyph_list[i]);
    }
  }

  italic_angle = fontm.italic_angle;

  if (italic_angle > 45.0 || italic_angle < -45.0)
    italic_angle = 0.0;  /* consider buggy */

  if (hints) {
    findblues();
    for (i = 0; i < numglyphs; i++) {
      if (glyph_list[i].flags & GF_USED) {
        DBG_TO_GLYPH(&glyph_list[i]);
        buildstems(&glyph_list[i]);
        assertpath(glyph_list[i].entries, __FILE__, __LINE__, glyph_list[i].name);
        DBG_FROM_GLYPH(&glyph_list[i]);
      }
    }
    stemstatistics();
  } else {
    for(i=0; i<4; i++)
      bbox[i] = iscale(fontm.bbox[i]);
  }
  /* don't touch the width of fixed width fonts */
  if( fontm.is_fixed_pitch )
    correctwidth=0;
  docorrectwidth(); /* checks correctwidth inside */
  if (reverse)
    for (i = 0; i < numglyphs; i++) {
      if (glyph_list[i].flags & GF_USED) {
        DBG_TO_GLYPH(&glyph_list[i]);
        reversepaths(&glyph_list[i]);
        assertpath(glyph_list[i].entries, __FILE__, __LINE__, glyph_list[i].name);
        DBG_FROM_GLYPH(&glyph_list[i]);
      }
    }


#if 0
  /*
  ** It seems to bring troubles. The problem is that some
  ** styles of the font may be recognized as fixed-width
  ** while other styles of the same font as proportional.
  ** So it's better to be commented out yet.
  */
  if (tryfixed) 
    alignwidths();
#endif

  if(trybold) {
    forcebold = fontm.force_bold;
  }

  fprintf(pfa_file, "%%!PS-AdobeFont-1.0: %s %s\n", fontm.name_ps, fontm.name_copyright);
  time(&now);
  fprintf(pfa_file, "%%%%CreationDate: %s", ctime(&now));
  fprintf(pfa_file, "%% Converted by ttf2pt1 %s/%s\n", TTF2PT1_VERSION, cursw->name);
  fprintf(pfa_file, "%% Args: %s\n", cmdline);
  fprintf(pfa_file, "%%%%EndComments\n");
  fprintf(pfa_file, "12 dict begin\n/FontInfo 9 dict dup begin\n");

  WARNING_3 fprintf(stderr, "FontName %s%s\n", fontm.name_ps, uni_font_name_suffix);


  fprintf(pfa_file, "/version (%s) readonly def\n", fontm.name_version);

  fprintf(pfa_file, "/Notice (%s) readonly def\n", fontm.name_copyright);

  fprintf(pfa_file, "/FullName (%s) readonly def\n", fontm.name_full);
  fprintf(pfa_file, "/FamilyName (%s) readonly def\n", fontm.name_family);

  if(wantuid) {
    if(strUID)
      fprintf(pfa_file, "/UniqueID %s def\n", strUID);
    else {
      numUID=0;
      for(i=0; fontm.name_full[i]!=0; i++) {
        numUID *= 37; /* magic number, good for hash */
        numUID += fontm.name_full[i]-' ';
        /* if the name is long the first chars
         * may be lost forever, so re-insert
         * them thus making kind of CRC
         */
        numUID += (numUID>>24) & 0xFF;
      }
      /* the range for private UIDs is 4 000 000 - 4 999 999 */
      fprintf(pfa_file, "/UniqueID %lu def\n", numUID%1000000+4000000);
    }
  }

  fprintf(pfa_file, "/Weight (%s) readonly def\n", fontm.name_style);

  fprintf(pfa_file, "/ItalicAngle %f def\n", italic_angle);
  fprintf(pfa_file, "/isFixedPitch %s def\n",
    fontm.is_fixed_pitch ? "true" : "false");

  /* we don't print out the unused glyphs */
  nchars = 0;
  for (i = 0; i < numglyphs; i++) {
    if (glyph_list[i].flags & GF_USED) {
      nchars++;
    }
  }

    fprintf(afm_file, "StartFontMetrics 4.1\n");
    fprintf(afm_file, "FontName %s%s\n", fontm.name_ps, uni_font_name_suffix);
    fprintf(afm_file, "FullName %s\n", fontm.name_full);
    fprintf(afm_file, "Notice %s\n", fontm.name_copyright);
    fprintf(afm_file, "EncodingScheme FontSpecific\n");
    fprintf(afm_file, "FamilyName %s\n", fontm.name_family);
    fprintf(afm_file, "Weight %s\n", fontm.name_style);
    fprintf(afm_file, "Version %s\n", fontm.name_version);
    fprintf(afm_file, "Characters %d\n", nchars);
    fprintf(afm_file, "ItalicAngle %.1f\n", italic_angle);

    fprintf(afm_file, "Ascender %d\n", iscale(fontm.ascender));
    fprintf(afm_file, "Descender %d\n", iscale(fontm.descender));

    fprintf(ufm_file, "StartFontMetrics 4.1\n");
    fprintf(ufm_file, "FontName %s%s\n", fontm.name_ps, uni_font_name_suffix);
    fprintf(ufm_file, "FullName %s\n", fontm.name_full);
    fprintf(ufm_file, "Notice %s\n", fontm.name_copyright);
    fprintf(ufm_file, "EncodingScheme FontSpecific\n");
    fprintf(ufm_file, "FamilyName %s\n", fontm.name_family);
    fprintf(ufm_file, "Weight %s\n", fontm.name_style);
    fprintf(ufm_file, "Version %s\n", fontm.name_version);
    fprintf(ufm_file, "Characters %d\n", nchars);
    fprintf(ufm_file, "ItalicAngle %.1f\n", italic_angle);

    fprintf(ufm_file, "Ascender %d\n", iscale(fontm.ascender));
    fprintf(ufm_file, "Descender %d\n", iscale(fontm.descender));

  fprintf(pfa_file, "/UnderlinePosition %d def\n",
    iscale(fontm.underline_position));

  fprintf(pfa_file, "/UnderlineThickness %hd def\nend readonly def\n",
    iscale(fontm.underline_thickness));

  fprintf(afm_file, "UnderlineThickness %d\n",
    iscale(fontm.underline_thickness));

  fprintf(afm_file, "UnderlinePosition %d\n",
    iscale(fontm.underline_position));

    fprintf(afm_file, "IsFixedPitch %s\n",
    fontm.is_fixed_pitch ? "true" : "false");
    fprintf(afm_file, "FontBBox %d %d %d %d\n",
    bbox[0], bbox[1], bbox[2], bbox[3]);

  fprintf(ufm_file, "UnderlineThickness %d\n",
    iscale(fontm.underline_thickness));

  fprintf(ufm_file, "UnderlinePosition %d\n",
    iscale(fontm.underline_position));

    fprintf(ufm_file, "IsFixedPitch %s\n",
    fontm.is_fixed_pitch ? "true" : "false");
    fprintf(ufm_file, "FontBBox %d %d %d %d\n",
    bbox[0], bbox[1], bbox[2], bbox[3]);

  fprintf(pfa_file, "/FontName /%s%s def\n", fontm.name_ps, uni_font_name_suffix);
  fprintf(pfa_file, "/PaintType 0 def\n/StrokeWidth 0 def\n");
  /* I'm not sure if these are fixed */
  fprintf(pfa_file, "/FontType 1 def\n");

  if (transform) {
    fprintf(pfa_file, "/FontMatrix [0.001 0 0 0.001 0 0] def\n");
  } else {
    fprintf(pfa_file, "/FontMatrix [%9.7f 0 0 %9.7f 0 0] def\n",
      original_scale_factor / 1000.0, original_scale_factor / 1000.0);
  }

  fprintf(pfa_file, "/FontBBox {%d %d %d %d} readonly def\n",
    bbox[0], bbox[1], bbox[2], bbox[3]);

  fprintf(pfa_file, "/Encoding 256 array\n");
  /* determine number of elements for metrics table */
  nmetrics = 256;
   for (i = 0; i < numglyphs; i++) {
    if( glyph_list[i].flags & GF_USED 
    && glyph_list[i].char_no == -1 ) {
      nmetrics++;
    }
  }
  fprintf(afm_file, "StartCharMetrics %d\n", nmetrics);
  fprintf(ufm_file, "StartCharMetrics %d\n", nmetrics);

  fprintf(dvienc_file, "/%s%sEncoding [\n",
    fontm.name_ps, uni_font_name_suffix);

   for (i = 0; i < 256; i++) { /* here 256, not ENCTABSZ */
    fprintf(pfa_file,
      "dup %d /%s put\n", i, glyph_list[encoding[i]].name);
    if( glyph_list[encoding[i]].flags & GF_USED )  {
      int j = 0;
      print_glyph_metrics(afm_file, i, encoding[i]);
      while ( glyph_list[encoding[i]].orig_code[j] != -1 ) {
        //print_glyph_metrics_ufm(ufm_file, glyph_list[encoding[i]].orig_code, encoding[i]);
        //print_glyph_metrics_ufm(ufm_file, i, encoding[i]);
        print_glyph_metrics_ufm(ufm_file, glyph_list[encoding[i]].orig_code[j], encoding[i]);
        j++;
      }
    }
    if (encoding[i])
      fprintf (dvienc_file, "/index0x%04X\n", encoding[i]);
    else
      fprintf (dvienc_file, "/.notdef\n");
   }

  /* print the metrics for glyphs not in encoding table */
  for(i=0; i<numglyphs; i++) {
    if( (glyph_list[i].flags & GF_USED)
        && glyph_list[i].char_no == -1 ) {
      int j = 0;
      print_glyph_metrics(afm_file, -1, i);
      while ( glyph_list[i].orig_code[j] != -1 ) {
        //print_glyph_metrics_ufm(ufm_file, glyph_list[i].orig_code, i);
        print_glyph_metrics_ufm(ufm_file, glyph_list[i].orig_code[j], i);
        j++;
      }
    }
  }

/*   for (i=0; i < ENCTABSZ; i++) { */
/*     print_glyph_metrics_ufm(ufm_file, i, encoding[i]); */
/*   } */

  fprintf(pfa_file, "readonly def\ncurrentdict end\ncurrentfile eexec\n");
  fprintf(pfa_file, "dup /Private 16 dict dup begin\n");

  fprintf(pfa_file, "/RD{string currentfile exch readstring pop}executeonly def\n");
  fprintf(pfa_file, "/ND{noaccess def}executeonly def\n");
  fprintf(pfa_file, "/NP{noaccess put}executeonly def\n");

  /* UniqueID must be shown twice, in both font and Private dictionary */
  if(wantuid) {
    if(strUID)
      fprintf(pfa_file, "/UniqueID %s def\n", strUID);
    else
      /* the range for private UIDs is 4 000 000 - 4 999 999 */
      fprintf(pfa_file, "/UniqueID %lu def\n", numUID%1000000+4000000);
  }

  if(forcebold==0)
    fprintf(pfa_file, "/ForceBold false def\n");
  else if(forcebold==1)
    fprintf(pfa_file, "/ForceBold true def\n");

  fprintf(pfa_file, "/BlueValues [ ");
  for (i = 0; i < nblues; i++)
    fprintf(pfa_file, "%d ", bluevalues[i]);
  fprintf(pfa_file, "] def\n");

  fprintf(pfa_file, "/OtherBlues [ ");
  for (i = 0; i < notherb; i++)
    fprintf(pfa_file, "%d ", otherblues[i]);
  fprintf(pfa_file, "] def\n");

  if (stdhw != 0)
    fprintf(pfa_file, "/StdHW [ %d ] def\n", stdhw);
  if (stdvw != 0)
    fprintf(pfa_file, "/StdVW [ %d ] def\n", stdvw);
  fprintf(pfa_file, "/StemSnapH [ ");
  for (i = 0; i < 12 && stemsnaph[i] != 0; i++)
    fprintf(pfa_file, "%d ", stemsnaph[i]);
  fprintf(pfa_file, "] def\n");
  fprintf(pfa_file, "/StemSnapV [ ");
  for (i = 0; i < 12 && stemsnapv[i] != 0; i++)
    fprintf(pfa_file, "%d ", stemsnapv[i]);
  fprintf(pfa_file, "] def\n");

  fprintf(pfa_file, "/MinFeature {16 16} def\n");
  /* Are these fixed also ? */
  fprintf(pfa_file, "/password 5839 def\n");

  /* calculate the number of subroutines */

  subid=5;
  for (i = 0; i < numglyphs; i++) {
    if (glyph_list[i].flags & GF_USED) {
      subid+=glyph_list[i].nsg;
    }
  }

  fprintf(pfa_file, "/Subrs %d array\n", subid);
  /* standard subroutines */
  fprintf(pfa_file, "dup 0 {\n\t3 0 callothersubr pop pop setcurrentpoint return\n\t} NP\n");
  fprintf(pfa_file, "dup 1 {\n\t0 1 callothersubr return\n\t} NP\n");
  fprintf(pfa_file, "dup 2 {\n\t0 2 callothersubr return\n\t} NP\n");
  fprintf(pfa_file, "dup 3 {\n\treturn\n\t} NP\n");
  /* our sub to make the hint substitution code shorter */
  fprintf(pfa_file, "dup 4 {\n\t1 3 callothersubr pop callsubr return\n\t} NP\n");

  if(pfa_file != null_file) { /* save time if the output would be wasted */
    /* print the hinting subroutines */
    subid=5;
    for (i = 0; i < numglyphs; i++) {
      if (glyph_list[i].flags & GF_USED) {
        subid+=print_glyph_subs(i, subid);
      }
    }

    fprintf(pfa_file, "ND\n");

    fprintf(pfa_file, "2 index /CharStrings %d dict dup begin\n", nchars);

    for (i = 0; i < numglyphs; i++) {
      if (glyph_list[i].flags & GF_USED) {
        print_glyph(i);
      }
    }
  }


  fprintf(pfa_file, "end\nend\nreadonly put\n");
  fprintf(pfa_file, "noaccess put\n");
  fprintf(pfa_file, "dup/FontName get exch definefont pop\n");
  fprintf(pfa_file, "mark currentfile closefile\n");
  fprintf(pfa_file, "cleartomark\n");
  if(pfa_file != null_file)
    fclose(pfa_file);

    fprintf(afm_file, "EndCharMetrics\n");
    fprintf(ufm_file, "EndCharMetrics\n");

  if(afm_file != null_file) { /* save time if the output would be wasted */
    /* print the kerning data if present */
    cursw->kerning(glyph_list);
    print_kerning(afm_file);
  }
  if(ufm_file != null_file) { /* save time if the output would be wasted */
    /* print the kerning data if present */
    cursw->kerning(glyph_list);
    print_kerning(ufm_file);
  }

    fprintf(afm_file, "EndFontMetrics\n");
  if(afm_file != null_file)
    fclose(afm_file);

    fprintf(ufm_file, "EndFontMetrics\n");
  if(ufm_file != null_file)
    fclose(ufm_file);

  fprintf(dvienc_file, "] def\n");
  if(dvienc_file != null_file)
    fclose(dvienc_file);

  WARNING_1 fprintf(stderr, "Finished - font files created\n");

  cursw->close();

#ifndef WINDOWS
  while (wait(&ws) > 0) {
  }
#else 
  if (encode && pfa_file != null_file) {
    extern FILE    *ifp, *ofp;  /* from t1asm.c */

    snprintf(filename, sizeof filename, "%s.%s", argv[2], pfbflag ? "pfb" : "pfa" );

    if ((ofp = fopen(filename, "w+b")) == NULL) {
      fprintf(stderr, "**** Cannot create %s ****\n", filename);
      exit(1);
    } else {
      WARNING_2 fprintf(stderr, "Creating file %s\n", filename);
    }

    snprintf(filename, sizeof filename, "%s.t1a", argv[2]);

    if ((ifp = fopen(filename, "rb")) == NULL) {
      fprintf(stderr, "**** Cannot read %s ****\n", filename);
      exit(1);
    } else {
      WARNING_2 fprintf(stderr, "Converting file %s\n", filename);
    }

    runt1asm(pfbflag);

    WARNING_2 fprintf(stderr, "Removing file %s\n", filename);
    if(unlink(filename) < 0) 
      WARNING_1 fprintf(stderr, "Unable to remove file %s\n", filename);
  }
#endif /* WINDOWS */

  fclose(null_file);
  return 0;
}
