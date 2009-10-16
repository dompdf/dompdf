
# This file should be configured before running `make'.
# Uncomment or change the values that are relevant for your OS.

# The preferred C compiler (by default use the OS-specific default value).
# For BSD/OS, FreeBSD, Linux (all flavors), NetBSD, OpenBSD the default
# compiler is GNU C. 
# (Note please the politically correct ordering by alphabet ! :-)
#
# Use GNU C even if it's not the default compiler
#
#CC=gcc
#
# Use the standard ANSI C compiler on HP-UX even if it's not default
#
#CC=c89

#
# The system-dependent flags for the C compiler
#
# Default

CFLAGS_SYS= -g

# For GNU C
#
#CFLAGS_SYS= -O2
#
# For GNU C with long options support library (Linux etc.)
#
#CFLAGS_SYS= -O2 -D_GNU_SOURCE
#
# For GNU C on HP-UX/PA-RISC 1.1
#
#CFLAGS_SYS= -O2 -Wa,-w
#
# For the standard ANSI C on HP-UX
#
#CFLAGS_SYS= +O2 -D_HPUX_SOURCE

#
# The system-dependent libraries
#
# Defalut (for the BSD-style OSes)

LIBS_SYS= -lm

# For SystemV (such as SCO, UnixWare, Solaris, but _NOT_ Linux or HP-UX)
#
#LIBS_SYS= -lm -lsocket

#
# The flags for C compiler for the FreeType-2 library (disabled by default). 
# This WON'T BUILD with FT2-beta8, use the FreeType release 2.0.4
# http://download.sourceforge.net/freetype/freetype-2.0.4.tar.gz

#CFLAGS_FT= 

# To enable use of the FreeType-2 library
# (if the include and lib directory do not match your installation,
# modify them), also uncomment LIBS_FT
#
CFLAGS_FT = -DUSE_FREETYPE -I/usr/include/freetype2 -I/usr/include

# 
# The FreeType-2 library flags (disabled by default)

#LIBS_FT=

# To enable use of the FreeType-2 library
# (if the include and lib directory do not match your installation,
# modify them), also uncomment CFLAGS_FT
#
LIBS_FT= -L/usr/lib -lfreetype

#
# The flags for C compiler for the Autotrace library (disabled by default). 
# USE OF THIS FEATURE IS STRONGLY DISCOURAGED, THE BUILT-IN TRACING
# (AKA VECTORIZATION) PROVIDES MUCH BETTER RESULTS.
# The tested version is 0.29a (and the fonts produced with it are
# absolutely not usable).
# http://download.sourceforge.net/autotrace/autotrace-0.29.tar.gz

CFLAGS_AT= 

# To enable use of the Autotrace library
# (if the include and lib directory do not match your installation,
# modify them), also uncomment LIBS_AT
#
#CFLAGS_AT = -DUSE_AUTOTRACE -I/usr/local/include

# 
# The Autotrace library flags (disabled by default)

LIBS_AT=

# To enable use of the Autotrace library
# (if the include and lib directory do not match your installation,
# modify them), also uncomment CFLAGS_AT
#
#LIBS_AT= -L/usr/local/lib -lautotrace

#
# Preference of front-ends if multiple parsers match a file
# (by default the build-in front-end takes preference over FreeType)

CFLAGS_PREF=

# To prefer FreeType (if enabled):
#
#CFLAGS_PREF= -DPREFER_FREETYPE

# Uncomment the second line to not compile t1asm into ttf2pt1
CFLAGS_EXTT1ASM=
#CFLAGS_EXTT1ASM= -DEXTERNAL_T1ASM

CFLAGS= $(CFLAGS_SYS) $(CFLAGS_FT) $(CFLAGS_AT) $(CFLAGS_PREF)
LIBS= $(LIBS_SYS) $(LIBS_FT) $(LIBS_AT)

# Installation-related stuff
# 
# The base dir for installation and subdirs in it
INSTDIR = /usr/local
# for binaries
BINDIR = $(INSTDIR)/bin
# for binaries of little general interest
LIBXDIR = $(INSTDIR)/libexec/ttf2pt1
# for scripts, maps/encodings etc.
SHAREDIR = $(INSTDIR)/share/ttf2pt1
MANDIR = $(INSTDIR)/man

# owner and group of installed files
OWNER = root
GROUP = bin

# After you have configured the Makefile, comment out the following
# definition:
warning: docs
	@echo >&2
	@echo "  You have to configure the Makefile before running make!" >&2
	@echo "(or if you are lazy and hope that it will work as is run \`make all')">&2
	@echo >&2

DOCS=CHANGES README FONTS FONTS.hpux encodings/README other/README \
	app/X11/README app/netscape/README app/TeX/README

SUBDIRS = app encodings maps scripts other
TXTFILES = README* FONTS* CHANGES* COPYRIGHT

MANS1=ttf2pt1.1 ttf2pt1_convert.1 ttf2pt1_x2gs.1
MANS=$(MANS1) $(MANS5)

all:	t1asm ttf2pt1 docs mans rpm

docs: $(DOCS)

mans: $(MANS) 

clean:
	rm -f t1asm ttf2pt1 *.o app/RPM/Makefile app/RPM/*.spec *.core core.* core
	( cd other && make clean; )
	( cd app/netscape && make clean; )

veryclean: clean
	rm -f $(DOCS) $(MANS)

rpm: app/RPM/Makefile app/RPM/ttf2pt1.spec

ttf2pt1.1: README.html
	scripts/html2man . . <README.html

ttf2pt1_convert.1 ttf2pt1_x2gs.1: FONTS.html
	scripts/html2man . . <FONTS.html

app/RPM/Makefile: Makefile
	sed 's/^CFLAGS_SYS.*=.*$$/CFLAGS_SYS= -O2 -D_GNU_SOURCE/;/warning:/,/^$$/s/^/#/' <Makefile >app/RPM/Makefile

app/RPM/ttf2pt1.spec: app/RPM/ttf2pt1.spec.src version.h
	sed 's/^Version:.*/Version: '`grep TTF2PT1_VERSION version.h| cut -d\" -f2`'/' <app/RPM/ttf2pt1.spec.src  >$@

