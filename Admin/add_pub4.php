<?php ;

// $Id: add_pub4.php,v 1.14 2006/09/22 17:07:11 aicmltec Exp $

/**
 * \file
 *
 * \brief This is the form portion for adding or editing author information.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdAuthor.php';
require_once 'includes/pdExtraInfoList.php';
require_once 'includes/pdAttachmentTypesList.php';

/**
 * Renders the whole page.
 */
class add_pub4 extends pdHtmlPage {
    var $debug = 0;

    function add_pub4() {
        global $access_level;

        parent::pdHtmlPage('add_publication', 'Select Authors',
                           'Admin/add_pub4.php',
                           PD_NAV_MENU_LEVEL_ADMIN);

        if ($access_level < 1) {
            $this->loginError = true;
            return;
        }

        if ($_SESSION['state'] != 'pub_add') {
            header('Location: add_pub1.php');
            return;
        }

        $this->navMenuItemEnable('add_publication', 0);
        $this->navMenuItemDisplay('add_author', 0);
        $this->navMenuItemDisplay('add_category', 0);
        $this->navMenuItemDisplay('add_venue', 0);

        $this->db =& dbCreate();
        $db =& $this->db;
        $pub =& $_SESSION['pub'];

        // initialize attachments
        if (!isset($_SESSION['paper']) && !isset($_SESSION['attachments'])) {
            $_SESSION['paper'] = $pub->paperFilenameGet();
            if (count($pub->additional_info) > 0)
                for ($i = 0; $i < count($pub->additional_info); $i++) {
                    $_SESSION['attachments'][$i] = $pub->attFilenameGet($i);
                    $_SESSION['att_types'][$i]
                        = $pub->additional_info[$i]->type;
                }
        }

        if ($this->debug) {
            $this->contentPost
                .= 'sess<pre>' . print_r($_SESSION, true) . '</pre>';
        }

        $form = new HTML_QuickForm('add_pub4');
        $this->form =& $form;
        $this->formAddAttachments();
        $this->formAddWebLinks();
        $this->formAddPubLinks();

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'submit', 'prev_step', '<< Previous Step'),
                HTML_QuickForm::createElement(
                    'button', 'cancel', 'Cancel',
                    array('onclick' => "location.href='" . $url . "';")),
                HTML_QuickForm::createElement(
                    'reset', 'reset', 'Reset'),
                HTML_QuickForm::createElement(
                    'submit', 'finish', 'Finish')),
            'buttons', null, '&nbsp', false);


        if ($form->validate())
            $this->processForm();
        else
            $this->renderForm();

        $this->db->close();
    }

    function formAddAttachments() {
        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];
        $user =& $_SESSION['user'];

        $num_att = count($_SESSION['attachments']);

        $form->addElement('header', null, 'Attachments');

        if (isset($_SESSION['paper']) && ($_SESSION['paper'] != 'none')) {
            $filename = basename($_SESSION['paper'], '.' . $user->login);
            $filename = str_replace('paper_', '', $filename);

            $form->addGroup(
                array(
                HTML_QuickForm::createElement(
                    'static', 'assigned_paper', null, $filename),
                HTML_QuickForm::createElement(
                    'submit', 'remove_paper', 'Remove Paper')
                    ),
                null, $this->helpTooltip('Current Paper', 'paperAtt') . ':',
                '&nbsp', false);
        }
        else {
            $form->addElement('file', 'uploadpaper',
                              $this->helpTooltip('Paper', 'paperAtt') . ':',
                              array('size' => 45));
        }

        $att_types = new pdAttachmentTypesList($db);
        $form->addElement('hidden', 'num_att', $num_att);

        for ($i = 0; $i < $num_att; $i++) {
            unset($filename);

            $filename = basename($_SESSION['attachments'][$i],                                 '.' . $user->login);
            $filename = str_replace('additional_', '', $filename);
            $att_type = $_SESSION['att_types'][$i];

            if ($filename != '') {
                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'static', 'att_type' . $i, null, '['.$att_type.']'),
                        HTML_QuickForm::createElement(
                            'static', 'attachment' . $i, null, $filename),
                        HTML_QuickForm::createElement(
                            'submit', 'remove_att' . $i, 'Remove Attachment')
                        ),
                    null, 'Attachment ' . ($i + 1) . ':', '&nbsp;', false);
            }
        }

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'select', 'new_att_type', null, $att_types->list),
                HTML_QuickForm::createElement(
                    'file', 'new_att', null, array('size' => 35))
                ),
            'new_att_group', 'Attachment ' . ($num_att + 1) . ':', '&nbsp;',
            false);

        $form->addElement('submit', 'add_att', 'Add Attachment');
    }

    function formAddWebLinks() {
        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        $num_web_links = count($pub->web_links);

        $form->addElement('header', 'web_links_hdr', 'Web Links', null);

        if (count($pub->web_links) > 0) {
            $c = 0;
            foreach (array_keys($pub->web_links) as $text) {
                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'text', 'curr_web_link_text' . $c, null,
                            array('size' => 25, 'maxlength' => 250)),
                        HTML_QuickForm::createElement(
                            'static', 'web_links_help', null, ':'),
                        HTML_QuickForm::createElement(
                            'text', 'curr_web_link_url' . $c, null,
                            array('size' => 25, 'maxlength' => 250)),
                        HTML_QuickForm::createElement(
                            'submit', 'remove_web_link' . $c, 'Remove')
                        ),
                    'curr_web_links_group',
                    $this->helpTooltip('Link ' . ($c+1), 'extLinks') . ':',
                    '&nbsp;', false);
                $label = '';
                $c++;
            }
        }

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'new_web_link_text', null,
                    array('size' => 25, 'maxlength' => 250)),
                HTML_QuickForm::createElement(
                    'static', 'web_links_help', null, ':'),
                HTML_QuickForm::createElement(
                    'text', 'new_web_link_url', null,
                    array('size' => 25, 'maxlength' => 250))
                ),
            'new_web_links_group',
            'New Link :<br/><span id="small">name&nbsp;:&nbsp;url</span>',
            '&nbsp;', false);

        $form->addElement('submit', 'add_web_link', 'Add Web Link');
        $form->addElement('hidden', 'num_web_links', $num_web_links);

        if ($this->debug) {
            $this->contentPost .= 'pub<pre>' . print_r($pub, true) . '</pre>';
        }
    }

    function formAddPubLinks() {
        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        // publication links
        $num_pub_links = count($pub->pub_links);

        $form->addElement('header', 'pub_links_hdr', 'Publication Links',
                          null);

        if (count($pub->pub_links) > 0) {
            $c = 0;
            foreach ($pub->pub_links as $pub_id) {
                $intPub = new pdPublication();
                $result = $intPub->dbLoad($db, $pub_id);

                if (!$result) continue;

                $pubLinkstr = '<a href="' . $url
                    . '../view_publication.php?pub_id=' . $pub_id
                    . '" target="blank">' . $intPub->title . '</a>';

                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'static', 'curr_pub_link' . $c, null,
                            $pubLinkstr),
                        HTML_QuickForm::createElement(
                            'submit', 'remove_pub_link' . $c, 'Remove')
                        ),
                    'curr_web_links_group',
                    $this->helpTooltip('Link ' . ($c+1), 'intLinks') . ':',
                    '&nbsp;', false);
                $label = '';
                $c++;
            }
        }

        $pub_list = new pdPubList($db);
        $options[''] = '--- select publication --';
        foreach ($pub_list->list as $p) {
            if (strlen($p->title) > 70)
                $options[$p->pub_id] = substr($p->title, 0, 67) . '...';
            else
                $options[$p->pub_id] = $p->title;
        }
        $form->addElement('select', 'new_pub_link', 'New Link', $options);

        $form->addElement('submit', 'add_pub_links', 'Add Publication Link');
        $form->addElement('hidden', 'num_pub_links', $num_pub_links);
    }

    function renderForm() {
        assert('isset($_SESSION["pub"])');

        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        $defaults = array();

        if (count($pub->web_links) > 0) {
            $c = 0;
            foreach ($pub->web_links as $text => $url) {
                $defaults['curr_web_link_text' . $c] = $text;
                $defaults['curr_web_link_url' . $c] = $url;
                $c++;
            }
        }

        $form->setDefaults($defaults);

        $this->contentPre .= '<h3>Publication Information</h3>'
            . $pub->getCitationHtml('', false) . '<p/>';

        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
            . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    function processForm() {
        assert('isset($_SESSION["pub"])');

        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];
        $user =& $_SESSION['user'];

        $values = $form->exportValues();

        $element =& $form->getElement('uploadpaper');
        if (!isset($element->message) && ($element->isUploadedFile())) {
            $basename = $_FILES['uploadpaper']['name'] . '.' . $user->login;
            $element->moveUploadedFile(FS_PATH_UPLOAD, $basename);
            $_SESSION['paper'] = FS_PATH_UPLOAD . $basename;
        }

        $group =& $form->getElement('new_att_group');
        $elements =& $group->getElements();
        foreach ($elements as $element) {
            if (($element->getName() == 'new_att')
                && $element->isUploadedFile()) {
                $basename = $_FILES['new_att']['name'] . '.' . $user->login;
                $element->moveUploadedFile(FS_PATH_UPLOAD, $basename);
                $_SESSION['attachments'][] =  FS_PATH_UPLOAD . $basename;
                $_SESSION['att_types'][] =  $values['new_att_type'];
            }
        }

        if (isset($values['remove_paper'])) {
            // check if this is a temporary file
            if (strpos($_SESSION['paper'], '.' . $user->login) !== false)
                unlink($_SESSION['paper']);

            $_SESSION['paper'] = 'none';
            header('Location: add_pub4.php');
            return;
        }

        for ($i = 0; $i < $values['num_att']; $i++) {
            if (isset($values['remove_att' . $i])) {
                // check if this is a temporary file
                if (strpos($_SESSION['attachments'][$i], $user->login) !== false)
                    unlink($_SESSION['attachments'][$i]);

                if (strpos($_SESSION['attachments'][$i], 'additional_') !== false)
                    $_SESSION['removed_atts'][] = $_SESSION['attachments'][$i];

                unset($_SESSION['attachments'][$i]);
                unset($_SESSION['att_types'][$i]);

                // reindex
                $_SESSION['attachments']
                    = array_values($_SESSION['attachments']);
                $_SESSION['att_types'] = array_values($_SESSION['att_types']);

                header('Location: add_pub4.php');
                return;
            }
        }

        if (($values['new_web_link_text'] != '')
            && ($values['new_web_link_url'] != '')) {
            $pub->addWebLink($values['new_web_link_text'],
                             $values['new_web_link_url'] );
        }

        for ($i = 0; $i < $values['num_web_links']; $i++) {
            if (isset($values['remove_web_link' . $i])) {
                $pub->delWebLink($values['curr_web_link_text' . $i]);
                header('Location: add_pub4.php');
                return;
            }
        }

        if ($values['new_pub_link'] > 0) {
            $pub->addPubLink($values['new_pub_link']);
        }

        for ($i = 0; $i < $values['num_pub_links']; $i++) {
            if (isset($values['remove_pub_link' . $i])) {
                $pub->pubLinkRemove($values['curr_pub_link' . $i]);
                header('Location: add_pub4.php');
                return;
            }
        }

        if ($this->debug) {
            $this->contentPre
                .= 'element<pre>' . print_r($form, true) . '</pre>'
                . 'sess<pre>' . print_r($_SESSION, true) . '</pre>'
                . 'values<pre>' . print_r($values, true) . '</pre>';
            //return;
        }

        if (isset($values['add_att'])) {
            header('Location: add_pub4.php');
        }
        else if (isset($values['add_web_link'])) {
            header('Location: add_pub4.php');
        }
        else if (isset($values['add_pub_links'])) {
            header('Location: add_pub4.php');
        }
        else if (isset($values['prev_step']))
            header('Location: add_pub3.php');
        else
            header('Location: add_pub_submit.php');
    }

    function javascript() {
        $this->js = <<<JS_END
            <script language="JavaScript" type="text/JavaScript">

        var paperAtt =
            "Attach a postscript, PDF, or other version of the publication.";

        var otherAtt =
            "In addition to the primary paper attachment, attach additional "
            + "files to this publication.";

        var extraInfoHelp=
            "Specify auxiliary information, to help classify this "
            + "publication. Eg, &quot;with student&quot; or &quot;best "
            + "paper&quot;, etc. Note that, by default, this information will "
            + "NOT be shown when this document is presented. Separate using "
            + "semiolons(;).";

        var extLinks=
            "Used to link this publication to an outside source such as a "
            + "website or a publication that is not in the current database.";

        var pubLinks =
            "Used to link other publications in the database to this "
            + "publication.";

        function dataKeep(num) {
            var form =  document.forms["add_pub4"];
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < form.elements.length; i++) {
                var element = form.elements[i];

                if ((element.type != "submit") && (element.type != "reset")
                    && (element.type != "button")
                    && (element.value != "") && (element.value != null)) {

                    if (element.type == "checkbox") {
                        if (element.checked) {
                            qsArray.push(element.name + "=" + element.value);
                        }
                    } else if (element.name == "num_att") {
                        qsArray.push(form.elements[i].name + "=" + num);
                    } else if (element.type != "hidden") {
                        qsArray.push(form.elements[i].name + "="
                                     + form.elements[i].value);
                    }
                }
            }

            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace(" ", "%20");
                qsString.replace("\"", "?");
            }

            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }
        </script>
JS_END;
    }

    function templateGet() {
        $template = <<<END
{javascript}
<table{class}>
<tr>
  <th>&nbsp;</th>
  <!-- BEGIN label_2 --><th>{label_2}</th><!-- END label_2 -->
  <th>&nbsp;</th>
  <!-- BEGIN label_3 --><th>{label_3}</th><!-- END label_3 -->
</tr>
<tr>
  <td valign="middle">{moveup}<br/>{movedown}<br/>{remove}</td>
  <td valign="top">{selected}</td>
  <td valign="middle">{add}</td>
  <td valign="top">{unselected}</td>
</tr>
</table>
{javascript}
END;
       return $template;
    }
}

session_start();
$access_level = check_login();
$page = new add_pub4();
echo $page->toHtml();


?>
