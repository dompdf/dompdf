/*
 * see COPYRIGHT
 */


/* options */

extern int      encode;	/* encode the resulting file */
extern int      pfbflag;	/* produce compressed file */
extern int      wantafm;	/* want to see .afm instead of .t1a on stdout */
extern int      correctvsize;	/* try to correct the vertical size of characters */
extern int      wantuid;	/* user wants UniqueID entry in the font */
extern int      allglyphs;	/* convert all glyphs, not only 256 of them */
extern int      warnlevel;	/* the level of permitted warnings */
extern int      forcemap; /* do mapping even on non-Unicode fonts */
/* options - maximal limits */
extern int      max_stemdepth;	/* maximal depth of stem stack in interpreter */
/* options - debugging */
extern int      absolute;	/* print out in absolute values */
extern int      reverse;	/* reverse font to Type1 path directions */
/* options - suboptions of Outline Processing */
extern int      optimize;	/* enables space optimization */
extern int      smooth;	/* enable smoothing of outlines */
extern int      transform;	/* enables transformation to 1000x1000 matrix */
extern int      hints;	/* enables autogeneration of hints */
extern int      subhints;	/* enables autogeneration of substituted hints */
extern int      trybold;	/* try to guess whether the font is bold */
extern int      correctwidth;	/* try to correct the character width */
extern int      vectorize;	/* vectorize the bitmaps */
extern int      use_autotrace;	/* use the autotrace library on bitmap */
/* options - suboptions of File Generation */
extern int      gen_pfa;	/* generate the font file */
extern int      gen_afm;	/* generate the metrics file */
extern int      gen_dvienc;	/* generate the dvips encoding file */

/* not quite options to select a particular source encoding */
extern int      force_pid; /* specific platform id */
extern int      force_eid; /* specific encoding id */

/* other globals */
extern FILE    *null_file, *pfa_file, *afm_file, *ufm_file, *dvienc_file;
extern int      numglyphs;

/* warnings */

#define WARNING_1	if(warnlevel >= 1)
#define WARNING_2	if(warnlevel >= 2)
#define WARNING_3	if(warnlevel >= 3)
#define WARNING_4	if(warnlevel >= 4)

/*
 * Bitmap control macros
 */

#define BITMAP_BYTES(size)	(((size)+7)>>3)
#define DEF_BITMAP(name, size)	unsigned char name[BITMAP_BYTES(size)]
#define SET_BITMAP(name, bit)	( name[(bit)>>3] |= (1<<((bit)&7)) )
#define CLR_BITMAP(name, bit)	( name[(bit)>>3] &= ~(1<<((bit)&7)) )
#define IS_BITMAP(name, bit)	( name[(bit)>>3] & (1<<((bit)&7)) )

/* debugging */

/* debug flags */
#define DEBUG_UNICODE	0x00000001 /* unicode to 8-bit code conversion */
#define DEBUG_MAINSTEMS	0x00000002 /* glyph-wide main stem generation */
#define DEBUG_SUBSTEMS	0x00000004 /* substituted stem generation */
#define DEBUG_STEMS	(DEBUG_MAINSTEMS|DEBUG_SUBSTEMS)
#define DEBUG_REVERSAL	0x00000008 /* reversal of the paths */
#define DEBUG_FIXCVDIR	0x00000010 /* fixcvdir() */
#define DEBUG_STEMOVERLAP	0x00000020 /* stemoverlap() */
#define DEBUG_BLUESTEMS	0x00000040 /* markbluestems() */
#define DEBUG_STRAIGHTEN	0x00000080 /* markbluestems() */
#define DEBUG_EXTMAP	0x00000100 /* parsing of external map */
#define DEBUG_TOINT	0x00000200 /* conversion of path to integer */
#define DEBUG_BUILDG	0x00000400 /* building of glyph path */
#define DEBUG_QUAD	0x00000800 /* splitting curves by quadrants */
#define DEBUG_SQEQ	0x00001000 /* square equation solver */
#define DEBUG_COMPOSITE	0x00002000 /* handling of composite glyphs */
#define DEBUG_FCONCISE	0x00004000 /* normalization of curves */
#define DEBUG_FT		0x00008000 /* FreeType front-end */
#define DEBUG_BITMAP	0x00010000 /* conversion from bitmap */
#define DEBUG_DISABLED	0x80000000 /* special flag: temporary disable debugging */

/* at what we want to look now */
#ifndef DEBUG
#	define DEBUG (0)
#endif

/* uncomment the next line if debugging data is wanted for one glyph only */
/* #define DBG_GLYPH	"C118"  /* */

#if DEBUG==0
#	define ISDBG(name)	(0)
#	define ENABLEDBG(condition) (0)
#	define DISABLEDBG(condition) (0)
#else
	extern int debug; /* collection of the flags */
/* this ISDBG will only work on ANSI C, not K&R */
#	define ISDBG(name)	( (debug & DEBUG_DISABLED) ? 0 : (debug & (DEBUG_##name)) )
#	define ENABLEDBG(condition) ( (condition) ? (debug&=~DEBUG_DISABLED) : 0 )
#	define DISABLEDBG(condition) ( (condition) ? (debug|=DEBUG_DISABLED) : 0 )
#endif

#ifdef DBG_GLYPH
#	define DBG_TO_GLYPH(g) DISABLEDBG( strcmp( (g)->name, DBG_GLYPH ) )
#	define DBG_FROM_GLYPH(g) ENABLEDBG(1)
#else
#	define DBG_TO_GLYPH(g) (0)
#	define DBG_FROM_GLYPH(g) (0)
#endif

/* prototypes */
int iscale( int val);
double fscale( double val);
int unicode_rev_lookup( int unival);
void bmp_outline( GLYPH *g, int scale, char *bmap,
	int xsz, int ysz, int xoff, int yoff);
int isign( int x);
int fsign( double x);
char *dupcnstring( unsigned char *s, int len);

/* global metrics for a font */

struct font_metrics {
	/* post */
	double	italic_angle;
	short	underline_position;
	short	underline_thickness;
	short	is_fixed_pitch;

	/* hhea */
	short	ascender; 
	short	descender;

	/* head */
	unsigned short	units_per_em;
	short   bbox[4];

	/* name */
	char	*name_copyright;
	char	*name_family;
	char	*name_style;
	char	*name_full;
	char	*name_version;
	char	*name_ps;

	/* other */
	int		force_bold;
};

/* size of the encoding table - glyphs beyond 255 are actually unnumbered */

#define ENCTABSZ	1024

/* switch table structure for front-ends */

#define MAXSUFFIX	10

struct frontsw {
	char  *name; /* name of the front end */
	char  *descr; /* description of the front end */
	char  *suffix[MAXSUFFIX]; /* possible file name suffixes */

	void  (*open)(char *fname, char *arg); /* open font file */
	void  (*close)(void); /* close font file */
	int   (*nglyphs)(void); /* get the number of glyphs */
	int   (*glnames)(GLYPH *glyphs); /* get the names of glyphs */
	void  (*glmetrics)(GLYPH *glyphs); /* get the metrics of glyphs */
	int   (*glenc)(GLYPH *glyphs, int *enc, int *unimap); /* get the encoding */
	void  (*fnmetrics)(struct font_metrics *fm); /* get the font metrics */
	void  (*glpath)(int glyphno, GLYPH *glyphs); /* get the glyph path */
	void  (*kerning)(GLYPH *glyph_list); /* extract the kerning data */
};
