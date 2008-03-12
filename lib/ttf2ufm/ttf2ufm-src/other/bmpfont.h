/*
 * see COPYRIGHT
 */


fchkneg(file, line, rc, cmd)
	char *file;
	int line;
	int rc;
	char *cmd;
{
	if(rc<0) {
		fprintf(stderr,"%s: fatal error on line %d of %s: %d\n", 
			PROGNAME, line, file, rc);
		fprintf(stderr,"%s\n", cmd);
		exit(1);
	}
}

fchknull(file, line, rc, cmd)
	char *file;
	int line;
	void *rc;
	char *cmd;
{
	if(rc==NULL) {
		fprintf(stderr,"%s: fatal error on line %d of %s: NULL\n", 
			PROGNAME, line, file);
		fprintf(stderr,"%s\n", cmd);
		exit(1);
	}
}

#define chkneg(f)	fchkneg(__FILE__,__LINE__,(f),#f)
#define chknull(f)	fchknull(__FILE__,__LINE__,(f),#f)

#define MYPAD 8

#define CHRNONE	' '
#define CHRBOTH	'.'
#define CHRONE	'1'
#define CHRTWO	'2'

#define MINSIZE 8
#define MAXSIZE 20

#define LINEWIDTH	80 /* screen line width in chars */
#define MAXLINES	(MAXSIZE*(MAXSIZE-MINSIZE+1))

static char map[MAXLINES][LINEWIDTH+1];
static char mbase, mx, mend;

/* returns 0 if the same, -1 if different */

int 
cmpglyphs(g1, g2)
	GLYPH *g1, *g2;
{
	int wd1, wd2;
	int ht1, ht2;
	int i, j;
	char *p1, *p2;

	wd1=g1->metrics.rightSideBearing - g1->metrics.leftSideBearing;
	ht1=g1->metrics.ascent - g1->metrics.descent;
	wd2=g2->metrics.rightSideBearing - g2->metrics.leftSideBearing;
	ht2=g2->metrics.ascent - g2->metrics.descent;

	if(g1->bits==NULL && g2->bits!=NULL
	|| g1->bits!=NULL && g2->bits==NULL)
		return -1;

	if(g1->metrics.ascent != g2->metrics.ascent)
		return -1;

	if(g1->metrics.descent != g2->metrics.descent)
		return -1;

	if( wd1 != wd2 )
		return -1;

	if( (p1=g1->bits) !=NULL && (p2=g2->bits) !=NULL )
		for(i=0; i<ht1; i++) {
			for(j=0; j<wd1; j+=8) {
				if( *p1++ != *p2++)
					return -1;
			}
		}
	return 0;
}

void
resetmap()
{
	int i, j;

	for(i=0; i<MAXLINES; i++)
		for(j=0; j<LINEWIDTH; j++)
			map[i][j]=' ';
	mbase=mx=mend=0;
}

void 
drawdot(row, col, val)
	unsigned row, col, val;
{
	if(row < MAXLINES && col < LINEWIDTH-1) {
		map[row][col]=val;
		if(row > mend)
			mend=row;
	}
}

void 
drawdotg1(row, col, val)
	unsigned row, col, val;
{
	if(row < MAXLINES && col < LINEWIDTH-1) {
		if(val)
			map[row][col]=CHRONE;
		else
			map[row][col]=CHRNONE;
		if(row > mend)
			mend=row;
	}
}

void 
drawdotg2(row, col, val)
	unsigned row, col, val;
{
	if(row < MAXLINES && col < LINEWIDTH-1) {
		if(val) 
			if(map[row][col]==CHRONE)
				map[row][col]=CHRBOTH;
			else
				map[row][col]=CHRTWO;
		else if(map[row][col]!=CHRONE)
			map[row][col]=CHRNONE;
		if(row > mend)
			mend=row;
	}
}

void 
drawglyf(size, g1)
	int size;
	GLYPH *g1;
{
	int wd1, wd2, wdm;
	int ht1, ht2, ascm, desm;
	int i, j, k, val;
	char *p;
	int off1, off2;

	wd1=g1->metrics.rightSideBearing - g1->metrics.leftSideBearing;
	ht1=g1->metrics.ascent - g1->metrics.descent;

	wdm=wd1;

	ascm=g1->metrics.ascent;
	desm= -g1->metrics.descent;

	if(mbase==0) 
		mbase=ascm+1;
	else if(LINEWIDTH-mx <= wdm+1) {
		mx=0; mbase=mend+ascm+2;
	}

	drawdot(mbase-ascm-1, mx, (size/10)%10+'0');
	drawdot(mbase-ascm-1, mx+1, size%10+'0');

	if( (p=g1->bits) !=NULL)
		for(i=0; i<ht1; i++) {
			for(j=0; j<wd1; j+=8) {
				val = *p++;
				for(k=0; k<8 && j+k<wd1; k++, val>>=1)
					drawdot(i+mbase-g1->metrics.ascent, mx+j+k, (val&1)?CHRBOTH:CHRNONE);
			}
		}

	wdm++;
	if(wdm<3)
		wdm=3;
	mx+=wdm;
	drawdot(mbase, mx-1, '-');
}

