<?php ;

// $Id: add_pub_base.php,v 1.3 2007/03/12 23:05:43 aicmltec Exp $

/**
 * Common functions used by pages for adding a new publication.
 *
 * @package PapersDB
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';
require_once 'includes/functions.php';


class add_pub_base extends pdHtmlPage {
    var $pub;
    var $pub_id;

    function add_pub_base() {
        if ($this->pub->pub_id != '')
            parent::pdHtmlPage('edit_publication');
        else
            parent::pdHtmlPage('add_publication');

        if ($this->loginError) return;

        if ((get_class($this) == "add_pub2")
            || (get_class($this) == "add_pub3")
            || (get_class($this) == "add_pub4")) {
            if ($_SESSION['state'] != 'pub_add') {
                header('Location: add_pub1.php');
                return;
            }
        }

        $this->addPubDisableMenuItems();
    }

    /**
     * This is a static function.
     */
    function similarPubsHtml() {
        if (!isset($_SESSION['similar_pubs'])) return;

        $html = '<h3>Similar Publications in Database</h3>';
        foreach ($_SESSION['similar_pubs'] as $sim_pub_id) {
            $sim_pub = new pdPublication();
            $sim_pub->dbLoad($this->db, $sim_pub_id);

            $html .= $sim_pub->getCitationHtml('..', false) . '<p/>';
        }

        return $html;
    }

    function addPubDisableMenuItems() {
        $this->navMenuItemEnable('add_publication', 0);
        $this->navMenuItemDisplay('add_author', 0);
        $this->navMenuItemDisplay('add_category', 0);
        $this->navMenuItemDisplay('add_venue', 0);
    }
}