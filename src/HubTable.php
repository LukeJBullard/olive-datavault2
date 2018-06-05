<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * Hub Table definition
     * 
     * @author Luke Bullard
     */

    namespace Lbullard\Datavault2;

     /**
     * A collection of Hubs
     * Stores the Hubs for runtime in memory
     */
    class HubTable
    {
        private $m_hubs;

        /**
         * Saves a Hub to the Table
         * 
         * @param Hub The Hub to save to the table
         * @return Int DV2_EXISTS if the Hub already exists in the table, DV2_SUCCESS or DV2_ERROR otherwise
         */
        public function saveHub($a_hub)
        {
            //if the hub already exists
            if ($this->hubExists($a_hub->getHashKey()))
            {
                return DV2_EXISTS;
            }
            
            array_push($this->m_hubs,$a_hub);

            return DV2_SUCCESS;
        }

        /**
         * Returns whether a hub exists in the tabe or not
         * 
         * @param String $a_hashKey the Hash of the Hub to search for
         * @return Boolean If the hub exists
         */
        public function hubExists($a_hashKey)
        {
            return array_key_exists($a_hashKey, $this->m_hubs);
        }

        /**
         * Retrieves a Hub from the Table
         * 
         * @param String $a_hashKey The Hash of the Hub to retrieve
         * @return Hub The retrieved Hub
         * @return Int DV2_ERROR If the hub could not be found or retrieved
         */
        public function getHub($a_hashKey)
        {
            if (isset($this->m_hubs[$a_hashKey]))
            {
                return $this->m_hubs[$a_hashKey];
            }
            return DV2_ERROR;
        }

        /**
         * Removes a Hub from the Table
         * 
         * @param String $a_hashKey The hash of the Hub to remove
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function clearHub($a_hashKey)
        {
            unset($this->m_hubs[$a_hashKey]);
            return DV2_SUCCESS;
        }

        /**
         * Gets the first hub in the table of Hubs
         * 
         * @return Hub The first Hub
         * @return Int DV2_ERROR If no Hub is present.
         */
        public function getFirstHub()
        {
            if (!empty($this->m_hubs))
            {
                return array_values($this->m_hubs)[0];
            }
            return DV2_ERROR;
        }
        
        /**
         * Returns the number of Hubs in the Table
         * 
         * @return Int The number of Hubs in the table
         */
        public function numberOfHubs()
        {
            return count($this->m_hubs);
        }

        public function __construct()
        {
            $this->m_hubs = array();
        }
    }
?>