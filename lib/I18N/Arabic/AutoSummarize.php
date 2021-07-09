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
 * Class Name: Arabic Auto Summarize Class
 *  
 * Filename: AutoSummarize.php
 *  
 * Original Author(s): Khaled Al-Sham'aa <khaled@ar-php.org>
 *  
 * Purpose: Automatic keyphrase extraction to provide a quick mini-summary 
 *          for a long Arabic document.
 *           
 * ----------------------------------------------------------------------
 *  
 * Arabic Auto Summarize
 *
 * This class identifies the key points in an Arabic document for you to share with 
 * others or quickly scan. The class determines key points by analyzing an Arabic 
 * document and assigning a score to each sentence. Sentences that contain words 
 * used frequently in the document are given a higher score. You can then choose a 
 * percentage of the highest-scoring sentences to display in the summary. 
 * "ArAutoSummarize" class works best on well-structured documents such as reports, 
 * articles, and scientific papers.
 * 
 * "ArAutoSummarize" class cuts wordy copy to the bone by counting words and ranking 
 * sentences. First, "ArAutoSummarize" class identifies the most common words in the 
 * document and assigns a "score" to each word--the more frequently a word is used, 
 * the higher the score.
 * 
 * Then, it "averages" each sentence by adding the scores of its words and dividing 
 * the sum by the number of words in the sentence--the higher the average, the 
 * higher the rank of the sentence. "ArAutoSummarize" class can summarize texts to 
 * specific number of sentences or percentage of the original copy.
 * 
 * We use statistical approach, with some attention apparently paid to:
 * 
 * - Location: leading sentences of paragraph, title, introduction, and conclusion.
 * - Fixed phrases: in-text summaries.
 * - Frequencies of words, phrases, proper names
 * - Contextual material: query, title, headline, initial paragraph
 * 
 * The motivation for this class is the range of applications for key phrases:
 * 
 * - Mini-summary: Automatic key phrase extraction can provide a quick mini-summary 
 *   for a long document. For example, it could be a feature in a web sites; just 
 *   click the summarize button when browsing a long web page.
 * 
 * - Highlights: It can highlight key phrases in a long document, to facilitate 
 *   skimming the document.
 * 
 * - Author Assistance: Automatic key phrase extraction can help an author or editor 
 *   who wants to supply a list of key phrases for a document. For example, the 
 *   administrator of a web site might want to have a key phrase list at the top of 
 *   each web page. The automatically extracted phrases can be a starting point for 
 *   further manual refinement by the author or editor.
 * 
 * - Text Compression: On a device with limited display capacity or limited 
 *   bandwidth, key phrases can be a substitute for the full text. For example, an 
 *   email message could be reduced to a set of key phrases for display on a pager; 
 *   a web page could be reduced for display on a portable wireless web browser.
 * 
 * This list is not intended to be exhaustive, and there may be some overlap in 
 * the items.
 *
 * Example:
 * <code>
 * include('./I18N/Arabic.php');
 * $obj = new I18N_Arabic('AutoSummarize');
 * 
 * $file = 'Examples/Articles/Ajax.txt';
 * $r = 20;
 * 
 * // get contents of a file into a string
 * $fhandle = fopen($file, "r");
 * $c = fread($fhandle, filesize($file));
 * fclose($fhandle);
 * 
 * $k = $obj->getMetaKeywords($c, $r);
 * echo '<b><font color=#FFFF00>';
 * echo 'Keywords:</font></b>';
 * echo '<p dir="rtl" align="justify">';
 * echo $k . '</p>';
 * 
 * $s = $obj->doRateSummarize($c, $r);
 * echo '<b><font color=#FFFF00>';
 * echo 'Summary:</font></b>';
 * echo '<p dir="rtl" align="justify">';
 * echo $s . '</p>';
 * 
 * echo '<b><font color=#FFFF00>';
 * echo 'Full Text:</font></b>';
 * echo '<p><a class=ar_link target=_blank ';
 * echo 'href='.$file.'>Source File</a></p>';
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
 * This PHP class do automatic keyphrase extraction to provide a quick 
 * mini-summary for a long Arabic document
 *  
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */ 
class I18N_Arabic_AutoSummarize
{
    private $_normalizeAlef       = array('أ','إ','آ');
    private $_normalizeDiacritics = array('َ','ً','ُ','ٌ','ِ','ٍ','ْ','ّ');

    private $_commonChars = array('ة','ه','ي','ن','و','ت','ل','ا','س','م',
                                   'e', 't', 'a', 'o', 'i', 'n', 's');

    private $_separators = array('.',"\n",'،','؛','(','[','{',')',']','}',',',';');

    private $_commonWords    = array();
    private $_importantWords = array();

