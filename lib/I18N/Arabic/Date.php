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
 * Class Name: Arabic Date
 *  
 * Filename:   Date.php
 *  
 * Original    Author(s): Khaled Al-Sham'aa <khaled@ar-php.org>
 *  
 * Purpose:    Arabic customization for PHP date function
 *  
 * ----------------------------------------------------------------------
 *  
 * Arabic Date
 *
 * PHP class for Arabic and Islamic customization of PHP date function. It
 * can convert UNIX timestamp into string in Arabic as well as convert it into
 * Hijri calendar
 * 
 * The Islamic Calendar:
 * 
 * The Islamic calendar is purely lunar and consists of twelve alternating months 
 * of 30 and 29 days, with the final 29 day month extended to 30 days during leap 
 * years. Leap years follow a 30 year cycle and occur in years 1, 5, 7, 10, 13, 16, 
 * 18, 21, 24, 26, and 29. The calendar begins on Friday, July 16th, 622 C.E. in 
 * the Julian calendar, Julian day 1948439.5, the day of Muhammad's separate from 
 * Mecca to Medina, the first day of the first month of year 1 A.H.--"Anno Hegira".
 * 
 * Each cycle of 30 years thus contains 19 normal years of 354 days and 11 leap 
 * years of 355, so the average length of a year is therefore 
 * ((19 x 354) + (11 x 355)) / 30 = 354.365... days, with a mean length of month of 
 * 1/12 this figure, or 29.53055... days, which closely approximates the mean 
 * synodic month (time from new Moon to next new Moon) of 29.530588 days, with the 
 * calendar only slipping one day with respect to the Moon every 2525 years. Since 
 * the calendar is fixed to the Moon, not the solar year, the months shift with 
 * respect to the seasons, with each month beginning about 11 days earlier in each 
 * successive solar year.
 * 
 * The convert presented here is the most commonly used civil calendar in the 
 * Islamic world; for religious purposes months are defined to start with the 
 * first observation of the crescent of the new Moon.
 * 
 * The Julian Calendar:
 * 
 * The Julian calendar was proclaimed by Julius Casar in 46 B.C. and underwent 
 * several modifications before reaching its final form in 8 C.E. The Julian 
 * calendar differs from the Gregorian only in the determination of leap years, 
 * lacking the correction for years divisible by 100 and 400 in the Gregorian 
 * calendar. In the Julian calendar, any positive year is a leap year if divisible 
 * by 4. (Negative years are leap years if when divided by 4 a remainder of 3 
 * results.) Days are considered to begin at midnight.
 * 
 * In the Julian calendar the average year has a length of 365.25 days. compared to 
 * the actual solar tropical year of 365.24219878 days. The calendar thus 
 * accumulates one day of error with respect to the solar year every 128 years. 
 * Being a purely solar calendar, no attempt is made to synchronise the start of 
 * months to the phases of the Moon.
 * 
 * The Gregorian Calendar:
 * 
 * The Gregorian calendar was proclaimed by Pope Gregory XIII and took effect in 
 * most Catholic states in 1582, in which October 4, 1582 of the Julian calendar 
 * was followed by October 15 in the new calendar, correcting for the accumulated 
 * discrepancy between the Julian calendar and the equinox as of that date. When 
 * comparing historical dates, it's important to note that the Gregorian calendar, 
 * used universally today in Western countries and in international commerce, was 
 * adopted at different times by different countries. Britain and her colonies 
 * (including what is now the United States), did not switch to the Gregorian 
 * calendar until 1752, when Wednesday 2nd September in the Julian calendar dawned 
 * as Thursday the 14th in the Gregorian.
 * 
 * The Gregorian calendar is a minor correction to the Julian. In the Julian 
 * calendar every fourth year is a leap year in which February has 29, not 28 days, 
 * but in the Gregorian, years divisible by 100 are not leap years unless they are 
 * also divisible by 400. How prescient was Pope Gregory! Whatever the problems of 
 * Y2K, they won't include sloppy programming which assumes every year divisible by 
 * 4 is a leap year since 2000, unlike the previous and subsequent years divisible 
 * by 100, is a leap year. As in the Julian calendar, days are considered to begin 
 * at midnight.
 * 
 * The average length of a year in the Gregorian calendar is 365.2425 days compared 
 * to the actual solar tropical year (time from equinox to equinox) of 365.24219878 
 * days, so the calendar accumulates one day of error with respect to the solar year 
 * about every 3300 years. As a purely solar calendar, no attempt is made to 
 * synchronise the start of months to the phases of the Moon.
 * 
 * date -- Format a local time/date
 * string date ( string format, int timestamp);
 * 
 * Returns a string formatted according to the given format string using the given 
 * integer timestamp or the current local time if no timestamp is given. In 
 * otherwords, timestamp is optional and defaults to the value of time().
 * 
 * Example:
 * <code>
 *   date_default_timezone_set('UTC');
 *   $time = time();
 *   
 *   echo date('l dS F Y h:i:s A', $time);
 *   echo '<br /><br />';
 *   
 *   include('./I18N/Arabic.php');
 *   $obj = new I18N_Arabic('Date');
 *   
 *   echo $obj->date('l dS F Y h:i:s A', $time);
 *   echo '<br /><br />';
 *   
 *   $obj->setMode(2);
 *   echo $obj->date('l dS F Y h:i:s A', $time);
 *   echo '<br /><br />';
 *   
 *   $obj->setMode(3);
 *   echo $obj->date('l dS F Y h:i:s A', $time);
 *   echo '<br /><br />';
 *   
 *   $obj->setMode(4);
 *   echo $obj->date('l dS F Y h:i:s A', $time);    
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
 * This PHP class is an Arabic customization for PHP date function
 *  
 * @category  I18N 
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *    
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org 
 */ 
