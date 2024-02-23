<?php

namespace WorldNewsGroup\Serverless;

class ServerlessSession implements \SessionHandlerInterface {
    private static $instance = null;

    private $db;
    private $enabled;
    private $tableName;
    private $data;
    private $marshaler;
    public $last_error;

    /**
     * 
     * @param mixed $tablename 
     * @param string $region 
     * @return void 
     */
    private function __construct($tablename, $region = 'us-east-1') {

        $this->db = new \Aws\DynamoDb\DynamoDbClient([
            'region'=>$region,
            'version'=>'latest'
        ]);

        $this->tableName = $tablename;
        $this->enabled = false;

        $this->marshaler = new \Aws\DynamoDb\Marshaler();
    }

    /**
     * 
     * @param mixed $tablename 
     * @param mixed $region 
     * @return ServerlessSession 
     */
    public static function getInstance($tablename, $region): ServerlessSession {
        if( is_null(self::$instance) ) {
            self::$instance = new static($tablename, $region);
        }
        
        session_set_save_handler(self::$instance, true);

        return self::$instance;        
    }

    /**
     * 
     * @return bool 
     */
    public function close(): bool {
        return true;
    }

    /**
     * 
     * @param string $id 
     * @return bool 
     */
    public function destroy($id): bool {
        if( !$this->enabled ) return false;

        try {
            $result = $this->db->deleteItem([
                'TableName'=>$this->tableName,
                'Key'=>[
                    'id'=>[
                        'S'=>$id
                    ]
                ]
            ]);
            return true;
        }
        catch(\Exception $e) {
            return false;
        }
    }

    /**
     * 
     * @param int $max_lifetime 
     * @return int|false 
     */
    public function gc($max_lifetime): int|false  {
        return false;
    }

    /**
     * 
     * @param string $path 
     * @param string $name 
     * @return bool 
     */
    public function open($path, $name): bool {
        try {
            $table = $this->db->describeTable([
                'TableName'=>$this->tableName
            ]);

            $this->enabled = true;
        }
        catch(\Exception $e) {
            $this->enabled = false;
        }

        return $this->enabled;
    }

    /**
     * 
     * @param string $id 
     * @return string|false 
     */
    public function read($id): string|false  {
        if( !$this->enabled ) return false;

        try {
            $result = $this->db->getItem([
                'TableName'=>$this->tableName,
                'Key'=>[
                    'id'=>[
                        'S'=>$id
                    ]
                ]
            ]);

            if( isset($result['Item']) > 0 ) {
                $this->data = $this->marshaler->unMarshalItem($result['Item']);
            }
            else {
                $this->data['payload'] = '';
            }

            return $this->data['payload'];
        }
        catch(\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * 
     * @param string $id 
     * @param string $data 
     * @return bool 
     */
    public function write($id, $data): bool {
        if( !$this->enabled ) return false;

        $this->data['id'] = $id;
        $this->data['payload'] = $data;
        $this->data['lastModified'] = time();

        try {
            $result = $this->db->putItem([
                'TableName'=>$this->tableName,
                'Item'=>$this->marshaler->marshalItem($this->data)
            ]);
            return true;
        }
        catch(\Exception $e) {
            return false;
        }
    }

    protected function __clone() {}

    public function __wakeup() {
        throw new \Exception('Cannot unserialize a singleton');
    }
}

class SessionPayload {
    public $id;
    public $lastModified;
    public $payload;
}