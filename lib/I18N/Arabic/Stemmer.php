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
 * Class Name: Arabic Text ArStemmer Class
 *  
 * Filename: Stemmer.php
 *  
 * Original  Author(s): Khaled Al-Sham'aa <khaled@ar-php.org>
 *  
 * Purpose:  Get stem of an Arabic word
 *  
 * ----------------------------------------------------------------------
 *  
 * Source: http://arabtechies.net/node/83
 * By: Taha Zerrouki <taha.zerrouki@gmail.com>
 *  
 * ----------------------------------------------------------------------
 *  
 * Arabic Word Stemmer Class
 *
 * PHP class to get stem of an Arabic word
 *
 * A stemmer is an automatic process in which morphological variants of terms 
 * are mapped to a single representative string called a stem. Arabic belongs 
 * to the Semitic family of languages which also includes Hebrew and Aramaic. 
 * Since morphological change in Arabic results from the addition of prefixes 
 * and infixes as well as suffixes, simple removal of suffixes is not as 
 * effective for Arabic as it is for English.
 * 
 * Arabic has much richer morphology than English. Arabic has two genders, 
 * feminine and masculine; three numbers, singular, dual, and plural; and three 
 * grammatical cases, nominative, genitive, and accusative. A noun has the 
 * nominative case when it is a subject; accusative when it is the object of a 
 * verb; and genitive when it is the object of a preposition. The form of an 
 * Arabic noun is determined by its gender, number, and grammatical case. The 
 * definitive nouns are formed by attaching the Arabic article "AL" to the 
 * immediate front of the nouns. Besides prefixes, a noun can also carry a 
 * suffix which is often a possessive pronoun. In Arabic, the conjunction word
 * "WA" (and) is often attached to the following word.
 *  
 * Like nouns, an Arabic adjective can also have many variants. When an 
 * adjective modifies a noun in a noun phrase, the adjective agrees with the 
 * noun in gender, number, case, and definiteness. Arabic verbs have two tenses: 
 * perfect and imperfect. Perfect tense denotes actions completed, while 
 * imperfect denotes uncompleted actions. The imperfect tense has four mood: 
 * indicative, subjective, jussive, and imperative. Arabic verbs in perfect 
 * tense consist of a stem and a subject marker. The subject marker indicates 
 * the person, gender, and number of the subject. The form of a verb in perfect 
 * tense can have subject marker and pronoun suffix. The form of a 
 * subject-marker is determined together by the person, gender, and number of 
 * the subject.
 * Example:
 * <code>
 *     include('./I18N/Arabic.php');
 *     $obj = new I18N_Arabic('Stemmer');
 * 
 *     echo $obj->stem($word);
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
 * This PHP class get stem of an Arabic word
 *  
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */ 
class I18N_Arabic_Stemmer
{
    private static $_verbPre  = 'وأسفلي';
    private static $_verbPost = 'ومكانيه';
    private static $_verbMay;

    private static $_verbMaxPre  = 4;
    private static $_verbMaxPost = 6;
    private static $_verbMinStem = 2;

    private static $_nounPre  = 'ابفكلوأ';
    private static $_nounPost = 'اتةكمنهوي';
    private static $_nounMay;

    private static $_nounMaxPre  = 4;
    private static $_nounMaxPost = 6;
    private static $_nounMinStem = 2;
    
    /**
     * Loads initialize values
     *
     * @ignore
     */         
    public function __construct()
    {
        self::$_verbMay = self::$_verbPre . self::$_verbPost;
        self::$_nounMay = self::$_nounPre . self::$_nounPost;
    }
    
    /**
     * Get rough stem of the given Arabic word 
     *      
     * @param string $word Arabic word you would like to get its stem
     *                    
     * @return string Arabic stem of the word
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public static function stem($word)
    {
        $nounStem = self::roughStem(
            $word, self::$_nounMay, self::$_nounPre, self::$_nounPost, 
            self::$_nounMaxPre, self::$_nounMaxPost, self::$_nounMinStem
        );
        $verbStem = self::roughStem(
            $word, self::$_verbMay, self::$_verbPre, self::$_verbPost, 
            self::$_verbMaxPre, self::$_verbMaxPost, self::$_verbMinStem
        );
        
        if (mb_strlen($nounStem, 'UTF-8') < mb_strlen($verbStem, 'UTF-8')) {
            $stem = $nounStem;
        } else {
            $stem = $verbStem;
        }
        
        return $stem;
    }
    
    /**
     * Get rough stem of the given Arabic word (under specific rules)
     *      
     * @param string  $word      Arabic word you would like to get its stem
     * @param string  $notChars  Arabic chars those can't be in postfix or prefix
     * @param string  $preChars  Arabic chars those may exists in the prefix
     * @param string  $postChars Arabic chars those may exists in the postfix
     * @param integer $maxPre    Max prefix length
     * @param integer $maxPost   Max postfix length
     * @param integer $minStem   Min stem length
     *
     * @return string Arabic stem of the word under giving rules
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected static function roughStem (
        $word, $notChars, $preChars, $postChars, $maxPre, $maxPost, $minStem
    ) {
        $right = -1;
        $left  = -1;
        $max   = mb_strlen($word, 'UTF-8');
        
        for ($i=0; $i < $max; $i++) {
            $needle = mb_substr($word, $i, 1, 'UTF-8');
            if (mb_strpos($notChars, $needle, 0, 'UTF-8') === false) {
                if ($right == -1) {
                    $right = $i;
                }
                $left = $i;
            }
        }
        
        if ($right > $maxPre) {
            $right = $maxPre;
        }
        
        if ($max - $left - 1 > $maxPost) {
            $left = $max - $maxPost -1;
        }
        
        for ($i=0; $i < $right; $i++) {
            $needle = mb_substr($word, $i, 1, 'UTF-8');
            if (mb_strpos($preChars, $needle, 0, 'UTF-8') === false) {
                $right = $i;
                break;
            }
        }
        
        for ($i=$max-1; $i>$left; $i--) {
            $needle = mb_substr($word, $i, 1, 'UTF-8');
            if (mb_strpos($postChars, $needle, 0, 'UTF-8') === false) {
                $left = $i;
                break;
            }
        }

        if ($left - $right >= $minStem) {
            $stem = mb_substr($word, $right, $left-$right+1, 'UTF-8');
        } else {
            $stem = null;
        }

        return $stem;
    }
}
