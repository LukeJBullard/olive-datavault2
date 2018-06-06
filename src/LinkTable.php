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

        /**
         * Retrieves a Link from the table by it's hash
         * 
         * @param String $a_hash The Hash of the Link to retrieve
         * @return Link The retrieved Link
         * @return Int An error code specifying why the Link could not be retrieved
         */
        public function getLink($a_hash)
        {
            return DV2_NOT_IMPLEMENTED;
        }

        /**
         * Saves a Link to the Table
         * 
         * @param Link $a_link The Link to save
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function saveLink($a_link)
        {
            return DV2_NOT_IMPLEMENTED;
        }

        /**
         * Deletes a Link from the table
         * 
         * @param String $a_hash The Hash of the Link to delete.
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function clearLink($a_hash)
        {
            return DV2_NOT_IMPLEMENTED;
        }

        /**
         * Returns if a link exists in the table.
         * 
         * @param String $a_hash The Hash of the Link to search for.
         * @return Boolean If the link exists.
         */
        public function linkExists($a_hash)
        {
            return DV2_NOT_IMPLEMENTED;
        }
    }
?>