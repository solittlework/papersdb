<?php

 /**
  * $Id: aicml_pubs_base.php,v 1.12 2008/02/12 18:08:27 loyola Exp $
  *
  * Script that reports statistics for thepublications made by AICML PIs, PDFs,
  * students and staff.
  *
  * @package PapersDB
  */

/** Requries the base class and classes to access the database. */
require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Displays various sets of statistics for the machine learning papers
 * published by AICML PIs, PDFs, students and staff.
 *
 * @package PapersDB
 */
class aicml_pubs_base extends pdHtmlPage {
    protected static $fiscal_years = array(
        array('2002-09-01', '2003-08-31'),
        array('2003-09-01', '2004-08-31'),
        array('2004-09-01', '2006-03-31'),
        array('2006-04-01', '2007-03-31'),
        array('2007-04-01', '2008-03-31'),
        array('2008-04-01', '2009-03-31'),
        array('2009-04-01', '2010-03-31'));
   
    protected $aicml_pi_authors;
    protected $aicml_pdf_students_staff_authors;
    protected $aicml_pi_dates;
    protected $aicml_pdf_students_staff_dates;
    
    /**
     * Base class constructor.
     *
     * @param string $page_id The page ID. If defined in pdNavMenu then it is
     * displayed in the navigation menu.
     * @param string $title
     * @param string $relative_url
     * @param string $login_level
     */
    public function __construct($page_id, $title = null, $relative_url = null,
                                $login_level = pdNavMenuItem::MENU_NEVER) {
        parent::__construct($page_id, $title, $relative_url, $login_level);

        $this->fiscal_year_ts = array();
        foreach (self::$fiscal_years as $key => $fy) {
            $this->fiscal_year_ts[$key] = array(date2Timestamp($fy[0]),
                                                date2Timestamp($fy[1]));
        }
    }
    
    /**
     * Retrieves the publications entries with keyword "machine learning" from 
     * the database.*
     *
     * @return an associative array with publication IDs for keys and 
     * their corresponding pdPublication objects for values.
     */
    protected function getMachineLearningPapers() {        
        $qry_str = <<<QRY_END
SELECT publication.pub_id
FROM publication 
inner join  pub_author on pub_author.pub_id=publication.pub_id 
inner join aicml_staff on aicml_staff.author_id=pub_author.author_id
inner join pub_cat on publication.pub_id=pub_cat.pub_id
left join pub_pending on publication.pub_id=pub_pending.pub_id
where publication.keywords rlike "mach.*learn.*" 
and publication.rank_id in (1, 2, 3)
and pub_cat.cat_id in (1, 3)
and pub_pending.pub_id is NULL
and publication.published >= '%s'
QRY_END;

        $qry_str = sprintf($qry_str, self::$fiscal_years[0][0]);                
        $q = $this->db->query($qry_str);
        if (!$q) return false;
        assert('count($q) > 0');
        $pub_ids = array();
        foreach ($q as $r) {
            $pub_ids[] = $r->pub_id;
        }
        return pdPubList::create($this->db, array('pub_ids' => $pub_ids, 'sort' => true));
    }    
    
    /**
     * Retrieves the publications entries with keyword "machine learning" from 
     * the database.*
     *
     * @return an associative array with publication IDs for keys and 
     * their corresponding pdPublication objects for values.
     */
    protected function getMachineLearningPapersNoRank() {        
        $qry_str = <<<QRY_END
SELECT publication.pub_id
FROM publication 
inner join  pub_author on pub_author.pub_id=publication.pub_id 
inner join aicml_staff on aicml_staff.author_id=pub_author.author_id
inner join pub_cat on publication.pub_id=pub_cat.pub_id
left join pub_pending on publication.pub_id=pub_pending.pub_id
where publication.keywords rlike "mach.*learn.*" 
and pub_cat.cat_id in (1, 3)
and pub_pending.pub_id is NULL
and publication.published >= '%s'
QRY_END;

        $qry_str = sprintf($qry_str, self::$fiscal_years[0][0]);
        $q = $this->db->query($qry_str);
        if (!$q) return false;
        assert('count($q) > 0');
        $pub_ids = array();
        foreach ($q as $r) {
            $pub_ids[] = $r->pub_id;
        }
        return pdPubList::create($this->db, array('pub_ids' => $pub_ids, 'sort' => true));
    }    

