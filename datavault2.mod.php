<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * Luke Bullard, May 2018
     */

    //make sure we are included securely
    if (!defined("INPROCESS")) { header("HTTP/1.0 403 Forbidden"); exit(0); }

    use Aws\DynamoDb\DynamoDbClient;    

    const SATELLITE_LATEST = -1;
    const DV2_NOT_IMPLEMENTED = -1;
    const DV2_ERROR = -2;
    const DV2_SUCCESS = 1;
    const DV2_EXISTS = 2;

    /**
     * Calculate the Hash of Data
     * 
     * @param String $a_data Input string to calculate the Hash of
     * @param Array $a_data Input array to calculate the Hash of
     * @return String The output hash of the input
     * @return Int The error code if the hash could not be generated
     */
    function calculateDV2Hash($a_data)
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

    /**
     * A structure to model a Data Vault 2.0 satellite.
     */
    class Satellite
    {
        private $m_source;
        private $m_loadDate;
        private $m_hashDiff;
        private $m_data;
        private $m_hubHash;

        /**
         * Constructor for Satellite
         * 
         * @param String $a_source The data source that the Satellite was loaded from.
         * @param String $a_loadDate The date that the Satellite was loaded into the Vault.
         * @param String $a_hubHash The hash of the Hub that this Satellite is linked to.
         * @param Array $a_data An associative array with the hub's data in it.
         */
        public function __construct($a_source="",$a_loadDate="",$a_hubHash="",$a_data=array())
        {
            $this->m_source = $a_source;
            $this->m_loadDate = $a_loadDate;
            $this->m_hubHash = $a_hubHash;
            $this->m_data = $a_data;
            $this->calculateHashDiff();
        }

        /**
         * Regenerates the hash for the Satellite based on it's data.
         * 
         * @return String The Satellite's Hash
         */
        public function calculateHashDiff()
        {
            $this->m_hashDiff = calculateDV2Hash($this->m_data);
            return $this->m_hashDiff;
        }

        /**
         * Retrieves the source that the Satellite was loaded from first.
         * 
         * @return String The source that the Satellite was loaded from.
         */
        public function getSource()
        {
            return $this->m_source;
        }

        /**
         * Retrieves the date that the Satellite was loaded first.
         * 
         * @return String The date that the Satellite was loaded.
         */
        public function getDate()
        {
            return $this->m_loadDate;
        }

        /**
         * Retrieves the current hash for the Satellite.
         * 
         * @return String The Satellite's hash
         */
        public function getHashDiff()
        {
            return $this->m_hashDiff;
        }

        /**
         * Retrieves the hash of the Hub that the Satellite is linked to.
         * 
         * @return String The parent Hub's hash
         */
        public function getHubHash()
        {
            return $this->m_hubHash;
        }

        /**
         * Retrieves the data of the Satellite.
         * 
         * @return Array An associative array of the Satellite's data
         */
        public function getData()
        {
            return $this->m_data;
        }
    }

    /**
     * A collection of Satellites with in-memory storage for runtime.
     * This class would usually be built upon for use with persistent storage systems.
     */
    class SatelliteTable
    {
        private $m_satellites;

        public function __construct()
        {
            $this->m_satellites = array();
        }

        /**
         * Returns if the Satellite exists in the SatelliteTable
         * 
         * @param String $a_hashDiff The Hash of the Satellite to check for
         * @return Boolean If the Satellite was found in the table
         */
        public function satelliteExists($a_hashDiff)
        {
            return array_key_exists($a_hashDiff,$this->m_satellites);
        }

        /**
         * Saves the Satellite in the Table
         * 
         * @param Satellite $a_satellite The Satellite to save
         * @return Int
         *      DV2_EXISTS if the Satellite already exists
         *      DV2_SUCCESS if the Satellite was added to the Table successfully
         */
        public function saveSatellite($a_satellite)
        {
            //if the satellite already exists
            if ($this->satelliteExists($a_satellite->getHashDiff()))
            {
                return DV2_EXISTS;
            }
            
            array_push($this->m_satellites,$a_satellite);

            return DV2_SUCCESS;
        }

        /**
         * Removes a Satellite from the Table
         * 
         * @param String $a_hashKey The hash of the Satellite to remove from the Table
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function clearSatellite($a_hashKey)
        {
            unset($this->m_satellites[$a_hashKey]);
            return DV2_SUCCESS;
        }

        /**
         * Retrieves a Satellite from the Table
         * 
         * @param String $a_hashKey The hash of the Satellite to retrieve
         * @return Satellite The Satellite retrieved from the Table
         * @return Int DV2_ERROR If the Satellite could not be retrieved
         */
        public function getSatellite($a_hashKey)
        {
            if (isset($this->m_satellites[$a_hashKey]))
            {
                return $this->m_satellites[$a_hashKey];
            }
            return DV2_ERROR;
        }
    }
    
    /**
     * A SatelliteTable that saves Satellites to a AWS DynamoDB Table
     */
    class DynamoSatelliteTable extends SatelliteTable
    {
        private $m_fieldMap;
        private $m_tableName;
        private $m_sourceFieldName;
        private $m_dateFieldName;
        private $m_hashDiffFieldName;
        private $m_hubHashFieldName;
        protected $m_dynamo;

        /**
         * Constructor for DynamoSatelliteTable
         * 
         * @param String $a_tableName The name of the DynamoDB table to use.
         * @param String $a_sourceFieldName The column in the DynamoDB to put the Satellite's Data Source information.
         * @param String $a_dateFieldName The column in the DynamoDB to put the Date that the Satellite was loaded.
         * @param String $a_hashDiffFieldName The column in the DynamoDB to put the Hash of the Satellite.
         * @param String $a_hubHashFieldName The column in the DynamoDB to put the hash of the Hub the Satellite is linked to.
         * @param Array $a_fieldMap An associative array. Each Key is the Key of the Satellite's Data, and the Value is
         *               another associative array (Key: Amazon's data type string (S/N/SS/etc.),
         *                  Val: The column to put the data into in DynamoDB)
         */
        public function __construct($a_tableName, $a_sourceFieldName, $a_dateFieldName,
                                    $a_hashDiffFieldName, $a_hubHashFieldName, $a_fieldMap=array())
        {
            $this->m_tableName = $a_tableName;
            $this->m_fieldMap = $a_fieldMap;
            $this->m_sourceFieldName = $a_sourceFieldName;
            $this->m_dateFieldName = $a_dateFieldName;
            $this->m_hashDiffFieldName = $a_hashDiffFieldName;
            $this->m_hubHashFieldName = $a_hubHashFieldName;

            $modules = Modules::getInstance();
            $this->m_dynamo = $modules['aws']->getDynamoDB();
        }

        /**
         * Returns if the specified Satellite exists in the Table
         * 
         * @param String $a_hashDiff The Hash Diff of the Satellite.
         * @param String $a_hubHash The Hash of the Hub the Satellite is under. (Optional- if omitted, will search for the first Satellite with the hash diff)
         * @return Boolean If the Satellite exists.
         */
        public function satelliteExists($a_hashDiff, $a_hubHash="")
        {
            $keyConditions = array(
                $this->m_hashDiffFieldName => array(
                    'AttributeValueList' => array(
                        array('S' => $a_hashDiff)
                    ),
                    'ComparisonOperator' => 'EQ'
                )
            );

            if ($a_hubHash != "")
            {
                $keyConditions[$this->m_hubHashFieldName] = array(
                    'AttributeValueList' => array(
                        array('S' => $a_hubHash)
                    ),
                    'ComparisonOperator' => 'EQ'
                );
            }

            return $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => $keyConditions
            ))->count() > 0;
        }

        /**
         * Retrieves a Satellite from the Table
         * @param String $a_hashDiff The Hash Diff of the Satellite to retrieve
         * @param String $a_hubHash The Hash of the Hub the Satellite is under (Optional- if omitted, will return the first Satellite with the hash diff)
         * @return Satellite The Satellite retrieved from the Table
         * @return Int DV2_ERROR If the Satellite was not found or could not be loaded
         */
        public function getSatellite($a_hashDiff, $a_hubHash="")
        {
            $keyConditions = array(
                $this->m_hashDiffFieldName => array(
                    'AttributeValueList' => array(
                        array('S' => $a_hashDiff)
                    ),
                    'ComparisonOperator' => 'EQ'
                )
            );

            if ($a_hubHash != "")
            {
                $keyConditions[$this->m_hubHashFieldName] = array(
                    'AttributeValueList' => array(
                        array('S' => $a_hubHash)
                    ),
                    'ComparisonOperator' => 'EQ'
                );
            }
            
            $result = $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => $keyConditions
            ));
            
            foreach ($result as $item)
            {
                $data = array();
                $source = "";
                $date = "";
                $hubHash = "";

                foreach ($item as $fieldName => $field)
                {
                    switch ($fieldName)
                    {
                        case $this->m_sourceFieldName:
                            foreach ($field as $key => $val)
                            {
                                $source = $val;
                                break;
                            }
                            continue;
                        
                        case $this->m_dateFieldName:
                            foreach ($field as $key => $val)
                            {
                                $date = $val;
                                break;
                            }
                            continue;

                        case $this->m_hubHashFieldName:
                            foreach ($field as $key => $val)
                            {
                                $hubHash = $val;
                                break;
                            }
                            continue;
                    }
                    foreach ($field as $key => $val)
                    {
                        $data[$fieldName] = $val;
                        break;
                    }
                }
                return new Satellite(
                    $source,
                    $date,
                    $hubHash,
                    $data
                );
            }

            return DV2_ERROR;
        }

        /**
         * Deletes the Satellite from the Table
         * 
         * @param String $a_hashDiff The hash of the Satellite to delete
         * @param String $a_hubHash The Hash of the Hub the Satellite is under (Optional- if omitted, will clear the first Satellite with the hash diff)
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function clearSatellite($a_hashDiff, $a_hubHash="")
        {
            $keyConditions = array(
                $this->m_hashDiffFieldName => array(
                    'AttributeValueList' => array(
                        array('S' => $a_hashDiff)
                    ),
                    'ComparisonOperator' => 'EQ'
                )
            );

            if ($a_hubHash != "")
            {
                $keyConditions[$this->m_hubHashFieldName] = array(
                    'AttributeValueList' => array(
                        array('S' => $a_hubHash)
                    ),
                    'ComparisonOperator' => 'EQ'
                );
            }

            $result = $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashDiffFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hashDiff)
                        )
                    )
                )
            ));

            foreach ($result as $item)
            {
                $date = $item[$this->m_dateFieldName]["S"];
                break;
            }

            if (!isset($date))
            {
                return DV2_SUCCESS;
            }

            $this->m_dynamo->deleteItem(array(
                'TableName' => $this->m_tableName,
                'Key' => array(
                    $this->m_hashDiffFieldName => $a_hashDiff,
                    $this->m_dateFieldName => $date
                )
            ));
            return DV2_SUCCESS;
        }

        /**
         * Saves a Satellite in the Table
         * 
         * @param Satellite $a_satellite
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function saveSatellite($a_satellite)
        {
            //get the satellite data into an array to send to dynamo
            $item = array(
                $this->m_hashDiffFieldName => array("S" => $a_satellite->getHashDiff()),
                $this->m_hubHashFieldName => array("S" => $a_satellite->getHubHash()),
                $this->m_sourceFieldName => array("S" => $a_satellite->getSource()),
                $this->m_dateFieldName => array("S" => date("Y-m-d H:i:s"))
            );

            $satelliteData = $a_satellite->getData();

            foreach (array_values($this->m_fieldMap) as $field)
            {
                $dataType = key($field);
                $fieldName = $field[$dataType];

                if (isset($satelliteData[$fieldName]))
                {
                    if ($satelliteData[$fieldName] == "")
                    {
                        continue;
                    }
                    $item[$fieldName] = array($dataType => $satelliteData[$fieldName]);
                }
            }

            //add the satellite in
            try {
                $result = $this->m_dynamo->putItem(array(
                    "TableName" => $this->m_tableName,
                    "Item" => $item,
                    "ConditionalExpression" => "attribute_not_exists(" . $this->m_hashDiffFieldName . ")"
                ));
            } catch (Aws\DynamoDb\Exception\ConditionalCheckFailedException $e) {
                //skip
            } catch (Exception $e) {
                print_r($a_satellite->getData());
                echo "<br />";
                die("Error Saving Satellite: Entry already exists.");
            }
            
            return DV2_SUCCESS;
        }
    }

    /**
     * A Data Vault 2.0 Hub
     */
    class Hub
    {
        private $m_source;
        private $m_loadDate;
        private $m_hashKey;
        private $m_dataFieldValue;

        /**
         * Constructor for a Hub
         * 
         * @param String $a_source The data source that this hub was originally loaded from
         * @param String $a_loadDate The date that the hub was originally loaded on.
         * @param String $a_data The unique data that defines this Hub.
         */
        public function __construct($a_source="", $a_loadDate="", $a_data="")
        {

            $this->m_source = $a_source;
            $this->m_loadDate = $a_loadDate;
            $this->m_dataFieldValue = $a_data;
            $this->calculateHash();
        }

        /**
         * Retrieves the hash of the Hub
         * 
         * @return String The hash of the Hub
         */
        public function getHashKey()
        {
            return $this->m_hashKey;
        }

        /**
         * Retrieves the Hub's unique data
         * 
         * @return String The Hub's unique data
         */
        public function getData()
        {
            return $this->m_dataFieldValue;
        }

        /**
         * Retrieves the original source of the Hub
         * 
         * @return String The Hub's original source
         */
        public function getSource()
        {
            return $this->m_source;
        }

        /**
         * Retrieves the original date the Hub was loaded
         * 
         * @return String The date the Hub was originally loaded
         */
        public function getLoadDate()
        {
            return $this->m_loadDate;
        }

        /**
         * Regenerates the SHA224 Hash of the hub
         * 
         * @return String The hub's regenerated hash
         */
        public function calculateHash()
        {
            $this->m_hashKey = calculateDV2Hash($this->m_dataFieldValue);
            return $this->m_hashKey;
        }

        public function loadSatellite($a_index=SATELLITE_LATEST) { return DV2_NOT_IMPLEMENTED; }
        public function numberOfSatellites() { return DV2_NOT_IMPLEMENTED; }
    }

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

    /**
     * A HubTable that saves Hubs to a AWS DynamoDB Table
     */
    class DynamoHubTable extends HubTable
    {
        private $m_tableName;
        private $m_dataFieldName;
        private $m_sourceFieldName;
        private $m_loadDateFieldName;
        private $m_hashKeyFieldName;
        protected $m_dynamo;

        /**
         * Constructor for DynamoHubTable
         * 
         * @param String $a_tableName The name of the DynamoDB table to store the Hubs in
         * @param String $a_dataFieldName The name of the column in the DynamoDB that stores the unique identifying data of the Hub
         * @param String $a_sourceFieldName The name of the column in the DynamoDB that stores the initial source of the Hub
         * @param String $a_loadDateFieldName The name of the column in the DynamoDB that stores the initial load date of the Hub
         * @param String $a_hashKeyFieldName The name of the column in the DynamoDB that stores the hash of the Hub (Primary key)
         */
        public function __construct($a_tableName,$a_dataFieldName, $a_sourceFieldName, $a_loadDateFieldName, $a_hashKeyFieldName)
        {
            $this->m_tableName = $a_tableName;
            $this->m_dataFieldName = $a_dataFieldName;
            $this->m_sourceFieldName = $a_sourceFieldName;
            $this->m_loadDateFieldName = $a_loadDateFieldName;
            $this->m_hashKeyFieldName = $a_hashKeyFieldName;

            $aws = Modules::getInstance()['aws'];
            $this->m_dynamo = $aws->getDynamoDB();
        }

        /**
         * Saves the hub to a DynamoDB. If the Hub already exists, skips it.
         * 
         * @param Hub $a_hub The Hub to save to the DynamoDB
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function saveHub($a_hub)
        {
            //get the hub data into an array to send to dynamo
            $item = array(
                $this->m_hashKeyFieldName => array("S" => $a_hub->getHashKey()),
                $this->m_dataFieldName => array("S" => $a_hub->getData()),
                $this->m_sourceFieldName => array("S" => $a_hub->getSource()),
                $this->m_loadDateFieldName => array("S" => date("Y-m-d H:i:s"))
            );

            try {
                $result = $this->m_dynamo->putItem(array(
                    'TableName' => $this->m_tableName,
                    'Item' => $item,
                    'ConditionExpression' => "attribute_not_exists(" . $this->m_hashKeyFieldName . ")"
                ));
            } catch (Aws\DynamoDb\Exception\ConditionalCheckFailedException $e) {
                //skip, do nothing
            } catch (Exception $e) {
                echo "Exception: " . $e->getMessage() . "<br />";
                die("Error Saving Hub: " . $a_hub->getData());
            }

            return DV2_SUCCESS;
        }

        /**
         * Retrieves if the hub already exists in the DynamoDB
         * 
         * @param String $a_hashKey The Hash of the hub to look for
         * @return Boolean If the hub exists
         */
        public function hubExists($a_hashKey)
        {
            return $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashKeyFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hashKey)
                        ),
                        'ComparisonOperator' => 'EQ'
                    )
                )
            ))->count() > 0;
        }

        /**
         * Retrieves a hub from the DynamoDB
         * 
         * @param String $a_hashKey The hash of the Hub to look for
         * @return Hub The retrieved Hub from the DynamoDB
         * @return Int DV2_ERROR If the Hub could not be retrieved or does not exist
         */
        public function getHub($a_hashKey)
        {
            $result = $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashKeyFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hashKey)
                        )
                    )
                )
            ));
            
            foreach ($result as $item)
            {
                $data = "";
                $loadDate = "";

                foreach ($item as $fieldName => $field)
                {
                    switch ($fieldName)
                    {
                        case $this->m_dataFieldName:
                            foreach ($field as $key => $val)
                            {
                                $data = $val;
                                break;
                            }
                            continue;
                        case $this->m_loadDateFieldName:
                            foreach ($field as $key => $val)
                            {
                                $loadDate = $val;
                                break;
                            }
                            continue;
                        case $this->m_sourceFieldName:
                            foreach ($field as $key => $val)
                            {
                                $sourceField = $val;
                                break;
                            }
                            continue;
                    }
                }
                return new Hub(
                    $this,
                    $source,
                    $date,
                    $data
                );
            }

            return DV2_ERROR;
        }

        /**
         * Deletes a Hub from the DynamoDB
         * 
         * @param String $a_hashKey The Hash of the Hub to delete
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function clearHub($a_hashKey)
        {
            $result = $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashKeyFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hashKey)
                        )
                    )
                )
            ));

            foreach ($result as $item)
            {
                $date = $item[$this->m_loadDateFieldName]["S"];
                break;
            }

            if (!isset($date))
            {
                return DV2_SUCCESS;
            }

            $this->m_dynamo->deleteItem(array(
                'TableName' => $this->m_tableName,
                'Key' => array(
                    $this->m_hashKeyFieldName => $a_hashKey,
                    $this->m_loadDateFieldName => $date
                )
            ));
            return DV2_SUCCESS;
        }
    }

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
            $this->m_hash = calculateDV2Hash($this->m_links);
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

    /**
     * A LinkTable that saves to an AWS DynamoDB Table
     */
    class DynamoLinkTable extends LinkTable
    {
        private $m_tableName;
        private $m_sourceFieldName;
        private $m_hashKeyFieldName;
        private $m_loadDateFieldName;
        private $m_fieldMap;

        /**
         * Constructor for DynamoLinkTable
         * 
         * @param String $a_tableName The name of the DynamoDB table to store the Links in
         * @param String $a_sourceFieldName The name of the column in the DynamoDB that stores the initial source of the Link
         * @param String $a_loadDateFieldName The name of the column in the DynamoDB that stores the initial load date of the Link
         * @param String $a_hashKeyFieldName The name of the column in the DynamoDB that stores the hash of the Link (Primary key)
         * @param Array $a_fieldMap An associative array. Each Key is the name of the linked hub, and the Value is
         *               another associative array (Key: Amazon's data type string (S/N/SS/etc.),
         *                  Val: The column to put the data into in DynamoDB)
         */
        public function __construct($a_tableName, $a_sourceFieldName, $a_loadDateFieldName, $a_hashKeyFieldName, $a_fieldMap)
        {
            $this->m_tableName = $a_tableName;
            $this->m_sourceFieldName = $a_sourceFieldName;
            $this->m_loadDateFieldName = $a_loadDateFieldName;
            $this->m_hashKeyFieldName = $a_hashKeyFieldName;
            $this->m_fieldMap = $a_fieldMap;

            $aws = Modules::getInstance()['aws'];
            $this->m_dynamo = $aws->getDynamoDB();
        }

        /**
         * Retrieves if a link exists in the Table
         * 
         * @param String $a_hash The hash of the link to search for.
         * @return Boolean If the hash exists
         */
        public function linkExists($a_hash)
        {
            return $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashKeyFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hash)
                        ),
                        'ComparisonOperator' => 'EQ'
                    )
                )
            ))->count() > 0;
        }

        /**
         * Deletes a Link from the DynamoDB
         * 
         * @param String $a_hash The Hash of the Link to delete
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function clearLink($a_hash)
        {
            $result = $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashKeyFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hash)
                        )
                    )
                )
            ));

            foreach ($result as $item)
            {
                $date = $item[$this->m_loadDateFieldName]["S"];
                break;
            }

            if (!isset($date))
            {
                return DV2_SUCCESS;
            }

            $this->m_dynamo->deleteItem(array(
                'TableName' => $this->m_tableName,
                'Key' => array(
                    $this->m_hashKeyFieldName => $a_hash,
                    $this->m_loadDateFieldName => $date
                )
            ));
            return DV2_SUCCESS;
        }

        /**
         * Saves a Link to the DynamoDB Table
         * 
         * @param Link $a_link The link to save
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function saveLink($a_link)
        {
            $item = array(
                $this->m_hashKeyFieldName => array("S" => $a_link->getHashKey()),
                $this->m_sourceFieldName => array("S" => $a_link->getSource()),
                $this->m_loadDateFieldName => array("S" => date("Y-m-d H:i:s"))
            );

            $links = $a_link->getLinks();

            foreach (array_values($this->m_fieldMap) as $field)
            {
                $dataType = key($field);
                $fieldName = $field[$dataType];

                if (isset($links[$fieldName]))
                {
                    if ($links[$fieldName] == "")
                    {
                        continue;
                    }
                    $item[$fieldName] = array($dataType => $links[$fieldName]);
                }
            }
            try {
                $result = $this->m_dynamo->putItem(array(
                    'TableName' => $this->m_tableName,
                    'Item' => $item,
                    'ConditionExpression' => "attribute_not_exists(" . $this->m_hashKeyFieldName .")"
                ));
            } catch (Aws\DynamoDb\Exception\ConditionalCheckFailedException $e)
            {
                //do nothing, skip
            } catch (Exeception $e)
            {
                print_r($a_link->getLinks());
                echo "<br />";
                die("Unable to save Link!");
            }
                

            return DV2_SUCCESS;
        }

        /**
         * Retrieves a Link from the DynamoDB Table
         * 
         * @param String $a_hash The Hash of the Link to retrieve
         * @return Link The returned Link
         * @return Int The DV2 status code if the Link could not be retrieved
         */
        public function getLink($a_hash)
        {
            $result = $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashKeyFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hash)
                        )
                    )
                )
            ));
            
            foreach ($result as $item)
            {
                $links = array();
                $source = "";
                $date = "";

                foreach ($item as $fieldName => $field)
                {
                    switch ($fieldName)
                    {
                        case $this->m_sourceFieldName:
                            foreach ($field as $key => $val)
                            {
                                $source = $val;
                                break;
                            }
                            continue;
                        
                        case $this->m_dateFieldName:
                            foreach ($field as $key => $val)
                            {
                                $date = $val;
                                break;
                            }
                            continue;
                    }
                    foreach ($field as $key => $val)
                    {
                        $links[$fieldName] = $val;
                        break;
                    }
                }
                return new Link(
                    $source,
                    $data,
                    $date
                );
            }

            return DV2_ERROR;
        }
    }

    /**
     * The DataVault 2.0 OliveWeb Module
     */
    class MOD_datavault2
    {}
?>