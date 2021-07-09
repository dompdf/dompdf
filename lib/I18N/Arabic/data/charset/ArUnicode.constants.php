<?php
/**
 * ----------------------------------------------------------------------
 *  
 * Copyright (c) 2006-2011 Khaled Al-Shamaa.
 *  
 * http://www.ar-php.org
 *  
 * PHP Version 5 
 *  
 * ----------------------------------------------------------------------
 *  
 * LICENSE
 *
 * This program is open source product; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License (LGPL)
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
 *  
 * ----------------------------------------------------------------------
 *  
 * Class Name: Functions to normalise Arabic text.
 *  
 * Filename:   ArUnicode.constants.php
 *  
 * Original    Author(s): Djihed Afifi <djihed@gmail.com>
 *  
 * Purpose:    Arabic Unicode code point constants. To be used by other classes. 
 *  
 * ----------------------------------------------------------------------
 *  
 * This is normally a private file that is used by the ArNormalise class.
 *
 * This file contains the Arabic Unicode code points.
 *
 * It also contains a map the maps the Arabic joined characters code
 * points (e.g Baa' in the middle of a word) with the original letter 
 * (e.g Baa).
 *
 * Code to load this file is present in the constructor of ArNormalise.
 *
 * @category  I18N 
 * @package   Arabic
 * @author    Djihed Afifi <djihed@gmail.com>
 * @copyright 2006-2010 Khaled Al-Shamaa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */

/**
 * Function to convert the code points to entites.
 *  
 * @param integer $u HTML entity number for Arabic character
 *                    
 * @return string Returns convert Arabic character encoding 
 *                from HTML entities to UTF-8
 */
function unichr($u) 
{
     return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
}

/**
 * A map of Arabic attached forms of characters to original characters
 */
