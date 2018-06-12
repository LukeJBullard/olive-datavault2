<?php 
    /**
     *  Corresponding Class to test Data Vault 2.0 Link Table class
     *
     *  @author Luke Bullard
     */

    require_once("../src/Link.php");
    require_once("../src/LinkTable.php");

    class LinkTableTest extends PHPUnit_Framework_TestCase
    {
        /**
         * Test to ensure there are no syntax errors in the class
         */
        public function testSyntaxError()
        {
            $var = new LinkTable();
            $this->assertTrue(is_object($var));
        }

        /**
         * Test to ensure linkExists is working
         */
        public function testLinkExists()
        {
            $var = new LinkTable();
            $link = new Link("source","date",array("hub" => "hash", "hub2" => "hash2"));
            $linkHash = $link->getHashKey();

            $var->saveLink($link);

            $this->assertTrue($var->linkExists($linkHash));
            $this->assertFalse($var->linkExists("non existant hash"));
        }

        /**
         * Test to ensure clearLink is working
         */
        public function testClearLink()
        {
            $var = new LinkTable();
            $link = new Link("source","date",array("hub" => "hash", "hub2" => "hash2"));
            $linkHash = $link->getHashKey();

            $var->saveLink($link);

            $this->assertEquals($var->clearLink($linkHash), DV2_SUCCESS);
            $this->assertFalse($var->linkExists($linkHash));
        }

        /**
         * Test to ensure saveLink is working
         */
        public function testSaveLink()
        {
            $var = new LinkTable();
            $link = new Link("source","date",array("hub" => "hash", "hub2" => "hash2"));
            $linkHash = $link->getHashKey();

            $this->assertEquals($var->saveLink($link), DV2_SUCCESS);
            $this->assertTrue($var->linkExists($linkHash));
        }
        
        /**
         * Test to ensure getLink is working
         */
        public function testGetLink()
        {
            $var = new LinkTable();
            $link = new Link("source","date",array("hub" => "hash", "hub2" => "hash2"));
            $linkHash = $link->getHashKey();

            $var->saveLink($link);


            $this->assertEquals($var->getLink($linkHash), $link);
        }
    }
?>