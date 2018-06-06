<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * Link definition
     * 
     * @author Luke Bullard
     */

    require_once("Utility.php");

    /**
     * A Data Vault 2.0 Link
     */
    class Link
    {
        private $m_source;
        private $m_hash;
        private $m_loadDate;
        private $m_links;

        /**
         * Constructor for a Link
         * 
         * @param String $a_source The source that the Link was first identified from.
         * @param String $a_loadDate The Date that the Link was first identified
         * @param Array $a_links An associative array of linked Hub name => hash
         */
        public function __construct($a_source,$a_loadDate,$a_links)
        {
            $this->m_source = $a_source;
            $this->m_links = $a_links;
            $this->m_loadDate = $a_loadDate;
            $this->calculateHash();
        }

        /**
         * Recalculates the hash of the link
         * 
         * @return String The Hash of the Link
         */
        public function calculateHash()
        {
            $this->m_hash = DV2_Utility::calculateDV2Hash($this->m_links);
            return $this->m_hash;
        }

        /**
         * Retrieves the initial source of the link
         * 
         * @return String The source of the Link
         */
        public function getSource()
        {
            return $this->m_source;
        }

        /**
         * Retrieves the date the Link was first identified
         * 
         * @return String The date the Link was first identified
         */
        public function getDate()
        {
            return $this->m_loadDate;
        }

        /**
         * Retrieves the Hash of the Link
         * 
         * @return String The Hash of the link
         */
        public function getHashKey()
        {
            return $this->m_hash;
        }

        /**
         * Retrieves the Hash of a specified Linked Hub
         * 
         * @param String $a_hubName The Name of the Linked Hub Type
         * @return String The specified Linked Hub's hash
         * @return Int DV2_ERROR If the Hub's Hash could not be retrieved
         */
        public function getLink($a_hubName)
        {
            if (!isset($this->m_links[$a_hubName]))
            {
                return DV2_ERROR;
            }

            return $this->m_links[$a_hubName];
        }

        /**
         * Retrieves all Hubs linked to this Link
         * 
         * @return Array The Hub names and their hashes that are linked in this Link
         */
        public function getLinks()
        {
            return $this->m_links;
        }
    }
?>