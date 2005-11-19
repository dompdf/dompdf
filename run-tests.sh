#!/bin/sh
echo "Running tests..."
for file in `ls test/*.html test/*.php`
do
  echo
  echo $file
  ./dompdf -v $file
done