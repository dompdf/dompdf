/*
 * see COPYRIGHT
 */

/*	This defines the macroes ntohs and ntohl, which convert short and long
	ints from network order (used on 68000 chips, and in TrueType font
	files) to whatever order your computer uses. #define _BIG_ENDIAN or not
	to control which set of definitions apply. If you don't know, try both. If
	you have a peculiar machine you're on your own.
*/

#if defined(_BIG_ENDIAN)
#define	ntohl(x)	(x)
#define	ntohs(x)	(x)
#else
#define ntohs(x) \
    ((USHORT)((((USHORT)(x) & 0x00ff) << 8) | \
              (((USHORT)(x) & 0xff00) >> 8))) 
#define ntohl(x) \
    ((ULONG)((((ULONG)(x) & 0x000000ffU) << 24) | \
             (((ULONG)(x) & 0x0000ff00U) <<  8) | \
             (((ULONG)(x) & 0x00ff0000U) >>  8) | \
             (((ULONG)(x) & 0xff000000U) >> 24)))  
#endif
