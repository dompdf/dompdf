/*
 * Fix the Netscape executable for specified font widths
 *
 * (c) 1999 Copyright by Sergey Babkin
 * see COPYRIGHT
 */

#include <sys/types.h>
#include <fcntl.h>
#include <stdio.h>
#include <locale.h>
#include <unistd.h>

/************************** DEFINES *************************/

#undef DEBUG

/* we can handle at most this many fonts */
#define MAXFONTS	20

/* maximal line buffer size */
#define MAXLINE 512

/* there may be multiple strings with the same contents */
#define MAXDUPS 10

/* file read buffer size */
#define FILEBF	40960

/* bits in the hardware page offset */
#define BITSPERPAGE	12

/* size of page in bytes */
#define PAGESIZE (1<<BITSPERPAGE)

/* mask of the in-page offset */
#define PAGEMASK (PAGESIZE-1)

/* this is machine-dependent! */
typedef short t2b; /* 2-byte type */
typedef int t4b; /* 4-byte type */
typedef int tptr; /* integer type with the same size as pointer */

struct bbox { /* bounding box */
	t2b llx; /* lower-left-x */
	t2b lly;
	t2b urx;
	t2b ury; /* upper-right-y */
};

struct glyphmetrics { /* metrics of one glyph */
	t2b	width;
	t2b	unknown;
	struct bbox bbox;
};

struct fontmetrics { /* metrics of the wholefont */
	tptr name;
	struct bbox bbox;
	t2b underlinepos;
	t2b underlinethick;
	struct glyphmetrics glyphs[256];
};

struct font {
	char nsname[MAXLINE]; /* name in the Netscape binary */
	char afmname[MAXLINE]; /* name of the .afm file */
	char pfaname[MAXLINE]; /* name of the .pfa (or .pfb) file */
	struct fontmetrics metrics;
	off_t binoff; /* offset in the binary */
};

#define SCONST(x)	(x), ((sizeof (x))-1)

/************************** GLOBALS *************************/

struct font font[MAXFONTS];
int nfonts=0;

char msg[MAXLINE];

/*************************** PROTOTYPES **********************/

void usage(void);
void readconfig( char *fn);
void readmetrics(void);
void replacefonts( char *fn);

/************************** main ****************************/

main(ac, av)
	int ac;
	char **av;
{
	setlocale(LC_ALL, "");

	if(ac!=3) {
		usage(); exit(1);
	}

	readconfig(av[2]);
	readmetrics();
	replacefonts( av[1]);
}

/************************** usage ***************************/

void
usage(void)
{
	fprintf(stderr,"Use:\n");
	fprintf(stderr,"   nsfix <netscape.bin> <config-file>\n");
}


/************************** readconfig **********************/

void
readconfig(fn)
	char *fn;
{
	char s[MAXLINE];
	char afmsuffix[MAXLINE], pfasuffix[MAXLINE];
	int lineno=0;
	FILE *f;

	if(( f=fopen(fn, "r") )==NULL) {
		sprintf(msg,"nsfix: open %s",fn);
		perror(msg);
		exit(1);
	}

	while( fgets(s, MAXLINE, f) ) {
		lineno++;
		if(s[0]=='#' || s[0]=='\n')
			continue;

		if(nfonts>=MAXFONTS) {
			fprintf(stderr, "nsfix: only %d fonts are supported at once\n", 
				MAXFONTS);
			exit(1);
		}

		if( sscanf(s, "%s %s %s %s", font[nfonts].nsname, 
				font[nfonts].afmname, afmsuffix, pfasuffix) != 4 ) {
			fprintf(stderr, "nsfix: syntax error at line %d of %s\n",
				lineno, fn);
			exit(1);
		}
		strcpy(font[nfonts].pfaname, font[nfonts].afmname);
		strcat(font[nfonts].afmname, afmsuffix);
		strcat(font[nfonts].pfaname, pfasuffix);
		nfonts++;
	}

	if(nfonts==0) {
		fprintf(stderr, "nsfix: no fonts are defined in %s\n", fn);
		exit(1);
	}
	fclose(f);
}

/************************** readmetrics *********************/

