###################################################################
#
# DOMPDF Makefile
#
# Creates documentation & distribution packages
#
###################################################################
VERSION=0.3.3
DIST_FILES = dompdf.php dompdf_config.inc.php INSTALL LICENSE.LGPL \
			 load_font.php README HACKING TODO \
			 include/*.php lib/class.pdf.php lib/res/*.*  \
		     lib/fonts/dompdf_font_family_cache.dist \
			 lib/fonts/Helvetica*.afm lib/fonts/Times-*.afm \
			 lib/fonts/Courier*.afm lib/fonts/ZapfDingbats.afm \
			 test/*.html test/*.css test/*.png test/*.php \
			 www/examples.php www/faq.php www/foot.inc www/head.inc \
			 www/index.php www/install.php www/style.css www/usage.php\
			 www/images/*.gif www/images/*.png www/images/*.ico

all: dist
dist: dompdf-$(VERSION).tar.gz
doc: dompdf-doc-$(VERSION).tar.gz
web: dompdf-web-$(VERSION).tar.gz

clean: 
	@rm -f dompdf-$(VERSION).* dompdf-doc-$(VERSION).* dompdf-web-$(VERSION).*

dompdf-doc-$(VERSION).tar.gz:
	@rm -rf dompdf-$(VERSION)/doc
	@phpdoc -po dompdf -dn dompdf -t dompdf-$(VERSION)/doc -d . -ti 'DOMPDF API Documentation'
	tar cvzf dompdf-doc-$(VERSION).tar.gz dompdf-$(VERSION)/doc
	zip -9 -r dompdf-doc-$(VERSION).zip dompdf-$(VERSION)/doc
	@rm -rf dompdf-$(VERSION)

dompdf-$(VERSION).tar.gz: $(DIST_FILES)
	@rm -rf dompdf-$(VERSION)
	@mkdir dompdf-$(VERSION)
	@cp --parents $(DIST_FILES) dompdf-$(VERSION)
	mv dompdf-$(VERSION)/test dompdf-$(VERSION)/www/test
	@cp dompdf-$(VERSION)/lib/fonts/dompdf_font_family_cache.dist dompdf-$(VERSION)/lib/fonts/dompdf_font_family_cache
	tar cvzf dompdf-$(VERSION).tar.gz dompdf-$(VERSION)
	zip -9 -r dompdf-$(VERSION).zip dompdf-$(VERSION)
	@rm -rf dompdf-$(VERSION)
