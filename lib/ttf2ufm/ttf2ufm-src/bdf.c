/*
 * The font parser for the BDF files
 *
 * Copyright (c) 2001 by the TTF2PT1 project
 * Copyright (c) 2001 by Sergey Babkin
 *
 * see COPYRIGHT for the full copyright notice
 */

#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <ctype.h>
#include "pt1.h"
#include "global.h"

/* prototypes of call entries */
static void openfont(char *fname, char *arg);
static void closefont( void);
static int getnglyphs ( void);
static int glnames( GLYPH *glyph_list);
static void readglyphs( GLYPH *glyph_list);
static int glenc( GLYPH *glyph_list, int *encoding, int *unimap);
static void fnmetrics( struct font_metrics *fm);
static void glpath( int glyphno, GLYPH *glyph_list);
static void kerning( GLYPH *glyph_list);

/* globals */

/* front-end descriptor */
struct frontsw bdf_sw = {
	/*name*/       "bdf",
	/*descr*/      "BDF bitmapped fonts",
	/*suffix*/     { "bdf" },
	/*open*/       openfont,
	/*close*/      closefont,
	/*nglyphs*/    getnglyphs,
	/*glnames*/    glnames,
	/*glmetrics*/  readglyphs,
	/*glenc*/      glenc,
	/*fnmetrics*/  fnmetrics,
	/*glpath*/     glpath,
	/*kerning*/    kerning,
};

/* statics */

#define MAXLINE	10240 /* maximal line length in the input file */

static int lineno; /* line number */

#define GETLEN(s)	s, (sizeof(s)-1)
#define LENCMP(str, txt)	strncmp(str, txt, sizeof(txt)-1)

static FILE *bdf_file;
static int nglyphs;
static struct font_metrics fmet;

/* many BDF fonts are of small pixel size, so we better try
 * to scale them by an integer to keep the dimensions in
 * whole pixels. However if the size is too big and a non-
 * integer scaling is needed, we use the standard ttf2pt1's
 * scaling abilities.
 */
static int pixel_size;
static int scale;
static int scale_external;

static char *slant;
static char xlfdname[201];
static char *spacing;
static char *charset_reg;
static char *charset_enc;
static char *fnwidth;
static int is_unicode = 0;

/* tempoary storage for returning data to ttf2pt1 later on request */
static int maxenc = 0;
static int *fontenc;
static GENTRY **glpaths;

static int got_glyphs = 0;
static GLYPH *glyphs;
static int curgl;

static int readfile(FILE *f, int (*strfunc)(int len, char *str));

/*
 * Read the file and parse each string with strfunc(),
 * until strfunc() returns !=0 or the end of file happens.
 * Returns -1 on EOF or strfunc() returning <0, else 0
 */

static int
readfile(
	FILE *f,
	int (*strfunc)(int len, char *str)
)
{
	static char str[MAXLINE]; /* input line, maybe should be dynamic ? */
	char *s;
	int len, c, res;

	len=0;
	while(( c=getc(f) )!=EOF) {
		if(c=='\n') {
			str[len]=0;

			res = strfunc(len, str);
			lineno++;
			if(res<0)
				return -1;
			else if(res!=0)
				return 0;

			len=0;
		} else if(len<MAXLINE-1) {
			if(c!='\r')
				str[len++]=c;
		} else {
			fprintf(stderr, "**** bdf: line %d is too long (>%d)\n", lineno, MAXLINE-1);
			exit(1);
		}
	}
	return -1; /* EOF */
}

/*
 * Parse the header of the font file. 
 * Stop after the line CHARS is encountered. Ignore the unknown lines.
 */

