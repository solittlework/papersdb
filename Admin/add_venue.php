<?php ;

// $Id: add_venue.php,v 1.25 2007/03/08 01:00:52 aicmltec Exp $

/**
 * This page displays, edits and adds venues.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_venue extends pdHtmlPage {
    var $debug = 0;
    var $venue_id = null;

    function add_venue() {
        global $access_level;

        parent::pdHtmlPage('add_venue');

        if ($access_level <= 0) {
            $this->loginError = true;
            return;
        }

        $db =& dbCreate();

        $venue = new pdVenue();

        if (isset($_GET['venue_id']) && ($_GET['venue_id'] != '')) {
            $this->venue_id = intval($_GET['venue_id']);
        }
        else if (isset($_POST['venue_id']) && ($_POST['venue_id'] != '')) {
            $this->venue_id = intval($_POST['venue_id']);
        }

        if ($this->venue_id != null)
            $venue->dbLoad($db, $this->venue_id);

        if (isset($_GET['type']) && ($_GET['type'] != ''))
            $venue->type = $_GET['type'];
        else if (isset($_POST['type']) && ($_POST['type'] != ''))
            $venue->type = $_POST['type'];

        $newOccurrences = 0;
        if (($venue->type == 'Conference') || ($venue->type == 'Workshop')) {
            if (isset($_GET['numNewOccurrences'])
                && is_numeric($_GET['numNewOccurrences'])) {
                $newOccurrences =  intval($_GET['numNewOccurrences']);
            }
            else if (isset($_POST['numNewOccurrences'])
                     && is_numeric($_POST['numNewOccurrences'])) {
                $newOccurrences =  intval($_POST['numNewOccurrences']);
            }
            else {
                $newOccurrences = count($venue->occurrences);
            }
        }

        $form = new HTML_QuickForm('venueForm', 'post',
                                   './add_venue.php?submit=true');

        if ($this->venue_id != '')
            $label = 'Edit Venue';
        else
            $label = 'Add Venue';

        $this->pageTitle = $label;

        if (($venue->type == 'Conference') || ($venue->type == 'Workshop'))
            $label .= '&nbsp;<span id="small"><a href="javascript:dataKeep('
                . ($newOccurrences+1) .')">[Add Occurrence]</a></span>';

        $form->addElement('header', null, $label);

        if ($this->venue_id != '') {
            $form->addElement('hidden', 'venue_id', $this->venue_id);
        }

        $form->addElement('radio', 'type', 'Type:', 'Journal', 'Journal',
                          array('onClick'
                                => 'dataKeep(' . $newOccurrences . ');'));
        $form->addElement('radio', 'type', null, 'Conference', 'Conference',
                          array('onClick'
                                => 'dataKeep(' . $newOccurrences . ');'));
        $form->addElement('radio', 'type', null, 'Workshop', 'Workshop',
                          array('onClick'
                                => 'dataKeep(' . $newOccurrences . ');'));
        $form->addElement('text', 'title', 'Internal Title:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('title', 'a venue title is required', 'required',
                       null, 'client');
        $form->addElement('text', 'name', 'Venue Name:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('name', 'a venue name is required', 'required',
                       null, 'client');
        $form->addRule('name', 'venue name cannot be left blank',
                       'required', null, 'client');
        $form->addElement('text', 'url', 'Venue URL:',
                          array('size' => 50, 'maxlength' => 250));

        if ($venue->type != '') {
            if (($venue->type == 'Journal') || ($venue->type == 'Workshop')) {
                if ($venue->type == 'Journal')
                    $label = 'Publisher:';
                else
                    $label = 'Associated Conference:';

                $form->addElement('text', 'data', $label,
                                  array('size' => 50, 'maxlength' => 250));
            }

            if ($venue->type == 'Workshop') {
                $form->addElement('text', 'editor', 'Editor:',
                                  array('size' => 50, 'maxlength' => 250));

                $form->addElement('date', 'venue_date', 'Date:',
                                  array('format' => 'YM', 'minYear' => '1985'));
            }

            if (($venue->type == 'Conference')
                || ($venue->type == 'Workshop')) {
                $form->addElement('hidden', 'numNewOccurrences',
                                  $newOccurrences);

                for ($i = 0; $i < $newOccurrences; $i++) {

                    $form->addElement('header', null, 'Occurrence ' . ($i + 1));
                    $form->addElement('text',
                                      'newOccurrenceLocation[' . $i . ']',
                                      'Location:',
                                      array('size' => 50, 'maxlength' => 250));
                    $form->addRule('newOccurrenceLocation[' . $i . ']',
                                   'venue occurrence ' . ($i + 1)
                                   . ' location cannot be left blank',
                                   'required', null, 'client');

                    $form->addElement('date', 'newOccurrenceDate[' . $i . ']',
                                      'Date:',
                                      array('format' => 'YM',
                                            'minYear' => '1985'));

                    $form->addElement('text',
                                      'newOccurrenceUrl[' . $i . ']',
                                      'URL:',
                                      array('size' => 50, 'maxlength' => 250));

                    $form->addElement('button', 'delOccurrence[' . $i . ']',
                                      'Delete',
                                      'onClick=dataRemove(' . $i . ');');
                }
            }
        }

        if ($_SESSION['state'] == 'pub_add') {
            $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
            $prev_page = substr($_SERVER['PHP_SELF'], 0, $pos)
                . 'papersdb/Admin/add_pub1.php';
            $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

            $buttons[] = HTML_QuickForm::createElement(
                'button', 'prev_step', '<< Previous Step',
                array('onClick' => "location.href='"
                      . $prev_page . "';"));
            $buttons[] = HTML_QuickForm::createElement(
                'button', 'cancel', 'Cancel',
                array('onclick' => "location.href='" . $url . "';"));
            $buttons[] = HTML_QuickForm::createElement(
                'reset', 'reset', 'Reset');
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'next_step', 'Next Step >>');

            $pub =& $_SESSION['pub'];

            if ($pub->pub_id != '')
                $buttons[] = HTML_QuickForm::createElement(
                    'submit', 'finish', 'Finish');

            $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

            $this->addPubDisableMenuItems();
        }
        else {
            if ($this->venue_id != '')
                $label = 'Submit';
            else
                $label = 'Add Venue';

            $form->addGroup(
                array(
                    HTML_QuickForm::createElement('submit', 'Submit', $label),
                    HTML_QuickForm::createElement('reset', 'Reset', 'Reset')
                    ),
                'submit_group', null, '&nbsp;', false);
        }

        if ($form->validate()) {
            $values = $form->exportValues();
            $venue->load($values);

            if ($this->debug) {
                $this->contentPre .= '<pre>' . print_r($values, true)
                    . '</pre>';
            }
            else {
                //add http:// to webpage address if needed
                if (($venue->url != '')
                    && (strpos($venue->url, 'http') === false)) {
                    $venue->url = "http://" . $venue->url;
                }
                $venue->title = str_replace("\"","'", $venue->title);

                if (($venue->type == 'Conference')
                    || ($venue->type == 'Workshop')) {
                    $venue->date = $values['venue_date']['Y']
                        . '-' . $values['venue_date']['M'] . '-1';
                }

                $venue->deleteOccurrences();
                for ($i = 0; $i < $values['numNewOccurrences']; $i++) {
                    $venue->addOccurrence(
                        $values['newOccurrenceLocation'][$i],
                        $values['newOccurrenceDate'][$i]['Y']
                        . '-' . $values['newOccurrenceDate'][$i]['M']
                        . '-1',
                        $values['newOccurrenceUrl'][$i]);
                }

                $venue->dbSave($db);

                if ($_SESSION['state'] == 'pub_add') {
                    assert('isset($_SESSION["pub"])');
                    $pub =& $_SESSION['pub'];
                    $pub->addVenue($db, $venue);

                    $this->contentPost .= '<pre>' . print_r($_SESSION, true) . '</pre>';

                    if ($this->debug) return;

                    if (isset($values['finish']))
                        header('Location: add_pub_submit.php');
                    else
                        header('Location: add_pub2.php');
                }
                else {
                    if (!isset($this->venue_id) || ($this->venue_id == '')) {
                        $this->contentPre
                            .= 'You have successfully added the venue "'
                            .  $venue->title . '".'
                            . '<br><a href="./add_venue.php">Add another venue</a>';
                    }
                    else {
                        $this->contentPre
                            .= 'You have successfully edited the venue "'
                            . $venue->title . '".';
                    }
                }
            }
        }
        else {
            $form->setConstants($_GET);
            if ($this->venue_id != '') {
                $arr = array('title'      => $venue->title,
                             'name'       => $venue->nameGet(),
                             'url'        => $venue->urlGet(),
                             'type'       => $venue->type,
                             'data'       => $venue->data,
                             'editor'     => $venue->editor,
                             'venue_date' => $venue->date);
                if (isset($_GET['numNewOccurrences'])) {
                    for ($i = 0; $i < $_GET['numNewOccurrences']; $i++) {
                        $arr['newOccurrenceLocation'][$c]
                            = $_GET['newOccurrenceLocation'][$c];
                        $arr['newOccurrenceDate'][$c]
                            = $_GET['newOccurrenceDate'][$c];
                        $arr['newOccurrenceUrl'][$c]
                            = $_GET['newOccurrenceUrl'][$c];
                    }
                }
                else if (count($venue->occurrences) > 0) {
                    $c = 0;
                    foreach ($venue->occurrences as $o) {
                        $arr['newOccurrenceLocation'][$c] = $o->location;
                        $arr['newOccurrenceDate'][$c] = $o->date;
                        $arr['newOccurrenceUrl'][$c] = $o->url;
                        $c++;
                    }
                }

                // set the default date for the new occurrences
                if (count($venue->occurrences) < $newOccurrences) {
                    $curdate = array('Y' => date('Y'), 'M' => date('m'));
                    for ($i = count($venue->occurrences);
                         $i < $newOccurrences; ++$i)
                        $arr['newOccurrenceDate'][$i] = $curdate;
                }

                $form->setConstants($arr);
            }
            else {
                $curdate = array('Y' => date('Y'), 'M' => date('m'));
                $arr = array('venue_date' => $curdate);
                for ($i = 0; $i < $newOccurrences; ++$i)
                    $arr['newOccurrenceDate'][$i] = $curdate;
                $form->setConstants($arr);
            }

            if ($_SESSION['state'] == 'pub_add') {
                assert('isset($_SESSION["pub"])');
                $pub =& $_SESSION['pub'];

                $this->contentPre .= '<h3>Publication Information</h3>'
                    . $pub->getCitationHtml('..', false) . '<p/>';
            }

            $renderer =& $form->defaultRenderer();

            $renderer->setFormTemplate(
                '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
                . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
            $renderer->setHeaderTemplate(
                '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
                . 'align="left" colspan="2"><b>{header}</b></td></tr>');

            $form->accept($renderer);

            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->table =& $table;
            $this->javascript();
        }
        $db->close();
    }

    function javascript() {
        $this->js = <<< JS_END
            <script language="JavaScript" type="text/JavaScript">

            function closewindow() {
            window.close();
        }

        function dataKeep(num) {
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < document.forms["venueForm"].elements.length; i++) {
                var element = document.forms["venueForm"].elements[i];
                if ((element.type != "submit") && (element.type != "reset")
                    && (element.type != "button")
                    && (element.value != "") && (element.value != null)) {

                    if (element.name == "venue_id") {
                        qsArray.push(element.name + "=" + element.value);
                        qsArray.push("status=change");
                    }
                    else if (element.name == "type") {
                        if (element.checked) {
                            qsArray.push(element.name + "="
                                         + element.value.replace("\"","'"));
                        }
                    }
                    else if (element.name == "numNewOccurrences") {
                        qsArray.push(element.name + "=" + num);
                    }
                    else {
                        qsArray.push(element.name + "=" + element.value);
                    }
                }
            }

            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace("\"", "?");
                qsString.replace(" ", "%20");
            }

            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }

        function dataRemove(num) {
            var qsArray = new Array();
            var qsString = "";
            var indexYear = 0;
            var indexLocation = 0;
            var indexDate = 0;
            var indexUrl = 0;

            for (i = 0; i < document.forms["venueForm"].elements.length; i++) {
                var element = document.forms["venueForm"].elements[i];
                if ((element.type != "submit") && (element.type != "reset")
                    && (element.type != "button")
                    && (element.value != "") && (element.value != null)) {

                    if (element.name == "venue_id") {
                        qsArray.push(element.name + "=" + element.value);
                        qsArray.push("status=change");
                    }
                    else if (element.name == "type") {
                        if (element.checked) {
                            qsArray.push(element.name + "="
                                         + element.value.replace("\"","'"));
                        }
                    }
                    else if (element.name == "numNewOccurrences") {
                        numOccur = parseInt(element.value) - 1;
                        qsArray.push(element.name + "=" + numOccur);
                    }
                    else if (element.name.indexOf("newOccurrenceLocation") >= 0) {
                        if (element.name != "newOccurrenceLocation[" + num + "]") {
                            qsArray.push("newOccurrenceLocation["
                                         + indexLocation + "]="
                                         + element.value);
                            indexLocation++;
                        }
                    }
                    else if (element.name.indexOf("newOccurrenceDate") >= 0) {
                        if (element.name != "newOccurrenceDate[" + num + "]") {
                            qsArray.push("newOccurrenceDate["
                                         + indexDate + "]=" + element.value);
                            indexDate++;
                        }
                    }
                    else if (element.name.indexOf("newOccurrenceUrl") >= 0) {
                        if (element.name != "newOccurrenceUrl[" + num + "]") {
                            qsArray.push("newOccurrenceUrl["
                                         + indexUrl + "]=" + element.value);
                            indexUrl++;
                        }
                    }
                }
            }

            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace("\"", "?");
                qsString.replace(" ", "%20");
            }

            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }
        </script>
JS_END;
    }
}

session_start();
$access_level = check_login();
$page = new add_venue();
echo $page->toHtml();

?>
