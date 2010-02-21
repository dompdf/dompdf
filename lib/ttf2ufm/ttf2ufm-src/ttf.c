/*
 * True Type Font to Adobe Type 1 font converter 
 * By Mark Heath <mheath@netspace.net.au> 
 * Based on ttf2pfa by Andrew Weeks <ccsaw@bath.ac.uk> 
 * With help from Frank M. Siegert <fms@this.net> 
 *
 * see COPYRIGHT
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

#ifndef WINDOWS
#	include <unistd.h>
#	include <netinet/in.h>
#else
#	include "windows.h"
#endif

#include "ttf.h"
#include "pt1.h"
#include "global.h"

/* prototypes of call entries */
static void openfont(char *fname, char *arg);
static void closefont( void);
static int getnglyphs ( void);
static int glnames( GLYPH *glyph_list);
static void glmetrics( GLYPH *glyph_list);
static int glenc( GLYPH *glyph_list, int *encoding, int *unimap);
static void fnmetrics( struct font_metrics *fm);
static void glpath( int glyphno, GLYPH *glyph_list);
static void kerning( GLYPH *glyph_list);

/* globals */

/* front-end descriptor */
struct frontsw ttf_sw = {
	/*name*/       "ttf",
	/*descr*/      "built-in TTF support",
	/*suffix*/     { "ttf" },
	/*open*/       openfont,
	/*close*/      closefont,
	/*nglyphs*/    getnglyphs,
	/*glnames*/    glnames,
	/*glmetrics*/  glmetrics,
	/*glenc*/      glenc,
	/*fnmetrics*/  fnmetrics,
	/*glpath*/     glpath,
	/*kerning*/    kerning,
};

/* statics */

static FILE    *ttf_file;
static int      ttf_nglyphs, long_offsets;

static TTF_DIRECTORY *directory;
static TTF_DIR_ENTRY *dir_entry;
static char    *filebuffer;
static char    *filebuffer_end;
static TTF_NAME *name_table = NULL;
static TTF_NAME_REC *name_record;
static TTF_HEAD *head_table = NULL;
static TTF_HHEA *hhea_table = NULL;
static TTF_KERN *kern_table = NULL;
static TTF_CMAP *cmap_table = NULL;
static LONGHORMETRIC *hmtx_table = NULL;
static TTF_GLYF *glyf_table;
static BYTE    *glyf_start = NULL;
static TTF_MAXP *maxp_table = NULL;
static TTF_POST_HEAD *post_table = NULL;
static union {
	USHORT *sp;
	ULONG  *lp;
} loca_table;
#define short_loca_table	loca_table.sp
#define long_loca_table		loca_table.lp

static short    cmap_n_segs;
static USHORT  *cmap_seg_start, *cmap_seg_end;
static short   *cmap_idDelta, *cmap_idRangeOffset;
static TTF_CMAP_FMT0  *encoding0;
static int             enc_type;

static char    *name_fields[8];

static int enc_found_ms, enc_found_mac;

static char    *mac_glyph_names[258] = {
	".notdef", ".null", "CR",
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
	"bar", "braceright", "asciitilde", "Adieresis",
	"Aring", "Ccedilla", "Eacute", "Ntilde",
	"Odieresis", "Udieresis", "aacute", "agrave",
	"acircumflex", "adieresis", "atilde", "aring",
	"ccedilla", "eacute", "egrave", "ecircumflex",
	"edieresis", "iacute", "igrave", "icircumflex",
	"idieresis", "ntilde", "oacute", "ograve",
	"ocircumflex", "odieresis", "otilde", "uacute",
	"ugrave", "ucircumflex", "udieresis", "dagger",
	"degree", "cent", "sterling", "section",
	"bullet", "paragraph", "germandbls", "registered",
	"copyright", "trademark", "acute", "dieresis",
	"notequal", "AE", "Oslash", "infinity",
	"plusminus", "lessequal", "greaterequal", "yen",
	"mu", "partialdiff", "summation", "product",
	"pi", "integral", "ordfeminine", "ordmasculine",
	"Omega", "ae", "oslash", "questiondown",
	"exclamdown", "logicalnot", "radical", "florin",
	"approxequal", "increment", "guillemotleft", "guillemotright",
	"ellipsis", "nbspace", "Agrave", "Atilde",
	"Otilde", "OE", "oe", "endash",
	"emdash", "quotedblleft", "quotedblright", "quoteleft",
	"quoteright", "divide", "lozenge", "ydieresis",
	"Ydieresis", "fraction", "currency", "guilsinglleft",
	"guilsinglright", "fi", "fl", "daggerdbl",
	"periodcentered", "quotesinglbase", "quotedblbase", "perthousand",
	"Acircumflex", "Ecircumflex", "Aacute", "Edieresis",
	"Egrave", "Iacute", "Icircumflex", "Idieresis",
	"Igrave", "Oacute", "Ocircumflex", "applelogo",
	"Ograve", "Uacute", "Ucircumflex", "Ugrave",
	"dotlessi", "circumflex", "tilde", "macron",
	"breve", "dotaccent", "ring", "cedilla",
	"hungarumlaut", "ogonek", "caron", "Lslash",
	"lslash", "Scaron", "scaron", "Zcaron",
	"zcaron", "brokenbar", "Eth", "eth",
	"Yacute", "yacute", "Thorn", "thorn",
	"minus", "multiply", "onesuperior", "twosuperior",
	"threesuperior", "onehalf", "onequarter", "threequarters",
	"franc", "Gbreve", "gbreve", "Idot",
	"Scedilla", "scedilla", "Cacute", "cacute",
	"Ccaron", "ccaron", "dmacron"
};

/* other prototypes */
static void draw_composite_glyf( GLYPH *g, GLYPH *glyph_list, int glyphno, 
	double *matrix, int level);
static void draw_simple_glyf( GLYPH *g, GLYPH *glyph_list, int glyphno, 
	double *matrix);
static double f2dot14( short x);

/* get the TTF description table address and length for this index */