$ligature_map = array(
  unichr(0xFE80) => unichr(0x0621), 
  unichr(0xFE81) => unichr(0x0622), unichr(0xFE82) => unichr(0x0622), 
  unichr(0xFE83) => unichr(0x0623), unichr(0xFE84) => unichr(0x0623), 
  unichr(0xFE85) => unichr(0x0624), unichr(0xFE86) => unichr(0x0624), 
  unichr(0xFE87) => unichr(0x0625), unichr(0xFE88) => unichr(0x0625), 
  unichr(0xFE89) => unichr(0x0626), unichr(0xFE8B) => unichr(0x0626), 
  unichr(0xFE8C) => unichr(0x0626), unichr(0xFE8A) => unichr(0x0626), 
  unichr(0xFE8D) => unichr(0x0627), unichr(0xFE8E) => unichr(0x0627), 
  unichr(0xFE8F) => unichr(0x0628), unichr(0xFE91) => unichr(0x0628), 
  unichr(0xFE92) => unichr(0x0628), unichr(0xFE90) => unichr(0x0628), 
  unichr(0xFE93) => unichr(0x0629), unichr(0xFE94) => unichr(0x0629), 
  unichr(0xFE95) => unichr(0x062A), unichr(0xFE97) => unichr(0x062A), 
  unichr(0xFE98) => unichr(0x062A), unichr(0xFE96) => unichr(0x062A), 
  unichr(0xFE99) => unichr(0x062B), unichr(0xFE9B) => unichr(0x062B), 
  unichr(0xFE9C) => unichr(0x062B), unichr(0xFE9A) => unichr(0x062B), 
  unichr(0xFE9D) => unichr(0x062C), unichr(0xFE9F) => unichr(0x062C), 
  unichr(0xFEA0) => unichr(0x062C), unichr(0xFE9E) => unichr(0x062C), 
  unichr(0xFEA1) => unichr(0x062D), unichr(0xFEA3) => unichr(0x062D), 
  unichr(0xFEA4) => unichr(0x062D), unichr(0xFEA2) => unichr(0x062D), 
  unichr(0xFEA5) => unichr(0x062E), unichr(0xFEA7) => unichr(0x062E), 
  unichr(0xFEA8) => unichr(0x062E), unichr(0xFEA6) => unichr(0x062E), 
  unichr(0xFEA9) => unichr(0x062F), unichr(0xFEAA) => unichr(0x062F), 
  unichr(0xFEAB) => unichr(0x0630), unichr(0xFEAC) => unichr(0x0630), 
  unichr(0xFEAD) => unichr(0x0631), unichr(0xFEAE) => unichr(0x0631), 
  unichr(0xFEAF) => unichr(0x0632), unichr(0xFEB0) => unichr(0x0632), 
  unichr(0xFEB1) => unichr(0x0633), unichr(0xFEB3) => unichr(0x0633), 
  unichr(0xFEB4) => unichr(0x0633), unichr(0xFEB2) => unichr(0x0633), 
  unichr(0xFEB5) => unichr(0x0634), unichr(0xFEB7) => unichr(0x0634), 
  unichr(0xFEB8) => unichr(0x0634), unichr(0xFEB6) => unichr(0x0634), 
  unichr(0xFEB9) => unichr(0x0635), unichr(0xFEBB) => unichr(0x0635), 
  unichr(0xFEBC) => unichr(0x0635), unichr(0xFEBA) => unichr(0x0635), 
  unichr(0xFEBD) => unichr(0x0636), unichr(0xFEBF) => unichr(0x0636), 
  unichr(0xFEC0) => unichr(0x0636), unichr(0xFEBE) => unichr(0x0636), 
  unichr(0xFEC1) => unichr(0x0637), unichr(0xFEC3) => unichr(0x0637), 
  unichr(0xFEC4) => unichr(0x0637), unichr(0xFEC2) => unichr(0x0637), 
  unichr(0xFEC5) => unichr(0x0638), unichr(0xFEC7) => unichr(0x0638), 
  unichr(0xFEC8) => unichr(0x0638), unichr(0xFEC6) => unichr(0x0638), 
  unichr(0xFEC9) => unichr(0x0639), unichr(0xFECB) => unichr(0x0639), 
  unichr(0xFECC) => unichr(0x0639), unichr(0xFECA) => unichr(0x0639), 
  unichr(0xFECD) => unichr(0x063A), unichr(0xFECF) => unichr(0x063A), 
  unichr(0xFED0) => unichr(0x063A), unichr(0xFECE) => unichr(0x063A), 
  unichr(0x0640) => unichr(0x0640), 
  unichr(0xFED1) => unichr(0x0641), unichr(0xFED3) => unichr(0x0641), 
  unichr(0xFED4) => unichr(0x0641), unichr(0xFED2) => unichr(0x0641), 
  unichr(0xFED5) => unichr(0x0642), unichr(0xFED7) => unichr(0x0642), 
  unichr(0xFED8) => unichr(0x0642), unichr(0xFED6) => unichr(0x0642), 
  unichr(0xFED9) => unichr(0x0643), unichr(0xFEDB) => unichr(0x0643), 
  unichr(0xFEDC) => unichr(0x0643), unichr(0xFEDA) => unichr(0x0643), 
  unichr(0xFEDD) => unichr(0x0644), unichr(0xFEDF) => unichr(0x0644), 
  unichr(0xFEE0) => unichr(0x0644), unichr(0xFEDE) => unichr(0x0644), 
  unichr(0xFEE1) => unichr(0x0645), unichr(0xFEE3) => unichr(0x0645), 
  unichr(0xFEE4) => unichr(0x0645), unichr(0xFEE2) => unichr(0x0645), 
  unichr(0xFEE5) => unichr(0x0646), unichr(0xFEE7) => unichr(0x0646), 
  unichr(0xFEE8) => unichr(0x0646), unichr(0xFEE6) => unichr(0x0646), 
  unichr(0xFEE9) => unichr(0x0647), unichr(0xFEEB) => unichr(0x0647), 
  unichr(0xFEEC) => unichr(0x0647), unichr(0xFEEA) => unichr(0x0647), 
  unichr(0xFEED) => unichr(0x0648), unichr(0xFEEE) => unichr(0x0648), 
  unichr(0xFEEF) => unichr(0x0649), unichr(0xFEF0) => unichr(0x0649), 
  unichr(0xFEF1) => unichr(0x064A), unichr(0xFEF3) => unichr(0x064A), 
  unichr(0xFEF4) => unichr(0x064A), unichr(0xFEF2) => unichr(0x064A) 
);


/**
 * Arabic unicode code points
 **/
