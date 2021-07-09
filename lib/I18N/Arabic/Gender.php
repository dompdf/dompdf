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
 * Class Name: Arabic Gender Guesser
 *  
 * Filename:   Gender.php
 *  
 * Original    Author(s): Khaled Al-Sham'aa <khaled@ar-php.org>
 *  
 * Purpose:    This class attempts to guess the gender of Arabic names
 *  
 * ----------------------------------------------------------------------
 *
 * Arabic Gender Guesser
 *
 * This PHP class attempts to guess the gender of Arabic names.
 * 
 * Arabic nouns are either masculine or feminine. Usually when referring to a male, 
 * a masculine noun is usually used and when referring to a female, a feminine noun 
 * is used. In most cases the feminine noun is formed by adding a special characters 
 * to the end of the masculine noun. Its not just nouns referring to people that 
 * have gender. Inanimate objects (doors, houses, cars, etc.) is either masculine or 
 * feminine. Whether an inanimate noun is masculine or feminine is mostly 
 * arbitrary.      
 * 
 * Example:
 * <code>
 *   include('./I18N/Arabic.php');
 *   $obj = new I18N_Arabic('Gender');
 *      
 *   echo "$name ";
 * 
 *   if ($obj->isFemale($name) == true) { 
 *      echo '(Female)';
 *   }else{
 *      echo '(Male)';
 *   }    
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
 * This PHP class attempts to guess the gender of Arabic names
 *  
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */ 
class I18N_Arabic_Gender
{
    /**
     * Loads initialize values
     *
     * @ignore
     */         
    public function __construct()
    {
    }

    /**
     * Check if Arabic word is feminine
     *          
     * @param string $str Arabic word you would like to check if it is 
     *                    feminine
     *                    
     * @return boolean Return true if input Arabic word is feminine
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public static function isFemale($str)
    {
        $female = false;
        
        $words = explode(' ', $str);
        $str   = $words[0];

        $str = str_replace(array('أ','إ','آ'), 'ا', $str);

        $last       = mb_substr($str, -1, 1, 'UTF-8');
        $beforeLast = mb_substr($str, -2, 1, 'UTF-8');

        if ($last == 'ة' || $last == 'ه' || $last == 'ى' || $last == 'ا' 
            || ($last == 'ء' && $beforeLast == 'ا')
        ) {

            $female = true;
        } elseif (preg_match("/^[اإ].{2}ا.$/u", $str) 
            || preg_match("/^[إا].ت.ا.+$/u", $str)
        ) {
            // الأسماء على وزن إفتعال و إفعال
            $female = true;
        } else {
            // List of the most common irregular Arabic female names
            $names = file(dirname(__FILE__).'/data/female.txt');
            $names = array_map('trim', $names);

            if (array_search($str, $names) > 0) {
                $female = true;
            }
        }

        return $female;
    }
}
