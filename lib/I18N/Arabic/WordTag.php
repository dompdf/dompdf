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
 * Class Name: Tagging Arabic Word Class
 *  
 * Filename: WordTag.php
 *  
 * Original  Author(s): Khaled Al-Sham'aa <khaled@ar-php.org>
 *  
 * Purpose:  Arabic grammarians describe Arabic as being derived from
 *           three main categories: noun, verb and particle. This class
 *           built to recognize the class of a given Arabic word.
 *            
 * ----------------------------------------------------------------------
 *  
 * Tagging Arabic Word
 *
 * This PHP Class can identifying names, places, dates, and other noun
 * words and phrases in Arabic language that establish the meaning of a body
 * of text.
 * 
 * This process of identifying names, places, dates, and other noun words and 
 * phrases that establish the meaning of a body of text-is critical to software 
 * systems that process large amounts of unstructured data coming from sources such 
 * as email, document files, and the Web.
 * 
 * Arabic words are classifies into three main classes, namely, verb, noun and 
 * particle. Verbs are sub classified into three subclasses (Past verbs, Present 
 * Verbs, etc.); nouns into forty six subclasses (e.g. Active participle, Passive 
 * participle, Exaggeration pattern, Adjectival noun, Adverbial noun, Infinitive 
 * noun, Common noun, Pronoun, Quantifier, etc.) and particles into twenty three 
 * subclasses (e.g. additional, resumption, Indefinite, Conditional, Conformational, 
 * Prohibition, Imperative, Optative, Reasonal, Dubious, etc.), and from these three 
 * main classes that the rest of the language is derived.
 * 
 * The most important aspect of this system of describing Arabic is that all the 
 * subclasses of these three main classes inherit properties from the parent 
 * classes.
 * 
 * Arabic is very rich in categorising words, and contains classes for almost every 
 * form of word imaginable. For example, there are classes for nouns of instruments, 
 * nouns of place and time, nouns of activity and so on. If we tried to use all the 
 * subclasses described by Arabic grammarians, the size of the tagset would soon 
 * reach more than two or three hundred tags. For this reason, we have chosen only 
 * the main classes. But because of the way all the classes inherit from others, it 
 * would be quite simple to extend this tagset to include more subclasses.      
 *
 * Example:
 * <code>
 *     include('./I18N/Arabic.php');
 *     $obj = new I18N_Arabic('WordTag');
 * 
 *     $hStr=$obj->highlightText($str,'#80B020');
 * 
 *     echo $str . '<hr />' . $hStr . '<hr />';
 *     
 *     $taggedText = $obj->tagText($str);
 * 
 *     foreach($taggedText as $wordTag) {
 *         list($word, $tag) = $wordTag;
 *     
 *         if ($tag == 1) {
 *             echo "<font color=#DBEC21>$word is Noun</font>, ";
 *         }
 *     
 *         if ($tag == 0) {
 *             echo "$word is not Noun, ";
 *         }
 *     }    
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
 * This PHP class to tagging Arabic Word
 *  
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */ 
class I18N_Arabic_WordTag
{
    private static $_particlePreNouns = array('عن', 'في', 'مذ', 'منذ',
                                              'من', 'الى', 'على', 'حتى',
                                              'الا', 'غير', 'سوى', 'خلا',
                                              'عدا', 'حاشا', 'ليس');

    private static $_normalizeAlef       = array('أ','إ','آ');
    private static $_normalizeDiacritics = array('َ','ً','ُ','ٌ',
                                                 'ِ','ٍ','ْ','ّ');

    private $_commonWords = array();

    /**
     * Loads initialize values
     *
     * @ignore
     */         
    public function __construct()
    {
        $words = file(dirname(__FILE__).'/data/ar-stopwords.txt');
        $words = array_map('trim', $words);
        
        $this->_commonWords = $words;
    }
    
