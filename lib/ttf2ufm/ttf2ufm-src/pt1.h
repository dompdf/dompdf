/*
 * see COPYRIGHT
 */


/* glyph entry, one drawing command */
typedef struct gentry {
	/* this list links all GENTRYs of a GLYPH sequentially */
	struct gentry  *next;	/* double linked list */
	struct gentry  *prev;

	/* this list links all GENTRYs of one contour - 
	 * of types GE_LINE and GE_CURVE only
	 * bkwd is also reused: in the very first entry (normally
	 * of type GE_MOVE) it points to g->entries
	 */
	struct gentry  *cntr[2]; /* double-linked circular list */
/* convenience handles */
#define bkwd cntr[0]
#define frwd cntr[1]

	/* various extended structures used at some stage of transformation */
	void *ext; 

	union {
		struct {
			int  val[2][3];	/* integer values */
		} i;
		struct {
			double  val[2][3];	/* floating values */
		} f;
	} points; /* absolute values, NOT deltas */
/* convenience handles */
#define ipoints	points.i.val
#define fpoints	points.f.val
#define ixn ipoints[0]
#define iyn ipoints[1]
#define fxn fpoints[0]
#define fyn fpoints[1]
#define ix1	ixn[0]
#define ix2 ixn[1]
#define ix3 ixn[2]
#define iy1	iyn[0]
#define iy2 iyn[1]
#define iy3 iyn[2]
#define fx1	fxn[0]
#define fx2 fxn[1]
#define fx3 fxn[2]
#define fy1	fyn[0]
#define fy2 fyn[1]
#define fy3 fyn[2]

	char            flags; 
#define GEF_FLOAT	0x02 /* entry contains floating point data */
#define GEF_LINE	0x04 /* entry looks like a line even if it's a curve */

	unsigned char	dir; /* used to temporarily store the values for
				* the directions of the ends of curves */
/* front end */
#define CVDIR_FUP	0x02	/* goes over the line connecting the ends */
#define CVDIR_FEQUAL	0x01	/* coincides with the line connecting the
				 * ends */
#define CVDIR_FDOWN	0x00	/* goes under the line connecting the ends */
#define CVDIR_FRONT	0x0F	/* mask of all front directions */
/* rear end */
#define CVDIR_RSAME	0x30	/* is the same as for the front end */
#define CVDIR_RUP	0x20	/* goes over the line connecting the ends */
#define CVDIR_REQUAL	0x10	/* coincides with the line connecting the
				 * ends */
#define CVDIR_RDOWN	0x00	/* goes under the line connecting the ends */
#define CVDIR_REAR	0xF0	/* mask of all rear directions */

	signed char     stemid; /* connection to the substituted stem group */
	char            type;
#define GE_HSBW	'B'
#define GE_MOVE 'M'
#define GE_LINE 'L'
#define GE_CURVE 'C'
#define GE_PATH 'P'

	/* indexes of the points to be used for calculation of the tangents */
	signed char     ftg; /* front tangent */
	signed char     rtg; /* rear tangent, -1 means "idx 2 of the previous entry" */
}               GENTRY;

/* stem structure, describes one [hv]stem  */
/* acually, it describes one border of a stem */
/* the whole stem is a pair of these structures */

typedef struct stem {
	short           value;	/* value of X or Y coordinate */
	short           origin;	/* point of origin for curve stems */
	GENTRY         *ge; /* entry that has (value, origin) as its first dot */
		/* also for all the stems the couple (value, origin)
		 * is used to determine whether a stem is relevant for a
		 * line, it's considered revelant if this tuple is
		 * equal to any of the ends of the line.
		 * ge is also used to resolve ambiguity if there is more than
		 * one line going through certain pointi, it is used to 
		 * distinguish these lines.
		 */
	 
	short           from, to;	/* values of other coordinate between
					 * which this stem is valid */

	short           flags;
	/* ordering of ST_END, ST_FLAT, ST_ZONE is IMPORTANT for sorting */
#define ST_END		0x01	/* end of line, lowest priority */
#define ST_FLAT		0x02	/* stem is defined by a flat line, not a
				 * curve */
#define ST_ZONE		0x04	/* pseudo-stem, the limit of a blue zone */
#define ST_UP		0x08	/* the black area is to up or right from
				 * value */
#define ST_3		0x20	/* first stem of [hv]stem3 */
#define ST_BLUE		0x40	/* stem is in blue zone */
#define ST_TOPZONE	0x80	/* 1 - top zone, 0 - bottom zone */
#define ST_VERT     0x100	/* vertical stem (used in substitutions) */
}               STEM;

#define MAX_STEMS	2000	/* we can't have more stems than path
				 * elements (or hope so) */
#define NSTEMGRP	50	/* maximal number of the substituted stem groups */

/* structure for economical representation of the
 * substituted stems
 */

typedef struct stembounds {
	short low; /* low bound */
	short high; /* high bound */
	char isvert; /* 1 - vertical, 0 - horizontal */
	char already; /* temp. flag: is aleready included */
} STEMBOUNDS;

struct kern {
	unsigned id; /* ID of the second glyph */
	int val; /* kerning value */
};

