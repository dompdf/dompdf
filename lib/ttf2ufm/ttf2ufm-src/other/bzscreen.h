/*
 * see COPYRIGHT
 */


/*
 * Screen for drawing the Bezier curves in text mode
 */

struct screen {
	unsigned physx;
	unsigned physy;
	unsigned cols;
	unsigned rows;
	unsigned xoff;
	unsigned yoff;
	unsigned minx;
	unsigned miny;
	char *dots;
	double xscale;
	double yscale;
} screen;

#define screenabsdot(x,y)	(screen.dots[(y)*screen.cols+(x)])
#define screendot(x,y)	screenabsdot((x)+screen.xoff, (y)+screen.yoff)

/* prototypes */
double fmin(double a, double b);
int abs(int x);
void initscreen(unsigned physx, unsigned physy, 
	unsigned cols, unsigned rows, unsigned xoff, unsigned yoff, 
	unsigned minx, unsigned miny, unsigned maxx, unsigned maxy);
void drawcurve(int mark, int ax,int ay, 
	int bx,int by, int cx,int cy, int dx,int dy);
void drawcurvedir(int mark, int ax,int ay, 
	int bx,int by, int cx,int cy, int dx,int dy);
void drawdot(int mark, int x, int y);
void setabsdot(int mark, int x, int y);
void setfdot(int mark, double x, double y);
void printscreen(FILE *f);
