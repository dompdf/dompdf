#!/bin/bash
# Script for Cygwin
# When argument is '1', save baseline

if [[ $(uname) == CYGWIN* ]] ; then
  MYPATH="$(cygpath -w "$(realpath .)")"
else
  MYPATH=$(realpath .)
fi

if [ "$1" == "1" ] ; then
  docker run -w="/mnt/src" -v "$MYPATH:/mnt/src" phanphp/phan:latest  -k /mnt/src/.github/workflows/phan_config.php --analyze-twice --save-baseline /mnt/src/.github/workflows/phan_baseline.txt
else
  docker run -w="/mnt/src" -v "$MYPATH:/mnt/src" phanphp/phan:latest  -k /mnt/src/.github/workflows/phan_config.php -B /mnt/src/.github/workflows/phan_baseline.txt --analyze-twice
fi
