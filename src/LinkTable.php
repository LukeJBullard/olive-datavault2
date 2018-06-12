<?php
    /**
     * Data Vault 2.0 module for OliveWeb
     * Definition of a table of Links
     * 
     * @author Luke Bullard
     */

    /**
     * An in-memory Table of Links
     */
    class LinkTable
    {
        private $m_links;

        public function __construct()
        {
            $this->m_links = array();
        }

        /**
         * Retrieves a Link from the table by it's hash
         * 
         * @param String $a_hash The Hash of the Link to retrieve
         * @return Link|Int The retrieved Link or an error code specifying why the Link could not be retrieved
         */
        public function getLink($a_hash)
        {
            if ($this->linkExists($a_hash))
            {
                return $this->m_links[$a_hash];
            }
            return DV2_ERROR;
        }

        /**
         * Saves a Link to the Table
         * 
         * @param Link $a_link The Link to save
         * @return Int DV2_SUCCESS, DV2_EXISTS or DV2_ERROR
         */
        public function saveLink($a_link)
        {
            //get the hash of the link
            $linkHash = $a_link->getHashKey();

            //if the link already exists, return DV2_EXISTS
            if ($this->linkExists($linkHash))
            {
                return DV2_EXISTS;
            }

            $this->m_links[$linkHash] = $a_link;
            return DV2_SUCCESS;
        }

        /**
         * Deletes a Link from the table
         * 
         * @param String $a_hash The Hash of the Link to delete.
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function clearLink($a_hash)
        {
            unset($this->m_links[$a_hash]);

            return DV2_SUCCESS;
        }

        /**
         * Returns if a link exists in the table.
         * 
         * @param String $a_hash The Hash of the Link to search for.
         * @return Boolean If the link exists.
         */
        public function linkExists($a_hash)
        {
            return array_key_exists($a_hash,$this->m_links);
        }
    }
?>