t1asm: t1asm.c
	$(CC) $(CFLAGS) -o t1asm -DSTANDALONE t1asm.c $(LIBS)

ttf2pt1.o: ttf2pt1.c ttf.h pt1.h global.h version.h
	$(CC) $(CFLAGS) -c ttf2pt1.c

pt1.o: pt1.c ttf.h pt1.h global.h
	$(CC) $(CFLAGS) -c pt1.c

ttf.o: ttf.c ttf.h pt1.h global.h
	$(CC) $(CFLAGS) -c ttf.c

ft.o: ft.c pt1.h global.h
	$(CC) $(CFLAGS) -c ft.c

bdf.o: bdf.c pt1.h global.h
	$(CC) $(CFLAGS) -c bdf.c

bitmap.o: bitmap.c pt1.h global.h
	$(CC) $(CFLAGS) -c bitmap.c

runt1asm.o: runt1asm.c global.h
	$(CC) $(CFLAGS) $(CFLAGS_EXTT1ASM) -c runt1asm.c

ttf2pt1:	ttf2pt1.o pt1.o runt1asm.o ttf.o ft.o bdf.o bitmap.o
	$(CC) $(CFLAGS) -o ttf2pt1 ttf2pt1.o pt1.o runt1asm.o ttf.o ft.o bdf.o bitmap.o $(LIBS)

CHANGES: CHANGES.html
	scripts/unhtml <CHANGES.html >CHANGES

README: README.html
	scripts/unhtml <README.html >README

encodings/README: encodings/README.html
	scripts/unhtml <encodings/README.html >encodings/README

other/README: other/README.html
	scripts/unhtml <other/README.html >other/README

app/X11/README: app/X11/README.html
	scripts/unhtml <app/X11/README.html >app/X11/README

app/netscape/README: app/netscape/README.html
	scripts/unhtml <app/netscape/README.html >app/netscape/README

app/TeX/README: app/TeX/README.html
	scripts/unhtml <app/TeX/README.html >app/TeX/README

FONTS: FONTS.html
	scripts/unhtml <FONTS.html >FONTS

FONTS.hpux: FONTS.hpux.html
	scripts/unhtml <FONTS.hpux.html >FONTS.hpux

install: all
	scripts/inst_dir $(BINDIR) $(OWNER) $(GROUP) 0755
	scripts/inst_dir $(LIBXDIR) $(OWNER) $(GROUP) 0755
	scripts/inst_dir $(SHAREDIR) $(OWNER) $(GROUP) 0755
	scripts/inst_dir $(MANDIR)/man1 $(OWNER) $(GROUP) 0755
	scripts/inst_dir $(MANDIR)/man5 $(OWNER) $(GROUP) 0755
	cp -R $(TXTFILES) $(SUBDIRS) $(SHAREDIR)
	chown -R $(OWNER) $(SHAREDIR)
	chgrp -R $(GROUP) $(SHAREDIR)
	chmod -R go-w $(SHAREDIR)
	scripts/inst_file ttf2pt1 $(BINDIR)/ttf2pt1 $(OWNER) $(GROUP) 0755
	[ -f $(BINDIR)/t1asm ] || scripts/inst_file t1asm $(LIBXDIR)/t1asm $(OWNER) $(GROUP) 0755
	sed -e 's|^TTF2PT1_BINDIR=$$|TTF2PT1_BINDIR=$(BINDIR)|;' \
		-e 's|^TTF2PT1_LIBXDIR=$$|TTF2PT1_LIBXDIR=$(LIBXDIR)|;' \
		-e 's|^TTF2PT1_SHAREDIR=$$|TTF2PT1_SHAREDIR=$(SHAREDIR)|;' <scripts/convert >cvt.tmp
	scripts/inst_file cvt.tmp $(BINDIR)/ttf2pt1_convert $(OWNER) $(GROUP) 0755
	scripts/inst_file cvt.tmp $(SHAREDIR)/scripts/convert $(OWNER) $(GROUP) 0755
	rm cvt.tmp
	scripts/inst_file scripts/x2gs $(BINDIR)/ttf2pt1_x2gs $(OWNER) $(GROUP) 0755
	for i in $(MANS1); do { \
		sed -e 's|TTF2PT1_BINDIR|$(BINDIR)|;' \
			-e 's|TTF2PT1_LIBXDIR|$(LIBXDIR)|;' \
			-e 's|TTF2PT1_SHAREDIR|$(SHAREDIR)|;' <$$i >$(MANDIR)/man1/$$i \
		&& chown $(OWNER) $(MANDIR)/man1/$$i \
		&& chgrp $(GROUP) $(MANDIR)/man1/$$i \
		&& chmod 0644 $(MANDIR)/man1/$$i \
		|| exit 1; \
	} done

uninstall:
	rm -f $(BINDIR)/ttf2pt1 $(BINDIR)/ttf2pt1_convert $(BINDIR)/ttf2pt1_x2gs
	rm -rf $(LIBXDIR)
	rm -rf $(SHAREDIR)
	for i in $(MANS1); do { \
		rm -f $(MANDIR)/man1/$$i $(MANDIR)/man1/$$i.gz; \
	} done


# targets for automatic generation of releases and snapshots

snapshot:
	scripts/mkrel snapshot

release:
	scripts/mkrel release
