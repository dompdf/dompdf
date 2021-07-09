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
 * Class Name: Arabic Queary Class
 *  
 * Filename: Query.php
 *  
 * Original  Author(s): Khaled Al-Sham'aa <khaled@ar-php.org>
 *  
 * Purpose:  Build WHERE condition for SQL statement using MySQL REGEXP and
 *           Arabic lexical  rules
 *            
 * ----------------------------------------------------------------------
 *  
 * Arabic Queary Class
 *
 * PHP class build WHERE condition for SQL statement using MySQL REGEXP and 
 * Arabic lexical  rules.
 *    
 * With the exception of the Qur'an and pedagogical texts, Arabic is generally 
 * written without vowels or other graphic symbols that indicate how a word is 
 * pronounced. The reader is expected to fill these in from context. Some of the 
 * graphic symbols include sukuun, which is placed over a consonant to indicate that 
 * it is not followed by a vowel; shadda, written over a consonant to indicate it is 
 * doubled; and hamza, the sign of the glottal stop, which can be written above or 
 * below (alif) at the beginning of a word, or on (alif), (waaw), (yaa'), 
 * or by itself on the line elsewhere. Also, common spelling differences regularly 
 * appear, including the use of (haa') for (taa' marbuuta) and (alif maqsuura) 
 * for (yaa'). These features of written Arabic, which are also seen in Hebrew as 
 * well as other languages written with Arabic script (such as Farsi, Pashto, and 
 * Urdu), make analyzing and searching texts quite challenging. In addition, Arabic 
 * morphology and grammar are quite rich and present some unique issues for 
 * information retrieval applications.
 * 
 * There are essentially three ways to search an Arabic text with Arabic queries: 
 * literal, stem-based or root-based.
 * 
 * A literal search, the simplest search and retrieval method, matches documents 
 * based on the search terms exactly as the user entered them. The advantage of this 
 * technique is that the documents returned will without a doubt contain the exact 
 * term for which the user is looking. But this advantage is also the biggest 
 * disadvantage: many, if not most, of the documents containing the terms in 
 * different forms will be missed. Given the many ambiguities of written Arabic, the 
 * success rate of this method is quite low. For example, if the user searches 
 * for (kitaab, book), he or she will not find documents that only 
 * contain (`al-kitaabu, the book).
 * 
 * Stem-based searching, a more complicated method, requires some normalization of 
 * the original texts and the queries. This is done by removing the vowel signs, 
 * unifying the hamza forms and removing or standardizing the other signs. 
 * Additionally, grammatical affixes and other constructions which attach directly 
 * to words, such as conjunctions, prepositions, and the definite article, should be 
 * identified and removed. Finally, regular and irregular plural forms need to be 
 * identified and reduced to their singular forms. Performing this type of stemming 
 * leads to more successful searches, but can be problematic due to over-generation 
 * or incorrect generation of stems.
 * 
 * A third method for searching Arabic texts is to index and search for the root 
 * forms of each word. Since most verbs and nouns in Arabic are derived from 
 * triliteral (or, rarely, quadriliteral) roots, identifying the underlying root of 
 * each word theoretically retrieves most of the documents containing a given search 
 * term regardless of form. However, there are some significant challenges with this 
 * approach. Determining the root for a given word is extremely difficult, since it 
 * requires a detailed morphological, syntactic and semantic analysis of the text to 
 * fully disambiguate the root forms. The issue is complicated further by the fact 
 * that not all words are derived from roots. For example, loan words (words 
 * borrowed from another language) are not based on root forms, although there are 
 * even exceptions to this rule. For example, some loans that have a structure 
 * similar to triliteral roots, such as the English word film, are handled 
 * grammatically as if they were root-based, adding to the complexity of this type 
 * of search. Finally, the root can serve as the foundation for a wide variety of 
 * words with related meanings. The root (k-t-b) is used for many words related 
 * to writing, including (kataba, to write), (kitaab, book), (maktab, 
 * office), and (kaatib, author). But the same root is also used for regiment/
 * battalion, (katiiba). As a result, searching based on root forms results in 
 * very high recall, but precision is usually quite low.
 * 
 * While search and retrieval of Arabic text will never be an easy task, relying on 
 * linguistic analysis tools and methods can help make the process more successful. 
 * Ultimately, the search method you choose should depend on how critical it is to 
 * retrieve every conceivable instance of a word or phrase and the resources you 
 * have to process search returns in order to determine their true relevance.
 * 
 * Source: Volume 13 Issue 7 of MultiLingual Computing & 
 * Technology published by MultiLingual Computing, Inc., 319 North First Ave., 
 * Sandpoint, Idaho, USA, 208-263-8178, Fax: 208-263-6310.
 * 
 * Example:
 * <code>
 *     include('./I18N/Arabic.php');
 *     $obj = new I18N_Arabic('Query');
 *     
 *     $dbuser = 'root';
 *     $dbpwd = '';
 *     $dbname = 'test';
 *     
 *     try {
 *         $dbh = new PDO('mysql:host=localhost;dbname='.$dbname, $dbuser, $dbpwd);
 * 
 *         // Set the error reporting attribute
 *         $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 * 
 *         $dbh->exec("SET NAMES 'utf8'");
 *     
 *         if ($_GET['keyword'] != '') {
 *             $keyword = @$_GET['keyword'];
 *             $keyword = str_replace('\"', '"', $keyword);
 *     
 *             $obj->setStrFields('headline');
 *             $obj->setMode($_GET['mode']);
 *     
 *             $strCondition = $Arabic->getWhereCondition($keyword);
 *         } else {
 *             $strCondition = '1';
 *         }
 *     
 *         $StrSQL = "SELECT `headline` FROM `aljazeera` WHERE $strCondition";
 * 
 *         $i = 0;
 *         foreach ($dbh->query($StrSQL) as $row) {
 *             $headline = $row['headline'];
 *             $i++;
 *             if ($i % 2 == 0) {
 *                 $bg = "#f0f0f0";
 *             } else {
 *                 $bg = "#ffffff";
 *             }
 *             echo "<tr bgcolor=\"$bg\"><td>$headline</td></tr>";
 *         }
 * 
 *         // Close the databse connection
 *         $dbh = null;
 * 
 *     } catch (PDOException $e) {
 *         echo $e->getMessage();
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
 * This PHP class build WHERE condition for SQL statement using MySQL REGEXP and
 * Arabic lexical  rules
 *  
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */ 
class I18N_Arabic_Query
{
    private $_fields          = array();
    private $_lexPatterns     = array();
    private $_lexReplacements = array();
    private $_mode            = 0;

    /**
     * Loads initialize values
     */         
    public function __construct()
    {
        $xml = simplexml_load_file(dirname(__FILE__).'/data/ArQuery.xml'); 
         
        foreach ($xml->xpath("//preg_replace[@function='__construct']/pair")
                 as $pair) { 

                 array_push($this->_lexPatterns, (string)$pair->search); 
            array_push($this->_lexReplacements, (string)$pair->replace); 
        }
    }
    
    /**
     * Setting value for $_fields array
     *      
     * @param array $arrConfig Name of the fields that SQL statement will search
     *                         them (in array format where items are those 
     *                         fields names)
     *                       
     * @return object $this to build a fluent interface
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function setArrFields($arrConfig)
    {
        if (is_array($arrConfig)) {
            // Get _fields array
            $this->_fields = $arrConfig;
        }
        
        return $this;
    }
    
    /**
     * Setting value for $_fields array
     *      
     * @param string $strConfig Name of the fields that SQL statement will search
     *                          them (in string format using comma as delimated)
     *                          
     * @return object $this to build a fluent interface
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function setStrFields($strConfig)
    {
        if (is_string($strConfig)) {
            // Get _fields array
            $this->_fields = explode(',', $strConfig);
        }

        return $this;
    }
    
    /**
     * Setting $mode propority value that refer to search mode
     * [0 for OR logic | 1 for AND logic]
     *      
     * @param integer $mode Setting value to be saved in the $mode propority
     *      
     * @return object $this to build a fluent interface
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function setMode($mode)
    {
        if (in_array($mode, array('0', '1'))) {
            // Set search mode [0 for OR logic | 1 for AND logic]
            $this->_mode = $mode;
        }
        
        return $this;
    }
    
    /**
     * Getting $mode propority value that refer to search mode 
     * [0 for OR logic | 1 for AND logic]
     *      
     * @return integer Value of $mode properity
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function getMode()
    {
        // Get search mode value [0 for OR logic | 1 for AND logic]
        return $this->_mode;
    }
    
    /**
     * Getting values of $_fields Array in array format
     *      
     * @return array Value of $_fields array in Array format
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function getArrFields()
    {
        $fields = $this->_fields;
        
        return $fields;
    }
    
    /**
     * Getting values of $_fields array in String format (comma delimated)
     *      
     * @return string Values of $_fields array in String format (comma delimated)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function getStrFields()
    {
        $fields = implode(',', $this->_fields);
        
        return $fields;
    }
    
    /**
     * Build WHERE section of the SQL statement using defind lex's rules, search 
     * mode [AND | OR], and handle also phrases (inclosed by "") using normal 
     * LIKE condition to match it as it is.
     *      
     * @param string $arg String that user search for in the database table
     *                    
     * @return string The WHERE section in SQL statement 
     *                (MySQL database engine format)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function getWhereCondition($arg)
    {
        $sql = '';
        
        //$arg = mysql_real_escape_string($arg);
        $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
        $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");
        $arg = str_replace($search, $replace, $arg);
        
        // Check if there are phrases in $arg should handle as it is
        $phrase = explode("\"", $arg);
        
        if (count($phrase) > 2) {
            // Re-init $arg variable
            // (It will contain the rest of $arg except phrases).
            $arg = '';
            
            for ($i = 0; $i < count($phrase); $i++) {
                $subPhrase = $phrase[$i]; 
                if ($i % 2 == 0 && $subPhrase != '') {
                    // Re-build $arg variable after restricting phrases
                    $arg .= $subPhrase;
                } elseif ($i % 2 == 1 && $subPhrase != '') {
                    // Handle phrases using reqular LIKE matching in MySQL
                    $this->wordCondition[] = $this->getWordLike($subPhrase);
                }
            }
        }
        
        // Handle normal $arg using lex's and regular expresion
        $words = preg_split('/\s+/', trim($arg));
        
        foreach ($words as $word) {
            //if (is_numeric($word) || strlen($word) > 2) {
                // Take off all the punctuation
                //$word = preg_replace("/\p{P}/", '', $word);
                $exclude = array('(', ')', '[', ']', '{', '}', ',', ';', ':', 
                                 '?', '!', '،', '؛', '؟');
                $word    = str_replace($exclude, '', $word);

                $this->wordCondition[] = $this->getWordRegExp($word);
            //}
        }
        
        if (!empty($this->wordCondition)) {
            if ($this->_mode == 0) {
                $sql = '(' . implode(') OR (', $this->wordCondition) . ')';
            } elseif ($this->_mode == 1) {
                $sql = '(' . implode(') AND (', $this->wordCondition) . ')';
            }
        }
        
        return $sql;
    }
    
    /**
     * Search condition in SQL format for one word in all defind fields using 
     * REGEXP clause and lex's rules
     *      
     * @param string $arg String (one word) that you want to build a condition for
     *      
     * @return string sub SQL condition (for internal use)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function getWordRegExp($arg)
    {
        $arg = $this->lex($arg);
        //$sql = implode(" REGEXP '$arg' OR ", $this->_fields) . " REGEXP '$arg'";
        $sql = ' REPLACE(' . 
               implode(", 'ـ', '') REGEXP '$arg' OR REPLACE(", $this->_fields) . 
               ", 'ـ', '') REGEXP '$arg'";

        
        return $sql;
    }
    
    /**
     * Search condition in SQL format for one word in all defind fields using 
     * normal LIKE clause
     *      
     * @param string $arg String (one word) that you want to build a condition for
     *      
     * @return string sub SQL condition (for internal use)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function getWordLike($arg)
    {
        $sql = implode(" LIKE '$arg' OR ", $this->_fields) . " LIKE '$arg'";
        
        return $sql;
    }
    
    /**
     * Get more relevant order by section related to the user search keywords
     *      
     * @param string $arg String that user search for in the database table
     *                    
     * @return string sub SQL ORDER BY section 
     * @author Saleh AlMatrafe <saleh@saleh.cc>
     */
    public function getOrderBy($arg)
    {
        // Check if there are phrases in $arg should handle as it is
        $phrase = explode("\"", $arg);
        if (count($phrase) > 2) {
            // Re-init $arg variable 
            // (It will contain the rest of $arg except phrases).
            $arg = '';
            for ($i = 0; $i < count($phrase); $i++) {
                if ($i % 2 == 0 && $phrase[$i] != '') {
                    // Re-build $arg variable after restricting phrases
                    $arg .= $phrase[$i];
                } elseif ($i % 2 == 1 && $phrase[$i] != '') {
                    // Handle phrases using reqular LIKE matching in MySQL
                    $wordOrder[] = $this->getWordLike($phrase[$i]);
                }
            }
        }
        
        // Handle normal $arg using lex's and regular expresion
        $words = explode(' ', $arg);
        foreach ($words as $word) {
            if ($word != '') {
                $wordOrder[] = 'CASE WHEN ' . 
                               $this->getWordRegExp($word) . 
                               ' THEN 1 ELSE 0 END';
            }
        }
        
        $order = '((' . implode(') + (', $wordOrder) . ')) DESC';
        
        return $order;
    }

    /**
     * This method will implement various regular expressin rules based on 
     * pre-defined Arabic lexical rules
     *      
     * @param string $arg String of one word user want to search for
     *      
     * @return string Regular Expression format to be used in MySQL query statement
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function lex($arg)
    {
        $arg = preg_replace($this->_lexPatterns, $this->_lexReplacements, $arg);
        
        return $arg;
    }
    
    /**
     * Get most possible Arabic lexical forms for a given word
     *      
     * @param string $word String that user search for
     *      
     * @return string list of most possible Arabic lexical forms for a given word 
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function allWordForms($word) 
    {
        $wordForms = array($word);
        
        $postfix1 = array('كم', 'كن', 'نا', 'ها', 'هم', 'هن');
        $postfix2 = array('ين', 'ون', 'ان', 'ات', 'وا');
        
        $len = mb_strlen($word);

        if (mb_substr($word, 0, 2) == 'ال') {
            $word = mb_substr($word, 2);
        }
        
        $wordForms[] = $word;

        $str1 = mb_substr($word, 0, -1);
        $str2 = mb_substr($word, 0, -2);
        $str3 = mb_substr($word, 0, -3);

        $last1 = mb_substr($word, -1);
        $last2 = mb_substr($word, -2);
        $last3 = mb_substr($word, -3);
        
        if ($len >= 6 && $last3 == 'تين') {
            $wordForms[] = $str3;
            $wordForms[] = $str3 . 'ة';
            $wordForms[] = $word . 'ة';
        }
        
        if ($len >= 6 && ($last3 == 'كما' || $last3 == 'هما')) {
            $wordForms[] = $str3;
            $wordForms[] = $str3 . 'كما';
            $wordForms[] = $str3 . 'هما';
        }

        if ($len >= 5 && in_array($last2, $postfix2)) {
            $wordForms[] = $str2;
            $wordForms[] = $str2.'ة';
            $wordForms[] = $str2.'تين';

            foreach ($postfix2 as $postfix) {
                $wordForms[] = $str2 . $postfix;
            }
        }

        if ($len >= 5 && in_array($last2, $postfix1)) {
            $wordForms[] = $str2;
            $wordForms[] = $str2.'ي';
            $wordForms[] = $str2.'ك';
            $wordForms[] = $str2.'كما';
            $wordForms[] = $str2.'هما';

            foreach ($postfix1 as $postfix) {
                $wordForms[] = $str2 . $postfix;
            }
        }

        if ($len >= 5 && $last2 == 'ية') {
            $wordForms[] = $str1;
            $wordForms[] = $str2;
        }

        if (($len >= 4 && ($last1 == 'ة' || $last1 == 'ه' || $last1 == 'ت')) 
            || ($len >= 5 && $last2 == 'ات')
        ) {
            $wordForms[] = $str1;
            $wordForms[] = $str1 . 'ة';
            $wordForms[] = $str1 . 'ه';
            $wordForms[] = $str1 . 'ت';
            $wordForms[] = $str1 . 'ات';
        }
        
        if ($len >= 4 && $last1 == 'ى') {
            $wordForms[] = $str1 . 'ا';
        }

        $trans = array('أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا');
        foreach ($wordForms as $word) {
            $normWord = strtr($word, $trans);
            if ($normWord != $word) {
                $wordForms[] = $normWord;
            }
        }
        
        $wordForms = array_unique($wordForms);
        
        return $wordForms;
    }
    
    /**
     * Get most possible Arabic lexical forms of user search keywords
     *      
     * @param string $arg String that user search for
     *                    
     * @return string list of most possible Arabic lexical forms for given keywords 
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function allForms($arg)
    {
        $wordForms = array();
        $words     = explode(' ', $arg);
        
        foreach ($words as $word) {
            $wordForms = array_merge($wordForms, $this->allWordForms($word));
        }
        
        $str = implode(' ', $wordForms);
        
        return $str;
    }
}
