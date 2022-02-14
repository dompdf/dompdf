#!/usr/bin/env bash
RELEASE=$1

if [ -z "$RELEASE" ]
then
	echo "Must pass release parameter like './scripts/make_release.sh 1.2.0'"
	exit 1
fi

mkdir -p releases;

# TODO: update VERSION file
# TODO: git stash or git clean

echo "installing composer dependencies with --no-dev"
composer install --no-dev

echo "building releases/dompdf-$RELEASE.tar.gz"
tar --transform "s,^,dompdf-$RELEASE/," \
    --transform "s,scripts/,," \
	-zcf releases/dompdf-$RELEASE.tar.gz \
	"src/"\
	"lib/"\
	"tests/"\
	"vendor/"\
	scripts/autoload.inc.php \
	LICENSE.LGPL\
	README.md\
	CONTRIBUTING.md\
	SECURITY.md\
	VERSION\
	phpcs.xml

echo "building zip from releases/dompdf-$RELEASE.tar.gz"
cd releases;

tar -zxf dompdf-$RELEASE.tar.gz
zip -r dompdf-$RELEASE.zip  dompdf-$RELEASE
rm -Rf dompdf-$RELEASE/
