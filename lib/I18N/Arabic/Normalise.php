<?php
/**
 * ----------------------------------------------------------------------
 *  
 * Copyright (c) 2006-2016 Khaled Al-Sham'aa.
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
 * Filename:   Normalise.php
 *  
 * Original    Author(s): Khaled Al-Sham'aa <khaled@ar-php.org>
 *  
 * Purpose:   Text normalisation through various stages. Also: unshaping. 
 *  
 * ----------------------------------------------------------------------
 *  
 *  This class provides various functions to manipulate arabic text and
 *  normalise it by applying filters, for example, to strip tatweel and
 *  tashkeel, to normalise hamza and lamalephs, and to unshape
 *  a joined Arabic text back into its normalised form.
 *
 *  There is also a function to reverse a utf8 string.
 *
 *  The functions are helpful for searching, indexing and similar 
 *  functions.
 *
 * Note that this class can only deal with UTF8 strings. You can use functions
 * from the other classes to convert between encodings if necessary.
 *
 * Example:
 * <code>
 *     include('./I18N/Arabic.php');
 *     $obj = new I18N_Arabic('Normalise');
 * 
 *     $str = "Arabic text with tatweel, tashkeel...";
 * 
 *     echo "<p><u><i>Before:</i></u><br />$str<br /><br />";
 *     
 *     $text = $obj->stripTatweel($str);
 *        
 *     echo "<u><i>After:</i></u><br />$text<br /><br />";    
 * </code>                  
 *
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Djihed Afifi <djihed@gmail.com>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */

/**
 *  This class provides various functions to manipulate arabic text and
 *  normalise it by applying filters, for example, to strip tatweel and
 *  tashkeel, to normalise hamza and lamalephs, and to unshape
 *  a joined Arabic text back into its normalised form.
 *
 *  The functions are helpful for searching, indexing and similar 
 *  functions.
 *  
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Djihed Afifi <djihed@gmail.com>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */ 
class I18N_Arabic_Normalise
{
    private $_unshapeMap    = array();
    private $_unshapeKeys   = array();
    private $_unshapeValues = array();
    private $_chars         = array();
    private $_charGroups    = array();
    private $_charArNames   = array();

     /**
      * Load the Unicode constants that will be used ibn substitutions
      * and normalisations.
      *
      * @ignore
      */
    public function __construct() 
    {
        include dirname(__FILE__) . '/data/charset/ArUnicode.constants.php';

        $this->_unshapeMap    = $ligature_map;
        $this->_unshapeKeys   = array_keys($this->_unshapeMap);
        $this->_unshapeValues = array_values($this->_unshapeMap);
        $this->_chars         = $char_names;
        $this->_charGroups    = $char_groups;
        $this->_charArNames   = $char_ar_names;
    }    

    /**
     * Strip all tatweel characters from an Arabic text.
     * 
     * @param string $text The text to be stripped.
     *      
     * @return string the stripped text.
     * @author Djihed Afifi <djihed@gmail.com>
     */ 
    public function stripTatweel($text) 
    {
        return str_replace($this->_chars['TATWEEL'], '', $text); 
    }

    /**
     * Strip all tashkeel characters from an Arabic text.
     * 
     * @param string $text The text to be stripped.
     *      
     * @return string the stripped text.
     * @author Djihed Afifi <djihed@gmail.com>
     */ 
    public function stripTashkeel($text) 
    {
        $tashkeel = array(
             $this->_chars['FATHATAN'], 
             $this->_chars['DAMMATAN'], 
             $this->_chars['KASRATAN'], 
             $this->_chars['FATHA'], 
             $this->_chars['DAMMA'], 
             $this->_chars['KASRA'],
             $this->_chars['SUKUN'],
             $this->_chars['SHADDA']
        );
        return str_replace($tashkeel, "", $text);
    }

    /**
     * Normalise all Hamza characters to their corresponding aleph 
     * character in an Arabic text.
     *
     * @param string $text The text to be normalised.
     *      
     * @return string the normalised text.
     * @author Djihed Afifi <djihed@gmail.com>
     */ 
    public function normaliseHamza($text) 
    {
        $replace = array(
             $this->_chars['WAW_HAMZA'] = $this->_chars['WAW'],
             $this->_chars['YEH_HAMZA'] = $this->_chars['YEH'],
        );
        $alephs = array(
             $this->_chars['ALEF_MADDA'],
             $this->_chars['ALEF_HAMZA_ABOVE'],
             $this->_chars['ALEF_HAMZA_BELOW'],
             $this->_chars['HAMZA_ABOVE,HAMZA_BELOW']
        );

        $text = str_replace(array_keys($replace), array_values($replace), $text);
        $text = str_replace($alephs, $this->_chars['ALEF'], $text);
        return $text;
    }

    /**
     * Unicode uses some special characters where the lamaleph and any
     * hamza above them are combined into one code point. Some input
     * system use them. This function expands these characters.
     *
     * @param string $text The text to be normalised.
     *      
     * @return string the normalised text.
     * @author Djihed Afifi <djihed@gmail.com>
     */ 
    public function normaliseLamaleph ($text) 
    {
        $text = str_replace(
            $this->_chars['LAM_ALEPH'], 
            $simple_LAM_ALEPH, 
            $text
        );
        $text = str_replace(
            $this->_chars['LAM_ALEPH_HAMZA_ABOVE'], 
            $simple_LAM_ALEPH_HAMZA_ABOVE, 
            $text
        );
        $text = str_replace(
            $this->_chars['LAM_ALEPH_HAMZA_BELOW'], 
            $simple_LAM_ALEPH_HAMZA_BELOW, 
            $text
        );
        $text = str_replace(
            $this->_chars['LAM_ALEPH_MADDA_ABOVE'], 
            $simple_LAM_ALEPH_MADDA_ABOVE, 
            $text
        );
        return $text;
    }

