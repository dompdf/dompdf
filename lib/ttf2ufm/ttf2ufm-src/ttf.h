/*
 * see COPYRIGHT
 */

/*	these definitions are mostly taken from Microsoft's True Type
	documentation.
*/

#define BYTE unsigned char
#define CHAR signed char
#define USHORT unsigned short
#define SHORT signed short
#define ULONG unsigned int
#define LONG signed int
#define FWORD SHORT
#define UFWORD USHORT

#define ONOROFF	0x01
#define XSHORT	0x02
#define YSHORT	0x04
#define REPEAT	0x08
#define XSAME	0x10
#define YSAME	0x20

#define ARG_1_AND_2_ARE_WORDS		0x0001
#define ARGS_ARE_XY_VALUES 			0x0002
#define XY_BOUND_TO_GRID			0x0004
#define WE_HAVE_A_SCALE		 		0x0008
#define MORE_COMPONENTS				0x0020
#define WE_HAVE_AN_X_AND_Y_SCALE	0x0040
#define WE_HAVE_A_TWO_BY_TWO		0x0080
#define WE_HAVE_INSTRUCTIONS		0x0100
#define USE_MY_METRICS				0x0200

typedef struct short_2 {
	SHORT	upper;
	USHORT	lower;
} FIXED ;

typedef struct longhormetric {
	UFWORD	advanceWidth;
	FWORD	lsb;
} LONGHORMETRIC;

typedef struct ttf_hhea {
	BYTE	version[4];
	SHORT	ascender, descender, lineGap;
	USHORT	advnaceWidthMax;
	SHORT	minLSB, minRSB, xMaxExtent;
	SHORT	caretSlopeRise, caretSlopeRun;
	SHORT	reserved[5];
	SHORT	metricDataFormat;
	USHORT	numberOfHMetrics;
} TTF_HHEA;

typedef struct ttf_dir_entry {
	char	tag[4];
	ULONG	checksum;
	ULONG	offset;
	ULONG	length;
} TTF_DIR_ENTRY ;

typedef struct ttf_directory {
	ULONG			sfntVersion;
	USHORT			numTables;
	USHORT			searchRange;
	USHORT			entrySelector;
	USHORT			rangeShift;
	TTF_DIR_ENTRY	list;
} TTF_DIRECTORY ;

typedef struct ttf_name_rec {
	USHORT	platformID;
	USHORT	encodingID;
	USHORT	languageID;
	USHORT	nameID;
	USHORT	stringLength;
	USHORT	stringOffset;
} TTF_NAME_REC;

typedef struct ttf_name {
	USHORT			format;
	USHORT			numberOfNameRecords;
	USHORT			offset;
	TTF_NAME_REC	nameRecords;
} TTF_NAME ;

typedef struct ttf_head {
	ULONG	version;
	ULONG	fontRevision;
	ULONG	checksumAdjust;
	ULONG	magicNo;
	USHORT	flags;
	USHORT	unitsPerEm;
	BYTE	created[8];
	BYTE	modified[8];
	FWORD	xMin, yMin, xMax, yMax;
	USHORT	macStyle, lowestRecPPEM;
	SHORT	fontDirection, indexToLocFormat, glyphDataFormat;
} TTF_HEAD ;

typedef struct ttf_kern {
	USHORT	version, nTables;
} TTF_KERN ;

typedef struct ttf_kern_sub {
	USHORT version, length, coverage;
	USHORT nPairs, searchRange, entrySelector, rangeShift;
} TTF_KERN_SUB;

typedef struct ttf_kern_entry {
	USHORT	left, right;
	FWORD	value;
} TTF_KERN_ENTRY;

typedef struct ttf_cmap_fmt0 {
	USHORT	format;
	USHORT	length;
	USHORT	version;
	BYTE	glyphIdArray[256];
} TTF_CMAP_FMT0;

typedef struct ttf_cmap_fmt4 {
	USHORT	format;
	USHORT	length;
	USHORT	version;
	USHORT	segCountX2;
	USHORT	searchRange;
	USHORT	entrySelector;
	USHORT	rangeShift;
} TTF_CMAP_FMT4;

typedef struct ttf_cmap_entry {
	USHORT	platformID;
	USHORT	encodingID;
	ULONG	offset;
} TTF_CMAP_ENTRY;

typedef struct ttf_cmap {
	USHORT			version;
	USHORT			numberOfEncodingTables;
	TTF_CMAP_ENTRY	encodingTable[1];
} TTF_CMAP ;

typedef struct ttf_glyf {
	SHORT	numberOfContours;
	FWORD	xMin, yMin, xMax, yMax;
} TTF_GLYF ;

typedef struct ttf_maxp {
	ULONG	version;
	USHORT	numGlyphs, maxPoints, maxContours;
	USHORT	maxCompositePoints, maxCompositeContours;
	USHORT	maxZones, maxTwilightPoints, maxStorage;
	USHORT	maxFunctionDefs, maxInstructionsDefs;
	USHORT	maxSizeOfInstructions, maxComponentElements;
	USHORT	maxComponentDepth;
} TTF_MAXP ;

typedef struct ttf_post_head {
	ULONG	formatType;
	FIXED	italicAngle;
	FWORD	underlinePosition;
	FWORD	underlineThickness;
	ULONG	isFixedPitch;
	ULONG	minMemType42;
	ULONG	maxMemType42;
	ULONG	minMemType1;
	ULONG	maxMemType1;
	USHORT	numGlyphs;
	USHORT	glyphNameIndex;
} TTF_POST_HEAD ;