struct line {
	char *name; /* property name with trailing space */
	int namelen; /* length of the name string */
	enum {
		ALLOW_REPEAT = 0x01, /* this property may be repeated in multiple lines */
		IS_SEEN = 0x02, /* this property has been seen already */
		MUST_SEE = 0x04, /* this property must be seen */
		IS_LAST = 0x08 /* this is the last property to be read */
	} flags;
	char *fmt; /* format string for the arguments, NULL means a string arg */
	int nvals; /* number of values to be read by sscanf */
	void *vp[4]; /* pointers to values to be read */
};
		
static struct line header[] = {
	{ GETLEN("FONT "), 0, " %200s", 1, {&xlfdname} },
	{ GETLEN("SIZE "), MUST_SEE, " %d", 1, {&pixel_size} },
	{ GETLEN("FONTBOUNDINGBOX "), MUST_SEE, " %hd %hd %hd %hd", 4, 
		{&fmet.bbox[2], &fmet.bbox[3], &fmet.bbox[0], &fmet.bbox[1]} },
	{ GETLEN("FAMILY_NAME "), MUST_SEE, NULL, 1, {&fmet.name_family} },
	{ GETLEN("WEIGHT_NAME "), MUST_SEE, NULL, 1, {&fmet.name_style} },
	{ GETLEN("COPYRIGHT "), 0, NULL, 1, {&fmet.name_copyright} },
	{ GETLEN("SLANT "), MUST_SEE, NULL, 1, {&slant} },
	{ GETLEN("SPACING "), 0, NULL, 1, {&spacing} },
	{ GETLEN("SETWIDTH_NAME "), 0, NULL, 1, {&fnwidth} },
	{ GETLEN("CHARSET_REGISTRY "), 0, NULL, 1, {&charset_reg} },
	{ GETLEN("CHARSET_ENCODING "), 0, NULL, 1, {&charset_enc} },
	{ GETLEN("FONT_ASCENT "), 0, " %hd", 1, {&fmet.ascender} },
	{ GETLEN("FONT_DESCENT "), 0, " %hd", 1, {&fmet.descender} },

	/* these 2 must go in this order for post-processing */
	{ GETLEN("UNDERLINE_THICKNESS "), 0, " %hd", 1, {&fmet.underline_thickness} },
	{ GETLEN("UNDERLINE_POSITION "), 0, " %hd", 1, {&fmet.underline_position} },

	{ GETLEN("CHARS "), MUST_SEE|IS_LAST, " %d", 1, {&nglyphs} },
	{ NULL, 0, 0 } /* end mark: name==NULL */
};

static int
handle_header(
	int len,
	char *str
)
{
	struct line *cl;
	char *s, *p;
	char bf[2000];
	int c;

#if 0
	fprintf(stderr, "line: %s\n", str);
#endif
	for(cl = header; cl->name != 0; cl++) {
		if(strncmp(str, cl->name, cl->namelen))
			continue;
#if 0
		fprintf(stderr, "match: %s\n", cl->name);
#endif
		if(cl->flags & IS_SEEN) {
			if(cl->flags & ALLOW_REPEAT)
				continue;
			
			fprintf(stderr, "**** input line %d redefines the property %s\n", lineno, cl->name);
			exit(1);
		}
		cl->flags |= IS_SEEN;
		if(cl->fmt == 0) {
			if(len - cl->namelen + 1 > sizeof bf)
				len = sizeof bf; /* cut it down */

			s = bf; /* a temporary buffer to extract the value */

			/* skip until a quote */
			for(p = str+cl->namelen; len!=0 && (c = *p)!=0; p++, len--) {
				if(c == '"') {
					p++;
					break;
				}
			}
			for(; len!=0 && (c = *p)!=0; p++, len--) {
				if(c == '"') {
					c = *++p;
					if(c == '"')
						*s++ = c;
					else
						break;
				} else
					*s++ = c;
			}
			*s = 0; /* end of line */

			*((char **)(cl->vp[0])) = dupcnstring(bf, s-bf);
		} else {
			c = sscanf(str+cl->namelen, cl->fmt, cl->vp[0], cl->vp[1], cl->vp[2], cl->vp[3]);
			if(c != cl->nvals) {
				fprintf(stderr, "**** property %s at input line %d must have %d arguments\n", 
					cl->name, lineno, cl->nvals);
				exit(1);
			}
		}
		if(cl->flags & IS_LAST)
			return 1;
		else
			return 0;
	}
	return 0;
}

