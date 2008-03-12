/*
 * see COPYRIGHT
 */

#include <stdio.h>
#include <ctype.h>
#include "t1lib.h"

/*
 * Dump a rasterizarion of the font at small size
 */

#define PROGNAME "dmpf"

#include "bmpfont.h"


main(ac, av)
	int ac;
	char **av;
{
	int fontid1, fontid2;
	GLYPH *g1, *g2;
	int chr, size, diff, offset;

	if(ac!=2) {
		fprintf(stderr,"Use: %s font\n", PROGNAME);
		exit(1);
	}

	chkneg(T1_SetBitmapPad(MYPAD));
	chkneg(T1_InitLib(NO_LOGFILE|IGNORE_CONFIGFILE|IGNORE_FONTDATABASE));
	chkneg(fontid1=T1_AddFont(av[1]));


	resetmap();
	for(chr=0; chr<256; chr++) {
		for(size=MAXSIZE; size>=MINSIZE; size--) {
			chknull( g1=T1_CopyGlyph(T1_SetChar( fontid1, chr, (float)size, NULL)) );

			drawglyf(size, g1);

			chkneg(T1_FreeGlyph(g1));
		}

		printf("*** Glyph %d==0x%x  %c\n", chr, chr,
			isprint(chr) ? chr : ' ');
		printmap(stdout);
		resetmap();
	}

	printf("All done!\n");
}
