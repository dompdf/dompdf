/*
 * Handling of the bitmapped glyphs
 *
 * Copyright (c) 2001 by the TTF2PT1 project
 * Copyright (c) 2001 by Sergey Babkin
 *
 * see COPYRIGHT for the full copyright notice
 */

#include <stdio.h>
#include <stdlib.h>
#include <math.h>
#include "pt1.h"
#include "global.h"

/* possible values of limits */
#define L_NONE	0 /* nothing here */
#define L_ON	1 /* black is on up/right */
#define L_OFF	2 /* black is on down/left */

static int warnedhints = 0;


#ifdef USE_AUTOTRACE
#include <autotrace/autotrace.h>

/*
 * Produce an autotraced outline from a bitmap.
 * scale - factor to scale the sizes
 * bmap - array of dots by lines, xsz * ysz
 * xoff, yoff - offset of the bitmap's lower left corner
 *              from the logical position (0,0)
 */

static void
autotraced_bmp_outline(
	GLYPH *g,
	int scale,
	char *bmap,
	int xsz,
	int ysz,
	int xoff,
	int yoff
)
{
	at_bitmap_type atb;
	at_splines_type *atsp;
	at_fitting_opts_type *atoptsp;
	at_spline_list_type *slp;
	at_spline_type *sp;
	int i, j, k;
	double lastx, lasty;
	double fscale;
	char *xbmap;

	fscale = (double)scale;

	/* provide a white margin around the bitmap */
	xbmap = malloc((ysz+2)*(xsz+2));
	if(xbmap == 0)  {
		fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
		exit(255);
	}
	memset(xbmap, 0, xsz+2);  /* top margin */
	for(i=0, j=xsz+2; i<ysz; i++, j+=xsz+2) {
		xbmap[j] = 0; /* left margin */
		memcpy(&xbmap[j+1], &bmap[xsz*(ysz-1-i)], xsz); /* a line of bitmap */
		xbmap[j+xsz+1] = 0; /* right margin */
	}
	memset(xbmap+j, 0, xsz+2);  /* bottom margin */
	xoff--; yoff-=2; /* compensate for the margins */

	atoptsp = at_fitting_opts_new();

	atb.width = xsz+2;
	atb.height = ysz+2;
	atb.np = 1;
	atb.bitmap = xbmap;

	atsp = at_splines_new(&atb, atoptsp);

	lastx = lasty = -1.;
	for(i=0; i<atsp->length; i++) {
		slp = &atsp->data[i];
#if 0
		fprintf(stderr, "%s: contour %d: %d entries clockwise=%d color=%02X%02X%02X\n",
			g->name, i, slp->length, slp->clockwise, slp->color.r, slp->color.g, slp->color.b);
#endif
		if(slp->length == 0)
			continue;
#if 0
		if(slp->color.r + slp->color.g + slp->color.b == 0)
			continue;
#endif
		fg_rmoveto(g, fscale*(slp->data[0].v[0].x+xoff), fscale*(slp->data[0].v[0].y+yoff));
		for(j=0; j<slp->length; j++) {
#if 0
			fprintf(stderr, "  ");
			for(k=0; k<4; k++)
				fprintf(stderr, "(%g %g) ", 
					fscale*(slp->data[j].v[k].x+xoff), 
					fscale*(ysz-slp->data[j].v[k].y+yoff)
					);
			fprintf(stderr, "\n");
#endif
			fg_rrcurveto(g,
				fscale*(slp->data[j].v[1].x+xoff), fscale*(slp->data[j].v[1].y+yoff),
				fscale*(slp->data[j].v[2].x+xoff), fscale*(slp->data[j].v[2].y+yoff),
				fscale*(slp->data[j].v[3].x+xoff), fscale*(slp->data[j].v[3].y+yoff) );
		}
		g_closepath(g);
	}

	at_splines_free(atsp);
	at_fitting_opts_free(atoptsp);
	free(xbmap);
}

#endif /*USE_AUTOTRACE*/

/* an extension of gentry for description of the fragments */
typedef struct gex_frag GEX_FRAG;
struct gex_frag {
	/* indexes to len, the exact values and order are important */
#define GEXFI_NONE	-1
#define GEXFI_CONVEX	0
#define GEXFI_CONCAVE	1
#define GEXFI_LINE	2 /* a line with steps varying by +-1 pixel */
#define GEXFI_EXACTLINE	3 /* a line with exactly the same steps */
#define GEXFI_SERIF	4 /* small serifs at the ends of stemsi - must be last */
#define GEXFI_COUNT	5 /* maximal index + 1 */
	unsigned short len[GEXFI_COUNT]; /* length of various fragment types starting here */
	unsigned short lenback[GEXFI_COUNT]; /* length back to the start of curve */

	signed char ixstart; /* index of the frag type that starts here */
	signed char ixcont; /* index of the frag type that continues here */

	short flags;
#define GEXFF_HLINE	0x0001 /* the exact line is longer in "horizontal" dimension */
#define GEXFF_EXTR	0x0002 /* this gentry is an extremum in some direction */
#define GEXFF_CIRC	0x0004 /* the joint at this gentry is for a circular curve */
#define GEXFF_DRAWCURVE	0x0008 /* vect[] describes a curve to draw */
#define GEXFF_DRAWLINE	0x0010 /* vect[] describes a line to draw */
#define GEXFF_SPLIT	0x0020 /* is a result of splitting a line */
#define GEXFF_SYMNEXT	0x0040 /* this subfrag is symmetric with next one */
#define GEXFF_DONE	0x0080 /* this subfrag has been vectorized */
#define GEXFF_LONG	0x0100 /* this gentry is longer than 1 pixel */

	unsigned short aidx; /* index of gentry in the array representing the contour */
	
	unsigned short vectlen; /* number of gentries represented by vect[] */

	/* coordinates for vectored replacement of this fragment */
	/* (already scaled because it's needed for curve approximation) */
	double vect[4 /*ref.points*/][2 /*X,Y*/];

	double bbox[2 /*X,Y*/]; /* absolute sizes of bounding box of a subfragment */

	/* used when splitting the curved frags into subfrags */
	GENTRY *prevsub;  /* to gentries describing neighboring subfrags */
	GENTRY *nextsub;
	GENTRY *ordersub; /* single-linked list describing the order of calculation */

	int sublen; /* length of this subfrag */
	/* the symmetry across the subfrags */
	int symaxis; /* the symmetry axis, with next subfrag */
	int symxlen; /* min length of adjacent symmetric frags */
	/* the symmetry within this subfrag (the axis is always diagonal) */
	GENTRY *symge; /* symge->i{x,y}3 is the symmetry point of symge==0 if none */

};
#define	X_FRAG(ge)	((GEX_FRAG *)((ge)->ext))

/* various interesting tables related to GEX_FRAG */
static char *gxf_name[GEXFI_COUNT] = {"Convex", "Concave", "Line", "ExLine", "Serif"};
static int gxf_cvk[2] = {-1, 1}; /* coefficients of concaveness */

/*
 * Dump the contents of X_EXT()->len and ->lenback for a contour
 */
static void
gex_dump_contour(
	GENTRY *ge,
	int clen
)
{
	int i, j;

	for(j = 0; j < GEXFI_COUNT; j++) {
		fprintf(stderr, "%-8s", gxf_name[j]);
		for(i = 0; i < clen; i++, ge = ge->frwd)
			fprintf(stderr, " %2d", X_FRAG(ge)->len[j]);
		fprintf(stderr, " %p\n (back) ", ge);
		for(i = 0; i < clen; i++, ge = ge->frwd)
			fprintf(stderr, " %2d", X_FRAG(ge)->lenback[j]);
		fprintf(stderr, "\n");
	}
}

/*
 * Calculate values of X_EXT()->lenback[] for all entries in
 * a contour. The contour is identified by:
 *  ge - any gentry of the contour
 *  clen - contour length
 */

static void
gex_calc_lenback(
	GENTRY *ge,
	int clen
)
{
	int i, j;
	int end;
	GEX_FRAG *f;
	int len[GEXFI_COUNT]; /* length of the most recent fragment */
	int count[GEXFI_COUNT]; /* steps since beginning of the fragment */

	for(j = 0; j < GEXFI_COUNT; j++)
		len[j] = count[j] = 0;

	end = clen;
	for(i = 0; i < end; i++, ge = ge->frwd) {
		f = X_FRAG(ge);
		for(j = 0; j < GEXFI_COUNT; j++) {
			if(len[j] != count[j]) {
				f->lenback[j] = count[j]++;
			} else
				f->lenback[j] = 0;
			if(f->len[j] != 0) {
				len[j] = f->len[j];
				count[j] = 1; /* start with the next gentry */
				/* if the fragment will wrap over the start, process to its end */
				if(i < clen && i + len[j] > end) 
					end = i + len[j];
			}
		}
	}
	gex_dump_contour(ge, clen);
}

/* Limit a curve to not exceed the given coordinates
 * at its given side
 */

static void
limcurve(
	double curve[4][2 /*X,Y*/],
	double lim[2 /*X,Y*/],
	int where /* 0 - start, 3 - end */
)
{
	int other = 3-where; /* the other end */
	int sgn[2 /*X,Y*/]; /* sign for comparison */
	double t, from, to, nt, t2, nt2, tt[4];
	double val[2 /*X,Y*/];
	int i;

	for(i=0; i<2; i++)
		sgn[i] = fsign(curve[other][i] - curve[where][i]);

#if 0
	fprintf(stderr, "     limit (%g,%g)-(%g,%g) at %d by (%g,%g), sgn(%d,%d)\n",
		curve[0][0], curve[0][1], curve[3][0], curve[3][1],
		where, lim[0], lim[1], sgn[0], sgn[1]);
#endif
	/* a common special case */
	if( sgn[0]*(curve[where][0] - lim[0]) >= 0.
	&& sgn[1]*(curve[where][1] - lim[1]) >= 0. )
		return; /* nothing to do */

	if(other==0) {
		from = 0.;
		to = 1.;
	} else {
		from = 1.;
		to = 0.;
	}
#if 0
	fprintf(stderr, "t=");
#endif
	while( fabs(from-to) > 0.00001 ) {
		t = 0.5 * (from+to);
		t2 = t*t;
		nt = 1.-t;
		nt2 = nt*nt;
		tt[0] = nt2*nt;
		tt[1] = 3*nt2*t;
		tt[2] = 3*nt*t2;
		tt[3] = t*t2;
		for(i=0; i<2; i++)
			val[i] = curve[0][i]*tt[0] + curve[1][i]*tt[1]
				+ curve[2][i]*tt[2] + curve[3][i]*tt[3];
#if 0
		fprintf(stderr, "%g(%g,%g) ", t, val[0], val[1]);
#endif
		if(fabs(val[0] - lim[0]) < 0.1
		|| fabs(val[1] - lim[1]) < 0.1)
			break;

		if(sgn[0] * (val[0] - lim[0]) < 0.
		|| sgn[1] * (val[1] - lim[1]) < 0.)
			to = t;
		else
			from = t;
	}
	/* now t is the point of splitting */
#define SPLIT(pt1, pt2)	( (pt1) + t*((pt2)-(pt1)) ) /* order is important! */
	for(i=0; i<2; i++) {
		double v11, v12, v13, v21, v22, v31; /* intermediate points */

		v11 = SPLIT(curve[0][i], curve[1][i]);
		v12 = SPLIT(curve[1][i], curve[2][i]);
		v13 = SPLIT(curve[2][i], curve[3][i]);
		v21 = SPLIT(v11, v12);
		v22 = SPLIT(v12, v13);
		v31 = SPLIT(v21, v22);
		if(other==0) {
			curve[1][i] = v11;
			curve[2][i] = v21;
			curve[3][i] = fabs(v31 - lim[i]) < 0.1 ? lim[i] : v31;
		} else {
			curve[0][i] = fabs(v31 - lim[i]) < 0.1 ? lim[i] : v31;
			curve[1][i] = v22;
			curve[2][i] = v13;
		}
	}
#undef SPLIT
#if 0
	fprintf(stderr, "\n");
#endif
}

/*
 * Vectorize a subfragment of a curve fragment. All the data has been already
 * collected by this time
 */

