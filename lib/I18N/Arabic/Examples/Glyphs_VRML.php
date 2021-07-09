<?php
/**
 * Example of VRML implementation for Arabic glyphs Class
 *
 * @category  I18N
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org
 */

error_reporting(E_STRICT);
header("Content-type: model/vrml");
?>
#VRML V2.0 utf8
# The VRML 2.0 Sourcebook
# Copyright [1997] By
# Andrea L. Ames, David R. Nadeau, and John L. Moreland
Group {
    children [
        Viewpoint {
            description "Forward view"
            position 0.0 1.6 5.0
        },
        NavigationInfo {
            type "WALK"
            speed 1.0
            headlight FALSE
            avatarSize [ 0.5, 1.6, 0.5 ]
        },
        Inline { url "VRML/dungeon.wrl" }
    ]
}

<?php
require '../../Arabic.php';
$Arabic = new I18N_Arabic('Glyphs');
$text = "خَالِد الشَّمْعَة";
$text = $Arabic->utf8Glyphs($text);
?>

Shape
        {
        geometry Text
                {string "<?php echo $text; ?>"}
        }