    /**
     * Loads initialize values
     *
     * @ignore
     */         
    public function __construct()
    {
        // This common words used in cleanCommon method
        $words    = file(dirname(__FILE__).'/data/ar-stopwords.txt');
        $en_words = file(dirname(__FILE__).'/data/en-stopwords.txt');

        $words = array_merge($words, $en_words);
        $words = array_map('trim', $words);
        
        $this->_commonWords = $words;
        
        // This important words used in rankSentences method
        $words = file(dirname(__FILE__).'/data/important-words.txt');
        $words = array_map('trim', $words);

        $this->_importantWords = $words;
    }
    
    /**
     * Load enhanced Arabic stop words list 
     * 
     * @return void          
     */         
    public function loadExtra()
    {
        $extra_words = file(dirname(__FILE__).'/data/ar-extra-stopwords.txt');
        $extra_words = array_map('trim', $extra_words);

        $this->_commonWords = array_merge($this->_commonWords, $extra_words);
    }

    /**
     * Core summarize function that implement required steps in the algorithm
     *                        
     * @param string  $str      Input Arabic document as a string
     * @param string  $keywords List of keywords higlited by search process
     * @param integer $int      Sentences value (see $mode effect also)
     * @param string  $mode     Mode of sentences count [number|rate]
     * @param string  $output   Output mode [summary|highlight]
     * @param string  $style    Name of the CSS class you would like to apply
     *                    
     * @return string Output summary requested
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function summarize($str, $keywords, $int, $mode, $output, $style=null)
    {
        preg_match_all(
            "/[^\.\n\،\؛\,\;](.+?)[\.\n\،\؛\,\;]/u", 
            $str, 
            $sentences
        );
        $_sentences = $sentences[0];

        if ($mode == 'rate') {
            $str            = preg_replace("/\s{2,}/u", ' ', $str);
            $totalChars     = mb_strlen($str);
            $totalSentences = count($_sentences);

            $maxChars = round($int * $totalChars / 100);
            $int      = round($int * $totalSentences / 100);
        } else {
            $maxChars = 99999;
        }
        
        $summary = '';

        $str           = strip_tags($str);
        $normalizedStr = $this->doNormalize($str);
        $cleanedStr    = $this->cleanCommon($normalizedStr);
        $stemStr       = $this->draftStem($cleanedStr);
        
        preg_match_all(
            "/[^\.\n\،\؛\,\;](.+?)[\.\n\،\؛\,\;]/u", 
            $stemStr, 
            $sentences
        );
        $_stemmedSentences = $sentences[0];

        $wordRanks = $this->rankWords($stemStr);
        
        if ($keywords) {
            $keywords = $this->doNormalize($keywords);
            $keywords = $this->draftStem($keywords);
            $words    = explode(' ', $keywords);
            
            foreach ($words as $word) {
                $wordRanks[$word] = 1000;
            }
        }
        
        $sentencesRanks = $this->rankSentences(
            $_sentences, 
            $_stemmedSentences, 
            $wordRanks
        );
        
        list($sentences, $ranks) = $sentencesRanks;

        $minRank = $this->minAcceptedRank($sentences, $ranks, $int, $maxChars);

        $totalSentences = count($ranks);
        
        for ($i = 0; $i < $totalSentences; $i++) {
            if ($sentencesRanks[1][$i] >= $minRank) {
                if ($output == 'summary') {
                    $summary .= ' '.$sentencesRanks[0][$i];
                } else {
                    $summary .= '<span class="' . $style .'">' . 
                                $sentencesRanks[0][$i] . '</span>';
                }
            } else {
                if ($output == 'highlight') {
                    $summary .= $sentencesRanks[0][$i];
                }
            }
        }
        
        if ($output == 'highlight') {
            $summary = str_replace("\n", '<br />', $summary);
        }
        
        return $summary;
    }
          
    /**
     * Summarize input Arabic string (document content) into specific number of 
     * sentences in the output
     *                        
     * @param string  $str      Input Arabic document as a string
     * @param integer $int      Number of sentences required in output summary
     * @param string  $keywords List of keywords higlited by search process
     *                    
     * @return string Output summary requested
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function doSummarize($str, $int, $keywords)
    {
        $summary = $this->summarize(
            $str, $keywords, $int, 'number', 'summary', $style
        );
        
        return $summary;
    }
    
    /**
     * Summarize percentage of the input Arabic string (document content) into output
     *      
     * @param string  $str      Input Arabic document as a string
     * @param integer $rate     Rate of output summary sentence number as 
     *                          percentage of the input Arabic string 
     *                          (document content)
     * @param string  $keywords List of keywords higlited by search process
     *                    
     * @return string Output summary requested
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function doRateSummarize($str, $rate, $keywords)
    {
        $summary = $this->summarize(
            $str, $keywords, $rate, 'rate', 'summary', $style
        );
        
        return $summary;
    }
    
    /**
     * Highlight key sentences (summary) of the input string (document content) 
     * using CSS and send the result back as an output
     *                             
     * @param string  $str      Input Arabic document as a string
     * @param integer $int      Number of key sentences required to be 
     *                          highlighted in the input string 
     *                          (document content)
     * @param string  $keywords List of keywords higlited by search process
     * @param string  $style    Name of the CSS class you would like to apply
     *                    
     * @return string Output highlighted key sentences summary (using CSS)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function highlightSummary($str, $int, $keywords, $style)
    {
        $summary = $this->summarize(
            $str, $keywords, $int, 'number', 'highlight', $style
        );
        
        return $summary;
    }
    
    /**
     * Highlight key sentences (summary) as percentage of the input string 
     * (document content) using CSS and send the result back as an output.
     *                    
     * @param string  $str      Input Arabic document as a string
     * @param integer $rate     Rate of highlighted key sentences summary 
     *                          number as percentage of the input Arabic 
     *                          string (document content)
     * @param string  $keywords List of keywords higlited by search process
     * @param string  $style    Name of the CSS class you would like to apply
     *                    
     * @return string Output highlighted key sentences summary (using CSS)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function highlightRateSummary($str, $rate, $keywords, $style)
    {
        $summary = $this->summarize(
            $str, $keywords, $rate, 'rate', 'highlight', $style
        );
        
        return $summary;
    }
    
    /**
     * Extract keywords from a given Arabic string (document content)
     *      
     * @param string  $str Input Arabic document as a string
     * @param integer $int Number of keywords required to be extracting 
     *                     from input string (document content)
     *                    
     * @return string List of the keywords extracting from input Arabic string
     *               (document content)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function getMetaKeywords($str, $int)
    {
        $patterns     = array();
        $replacements = array();
        $metaKeywords = '';
        
        array_push($patterns, '/\.|\n|\،|\؛|\(|\[|\{|\)|\]|\}|\,|\;/u');
        array_push($replacements, ' ');
        $str = preg_replace($patterns, $replacements, $str);
        
        $normalizedStr = $this->doNormalize($str);
        $cleanedStr    = $this->cleanCommon($normalizedStr);
        
        $str = preg_replace('/(\W)ال(\w{3,})/u', '\\1\\2', $cleanedStr);
        $str = preg_replace('/(\W)وال(\w{3,})/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})هما(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})كما(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})تين(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})هم(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})هن(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})ها(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})نا(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})ني(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})كم(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})تم(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})كن(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})ات(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})ين(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})تن(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})ون(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})ان(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})تا(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})وا(\W)/u', '\\1\\2', $str);
        $str = preg_replace('/(\w{3,})ة(\W)/u', '\\1\\2', $str);

        $stemStr = preg_replace('/(\W)\w{1,3}(\W)/u', '\\2', $str);
        
        $wordRanks = $this->rankWords($stemStr);
        
        arsort($wordRanks, SORT_NUMERIC);
        
        $i = 1;
        foreach ($wordRanks as $key => $value) {
            if ($this->acceptedWord($key)) {
                $metaKeywords .= $key . '، ';
                $i++;
            }
            if ($i > $int) {
                break;
            }
        }
        
        $metaKeywords = mb_substr($metaKeywords, 0, -2);
        
        return $metaKeywords;
    }
    
    /**
     * Normalized Arabic document
     *      
     * @param string $str Input Arabic document as a string
     *      
     * @return string Normalized Arabic document
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function doNormalize($str)
    {
        $str = str_replace($this->_normalizeAlef, 'ا', $str);
        $str = str_replace($this->_normalizeDiacritics, '', $str);
        $str = strtr(
            $str, 
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
            'abcdefghijklmnopqrstuvwxyz'
        );

        return $str;
    }
    
    /**
     * Extracting common Arabic words (roughly) 
     * from input Arabic string (document content)
     *                        
     * @param string $str Input normalized Arabic document as a string
     *      
     * @return string Arabic document as a string free of common words (roughly)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function cleanCommon($str)
    {
        $str = str_replace($this->_commonWords, ' ', $str);
        
        return $str;
    }
    
    /**
     * Remove less significant Arabic letter from given string (document content). 
     * Please note that output will not be human readable.
     *                      
     * @param string $str Input Arabic document as a string
     *      
     * @return string Output string after removing less significant Arabic letter
     *                (not human readable output)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function draftStem($str)
    {
        $str = str_replace($this->_commonChars, '', $str);
        return $str;
    }
    
    /**
     * Ranks words in a given Arabic string (document content). That rank refers 
     * to the frequency of that word appears in that given document.
     *                      
     * @param string $str Input Arabic document as a string
     *      
     * @return hash Associated array where document words referred by index and
     *              those words ranks referred by values of those array items.
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function rankWords($str)
    {
        $wordsRanks = array();
        
        $str   = str_replace($this->_separators, ' ', $str);
        $words = preg_split("/[\s,]+/u", $str);
        
        foreach ($words as $word) {
            if (isset($wordsRanks[$word])) {
                $wordsRanks[$word]++;
            } else {
                $wordsRanks[$word] = 1;
            }
        }

        foreach ($wordsRanks as $wordRank => $total) {
            if (mb_substr($wordRank, 0, 1) == 'و') {
                $subWordRank = mb_substr($wordRank, 1, mb_strlen($wordRank) - 1);
                if (isset($wordsRanks[$subWordRank])) {
                    unset($wordsRanks[$wordRank]);
                    $wordsRanks[$subWordRank] += $total;
                }
            }
        }

        return $wordsRanks;
    }
    
    /**
     * Ranks sentences in a given Arabic string (document content).
     *      
     * @param array $sentences        Sentences of the input Arabic document 
     *                                as an array
     * @param array $stemmedSentences Stemmed sentences of the input Arabic 
     *                                document as an array
     * @param array $arr              Words ranks array (word as an index and 
     *                                value refer to the word frequency)
     *                         
     * @return array Two dimension array, first item is an array of document
     *               sentences, second item is an array of ranks of document
     *               sentences.
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function rankSentences($sentences, $stemmedSentences, $arr)
    {
        $sentenceArr = array();
        $rankArr     = array();
        
        $max = count($sentences);
        
        for ($i = 0; $i < $max; $i++) {
            $sentence = $sentences[$i];

            $w     = 0;
            $first = mb_substr($sentence, 0, 1);
            $last  = mb_substr($sentence, -1, 1);
                    
            if ($first == "\n") {
                $w += 3;
            } elseif (in_array($first, $this->_separators)) {
                $w += 2;
            } else {
                $w += 1;
            }
                    
            if ($last == "\n") {
                $w += 3;
            } elseif (in_array($last, $this->_separators)) {
                $w += 2;
            } else {
                $w += 1;
            }

            foreach ($this->_importantWords as $word) {
                if ($word != '') {
                    $w += mb_substr_count($sentence, $word);
                }
            }
            
            $sentence = mb_substr(mb_substr($sentence, 0, -1), 1);
            if (!in_array($first, $this->_separators)) {
                $sentence = $first . $sentence;
            }
            
            $stemStr = $stemmedSentences[$i];
            $stemStr = mb_substr($stemStr, 0, -1);
            
            $words = preg_split("/[\s,]+/u", $stemStr);
            
            $totalWords = count($words);
            if ($totalWords > 4) {
                $totalWordsRank = 0;
                
                foreach ($words as $word) {
                    if (isset($arr[$word])) {
                        $totalWordsRank += $arr[$word];
                    }
                }
                
                $wordsRank     = $totalWordsRank / $totalWords;
                $sentenceRanks = $w * $wordsRank;
                
                array_push($sentenceArr, $sentence . $last);
                array_push($rankArr, $sentenceRanks);
            }
        }
        
        $sentencesRanks = array($sentenceArr, $rankArr);
        
        return $sentencesRanks;
    }
    
    /**
     * Calculate minimum rank for sentences which will be including in the summary
     *      
     * @param array   $str Document sentences
     * @param array   $arr Sentences ranks
     * @param integer $int Number of sentences you need to include in your summary
     * @param integer $max Maximum number of characters accepted in your summary
     *      
     * @return integer Minimum accepted sentence rank (sentences with rank more
     *                 than this will be listed in the document summary)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function minAcceptedRank($str, $arr, $int, $max)
    {
        $len = array();
        
        foreach ($str as $line) {
            $len[] = mb_strlen($line);
        }

        rsort($arr, SORT_NUMERIC);

        $totalChars = 0;
        
        for ($i=0; $i<=$int; $i++) {

            if (!isset($arr[$i])) {
                $minRank = 0;
                break;
            }

            $totalChars += $len[$i];

            if ($totalChars >= $max) {
                $minRank = $arr[$i];
                break;
            }

            $minRank = $arr[$i];
        }

        return $minRank;
    }
    
    /**
     * Check some conditions to know if a given string is a formal valid word or not
     *      
     * @param string $word String to be checked if it is a valid word or not
     *      
     * @return boolean True if passed string is accepted as a valid word else 
     *                 it will return False
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function acceptedWord($word)
    {
        $accept = true;
        
        if (mb_strlen($word) < 3) {
            $accept = false;
        }
        
        return $accept;
    }
}