void
readmetrics(void)
{
	int i;
	char s[MAXLINE];
	FILE *f;
	int n;
	int lineno;
	int code, width, llx, lly, urx, ury;
	char gn[MAXLINE];
	struct glyphmetrics *gm;

	for(i=0; i<nfonts; i++) {
		if(( f=fopen(font[i].afmname, "r") )==NULL) {
			sprintf(msg,"nsfix: open %s", font[i].afmname);
			perror(msg);
			exit(1);
		}
		lineno=0;
		while( fgets(s, MAXLINE, f) ) {
			lineno++;
			if( !strncmp(s, SCONST("UnderlineThickness ")) ) {
				if( sscanf(s, "UnderlineThickness %d", &n) <1) {
					fprintf(stderr, "nsfix: weird UnderlineThickness at line %d in %s\n",
						lineno, font[i].afmname);
					exit(1);
				}
				font[i].metrics.underlinethick=n;
			} else if( !strncmp(s, SCONST("UnderlinePosition ")) ) {
				if( sscanf(s, "UnderlinePosition %d", &n) <1) {
					fprintf(stderr, "nsfix: weird UnderlinePosition at line %d in %s\n",
						lineno, font[i].afmname);
					exit(1);
				}
				font[i].metrics.underlinepos=n;
			} else if( !strncmp(s, SCONST("FontBBox ")) ) {
				if( sscanf(s, "FontBBox %d %d %d %d", &llx, &lly, &urx, &ury) <4) {
					fprintf(stderr, "nsfix: weird FontBBox at line %d in %s\n",
						lineno, font[i].afmname);
					exit(1);
				}
				font[i].metrics.bbox.llx=llx;
				font[i].metrics.bbox.lly=lly;
				font[i].metrics.bbox.urx=urx;
				font[i].metrics.bbox.ury=ury;
			} else if( !strncmp(s, SCONST("C ")) ) {
				if( sscanf(s, "C %d ; WX %d ; N %s ; B %d %d %d %d", 
					&code, &width, &gn, &llx, &lly, &urx, &ury) <7) 
				{
					fprintf(stderr, "nsfix: weird metrics at line %d in %s\n",
						lineno, font[i].afmname);
					exit(1);
				}
				if(code>=32 && code<=255) {
					font[i].metrics.glyphs[code].width=width;
					font[i].metrics.glyphs[code].bbox.llx=llx;
					font[i].metrics.glyphs[code].bbox.lly=lly;
					font[i].metrics.glyphs[code].bbox.urx=urx;
					font[i].metrics.glyphs[code].bbox.ury=ury;
				}
			}
		}
		fclose(f);
	}

#ifdef DEBUG
	for(i=0; i<nfonts; i++) {
		printf("Font %s\n", font[i].nsname);
		for(n=0; n<256; n++) {
			gm= &font[i].metrics.glyphs[n];
			printf("  %d w=%4d [%4d %4d %4d %4d]", n, gm->width,
				gm->bbox.llx, gm->bbox.lly, gm->bbox.urx, gm->bbox.ury);
			printf("  w=0x%04x [0x%04x 0x%04x 0x%04x 0x%04x]\n", gm->width & 0xffff,
				gm->bbox.llx & 0xffff, gm->bbox.lly & 0xffff, gm->bbox.urx & 0xffff, gm->bbox.ury & 0xffff);
		}
	}

	exit(0);
#endif

}

/************************** replacefonts ********************/

