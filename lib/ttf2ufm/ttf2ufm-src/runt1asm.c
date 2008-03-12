/*
 * Wrap-around code to either compile in t1asm or call it externally
 *
 * Copyright (C) 2000 by Sergey Babkin
 * Copyright (C) 2000 by The TTF2PT1 Project
 *
 * See COPYRIGHT for full license
 */

#ifdef EXTERNAL_T1ASM

#include <stdio.h>
#include <errno.h>

FILE *ifp;
FILE *ofp;

int 
runt1asm(
	int pfbflag
)
{
	char *cmd;
	int id, od;
	int error;

	/* first make a copy in case some of then is already stdin/stdout */
	if(( id = dup(fileno(ifp)) )<0) {
		perror("** Re-opening input file for t1asm");
		exit(1);
	}
	if(( od = dup(fileno(ofp)) )<0) {
		perror("** Re-opening output file for t1asm");
		exit(1);
	}
	fclose(ifp); fclose(ofp);
	close(0);
	if(( dup(id) )!=0) {
		perror("** Re-directing input file for t1asm");
		exit(1);
	}
	close(1);
	if(( dup(od) )!=1) {
		perror("** Re-directing output file for t1asm");
		exit(1);
	}
	close(id); close(od);

	if(pfbflag)
		error = execlp("t1asm", "t1asm", "-b", NULL);
	else
		error = execlp("t1asm", "t1asm", NULL);

	perror("** Calling t1asm");
	
	exit(1);
}

#else
#	include "t1asm.c"
#endif