static void
get_glyf_table(
	int glyphno,
	TTF_GLYF **tab,
	int *len
)
{
	if(tab!=NULL) {
		if (long_offsets) {
			*tab = (TTF_GLYF *) (glyf_start + ntohl(long_loca_table[glyphno]));
		} else {
			*tab = (TTF_GLYF *) (glyf_start + (ntohs(short_loca_table[glyphno]) << 1));
		}
	}
	if(len!=NULL) {
		if (long_offsets) {
			*len = ntohl(long_loca_table[glyphno + 1]) - ntohl(long_loca_table[glyphno]);
		} else {
			*len = (ntohs(short_loca_table[glyphno + 1]) - ntohs(short_loca_table[glyphno])) << 1;
		}
	}
}

static void
handle_name(void)
{
	int             j, k, lang, len, platform;
	char           *p, *string_area;
	int             found3 = 0;

	string_area = (char *) name_table + ntohs(name_table->offset);
	name_record = &(name_table->nameRecords);

	for (j = 0; j < 8; j++) {
		name_fields[j] = ""; 
	}

	for (j = 0; j < ntohs(name_table->numberOfNameRecords); j++) {

		platform = ntohs(name_record->platformID);

		if (platform == 3) {

			found3 = 1;
			lang = ntohs(name_record->languageID) & 0xff;
			len = ntohs(name_record->stringLength);
			if (lang == 0 || lang == 9) {
				k = ntohs(name_record->nameID);
				if (k < 8) {
					p = string_area + ntohs(name_record->stringOffset);
					name_fields[k] = dupcnstring(p, len);
				}
			}
		}
		name_record++;
	}

	string_area = (char *) name_table + ntohs(name_table->offset);
	name_record = &(name_table->nameRecords);

	if (!found3) {
		for (j = 0; j < ntohs(name_table->numberOfNameRecords); j++) {

			platform = ntohs(name_record->platformID);

			if (platform == 1) {

				found3 = 1;
				lang = ntohs(name_record->languageID) & 0xff;
				len = ntohs(name_record->stringLength);
				if (lang == 0 || lang == 9) {
					k = ntohs(name_record->nameID);
					if (k < 8) {
						p = string_area + ntohs(name_record->stringOffset);
						name_fields[k] = dupcnstring(p, len);
					}
				}
			}
			name_record++;
		}
	}
	if (!found3) {
		fprintf(stderr, "**** Cannot decode font name fields ****\n");
		exit(1);
	}
	if (name_fields[4][0] == 0) { /* Full Name empty, use Family Name */
		name_fields[4] = name_fields[1];
	}
	if (name_fields[6][0] == 0) { /* Font Name empty, use Full Name */
		name_fields[6] = name_fields[4];
		if (name_fields[6][0] == 0) { /* oops, empty again */
			WARNING_1 fprintf(stderr, "Font name is unknown, setting to \"Unknown\"\n");
			name_fields[6] = "Unknown";
		}
	}
	p = name_fields[6];
	/* must not start with a digit */
	if(isdigit(*p))
		*p+= 'A'-'0'; /* change to a letter */
	while (*p != '\0') {
		if (!isalnum(*p) || *p=='_') {
			*p = '-';
		}
		p++;
	}
}

static void
handle_head(void)
{
	long_offsets = ntohs(head_table->indexToLocFormat);
	if (long_offsets != 0 && long_offsets != 1) {
		fprintf(stderr, "**** indexToLocFormat wrong ****\n");
		exit(1);
	}
}

/* limit the recursion level to avoid cycles */
#define MAX_COMPOSITE_LEVEL 20