void
replacefonts(fn)
	char *fn;
{
	int f; /* don't use stdio */
	char bf[FILEBF];
	char *bfend, *p;
	int len;
	off_t pos;

	off_t zerooff[MAXFONTS*MAXDUPS]; /* offset of zero strings */
	tptr nameaddr[MAXFONTS*MAXDUPS]; /* name pointers before these zero strings */
	int zeroid[MAXFONTS*MAXDUPS]; /* font number for this zero block */
	int nzeroes;
	short matched[MAXFONTS]; /* counters how many matches we have for each requested font */
	struct fontmetrics *fp;

	struct {
		int noff;
		int nz;
		off_t off[MAXDUPS]; /* there may be multiple strings with the same contents */
	} o[MAXFONTS];
	int maxnlen;
	int i, j, k, n;

	static struct glyphmetrics gm[32]; /* 0-initialized */


	if(( f=open(fn, O_RDWR) )<0) {
		sprintf(msg,"nsfix: open %s",fn);
		perror(msg);
		exit(1);
	}


	/* get the maximal font name length */
	maxnlen=0;
	for(i=0; i<nfonts; i++) {
		o[i].noff=o[i].nz=0;
		matched[i]=0;
		len=strlen(font[i].nsname)+1;
		if(len>maxnlen)
			maxnlen=len;
	}

	/* fprintf(stderr,"maxnlen= 0x%x\n", maxnlen); /* */
	/* try to find the literal strings of the font names */
	pos=0; bfend=bf;
	while(( len=read(f, bfend, FILEBF-(bfend-bf)) )>=0 ) {
		/* fprintf(stderr,"looking at 0x%lx\n", (long)pos); /* */
		/* the last position to check */
		if(len>=maxnlen) 
			/* leave the rest with the next block */
			bfend+= len-maxnlen; 
		else {
			/* we are very near to the end of file, check
			 * up to the very last byte */
			bfend+= len-2;
			memset(bfend+2, 0, maxnlen);
		}

		for(p=bf; p<=bfend; p++)
			for(i=0; i<nfonts; i++)
				if(!strcmp(font[i].nsname, p) && o[i].noff<MAXDUPS) {
					o[i].off[ o[i].noff++ ] = pos + (p-bf);
					fprintf(stderr,"found %s at 0x%lx\n", font[i].nsname, (long)pos + (p-bf));
				}

		if(len==0)
			break;

		memmove(bf, bfend, maxnlen);
		pos+= (bfend-bf);
		bfend= (bf+maxnlen);
	}
	if(len<0) {
		sprintf(msg,"nsfix: read %s",fn);
		perror(msg);
		exit(1);
	}
	fprintf(stderr,"---\n");
	/* if there are any dups try to resolve them */
	for(i=0; i<nfonts; i++) {
		if(o[i].noff==0) {
			fprintf(stderr, "nsfix: font %s (%d of %d) is missing in %s\n", 
				font[i].nsname, i, nfonts, fn);
			exit(1);
		}
		if(o[i].noff!=1)
			continue;
		/* good, only one entry */
		fprintf(stderr,"found unique %s at 0x%lx\n", font[i].nsname, (long)o[i].off[0] );
		/* if any dupped entry is right after this one then it's good */
		/* if it's farther than PAGESIZE/2 then it's bad */
		pos=o[i].off[0]+strlen(font[i].nsname)+1;
		for(j=0; j<MAXFONTS; j++) {
			if(o[j].noff<=1)
				continue;
			for(k=0; k<o[j].noff; k++) {
				if(o[j].off[k]==pos) { /* good */
					fprintf(stderr,"got unique %s at 0x%lx\n", font[j].nsname, (long)pos );
					o[j].off[0]=pos;
					o[j].noff=1;
					break;
				}
				if(o[j].off[k] < pos - PAGESIZE/2
				|| o[j].off[k] > pos + PAGESIZE/2) { /* bad */
					fprintf(stderr, "eliminated %s at 0x%lx\n", font[j].nsname, (long)o[j].off[k] );
					for(n=k+1; n<o[j].noff; n++)
						o[j].off[n-1]=o[j].off[n];
					o[j].noff--;
					k--;
				}
			}
			if(o[j].noff==1 && j<i) { /* have to revisit this font */
				i=j-1; /* compensate for i++ */
				break; 
			}
		}
	}


	/* try to find the metric tables in the executable */
	if(lseek(f, (off_t)0, SEEK_SET)<0) {
		sprintf(msg,"nsfix: rewind %s",fn);
		perror(msg);
		exit(1);
	}

	/*
	 * search for the zeroes in place of the metrics for the codes 0-31:
	 * 4-byte aligned strings of (32*sizeof(struct glyphmetrics)) zero bytes
	 */
	maxnlen=sizeof(struct fontmetrics);

	pos=0; bfend=bf; nzeroes=0;
	while(( len=read(f, bfend, FILEBF-(bfend-bf)) )>=0 ) {
		/* fprintf(stderr,"looking at 0x%lx\n", (long)pos); /* */
		/* the last position to check */
		bfend+= len-maxnlen; /* don't look beyond the EOF */

		for(p=bf; p<=bfend; p+=4 /* 4-byte aligned */ ) {
			fp=(struct fontmetrics *)p;
			if(fp->name==0)
				continue;
			if( memcmp(gm, fp->glyphs, sizeof gm) )
				continue;

			/* OK, looks like it, see if we can match it to any name */
			n= fp->name & PAGEMASK;
			for(i=0; i<nfonts; i++) {
				for(j=0; j<o[i].noff; j++)
					if( n==(o[i].off[j] & PAGEMASK) )  {
						zerooff[nzeroes]= pos + (p-bf);
						nameaddr[nzeroes]= fp->name;
						zeroid[nzeroes]=i;
						o[i].nz++;
						fprintf(stderr, "matched %s at 0x%lx\n", 
							font[i].nsname, (long) zerooff[nzeroes]);
						nzeroes++;
						matched[i]++;
						break;
					}
			}

		}

		if(len==0)
			break;

		memmove(bf, bfend, maxnlen);
		pos+= (bfend-bf);
		bfend= (bf+maxnlen);
	}
	if(len<0) {
		sprintf(msg,"nsfix: read %s",fn);
		perror(msg);
		exit(1);
	}
	fprintf(stderr,"---\n");

	/* make sure that all the fonts got one match */
	k=0; /* flag: have non-matched fonts */ n=0; /* flag: have ambiguities */
	for(i=0; i<nfonts; i++)
		if(matched[i]==0)
			k=1;
		else if(matched[i]>1)
			n=1;

	if(k) {
		fprintf(stderr,"nsfix: can't find match for some of the fonts\n");
		fprintf(stderr,"nsfix: maybe wrong byte order, aborting\n");
		exit(1);
	}
	if(n) {
		fprintf(stderr,"nsfix: got multiple matches for some of the fonts\n");
		fprintf(stderr,"nsfix: can't resolve, aborting\n");
		exit(1);
	}

	/* now finally write the updated tables */
	for(i=0; i<nzeroes; i++) {
		j=zeroid[i];
		fprintf(stderr, "nsfix: writing table for %s at 0x%lx\n", font[j].nsname,
			(long)zerooff[i]);

		font[j].metrics.name=nameaddr[i];
		if( lseek(f, zerooff[i], SEEK_SET)<0 ) {
			sprintf(msg,"nsfix: seek %s to 0x%lx",fn, (long)zerooff[i] );
			perror(msg);
			exit(1);
		}
		if( write(f, &font[j].metrics, sizeof font[j].metrics) != sizeof font[j].metrics ) {
			sprintf(msg,"nsfix: write to %s",fn );
			perror(msg);
			exit(1);
		}
	}

	close(f);
}
