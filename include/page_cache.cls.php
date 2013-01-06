<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Caches individual rendered PDF pages
 *
 * Not totally implemented yet.  Use at your own risk ;)
 * 
 * @access private
 * @package dompdf
 * @static
 */
class Page_Cache {

  const DB_USER = "dompdf_page_cache";
  const DB_PASS = "some meaningful password";
  const DB_NAME = "dompdf_page_cache";
  
  static private $__connection = null;
  
  static function init() {
    if ( is_null(self::$__connection) ) {
      $con_str = "host=" . DB_HOST .
        " dbname=" . self::DB_NAME .
        " user=" . self::DB_USER .
        " password=" . self::DB_PASS;
      
      if ( !self::$__connection = pg_connect($con_str) )
        throw new Exception("Database connection failed.");
    }
  }
  
  function __construct() { throw new Exception("Can not create instance of Page_Class.  Class is static."); }

  private static function __query($sql) {
    if ( !($res = pg_query(self::$__connection, $sql)) )
      throw new Exception(pg_last_error(self::$__connection));
    return $res;
  }
  
  static function store_page($id, $page_num, $data) {
    $where = "WHERE id='" . pg_escape_string($id) . "' AND ".
      "page_num=". pg_escape_string($page_num);

    $res = self::__query("SELECT timestamp FROM page_cache ". $where);

    $row = pg_fetch_assoc($res);
    
    if ( $row ) 
      self::__query("UPDATE page_cache SET data='" . pg_escape_string($data) . "' " . $where);
    else 
      self::__query("INSERT INTO page_cache (id, page_num, data) VALUES ('" . pg_escape_string($id) . "', ".
                     pg_escape_string($page_num) . ", ".
                     "'". pg_escape_string($data) . "')");

  }

  static function store_fonts($id, $fonts) {
    self::__query("BEGIN");
    // Update the font information
    self::__query("DELETE FROM page_fonts WHERE id='" . pg_escape_string($id) . "'");

    foreach (array_keys($fonts) as $font)
      self::__query("INSERT INTO page_fonts (id, font_name) VALUES ('" .
                    pg_escape_string($id) . "', '" . pg_escape_string($font) . "')");
    self::__query("COMMIT");
  }
  
//   static function retrieve_page($id, $page_num) {

//     $res = self::__query("SELECT data FROM page_cache WHERE id='" . pg_escape_string($id) . "' AND ".
//                           "page_num=". pg_escape_string($page_num));

//     $row = pg_fetch_assoc($res);

//     return pg_unescape_bytea($row["data"]);
    
//   }

  static function get_page_timestamp($id, $page_num) {
    $res = self::__query("SELECT timestamp FROM page_cache WHERE id='" . pg_escape_string($id) . "' AND ".
                          "page_num=". pg_escape_string($page_num));

    $row = pg_fetch_assoc($res);

    return $row["timestamp"];
    
  }

  // Adds the cached document referenced by $id to the provided pdf
  static function insert_cached_document(CPDF_Adapter $pdf, $id, $new_page = true) {
    $res = self::__query("SELECT font_name FROM page_fonts WHERE id='" . pg_escape_string($id) . "'");

    // Ensure that the fonts needed by the cached document are loaded into
    // the pdf
    while ($row = pg_fetch_assoc($res)) 
      $pdf->get_cpdf()->selectFont($row["font_name"]);
    
    $res = self::__query("SELECT data FROM page_cache WHERE id='" . pg_escape_string($id) . "'");

    if ( $new_page )
      $pdf->new_page();

    $first = true;
    while ($row = pg_fetch_assoc($res)) {

      if ( !$first ) 
        $pdf->new_page();
      else 
        $first = false;        
      
      $page = $pdf->reopen_serialized_object($row["data"]);
      //$pdf->close_object();
      $pdf->add_object($page, "add");

    }
      
  }
}

Page_Cache::init();
