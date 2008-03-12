/* t1asm
 *
 * This program `assembles' Adobe Type-1 font programs in pseudo-PostScript
 * form into either PFB or PFA format.  The human readable/editable input is
 * charstring- and eexec-encrypted as specified in the `Adobe Type 1 Font
 * Format' version 1.1 (the `black book').  There is a companion program,
 * t1disasm, which `disassembles' PFB and PFA files into a pseudo-PostScript
 * file.
 *
 * Copyright (c) 1992 by I. Lee Hetherington, all rights reserved.
 *
 * Permission is hereby granted to use, modify, and distribute this program
 * for any purpose provided this copyright notice and the one below remain
 * intact.
 *
 * I. Lee Hetherington (ilh@lcs.mit.edu)
 *
 * Revision 1.2  92/05/22  11:54:45  ilh
 * Fixed bug where integers larger than 32000 could not be encoded in
 * charstrings.  Now integer range is correct for four-byte
 * twos-complement integers: -(1<<31) <= i <= (1<<31)-1.  Bug detected by
 * Piet Tutelaers (rcpt@urc.tue.nl).
 *
 * Revision 1.1  92/05/22  11:48:46  ilh
 * initial version
 *
 * Ported to Microsoft C/C++ Compiler and MS-DOS operating system by
 * Kai-Uwe Herbing (herbing@netmbx.netmbx.de) on June 12, 1992. Code
 * specific to the MS-DOS version is encapsulated with #ifdef _MSDOS
 * ... #endif, where _MSDOS is an identifier, which is automatically
 * defined, if you compile with the Microsoft C/C++ Compiler.
 *
 */

#ifndef lint
static char copyright[] =
  "@(#) Copyright (c) 1992 by I. Lee Hetherington, all rights reserved.";
#ifdef _MSDOS
static char portnotice[] =
  "@(#) Ported to MS-DOS by Kai-Uwe Herbing (herbing@netmbx.netmbx.de).";
#endif
#endif

/* Note: this is ANSI C. */

#ifdef _MSDOS
  #include <fcntl.h>
  #include <getopt.h>
  #include <io.h>
#endif
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <limits.h>

#ifdef WINDOWS
#	ifdef STANDALONE
#		define WINDOWS_FUNCTIONS
#		include "windows.h"
#	endif
#endif

/* int32 must be at least 32-bit and uint16 must be at least 16-bit */
#if INT_MAX >= 0x7FFFFFFFUL
typedef int int32;
#else
typedef long int32;
#endif
#if USHRT_MAX >= 0xFFFFUL
typedef unsigned short uint16;
#else
typedef unsigned int uint16;
#endif

#define LINESIZE 256

#define MAXBLOCKLEN ((1L<<17)-6)
#define MINBLOCKLEN ((1L<<8)-6)

#define MARKER   128
#define ASCII    1
#define BINARY   2
#define DONE     3

typedef unsigned char byte;

/* must be visible from outside */
FILE *ifp;
FILE *ofp;

/* flags */
static int pfb = 0;
static int active = 0;
static int start_charstring = 0;
static int in_eexec = 0;

static char line[LINESIZE];

/* lenIV and charstring start command */
static int lenIV = 4;
static char cs_start[10];

/* for charstring buffering */
static byte charstring_buf[65535];
static byte *charstring_bp;

/* for PFB block buffering */
static byte blockbuf[MAXBLOCKLEN];
static int32 blocklen = MAXBLOCKLEN;
static int32 blockpos = -1;
static int blocktyp = ASCII;

/* decryption stuff */
static uint16 er, cr;
static uint16 c1 = 52845, c2 = 22719;

/* table of charstring commands */
static struct command {
  char *name;
  int one, two;
} command_table[] = {
  { "callothersubr", 12, 16 },
  { "callsubr", 10, -1 },
  { "closepath", 9, -1 },
  { "div", 12, 12 },
  { "dotsection", 12, 0 },
  { "endchar", 14, -1 },
  { "hlineto", 6, -1 },
  { "hmoveto", 22, -1 },
  { "hsbw", 13, -1 },
  { "hstem", 1, -1 },
  { "hstem3", 12, 2 },
  { "hvcurveto", 31, -1 },
  { "pop", 12, 17 },
  { "return", 11, -1 },
  { "rlineto", 5, -1 },
  { "rmoveto", 21, -1 },
  { "rrcurveto", 8, -1 },
  { "sbw", 12, 7 },
  { "seac", 12, 6 },
  { "setcurrentpoint", 12, 33 },
  { "vhcurveto", 30, -1 },
  { "vlineto", 7, -1 },
  { "vmoveto", 4, -1 },
  { "vstem", 3, -1 },
  { "vstem3", 12, 1 },
};                                                /* alphabetical */

