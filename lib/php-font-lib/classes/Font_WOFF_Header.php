<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

require_once dirname(__FILE__) . "/Font_TrueType_Header.php";

/**
 * WOFF font file header.
 * 
 * @package php-font-lib
 */
class Font_WOFF_Header extends Font_TrueType_Header {
  protected $def = array(
    "format"         => self::uint32,
    "flavor"         => self::uint32,
    "length"         => self::uint32,
    "numTables"      => self::uint16,
                        self::uint16,
    "totalSfntSize"  => self::uint32,
    "majorVersion"   => self::uint16,
    "minorVersion"   => self::uint16,
    "metaOffset"     => self::uint32,
    "metaLength"     => self::uint32,
    "metaOrigLength" => self::uint32,
    "privOffset"     => self::uint32,
    "privLength"     => self::uint32,
  );
}