    /**
     * Return unicode char by its code point.
     *
     * @param char $u code point
     *      
     * @return string the result character.
     * @author Djihed Afifi <djihed@gmail.com>
     */
    public function unichr($u) 
    {
        return mb_convert_encoding('&#'.intval($u).';', 'UTF-8', 'HTML-ENTITIES');
    }

    /**
     * Takes a string, it applies the various filters in this class
     * to return a unicode normalised string suitable for activities
     * such as searching, indexing, etc.
     *
     * @param string $text the text to be normalised.
     *      
     * @return string the result normalised string.
     * @author Djihed Afifi <djihed@gmail.com>
     */ 
    public function normalise($text)
    {
        $text = $this->stripTashkeel($text);
        $text = $this->stripTatweel($text);
        $text = $this->normaliseHamza($text);
        $text = $this->normaliseLamaleph($text);

        return $text;
    } 

    /**
     * Takes Arabic text in its joined form, it untangles the characters
     * and  unshapes them.
     *
     * This can be used to process text that was processed through OCR
     * or by extracting text from a PDF document.
     *
     * Note that the result text may need further processing. In most
     * cases, you will want to use the utf8Strrev function from
     * this class to reverse the string.
     *  
     * Most of the work of setting up the characters for this function
     * is done through the ArUnicode.constants.php constants and 
     * the constructor loading.
     *
     * @param string $text the text to be unshaped.
     *      
     * @return string the result normalised string.
     * @author Djihed Afifi <djihed@gmail.com>
     */
    public function unshape($text)
    {
          return str_replace($this->_unshapeKeys, $this->_unshapeValues, $text);
    }

    /**
     * Take a UTF8 string and reverse it.
     *
     * @param string  $str             the string to be reversed.
     * @param boolean $reverse_numbers whether to reverse numbers.
     *      
     * @return string The reversed string.
     */
    public function utf8Strrev($str, $reverse_numbers = false) 
    {
        preg_match_all('/./us', $str, $ar);
        if ($reverse_numbers) {
            return join('', array_reverse($ar[0]));
        } else {
            $temp = array();
            foreach ($ar[0] as $value) {
                if (is_numeric($value) && !empty($temp[0]) && is_numeric($temp[0])) {
                    foreach ($temp as $key => $value2) {
                        if (is_numeric($value2)) {
                            $pos = ($key + 1);
                        } else {
                            break;
                        }
                    }
                    $temp2 = array_splice($temp, $pos);
                    $temp  = array_merge($temp, array($value), $temp2);
                } else {
                    array_unshift($temp, $value);
                }
            }
            return implode('', $temp);
        }
    }
    
    /**
     * Checks for Arabic Tashkeel marks (i.e. FATHA, DAMMA, KASRA, SUKUN, 
     * SHADDA, FATHATAN, DAMMATAN, KASRATAN).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Tashkeel mark
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isTashkeel($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['TASHKEEL'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Harakat marks (i.e. FATHA, DAMMA, KASRA, SUKUN, TANWIN).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Harakat mark
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isHaraka($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['HARAKAT'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic short Harakat marks (i.e. FATHA, DAMMA, KASRA, SUKUN).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic short Harakat mark
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isShortharaka($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['SHORTHARAKAT'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Tanwin marks (i.e. FATHATAN, DAMMATAN, KASRATAN).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Tanwin mark
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isTanwin($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['TANWIN'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Ligatures like LamAlef (i.e. LAM ALEF, LAM ALEF HAMZA 
     * ABOVE, LAM ALEF HAMZA BELOW, LAM ALEF MADDA ABOVE).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Ligatures like LamAlef
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isLigature($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['LIGUATURES'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Hamza forms (i.e. HAMZA, WAW HAMZA, YEH HAMZA, HAMZA ABOVE, 
     * HAMZA BELOW, ALEF HAMZA BELOW, ALEF HAMZA ABOVE).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Hamza form
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isHamza($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['HAMZAT'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Alef forms (i.e. ALEF, ALEF MADDA, ALEF HAMZA ABOVE, 
     * ALEF HAMZA BELOW,ALEF WASLA, ALEF MAKSURA).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Alef form
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isAlef($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['ALEFAT'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Weak letters (i.e. ALEF, WAW, YEH, ALEF_MAKSURA).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Weak letter
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isWeak($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['WEAK'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Yeh forms (i.e. YEH, YEH HAMZA, SMALL YEH, ALEF MAKSURA).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Yeh form
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isYehlike($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['YEHLIKE'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Waw like forms (i.e. WAW, WAW HAMZA, SMALL WAW).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Waw like form
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isWawlike($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['WAWLIKE'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Teh forms (i.e. TEH, TEH MARBUTA).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Teh form
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isTehlike($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['TEHLIKE'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Small letters (i.e. SMALL ALEF, SMALL WAW, SMALL YEH).
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Small letter
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isSmall($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['SMALL'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Moon letters.
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Moon letter
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isMoon($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['MOON'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Checks for Arabic Sun letters.
     *
     * @param string $archar Arabic unicode char
     *      
     * @return boolean True if it is Arabic Sun letter
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function isSun($archar)
    {
        $key = array_search($archar, $this->_chars);

        if (in_array($key, $this->_charGroups['SUN'])) {
            $value = true;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * Return Arabic letter name in arabic.
     *
     * @param string $archar Arabic unicode char
     *      
     * @return string Arabic letter name in arabic
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function charName($archar)
    {
        $key = array_search($archar, $this->_chars);

        $name = $this->_charArNames["$key"];
        
        return $name;
    }
}