class I18N_Arabic_Date
{
    private $_mode = 1;
    private $_xml  = null;

    /**
     * Loads initialize values
     *
     * @ignore
     */         
    public function __construct()
    {
        $this->_xml = simplexml_load_file(dirname(__FILE__).'/data/ArDate.xml');
    }
    
    /**
     * Setting value for $mode scalar
     *      
     * @param integer $mode Output mode of date function where:
     *                       1) Hijri format (Islamic calendar)
     *                       2) Arabic month names used in Middle East countries
     *                       3) Arabic Transliteration of Gregorian month names
     *                       4) Both of 2 and 3 formats together
     *                       5) Libya style
     *                       6) Algeria and Tunis style
     *                       7) Morocco style          
     *                       8) Hijri format (Islamic calendar) in English
     *                                   
     * @return object $this to build a fluent interface
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function setMode($mode = 1)
    {
        $mode = (int) $mode;
        
        if ($mode > 0 && $mode < 9) {
            $this->_mode = $mode;
        }
        
        return $this;
    }
    
    /**
     * Getting $mode value that refer to output mode format
     *               1) Hijri format (Islamic calendar)
     *               2) Arabic month names used in Middle East countries
     *               3) Arabic Transliteration of Gregorian month names
     *               4) Both of 2 and 3 formats together
     *               5) Libyan way
     *               6) Algeria and Tunis style
     *               7) Morocco style          
     *               8) Hijri format (Islamic calendar) in English
     *                           
     * @return Integer Value of $mode properity
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function getMode()
    {
        return $this->_mode;
    }
    
    /**
     * Format a local time/date in Arabic string
     *      
     * @param string  $format     Format string (same as PHP date function)
     * @param integer $timestamp  Unix timestamp
     * @param integer $correction To apply correction factor (+/- 1-2) to
     *                            standard hijri calendar
     *                    
     * @return string Format Arabic date string according to given format string
     *                using the given integer timestamp or the current local
     *                time if no timestamp is given.
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function date($format, $timestamp, $correction = 0)
    {
        if ($this->_mode == 1 || $this->_mode == 8) {
            if ($this->_mode == 1) {
                foreach ($this->_xml->ar_hj_month->month as $month) {
                    $hj_txt_month["{$month['id']}"] = (string)$month;
                } 
            }
            
            if ($this->_mode == 8) {
                foreach ($this->_xml->en_hj_month->month as $month) {
                    $hj_txt_month["{$month['id']}"] = (string)$month;
                } 
            }
            
            $patterns     = array();
            $replacements = array();
            
            array_push($patterns, 'Y');
            array_push($replacements, 'x1');
            array_push($patterns, 'y');
            array_push($replacements, 'x2');
            array_push($patterns, 'M');
            array_push($replacements, 'x3');
            array_push($patterns, 'F');
            array_push($replacements, 'x3');
            array_push($patterns, 'n');
            array_push($replacements, 'x4');
            array_push($patterns, 'm');
            array_push($replacements, 'x5');
            array_push($patterns, 'j');
            array_push($replacements, 'x6');
            array_push($patterns, 'd');
            array_push($replacements, 'x7');
            
            if ($this->_mode == 8) {
                array_push($patterns, 'S');
                array_push($replacements, '');
            }
            
            $format = str_replace($patterns, $replacements, $format);
            
            $str = date($format, $timestamp);
            if ($this->_mode == 1) {
                $str = $this->en2ar($str);
            }

            $timestamp       = $timestamp + 3600*24*$correction;
            list($Y, $M, $D) = explode(' ', date('Y m d', $timestamp));
            
            list($hj_y, $hj_m, $hj_d) = $this->hjConvert($Y, $M, $D);
            
            $patterns     = array();
            $replacements = array();
            
            array_push($patterns, 'x1');
            array_push($replacements, $hj_y);
            array_push($patterns, 'x2');
            array_push($replacements, substr($hj_y, -2));
            array_push($patterns, 'x3');
            array_push($replacements, $hj_txt_month[$hj_m]);
            array_push($patterns, 'x4');
            array_push($replacements, $hj_m);
            array_push($patterns, 'x5');
            array_push($replacements, sprintf('%02d', $hj_m));
            array_push($patterns, 'x6');
            array_push($replacements, $hj_d);
            array_push($patterns, 'x7');
            array_push($replacements, sprintf('%02d', $hj_d));
            
            $str = str_replace($patterns, $replacements, $str);
        } elseif ($this->_mode == 5) {
            $year  = date('Y', $timestamp);
            $year -= 632;
            $yr    = substr("$year", -2);
            
            $format = str_replace('Y', $year, $format);
            $format = str_replace('y', $yr, $format);
            
            $str = date($format, $timestamp);
            $str = $this->en2ar($str);

        } else {
            $str = date($format, $timestamp);
            $str = $this->en2ar($str);
        }
        
        if (0) {
            if ($outputCharset == null) { 
                $outputCharset = $main->getOutputCharset(); 
            }
            $str = $main->coreConvert($str, 'utf-8', $outputCharset);
        }

        return $str;
    }
    
    /**
     * Translate English date/time terms into Arabic langauge
     *      
     * @param string $str Date/time string using English terms
     *      
     * @return string Date/time string using Arabic terms
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function en2ar($str)
    {
        $patterns     = array();
        $replacements = array();
        
        $str = strtolower($str);
        
        foreach ($this->_xml->xpath("//en_day/mode[@id='full']/search") as $day) {
            array_push($patterns, (string)$day);
        } 

        foreach ($this->_xml->ar_day->replace as $day) {
            array_push($replacements, (string)$day);
        } 

        foreach (
            $this->_xml->xpath("//en_month/mode[@id='full']/search") as $month
        ) {
            array_push($patterns, (string)$month);
        } 

        $replacements = array_merge(
            $replacements, 
            $this->arabicMonths($this->_mode)
        );
        
        foreach ($this->_xml->xpath("//en_day/mode[@id='short']/search") as $day) {
            array_push($patterns, (string)$day);
        } 

        foreach ($this->_xml->ar_day->replace as $day) {
            array_push($replacements, (string)$day);
        } 

        foreach ($this->_xml->xpath("//en_month/mode[@id='short']/search") as $m) {
            array_push($patterns, (string)$m);
        } 
        
        $replacements = array_merge(
            $replacements, 
            $this->arabicMonths($this->_mode)
        );
    
        foreach (
            $this->_xml->xpath("//preg_replace[@function='en2ar']/pair") as $p
        ) {
            array_push($patterns, (string)$p->search);
            array_push($replacements, (string)$p->replace);
        } 

        $str = str_replace($patterns, $replacements, $str);
        
        return $str;
    }

    /**
     * Add Arabic month names to the replacement array
     *      
     * @param integer $mode Naming mode of months in Arabic where:
     *                       2) Arabic month names used in Middle East countries
     *                       3) Arabic Transliteration of Gregorian month names
     *                       4) Both of 2 and 3 formats together
     *                       5) Libya style
     *                       6) Algeria and Tunis style
     *                       7) Morocco style          
     *                                   
     * @return array Arabic month names in selected style
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function arabicMonths($mode)
    {
        $replacements = array();

        foreach (
            $this->_xml->xpath("//ar_month/mode[@id=$mode]/replace") as $month
        ) {
            array_push($replacements, (string)$month);
        } 

        return $replacements;
    }
    
    /**
     * Convert given Gregorian date into Hijri date
     *      
     * @param integer $Y Year Gregorian year
     * @param integer $M Month Gregorian month
     * @param integer $D Day Gregorian day
     *      
     * @return array Hijri date [int Year, int Month, int Day](Islamic calendar)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function hjConvert($Y, $M, $D)
    {
        if (function_exists('GregorianToJD')) {
            $jd = GregorianToJD($M, $D, $Y);
        } else {
            $jd = $this->gregToJd($M, $D, $Y);
        }
        
        list($year, $month, $day) = $this->jdToIslamic($jd);
        
        return array($year, $month, $day);
    }
    
    /**
     * Convert given Julian day into Hijri date
     *      
     * @param integer $jd Julian day
     *      
     * @return array Hijri date [int Year, int Month, int Day](Islamic calendar)
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function jdToIslamic($jd)
    {
        $l = (int)$jd - 1948440 + 10632;
        $n = (int)(($l - 1) / 10631);
        $l = $l - 10631 * $n + 354;
        $j = (int)((10985 - $l) / 5316) * (int)((50 * $l) / 17719) 
            + (int)($l / 5670) * (int)((43 * $l) / 15238);
        $l = $l - (int)((30 - $j) / 15) * (int)((17719 * $j) / 50) 
            - (int)($j / 16) * (int)((15238 * $j) / 43) + 29;
        $m = (int)((24 * $l) / 709);
        $d = $l - (int)((709 * $m) / 24);
        $y = (int)(30 * $n + $j - 30);
        
        return array($y, $m, $d);
    }
    
    /**
     * Convert given Hijri date into Julian day
     *      
     * @param integer $year  Year Hijri year
     * @param integer $month Month Hijri month
     * @param integer $day   Day Hijri day
     *      
     * @return integer Julian day
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function islamicToJd($year, $month, $day)
    {
        $jd = (int)((11 * $year + 3) / 30) + (int)(354 * $year) + (int)(30 * $month) 
            - (int)(($month - 1) / 2) + $day + 1948440 - 385;
        return $jd;
    }
    
    /**
     * Converts a Gregorian date to Julian Day Count
     *      
     * @param integer $m The month as a number from 1 (for January) 
     *                   to 12 (for December) 
     * @param integer $d The day as a number from 1 to 31
     * @param integer $y The year as a number between -4714 and 9999
     *       
     * @return integer The julian day for the given gregorian date as an integer
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    protected function gregToJd ($m, $d, $y)
    {
        if ($m < 3) {
            $y--;
            $m += 12;
        }
        
        if (($y < 1582) || ($y == 1582 && $m < 10) 
            || ($y == 1582 && $m == 10 && $d <= 15)
        ) {
            // This is ignored in the GregorianToJD PHP function!
            $b = 0;
        } else {
            $a = (int)($y / 100);
            $b = 2 - $a + (int)($a / 4);
        }
        
        $jd = (int)(365.25 * ($y + 4716)) + (int)(30.6001 * ($m + 1)) 
            + $d + $b - 1524.5;
        
        return round($jd);
    }

    /**
     * Calculate Hijri calendar correction using Um-Al-Qura calendar information
     *      
     * @param integer $time Unix timestamp
     *       
     * @return integer Correction factor to fix Hijri calendar calculation using
     *                 Um-Al-Qura calendar information     
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    public function dateCorrection ($time)
    {
        $calc = $time - $this->date('j', $time) * 3600 * 24;
        
        $file = dirname(__FILE__).'/data/um_alqoura.txt';

        $content = file_get_contents($file);

        $y      = $this->date('Y', $time);
        $m      = $this->date('n', $time);
        $offset = (($y-1420) * 12 + $m) * 11;
        
        $d = substr($content, $offset, 2);
        $m = substr($content, $offset+3, 2);
        $y = substr($content, $offset+6, 4);
        
        $real = mktime(0, 0, 0, $m, $d, $y);
        
        $diff = (int)(($calc - $real) / (3600 * 24));
        
        return $diff;
    }
}