static void
dosubfrag(
	GLYPH *g,
	int fti, /* fragment type index */
	GENTRY *firstge, /* first gentry of fragment */
	GENTRY *ge, /* first gentry of subfragment */
	double fscale
) 
{
	GENTRY *gel, *gei; /* last gentry of this subfrag */
	GEX_FRAG *f, *ff, *lf, *pf, *xf;
	/* maximal amount of space that can be used at the beginning and the end */
	double fixfront[2], fixend[2]; /* fixed points - used to show direction */
	double mvfront[2], mvend[2]; /* movable points */
	double limfront[2], limend[2]; /* limit of movement for movabel points */
	double sympt;
	int outfront, outend; /* the beginning/end is going outwards */
	int symfront, symend; /* a ready symmetric fragment is present at front/end */
	int drnd[2 /*X,Y*/]; /* size of the round part */
	int i, j, a1, a2, ndots;
	double avg2, max2; /* squared distances */
	struct dot_dist *dots, *usedots;

	ff = X_FRAG(firstge);
	f = X_FRAG(ge);
	gel = f->nextsub;
	lf = X_FRAG(gel);
	if(f->prevsub != 0)
		pf = X_FRAG(f->prevsub);
	else
		pf = 0;

	for(i=0; i<2; i++)
		drnd[i] = gel->bkwd->ipoints[i][2] - ge->ipoints[i][2];

	if(f->prevsub==0 && f->ixcont == GEXFI_NONE) {
		/* nothing to join with : may use the whole length */
		for(i = 0; i < 2; i++)
			limfront[i] = ge->bkwd->ipoints[i][2];
	} else {
		/* limit to a half */
		for(i = 0; i < 2; i++)
			limfront[i] = 0.5 * (ge->ipoints[i][2] + ge->bkwd->ipoints[i][2]);
	}
	if( (ge->ix3 == ge->bkwd->ix3) /* vert */
	^ (isign(ge->bkwd->ix3 - ge->frwd->ix3)==isign(ge->bkwd->iy3 - ge->frwd->iy3))
	^ (fti == GEXFI_CONCAVE) /* counter-clockwise */ ) {
		/* the beginning is not a flat 90-degree end */
		outfront = 1;
		for(i = 0; i < 2; i++)
			fixfront[i] = ge->frwd->ipoints[i][2];
	} else {
		outfront = 0;
		for(i = 0; i < 2; i++)
			fixfront[i] = ge->ipoints[i][2];
	}

	if(lf->nextsub==0 && lf->ixstart == GEXFI_NONE) {
		/* nothing to join with : may use the whole length */
		for(i = 0; i < 2; i++)
			limend[i] = gel->ipoints[i][2];
	} else {
		/* limit to a half */
		for(i = 0; i < 2; i++)
			limend[i] = 0.5 * (gel->ipoints[i][2] + gel->bkwd->ipoints[i][2]);
	}
	gei = gel->bkwd->bkwd;
	if( (gel->ix3 == gel->bkwd->ix3) /* vert */
	^ (isign(gel->ix3 - gei->ix3)==isign(gel->iy3 - gei->iy3))
	^ (fti == GEXFI_CONVEX) /* clockwise */ ) {
		/* the end is not a flat 90-degree end */
		outend = 1;
		for(i = 0; i < 2; i++)
			fixend[i] = gel->bkwd->bkwd->ipoints[i][2];
	} else {
		outend = 0;
		for(i = 0; i < 2; i++)
			fixend[i] = gel->bkwd->ipoints[i][2];
	}

	for(i = 0; i < 2; i++) {
		fixfront[i] *= fscale;
		limfront[i] *= fscale;
		fixend[i] *= fscale;
		limend[i] *= fscale;
	}

	fprintf(stderr, "    %d out(%d[%d %d %d],%d[%d %d %d]) drnd(%d, %d)\n", 
		fti,
		outfront, 
			(ge->ix3 == ge->bkwd->ix3),
			(isign(ge->bkwd->ix3 - ge->frwd->ix3)==isign(ge->bkwd->iy3 - ge->frwd->iy3)),
			(fti == GEXFI_CONCAVE),
		outend,
			(gel->ix3 == gel->bkwd->ix3),
			(isign(gel->ix3 - gei->ix3)==isign(gel->iy3 - gei->iy3)),
			(fti == GEXFI_CONVEX),
		drnd[0], drnd[1]);

	/* prepare to calculate the distances */
	ndots = f->sublen - 1;
	dots = malloc(sizeof(*dots) * ndots);
	if(dots == 0) {
		fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
		exit(255);
	}
	for(i = 0, gei = ge; i < ndots; i++, gei = gei->frwd) {
		for(a1 = 0; a1 < 2; a1++)
			dots[i].p[a1] = fscale * gei->ipoints[a1][2];
	}

	/* see if we can mirror a ready symmetric curve */

	/* check symmetry with the fragment before this */
	symfront = (pf != 0 && (pf->flags & GEXFF_SYMNEXT) && (pf->flags & GEXFF_DONE)
		&& ( outend && f->sublen <= pf->sublen
			|| ( pf->sublen == f->sublen 
				&& (lf->sublen == 0
					|| ( abs(limfront[0]-limend[0]) >= abs(pf->vect[0][0]-pf->vect[3][0])
						&& abs(limfront[1]-limend[1]) >= abs(pf->vect[0][1]-pf->vect[3][1]) ))
			)
		)
	);
	/* check symmetry with the fragment after this */
	symend = ( (f->flags & GEXFF_SYMNEXT) && (lf->flags & GEXFF_DONE)
		&& ( outfront && f->sublen <= lf->sublen
			|| ( lf->sublen == f->sublen 
				&& (pf == 0 
					|| ( abs(limfront[0]-limend[0]) >= abs(lf->vect[0][0]-lf->vect[3][0])
						&& abs(limfront[1]-limend[1]) >= abs(lf->vect[0][1]-lf->vect[3][1]) )) 
			)
		)
	);
	if(symfront || symend) {
		/* mirror the symmetric neighbour subfrag */
		if(symfront) {
			a1 = (ge->ix3 != ge->bkwd->ix3); /* the symmetry axis */
			a2 = !a1; /* the other axis (goes along the extremum gentry) */

			/* the symmetry point on a2 */
			sympt = fscale * 0.5 * (ge->ipoints[a2][2] + ge->bkwd->ipoints[a2][2]);
			xf = pf; /* the symmetric fragment */
		} else {
			a1 = (gel->ix3 != gel->bkwd->ix3); /* the symmetry axis */
			a2 = !a1; /* the other axis (goes along the extremum gentry) */

			/* the symmetry point on a2 */
			sympt = fscale * 0.5 * (gel->ipoints[a2][2] + gel->bkwd->ipoints[a2][2]);
			xf = lf; /* the symmetric fragment */
		}
		fprintf(stderr, "     sym with %p f=%d(%p) e=%d(%p) a1=%c a2=%c sympt=%g\n",
			xf, symfront, pf, symend, lf,
			a1 ? 'Y': 'X', a2 ? 'Y': 'X', sympt
		);
		for(i=0; i<4; i++) {
			f->vect[3-i][a1] = xf->vect[i][a1];
			f->vect[3-i][a2] = sympt - (xf->vect[i][a2]-sympt);
		}
		if(symfront) {
			if(outend || lf->sublen==0)
				limcurve(f->vect, limend, 3);
		} else {
			if(outfront || pf == 0)
				limcurve(f->vect, limfront, 0);
		}
		avg2 = fdotcurvdist2(f->vect, dots, ndots, &max2);
		fprintf(stderr, "     avg=%g max=%g fscale=%g\n", sqrt(avg2), sqrt(max2), fscale);
		if(max2 <= fscale*fscale) {
			f->flags |= (GEXFF_DONE | GEXFF_DRAWCURVE);
			f->vectlen = f->sublen;
			free(dots);
			return;
		}
	}

	if( !outfront && !outend && f->symge != 0) {
		/* a special case: try a circle segment as an approximation */
		double lenfront, lenend, len, maxlen;

		/* coefficient for a Bezier approximation of a circle */
#define CIRCLE_FRAC	0.55

		a1 = (ge->ix3 == ge->bkwd->ix3); /* get the axis along the front */
		a2 = !a1; /* axis along the end */

		lenfront = fixfront[a1] - limfront[a1];
		lenend = fixend[a2] - limend[a2];
		if(fabs(lenfront) < fabs(lenend))
			len = fabs(lenfront);
		else
			len = fabs(lenend);

		/* make it go close to the round shape */
		switch(f->sublen) {
		case 2:
			maxlen = fscale;
			break;
		case 4:
		case 6:
			maxlen = fscale * 2.;
			break;
		default:
			maxlen = fscale * abs(ge->frwd->frwd->ipoints[a1][2] 
				- ge->ipoints[a1][2]);
			break;
		}
		if(len > maxlen)
			len = maxlen;

		mvfront[a1] = fixfront[a1] - fsign(lenfront) * len;
		mvfront[a2] = limfront[a2];
		mvend[a2] = fixend[a2] - fsign(lenend) * len;
		mvend[a1] = limend[a1];

		for(i=0; i<2; i++) {
			f->vect[0][i] = mvfront[i];
			f->vect[3][i] = mvend[i];
		}
		f->vect[1][a1] = mvfront[a1] + CIRCLE_FRAC*(mvend[a1]-mvfront[a1]);
		f->vect[1][a2] = mvfront[a2];
		f->vect[2][a1] = mvend[a1];
		f->vect[2][a2] = mvend[a2] + CIRCLE_FRAC*(mvfront[a2]-mvend[a2]);

		avg2 = fdotcurvdist2(f->vect, dots, ndots, &max2);
		fprintf(stderr, "     avg=%g max=%g fscale=%g\n", sqrt(avg2), sqrt(max2), fscale);
		if(max2 <= fscale*fscale) {
			f->flags |= (GEXFF_DONE | GEXFF_DRAWCURVE);
			f->vectlen = f->sublen;
			free(dots);
			return;
		}
#undef CIRCLE_FRAC
	}
	for(i=0; i<2; i++) {
		f->vect[0][i] = limfront[i];
		f->vect[1][i] = fixfront[i];
		f->vect[2][i] = fixend[i];
		f->vect[3][i] = limend[i];
	}
	usedots = dots;
	if(outfront) {
		usedots++; ndots--;
	}
	if(outend)
		ndots--;
	if( fcrossrayscv(f->vect, NULL, NULL) == 0) {
		fprintf(stderr, "**** Internal error: rays must cross but don't at %p-%p\n",
			ge, gel);
		fprintf(stderr, "  (%g, %g) (%g, %g) (%g, %g) (%g, %g)\n", 
			limfront[0], limfront[1],
			fixfront[0], fixfront[1],
			fixend[0], fixend[1],
			limend[0], limend[1]
		);
		dumppaths(g, NULL, NULL);
		exit(1);
	} else {
		if(ndots != 0)
			fapproxcurve(f->vect, usedots, ndots);
		f->flags |= (GEXFF_DONE | GEXFF_DRAWCURVE);
		f->vectlen = f->sublen;
	}
	free(dots);
}

/*
 * Subtract a list of gentries (covered by a fragment of higher
 * priority) from the set of fragments of a given
 * type.
 *
 * An example is subtraction of the long exact line fragments
 * from the curve fragments which get overridden.
 */

