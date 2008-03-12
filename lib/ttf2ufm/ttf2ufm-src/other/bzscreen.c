/*
 * see COPYRIGHT
 */

#include <stdio.h>
#include <stdlib.h>
#include "bzscreen.h"

/*
 * functions to draw the bezier curves in text mode 
 */

double
fmin(a,b)
	double a, b;
{
	if(a<b)
		return a;
	else
		return b;
}

int
abs(x)
	int x;
{
	if(x<0)
		return -x;
	else
		return x;
}

void
initscreen(physx, physy, cols, rows, xoff, yoff, minx, miny, maxx, maxy)
	unsigned physx, physy, cols, rows, xoff, yoff, minx, miny, maxx, maxy;
{
	int i,j;
	double yxscale;

	if(screen.dots != NULL)
		free(screen.dots);

	if(physx==0 || physy==0 || rows==0 || cols==0) {
		fprintf(stderr, "*** negative or zero screen size\n");
		exit(1);
	}

	if(physx+xoff > cols || physy+yoff > rows) {
		fprintf(stderr, "*** drawable area out of screen\n");
		exit(1);
	}

	if(minx>maxx || miny>maxy) {
		fprintf(stderr, "*** empty drawable area\n");
		exit(1);
	}

	screen.physx = physx;
	screen.physy = physy;
	screen.rows = rows; 
	screen.cols = cols+2; /* for '\n\0' */
	screen.xoff = xoff;
	screen.yoff = yoff;
	screen.minx = minx;
	screen.miny = miny;

	if(( screen.dots=malloc(screen.rows*screen.cols) )==NULL) {
		perror("*** no memory for screen: ");
		exit(1);
	}

	j=screen.rows*screen.cols;
	for(i=0; i<j; i++)
		screen.dots[i]=' ';

	/* scale of Y to X on the screen, i.e. x=YXSCALE*y */
	/* 3/4 is the approx. ratio of Y/X sizes of the physical screen */
	yxscale = ((double)physx/(double)physy*3.0/4.0);

	/* scale of "logical" to "physical", i.e. physical=PHYSSCALE*logical */
	screen.yscale = fmin( ((double)physy-0.51)/(maxy+1-miny), 
		((double)physx-0.51)/yxscale/(maxx+1-minx) );
	screen.xscale = yxscale * screen.yscale;
}

void
drawcurve(mark, ax,ay, bx,by, cx,cy, dx,dy)
	int mark, ax,ay, bx,by, cx,cy, dx,dy;
{
	int i,j,n,c;
	int maxn=(screen.physx + screen.physy)*2;

	ax-=screen.minx; bx-=screen.minx; cx-=screen.minx; dx-=screen.minx;
	ay-=screen.miny; by-=screen.miny; cy-=screen.miny; dy-=screen.miny;

	for(i=0; i<=maxn; i++) {
		double t, t2, t3, nt, nt2, nt3;

		t=(double)i/(double)maxn; t2=t*t; t3=t2*t;
		nt=1-t; nt2=nt*nt; nt3=nt2*nt;

		setfdot(
			mark, 
			( ax*t3 + bx*3*t2*nt + cx*3*t*nt2 + dx*nt3 ),
			( ay*t3 + by*3*t2*nt + cy*3*t*nt2 + dy*nt3 )
		);
	}
}

/* draw curve and mark direction at the ends */

void
drawcurvedir(mark, ax,ay, bx,by, cx,cy, dx,dy)
	int mark, ax,ay, bx,by, cx,cy, dx,dy;
{
	int i,j,n,c;
	int maxn=(screen.physx + screen.physy)*2;
	double t, t2, t3, nt, nt2, nt3;
	int markb, marke;

	ax-=screen.minx; bx-=screen.minx; cx-=screen.minx; dx-=screen.minx;
	ay-=screen.miny; by-=screen.miny; cy-=screen.miny; dy-=screen.miny;

	if(bx==ax && by==ay) {
		markb=mark;
	} else if( abs(by-ay) > abs(bx-ax) ) {
		if(by>ay)
			markb='^';
		else
			markb='v';
	} else {
		if(bx>ax)
			markb='>';
		else
			markb='<';
	}

	if(dx==cx && dy==cy) {
		marke=mark;
	} else if( abs(dy-cy) > abs(dx-cx) ) {
		if(dy>cy)
			marke='^';
		else
			marke='v';
	} else {
		if(dx>cx)
			marke='>';
		else
			marke='<';
	}

	for(i=1; i<maxn; i++) {
		t=(double)i/(double)maxn; t2=t*t; t3=t2*t;
		nt=1-t; nt2=nt*nt; nt3=nt2*nt;

		setfdot(
			mark,
			( ax*t3 + bx*3*t2*nt + cx*3*t*nt2 + dx*nt3 ),
			( ay*t3 + by*3*t2*nt + cy*3*t*nt2 + dy*nt3 )
		);
	}
	/* mark the ends */
	setfdot( markb, (double)ax, (double)ay );
	setfdot( marke, (double)dx, (double)dy );
}

void
drawdot(mark, x, y)
	int mark;
	int x, y;
{
	x=(int)((x-screen.minx)*screen.xscale+0.5);
	y=(int)((y-screen.miny)*screen.yscale+0.5);

	if(y<0 || y>=screen.physy || x<0 || x>=screen.physx)
		return;
	screendot(x,y)=mark;
}

void
setabsdot(mark, x, y)
	int x, y, mark;
{
	if(y<0 || y>=screen.rows || x<0 || x>=screen.cols-2)
		return;
	screenabsdot(x,y)=mark;
}

void
setfdot(mark, fx, fy)
	int mark;
	double fx, fy;
{
	int x, y;

	x=(int)(fx*screen.xscale+0.5);
	y=(int)(fy*screen.yscale+0.5);

	if(y<0 || y>=screen.physy || x<0 || x>=screen.physx)
		return;
	screendot(x,y)=mark;
}

/* destructive */
void
printscreen(f)
	FILE *f;
{
	int r;
	char *pi, *pc;

	for(r=screen.rows-1; r>=0; r--) {
		pc=&screenabsdot(0,r);
		for(pi=&screenabsdot(-2,r+1); pi>=pc && *pi == ' '; pi--)
			{}
		pi[1]='\n';
		pi[2]=0;
		fputs(pc, f);
	}
}