static void
draw_composite_glyf(
	GLYPH *g,
	GLYPH *glyph_list,
	int glyphno,
	double *orgmatrix,
	int level
)
{
	int len;
	short           ncontours;
	USHORT          flagbyte, glyphindex;
	double          arg1, arg2;
	BYTE           *ptr;
	char           *bptr;
	SHORT          *sptr;
	double          matrix[6], newmatrix[6];

	get_glyf_table(glyphno, &glyf_table, &len);

	if(len<=0) /* nothing to do */
		return;

	ncontours = ntohs(glyf_table->numberOfContours);
	if (ncontours >= 0) { /* simple case */
		draw_simple_glyf(g, glyph_list, glyphno, orgmatrix);
		return;
	}

	if(ISDBG(COMPOSITE) && level ==0)
		fprintf(stderr, "* %s [ %.2f %.2f %.2f %.2f %.2f %.2f ]\n", g->name,
			orgmatrix[0], orgmatrix[1], orgmatrix[2], orgmatrix[3],
			orgmatrix[4], orgmatrix[5]);

	/* complex case */
	if(level >= MAX_COMPOSITE_LEVEL) {
		WARNING_1 fprintf(stderr, 
			"*** Glyph %s: stopped (possibly infinite) recursion at depth %d\n",
			g->name, level);
		return;
	}

	ptr = ((BYTE *) glyf_table + sizeof(TTF_GLYF));
	sptr = (SHORT *) ptr;
	do {
		flagbyte = ntohs(*sptr);
		sptr++;
		glyphindex = ntohs(*sptr);
		sptr++;

		if (flagbyte & ARG_1_AND_2_ARE_WORDS) {
			arg1 = (short)ntohs(*sptr);
			sptr++;
			arg2 = (short)ntohs(*sptr);
			sptr++;
		} else {
			bptr = (char *) sptr;
			arg1 = (signed char) bptr[0];
			arg2 = (signed char) bptr[1];
			sptr++;
		}
		matrix[1] = matrix[2] = 0.0;

		if (flagbyte & WE_HAVE_A_SCALE) {
			matrix[0] = matrix[3] = f2dot14(*sptr);
			sptr++;
		} else if (flagbyte & WE_HAVE_AN_X_AND_Y_SCALE) {
			matrix[0] = f2dot14(*sptr);
			sptr++;
			matrix[3] = f2dot14(*sptr);
			sptr++;
		} else if (flagbyte & WE_HAVE_A_TWO_BY_TWO) {
			matrix[0] = f2dot14(*sptr);
			sptr++;
			matrix[2] = f2dot14(*sptr);
			sptr++;
			matrix[1] = f2dot14(*sptr);
			sptr++;
			matrix[3] = f2dot14(*sptr);
			sptr++;
		} else {
			matrix[0] = matrix[3] = 1.0;
		}

		/*
		 * See *
		 * http://fonts.apple.com/TTRefMan/RM06/Chap6g
		 * lyf.html * matrix[0,1,2,3,4,5]=a,b,c,d,m,n
		 */

		if (fabs(matrix[0]) > fabs(matrix[1]))
			matrix[4] = fabs(matrix[0]);
		else
			matrix[4] = fabs(matrix[1]);
		if (fabs(fabs(matrix[0]) - fabs(matrix[2])) <= 33. / 65536.)
			matrix[4] *= 2.0;

		if (fabs(matrix[2]) > fabs(matrix[3]))
			matrix[5] = fabs(matrix[2]);
		else
			matrix[5] = fabs(matrix[3]);
		if (fabs(fabs(matrix[2]) - fabs(matrix[3])) <= 33. / 65536.)
			matrix[5] *= 2.0;

		/*
		 * fprintf (stderr,"Matrix Opp %hd
		 * %hd\n",arg1,arg2);
		 */
#if 0
		fprintf(stderr, "Matrix: %f %f %f %f %f %f\n",
		 matrix[0], matrix[1], matrix[2], matrix[3],
			matrix[4], matrix[5]);
		fprintf(stderr, "Offset: %f %f (%s)\n",
			arg1, arg2,
			((flagbyte & ARGS_ARE_XY_VALUES) ? "XY" : "index"));
#endif

		if (flagbyte & ARGS_ARE_XY_VALUES) {
			matrix[4] *= arg1;
			matrix[5] *= arg2;
		} else {
			WARNING_1 fprintf(stderr, 
				"*** Glyph %s: reusing scale from another glyph is unsupported\n",
				g->name);
			/*
			 * must extract values from a glyph
			 * but it seems to be too much pain
			 * and it's not clear now that it
			 * would be really used in any
			 * interesting font
			 */
		}

		/* at this point arg1,arg2 contain what logically should be matrix[4,5] */

		/* combine matrices */

		newmatrix[0] = orgmatrix[0]*matrix[0] + orgmatrix[2]*matrix[1];
		newmatrix[1] = orgmatrix[0]*matrix[2] + orgmatrix[2]*matrix[3];

		newmatrix[2] = orgmatrix[1]*matrix[0] + orgmatrix[3]*matrix[1];
		newmatrix[3] = orgmatrix[1]*matrix[2] + orgmatrix[3]*matrix[3];

		newmatrix[4] = orgmatrix[0]*matrix[4] + orgmatrix[2]*matrix[5] + orgmatrix[4];
		newmatrix[5] = orgmatrix[1]*matrix[4] + orgmatrix[3]*matrix[5] + orgmatrix[5];

		if(ISDBG(COMPOSITE)) {
			fprintf(stderr, "%*c+-> %2d %s [ %.2f %.2f %.2f %.2f %.2f %.2f ]\n",
				level+1, ' ', level, glyph_list[glyphindex].name,
				matrix[0], matrix[1], matrix[2], matrix[3],
				matrix[4], matrix[5]);
			fprintf(stderr, "%*c        = [ %.2f %.2f %.2f %.2f %.2f %.2f ]\n",
				level+1, ' ',
				newmatrix[0], newmatrix[1], newmatrix[2], newmatrix[3],
				newmatrix[4], newmatrix[5]);
		}
		draw_composite_glyf(g, glyph_list, glyphindex, newmatrix, level+1);

	} while (flagbyte & MORE_COMPONENTS);
}