/*
 * Parse the description of the glyphs
 */

static int
handle_glyphs(
	int len,
	char *str
)
{
	static int inbmap=0;
	static char *bmap;
	static int xsz, ysz, xoff, yoff;
	static int curln;
	int i, c;
	char *p, *plim, *psz;

	if(!LENCMP(str, "ENDFONT")) {
		if(curgl < nglyphs) {
			fprintf(stderr, "**** unexpected end of font file after %d glyphs\n", curgl);
			exit(1);
		} else
			return 1;
	}
	if(curgl >= nglyphs) {
		fprintf(stderr, "**** file contains more glyphs than advertised (%d)\n", nglyphs);
		exit(1);
	}
	if(!LENCMP(str, "STARTCHAR")) {
		/* sizeof will count \0 instead of ' ' */
		for(i=sizeof("STARTCHAR"); str[i] == ' '; i++) 
			{}

		glyphs[curgl].name = strdup(str + i);
		if(glyphs[curgl].name == 0) {
			fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
			exit(255);
		}
	} else if(!LENCMP(str, "ENCODING")) {
		if(sscanf(str, "ENCODING %d", &fontenc[curgl])!=1) {
			fprintf(stderr,"**** weird ENCODING statement at line %d\n", lineno);
			exit(1);
		}
		if(fontenc[curgl] == -1)  /* compatibility format */
			sscanf(str, "ENCODING -1 %d", &fontenc[curgl]);
		if(fontenc[curgl] > maxenc)
			maxenc = fontenc[curgl];
	} else if(!LENCMP(str, "DWIDTH")) {
		if(sscanf(str, "DWIDTH %d %d", &xsz, &ysz)!=2) {
			fprintf(stderr,"**** weird DWIDTH statement at line %d\n", lineno);
			exit(1);
		}
		glyphs[curgl].width = xsz*scale;
	} else if(!LENCMP(str, "BBX")) {
		if(sscanf(str, "BBX %d %d %d %d", &xsz, &ysz, &xoff, &yoff)!=4) {
			fprintf(stderr,"**** weird BBX statement at line %d\n", lineno);
			exit(1);
		}
		bmap=malloc(xsz*ysz);
		if(bmap==0) {
			fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
			exit(255);
		}
		glyphs[curgl].lsb = -xoff*scale;
		glyphs[curgl].xMin = -xoff*scale;
		glyphs[curgl].xMax = (xsz-xoff)*scale;
		glyphs[curgl].yMin = -yoff*scale;
		glyphs[curgl].yMax = (ysz-xoff)*scale;
	} else if(!LENCMP(str, "BITMAP")) {
		inbmap=1; 
		curln=ysz-1; /* the lowest line has index 0 */
	} else if(!LENCMP(str, "ENDCHAR")) {
		inbmap=0;
		if(bmap) {
			glyphs[curgl].lastentry = 0;
			glyphs[curgl].path = 0;
			glyphs[curgl].entries = 0;
			bmp_outline(&glyphs[curgl], scale, bmap, xsz, ysz, xoff, yoff);
			free(bmap);
			/* remember in a static table or it will be erased */
			glpaths[curgl] = glyphs[curgl].entries;
			glyphs[curgl].entries = 0;

			if(glpaths[curgl])
				glyphs[curgl].ttf_pathlen = 1;
			else
				glyphs[curgl].ttf_pathlen = 0;
		}
		curgl++;
	} else if(inbmap) {
		if(curln<0) {
			fprintf(stderr,"**** bitmap is longer than %d lines at line %d\n", ysz, lineno);
			exit(1);
		}

		i=0;
		p=&bmap[curln*xsz]; psz=p+xsz;
		while(i<len) {
			c=str[i++];
			if(!isxdigit(c)) {
				fprintf(stderr,"**** non-hex digit in bitmap at line %d\n", lineno);
				exit(1);
			}
			if(c<='9')
				c-='0';
			else 
				c= tolower(c)-'a'+10;

			for(plim=p+4; p<psz && p<plim; c<<=1) 
				*p++ = (( c & 0x08 )!=0);
		}
		if(p<psz) {
			fprintf(stderr,"**** bitmap line is too short at line %d\n", lineno);
			exit(1);
		}
		curln--;
	}
	return 0;
}