void 
drawdiff(size, g1, g2)
	int size;
	GLYPH *g1, *g2;
{
	int wd1, wd2, wdm;
	int ht1, ht2, ascm, desm;
	int i, j, k, val;
	char *p;
	int off1, off2;

	wd1=g1->metrics.rightSideBearing - g1->metrics.leftSideBearing;
	ht1=g1->metrics.ascent - g1->metrics.descent;
	wd2=g2->metrics.rightSideBearing - g2->metrics.leftSideBearing;
	ht2=g2->metrics.ascent - g2->metrics.descent;

	if(wd1>wd2) {
		wdm=wd1;
		off1=0; off2=wd1-wd2;
	} else {
		wdm=wd2;
		off2=0; off1=wd2-wd1;
	}

	if(g1->metrics.ascent > g2->metrics.ascent)
		ascm=g1->metrics.ascent;
	else
		ascm=g2->metrics.ascent;

	if(g1->metrics.descent < g2->metrics.descent)
		desm= -g1->metrics.descent;
	else
		desm= -g2->metrics.descent;

	if(mbase==0) 
		mbase=ascm+1;
	else if(LINEWIDTH-mx <= wdm+1) {
		mx=0; mbase=mend+ascm+2;
	}

	drawdot(mbase-ascm-1, mx, (size/10)%10+'0');
	drawdot(mbase-ascm-1, mx+1, size%10+'0');

	/* check which alignment is better */
	if(off1!=0 || off2!=0) {
		int cntl,cntr;
		int a1, a2, d1, d2;
		int val1, val2;
		int rstep1, rstep2;

		cntl=cntr=0;
		rstep1=(wd1+7)/8;
		rstep2=(wd2+7)/8;
		a1=g1->metrics.ascent;
		d1=g1->metrics.descent;
		a2=g2->metrics.ascent;
		d2=g2->metrics.descent;

#ifdef dbgoff
		printf("size: %d\n", size);
#endif
		for(i=ascm; i>= -desm; i--) {
			for(j=0; j<wdm; j++) {
				/* first the left alignment */
				if(i>a1 || i<d1 || j>=wd1)
					val1=0;
				else
					val1=( g1->bits[ (a1-i)*rstep1+j/8 ] >> (j%8) ) & 1;
				if(i>a2 || i<d2 || j>=wd2)
					val2=0;
				else
					val2=( g2->bits[ (a2-i)*rstep2+j/8 ] >> (j%8) ) & 1;

				cntl += (val1 ^ val2);

#ifdef dbgoff
				putchar(val1?'1':' ');
				putchar(val2?'2':' ');
				putchar('.');
#endif

				/* now the right alignment */
				if(i>a1 || i<d1 || j-off1>=wd1 || j<off1)
					val1=0;
				else
					val1=( g1->bits[ (a1-i)*rstep1+(j-off1)/8 ] >> ((j-off1)%8) ) & 1;
				if(i>a2 || i<d2 || j-off2>=wd2)
					val2=0;
				else
					val2=( g2->bits[ (a2-i)*rstep2+(j-off2)/8 ] >> ((j-off2)%8) ) & 1;

				cntr += (val1 ^ val2);

#ifdef dbgoff
				putchar(val1?'1':' ');
				putchar(val2?'2':' ');
				putchar('|');
#endif
			}
#ifdef dbgoff
			putchar('\n');
#endif
		}

#ifdef dbgoff
		printf("size %d: left %d right %d\n",size, cntl, cntr);
#endif
		if(cntl <= cntr) /* left is better or the same */
			off1=off2=0;
	}

	if( (p=g1->bits) !=NULL)
		for(i=0; i<ht1; i++) {
			for(j=0; j<wd1; j+=8) {
				val = *p++;
				for(k=0; k<8 && j+k<wd1; k++, val>>=1)
					drawdotg1(i+mbase-g1->metrics.ascent, mx+j+k+off1, val&1);
			}
		}
	if( (p=g2->bits) !=NULL)
		for(i=0; i<ht2; i++) {
			for(j=0; j<wd2; j+=8) {
				val = *p++;
				for(k=0; k<8 && j+k<wd2; k++, val>>=1)
					drawdotg2(i+mbase-g2->metrics.ascent, mx+j+k+off2, val&1);
			}
		}

	wdm++;
	if(wdm<3)
		wdm=3;
	mx+=wdm;
	drawdot(mbase, mx-1, '-');
}

void
printmap(f)
	FILE *f;
{
	int i, j;

	for(i=0; i<=mend; i++) {
		for(j=LINEWIDTH-1; j>=0 && map[i][j]==' '; j--)
			{}
		map[i][j+1]='\n';
		map[i][j+2]=0;
		fputs(map[i], f);
	}
}