static void
draw_simple_glyf(
	GLYPH *g,
	GLYPH *glyph_list,
	int glyphno,
	double *matrix
)
{
	int             i, j, k, k1, len, first, cs, ce;
	/* We assume that hsbw always sets to(0, 0) */
	double          xlast = 0, ylast = 0;
	int             finished, nguide, contour_start, contour_end;
	short           ncontours, n_inst, last_point;
	USHORT         *contour_end_pt;
	BYTE           *ptr;
#define GLYFSZ	2000
	short           xabs[GLYFSZ], yabs[GLYFSZ], xrel[GLYFSZ], yrel[GLYFSZ];
	double          xcoord[GLYFSZ], ycoord[GLYFSZ];
	BYTE            flags[GLYFSZ];
	double          tx, ty;
	int             needreverse = 0;	/* transformation may require
						 * that */
	GENTRY         *lge;

	lge = g->lastentry;

	get_glyf_table(glyphno, &glyf_table, &len);

	if (len <= 0) {
		WARNING_1 fprintf(stderr,
			"**** Composite glyph %s refers to non-existent glyph %s, ignored\n",
			g->name,
			glyph_list[glyphno].name);
		return;
	}
	ncontours = ntohs(glyf_table->numberOfContours);
	if (ncontours < 0) {
		WARNING_1 fprintf(stderr,
			"**** Composite glyph %s refers to composite glyph %s, ignored\n",
			g->name,
			glyph_list[glyphno].name);
		return;
	}
	contour_end_pt = (USHORT *) ((char *) glyf_table + sizeof(TTF_GLYF));

	last_point = ntohs(contour_end_pt[ncontours - 1]);
	n_inst = ntohs(contour_end_pt[ncontours]);

	ptr = ((BYTE *) contour_end_pt) + (ncontours << 1) + n_inst + 2;
	j = k = 0;
	while (k <= last_point) {
		flags[k] = ptr[j];

		if (ptr[j] & REPEAT) {
			for (k1 = 0; k1 < ptr[j + 1]; k1++) {
				k++;
				flags[k] = ptr[j];
			}
			j++;
		}
		j++;
		k++;
	}

	for (k = 0; k <= last_point; k++) {
		if (flags[k] & XSHORT) {
			if (flags[k] & XSAME) {
				xrel[k] = ptr[j];
			} else {
				xrel[k] = -ptr[j];
			}
			j++;
		} else if (flags[k] & XSAME) {
			xrel[k] = 0.0;
		} else {
			xrel[k] = (short)( ptr[j] * 256 + ptr[j + 1] );
			j += 2;
		}
		if (k == 0) {
			xabs[k] = xrel[k];
		} else {
			xabs[k] = xrel[k] + xabs[k - 1];
		}

	}

	for (k = 0; k <= last_point; k++) {
		if (flags[k] & YSHORT) {
			if (flags[k] & YSAME) {
				yrel[k] = ptr[j];
			} else {
				yrel[k] = -ptr[j];
			}
			j++;
		} else if (flags[k] & YSAME) {
			yrel[k] = 0;
		} else {
			yrel[k] = ptr[j] * 256 + ptr[j + 1];
			j += 2;
		}
		if (k == 0) {
			yabs[k] = yrel[k];
		} else {
			yabs[k] = yrel[k] + yabs[k - 1];
		}
	}

	if (matrix) {
		for (i = 0; i <= last_point; i++) {
			tx = xabs[i];
			ty = yabs[i];
			xcoord[i] = fscale(matrix[0] * tx + matrix[2] * ty + matrix[4]);
			ycoord[i] = fscale(matrix[1] * tx + matrix[3] * ty + matrix[5]);
		}
	} else {
		for (i = 0; i <= last_point; i++) {
			xcoord[i] = fscale(xabs[i]);
			ycoord[i] = fscale(yabs[i]);
		}
	}

	i = j = 0;
	first = 1;

	while (i <= ntohs(contour_end_pt[ncontours - 1])) {
		contour_end = ntohs(contour_end_pt[j]);

		if (first) {
			fg_rmoveto(g, xcoord[i], ycoord[i]);
			xlast = xcoord[i];
			ylast = ycoord[i];
			contour_start = i;
			first = 0;
		} else if (flags[i] & ONOROFF) {
			fg_rlineto(g, xcoord[i], ycoord[i]);
			xlast = xcoord[i];
			ylast = ycoord[i];
		} else {
			cs = i - 1;
			finished = nguide = 0;
			while (!finished) {
				if (i == contour_end + 1) {
					ce = contour_start;
					finished = 1;
				} else if (flags[i] & ONOROFF) {
					ce = i;
					finished = 1;
				} else {
					i++;
					nguide++;
				}
			}

			switch (nguide) {
			case 0:
				fg_rlineto(g, xcoord[ce], ycoord[ce]);
				xlast = xcoord[ce];
				ylast = ycoord[ce];
				break;

			case 1:
				fg_rrcurveto(g,
				      (xcoord[cs] + 2.0 * xcoord[cs + 1]) / 3.0,
				      (ycoord[cs] + 2.0 * ycoord[cs + 1]) / 3.0,
				      (2.0 * xcoord[cs + 1] + xcoord[ce]) / 3.0,
				      (2.0 * ycoord[cs + 1] + ycoord[ce]) / 3.0,
					    xcoord[ce],
					    ycoord[ce]
					);
				xlast = xcoord[ce];
				ylast = ycoord[ce];

				break;

			case 2:
				fg_rrcurveto(g,
				     (-xcoord[cs] + 4.0 * xcoord[cs + 1]) / 3.0,
				     (-ycoord[cs] + 4.0 * ycoord[cs + 1]) / 3.0,
				      (4.0 * xcoord[cs + 2] - xcoord[ce]) / 3.0,
				      (4.0 * ycoord[cs + 2] - ycoord[ce]) / 3.0,
					    xcoord[ce],
					    ycoord[ce]
					);
				xlast = xcoord[ce];
				ylast = ycoord[ce];
				break;

			case 3:
				fg_rrcurveto(g,
				      (xcoord[cs] + 2.0 * xcoord[cs + 1]) / 3.0,
				      (ycoord[cs] + 2.0 * ycoord[cs + 1]) / 3.0,
				  (5.0 * xcoord[cs + 1] + xcoord[cs + 2]) / 6.0,
				  (5.0 * ycoord[cs + 1] + ycoord[cs + 2]) / 6.0,
				      (xcoord[cs + 1] + xcoord[cs + 2]) / 2.0,
				      (ycoord[cs + 1] + ycoord[cs + 2]) / 2.0
					);

				fg_rrcurveto(g,
				  (xcoord[cs + 1] + 5.0 * xcoord[cs + 2]) / 6.0,
				  (ycoord[cs + 1] + 5.0 * ycoord[cs + 2]) / 6.0,
				  (5.0 * xcoord[cs + 2] + xcoord[cs + 3]) / 6.0,
				  (5.0 * ycoord[cs + 2] + ycoord[cs + 3]) / 6.0,
				      (xcoord[cs + 3] + xcoord[cs + 2]) / 2.0,
				      (ycoord[cs + 3] + ycoord[cs + 2]) / 2.0
					);

				fg_rrcurveto(g,
				  (xcoord[cs + 2] + 5.0 * xcoord[cs + 3]) / 6.0,
				  (ycoord[cs + 2] + 5.0 * ycoord[cs + 3]) / 6.0,
				      (2.0 * xcoord[cs + 3] + xcoord[ce]) / 3.0,
				      (2.0 * ycoord[cs + 3] + ycoord[ce]) / 3.0,
					    xcoord[ce],
					    ycoord[ce]
					);
				ylast = ycoord[ce];
				xlast = xcoord[ce];

				break;

			default:
				k1 = cs + nguide;
				fg_rrcurveto(g,
				      (xcoord[cs] + 2.0 * xcoord[cs + 1]) / 3.0,
				      (ycoord[cs] + 2.0 * ycoord[cs + 1]) / 3.0,
				  (5.0 * xcoord[cs + 1] + xcoord[cs + 2]) / 6.0,
				  (5.0 * ycoord[cs + 1] + ycoord[cs + 2]) / 6.0,
				      (xcoord[cs + 1] + xcoord[cs + 2]) / 2.0,
				      (ycoord[cs + 1] + ycoord[cs + 2]) / 2.0
					);

				for (k = cs + 2; k <= k1 - 1; k++) {
					fg_rrcurveto(g,
					(xcoord[k - 1] + 5.0 * xcoord[k]) / 6.0,
					(ycoord[k - 1] + 5.0 * ycoord[k]) / 6.0,
					(5.0 * xcoord[k] + xcoord[k + 1]) / 6.0,
					(5.0 * ycoord[k] + ycoord[k + 1]) / 6.0,
					    (xcoord[k] + xcoord[k + 1]) / 2.0,
					     (ycoord[k] + ycoord[k + 1]) / 2.0
						);

				}

				fg_rrcurveto(g,
				      (xcoord[k1 - 1] + 5.0 * xcoord[k1]) / 6.0,
				      (ycoord[k1 - 1] + 5.0 * ycoord[k1]) / 6.0,
					  (2.0 * xcoord[k1] + xcoord[ce]) / 3.0,
					  (2.0 * ycoord[k1] + ycoord[ce]) / 3.0,
					    xcoord[ce],
					    ycoord[ce]
					);
				xlast = xcoord[ce];
				ylast = ycoord[ce];

				break;
			}
		}
		if (i >= contour_end) {
			g_closepath(g);
			first = 1;
			i = contour_end + 1;
			j++;
		} else {
			i++;
		}
	}

	if (matrix) {
		/* guess whether do we need to reverse the results */

		double             x[3], y[3];
		int                max = 0, from, to;

		/* transform a triangle going in proper direction */
		/*
		 * the origin of triangle is in (0,0) so we know it in
		 * advance
		 */

		x[0] = y[0] = 0;
		x[1] = matrix[0] * 0 + matrix[2] * 300;
		y[1] = matrix[1] * 0 + matrix[3] * 300;
		x[2] = matrix[0] * 300 + matrix[2] * 0;
		y[2] = matrix[1] * 300 + matrix[3] * 0;

		/* then find the topmost point */
		for (i = 0; i < 3; i++)
			if (y[i] > y[max])
				max = i;
		from = (max + 3 - 1) % 3;
		to = (max + 1) % 3;

		needreverse = 0;

		/* special cases for horizontal lines */
		if (y[max] == y[from]) {
			if (x[max] < y[from])
				needreverse = 1;
		} else if (y[to] == y[from]) {
			if (x[to] < x[max])
				needreverse = 1;
		} else {	/* generic case */
			if ((x[to] - x[max]) * (y[max] - y[from])
			    > (x[max] - x[from]) * (y[to] - y[max]))
				needreverse = 1;
		}

		if (needreverse) {
			if (lge) {
				assertpath(lge->next, __FILE__, __LINE__, g->name);
				reversepathsfromto(lge->next, NULL);
			} else {
				assertpath(g->entries, __FILE__, __LINE__, g->name);
				reversepaths(g);
			}
		}
	}
}