typedef struct contour {
	short           ymin, xofmin;
	short           inside;	/* inside which contour */
	char            direction;
#define DIR_OUTER 1
#define DIR_INNER 0
}               CONTOUR;

/* becnjcarson: allow glyphs to have multiple character codes.  This isn't 100%
   perfect, but should be enough for most normal fonts. */
#define GLYPH_MAX_ENCODINGS  (4)

typedef struct glyph {
	int             char_no;/* Encoding of glyph */
	int             orig_code[GLYPH_MAX_ENCODINGS]; /* code(s) of glyph in the font's original encoding */
	char           *name;	/* Postscript name of glyph */
	int             xMin, yMin, xMax, yMax;	/* values from TTF dictionary */
	int             lsb; /* left sidebearing */
	int             ttf_pathlen; /* total length of TTF paths */
	short           width;
	short           flags;
#define GF_USED	0x0001		/* whether is this glyph used in T1 font */
#define GF_FLOAT 0x0002		/* thys glyph contains floating point entries */

	GENTRY         *entries;/* doube linked list of entries */
	GENTRY         *lastentry;	/* the last inserted entry */
	GENTRY         *path;	/* beggining of the last path */
	int             oldwidth; /* actually also scaled */
	int             scaledwidth;
#define	MAXLEGALWIDTH	10000 

	struct kern    *kern; /* kerning data */
	int             kerncount; /* number of kerning pairs */
	int             kernalloc; /* for how many pairs we have space */

	STEM           *hstems; /* global horiz. and vert. stems */
	STEM           *vstems;
	int             nhs, nvs;	/* numbers of stems */

	STEMBOUNDS     *sbstems; /* substituted stems for all the groups */
	short          *nsbs; /* indexes of the group ends in the common array */
	int             nsg; /* actual number of the stem groups */
	int             firstsubr; /* first substistuted stems subroutine number */

	CONTOUR        *contours;	/* it is not used now */
	int             ncontours;

	int             rymin, rymax;	/* real values */
	/* do we have flat surfaces on top/bottom */
	char            flatymin, flatymax;

}               GLYPH;

/* description of a dot for calculation of its distance to a curve */

struct dot_dist {
	double p[2 /*X,Y*/]; /* coordinates of a dot */
	double dist2; /* squared distance from the dot to the curve */
	short seg; /* the closest segment of the curve */
};

extern int      stdhw, stdvw;	/* dominant stems widths */
extern int      stemsnaph[12], stemsnapv[12];	/* most typical stem width */

extern int      bluevalues[14];
extern int      nblues;
extern int      otherblues[10];
extern int      notherb;
extern int      bbox[4];	/* the FontBBox array */
extern double   italic_angle;

extern GLYPH   *glyph_list;
extern int    encoding[];	/* inverse of glyph[].char_no */

/* prototypes of functions */
void rmoveto( int dx, int dy);
void rlineto( int dx, int dy);
void rrcurveto( int dx1, int dy1, int dx2, int dy2, int dx3, int dy3);
void assertpath( GENTRY * from, char *file, int line, char *name);

void fg_rmoveto( GLYPH * g, double x, double y);
void ig_rmoveto( GLYPH * g, int x, int y);
void fg_rlineto( GLYPH * g, double x, double y);
void ig_rlineto( GLYPH * g, int x, int y);
void fg_rrcurveto( GLYPH * g, double x1, double y1,
	double x2, double y2, double x3, double y3);
void ig_rrcurveto( GLYPH * g, int x1, int y1,
	int x2, int y2, int x3, int y3);
void g_closepath( GLYPH * g);

void pathtoint( GLYPH *g);
void ffixquadrants( GLYPH *g);
void flattencurves( GLYPH * g);
int checkcv( GENTRY * ge, int dx, int dy);
void iclosepaths( GLYPH * g);
void fclosepaths( GLYPH * g);
void smoothjoints( GLYPH * g);
void buildstems( GLYPH * g);
void fstraighten( GLYPH * g);
void istraighten( GLYPH * g, int zigonly);
void isplitzigzags( GLYPH * g);
void fsplitzigzags( GLYPH * g);
void fforceconcise( GLYPH * g);
void iforceconcise( GLYPH * g);
void reversepathsfromto( GENTRY * from, GENTRY * to);
void reversepaths( GLYPH * g);
void dumppaths( GLYPH * g, GENTRY *start, GENTRY *end);
void print_glyph( int glyphno);
int print_glyph_subs( int glyphno, int startid);
void print_glyph_metrics( FILE *afm_file, int code, int glyphno);
void print_glyph_metrics_ufm( FILE *ufm_file, int code, int glyphno);
void findblues(void);
void stemstatistics(void);
void docorrectwidth(void);
void addkernpair( unsigned id1, unsigned id2, int unscval);
void print_kerning( FILE *afm_file);

int fcrossrayscv( double curve[4][2], double *max1, double *max2);
int fcrossraysge( GENTRY *ge1, GENTRY *ge2, double *max1, double *max2,
	double crossdot[2][2]);
double fdotsegdist2( double seg[2][2], double dot[2]);
double fdotcurvdist2( double curve[4][2], struct dot_dist *dots, int ndots, double *maxp);
void fapproxcurve( double cv[4][2], struct dot_dist *dots, int ndots);