    protected function getAllAicmlAuthoredPapers() {
        $pubs = array();
        // first get publications by PIs
        foreach (self::$aicml_authors['pi'] as $name) {
            $author_pubs = pdPubList::create($this->db,                                    
                array('author_name' => $name,                                                        
                	  'date_start' => self::$author_dates[$name][0],
                      'date_end' => self::$author_dates[$name][1],
                      'pub_id_keys' => true,
                      'keyword' => 'machine learning'));
            $pubs = $this->pubsArrayMerge($pubs, $author_pubs);
        }

        // now get publications by AICML PDFs, students and staff members
     	$this->getPdfStudentsAndStaff();
        foreach ($this->aicml_pdf_students_staff_authors as $author) {
            $author_pubs
                = pdPubList::create($this->db,
                                    array('author_name' => $author,
                                          'date_start' => self::$fiscal_years[4][0],
                                          'date_end' => self::$fiscal_years[0][1],
                                          'pub_id_keys' => true));
            $pubs = $this->pubsArrayMerge($pubs, $author_pubs);
        }
        return $pubs;
    }

    /**
     * Adds the publications in $pubs2 that are not already in $pubs1.
     *
     * @param array $pubs1 an associative array with publication IDs for keys 
     * and their corresponding pdPublication objects for values.
     * @param array $pubs2 an associative array with publication IDs for keys 
     * and their corresponding pdPublication objects for values.
     * @return the merged array 
     */
    protected function pubsArrayMerge($pubs1, $pubs2) {
        assert('is_array($pubs1)');
        assert('is_array($pubs2)');
 
        $result = $pubs1;
        $diffs = array_diff(array_keys($pubs2), array_keys($pubs1));
        foreach ($diffs as $pub_id) {
            $result[$pub_id] = $pubs2[$pub_id];
        }
        return $result;
    }

    /**
     * Returns all the AICML personnel names in the database.
     */
    public function getPiAuthors() {
        if (isset($this->aicml_pi_authors)) return;
        
        $q = $this->db->select(
        	array('aicml_staff', 'author'),
        	array('author.author_id', 
        		'author.name', 
        		'aicml_staff.start_date', 
        		'aicml_staff.end_date'),
        	array('aicml_staff.pos_id=1', 
        		'aicml_staff.author_id=author.author_id'));
        
        $this->aicml_pi_authors = array();
        foreach ($q as $r) {
        	$this->aicml_pi_authors[$r->author_id] = utf8_encode($r->name);
        	$this->aicml_pi_dates[$r->author_id] = array(
        		date2Timestamp($r->start_date), 
        		($r->end_date != null) ? date2Timestamp($r->end_date) : -1);
        }
    }

    /**
     * Returns all the AICML personnel names in the database.
     */
    public function getPdfStudentsAndStaffAuthors() {
        if (isset($this->aicml_pdf_students_staff_authors)) return;
        
        $q = $this->db->select(
        	array('aicml_staff', 'author'),
        	array('author.author_id', 
        		'author.name', 
        		'aicml_staff.start_date', 
        		'aicml_staff.end_date'),
        	array('aicml_staff.pos_id!=1', 
        		'aicml_staff.author_id=author.author_id'));
        
        $this->aicml_pdf_students_staff_authors = array();
        foreach ($q as $r) {
        	$this->aicml_pdf_students_staff_authors[$r->author_id] = $r->name;
        	$this->aicml_pdf_students_staff_dates[$r->author_id] = array(
        		date2Timestamp($r->start_date), 
        		($r->end_date != null) ? date2Timestamp($r->end_date) : -1);
        }
    }

    /**
     * Returns the corresponding fiscal year for the date passed in.
     * 
     * @param string $date in YYYY-MM-DD format.
     * @return unknown
     */
    protected function getFiscalYearKey($date) {
        $datestamp = date2Timestamp($date);
        foreach ($this->fiscal_year_ts as $key => $fyts) {
            if (($fyts[0] <= $datestamp) && ($fyts[1] >= $datestamp))
                return $key;
        }
        //throw new Exception("date not within fiscal years: " + $date);
        return false;
    }
}

?>
