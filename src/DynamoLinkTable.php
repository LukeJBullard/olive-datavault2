<?php
    /**
     * DataVault 2.0 module for OliveWeb
     * A Link Table backed by AWS DynamoDB for Storage
     * 
     * @author Luke Bullard
     */
    
    namespace Lbullard\Datavault2;

    use Aws\DynamoDb\DynamoDbClient;

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
?>