<?php
/**
 * ----------------------------------------------------------------------
 *  
 * Copyright (c) 2006-2016 Khaled Al-Sham'aa
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
 * Class Name: Arabic Keyboard Swapping Language
 *  
 * Filename:   KeySwap.php
 *  
 * Original    Author(s): Khaled Al-Sham'aa <khaled@ar-php.org>
 *  
 * Purpose:    Convert keyboard language programmatically (English - Arabic)
 *  
 * ----------------------------------------------------------------------
 *  
 * Arabic Keyboard Swapping Language
 *
 * PHP class to convert keyboard language between English and Arabic
 * programmatically. This function can be helpful in dual language forms when
 * users miss change keyboard language while they are entering data.
 * 
 * If you wrote an Arabic sentence while your keyboard stays in English mode by 
 * mistake, you will get a non-sense English text on your PC screen. In that case 
 * you can use this class to make a kind of magic conversion to swap that odd text 
 * by original Arabic sentence you meant when you type on your keyboard.
 * 
 * Please note that magic conversion in the opposite direction (if you type English 
 * sentences while your keyboard stays in Arabic mode) is also available in this 
 * class, but it is not reliable as much as previous case because in Arabic keyboard 
 * we have some keys provide a short-cut to type two chars in one click (those keys 
 * include: b, B, G and T).
 * 
 * Well, we try in this class to come over this issue by suppose that user used 
 * optimum way by using short-cut keys when available instead of assemble chars  
 * using stand alone keys, but if (s)he does not then you may have some typo chars 
 * in converted text.
 * 
 * Example:
 * <code>
 *     include('./I18N/Arabic.php');
 *     $obj = new I18N_Arabic('KeySwap');
 * 
 *     $str = "Hpf lk hgkhs hglj'vtdkK Hpf hg`dk dldg,k f;gdjil Ygn ,p]hkdm ...";
 * 
 *     echo "<p><u><i>Before:</i></u><br />$str<br /><br />";
 *     
 *     $text = $obj->swapEa($str);
 *        
 *     echo "<u><i>After:</i></u><br />$text<br /><br />";    
 * </code>                  
 *
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */

/**
 * This PHP class convert keyboard language programmatically (English - Arabic)
 *  
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */ 
class I18N_Arabic_KeySwap
{
    private $_transliteration = array();
    private $_arLogodd;
    private $_enLogodd;
    
    private $_arKeyboard;
    private $_enKeyboard;
    private $_frKeyboard;
    
    /**
     * Loads initialize values
     *
     * @ignore
     */         
    public function __construct()
    {
        $xml = simplexml_load_file(dirname(__FILE__).'/data/charset/arabizi.xml');
        
        foreach ($xml->transliteration->item as $item) {
            $index = $item['id'];
            $this->_transliteration["$index"] = (string)$item;
        } 

        $xml = simplexml_load_file(dirname(__FILE__).'/data/ArKeySwap.xml');
        
        foreach ($xml->arabic->key as $key) {
            $index = (int)$key['id'];
            $this->_arKeyboard[$index] = (string)$key;
        } 
        
        foreach ($xml->english->key as $key) {
            $index = (int)$key['id'];
            $this->_enKeyboard[$index] = (string)$key;
        } 
        
        foreach ($xml->french->key as $key) {
            $index = (int)$key['id'];
            $this->_frKeyboard[$index] = (string)$key;
        } 

        $this->_arLogodd = file(dirname(__FILE__).'/data/ar-logodd.php');
        $this->_enLogodd = file(dirname(__FILE__).'/data/en-logodd.php');
    }
    
    /**
     * Make conversion to swap that odd Arabic text by original English sentence 
     * you meant when you type on your keyboard (if keyboard language was  
     * incorrect)
     *          
     * @param string $text Odd Arabic string
     *                    
     * @return string Normal English string
     * @author Khaled Al-Sham'aa
     */
    public function swapAe($text)
    {
        $output = $this->_swapCore($text, 'ar', 'en');
        
        return $output;
    }
    
    /**
     * Make conversion to swap that odd English text by original Arabic sentence 
     * you meant when you type on your keyboard (if keyboard language was  
     * incorrect)
     *           
     * @param string $text Odd English string
     *                    
     * @return string Normal Arabic string
     * @author Khaled Al-Sham'aa
     */
    public function swapEa($text)
    {
        $output = $this->_swapCore($text, 'en', 'ar');
        
        return $output;
    }
    
    /**
     * Make conversion to swap that odd Arabic text by original French sentence 
     * you meant when you type on your keyboard (if keyboard language was  
     * incorrect)
     *          
     * @param string $text Odd Arabic string
     *                    
     * @return string Normal French string
     * @author Khaled Al-Sham'aa
     */
    public function swapAf($text)
    {
        $output = $this->_swapCore($text, 'ar', 'fr');
        
        return $output;
    }
    
    /**
     * Make conversion to swap that odd French text by original Arabic sentence 
     * you meant when you type on your keyboard (if keyboard language was  
     * incorrect)
     *           
     * @param string $text Odd French string
     *                    
     * @return string Normal Arabic string
     * @author Khaled Al-Sham'aa
     */
    public function swapFa($text)
    {
        $output = $this->_swapCore($text, 'fr', 'ar');
        
        return $output;
    }
    
