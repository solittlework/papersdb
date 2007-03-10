<?php ;

// $Id: delete_author.php,v 1.15 2007/03/10 01:23:05 aicmltec Exp $

/**
 * Deletes an author from the database.
 *
 * This page first confirms that the user would like to delete the specified
 * author, and then makes the actual deletion. Before the author can removed
 * though, it must not be in any publications. This is checked, and displays
 * the titles of all the publications the author is in.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthor.php';
require_once('HTML/QuickForm/Renderer/QuickHtml.php');

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class delete_author extends pdHtmlPage {
    function delete_author() {
        global $access_level;

        pubSessionInit();
        parent::pdHtmlPage('delete_author');

        if ($access_level <= 0) {
            $this->loginError = true;
            return;
        }

        $form =& $this->confirmForm('deleter');

        if (isset($_GET['author_id']) && ($_GET['author_id'] != '')) {
            $author_id = intval($_GET['author_id']);
            $form->addElement('hidden', 'author_id', $author_id);
        }

        if ($form->validate()) {
            $values = $form->exportValues();

            $db = dbCreate();
            $author = new pdAuthor();
            $result = $author->dbLoad($db, $values['author_id']);
            if (!$result) {
                $db->close();
                $this->pageError = true;
                return;
            }

            if (isset($author->pub_list) && (count($author->pub_list) > 0)) {
                $this->contentPre .= '<b>Deletion Failed</b><p/>'
                    . 'This author is listed as author for the following '
                    . 'publications:<p/>';

                foreach ($author->pub_list->list as $pub)
                    $this->contentPre .= '<b>' . $pub->title . '</b><br/>';

                $this->contentPre
                    .= '<p/>You must change or remove the author of the '
                    . 'following publication(s) in order to delete this author.';
            }
            else {
                // This is where the actual deletion happens.
                $name = $author->name;
                $author->dbDelete($db);

                $this->contentPre .= 'You have successfully removed the '
                    . 'following author from the database: <p/>'
                    . '<b>' . $name . '</b>';
            }
        }
        else {
            if ($author_id == null) {
                $this->contentPre .= 'No author id defined';
                $this->pageError = true;
                return;
            }

            $db = dbCreate();
            $author = new pdAuthor();
            $result = $author->dbLoad($db, $author_id);
            if (!$result) {
                $db->close();
                $this->pageError = true;
                return;
            }

            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $form->accept($renderer);

            $table = new HTML_Table(array('width' => '100%',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));

            $table->addRow(array('Name:', $author->name));

            if (isset($author->title) && trim($author->title != ''))
                $table->addRow(array('Title:', $author->title));

            $table->addRow(array('Email:', $author->email));
            $table->addRow(array('Organization:', $author->organization));
            $cell = '';

            if (isset($author->webpage) && trim($author->webpage != ""))
                $cell = '<a href="' . $author->webpage . '">'
                    . $author->webpage . '</a>';
            else
                $cell = "none";

            $table->addRow(array('Web page:', $cell));
            $table->updateColAttributes(0, array('id' => 'emph',
                                                 'width' => '25%'));

            $this->contentPre .= '<h3>Delete Author</h3><p/>'
                . 'Delete the following author?';

            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->table =& $table;
        }
        $db->close();
    }
}

session_start();
$access_level = check_login();
$page = new delete_author();
echo $page->toHtml();

?>