static void
frag_subtract(
	GLYPH *g,
	GENTRY **age, /* array of gentries for this contour */
	int clen, /* length of the contour */
	GENTRY *ge, /* first gentry to be subtracted */
	int slen, /* number of gentries in the list to be subtracted */
	int d /* type of fragments from which to subtract, as in GEXFI_... */
)
{
	GENTRY *pge;
	GEX_FRAG *f, *pf;
	int len, i, j;

	f = X_FRAG(ge);
	len = slen; 

	/* check if we overlap the end of some fragment */
	if(f->lenback[d]) {
		/* chop off the end of conflicting fragment */
		len = f->lenback[d];
		pge = age[(f->aidx + clen - len)%clen];
		pf = X_FRAG(pge);
		if(pf->len[d] == clen+1 && pf->flags & GEXFF_CIRC) {
			/* the conflicting fragment is self-connected */

			pf->len[d] = 0;
			/* calculate the new value for lenback */
			len = clen+1 - slen;
			for(pge = ge; len > 0; pge = pge->bkwd, len--)
				X_FRAG(pge)->lenback[d] = len;
			/* now pge points to the last entry of the line,
			 * which is also the new first entry of the curve
			 */
			X_FRAG(pge)->len[d] = clen+2 - slen;
			/* clean lenback of gentries covered by the line */
			for(pge = ge->frwd, j = slen-1; j > 0; pge = pge->frwd, j--)
				X_FRAG(pge)->lenback[d] = 0;
			fprintf(stderr, "    cut %s circular frag to %p-%p\n", 
				gxf_name[d], pge, ge);
			gex_dump_contour(ge, clen);
		} else {
			/* when we chop off a piece of fragment, we leave the remaining
			 * piece(s) overlapping with the beginning and possibly the end 
			 * of the line fragment under consideration
			 */
			fprintf(stderr, "    cut %s frag at %p from len=%d to len=%d (end %p)\n", 
				gxf_name[d], pge, pf->len[d], len+1, ge);
			j = pf->len[d] - len - 1; /* how many gentries are chopped off */
			pf->len[d] = len + 1;
			i = slen - 1;
			for(pge = ge->frwd; j > 0 && i > 0; j--, i--, pge = pge->frwd)
				X_FRAG(pge)->lenback[d] = 0;
			gex_dump_contour(ge, clen);

			if(j != 0) {
				/* the conflicting fragment is split in two by this line
				 * fragment, fix up its tail
				 */

				fprintf(stderr, "    end of %s frag len=%d %p-", 
					gxf_name[d], j+1, pge->bkwd);
				X_FRAG(pge->bkwd)->len[d] = j+1; /* the overlapping */
				for(i = 1; j > 0; j--, i++, pge = pge->frwd)
					X_FRAG(pge)->lenback[d] = i;
				fprintf(stderr, "%p\n", pge->bkwd);
				gex_dump_contour(ge, clen);
			}
		}
	}
	/* check if we overlap the beginning of some fragments */
	i = slen-1; /* getntries remaining to consider */
	j = 0; /* gentries remaining in the overlapping fragment */
	for(pge = ge; i > 0; i--, pge = pge->frwd) {
		if(j > 0) {
			X_FRAG(pge)->lenback[d] = 0;
			j--;
		} 
		/* the beginning of one fragment may be the end of another
		 * fragment, in this case if j-- above results in 0, that will 
		 * cause it to check the same gentry for the beginning
		 */
		if(j == 0) {
			pf = X_FRAG(pge);
			j = pf->len[d]; 
			if(j != 0) {
				fprintf(stderr, "    removed %s frag at %p len=%d\n", 
					gxf_name[d], pge, j);
				gex_dump_contour(ge, clen);
				pf->len[d] = 0;
				j--;
			}
		}
	}
	/* pge points at the last gentry of the line fragment */
	if(j > 1) { /* we have the tail of a fragment left */
		fprintf(stderr, "    end of %s frag len=%d %p-", 
			gxf_name[d], j, pge);
		X_FRAG(pge)->len[d] = j; /* the overlapping */
		for(i = 0; j > 0; j--, i++, pge = pge->frwd)
			X_FRAG(pge)->lenback[d] = i;
		fprintf(stderr, "%p\n", pge->bkwd);
		gex_dump_contour(ge, clen);
	} else if(j == 1) {
		X_FRAG(pge)->lenback[d] = 0;
	}
}

/*
 * Produce an outline from a bitmap.
 * scale - factor to scale the sizes
 * bmap - array of dots by lines, xsz * ysz
 * xoff, yoff - offset of the bitmap's lower left corner
 *              from the logical position (0,0)
 */

