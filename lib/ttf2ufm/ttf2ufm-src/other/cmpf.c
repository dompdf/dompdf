/*
 * see COPYRIGHT
 */

#include <stdio.h>
#include <ctype.h>
#include "t1lib.h"

/*
 * compare two [ almost the same ] fonts
 */

#define PROGNAME "cmpf"

#include "bmpfont.h"


main(ac, av)
	int ac;
	char **av;
{
	int fontid1, fontid2;
	GLYPH *g1, *g2;
	int chr, size, diff, offset;

	if(ac!=3) {
		fprintf(stderr,"Use: %s font1 font2\n", PROGNAME);
		exit(1);
	}

	chkneg(T1_SetBitmapPad(MYPAD));
	chkneg(T1_InitLib(NO_LOGFILE|IGNORE_CONFIGFILE|IGNORE_FONTDATABASE));
	chkneg(fontid1=T1_AddFont(av[1]));
	chkneg(fontid2=T1_AddFont(av[2]));


	resetmap();
	for(chr=0; chr<256; chr++) {
		diff=0;
		for(size=MAXSIZE; size>=MINSIZE; size--) {
			chknull( g1=T1_CopyGlyph(T1_SetChar( fontid1, chr, (float)size, NULL)) );
			chknull( g2=T1_CopyGlyph(T1_SetChar( fontid2, chr, (float)size, NULL)) );

			if( cmpglyphs(g1, g2) ) {
				/* printf("%d %d - diff\n", chr, size); */
				diff=1;
				drawdiff(size, g1, g2);
			} 
			/*
			else
				fprintf(stderr, "%d %d - same\n", chr, size);
			*/

			chkneg(T1_FreeGlyph(g1));
			chkneg(T1_FreeGlyph(g2));
		}
		if(diff) {
			printf("*** Difference for %d==0x%x  %c\n", chr, chr,
				isprint(chr) ? chr : ' ');
			printmap(stdout);
			diff=0;
			resetmap();
		}
	}

	printf("All done!\n");
}