static double
f2dot14(
	short x
)
{
	short           y = ntohs(x);
	return (y >> 14) + ((y & 0x3fff) / 16384.0);
}


/* check that the pointer points within the file */
/* returns 0 if pointer is good, 1 if bad */
static int
badpointer(
	void *ptr
)
{
	return (ptr < (void *)filebuffer || ptr >= (void *)filebuffer_end);
}

/*
 * Externally accessible methods
 */

/*
 * Open font and prepare to return information to the main driver.
 * May print error and warning messages.
 * Exit on error.
 */

static void
openfont(
	char *fname,
	char *arg /* unused now */
)
{
	int             i, j;
	struct stat     statbuf;
	static struct {
		void **tbpp; /* pointer to pointer to the table */
		char name[5]; /* table name */
		char optional; /* flag: table may be missing */
	} tables[] = {
		{ (void **)&name_table, "name", 0 },
		{ (void **)&head_table, "head", 0 },
		{ (void **)&hhea_table, "hhea", 0 },
		{ (void **)&post_table, "post", 0 },
		{ (void **)&glyf_start, "glyf", 0 },
		{ (void **)&cmap_table, "cmap", 0 },
		{ (void **)&kern_table, "kern", 1 },
		{ (void **)&maxp_table, "maxp", 0 },
		{ (void **)&hmtx_table, "hmtx", 0 },
		{ (void **)&long_loca_table, "loca", 0 },
		{ NULL, "", 0 } /* end of table */
	};

	if (stat(fname, &statbuf) == -1) {
		fprintf(stderr, "**** Cannot access %s ****\n", fname);
		exit(1);
	}
	if ((filebuffer = malloc(statbuf.st_size)) == NULL) {
		fprintf(stderr, "**** Cannot malloc space for file ****\n");
		exit(1);
	}

	filebuffer_end = filebuffer + statbuf.st_size;

	if ((ttf_file = fopen(fname, "rb")) == NULL) {
		fprintf(stderr, "**** Cannot open file '%s'\n", fname);
		exit(1);
	} else {
		WARNING_2 fprintf(stderr, "Processing file %s\n", fname);
	}

	if (fread(filebuffer, 1, statbuf.st_size, ttf_file) != statbuf.st_size) {
		fprintf(stderr, "**** Could not read whole file \n");
		exit(1);
	}
	fclose(ttf_file);

	directory = (TTF_DIRECTORY *) filebuffer;

	if (ntohl(directory->sfntVersion) != 0x00010000) {
		fprintf(stderr,
			"**** Unknown File Version number [%x], or not a TrueType file\n",
			directory->sfntVersion);
		exit(1);
	}

	/* clear the tables */
	for(j=0; tables[j].tbpp != NULL; j++)
		*(tables[j].tbpp) = NULL;

	dir_entry = &(directory->list);

	for (i = 0; i < ntohs(directory->numTables); i++) {

		for(j=0; tables[j].tbpp != NULL; j++)
			if (memcmp(dir_entry->tag, tables[j].name, 4) == 0) {
				*(tables[j].tbpp) = (void *) (filebuffer + ntohl(dir_entry->offset));
				break;
			}

		if (memcmp(dir_entry->tag, "EBDT", 4) == 0 ||
			   memcmp(dir_entry->tag, "EBLC", 4) == 0 ||
			   memcmp(dir_entry->tag, "EBSC", 4) == 0) {
			WARNING_1 fprintf(stderr, "Font contains bitmaps\n");
		}
		dir_entry++;
	}

	for(j=0; tables[j].tbpp != NULL; j++)
		if(!tables[j].optional && badpointer( *(tables[j].tbpp) )) {
			fprintf(stderr, "**** File contains no required table '%s'\n", tables[j].name);
			exit(1);
		}

	handle_name();

	handle_head();

	ttf_nglyphs = ntohs(maxp_table->numGlyphs);

	enc_found_ms = enc_found_mac = 0;
}

/*
 * Close font.
 * Exit on error.
 */

static void
closefont(
	void
)
{
	return; /* empty operation */
}

/*
 * Get the number of glyphs in font.
 */

static int
getnglyphs (
	void
)
{
	return ttf_nglyphs;
}

/*
 * Get the names of the glyphs.
 * Returns 0 if the names were assigned, non-zero if the font
 * provides no glyph names.
 */

