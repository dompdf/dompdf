
PROGS= bz cmpf dmpf

BZOBJS= bz.o bzscreen.o

CFLAGS= -g

all: $(PROGS)

clean:
	rm -f $(PROGS) *.o *.core core.* core

bz: $(BZOBJS)
	$(CC) $(CFLAGS) -o bz $(BZOBJS)

cmpf: cmpf.c bmpfont.h
	$(CC) $(CFLAGS) -o cmpf -I/usr/local/include -L/usr/local/lib cmpf.c -lt1 -lm

dmpf: dmpf.c bmpfont.h
	$(CC) $(CFLAGS) -o dmpf -I/usr/local/include -L/usr/local/lib dmpf.c -lt1 -lm