    /**
     * Check if given rabic word is noun or not
     *      
     * @param string $word       Word you want to check if it is 
     *                           noun (utf-8)
     * @param string $word_befor The word before word you want to check
     *                    
     * @return boolean TRUE if given word is Arabic noun
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public static function isNoun($word, $word_befor)
    {
        $word       = trim($word);
        $word_befor = trim($word_befor);

        $word       = str_replace(self::$_normalizeAlef, 'ا', $word);
        $word_befor = str_replace(self::$_normalizeAlef, 'ا', $word_befor);
        $wordLen    = strlen($word);
        
        // إذا سبق بحرف جر فهو اسم مجرور
        if (in_array($word_befor, self::$_particlePreNouns)) {
            return true;
        }
        
        // إذا سبق بعدد فهو معدود
        if (is_numeric($word) || is_numeric($word_befor)) {
            return true;
        }
        
        // إذا كان منون
        if (mb_substr($word, -1, 1) == 'ً' || mb_substr($word, -1, 1) == 'ٌ' 
            || mb_substr($word, -1, 1) == 'ٍ'
        ) {
            return true;
        }
        
        $word    = str_replace(self::$_normalizeDiacritics, '', $word);
        $wordLen = mb_strlen($word);
        
        // إن كان معرف بأل التعريف
        if (mb_substr($word, 0, 1) == 'ا' && mb_substr($word, 1, 1) == 'ل' 
            && $wordLen >= 5
        ) {
            return true;
        }
        
        // إذا كان في الكلمة  ثلاث ألفات
        // إن لم تكن الألف الثالثة متطرفة
        if (mb_substr_count($word, 'ا') >= 3) {
            return true;
        }

        //إن كان مؤنث تأنيث لفظي، منتهي بتاء مربوطة
        // أو همزة أو ألف مقصورة
        if ((mb_substr($word, -1, 1) == 'ة' || mb_substr($word, -1, 1) == 'ء' 
            || mb_substr($word, -1, 1) == 'ى') && $wordLen >= 4
        ) {
            return true;
        }

        // مؤنث تأنيث لفظي،
        // منتهي بألف وتاء مفتوحة - جمع مؤنث سالم
        if (mb_substr($word, -1, 1) == 'ت' && mb_substr($word, -2, 1) == 'ا' 
            && $wordLen >= 5
        ) {
            return true;
        }

        // started by Noon, before REH or LAM, or Noon, is a verb and not a noun
        if (mb_substr($word, 0, 1) == 'ن' && (mb_substr($word, 1, 1) == 'ر' 
            || mb_substr($word, 1, 1) == 'ل' || mb_substr($word, 1, 1) == 'ن') 
            && $wordLen > 3
        ) {
            return false;
        }
        
        // started by YEH, before some letters is a verb and not a noun
        // YEH,THAL,JEEM,HAH,KHAH,ZAIN,SHEEN,SAD,DAD,TAH,ZAH,GHAIN,KAF
        $haystack = 'يذجهخزشصضطظغك';
        if (mb_substr($word, 0, 1) == 'ي' 
            && (mb_strpos($haystack, mb_substr($word, 1, 1)) !== false) 
            && $wordLen > 3
        ) {
            return false;
        }
        
        // started by beh or meem, before BEH,FEH,MEEM is a noun and not a verb
        if ((mb_substr($word, 0, 1) == 'ب' || mb_substr($word, 0, 1) == 'م') 
            && (mb_substr($word, 1, 1) == 'ب' || mb_substr($word, 1, 1) == 'ف' 
            || mb_substr($word, 1, 1) == 'م') && $wordLen > 3
        ) {
            return true;
        }
        
        // الكلمات التي  تنتهي بياء ونون
        // أو ألف ونون أو ياء ونون
        // تكون أسماء ما لم تبدأ بأحد حروف المضارعة 
        if (preg_match('/^[^ايتن]\S{2}[اوي]ن$/u', $word)) {
            return true;
        }

        // إن كان على وزن اسم الآلة
        // أو اسم المكان أو اسم الزمان
        if (preg_match('/^م\S{3}$/u', $word) 
            || preg_match('/^م\S{2}ا\S$/u', $word)  
            || preg_match('/^م\S{3}ة$/u', $word)  
            || preg_match('/^\S{2}ا\S$/u', $word)  
            || preg_match('/^\Sا\Sو\S$/u', $word)  
            || preg_match('/^\S{2}و\S$/u', $word)  
            || preg_match('/^\S{2}ي\S$/u', $word)  
            || preg_match('/^م\S{2}و\S$/u', $word)  
            || preg_match('/^م\S{2}ي\S$/u', $word)  
            || preg_match('/^\S{3}ة$/u', $word) 
            || preg_match('/^\S{2}ا\Sة$/u', $word)  
            || preg_match('/^\Sا\S{2}ة$/u', $word)  
            || preg_match('/^\Sا\Sو\Sة$/u', $word)  
            || preg_match('/^ا\S{2}و\Sة$/u', $word)  
            || preg_match('/^ا\S{2}ي\S$/u', $word) 
            || preg_match('/^ا\S{3}$/u', $word)  
            || preg_match('/^\S{3}ى$/u', $word)  
            || preg_match('/^\S{3}اء$/u', $word)  
            || preg_match('/^\S{3}ان$/u', $word)  
            || preg_match('/^م\Sا\S{2}$/u', $word)  
            || preg_match('/^من\S{3}$/u', $word)  
            || preg_match('/^مت\S{3}$/u', $word)  
            || preg_match('/^مست\S{3}$/u', $word)  
            || preg_match('/^م\Sت\S{2}$/u', $word)  
            || preg_match('/^مت\Sا\S{2}$/u', $word) 
            || preg_match('/^\Sا\S{2}$/u', $word)
        ) {
            return true;
        }

        return false;
    }
    
    /**
     * Tag all words in a given Arabic string if they are nouns or not
     *      
     * @param string $str Arabic string you want to tag all its words
     *                    
     * @return array Two dimension array where item[i][0] represent the word i
     *               in the given string, and item[i][1] is 1 if that word is
     *               noun and 0 if it is not
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public static function tagText($str)
    {
        $text     = array();
        $words    = explode(' ', $str);
        $prevWord = '';
        
        foreach ($words as $word) {
            if ($word == '') {
                continue;
            }

            if (self::isNoun($word, $prevWord)) {
                $text[] = array($word, 1);
            } else {
                $text[] = array($word, 0);
            }
            
            $prevWord = $word;
        }

        return $text;
    }
    
    /**
     * Highlighted all nouns in a given Arabic string
     *      
     * @param string $str   Arabic string you want to highlighted 
     *                      all its nouns
     * @param string $style Name of the CSS class you would like to apply
     *                    
     * @return string Arabic string in HTML format where all nouns highlighted
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public static function highlightText($str, $style = null)
    {
        $html     = '';
        $prevTag  = 0;
        $prevWord = '';
        
        $taggedText = self::tagText($str);
        
        foreach ($taggedText as $wordTag) {
            list($word, $tag) = $wordTag;
            
            if ($prevTag == 1) {
                if (in_array($word, self::$_particlePreNouns)) {
                    $prevWord = $word;
                    continue;
                }
                
                if ($tag == 0) {
                    $html .= "</span> \r\n";
                }
            } else {
                // if ($tag == 1 && !in_array($word, $this->_commonWords)) {
                if ($tag == 1) {
                    $html .= " \r\n<span class=\"" . $style ."\">";
                }
            }
            
            $html .= ' ' . $prevWord . ' ' . $word;
            
            if ($prevWord != '') {
                $prevWord = '';
            }
            $prevTag = $tag;
        }
        
        if ($prevTag == 1) {
            $html .= "</span> \r\n";
        }
        
        return $html;
    }
}