static int
glnames(
	GLYPH *glyph_list
)
{
	int             i, len, n, npost;
	unsigned int    format;
	USHORT         *name_index;
	char           *ptr, *p;
	char          **ps_name_ptr = (char **) malloc(ttf_nglyphs * sizeof(char *));
	int             n_ps_names;
	int             ps_fmt_3 = 0;

	format = ntohl(post_table->formatType);

	if (format == 0x00010000) {
		for (i = 0; i < 258 && i < ttf_nglyphs; i++) {
			glyph_list[i].name = mac_glyph_names[i];
		}
	} else if (format == 0x00020000) {
                npost = ntohs(post_table->numGlyphs);
                if (ttf_nglyphs != npost) {
                        /* This is an error in the font, but we can now cope */
                        WARNING_1 fprintf(stderr, "**** Postscript table size mismatch %d/%d ****\n",
                                npost, ttf_nglyphs);
                }
                n_ps_names = 0;
                name_index = &(post_table->glyphNameIndex);

                /* This checks the integrity of the post table */       
                for (i=0; i<npost; i++) {
                    n = ntohs(name_index[i]);
                    if (n > n_ps_names + 257) {
                        n_ps_names = n - 257;
                    }
                }

                ptr = (char *) post_table + 34 + (ttf_nglyphs << 1);
                i = 0;
                while (*ptr > 0 && i < n_ps_names) {
                        len = *ptr;
                        /* previously the program wrote nulls into the table. If the table
                           was corrupt, this could put zeroes anywhere, leading to obscure bugs,
                           so now I malloc space for the names. Yes it is much less efficient */
                           
                        if ((p = malloc(len+1)) == NULL) {
                            fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
                            exit(255);
                        }
                        
                        ps_name_ptr[i] = p;
                        strncpy(p, ptr+1, len);
                        p[len] = '\0';
                        i ++;
                        ptr += len + 1;
                }
        
                if (i != n_ps_names)
                {
                    WARNING_2 fprintf (stderr, "** Postscript Name mismatch %d != %d **\n",
                             i, n_ps_names);
                    n_ps_names = i;
                }

                /*
                 * for (i=0; i<n_ps_names; i++) { fprintf(stderr, "i=%d,
                 * len=%d, name=%s\n", i, ps_name_len[i], ps_name_ptr[i]); }
                 */

                for (i = 0; i < npost; i++) {
                        n = ntohs(name_index[i]);
                        if (n < 258) {
                                glyph_list[i].name = mac_glyph_names[n];
                        } else if (n < 258 + n_ps_names) {
                                glyph_list[i].name = ps_name_ptr[n - 258];
                        } else {
                                glyph_list[i].name = malloc(16);
                                sprintf(glyph_list[i].name, "_g_%d", i);
                                WARNING_2 fprintf(stderr,
                                        "Glyph No. %d has no postscript name, becomes %s\n",
                                        i, glyph_list[i].name);
                        }
                }
                /* Now fake postscript names for all those beyond the end of the table */
                if (npost < ttf_nglyphs) {
                    for (i=npost; i<ttf_nglyphs; i++) {
                        if ((glyph_list[i].name = malloc(16)) == NULL)
                        {
                            fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
                            exit(255);
                        }
                        sprintf(glyph_list[i].name, "_g_%d", i);
                        WARNING_2 fprintf(stderr,
                                "Glyph No. %d has no postscript name, becomes %s\n",
                                i, glyph_list[i].name);
                    }
                }
	} else if (format == 0x00030000) {
		WARNING_3 fputs("No postscript table, using default\n", stderr);
		ps_fmt_3 = 1;
	} else if (format == 0x00028000) {
		ptr = (char *) &(post_table->numGlyphs);
		for (i = 0; i < ttf_nglyphs; i++) {
			glyph_list[i].name = mac_glyph_names[i + ptr[i]];
		}
	} else {
		fprintf(stderr,
			"**** Postscript table in wrong format %x ****\n",
			format);
		exit(1);
	}

	return ps_fmt_3;
}

/*
 * Get the metrics of the glyphs.
 */

static void
glmetrics(
	GLYPH *glyph_list
)
{
	int             i;
	int             n_hmetrics = ntohs(hhea_table->numberOfHMetrics);
	GLYPH          *g;
	LONGHORMETRIC  *hmtx_entry = hmtx_table;
	FWORD          *lsblist;

	for (i = 0; i < n_hmetrics; i++) {
		g = &(glyph_list[i]);
		g->width = ntohs(hmtx_entry->advanceWidth);
		g->lsb = ntohs(hmtx_entry->lsb);
		hmtx_entry++;
	}

	lsblist = (FWORD *) hmtx_entry;
	hmtx_entry--;

	for (i = n_hmetrics; i < ttf_nglyphs; i++) {
		g = &(glyph_list[i]);
		g->width = ntohs(hmtx_entry->advanceWidth);
		g->lsb = ntohs(lsblist[i - n_hmetrics]);
	}

	for (i = 0; i < ttf_nglyphs; i++) {
		g = &(glyph_list[i]);
		get_glyf_table(i, &glyf_table, &g->ttf_pathlen);

		g->xMin = (short)ntohs(glyf_table->xMin);
		g->xMax = (short)ntohs(glyf_table->xMax);
		g->yMin = (short)ntohs(glyf_table->yMin);
		g->yMax = (short)ntohs(glyf_table->yMax);
	}

}


