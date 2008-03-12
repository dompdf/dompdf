rem file to build ttf2pt1 with Visual C++

cl -DWINDOWS -c bdf.c
cl -DWINDOWS -c ttf2pt1.c
cl -DWINDOWS -c pt1.c
cl -DWINDOWS -c ttf.c
cl -DWINDOWS -c t1asm.c
cl -DWINDOWS -c bitmap.c
cl -o ttf2ufm ttf2pt1.obj pt1.obj t1asm.obj ttf.obj bdf.obj bitmap.obj
cl -o t1asm -DWINDOWS -DSTANDALONE t1asm.c