/* Two separate encryption functions because eexec and charstring encryption
   must proceed in parallel. */

static byte eencrypt(byte plain)
{
  byte cipher;

  cipher = (byte) (plain ^ (er >> 8));
  er = (uint16) ((cipher + er) * c1 + c2);
  return cipher;
}

static byte cencrypt(byte plain)
{
  byte cipher;

  cipher = (byte) (plain ^ (cr >> 8));
  cr = (uint16) ((cipher + cr) * c1 + c2);
  return cipher;
}

/* This function flushes a buffered PFB block. */

static void output_block()
{
  int32 i;

  /* output four-byte block length */
  fputc((int) (blockpos & 0xff), ofp);
  fputc((int) ((blockpos >> 8) & 0xff), ofp);
  fputc((int) ((blockpos >> 16) & 0xff), ofp);
  fputc((int) ((blockpos >> 24) & 0xff), ofp);

  /* output block data */
  for (i = 0; i < blockpos; i++)
    fputc(blockbuf[i], ofp);

  /* mark block buffer empty and uninitialized */
  blockpos =  -1;
}

/* This function outputs a single byte.  If output is in PFB format then output
   is buffered through blockbuf[].  If output is in PFA format, then output
   will be hexadecimal if in_eexec is set, ASCII otherwise. */

static void output_byte(byte b)
{
  static char *hexchar = "0123456789ABCDEF";
  static int hexcol = 0;

  if (pfb) {
    /* PFB */
    if (blockpos < 0) {
      fputc(MARKER, ofp);
      fputc(blocktyp, ofp);
      blockpos = 0;
    }
    blockbuf[blockpos++] = b;
    if (blockpos == blocklen)
      output_block();
  } else {
    /* PFA */
    if (in_eexec) {
      /* trim hexadecimal lines to 64 columns */
      if (hexcol >= 64) {
        fputc('\n', ofp);
        hexcol = 0;
      }
      fputc(hexchar[(b >> 4) & 0xf], ofp);
      fputc(hexchar[b & 0xf], ofp);
      hexcol += 2;
    } else {
      fputc(b, ofp);
    }
  }
}

/* This function outputs a byte through possible eexec encryption. */

static void eexec_byte(byte b)
{
  if (in_eexec)
    output_byte(eencrypt(b));
  else
    output_byte(b);
}

/* This function outputs a null-terminated string through possible eexec
   encryption. */

static void eexec_string(char *string)
{
  while (*string)
    eexec_byte((byte) *string++);
}

/* This function gets ready for the eexec-encrypted data.  If output is in
   PFB format then flush current ASCII block and get ready for binary block.
   We start encryption with four random (zero) bytes. */

static void eexec_start()
{
  eexec_string(line);
  if (pfb) {
    output_block();
    blocktyp = BINARY;
  }

  in_eexec = 1;
  er = 55665;
  eexec_byte(0);
  eexec_byte(0);
  eexec_byte(0);
  eexec_byte(0);
}

/* This function wraps-up the eexec-encrypted data.
   If output is in PFB format then this entails flushing binary block and
   starting an ASCII block. */

static void eexec_end()
{
  int i, j;

  if (pfb) {
    output_block();
    blocktyp = ASCII;
  } else {
    fputc('\n', ofp);
  }
  in_eexec = 0;
  for (i = 0; i < 8; i++) {
    for (j = 0; j < 64; j++)
      eexec_byte('0');
    eexec_byte('\n');
  }
#if 0
  eexec_string("cleartomark\n");
#endif
}

/* This function writes ASCII trailer.
   If output is in PFB format then this entails flushing binary block and
   starting an ASCII block. */

