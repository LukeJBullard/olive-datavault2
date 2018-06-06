<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * Misc Utilities
     * 
     * @author Luke Bullard
     */

    const SATELLITE_LATEST = -1;
    const DV2_NOT_IMPLEMENTED = -1;
    const DV2_ERROR = -2;
    const DV2_SUCCESS = 1;
    const DV2_EXISTS = 2;

    class DV2_Utility
    {
        /**
         * Calculate the Hash of Data
         * 
         * @param String $a_data Input string to calculate the Hash of
         * @param Array $a_data Input array to calculate the Hash of
         * @return String The output hash of the input
         * @return Int The error code if the hash could not be generated
         */
        public static function calculateDV2Hash($a_data)
        {
            if (is_array($a_data))
            {
                ksort($a_data);
                $string = "";
                foreach ($a_data as $key => $val)
                {
                    $string .= $val;
                }
                return hash('sha224',$string);
            }
            if (is_string($a_data))
            {
                return hash('sha224',$a_data);
            }
            return DV2_ERROR; 
        }
    }
?>