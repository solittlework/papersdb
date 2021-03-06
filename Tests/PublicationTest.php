<?php

require_once '../includes/defines.php';
require_once '../includes/functions.php';
require_once '../includes/pdDb.php';
require_once '../includes/pdAuthor.php';

class PublicationTest extends PHPUnit_Framework_TestCase {
   protected $db;
   protected $mysqli;

   protected function setUp() {
      $this->db = new pdDb(array('name' => 'pubDBdev'));
      $this->mysqli = new mysqli("localhost", "dummy", "ozzy498", "pubDBdev");

      if (mysqli_connect_errno()) {
         die("Connect failed: " . mysqli_connect_error() . "\n");
      }
   }

   protected function tearDown() {
      unset($this->author);
   }

   public function testSaveWithNoAssoc() {
      assert('is_object($this->db)');
      assert('is_object($this->mysqli)');

      $title      = uniqid('pub_title_');
      $paper      = uniqid('pub_paper_att_path');
      $keywords   = uniqid('pub_keywords_');
      $published  = date('Y-m-d');
      $venue_id   = 0;
      $extra_info = 0;
      $submit     = uniqid('pub_submit_');
      $rank_id    = 1;
      $updated    = date('Y-m-d');
      $user       = uniqid('pub_user_');

      $abstract   = <<< TEST_SAVE_WITH_NO_ASSOC_ABSTRACT_END
Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore
et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
culpa qui officia deserunt mollit anim id est laborum.
TEST_SAVE_WITH_NO_ASSOC_ABSTRACT_END;

      $pub = new pdPublication();

      $pub->title      = $title;
      $pub->paper      = $paper;
      $pub->abstract   = $abstract;
      $pub->keywords   = $keywords;
      $pub->published  = $published;
      $pub->venue_id   = $venue_id;
      $pub->extra_info = $extra_info;
      $pub->submit     = $submit;
      $pub->rank_id    = $rank_id;
      $pub->updated    = $updated;
      $pub->user       = $user;

      $pub->dbSave($this->db);

      $r = $this->mysqli->query("SELECT * FROM publication WHERE pub_id=" . $pub->pub_id);
      $resultObj = $r->fetch_object();

      $this->assertEquals(1,           $r->num_rows);
      $this->assertEquals($title,      $resultObj->title);
      $this->assertEquals($paper,      $resultObj->paper);
      $this->assertEquals($abstract,   $resultObj->abstract);
      $this->assertEquals($keywords,   $resultObj->keywords);
      $this->assertEquals($published,  $resultObj->published);
      $this->assertEquals($venue_id,   $resultObj->venue_id);
      $this->assertEquals($extra_info, $resultObj->extra_info);
      $this->assertEquals($submit,     $resultObj->submit);
      $this->assertEquals($rank_id,    $resultObj->rank_id);
      $this->assertEquals($updated,    $resultObj->updated);
      $this->assertEquals($user,       $resultObj->user);
      $r->close();
   }

   public function testWebLinkAdd() {
      $web_links = array(
         'web_link_1' => 'web_link_1_url',
         'web_link_2' => 'web_link_2_url');

      $pub = new pdPublication();
      foreach ($web_links as $key => $val) {
         $pub->addWebLink($key, $val);
      }

      $web_links = $pub->getWebLinks();
      $this->assertEquals(2, count($web_links));
      foreach ($web_links as $key => $val) {
         $this->assertEquals($val, $web_links[$key]);
      }
   }

   public function testWebLinkRemove() {
      $web_links = array(
         'web_link_1' => 'web_link_1_url',
         'web_link_2' => 'web_link_2_url');

      $pub = new pdPublication();
      foreach ($web_links as $key => $val) {
         $pub->addWebLink($key, $val);
      }

      $pub->delWebLink('web_link_1');

      $web_links = $pub->getWebLinks();
      $this->assertEquals(1, count($web_links));
      $this->assertFalse(in_array('web_link_1_url', $web_links));
   }