static void
handle_ms_encoding(
	GLYPH *glyph_list,
	int *encoding,
	int *unimap
)
{
	int             i, j, k, kk, set_ok;
	USHORT          start, end, ro;
	short           delta, n;

	for (j = 0; j < cmap_n_segs - 1; j++) {
		start = ntohs(cmap_seg_start[j]);
		end = ntohs(cmap_seg_end[j]);
		delta = ntohs(cmap_idDelta[j]);
		ro = ntohs(cmap_idRangeOffset[j]);

		for (k = start; k <= end; k++) {
			if (ro == 0) {
				n = k + delta;
			} else {
				n = ntohs(*((ro >> 1) + (k - start) +
				 &(cmap_idRangeOffset[j])));
				if (delta != 0)
				{
					/*  Not exactly sure how to deal with this circumstance,
						I suspect it never occurs */
					n += delta;
					fprintf (stderr,
						 "rangeoffset and delta both non-zero - %d/%d",
						 ro, delta);
				}
			}
			if(n<0 || n>=ttf_nglyphs) {
				WARNING_1 fprintf(stderr, "Font contains a broken glyph code mapping, ignored\n");
				continue;
			}

            // Find the first available orig_code slot
            for (i = 0; i < GLYPH_MAX_ENCODINGS; i++ ) {

              if ( glyph_list[n].orig_code[i] == -1 )
                break;

            }

            if ( i == GLYPH_MAX_ENCODINGS ) {
              //#if 0
				if (strcmp(glyph_list[n].name, ".notdef") != 0) {
					WARNING_2 fprintf(stderr,
						"Glyph %s has >= %d encodings (A), %4.4x & %4.4x\n",
                                      glyph_list[n].name,
                                      GLYPH_MAX_ENCODINGS,
                                      glyph_list[n].orig_code[0],
                                      k);
				}
                //#endif
				set_ok = 0;
                break;
			} else {
				set_ok = 1;
			}
			if (enc_type==1 || forcemap) {
				kk = unicode_rev_lookup(k);
				if(ISDBG(UNICODE))
					fprintf(stderr, "Unicode %s - 0x%04x\n",glyph_list[n].name,k);
				if (set_ok) {
					glyph_list[n].orig_code[i] = k;
					/* glyph_list[n].char_no = kk; */
				}
				if (kk >= 0 && kk < ENCTABSZ && encoding[kk] == -1)
					encoding[kk] = n;
			} else {
				if ((k & 0xff00) == 0xf000) {
					if( encoding[k & 0x00ff] == -1 ) {
						encoding[k & 0x00ff] = n;
						if (set_ok) {
							/* glyph_list[n].char_no = k & 0x00ff; */
							glyph_list[n].orig_code[i] = k;
						}
					}
				} else {
					if (set_ok) {
						/* glyph_list[n].char_no = k; */
						glyph_list[n].orig_code[i] = k;
					}
					WARNING_2 fprintf(stderr,
						"Glyph %s has non-symbol encoding %4.4x\n",
					 glyph_list[n].name,
						k & 0xffff);
					/*
					 * just use the code
					 * as it is
					 */
					if ((k & ~0xff) == 0 && encoding[k] == -1 )
						encoding[k] = n;
				}
			}
		}
	}
}

static void
handle_mac_encoding(
	GLYPH *glyph_list,
	int *encoding,
	int *unimap
)
{
	short           n;
	int             j, size;

	size = ntohs(encoding0->length) - 6;
	for (j = 0; j < size; j++) {
		n = encoding0->glyphIdArray[j];
		if (glyph_list[n].char_no != -1) {
			WARNING_2 fprintf(stderr,
				"Glyph %s has >= two encodings (B), %4.4x & %4.4x\n",
				glyph_list[n].name,
				  glyph_list[n].char_no,
				j);
		} else {
			if (j < ENCTABSZ) {
				if(encoding[j] == -1) {
					glyph_list[n].char_no = j;
					encoding[j] = n;
				}
			}
		}
	}
}

/*
 * Get the original encoding of the font. 
 * Returns 1 for if the original encoding is Unicode, 2 if the
 * original encoding is other 16-bit, 0 if 8-bit.
 */

static int
glenc(
	GLYPH *glyph_list,
	int *encoding,
	int *unimap
)
{
	int             num_tables = ntohs(cmap_table->numberOfEncodingTables);
	BYTE           *ptr;
	int             i, format, offset, seg_c2, found;
	int             platform, encoding_id;
	TTF_CMAP_ENTRY *table_entry;
	TTF_CMAP_FMT4  *encoding4;

	if(enc_found_ms) {
		handle_ms_encoding(glyph_list, encoding, unimap);
		return enc_type;
	} else if(enc_found_mac) {
		handle_mac_encoding(glyph_list, encoding, unimap);
		return 0;
	}

	enc_type = 0;
	found = 0;

	for (i = 0; i < num_tables && !found; i++) {
		table_entry = &(cmap_table->encodingTable[i]);
		offset = ntohl(table_entry->offset);
		encoding4 = (TTF_CMAP_FMT4 *) ((BYTE *) cmap_table + offset);
		format = ntohs(encoding4->format);
		platform = ntohs(table_entry->platformID);
		encoding_id = ntohs(table_entry->encodingID);

		if (format != 4)
			continue;

		if(force_pid != -1) {
			if(force_pid != platform || encoding_id != force_eid)
				continue;
			WARNING_1 fprintf(stderr, "Found Encoding PID=%d/EID=%d\n", 
				force_pid, force_eid);
			enc_type = 1;
		} else {
			if (platform == 3 ) {
				switch (encoding_id) {
				case 0:
					WARNING_1 fputs("Found Symbol Encoding\n", stderr);
					break;
				case 1:
					WARNING_1 fputs("Found Unicode Encoding\n", stderr);
					enc_type = 1;
					break;
				default:
					WARNING_1 {
						fprintf(stderr,
						"****MS Encoding ID %d not supported****\n",
							encoding_id);
						fputs("Treating it like Symbol encoding\n", stderr);
					}
					break;
				}
			} else
				continue;
		}

		found = 1;
		seg_c2 = ntohs(encoding4->segCountX2);
		cmap_n_segs = seg_c2 >> 1;
		ptr = (BYTE *) encoding4 + 14;
		cmap_seg_end = (USHORT *) ptr;
		cmap_seg_start = (USHORT *) (ptr + seg_c2 + 2);
		cmap_idDelta = (short *) (ptr + (seg_c2 * 2) + 2);
		cmap_idRangeOffset = (short *) (ptr + (seg_c2 * 3) + 2);
		enc_found_ms = 1;

		handle_ms_encoding(glyph_list, encoding, unimap);
	}

	if (!found && force_pid == -1) {
		WARNING_1 fputs("No Microsoft encoding, looking for MAC encoding\n", stderr);
		for (i = 0; i < num_tables && !found; i++) {
			table_entry = &(cmap_table->encodingTable[i]);
			offset = ntohl(table_entry->offset);
			encoding0 = (TTF_CMAP_FMT0 *) ((BYTE *) cmap_table + offset);
			format = ntohs(encoding0->format);
			platform = ntohs(table_entry->platformID);
			encoding_id = ntohs(table_entry->encodingID);

			if (format == 0) {
				found = 1;
				enc_found_mac = 1;

				handle_mac_encoding(glyph_list, encoding, unimap);
			}
		}
	}

	if (!found && force_pid == -1) {
		WARNING_1 fputs("No MAC encoding either, looking for any format 4 encoding\n", stderr);
		for (i = 0; i < num_tables && !found; i++) {
			table_entry = &(cmap_table->encodingTable[i]);
			offset = ntohl(table_entry->offset);
			encoding4 = (TTF_CMAP_FMT4 *) ((BYTE *) cmap_table + offset);
			format = ntohs(encoding4->format);
			platform = ntohs(table_entry->platformID);
			encoding_id = ntohs(table_entry->encodingID);

			if (format != 4)
				continue;

			WARNING_1 fprintf(stderr, "Found a last ditch encoding PID=%d/EID=%d, treating it as Unicode.\n", 
				platform, encoding_id);
			found = 1;
			enc_type = 1;
			seg_c2 = ntohs(encoding4->segCountX2);
			cmap_n_segs = seg_c2 >> 1;
			ptr = (BYTE *) encoding4 + 14;
			cmap_seg_end = (USHORT *) ptr;
			cmap_seg_start = (USHORT *) (ptr + seg_c2 + 2);
			cmap_idDelta = (short *) (ptr + (seg_c2 * 2) + 2);
			cmap_idRangeOffset = (short *) (ptr + (seg_c2 * 3) + 2);
			enc_found_ms = 1;

			handle_ms_encoding(glyph_list, encoding, unimap);
		
		}
	}

	if (!found) {
		if(force_pid != -1) {
			fprintf(stderr, "*** TTF encoding table PID=%d/EID=%d not found\n", 
				force_pid, force_eid);
		}
		fprintf(stderr, "**** No Recognised Encoding Table ****\n");
		if(warnlevel >= 2) {
			fprintf(stderr, "Font contains the following unsupported encoding tables:\n");
			for (i = 0; i < num_tables && !found; i++) {
				table_entry = &(cmap_table->encodingTable[i]);
				offset = ntohl(table_entry->offset);
				encoding0 = (TTF_CMAP_FMT0 *) ((BYTE *) cmap_table + offset);
				format = ntohs(encoding0->format);
				platform = ntohs(table_entry->platformID);
				encoding_id = ntohs(table_entry->encodingID);

				fprintf(stderr, "  format=%d platform=%d encoding_id=%d\n",
					format, platform, encoding_id);
			}
		}
		exit(1);
	}

	return enc_type;
}