static void file_end()
{
  if (pfb) {
    output_block();
    fputc(MARKER, ofp);
    fputc(DONE, ofp);
  }
}
/* This function returns an input line of characters.  A line is terminated by
   length (including terminating null) greater than LINESIZE, a newline \n, or
   when active (looking for charstrings) by '{'.  When terminated by a newline
   the newline is put into line[].  When terminated by '{', the '{' is not put
   into line[], and the flag start_charstring is set to 1. */

static void t1asm_getline()
{
  int c;
  char *p = line;
  int comment = 0;

  start_charstring = 0;
  while (p < line + LINESIZE) {
    c = fgetc(ifp);
    if (c == EOF)
      break;
    if (c == '%')
      comment = 1;
    if (active && !comment && c == '{') {
      start_charstring = 1;
      break;
    }
    *p++ = (char) c;
    if (c == '\n')
      break;
  }
  *p = '\0';
}

/* This function is used by the binary search, bsearch(), for command names in
   the command table. */

static int command_compare(const void *key, const void *item)
{
  return strcmp((char *) key, ((struct command *) item)->name);
}

/* This function returns 1 if the string is an integer and 0 otherwise. */

static int is_integer(char *string)
{
  if (isdigit(string[0]) || string[0] == '-' || string[0] == '+') {
    while (*++string && isdigit(*string))
      ;                                           /* deliberately empty */
    if (!*string)
      return 1;
  }
  return 0;
}

/* This function initializes charstring encryption.  Note that this is called
   at the beginning of every charstring. */

static void charstring_start()
{
  int i;

  charstring_bp = charstring_buf;
  cr = 4330;
  for (i = 0; i < lenIV; i++)
    *charstring_bp++ = cencrypt((byte) 0);
}

/* This function encrypts and buffers a single byte of charstring data. */

static void charstring_byte(int v)
{
  byte b = (byte) (v & 0xff);

  if (charstring_bp - charstring_buf > sizeof(charstring_buf)) {
    fprintf(stderr, "error: charstring_buf full (%d bytes)\n",
            sizeof(charstring_buf));
    exit(1);
  }
  *charstring_bp++ = cencrypt(b);
}

/* This function outputs buffered, encrypted charstring data through possible
   eexec encryption. */

static void charstring_end()
{
  byte *bp;

  sprintf(line, "%d ", charstring_bp - charstring_buf);
  eexec_string(line);
  sprintf(line, "%s ", cs_start);
  eexec_string(line);
  for (bp = charstring_buf; bp < charstring_bp; bp++)
    eexec_byte(*bp);
}

/* This function generates the charstring representation of an integer. */

static void charstring_int(int num)
{
  int x;

  if (num >= -107 && num <= 107) {
    charstring_byte(num + 139);
  } else if (num >= 108 && num <= 1131) {
    x = num - 108;
    charstring_byte(x / 256 + 247);
    charstring_byte(x % 256);
  } else if (num >= -1131 && num <= -108) {
    x = abs(num) - 108;
    charstring_byte(x / 256 + 251);
    charstring_byte(x % 256);
  } else if (num >= (-2147483647-1) && num <= 2147483647) {
    charstring_byte(255);
    charstring_byte(num >> 24);
    charstring_byte(num >> 16);
    charstring_byte(num >> 8);
    charstring_byte(num);
  } else {
    fprintf(stderr,
            "error: cannot format the integer %d, too large\n", num);
    exit(1);
  }
}

/* This function parses an entire charstring into integers and commands,
   outputting bytes through the charstring buffer. */

static void parse_charstring()
{
  struct command *cp;

  charstring_start();
  while (fscanf(ifp, "%s", line) == 1) {
    if (line[0] == '%') {
      /* eat comment to end of line */
      while (fgetc(ifp) != '\n' && !feof(ifp))
        ;                                         /* deliberately empty */
      continue;
    }
    if (line[0] == '}')
      break;
    if (is_integer(line)) {
      charstring_int(atoi(line));
    } else {
      cp = (struct command *)
        bsearch((void *) line, (void *) command_table,
                sizeof(command_table) / sizeof(struct command),
                sizeof(struct command),
                command_compare);
      if (cp) {
        charstring_byte(cp->one);
        if (cp->two >= 0)
          charstring_byte(cp->two);
      } else {
        fprintf(stderr, "error: cannot use `%s' in charstring\n",line);
        exit(1);
      }
    }
  }
  charstring_end();
}