$char_names = array(
    'COMMA' => unichr(0x060C),
    'SEMICOLON' => unichr(0x061B),
    'QUESTION' => unichr(0x061F),
    'HAMZA' => unichr(0x0621),
    'ALEF_MADDA' => unichr(0x0622),
    'ALEF_HAMZA_ABOVE' => unichr(0x0623),
    'WAW_HAMZA' => unichr(0x0624),
    'ALEF_HAMZA_BELOW' => unichr(0x0625),
    'YEH_HAMZA' => unichr(0x0626),
    'ALEF' => unichr(0x0627),
    'BEH' => unichr(0x0628),
    'TEH_MARBUTA' => unichr(0x0629),
    'TEH' => unichr(0x062a),
    'THEH' => unichr(0x062b),
    'JEEM' => unichr(0x062c),
    'HAH' => unichr(0x062d),
    'KHAH' => unichr(0x062e),
    'DAL' => unichr(0x062f),
    'THAL' => unichr(0x0630),
    'REH' => unichr(0x0631),
    'ZAIN' => unichr(0x0632),
    'SEEN' => unichr(0x0633),
    'SHEEN' => unichr(0x0634),
    'SAD' => unichr(0x0635),
    'DAD' => unichr(0x0636),
    'TAH' => unichr(0x0637),
    'ZAH' => unichr(0x0638),
    'AIN' => unichr(0x0639),
    'GHAIN' => unichr(0x063a),
    'TATWEEL' => unichr(0x0640),
    'FEH' => unichr(0x0641),
    'QAF' => unichr(0x0642),
    'KAF' => unichr(0x0643),
    'LAM' => unichr(0x0644),
    'MEEM' => unichr(0x0645),
    'NOON' => unichr(0x0646),
    'HEH' => unichr(0x0647),
    'WAW' => unichr(0x0648),
    'ALEF_MAKSURA' => unichr(0x0649),
    'YEH' => unichr(0x064a),
    'MADDA_ABOVE' => unichr(0x0653),
    'HAMZA_ABOVE' => unichr(0x0654),
    'HAMZA_BELOW' => unichr(0x0655),
    'ZERO' => unichr(0x0660),
    'ONE' => unichr(0x0661),
    'TWO' => unichr(0x0662),
    'THREE' => unichr(0x0663),
    'FOUR' => unichr(0x0664),
    'FIVE' => unichr(0x0665),
    'SIX' => unichr(0x0666),
    'SEVEN' => unichr(0x0667),
    'EIGHT' => unichr(0x0668),
    'NINE' => unichr(0x0669),
    'PERCENT' => unichr(0x066a),
    'DECIMAL' => unichr(0x066b),
    'THOUSANDS' => unichr(0x066c),
    'STAR' => unichr(0x066d),
    'MINI_ALEF' => unichr(0x0670),
    'ALEF_WASLA' => unichr(0x0671),
    'FULL_STOP' => unichr(0x06d4),
    'BYTE_ORDER_MARK' => unichr(0xfeff),

    //Diacritics
    'FATHATAN' => unichr(0x064B),
    'DAMMATAN' => unichr(0x064C),
    'KASRATAN' => unichr(0x064D),
    'FATHA' => unichr(0x064E),
    'DAMMA' => unichr(0x064F),
    'KASRA' => unichr(0x0650),
    'SHADDA' => unichr(0x0651),
    'SUKUN' => unichr(0x0652),

    'SMALL_ALEF' => unichr(0x0670),
    'SMALL_WAW' => unichr(0x06E5),
    'SMALL_YEH' => unichr(0x06E6),

    //Ligatures
    'LAM_ALEF' => unichr(0xFEFb),
    'LAM_ALEF_HAMZA_ABOVE' => unichr(0xFEF7),
    'LAM_ALEF_HAMZA_BELOW' => unichr(0xFEF9),
    'LAM_ALEF_MADDA_ABOVE' => unichr(0xFEF5),
    'simple_LAM_ALEF' => unichr(0x0644).unichr(0x064E).unichr(0x0627),
    'simple_LAM_ALEF_HAMZA_ABOVE' => unichr(0x0644).unichr(0x0623),
    'simple_LAM_ALEF_HAMZA_BELOW' => unichr(0x0644).unichr(0x0625),
    'simple_LAM_ALEF_MADDA_ABOVE' => unichr(0x0644).unichr(0x0621).
                                     unichr(0x064E).unichr(0x0627)
);

/**
 * Arabic char groups
 **/