/*
 * Get the font metrics
 */
static void 
fnmetrics(
	struct font_metrics *fm
)
{
	char *str;
	static int fieldstocheck[]= {2,4,6};
	int i, j, len;

	fm->italic_angle = (short) (ntohs(post_table->italicAngle.upper)) +
		((short) ntohs(post_table->italicAngle.lower) / 65536.0);
	fm->underline_position = (short) ntohs(post_table->underlinePosition);
	fm->underline_thickness = (short) ntohs(post_table->underlineThickness);
	fm->is_fixed_pitch = ntohl(post_table->isFixedPitch);

	fm->ascender = (short)ntohs(hhea_table->ascender);
	fm->descender = (short)ntohs(hhea_table->descender);

	fm->units_per_em =  ntohs(head_table->unitsPerEm);

	fm->bbox[0] = (short) ntohs(head_table->xMin);
	fm->bbox[1] = (short) ntohs(head_table->yMin);
	fm->bbox[2] = (short) ntohs(head_table->xMax);
	fm->bbox[3] = (short) ntohs(head_table->yMax);

	fm->name_copyright = name_fields[0];
	fm->name_family = name_fields[1];
	fm->name_style = name_fields[2];
	fm->name_full = name_fields[4];
	fm->name_version = name_fields[5];
	fm->name_ps = name_fields[6];

	/* guess the boldness from the font names */
	fm->force_bold=0;

	for(i=0; !fm->force_bold && i<sizeof fieldstocheck /sizeof(int); i++) {
		str = name_fields[fieldstocheck[i]];
		len = strlen(str);
		for(j=0; j<len; j++) {
			if( (str[j]=='B'
				|| str[j]=='b' 
					&& ( j==0 || !isalpha(str[j-1]) )
				)
			&& !strncmp("old",&str[j+1],3)
			&& (j+4 >= len || !islower(str[j+4]))
			) {
				fm->force_bold=1;
				break;
			}
		}
	}
}

/*
 * Get the path of contrours for a glyph.
 */

static void
glpath(
	int glyphno,
	GLYPH *glyf_list
)
{
	double          matrix[6];
	GLYPH          *g;

	g = &glyph_list[glyphno];

	matrix[0] = matrix[3] = 1.0;
	matrix[1] = matrix[2] = matrix[4] = matrix[5] = 0.0;
	draw_composite_glyf(g, glyf_list, glyphno, matrix, 0 /*level*/);
}

/*
 * Get the kerning data.
 */

static void
kerning(
	GLYPH *glyph_list
)
{
	TTF_KERN_SUB   *subtable;
	TTF_KERN_ENTRY *kern_entry;
	int             i, j;
	int             ntables;
	int             npairs;
	char           *ptr;

	if(kern_table == NULL) {
        WARNING_1 fputs("No Kerning data\n", stderr);
		return;
	}
	if(badpointer(kern_table)) {
        fputs("**** Defective Kerning table, ignored\n", stderr);
		return;
	}

	ntables = ntohs(kern_table->nTables);
	ptr = (char *) kern_table + 4;

	for (i = 0; i < ntables; i++) {
		subtable = (TTF_KERN_SUB *) ptr;
		if ((ntohs(subtable->coverage) & 0xff00) == 0) {
			npairs = (short) ntohs(subtable->nPairs);
			kern_entry = (TTF_KERN_ENTRY *) (ptr + sizeof(TTF_KERN_SUB));

			kern_entry = (TTF_KERN_ENTRY *) (ptr + sizeof(TTF_KERN_SUB));
			for (j = 0; j < npairs; j++) {
				if( kern_entry->value != 0)
					addkernpair(ntohs(kern_entry->left), 
						ntohs(kern_entry->right), (short)ntohs(kern_entry->value));
				kern_entry++;
			}
		}
		ptr += subtable->length;
	}
}