    /**
     * Make conversion between different keyboard maps to swap odd text in
     * one language by original meaningful text in another language that 
     * you meant when you type on your keyboard (if keyboard language was  
     * incorrect)
     *           
     * @param string $text Odd string
     * @param string $in   Input language [ar|en|fr]
     * @param string $out  Output language [ar|en|fr]
     *                    
     * @return string Normal string
     * @author Khaled Al-Sham'aa
     */
    private function _swapCore($text, $in, $out)
    {
        $output = '';
        $text   = stripslashes($text);
        $max    = mb_strlen($text);
        
        switch ($in) {
        case 'ar':
            $inputMap = $this->_arKeyboard;
            break;
        case 'en':
            $inputMap = $this->_enKeyboard;
            break;
        case 'fr':
            $inputMap = $this->_frKeyboard;
            break;
        }
        
        switch ($out) {
        case 'ar':
            $outputMap = $this->_arKeyboard;
            break;
        case 'en':
            $outputMap = $this->_enKeyboard;
            break;
        case 'fr':
            $outputMap = $this->_frKeyboard;
            break;
        }
        
        for ($i=0; $i<$max; $i++) {
            $chr = mb_substr($text, $i, 1);
            $key = array_search($chr, $inputMap);
            
            if ($key === false) {
                $output .= $chr;
            } else {
                $output .= $outputMap[$key];
            }
        }
        
        return $output;
    }
    
    /**
     * Calculate the log odd probability that inserted string from keyboard
     * is in English language
     *           
     * @param string $str Inserted string from the keyboard
     *                    
     * @return float Calculated score for input string as English language
     * @author Khaled Al-Sham'aa
     */
    protected function checkEn($str) 
    {
        $lines  = $this->_enLogodd;
        $logodd = array();
        
        $line   = array_shift($lines);
        $line   = rtrim($line);
        $second = preg_split("/\t/", $line);
        $temp   = array_shift($second);
        
        foreach ($lines as $line) {
            $line   = rtrim($line);
            $values = preg_split("/\t/", $line);
            $first  = array_shift($values);
            
            for ($i=0; $i<28; $i++) {
                $logodd["$first"]["{$second[$i]}"] = $values[$i];
            }
        }
        
        $str  = mb_strtolower($str);
        $max  = mb_strlen($str, 'UTF-8');
        $rank = 0;
        
        for ($i=1; $i<$max; $i++) {
            $first  = mb_substr($str, $i-1, 1, 'UTF-8');
            $second = mb_substr($str, $i, 1, 'UTF-8');
     
            if (isset($logodd["$first"]["$second"])) {
                $rank += $logodd["$first"]["$second"]; 
            } else {
                $rank -= 10;
            }
        }
        
        return $rank;
    }

    /**
     * Calculate the log odd probability that inserted string from keyboard
     * is in Arabic language
     *           
     * @param string $str Inserted string from the keyboard
     *                    
     * @return float Calculated score for input string as Arabic language
     * @author Khaled Al-Sham'aa
     */
    protected function checkAr($str) 
    {
        $lines  = $this->_arLogodd;
        $logodd = array();
        
        $line   = array_shift($lines);
        $line   = rtrim($line);
        $second = preg_split("/\t/", $line);
        $temp   = array_shift($second);
        
        foreach ($lines as $line) {
            $line   = rtrim($line);
            $values = preg_split("/\t/", $line);
            $first  = array_shift($values);
            
            for ($i=0; $i<37; $i++) {
                $logodd["$first"]["{$second[$i]}"] = $values[$i];
            }
        }
        
        $max  = mb_strlen($str, 'UTF-8');
        $rank = 0;
        
        for ($i=1; $i<$max; $i++) {
            $first  = mb_substr($str, $i-1, 1, 'UTF-8');
            $second = mb_substr($str, $i, 1, 'UTF-8');
     
            if (isset($logodd["$first"]["$second"])) {
                $rank += $logodd["$first"]["$second"]; 
            } else {
                $rank -= 10;
            }
        }

        return $rank;
    }
    
    /**
     * This method will automatically detect the language of content supplied 
     * in the input string. It will return the suggestion of correct inserted text. 
     * The accuracy of the automatic language detection increases with the amount 
     * of text entered.
     *           
     * @param string $str Inserted string from the keyboard
     *                    
     * @return string Fixed string language and letter case to the better guess
     * @author Khaled Al-Sham'aa
     */
    public function fixKeyboardLang($str) 
    {
        preg_match_all("/([\x{0600}-\x{06FF}])/u", $str, $matches);

        $arNum    = count($matches[0]);
        $nonArNum = mb_strlen(str_replace(' ', '', $str), 'UTF-8') - $arNum;

        $capital = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $small   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ($arNum > $nonArNum) {
            $arStr = $str;
            $enStr = $this->swapAe($str);
            $isAr  = true;
        } else {            
            $arStr = $this->swapEa($str);
            $enStr = $str;

            $strCaps   = strtr($str, $capital, $small);
            $arStrCaps = $this->swapEa($strCaps);

            $isAr = false;
        }

        $enRank = $this->checkEn($enStr);
        $arRank = $this->checkAr($arStr);
        
        if ($arNum > $nonArNum) {
            $arCapsRank = $arRank;
        } else {
            $arCapsRank = $this->checkAr($arStrCaps);
        }

        if ($enRank > $arRank && $enRank > $arCapsRank) {
            if ($isAr) {
                $fix = $enStr;
            } else {
                preg_match_all("/([A-Z])/u", $enStr, $matches);
                $capsNum = count($matches[0]);
                
                preg_match_all("/([a-z])/u", $enStr, $matches);
                $nonCapsNum = count($matches[0]);
                
                if ($capsNum > $nonCapsNum && $nonCapsNum > 0) {
                    $enCapsStr = strtr($enStr, $capital, $small);
                    $fix       = $enCapsStr;
                } else {
                    $fix = '';
                }
            }
        } else {
            if ($arCapsRank > $arRank) {
                $arStr  = $arStrCaps;
                $arRank = $arCapsRank;
            }
            
            if (!$isAr) {
                $fix = $arStr;
            } else {
                $fix = '';
            }
        }

        return $fix;
    }
}