void
bmp_outline(
	GLYPH *g,
	int scale,
	char *bmap,
	int xsz,
	int ysz,
	int xoff,
	int yoff
)
{
	char *hlm, *vlm; /* arrays of the limits of outlines */
	char *amp; /* map of ambiguous points */
	int x, y;
	char *ip, *op;
	double fscale;

	if(xsz==0 || ysz==0)
		return;

#ifdef USE_AUTOTRACE
	if(use_autotrace) {
		autotraced_bmp_outline(g, scale, bmap, xsz, ysz, xoff, yoff);
		return;
	}
#endif /*USE_AUTOTRACE*/

	fscale = (double)scale;
	g->flags &= ~GF_FLOAT; /* build it as int first */

	if(!warnedhints) {
		warnedhints = 1;
		if(hints && subhints) {
			WARNING_2 fprintf(stderr, 
				"Use of hint substitution on bitmap fonts is not recommended\n");
		}
	}

#if 0
	printbmap(bmap, xsz, ysz, xoff, yoff);
#endif

	/* now find the outlines */
	hlm=calloc( xsz, ysz+1 ); /* horizontal limits */
	vlm=calloc( xsz+1, ysz ); /* vertical limits */
	amp=calloc( xsz, ysz ); /* ambiguous points */

	if(hlm==0 || vlm==0 || amp==0)  {
		fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
		exit(255);
	}

	/*
	 * hlm and vlm represent a grid of horisontal and
	 * vertical lines. Each pixel is surrounded by the grid
	 * from all the sides. The values of [hv]lm mark the
	 * parts of grid where the pixel value switches from white
	 * to black and back.
	 */

	/* find the horizontal limits */
	ip=bmap; op=hlm;
	/* 1st row */
	for(x=0; x<xsz; x++) {
		if(ip[x])
			op[x]=L_ON;
	}
	ip+=xsz; op+=xsz;
	/* internal rows */
	for(y=1; y<ysz; y++) {
		for(x=0; x<xsz; x++) {
			if(ip[x]) {
				if(!ip[x-xsz])
					op[x]=L_ON;
			} else {
				if(ip[x-xsz])
					op[x]=L_OFF;
			}
		}
		ip+=xsz; op+=xsz;
	}

	/* last row */
	ip-=xsz;
	for(x=0; x<xsz; x++) {
		if(ip[x])
			op[x]=L_OFF;
	}

	/* find the vertical limits */
	ip=bmap; op=vlm;
	for(y=0; y<ysz; y++) {
		if(ip[0])
			op[0]=L_ON;
		for(x=1; x<xsz; x++) {
			if(ip[x]) {
				if(!ip[x-1])
					op[x]=L_ON;
			} else {
				if(ip[x-1])
					op[x]=L_OFF;
			}
		}
		if(ip[xsz-1])
			op[xsz]=L_OFF;
		ip+=xsz; op+=xsz+1; 
	}

	/*
	 * Ambiguous points are the nodes of the grids
	 * that are between two white and two black pixels
	 * located in a checkerboard style. Actually
	 * there are only two patterns that may be
	 * around an ambiguous point:
	 *
	 *    X|.    .|X
	 *    -*-    -*-
	 *    .|X    X|.
	 *
	 * where "|" and "-" represent the grid (respectively members
	 * of vlm and hlm), "*" represents an ambiguous point
	 * and "X" and "." represent black and white pixels.
	 *
	 * If these sample pattern occur in the lower left corner
	 * of the bitmap then this ambiguous point will be
	 * located at amp[1][1] or in other words amp[1*xsz+1].
	 *
	 * These points are named "ambiguous" because it's
	 * not easy to guess what did the font creator mean
	 * at these points. So we are going to treat them 
	 * specially, doing no aggressive smoothing.
	 */

	/* find the ambiguous points */
	for(y=ysz-1; y>0; y--)
		for(x=xsz-1; x>0; x--) {
			if(bmap[y*xsz+x]) {
				if( !bmap[y*xsz+x-1] && !bmap[y*xsz-xsz+x] && bmap[y*xsz-xsz+x-1] )
					amp[y*xsz+x]=1;
			} else {
				if( bmap[y*xsz+x-1] && bmap[y*xsz-xsz+x] && !bmap[y*xsz-xsz+x-1] )
					amp[y*xsz+x]=1;
			}
		}

#if 0
	printlimits(hlm, vlm, amp, xsz, ysz);
#endif

	/* generate the vectored (stepping) outline */

	while(1) {
		int found = 0;
		int outer; /* flag: this is an outer contour */
		int hor, newhor; /* flag: the current contour direction is horizontal */
		int dir; /* previous direction of the coordinate, 1 - L_ON, 0 - L_OFF */
		int startx, starty; /* start of a contour */
		int firstx, firsty; /* start of the current line */
		int newx, newy; /* new coordinates to try */
		char *lm, val;
		int maxx, maxy, xor;

		for(y=ysz; !found &&  y>0; y--) 
			for(x=0; x<xsz; x++) 
				if(hlm[y*xsz+x] > L_NONE) 
					goto foundcontour;
		break; /* have no contours left */

	foundcontour:
		ig_rmoveto(g, x+xoff, y+yoff); /* intermediate as int */

		startx = firstx = x;
		starty = firsty = y;

		if(hlm[y*xsz+x] == L_OFF) {
			outer = 1; dir = 0;
			hlm[y*xsz+x] = -hlm[y*xsz+x]; /* mark as seen */
			hor = 1; x++;
		} else {
			outer = 0; dir = 0;
			hor = 0; y--;
			vlm[y*(xsz+1)+x] = -vlm[y*(xsz+1)+x]; /* mark as seen */
		}

		while(x!=startx || y!=starty) {
#if 0
			printf("trace (%d, %d) outer=%d hor=%d dir=%d\n", x, y, outer, hor, dir);
#endif

			/* initialization common for try1 and try2 */
			if(hor) {
				lm = vlm; maxx = xsz+1; maxy = ysz; newhor = 0;
			} else {
				lm = hlm; maxx = xsz; maxy = ysz+1; newhor = 1;
			}
			xor = (outer ^ hor ^ dir);

		try1:
			/* first we try to change axis, to keep the
			 * contour as long as possible
			 */

			newx = x; newy = y;
			if(!hor && (!outer ^ dir))
				newx--;
			if(hor && (!outer ^ dir))
				newy--;

			if(newx < 0 || newx >= maxx || newy < 0 || newy >= maxy)
				goto try2;

			if(!xor)
				val = L_ON;
			else
				val = L_OFF;
#if 0
			printf("try 1, want %d have %d at %c(%d, %d)\n", val, lm[newy*maxx + newx],
				(newhor ? 'h':'v'), newx, newy);
#endif
			if( lm[newy*maxx + newx] == val )
				goto gotit;

		try2:
			/* try to change the axis anyway */

			newx = x; newy = y;
			if(!hor && (outer ^ dir))
				newx--;
			if(hor && (outer ^ dir))
				newy--;

			if(newx < 0 || newx >= maxx || newy < 0 || newy >= maxy)
				goto try3;

			if(xor)
				val = L_ON;
			else
				val = L_OFF;
#if 0
			printf("try 2, want %d have %d at %c(%d, %d)\n", val, lm[newy*maxx + newx],
				(newhor ? 'h':'v'), newx, newy);
#endif
			if( lm[newy*maxx + newx] == val )
				goto gotit;

		try3:
			/* try to continue in the old direction */

			if(hor) {
				lm = hlm; maxx = xsz; maxy = ysz+1;
			} else {
				lm = vlm; maxx = xsz+1; maxy = ysz;
			}
			newhor = hor;
			newx = x; newy = y;
			if(hor && dir)
				newx--;
			if(!hor && !dir)
				newy--;

			if(newx < 0 || newx >= maxx || newy < 0 || newy >= maxy)
				goto badtry;

			if(dir)
				val = L_ON;
			else
				val = L_OFF;
#if 0
			printf("try 3, want %d have %d at %c(%d, %d)\n", val, lm[newy*maxx + newx],
				(newhor ? 'h':'v'), newx, newy);
#endif
			if( lm[newy*maxx + newx] == val )
				goto gotit;

		badtry:
			fprintf(stderr, "**** Internal error in the contour detection code at (%d, %d)\n", x, y);
			fprintf(stderr, "glyph='%s' outer=%d hor=%d dir=%d\n", g->name, outer, hor, dir);
			fflush(stdout);
			exit(1);

		gotit:
			if(hor != newhor) { /* changed direction, end the previous line */
				ig_rlineto(g, x+xoff, y+yoff); /* intermediate as int */
				firstx = x; firsty = y;
			}
			lm[newy*maxx + newx] = -lm[newy*maxx + newx];
			hor = newhor;
			dir = (val == L_ON);
			if(newhor)
				x -= (dir<<1)-1;
			else
				y += (dir<<1)-1;
		}
#if 0
		printf("trace (%d, %d) outer=%d hor=%d dir=%d\n", x, y, outer, hor, dir);
#endif
		ig_rlineto(g, x+xoff, y+yoff); /* intermediate as int */
		g_closepath(g);
	}


	/* try to vectorize the curves and sloped lines in the bitmap */
	if(vectorize) { 
		GENTRY *ge, *pge, *cge, *loopge;
		int i;
		int skip;

		dumppaths(g, NULL, NULL);

		/* allocate the extensions */
		for(cge=g->entries; cge!=0; cge=cge->next) {
			cge->ext = calloc(1, sizeof(GEX_FRAG) );
			if(cge->ext == 0)  {
				fprintf (stderr, "****malloc failed %s line %d\n", __FILE__, __LINE__);
				exit(255);
			}
		}

		for(cge=g->entries; cge!=0; cge=cge->next) {
			if(cge->type != GE_MOVE)
				continue;

			/* we've found the beginning of a contour */
			{
				int d, vert, count, stepmore, delaystop;
				int vdir, hdir, fullvdir, fullhdir, len;
				int dx, dy, lastdx, lastdy; 
				int k1, k2, reversal, smooth, good;
				int line[2 /*H,V*/], maxlen[2 /*H,V*/], minlen[2 /*H,V*/];
				GENTRY **age; /* array of gentries in a contour */
				int clen; /* contour length, size of ths array */
				int i, j;
				GEX_FRAG *f;

				/* we know that all the contours start at the top-left corner,
				 * so at most it might be before/after the last element of
				 * the last/first fragment
				 */

				ge = cge->next;
				pge = ge->bkwd;
				if(ge->ix3 == pge->ix3) { /* a vertical line */
					/* we want to start always from a horizontal line because
					 * then we always start from top and that is quaranteed to be a 
					 * fragment boundary, so move the start point of the contour
					 */
					pge->prev->next = pge->next;
					pge->next->prev = pge->prev;
					cge->next = pge;
					pge->prev = cge;
					pge->next = ge;
					ge->prev = pge;
					ge = pge; pge = ge->bkwd;
					cge->ix3 = pge->ix3; cge->iy3 = pge->iy3;
				}

				/* fill the array of gentries */
				clen = 1;
				for(ge = cge->next->frwd; ge != cge->next; ge = ge->frwd)
					clen++;
				age = (GENTRY **)malloc(sizeof(*age) * clen);
				ge = cge->next;
				count = 0;
				do {
					age[count] = ge;
					X_FRAG(ge)->aidx = count++;

					/* and by the way find the extremums */
					for(i=0; i<2; i++) {
						if( isign(ge->frwd->ipoints[i][2] - ge->ipoints[i][2])
						* isign(ge->bkwd->bkwd->ipoints[i][2] - ge->bkwd->ipoints[i][2]) == 1) {
							X_FRAG(ge)->flags |= GEXFF_EXTR;
							fprintf(stderr, "  %s extremum at %p\n", (i?"vert":"hor"), ge);
						}
						if(abs(ge->ipoints[i][2] - ge->bkwd->ipoints[i][2]) > 1)
							X_FRAG(ge)->flags |= GEXFF_LONG;
					}

					ge = ge->frwd;
				} while(ge != cge->next);

				/* Find the serif fragments, looking as either of:
				 *           -+              |
				 *            |              |        
				 *          +-+            +-+        
				 *          |              |          
				 *          +--...         +--...
				 * with the thickness of serifs being 1 pixel. We make no
				 * difference between serifs on vertical and horizontal stems.
				 */

				ge = cge->next;
				do {
					GENTRY *nge;
					int pdx, pdy, ndx, ndy;

					/* two gentries of length 1 mean a potential serif */
					pge = ge->bkwd;
					nge = ge->frwd;

					dx = nge->ix3 - pge->ix3;
					dy = nge->iy3 - pge->iy3;

					if(abs(dx) != 1 || abs(dy) != 1) /* 2 small ones */
						continue;

					if( 
						(!(X_FRAG(ge)->flags & GEXFF_EXTR) 
							|| !(X_FRAG(ge->bkwd)->flags & GEXFF_EXTR))
						&& (!(X_FRAG(ge->frwd)->flags & GEXFF_EXTR) 
							|| !(X_FRAG(ge->frwd->frwd)->flags & GEXFF_EXTR))
					)
						continue; /* either side must be a couple of extremums */

					pdx = pge->ix3 - pge->bkwd->ix3;
					pdy = pge->iy3 - pge->bkwd->iy3;
					ndx = nge->frwd->ix3 - nge->ix3;
					ndy = nge->frwd->iy3 - nge->iy3;

					if(pdx*dx + pdy*dy > 0 && ndx*dx + ndy*dy > 0) 
						continue; /* definitely not a serif but a round corner */

					if(abs(pdx) + abs(pdy) == 1 || abs(ndx) + abs(ndy) == 1)
						continue;

					/* we've found a serif including this and next gentry */
					X_FRAG(ge)->len[GEXFI_SERIF] = 2;

				} while( (ge = ge->frwd) != cge->next );


				/* Find the convex and concave fragments, defined as:
				 * convex (clockwise): dy/dx <= dy0/dx0, 
				 *  or a reversal: dy/dx == - dy0/dx0 && abs(dxthis) == 1 && dy/dx > 0
				 * concave (counter-clockwise): dy/dx >= dy0/dx0, 
				 *  or a reversal: dy/dx == - dy0/dx0 && abs(dxthis) == 1 && dy/dx < 0
				 *
				 * Where dx and dy are measured between the end of this gentry
				 * and the start of the previous one (dx0 and dy0 are the same
				 * thing calculated for the previous gentry and its previous one),
				 * dxthis is between the end and begginning of this gentry.
				 *
				 * A reversal is a situation when the curve changes its direction
				 * along the x axis, so it passes through a momentary vertical
				 * direction.
				 */
				for(d = GEXFI_CONVEX; d<= GEXFI_CONCAVE; d++) {
					ge = cge->next;
					pge = ge->bkwd; /* the beginning of the fragment */
					count = 1;
					lastdx = pge->ix3 - pge->bkwd->bkwd->ix3;
					lastdy = pge->iy3 - pge->bkwd->bkwd->iy3;

#define CHKCURVCONN(ge, msg)	do { \
		dx = (ge)->ix3 - (ge)->bkwd->bkwd->ix3; \
		dy = (ge)->iy3 - (ge)->bkwd->bkwd->iy3; \
		if(0 && msg) { \
			fprintf(stderr, "  %p: dx=%d dy=%d dx0=%d dy0=%d ", \
				(ge), dx, dy, lastdx, lastdy); \
		} \
		k1 = X_FRAG(ge)->flags; \
		k2 = X_FRAG((ge)->bkwd)->flags; \
		if(0 && msg) { \
			fprintf(stderr, "fl=%c%c%c%c ", \
				(k1 & GEXFF_EXTR) ? 'X' : '-', \
				(k1 & GEXFF_LONG) ? 'L' : '-', \
				(k2 & GEXFF_EXTR) ? 'X' : '-', \
				(k2 & GEXFF_LONG) ? 'L' : '-' \
			); \
		} \
		if( (k1 & GEXFF_EXTR) && (k2 & GEXFF_LONG) \
		|| (k2 & GEXFF_EXTR) && (k1 & GEXFF_LONG) ) { \
			smooth = 0; \
			good = reversal = -1; /* for debugging */ \
		} else { \
			k1 = dy * lastdx; \
			k2 = lastdy * dx; \
			smooth = (abs(dx)==1 || abs(dy)==1); \
			good = (k1 - k2)*gxf_cvk[d] >= 0; \
			if(smooth && !good) { \
				reversal = (k1 == -k2 && abs((ge)->ix3 - (ge)->bkwd->ix3)==1  \
					&& dy*dx*gxf_cvk[d] < 0); \
			} else \
				reversal = 0; \
		} \
		if(0 && msg) { \
			fprintf(stderr, "k1=%d k2=%d pge=%p count=%d %s good=%d rev=%d\n", \
				k1, k2, pge, count, gxf_name[d], good, reversal); \
		} \
	} while(0)

					do {
						CHKCURVCONN(ge, 1);

						if(smooth && (good || reversal) )
							count++;
						else {
							/* can't continue */
#if 0
							if(count >= 4) { /* worth remembering */
								fprintf(stderr, " %s frag %p-%p count=%d\n", gxf_name[d], pge, ge->bkwd, count);
							}
#endif
							X_FRAG(pge)->len[d] = count;
							if(smooth) {
								pge = ge->bkwd;
								count = 2;
							} else {
								pge = ge;
								count = 1;
							}
						}
						lastdx = dx; lastdy = dy;
						ge = ge->frwd;
					} while(ge != cge->next);

					/* see if we can connect the last fragment to the first */
					CHKCURVCONN(ge, 1);

					if(smooth && (good || reversal) ) {
						/* -1 to avoid ge->bkwd being counted twice */
						if( X_FRAG(ge->bkwd)->len[d] >= 2 )
							count += X_FRAG(ge->bkwd)->len[d] - 1;
						else if(count == clen+1) {
							/* we are joining a circular (closed) curve, check whether it
							 * can be joined at any point or whether it has a discontinuity
							 * at the point where we join it now
							 */
							lastdx = dx; lastdy = dy;
							CHKCURVCONN(ge->frwd, 0);

							if(smooth && (good || reversal) ) {
								/* yes, the curve is truly a circular one and can be 
								 * joined at any point
								 */

#if 0
								fprintf(stderr, " found a circular joint point at %p\n", pge);
#endif
								/* make sure that in a circular fragment we start from an extremum */
								while( ! (X_FRAG(pge)->flags & GEXFF_EXTR) )
									pge = pge->frwd;
								X_FRAG(pge)->flags |= GEXFF_CIRC;
							}
						}
#if 0
						fprintf(stderr, " %s joined %p to %p count=%d bk_count=%d\n", gxf_name[d], pge, ge->bkwd, 
							count, X_FRAG(ge->bkwd)->len[d] );
#endif
						X_FRAG(ge->bkwd)->len[d] = 0;
					} 
					X_FRAG(pge)->len[d] = count;
#if 0
					if(count >= 4) { /* worth remembering */
						fprintf(stderr, " %s last frag %p-%p count=%d\n", gxf_name[d], pge, ge->bkwd, count);
					}
#endif
#undef CHKCURVCONN

					/* do postprocessing */
					ge = cge->next;
					do {
						f = X_FRAG(ge);
						len = f->len[d];
#if 0
						fprintf(stderr, "   %p %s len=%d clen=%d\n", ge, gxf_name[d], len, clen);
#endif
						if(len < 3) /* get rid of the fragments that are too short */
							f->len[d] = 0;
						else if(len == 3) {
							/*                                                    _
							 * drop the |_| - shaped fragments, leave alone the _|  - shaped
							 * (and even those only if not too short in pixels),
							 * those left alone are further filtered later
							 */
							k1 = (ge->ix3 == ge->bkwd->ix3); /* axis of the start */
							if(isign(ge->ipoints[k1][2] - ge->bkwd->ipoints[k1][2])
								!= isign(ge->frwd->ipoints[k1][2] - ge->frwd->frwd->ipoints[k1][2])
							&& abs(ge->frwd->frwd->ipoints[k1][2] - ge->bkwd->ipoints[k1][2]) > 2) {
#if 0
								fprintf(stderr, " %s frag %p count=%d good shape\n", 
									gxf_name[d], ge, count);
#endif
							} else
								f->len[d] = 0;
						} else if(len == clen+1)
							break;  /* a closed fragment, nothing else interesting */
						else { /* only for open fragments */
							GENTRY *gem, *gex, *gei, *ges;

							ges = ge; /* the start entry */
							gem = age[(f->aidx + f->len[d])%clen]; /* entry past the end of the fragment */

							gei = ge->frwd;
							if( (ge->ix3 == ge->bkwd->ix3) /* vert */
							^ (isign(ge->bkwd->ix3 - gei->ix3)==isign(ge->bkwd->iy3 - gei->iy3))
							^ !(d == GEXFI_CONVEX) /* counter-clockwise */ ) {

#if 0
								fprintf(stderr, " %p: %s potential spurious start\n", ge, gxf_name[d]);
#endif
								/* the beginning may be a spurious entry */

								gex = 0; /* the extremum closest to the beginning - to be found */
								for(gei = ge->frwd; gei != gem; gei = gei->frwd) {
									if(X_FRAG(gei)->flags & GEXFF_EXTR) {
										gex = gei;
										break;
									}
								}
								if(gex == 0)
									gex = gem->bkwd; 

								/* A special case: ignore the spurious ends on small curves that
								 * either enclose an 1-pixel-wide extremum or are 1-pixel deep.
								 * Any 5-or-less-pixel-long curve with extremum 2 steps away
								 * qualifies for that.
								 */

								if(len <= 5 && gex == ge->frwd->frwd) {
									good = 0;
#if 0
									fprintf(stderr, " E");
#endif
								} else {
									good = 1; /* assume that ge is not spurious */

									/* gei goes backwards, gex goes forwards from the extremum */
									gei = gex;
									/* i is the symmetry axis, j is the other axis (X=0 Y=1) */
									i = (gex->ix3 != gex->bkwd->ix3);
									j = !i;
									for( ; gei!=ge && gex!=gem; gei=gei->bkwd, gex=gex->frwd) {
										if( gei->bkwd->ipoints[i][2] != gex->ipoints[i][2]
										|| gei->bkwd->ipoints[j][2] - gei->ipoints[j][2]
											!= gex->bkwd->ipoints[j][2] - gex->ipoints[j][2] 
										) {
											good = 0; /* no symmetry - must be spurious */
#if 0
											fprintf(stderr, " M(%p,%p)(%d %d,%d)(%d %d,%d)",
												gei, gex,
												i, gei->bkwd->ipoints[i][2], gex->ipoints[i][2],
												j, gei->bkwd->ipoints[j][2] - gei->ipoints[j][2],
												gex->bkwd->ipoints[j][2] - gex->ipoints[j][2] );
#endif
											break;
										}
									}
									if(gex == gem) { /* oops, the other side is too short */
										good = 0;
#if 0
										fprintf(stderr, " X");
#endif
									}
									if(good && gei == ge) {
										if( isign(gei->bkwd->ipoints[j][2] - gei->ipoints[j][2])
										!= isign(gex->bkwd->ipoints[j][2] - gex->ipoints[j][2]) ) {
											good = 0; /* oops, goes into another direction */
#if 0
											fprintf(stderr, " D");
#endif
										}
									}
								}
								if(!good) { /* it is spurious, drop it */
#if 0
									fprintf(stderr, " %p: %s spurious start\n", ge, gxf_name[d]);
#endif
									f->len[d] = 0;
									ges = ge->frwd;
									len--;
									X_FRAG(ges)->len[d] = len;
								}
							}

							gei = gem->bkwd->bkwd->bkwd;
							if( (gem->ix3 != gem->bkwd->ix3) /* gem->bkwd is vert */
							^ (isign(gem->bkwd->ix3 - gei->ix3)==isign(gem->bkwd->iy3 - gei->iy3))
							^ (d == GEXFI_CONVEX) /* clockwise */ ) {
								
#if 0
								fprintf(stderr, " %p: %s potential spurious end\n", gem->bkwd, gxf_name[d]);
#endif
								/* the end may be a spurious entry */

								gex = 0; /* the extremum closest to the end - to be found */
								for(gei = gem->bkwd->bkwd; gei != ges->bkwd; gei = gei->bkwd) {
									if(X_FRAG(gei)->flags & GEXFF_EXTR) {
										gex = gei;
										break;
									}
								}
								if(gex == 0)
									gex = ges; 

								good = 1; /* assume that gem->bkwd is not spurious */
								/* gei goes backwards, gex goes forwards from the extremum */
								gei = gex;
								/* i is the symmetry axis, j is the other axis (X=0 Y=1) */
								i = (gex->ix3 != gex->bkwd->ix3);
								j = !i;
								for( ; gei!=ges->bkwd && gex!=gem->bkwd; gei=gei->bkwd, gex=gex->frwd) {
									if( gei->bkwd->ipoints[i][2] != gex->ipoints[i][2]
									|| gei->bkwd->ipoints[j][2] - gei->ipoints[j][2]
										!= gex->bkwd->ipoints[j][2] - gex->ipoints[j][2] 
									) {
										good = 0; /* no symmetry - must be spurious */
#if 0
										fprintf(stderr, " M(%p,%p)(%d %d,%d)(%d %d,%d)",
											gei, gex,
											i, gei->bkwd->ipoints[i][2], gex->ipoints[i][2],
											j, gei->bkwd->ipoints[j][2] - gei->ipoints[j][2],
											gex->bkwd->ipoints[j][2] - gex->ipoints[j][2] );
#endif
										break;
									}
								}
								if(gei == ges->bkwd) { /* oops, the other side is too short */
									good = 0;
#if 0
									fprintf(stderr, " X");
#endif
								}
								if(good && gex == gem->bkwd) {
									if( isign(gei->bkwd->ipoints[j][2] - gei->ipoints[j][2])
									!= isign(gex->bkwd->ipoints[j][2] - gex->ipoints[j][2]) ) {
										good = 0; /* oops, goes into another direction */
#if 0
										fprintf(stderr, " D");
#endif
									}
								}
								if(!good) { /* it is spurious, drop it */
#if 0
									fprintf(stderr, " %p: %s spurious end\n", gem->bkwd, gxf_name[d]);
#endif
									X_FRAG(ges)->len[d] = --len;
								}
							}
							if(len < 4) {
								X_FRAG(ges)->len[d] = 0;
#if 0
								fprintf(stderr, " %p: %s frag discarded, too small now\n", ge, gxf_name[d]);
#endif
							}
							if(ges != ge) {
								if(ges == cge->next)
									break; /* went around the loop */
								else {
									ge = ges->frwd; /* don't look at this fragment twice */
									continue;
								}
							}
						}

						ge = ge->frwd;
					} while(ge != cge->next);
				}

				/* Find the straight line fragments.
				 * Even though the lines are sloped, they are called
				 * "vertical" or "horizontal" according to their longer
				 * dimension. All the steps in the shother dimension must 
				 * be 1 pixel long, all the steps in the longer dimension
				 * must be within the difference of 1 pixel.
				 */
				for(d = GEXFI_LINE; d<= GEXFI_EXACTLINE; d++) {
					ge = cge->next;
					pge = ge->bkwd; /* the beginning of the fragment */
					count = 1;
					delaystop = 0;
					do {
						int h;

						stepmore = 0;
						hdir = isign(ge->ix3 - ge->bkwd->ix3);
						vdir = isign(ge->iy3 - ge->bkwd->iy3);
						vert = (hdir == 0);
						if(count==1) {
							/* at this point pge==ge->bkwd */
							/* account for the previous gentry, which was !vert */
							if(!vert) { /* prev was vertical */
								maxlen[0] = minlen[0] = 0;
								maxlen[1] = minlen[1] = abs(pge->iy3 - pge->bkwd->iy3);
								line[0] = (maxlen[1] == 1);
								line[1] = 1;
								fullhdir = hdir;
								fullvdir = isign(pge->iy3 - pge->bkwd->iy3);
							} else {
								maxlen[0] = minlen[0] = abs(pge->ix3 - pge->bkwd->ix3);
								maxlen[1] = minlen[1] = 0;
								line[0] = 1;
								line[1] = (maxlen[0] == 1);
								fullhdir = isign(pge->ix3 - pge->bkwd->ix3);
								fullvdir = vdir;
							}
						}
						h = line[0]; /* remember the prevalent direction */
#if 0
						fprintf(stderr, "  %p: v=%d(%d) h=%d(%d) vl(%d,%d,%d) hl=(%d,%d,%d) %s count=%d ",
							ge, vdir, fullvdir, hdir, fullhdir, 
							line[1], minlen[1], maxlen[1],
							line[0], minlen[0], maxlen[0],
							gxf_name[d], count);
#endif
						if(vert) {
							if(vdir != fullvdir)
								line[0] = line[1] = 0;
							len = abs(ge->iy3 - ge->bkwd->iy3);
						} else {
							if(hdir != fullhdir)
								line[0] = line[1] = 0;
							len = abs(ge->ix3 - ge->bkwd->ix3);
						}
#if 0
						fprintf(stderr, "len=%d\n", len);
#endif
						if(len != 1) /* this is not a continuation in the short dimension */
							line[!vert] = 0;

						/* can it be a continuation in the long dimension ? */
						if( line[vert] ) {
							if(maxlen[vert]==0)
								maxlen[vert] = minlen[vert] = len;
							else if(maxlen[vert]==minlen[vert]) {
								if(d == GEXFI_EXACTLINE) {
									if(len != maxlen[vert])
										line[vert] = 0; /* it can't */
								} else if(len < maxlen[vert]) {
									if(len < minlen[vert]-1)
										line[vert] = 0; /* it can't */
									else
										minlen[vert] = len;
								} else {
									if(len > maxlen[vert]+1)
										line[vert] = 0; /* it can't */
									else
										maxlen[vert] = len;
								}
							} else if(len < minlen[vert] || len > maxlen[vert])
								line[vert] = 0; /* it can't */
						}

						if(line[0] == 0 && line[1] == 0) {
#if 0
							if(count >= 3)
								fprintf(stderr, " %s frag %p-%p count=%d\n", gxf_name[d], pge, ge->bkwd, count);
#endif
							X_FRAG(pge)->len[d] = count;
							if(d == GEXFI_EXACTLINE && h) {
								X_FRAG(pge)->flags |= GEXFF_HLINE;
							}
							if(count == 1)
								pge = ge;
							else {
								stepmore = 1; /* may reconsider the 1st gentry */
								pge = ge = ge->bkwd;
								count = 1;
							}
						} else
							count++;

						ge = ge->frwd;
						if(ge == cge->next && !stepmore)
							delaystop = 1; /* consider the first gentry again */
					} while(stepmore || ge != cge->next ^ delaystop);
					/* see if there is an unfinished line left */
					if(count != 1) {
#if 0
						if(count >= 3)
							fprintf(stderr, " %s frag %p-%p count=%d\n", gxf_name[d], pge, ge->bkwd, count);
#endif
						X_FRAG(ge->bkwd->bkwd)->len[d] = 0;
						X_FRAG(pge)->len[d] = count;
					}
				}

				/* do postprocessing of the lines */
#if 0
				fprintf(stderr, "Line postprocessing\n");
				gex_dump_contour(cge->next, clen);
#endif

				/* the non-exact line frags are related to exact line frags sort 
				 * of like to individual gentries: two kinds of exact frags
				 * must be interleaved, with one kind having the size of 3
				 * and the other kind having the size varying within +-2.
				 */

				ge = cge->next;
				do {
					GEX_FRAG *pf, *lastf1, *lastf2;
					int len1, len2, fraglen;

					f = X_FRAG(ge);

					fraglen = f->len[GEXFI_LINE];
					if(fraglen >= 4) {

						vert = 0; /* vert is a pseudo-directon */
						line[0] = line[1] = 1;
						maxlen[0] = minlen[0] = maxlen[1] = minlen[1] = 0;
						lastf2 = lastf1 = f;
						len2 = len1 = 0;
						for(pge = ge, i = 1; i < fraglen; i++, pge=pge->frwd) {
							pf = X_FRAG(pge);
							len = pf->len[GEXFI_EXACTLINE];
#if 0
							fprintf(stderr, "      pge=%p i=%d of %d ge=%p exLen=%d\n", pge, i, 
								f->len[GEXFI_LINE], ge, len);
#endif
							len1++; len2++;
							if(len==0) {
								continue;
							}
							vert = !vert; /* alternate the pseudo-direction */
							if(len > 3)
								line[!vert] = 0;
							if(maxlen[vert] == 0)
								maxlen[vert] = minlen[vert] = len;
							else if(maxlen[vert]-2 >= len && minlen[vert]+2 <= len) {
								if(len > maxlen[vert])
									maxlen[vert] = len;
								else if(len < minlen[vert])
									minlen[vert] = len;
							} else
								line[vert] = 0;
							if(line[0] == 0 && line[1] == 0) {
#if 0
								fprintf(stderr, "  Line breaks at %p %c(%d, %d) %c(%d, %d) len=%d fl=%d l2=%d l1=%d\n",
									pge, (!vert)?'*':' ', minlen[0], maxlen[0], 
									vert?'*':' ', minlen[1], maxlen[1], len, fraglen, len2, len1);
#endif
								if(lastf2 != lastf1) {
									lastf2->len[GEXFI_LINE] = len2-len1;
								}
								lastf1->len[GEXFI_LINE] = len1+1;
								pf->len[GEXFI_LINE] = fraglen+1 - i;
#if 0
								gex_dump_contour(pge, clen);
#endif

								/* continue with the line */
								vert = 0; /* vert is a pseudo-directon */
								line[0] = line[1] = 1;
								maxlen[0] = minlen[0] = maxlen[1] = minlen[1] = 0;
								lastf2 = lastf1 = f;
								len2 = len1 = 0;
							} else {
								lastf1 = pf;
								len1 = 0;
							}
						}
					}

					ge = ge->frwd;
				} while(ge != cge->next);
#if 0
				fprintf(stderr, "Line postprocessing part 2\n");
				gex_dump_contour(cge->next, clen);
#endif

				ge = cge->next;
				do {
					f = X_FRAG(ge);

					if(f->len[GEXFI_LINE] >= 4) {
						len = f->len[GEXFI_EXACTLINE];
						/* if a non-exact line covers precisely two exact lines,
						 * split it
						 */
						if(len > 0 && f->len[GEXFI_LINE] >= len+1) {
							GEX_FRAG *pf;
							pge = age[(f->aidx + len - 1)%clen]; /* last gentry of exact line */
							pf = X_FRAG(pge);
							if(f->len[GEXFI_LINE] + 1 == len + pf->len[GEXFI_EXACTLINE]) {
								f->len[GEXFI_LINE] = len;
								f->flags |= GEXFF_SPLIT;
								pf->len[GEXFI_LINE] = pf->len[GEXFI_EXACTLINE];
								pf->flags |= GEXFF_SPLIT;
							}
						}
					}

					ge = ge->frwd;
				} while(ge != cge->next);
#if 0
				fprintf(stderr, "Line postprocessing part 2a\n");
				gex_dump_contour(cge->next, clen);
#endif
				ge = cge->next;
				do {
					f = X_FRAG(ge);

					/* too small lines are of no interest */
					if( (f->flags & GEXFF_SPLIT)==0 && f->len[GEXFI_LINE] < 4)
						f->len[GEXFI_LINE] = 0;

					len = f->len[GEXFI_EXACTLINE];
					/* too small exact lines are of no interest */
					if(len < 3) /* exact lines may be shorter */
						f->len[GEXFI_EXACTLINE] = 0;
					/* get rid of inexact additions to the end of the exact lines */
					else if(f->len[GEXFI_LINE] == len+1)
						f->len[GEXFI_LINE] = len;
					/* same at the beginning */
					else {
						int diff = X_FRAG(ge->bkwd)->len[GEXFI_LINE] - len;

						if(diff == 1 || diff == 2) {
							X_FRAG(ge->bkwd)->len[GEXFI_LINE] = 0;
							f->len[GEXFI_LINE] = len;
						}
					}

					ge = ge->frwd;
				} while(ge != cge->next);
#if 0
				fprintf(stderr, "Line postprocessing is completed\n");
				gex_dump_contour(cge->next, clen);
#endif

				gex_calc_lenback(cge->next, clen); /* prepare data */

				/* resolve conflicts between lines and curves */

				/*
				 * the short (3-gentry) curve frags must have one of the ends
				 * coinciding with another curve frag of the same type
				 */

				for(d = GEXFI_CONVEX; d<= GEXFI_CONCAVE; d++) {
					ge = cge->next;
					do {
						f = X_FRAG(ge);

						if(f->len[d] == 3) {
							pge = age[(f->aidx + 2)%clen]; /* last gentry of this frag */
							if(f->lenback[d] == 0 && X_FRAG(pge)->len[d] == 0) {
								fprintf(stderr, "    discarded small %s at %p-%p\n", gxf_name[d], ge, pge);
								f->len[d] = 0;
								X_FRAG(ge->frwd)->lenback[d] = 0;
								X_FRAG(ge->frwd->frwd)->lenback[d] = 0;
							}
						}
						ge = ge->frwd;
					} while(ge != cge->next);
				}

				/* the serifs take priority over everything else */
				ge = cge->next;
				do {
					f = X_FRAG(ge);

					len = f->len[GEXFI_SERIF];
					if(len == 0)
						continue;

					if(len != 2) { /* this is used in the code below */
						fprintf(stderr, "Internal error at %s line %d: serif frags len is %d\n",
							__FILE__, __LINE__, len);
						exit(1);
					}

					for(d = 0; d < GEXFI_SERIF; d++) {
						/* serifs may not have common ends with the other fragments,
						 * this is expressed as extending them by 1 gentry on each side
						 */
						frag_subtract(g, age, clen, ge->bkwd, len+2, d);
					}
				} while( (ge = ge->frwd) != cge->next);

				/*
				 * longer exact lines take priority over curves; shorter lines
				 * and inexact lines are resolved with convex/concave conflicts
				 */
				ge = cge->next;
				do {
					f = X_FRAG(ge);

					len = f->len[GEXFI_EXACTLINE]; 

					if(len < 6) { /* line is short */
						ge = ge->frwd;
						continue;
					}

					fprintf(stderr, "   line at %p len=%d\n", ge, f->len[GEXFI_EXACTLINE]);
					for(d = GEXFI_CONVEX; d<= GEXFI_CONCAVE; d++) {
						frag_subtract(g, age, clen, ge, len, d);
					}

					ge = ge->frwd;
				} while(ge != cge->next);

				/*
				 * The exact lines take priority over curves that coincide
				 * with them or extend by only one gentry on either side
				 * (but not both sides). By this time it applies only to the
				 * small exact lines.
				 *
				 * An interesting general case is when a curve matches more
				 * than one exact line going diamond-like.
				 */

				ge = cge->next;
				do {
					int done, len2;
					int sharpness;
					GEX_FRAG *pf;

					f = X_FRAG(ge);

					/* "sharpness" shows how a group of exact line frags is connected: if the gentries 
					 * of some of them overlap, the curve matching requirement is loosened: it may
					 * extend up to 1 gentry beyond each end of the group of exact line frags
					 * (sharpness=2); otherwise it may extend to only one end (sharpness=1)
					 */
					sharpness = 1;

					len = f->len[GEXFI_EXACTLINE];
					if(len >= 4) {
						while(len < clen) {
							done = 0;
							pf = X_FRAG(ge->bkwd);
							for(d = GEXFI_CONVEX; d<= GEXFI_CONCAVE; d++) {
								if(f->len[d] == len || f->len[d] == len+1) {

									fprintf(stderr, "    removed %s frag at %p len=%d linelen=%d\n", 
										gxf_name[d], ge, f->len[d], len);
									pge = ge->frwd;
									for(i = f->len[d]; i > 1; i--, pge = pge->frwd)
										X_FRAG(pge)->lenback[d] = 0;
									f->len[d] = 0;
									gex_dump_contour(ge, clen);
									done = 1;
								} else if(pf->len[d] == len+1 || pf->len[d] == len+sharpness) {
									fprintf(stderr, "    removed %s frag at %p len=%d next linelen=%d\n", 
										gxf_name[d], ge->bkwd, pf->len[d], len);
									pge = ge;
									for(i = pf->len[d]; i > 1; i--, pge = pge->frwd)
										X_FRAG(pge)->lenback[d] = 0;
									pf->len[d] = 0;
									gex_dump_contour(ge, clen);
									done = 1;
								}
							}
							if(done)
								break;

							/* is there any chance to match a sequence of exect lines ? */
							if(f->len[GEXFI_CONVEX] < len && f->len[GEXFI_CONCAVE] < len
							&& pf->len[GEXFI_CONVEX] < len && pf->len[GEXFI_CONCAVE] < len)
								break;

							done = 1;
							/* check whether the line is connected to another exact line at an extremum */
							pge = age[(f->aidx + len - 1)%clen]; /* last gentry of exact line */
							len2 = X_FRAG(pge)->len[GEXFI_EXACTLINE];
							if(len2 > 0) {
								if( len2 >= 4 && (X_FRAG(pge)->flags & GEXFF_EXTR) ) {
									len += len2 - 1;
									sharpness = 2;
									done = 0;
								}
							} else {
								/* see if the extremum is between two exact lines */
								pge = pge->frwd;
								if(X_FRAG(pge)->flags & GEXFF_EXTR) {
									pge = pge->frwd;
									len2 = X_FRAG(pge)->len[GEXFI_EXACTLINE];
									if(len2 >= 4) {
										len += len2 + 1;
										done = 0;
									}
								}
							}
							if(done)
								break;
						}
					}

					ge = ge->frwd;
				} while(ge != cge->next);

				/* 
				 * The lines may cover only whole curves (or otherwise empty space),
				 * so cut them where they overlap parts of the curves. If 2 or less
				 * gentries are left in the line, remove the line.
				 * If a line and a curve fully coincide, remove the line.  Otherwise 
				 * remove the curves that are completely covered by the lines.
				 */

				ge = cge->next;
				do {
					f = X_FRAG(ge);

				reconsider_line:
					len = f->len[GEXFI_LINE];

					if(len == 0) {
						ge = ge->frwd;
						continue;
					}

					if(f->len[GEXFI_CONVEX] >= len 
					|| f->len[GEXFI_CONCAVE] >= len) {
				line_completely_covered:
						fprintf(stderr, "    removed covered Line frag at %p len=%d\n", 
							ge, len);
						f->len[GEXFI_LINE] = 0;
						for(pge = ge->frwd; len > 1; len--, pge = pge->frwd)
							X_FRAG(pge)->lenback[GEXFI_LINE] = 0;
						gex_dump_contour(ge, clen);
						ge = ge->frwd;
						continue;
					}
					
					k1 = 0; /* how much to cut at the front */
					for(d = GEXFI_CONVEX; d<= GEXFI_CONCAVE; d++) {
						if(f->lenback[d]) {
							pge = age[(f->aidx + clen - f->lenback[d])%clen];
							i = X_FRAG(pge)->len[d] - f->lenback[d] - 1;
							if(i > k1)
								k1 = i;
						}
					}

					k2 = 0; /* how much to cut at the end */
					pge = age[(f->aidx + len)%clen]; /* gentry after the end */
					for(d = GEXFI_CONVEX; d<= GEXFI_CONCAVE; d++) {
						i = X_FRAG(pge)->lenback[d] - 1;
						if(i > k2)
							k2 = i;
					}

					if(k1+k2 > 0 && k1+k2 >= len-3) {
						fprintf(stderr, "    k1=%d k2=%d\n", k1, k2);
						goto line_completely_covered;
					}


					if(k2 != 0) { /* cut the end */
						len -= k2;
						f->len[GEXFI_LINE] = len;
						/* pge still points after the end */
						for(i = k2, pge = pge->bkwd; i > 0; i--, pge = pge->bkwd)
							X_FRAG(pge)->lenback[GEXFI_LINE] = 0;
					}
					if(k1 != 0) { /* cut the beginning */
						len -= k1;
						f->len[GEXFI_LINE] = 0;
						for(i = 1, pge = ge->frwd; i < k1; i++, pge = pge->frwd)
							X_FRAG(pge)->lenback[GEXFI_LINE] = 0;
						X_FRAG(pge)->len[GEXFI_LINE] = len;
						for(i = 0; i < len; i++, pge = pge->frwd)
							X_FRAG(pge)->lenback[GEXFI_LINE] = i;
					}
					if(k1 != 0 || k2 != 0) {
						fprintf(stderr, "    cut Line frag at %p by (%d,%d) to len=%d\n", 
							ge, k1, k2, len);
						gex_dump_contour(ge, clen);

						goto reconsider_line; /* the line may have to be cut again */
					}
					pge = age[(f->aidx + k1)%clen]; /* new beginning */
					good = 1; /* flag: no need do do a debugging dump */
					for(i=1; i<len; i++, pge = pge->frwd)
						for(d = GEXFI_CONVEX; d<= GEXFI_CONCAVE; d++) {
							if(X_FRAG(pge)->len[d]) {
								fprintf(stderr, "    removed %s frag at %p len=%d covered by line\n", 
									gxf_name[d], pge, X_FRAG(pge)->len[d], len);
								good = 0;
							}
							X_FRAG(pge)->len[d] = 0;
						}
					pge = age[(f->aidx + k1 + 1)%clen]; /* next after new beginning */
					for(i=1; i<len; i++, pge = pge->frwd)
						for(d = GEXFI_CONVEX; d<= GEXFI_CONCAVE; d++)
							X_FRAG(pge)->lenback[d] = 0;
					if(!good)
						gex_dump_contour(ge, clen);

					ge = ge->frwd;
				} while(ge != cge->next);

				/* Resolve conflicts between curves */
				for(d = GEXFI_CONVEX; d<= GEXFI_CONCAVE; d++) {
					dx = (GEXFI_CONVEX + GEXFI_CONCAVE) - d; /* the other type */
					ge = cge->next;
					do {
						GENTRY *sge;

						f = X_FRAG(ge);
						len = f->len[d];
						if(len < 2) {
							ge = ge->frwd;
							continue;
						}
						sge = ge; /* the start of fragment */

						i = f->len[dx];
						if(i != 0) { /* two curved frags starting here */
							/* should be i!=len because otherwise they would be
							 * covered by an exact line
							 */
							if(i > len) {
							curve_completely_covered:
								/* remove the convex frag */
								fprintf(stderr, "    removed %s frag at %p len=%d covered by %s\n", 
									gxf_name[d], ge, len, gxf_name[dx]);
								f->len[d] = 0;
								for(pge = ge->frwd, j = 1; j < len; j++, pge = pge->frwd)
									X_FRAG(pge)->lenback[d] = 0;
								gex_dump_contour(ge, clen);

								ge = ge->frwd; /* the frag is gone, nothing more to do */
								continue;
							} else {
								/* remove the concave frag */
								fprintf(stderr, "    removed %s frag at %p len=%d covered by %s\n", 
									gxf_name[dx], ge, i, gxf_name[d]);
								f->len[dx] = 0;
								for(pge = ge->frwd, j = 1; j < i; j++, pge = pge->frwd)
									X_FRAG(pge)->lenback[dx] = 0;
								gex_dump_contour(ge, clen);
							}
						}


						k1 = X_FRAG(ge->frwd)->lenback[dx];
						if(k1 != 0) { /* conflict at the front */
							GENTRY *gels, *gele, *gei;

							pge = age[(f->aidx + clen - (k1-1))%clen]; /* first gentry of concave frag */
							k2 = X_FRAG(pge)->len[dx]; /* its length */
							
							i = k2 - (k1-1); /* amount of overlap */
							if(i > len)
								i = len;
							/* i >= 2 by definition */
							if(i >= k2-1) { /* covers the other frag - maybe with 1 gentry showing */
								fprintf(stderr, "    removed %s frag at %p len=%d covered by %s\n", 
									gxf_name[dx], pge, k2, gxf_name[d]);
								X_FRAG(pge)->len[dx] = 0;
								for(pge = pge->frwd, j = 1; j < k2; j++, pge = pge->frwd)
									X_FRAG(pge)->lenback[dx] = 0;
								if(i >= len-1) { /* covers our frag too - maybe with 1 gentry showing */
									/* our frag will be removed as well, prepare a line to replace it */
									gels = ge;
									gele = age[(f->aidx + i - 1)%clen];
									fprintf(stderr, "    new Line frag at %p-%p len=%d\n", gels, gele, i);
									X_FRAG(gels)->len[GEXFI_LINE] = i;
									for(gei = gels->frwd, j = 1; j < i; gei = gei->frwd, j++)
										X_FRAG(gei)->lenback[GEXFI_LINE] = j;
								} else {
									gex_dump_contour(ge, clen);
									ge = ge->frwd;
									continue;
								}
							}
							if(i >= len-1) /* covers our frag - maybe with 1 gentry showing */
								goto curve_completely_covered;

							/* XXX need to do something better for the case when a curve frag
							 * is actually nothing but an artifact of two other curves of
							 * the opposite type touching each other, like on the back of "3"
							 */

							/* change the overlapping part to a line */
							gels = ge;
							gele = age[(f->aidx + i - 1)%clen];
							/* give preference to local extremums */
							if(X_FRAG(gels)->flags & GEXFF_EXTR) {
								gels = gels->frwd;
								i--;
							}
							if(X_FRAG(gele)->flags & GEXFF_EXTR) {
								gele = gele->bkwd;
								i--;
							}
							if(gels->bkwd == gele) { 
								/* Oops the line became negative.  Probably should 
								 * never happen but I can't think of any formal reasoning
								 * leading to that, so check just in case. Restore
								 * the previous state.
								 */
								gels = gele; gele = gels->frwd; i = 2;
							}

							j = X_FRAG(gels)->lenback[dx] + 1; /* new length */
							if(j != k2) {
								X_FRAG(pge)->len[dx] = j;
								fprintf(stderr, "    cut %s frag at %p len=%d to %p len=%d end overlap with %s\n", 
									gxf_name[dx], pge, k2, gels, j, gxf_name[d]);
								for(gei = gels->frwd; j < k2; gei = gei->frwd, j++)
									X_FRAG(gei)->lenback[dx] = 0;
							}

							if(gele != ge) {
								sge = gele;
								f->len[d] = 0;
								fprintf(stderr, "    cut %s frag at %p len=%d ", gxf_name[d], ge, len);
								len--;
								for(gei = ge->frwd; gei != gele; gei = gei->frwd, len--)
									X_FRAG(gei)->lenback[d] = 0;
								X_FRAG(gele)->len[d] = len;
								X_FRAG(gele)->lenback[d] = 0;
								fprintf(stderr, "to %p len=%d start overlap with %s\n", 
									sge, len, gxf_name[dx]);
								for(gei = gei->frwd, j = 1; j < len; gei = gei->frwd, j++)
									X_FRAG(gei)->lenback[d] = j;

							}
							if(i > 1) {
								fprintf(stderr, "    new Line frag at %p-%p len=%d\n", gels, gele, i);
								X_FRAG(gels)->len[GEXFI_LINE] = i;
								for(gei = gels->frwd, j = 1; j < i; gei = gei->frwd, j++)
									X_FRAG(gei)->lenback[GEXFI_LINE] = j;
							}
							gex_dump_contour(ge, clen);
						}

						ge = ge->frwd;
					} while(ge != cge->next);
				}

				/* 
				 * Assert that there are no conflicts any more and
				 * for each gentry find the fragment types that start
				 * and continue here.
				 */
				ge = cge->next;
				do {
					f = X_FRAG(ge);
					dx = GEXFI_NONE; /* type that starts here */
					dy = GEXFI_NONE; /* type that goes through here */
					/* GEXFI_EXACTLINE and GEXFI_SERIF are auxiliary and don't
					 * generate any actual lines/curves in the result
					 */
					for(d = GEXFI_CONVEX; d<= GEXFI_LINE; d++) {
						if(f->len[d]) {
							if(dx >= 0) {
								fprintf(stderr, "**** Internal error in vectorization\n");
								fprintf(stderr, "CONFLICT in %s at %p between %s and %s\n",
									g->name, ge, gxf_name[dx], gxf_name[d]);
								dumppaths(g, cge->next, cge->next->bkwd);
								gex_dump_contour(ge, clen);
								exit(1);
							}
							dx = d;
						}
						if(f->lenback[d]) {
							if(dy >= 0) {
								fprintf(stderr, "**** Internal error in vectorization\n");
								fprintf(stderr, "CONFLICT in %s at %p between %s and %s\n",
									g->name, ge, gxf_name[dy], gxf_name[d]);
								dumppaths(g, cge->next, cge->next->bkwd);
								gex_dump_contour(ge, clen);
								exit(1);
							}
							dy = d;
						}
					}
					f->ixstart = dx;
					f->ixcont = dy;
					ge = ge->frwd;
				} while(ge != cge->next);

				/*
				 * make sure that the contour does not start in the
				 * middle of a fragment
				 */
				ge = cge->next; /* old start of the contour */
				f = X_FRAG(ge);
				if(f->ixstart == GEXFI_NONE && f->ixcont != GEXFI_NONE) {
					/* oops, it's mid-fragment, move the start */
					GENTRY *xge;

					xge = ge->bkwd->next; /* entry following the contour */

					/* find the first gentry of this frag */
					pge = age[(f->aidx + clen - f->lenback[f->ixcont])%clen]; 

					ge->prev = ge->bkwd; 
					ge->bkwd->next = ge;

					cge->next = pge;
					pge->prev = cge;

					pge->bkwd->next = xge;
					if(xge) 
						xge->prev = pge->bkwd;

					cge->ix3 = pge->bkwd->ix3; cge->iy3 = pge->bkwd->iy3;
				}

				/* vectorize each fragment separately 
				 * make 2 passes: first handle the straight lines, then
				 * the curves to allow the curver to be connected smoothly
				 * to the straights
				 */
				ge = cge->next;
				do { /* pass 1 */
					f = X_FRAG(ge);
					switch(f->ixstart) {
					case GEXFI_LINE:
						len = f->len[GEXFI_LINE];
						pge = age[(f->aidx + len - 1)%clen]; /* last gentry */

						if(ge->iy3 == ge->bkwd->iy3) { /* frag starts and ends horizontally */
							k1 = 1/*Y*/ ; /* across the direction of start */
							k2 = 0/*X*/ ; /* along the direction of start */
						} else { /* frag starts and ends vertically */
							k1 = 0/*X*/ ; /* across the direction of start */
							k2 = 1/*Y*/ ; /* along the direction of start */
						}

						if(len % 2) {
							/* odd number of entries in the frag */
							double halfstep, halfend;

							f->vect[0][k1] = fscale * ge->ipoints[k1][2];
							f->vect[3][k1] = fscale * pge->ipoints[k1][2];

							halfstep = (pge->ipoints[k2][2] - ge->bkwd->ipoints[k2][2]) 
								* 0.5 / ((len+1)/2);
							if(f->ixcont != GEXFI_NONE) {
								halfend = (ge->ipoints[k2][2] - ge->bkwd->ipoints[k2][2]) * 0.5;
								if(fabs(halfstep) < fabs(halfend)) /* must be at least half gentry away */
									halfstep = halfend;
							}
							if(X_FRAG(pge)->ixstart != GEXFI_NONE) {
								halfend = (pge->ipoints[k2][2] - pge->bkwd->ipoints[k2][2]) * 0.5;
								if(fabs(halfstep) < fabs(halfend)) /* must be at least half gentry away */
									halfstep = halfend;
							}
							f->vect[0][k2] = fscale * (ge->bkwd->ipoints[k2][2] + halfstep);
							f->vect[3][k2] = fscale * (pge->ipoints[k2][2] - halfstep);
						} else {
							/* even number of entries */
							double halfstep, halfend;

							f->vect[0][k1] = fscale * ge->ipoints[k1][2];
							halfstep = (pge->ipoints[k2][2] - ge->bkwd->ipoints[k2][2]) 
								* 0.5 / (len/2);
							if(f->ixcont != GEXFI_NONE) {
								halfend = (ge->ipoints[k2][2] - ge->bkwd->ipoints[k2][2]) * 0.5;
								if(fabs(halfstep) < fabs(halfend)) /* must be at least half gentry away */
									halfstep = halfend;
							}
							f->vect[0][k2] = fscale * (ge->bkwd->ipoints[k2][2] + halfstep);

							halfstep = (pge->ipoints[k1][2] - ge->bkwd->ipoints[k1][2]) 
								* 0.5 / (len/2);
							if(X_FRAG(pge)->ixstart != GEXFI_NONE) {
								halfend = (pge->ipoints[k1][2] - pge->bkwd->ipoints[k1][2]) * 0.5;
								if(fabs(halfstep) < fabs(halfend)) /* must be at least half gentry away */
									halfstep = halfend;
							}
							f->vect[3][k1] = fscale * (pge->ipoints[k1][2] - halfstep);
							f->vect[3][k2] = fscale * pge->ipoints[k2][2];
						}
						f->vectlen = len;
						f->flags |= GEXFF_DRAWLINE;
						break;
					}
				} while((ge = ge->frwd) != cge->next);

				ge = cge->next;
				do { /* pass 2 */
					/* data for curves */
					GENTRY *firstge, *lastge, *gef, *gel, *gei, *gex;
					GENTRY *ordhd; /* head of the order list */
					GENTRY **ordlast;
					int nsub; /* number of subfrags */
					GEX_FRAG *ff, *lf, *xf;

					f = X_FRAG(ge);
					switch(f->ixstart) {
					case GEXFI_CONVEX:
					case GEXFI_CONCAVE:
						len = f->len[f->ixstart];
						firstge = ge;
						lastge = age[(f->aidx + len - 1)%clen]; /* last gentry */

						nsub = 0;
						gex = firstge;
						xf = X_FRAG(gex);
						xf->prevsub = 0;
						xf->sublen = 1;
						xf->flags &= ~GEXFF_DONE;
						for(gei = firstge->frwd; gei != lastge; gei = gei->frwd) {
							xf->sublen++;
							if(X_FRAG(gei)->flags & GEXFF_EXTR) {
									xf->nextsub = gei;
									for(i=0; i<2; i++)
										xf->bbox[i] = abs(gei->ipoints[i][2] - gex->bkwd->ipoints[i][2]);
									nsub++;
									xf = X_FRAG(gei);
									xf->prevsub = gex;
									xf->sublen = 1;
									xf->flags &= ~GEXFF_DONE;
									gex = gei;
								}
						}
						xf->sublen++;
						xf->nextsub = gei;
						for(i=0; i<2; i++)
							xf->bbox[i] = abs(gei->ipoints[i][2] - gex->bkwd->ipoints[i][2]);
						nsub++;
						ff = xf; /* remember the beginning of the last subfrag */
						xf = X_FRAG(gei);
						xf->prevsub = gex;
						if(firstge != lastge) {
							xf->nextsub = 0;
							xf->sublen = 0;

							/* correct the bounding box of the last and first subfrags for
							 * intersections with other fragments 
							 */
							if(xf->ixstart != GEXFI_NONE) {
								/* ff points to the beginning of the last subfrag */
								for(i=0; i<2; i++)
									ff->bbox[i] -= 0.5 * abs(lastge->ipoints[i][2] - lastge->bkwd->ipoints[i][2]);
							}
							ff = X_FRAG(firstge);
							if(ff->ixcont != GEXFI_NONE) {
								for(i=0; i<2; i++)
									ff->bbox[i] -= 0.5 * abs(firstge->ipoints[i][2] - firstge->bkwd->ipoints[i][2]);
							}
						}

						fprintf(stderr, " %s frag %p%s nsub=%d\n", gxf_name[f->ixstart],
							ge, (f->flags&GEXFF_CIRC)?" circular":"", nsub);

						/* find the symmetry between the subfragments */
						for(gef = firstge, count=0; count < nsub; gef = ff->nextsub, count++) {
							ff = X_FRAG(gef);
							gex = ff->nextsub;
							xf = X_FRAG(gex);
							gel = xf->nextsub;
							if(gel == 0) {
								ff->flags &= ~GEXFF_SYMNEXT;
								break; /* not a circular frag */
							}
							good = 1; /* assume that we have symmetry */
							/* gei goes backwards, gex goes forwards from the extremum */
							gei = gex;
							/* i is the symmetry axis, j is the other axis (X=0 Y=1) */
							ff->symaxis = i = (gex->ix3 != gex->bkwd->ix3);
							j = !i;
							for( ; gei!=gef && gex!=gel; gei=gei->bkwd, gex=gex->frwd) {
								if( gei->bkwd->ipoints[i][2] != gex->ipoints[i][2]
								|| gei->bkwd->ipoints[j][2] - gei->ipoints[j][2]
									!= gex->bkwd->ipoints[j][2] - gex->ipoints[j][2] 
								) {
									good = 0; /* no symmetry */
									break;
								}
							}
							if(good) {
								if( isign(gei->bkwd->ipoints[j][2] - gei->ipoints[j][2])
								!= isign(gex->bkwd->ipoints[j][2] - gex->ipoints[j][2]) ) {
									good = 0; /* oops, goes into another direction */
								}
							}
							if(good)
								ff->flags |= GEXFF_SYMNEXT;
							else
								ff->flags &= ~GEXFF_SYMNEXT;
						}

						for(gef = firstge, count=0; count < nsub; gef = ff->nextsub, count++) {
							ff = X_FRAG(gef);
							if((ff->flags & GEXFF_SYMNEXT)==0) {
								ff->symxlen = 0;
								continue;
							}
							gex = ff->prevsub;
							if(gex == 0 || (X_FRAG(gex)->flags & GEXFF_SYMNEXT)==0) {
								ff->symxlen = 0;
								continue;
							}
							ff->symxlen = X_FRAG(gex)->sublen;
							xf = X_FRAG(ff->nextsub);
							if(xf->sublen < ff->symxlen)
								ff->symxlen = xf->sublen;
						}

						/* find the symmetry inside the subfragments */
						for(gef = firstge, count=0; count < nsub; gef = ff->nextsub, count++) {
							ff = X_FRAG(gef);

							if(ff->sublen % 2) {
								/* we must have an even number of gentries for diagonal symmetry */
								ff->symge = 0;
								continue;
							}

							/* gei goes forwards from the front */
							gei = gef->frwd;
							/* gex goes backwards from the back */
							gex = ff->nextsub->bkwd;

							/* i is the direction of gei, j is the direction of gex */
							i = (gei->iy3 != gei->bkwd->iy3);
							j = !i;
							for( ; gei->bkwd != gex; gei=gei->frwd, gex=gex->bkwd) {
								if( abs(gei->bkwd->ipoints[i][2] - gei->ipoints[i][2])
								!= abs(gex->bkwd->ipoints[j][2] - gex->ipoints[j][2]) )
									break; /* no symmetry */
								i = j;
								j = !j;
							}
							if(gei->bkwd == gex)
								ff->symge = gex;
							else
								ff->symge = 0; /* no symmetry */
						}

						/* find the order of calculation:
						 * prefer to start from long fragments that have the longest
						 * neighbours symmetric with them, with all being equal prefer
						 * the fragments that have smaller physical size
						 */
						ordhd = 0;
						for(gef = firstge, count=0; count < nsub; gef = ff->nextsub, count++) {
							ff = X_FRAG(gef);

							for(ordlast = &ordhd; *ordlast != 0; ordlast = &xf->ordersub) {
								xf = X_FRAG(*ordlast);
								if(ff->sublen > xf->sublen)
									break;
								if(ff->sublen < xf->sublen)
									continue;
								if(ff->symxlen > xf->symxlen)
									break;
								if(ff->symxlen < xf->symxlen)
									continue;
								if(ff->bbox[0] < xf->bbox[0] || ff->bbox[1] < xf->bbox[1])
									break;
							}

							ff->ordersub = *ordlast;
							*ordlast = gef;
						}

						/* vectorize the subfragments */
						for(gef = ordhd; gef != 0; gef = ff->ordersub) {

							/* debugging stuff */
							ff = X_FRAG(gef);
							fprintf(stderr, "   %p-%p bbox[%g,%g] sym=%p %s len=%d xlen=%d\n",
								gef, ff->nextsub, ff->bbox[0], ff->bbox[1], ff->symge, 
								(ff->flags & GEXFF_SYMNEXT) ? "symnext" : "",
								ff->sublen, ff->symxlen);

							dosubfrag(g, f->ixstart, firstge, gef, fscale);
						}

						break;
					}
				} while((ge = ge->frwd) != cge->next);

				free(age);

			}

		}

		/* all the fragments are found, extract the vectorization */
		pge = g->entries;
		g->entries = g->lastentry = 0;
		g->flags |= GF_FLOAT;
		loopge = 0;
		skip = 0;

		for(ge = pge; ge != 0; ge = ge->next) {
			GEX_FRAG *f, *pf;

			switch(ge->type) {
			case GE_LINE:
				f = X_FRAG(ge);
				if(skip == 0) {
					if(f->flags & (GEXFF_DRAWLINE|GEXFF_DRAWCURVE)) {
						/* draw a line to the start point */
						fg_rlineto(g, f->vect[0][0], f->vect[0][1]);
						/* draw the fragment */
						if(f->flags & GEXFF_DRAWCURVE)
							fg_rrcurveto(g, 
								f->vect[1][0], f->vect[1][1],
								f->vect[2][0], f->vect[2][1],
								f->vect[3][0], f->vect[3][1]);
						else
							fg_rlineto(g, f->vect[3][0], f->vect[3][1]);
						skip = f->vectlen - 2;
					} else {
						fg_rlineto(g, fscale * ge->ix3, fscale * ge->iy3);
					}
				} else
					skip--;
				break;
			case GE_MOVE:
				fg_rmoveto(g, -1e6, -1e6); /* will be fixed by GE_PATH */
				skip = 0;
				/* remember the reference to update it later */
				loopge = g->lastentry;
				break;
			case GE_PATH:
				/* update the first MOVE of this contour */
				if(loopge) {
					loopge->fx3 = g->lastentry->fx3;
					loopge->fy3 = g->lastentry->fy3;
					loopge = 0;
				}
				g_closepath(g);
				break;
			}
		}
		for(ge = pge; ge != 0; ge = cge) {
			cge = ge->next;
			free(ge->ext);
			free(ge);
		}
		dumppaths(g, NULL, NULL);
		
		/* end of vectorization logic */
	} else {
		/* convert the data to float */
		GENTRY *ge;
		double x, y;

		for(ge = g->entries; ge != 0; ge = ge->next) {
			ge->flags |= GEF_FLOAT;
			if(ge->type != GE_MOVE && ge->type != GE_LINE) 
				continue;

			x = fscale * ge->ix3;
			y = fscale * ge->iy3;

			ge->fx3 = x;
			ge->fy3 = y;
		}
		g->flags |= GF_FLOAT;
	}

	free(hlm); free(vlm); free(amp);
}

