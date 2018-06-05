<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * Satellite Table backed by AWS DynamoDB for Storage
     * 
     * @author Luke Bullard
     */

     namespace Lbullard\Datavault2;

     use Aws\DynamoDb\DynamoDbClient;

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
?>