$char_groups = array(
    'LETTER' => array('ALEF', 'BEH', 'TEH', 'TEH_MARBUTA', 'THEH', 'JEEM', 'HAH', 'KHAH',
                      'DAL', 'THAL', 'REH', 'ZAIN', 'SEEN', 'SHEEN', 'SAD', 'DAD', 'TAH', 'ZAH',
                      'AIN', 'GHAIN', 'FEH', 'QAF', 'KAF', 'LAM', 'MEEM', 'NOON', 'HEH', 'WAW', 'YEH',
                      'HAMZA', 'ALEF_MADDA', 'ALEF_HAMZA_ABOVE', 'WAW_HAMZA', 'ALEF_HAMZA_BELOW', 'YEH_HAMZA'),
    'TASHKEEL' => array('FATHATAN', 'DAMMATAN', 'KASRATAN', 'FATHA', 'DAMMA', 'KASRA', 'SUKUN', 'SHADDA'),
    'HARAKAT' => array('FATHATAN', 'DAMMATAN', 'KASRATAN', 'FATHA', 'DAMMA', 'KASRA', 'SUKUN'),
    'SHORTHARAKAT' => array('FATHA', 'DAMMA', 'KASRA', 'SUKUN'),
    'TANWIN' => array('FATHATAN', 'DAMMATAN', 'KASRATAN'),
    'LIGUATURES' => array('LAM_ALEF', 'LAM_ALEF_HAMZA_ABOVE', 'LAM_ALEF_HAMZA_BELOW', 'LAM_ALEF_MADDA_ABOVE'),
    'HAMZAT' => array('HAMZA', 'WAW_HAMZA', 'YEH_HAMZA', 'HAMZA_ABOVE', 'HAMZA_BELOW', 'ALEF_HAMZA_BELOW', 'ALEF_HAMZA_ABOVE'),
    'ALEFAT' => array('ALEF', 'ALEF_MADDA', 'ALEF_HAMZA_ABOVE', 'ALEF_HAMZA_BELOW', 'ALEF_WASLA', 'ALEF_MAKSURA', 'SMALL_ALEF'),
    'WEAK' => array('ALEF', 'WAW', 'YEH', 'ALEF_MAKSURA'),
    'YEHLIKE' => array('YEH', 'YEH_HAMZA', 'ALEF_MAKSURA', 'SMALL_YEH'),
    'WAWLIKE' => array('WAW', 'WAW_HAMZA', 'SMALL_WAW'),
    'TEHLIKE' => array('TEH', 'TEH_MARBUTA'),
    'SMALL' => array('SMALL_ALEF', 'SMALL_WAW', 'SMALL_YEH'),
    'MOON' => array('HAMZA', 'ALEF_MADDA', 'ALEF_HAMZA_ABOVE', 'ALEF_HAMZA_BELOW', 'ALEF', 'BEH', 'JEEM', 'HAH', 'KHAH', 'AIN', 'GHAIN', 'FEH', 'QAF', 'KAF', 'MEEM', 'HEH', 'WAW', 'YEH'),
    'SUN' => array('TEH', 'THEH', 'DAL', 'THAL', 'REH', 'ZAIN', 'SEEN', 'SHEEN', 'SAD', 'DAD', 'TAH', 'ZAH', 'LAM', 'NOON'),
);

/**
 * Arabic char names
 **/
$char_ar_names = array(
                'ALEF'        => 'ألف',
                'BEH'         => 'باء',
                'TEH'         => 'تاء',
                'TEH_MARBUTA' => 'تاء مربوطة',
                'THEH'        => 'ثاء',
                'JEEM'        => 'جيم',
                'HAH'         => 'حاء',
                'KHAH'        => 'خاء',
                'DAL'         => 'دال',
                'THAL'        => 'ذال',
                'REH'         => 'راء',
                'ZAIN'        => 'زاي',
                'SEEN'        => 'سين',
                'SHEEN'       => 'شين',
                'SAD'         => 'صاد',
                'DAD'         => 'ضاد',
                'TAH'         => 'طاء',
                'ZAH'         => 'ظاء',
                'AIN'         => 'عين',
                'GHAIN'       => 'غين',
                'FEH'         => 'فاء',
                'QAF'         => 'قاف',
                'KAF'         => 'كاف',
                'LAM'         => 'لام',
                'MEEM'        => 'ميم',
                'NOON'        => 'نون',
                'HEH'         => 'هاء',
                'WAW'         => 'واو',
                'YEH'         => 'ياء',
                'HAMZA'       => 'همزة',

                'TATWEEL'          => 'تطويل',
                'ALEF_MADDA'       => 'ألف ممدودة',
                'ALEF_MAKSURA'     => 'ألف مقصورة',
                'ALEF_HAMZA_ABOVE' => 'همزة على الألف',
                'WAW_HAMZA'        => 'همزة على الواو',
                'ALEF_HAMZA_BELOW' => 'همزة تحت الألف',
                'YEH_HAMZA'        => 'همزة على الياء',
                'FATHATAN'         => 'فتحتان',
                'DAMMATAN'         => 'ضمتان',
                'KASRATAN'         => 'كسرتان',
                'FATHA'            => 'فتحة',
                'DAMMA'            => 'ضمة',
                'KASRA'            => 'كسرة',
                'SHADDA'           => 'شدة',
                'SUKUN'            => 'سكون',
);