/*
 * see COPYRIGHT
 */

#include <stdio.h>
#include <stdlib.h>
#include "bzscreen.h"

/* size of the screen in "physical pixels" */
#define PHYSX	980
#define PHYSY	310

/* the bounding box of the drawing in "logical pixels" */
/* the base point - set to 0, 0 for absolute coordinates */
#define BASEX 19 
#define BASEY 122
/* the maximal point */
#define MAXX 450
#define MAXY 481

main(argc,argv)
	int argc;
	char **argv;
{
	initscreen(PHYSX, PHYSY, PHYSX, PHYSY, 0, 0, BASEX, BASEY, MAXX, MAXY);

	/*
	drawcurve('#', 0,0, 51,0, 1,49, 45,98);
	drawcurve('1', 5,28, 8,37, 16,65, 45,98);

	drawcurve('3', 0,0, 0,24, 30,68, 80,72);

	drawcurve('1', 0,0, 0,5, 1,10, 2,15);
	drawcurve('2', 2,15, 8,42, 30,68, 80,72);

	drawcurve('4', 0,0, 0,37, 22,67, 80,72);
	*/

	/* final */
	/*
	drawcurve('#', 324, 481, 390, 481, 448, 475, 448, 404 );
	drawcurve('#', 448, 404, 448, 404, 448, 324, 448, 324 );
	drawcurve('#', 448, 324, 402, 245, 19, 338, 19, 122 );
	*/

	/* 3 */
	/*
	*/
	drawcurve('*', 450, 404, 450, 397, 450, 390, 448, 384 );

	drawcurve('*', 448, 384, 446, 378, 444, 370, 443, 360 );
	drawcurve('.', 443, 360, 309, 356, 206, 341, 132, 304 );
	drawcurve('.', 132, 304, 57, 266, 19, 208, 19, 122 );

	/* 4 */
	drawcurve('#', 324, 481, 390, 481, 450, 475, 450, 404 );
	drawcurve('#', 450, 404, 450, 397, 450, 390, 448, 384 );

	drawcurve('#', 448, 384, 402, 245, 19, 338, 19, 122 );

	/*
	drawcurve('.', 324, 481, 361, 481, 391, 478, 414, 466 );
	drawcurve('.', 414, 466, 436, 454, 450, 436, 450, 404 );

	drawcurve('.', 450, 404, 450, 390, 447, 378, 443, 360 );
	drawcurve('.', 443, 360, 309, 356, 206, 341, 132, 304 );

	drawcurve('.', 132, 304, 57, 266, 19, 208, 19, 122 );
	*/

	printscreen(stdout);
}

sumcurves(dx11, dy11, dx12, dy12, dx13, dy13,
	dx21, dy21, dx22, dy22, dx23, dy23)
{
}