#if 0
/* print out the bitmap */
printbmap(bmap, xsz, ysz, xoff, yoff)
	char *bmap;
	int xsz, ysz, xoff, yoff;
{
	int x, y;

	for(y=ysz-1; y>=0; y--) {
		putchar( (y%10==0) ? y/10+'0' : ' ' );
		putchar( y%10+'0' );
		for(x=0; x<xsz; x++)
			putchar( bmap[y*xsz+x] ? 'X' : '.' );
		if(-yoff==y)
			putchar('_'); /* mark the baseline */
		putchar('\n');
	}
	putchar(' '); putchar(' ');
	for(x=0; x<xsz; x++)
		putchar( x%10+'0' );
	putchar('\n'); putchar(' '); putchar(' ');
	for(x=0; x<xsz; x++)
		putchar( (x%10==0) ? x/10+'0' : ' ' );
	putchar('\n');
}

/* print out the limits of outlines */
printlimits(hlm, vlm, amp, xsz, ysz)
	char *hlm, *vlm, *amp;
	int xsz, ysz;
{
	int x, y;
	static char h_char[]={ ' ', '~', '^' };
	static char v_char[]={ ' ', '(', ')' };

	for(y=ysz-1; y>=0; y--) {
		for(x=0; x<xsz; x++) {
			if(amp[y*xsz+x])
				putchar('!'); /* ambigouos point is always on a limit */
			else
				putchar(v_char[ vlm[y*(xsz+1)+x] & (L_ON|L_OFF) ]);
			putchar(h_char[ hlm[(y+1)*xsz+x] & (L_ON|L_OFF) ]);
		}
		putchar(v_char[ vlm[y*(xsz+1)+x] & (L_ON|L_OFF) ]);
		putchar('\n');
	}
	/* last line */
	for(x=0; x<xsz; x++) {
		putchar(' ');
		putchar(h_char[ hlm[x] & (L_ON|L_OFF) ]);
	}
	putchar(' ');
	putchar('\n');
}
#endif /* 0 */