/*
 * Read all the possible information about the glyphs
 */

static void
readglyphs(
	GLYPH *glyph_list
)
{
	int i;
	GLYPH *g;

	if(got_glyphs)
		return;

	/* pass them to handle_glyphs() through statics */
	glyphs = glyph_list;
	curgl = 2; /* skip the empty glyph and .notdef */

	/* initialize the empty glyph and .notdef */

	for(i=0; i<2; i++) {
		g = &glyphs[i];
		g->lsb = 0;
		g->width = fmet.bbox[2];
		g->xMin = 0;
		g->yMin = 0;
	}
	g = &glyphs[0];
	g->name = ".notdef";
	g->xMax = fmet.bbox[2]*4/5;
	g->yMax = fmet.bbox[3]*4/5;
	g->entries = g->path = g->lastentry = 0;
	/* make it look as a black square */
	fg_rmoveto(g, 0.0, 0.0);
	fg_rlineto(g, 0.0, (double)g->yMax);
	fg_rlineto(g, (double)g->xMax, (double)g->yMax);
	fg_rlineto(g, (double)g->xMax, 0.0);
	fg_rlineto(g, 0.0, 0.0);
	g_closepath(g);
	glpaths[0] = g->entries;
	g->entries = 0;
	g->ttf_pathlen = 4;

	g = &glyphs[1];
	g->name = ".null";
	g->xMax = g->yMax = 0;
	g->ttf_pathlen = 0;

	if(readfile(bdf_file, handle_glyphs) < 0) {
		fprintf(stderr, "**** file does not contain the ENDFONT line\n");
		exit(1);
	}
	got_glyphs = 1;
}

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
	struct line *cl;
	int i, l;

	if ((bdf_file = fopen(fname, "r")) == NULL) {
		fprintf(stderr, "**** Cannot open file '%s'\n", fname);
		exit(1);
	} else {
		WARNING_2 fprintf(stderr, "Processing file %s\n", fname);
	}

	lineno = 1;

	for(cl = header; cl->name != 0; cl++)
		cl->flags &= ~IS_SEEN;
	if(readfile(bdf_file, handle_header) < 0) {
		fprintf(stderr, "**** file does not contain the CHARS definition\n");
		exit(1);
	}
	for(cl = header; cl->name != 0; cl++) {
		if( (cl->flags & MUST_SEE) && !(cl->flags & IS_SEEN) ) {
			fprintf(stderr, "**** mandatory property %sis not found in the input line\n", 
				cl->name); /* cl->name has a space at the end */
			exit(1);
		}

		/* set a few defaults */
		if( !(cl->flags & IS_SEEN) ) {
			if(cl->vp[0] == &fmet.underline_thickness) {
				fmet.underline_thickness = 1;
			} else if(cl->vp[0] == &fmet.underline_position) {
				fmet.underline_position = fmet.bbox[1] + fmet.underline_thickness
					- (pixel_size - fmet.bbox[3]);
			} else if(cl->vp[0] == &fmet.ascender) {
				fmet.ascender = fmet.bbox[2] + fmet.bbox[0];
			} else if(cl->vp[0] == &fmet.descender) {
				fmet.descender = fmet.bbox[0];
			}
		}
	}

	nglyphs += 2; /* add empty glyph and .notdef */

	/* postprocessing to compensate for the differences in the metric formats */
	fmet.bbox[2] += fmet.bbox[0];
	fmet.bbox[3] += fmet.bbox[1];

	scale = 1000/pixel_size; /* XXX ? */
	if(scale*pixel_size < 950) {
		scale = 1;
		scale_external = 1;
		fmet.units_per_em = pixel_size;
	} else {
		scale_external = 0;
		fmet.units_per_em = scale*pixel_size;

		fmet.underline_position *= scale;
		fmet.underline_thickness *= scale;
		fmet.ascender *= scale;
		fmet.descender *= scale;
		for(i=0; i<4; i++)
			fmet.bbox[i] *= scale;
	}

	fmet.italic_angle = 0.0;
	if(spacing == 0 /* possibly an old font */ 
	|| toupper(spacing[0]) != 'P') /* or anything non-proportional */
		fmet.is_fixed_pitch = 1;
	else
		fmet.is_fixed_pitch = 0;

	if(fmet.name_copyright==NULL)
		fmet.name_copyright = "";
	
	/* create the full name */
	l = strlen(fmet.name_family) 
		+ (fmet.name_style? strlen(fmet.name_style) : 0)
		+ (fnwidth? strlen(fnwidth) : 0)
		+ strlen("Oblique") + 1;

	if(( fmet.name_full = malloc(l) )==NULL) {
		fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
		exit(255);
	}
	strcpy(fmet.name_full, fmet.name_family);
	if(fnwidth && strcmp(fnwidth, "Normal")) {
		strcat(fmet.name_full, fnwidth);
	}
	if(fmet.name_style && strcmp(fmet.name_style, "Medium")) {
		strcat(fmet.name_full, fmet.name_style);
	}
	switch(toupper(slant[0])) {
	case 'O':
		strcat(fmet.name_full, "Oblique");
		break;
	case 'I':
		strcat(fmet.name_full, "Italic");
		break;
	}

	fmet.name_ps = fmet.name_full;
	fmet.name_version = "1.0";

	if(charset_reg && charset_enc
	&& !strcmp(charset_reg, "iso10646") && !strcmp(charset_enc, "1"))
		is_unicode = 1;

	if(( fontenc = calloc(nglyphs, sizeof *fontenc) )==NULL) {
		fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
		exit(255);
	}
	for(i=0; i<nglyphs; i++)
		fontenc[i] = -1;
	if(( glpaths = calloc(nglyphs, sizeof *glpaths) )==NULL) {
		fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
		exit(255);
	}
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
	if(fclose(bdf_file) < 0) {
		WARNING_1 fprintf(stderr, "Errors when closing the font file, ignored\n");
	}
}

/*
 * Get the number of glyphs in font.
 */

static int
getnglyphs (
	void
)
{
	return nglyphs;
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
	readglyphs(glyph_list);
	return 0;
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
	int i, douni, e;

	if(is_unicode || forcemap)
		douni = 1;
	else
		douni = 0;

	for(i=0; i<nglyphs; i++) {
		e = fontenc[i];
		if(douni)
			e = unicode_rev_lookup(e);
		if(e>=0 && e<ENCTABSZ && encoding[e] == -1)
			encoding[e] = i;
	}

	if(is_unicode)
		return 1;
	else if(maxenc > 255)
		return 2;
	else
		return 0;
}
	
/*
 * Get the font metrics
 */
static void 
fnmetrics(
	struct font_metrics *fm
)
{
	*fm = fmet;
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
	readglyphs(glyf_list);
	glyf_list[glyphno].entries = glpaths[glyphno];
	glpaths[glyphno] = 0;
}

/*
 * Get the kerning data.
 */

static void
kerning(
	GLYPH *glyph_list
)
{
	return; /* no kerning in BDF */
}