   public function testWebLinksRemove() {
      $pub = new pdPublication();
      $pub->addWebLink('web_link_1', 'web_link_1_url');
      $pub->addWebLink('web_link_2', 'web_link_2_url');
      $pub->webLinkRemoveAll();
      $web_links = $pub->getWebLinks();
      $this->assertEquals(0, count($web_links));
   }

   public function testWebLinkSave() {
      $web_links = array(
         'web_link_1' => 'http://web_link_1_url/',
         'web_link_2' => 'https://web_link_2_url/');

      $pub = new pdPublication();
      $pub->title =  uniqid('pub_title_');
      foreach ($web_links as $key => $val) {
         $pub->addWebLink($key, $val);
      }
      $pub->dbSave($this->db);

      $r = $this->mysqli->query("SELECT * FROM pointer WHERE pub_id=" . $pub->pub_id);
      $this->assertEquals(2, $r->num_rows);

      $result_links = array();
      while ($resultObj = $r->fetch_object()) {
         $this->assertEquals('ext', $resultObj->type);
         $result_links[$resultObj->name] = $resultObj->value;
      }
      $r->close();

      foreach ($web_links as $key => $val) {
         $this->assertEquals($val, $result_links[$key]);
      }
   }

   /**
    * Test that when web links are saved the string "http://" is prepended to them
    * when saved to the database.
    */
   public function testWebLinkSaveAppend() {
      $web_links = array(
         'web_link_1' => 'web_link_1_url/',
         'web_link_2' => 'web_link_2_url/');

      $pub = new pdPublication();
      $pub->title =  uniqid('pub_title_');
      foreach ($web_links as $key => $val) {
         $pub->addWebLink($key, $val);
      }
      $pub->dbSave($this->db);

      $r = $this->mysqli->query("SELECT * FROM pointer WHERE pub_id=" . $pub->pub_id);
      $this->assertEquals(2, $r->num_rows);

      $result_links = array();
      while ($resultObj = $r->fetch_object()) {
         $this->assertEquals('ext', $resultObj->type);
         $result_links[$resultObj->name] = $resultObj->value;
      }
      $r->close();

      foreach ($web_links as $key => $val) {
         $this->assertEquals('http://' . $val, $result_links[$key]);
      }
   }


   /**
    * Test that when web links are saved the string "http://" is prepended to them
    * when saved to the database.
    */
   public function testWebLinkDbRemove() {
      $web_links = array(
         'web_link_1' => 'http://web_link_1_url/',
         'web_link_2' => 'https://web_link_2_url/');

      $pub = new pdPublication();
      $pub->title =  uniqid('pub_title_');
      foreach ($web_links as $key => $val) {
         $pub->addWebLink($key, $val);
      }
      $pub->dbSave($this->db);

      $keys = array_keys($web_links);
      $pub->delWebLink($keys[0]);
      $pub->dbSave($this->db);

      $r = $this->mysqli->query("SELECT * FROM pointer WHERE pub_id=" . $pub->pub_id);
      $this->assertEquals(1, $r->num_rows);

      $result_links = array();
      while ($resultObj = $r->fetch_object()) {
         $this->assertEquals('ext', $resultObj->type);
         $result_links[$resultObj->name] = $resultObj->value;
      }
      $r->close();

      $this->assertEquals($web_links[$keys[1]], $result_links[$keys[1]]);

      $pub->delWebLink($keys[1]);
      $pub->dbSave($this->db);

      $r = $this->mysqli->query("SELECT * FROM pointer WHERE pub_id=" . $pub->pub_id);
      $this->assertEquals(0, $r->num_rows);
   }

   public function testBibtexPages() {
      $pub = new pdPublication();
      $pub->title =  uniqid('pub_title_');
      $pub->addCategory($this->db, 1);
      $pub->info['Editor'] = 'test';
      $pub->info['Pages'] = '22-23';
      $pub->dbSave($this->db);

      //var_dump($pub);
      var_dump($pub->getBibtex());
   }

}

?>