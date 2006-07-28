<?php ;

// $Id: delete_publication.php,v 1.7 2006/07/28 22:10:49 aicmltec Exp $

/**
 * \file
 *
 * \brief Deletes a publication from the database.
 *
 * This page confirms that the user would like to delete the following
 * publication and then removes it from the database once confirmation has been
 * given.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 */
class delete_publication extends pdHtmlPage {
    function delete_publication() {
        global $logged_in;

        parent::pdHtmlPage('delete_publication');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        $pub_id = null;
        if (isset($_GET['pub_id']) && ($_GET['pub_id'] != ''))
            $pub_id = intval($_GET['pub_id']);

        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'pub_id', $pub_id);

        if ($form->validate()) {
            $values = $form->exportValues();

            $db =& dbCreate();
            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $values['pub_id']);
            if (!$result) {
                $this->pageError = true;
                $db->close();
                return;
            }


            $title = $pub->title;
            $pub->dbDelete($db);

            $this->contentPre .= 'You have successfully removed the following '
                . 'publication from the database: <p/><b>' . $title . '</b>';
        }
        else {
            if ($pub_id == null) {
                $this->contentPre .= 'No pub id defined';
                $this->pageError = true;
                return;
            }

            $db =& dbCreate();
            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id);
            if (!$result) {
                $db->close();
                $this->pageError = true;
                $db->close();
                return;
            }

            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $form->accept($renderer);

            $this->contentPre .= '<h3>Delete Publication</h3>'
                . 'Delete the following paper?<p/>'
                . $pub->getCitationHtml();

            $this->form =& $form;
            $this->renderer =& $renderer;
        }
        $db->close();
    }
}

session_start();
$logged_in = check_login();
$page = new delete_publication();
echo $page->toHtml();

?>