static void usage()
{
  fprintf(stderr,
          "usage: t1asm [-b] [-l block-length] [input [output]]\n");
  fprintf(stderr,
          "\n-b means output in PFB format, otherwise PFA format.\n");
  fprintf(stderr,
          "The block length applies to the length of blocks in the\n");
  fprintf(stderr,
          "PFB output file; the default is to use the largest possible.\n");
  exit(1);
}

static void print_banner()
{
  static char rcs_revision[] = ""; /* removed RCS */
  static char revision[20];

  if (sscanf(rcs_revision, "$Revision: %19s", revision) != 1)
    revision[0] = '\0';
  fprintf(stderr, "This is t1asm %s.\n", revision);
}

#ifdef STANDALONE
int main(int argc, char **argv)
{
  char *p, *q, *r;
  int c;

  extern char *optarg;
  extern int optind;

  ifp = stdin;
  ofp = stdout;

  print_banner();

  /* interpret command line arguments using getopt */
  while ((c = getopt(argc, argv, "bl:")) != -1)
    switch (c) {
    case 'b':
      pfb = 1;
      break;
    case 'l':
      blocklen = atoi(optarg);
      if (blocklen < MINBLOCKLEN) {
        blocklen = MINBLOCKLEN;
        fprintf(stderr,
                "warning: using minimum block length of %d\n",
                blocklen);
      } else if (blocklen > MAXBLOCKLEN) {
        blocklen = MAXBLOCKLEN;
        fprintf(stderr,
                "warning: using maximum block length of %d\n",
                blocklen);
      }
      break;
    default:
      usage();
      break;
    }
  if (argc - optind > 2)
    usage();

  /* possibly open input & output files */
  if (argc - optind >= 1) {
    ifp = fopen(argv[optind], "r");
    if (!ifp) {
      fprintf(stderr, "error: cannot open %s for reading\n", argv[1]);
      exit(1);
    }
  }
  if (argc - optind >= 2) {
    ofp = fopen(argv[optind + 1], "w");
    if (!ofp) {
      fprintf(stderr, "error: cannot open %s for writing\n", argv[2]);
      exit(1);
    }
  }

#else
int runt1asm(int pfbflag)
{
  char *p, *q, *r;

  pfb = pfbflag;
#endif

  #ifdef _MSDOS
    /* If we are processing a PFB (binary) output */
    /* file, we must set its file mode to binary. */
    if (pfb)
      _setmode(_fileno(ofp), _O_BINARY);
  #endif

  /* Finally, we loop until no more input.  Some special things to look for
     are the `currentfile eexec' line, the beginning of the `/Subrs'
     definition, the definition of `/lenIV', and the definition of the
     charstring start command which has `...string currentfile...' in it. */

  while (!feof(ifp) && !ferror(ifp)) {
    t1asm_getline();
    if (strcmp(line, "currentfile eexec\n") == 0) {
      eexec_start();
      continue;
    } else if (strstr(line, "/Subrs") && isspace(line[6])) {
      active = 1;
    } else if ((p = strstr(line, "/lenIV"))) {
      sscanf(p, "%*s %d", &lenIV);
    } else if ((p = strstr(line, "string currentfile"))) {
      /* locate the name of the charstring start command */
      *p = '\0';                                  /* damage line[] */
      q = strrchr(line, '/');
      if (q) {
        r = cs_start;
        ++q;
        while (!isspace(*q) && *q != '{')
          *r++ = *q++;
        *r = '\0';
      }
      *p = 's';                                   /* repair line[] */
    }
    /* output line data */
    eexec_string(line);
	if ((p = strstr(line, "currentfile closefile"))) {
		eexec_end();
	}
    if (start_charstring) {
      if (!cs_start[0]) {
        fprintf(stderr, "error: couldn't find charstring start command\n");
        exit(1);
      }
      parse_charstring();
    }
  }
  file_end();

  fclose(ifp);
  fclose(ofp);

  return 0;
}
