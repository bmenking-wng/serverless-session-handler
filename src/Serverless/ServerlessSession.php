<?php

namespace WorldNewsGroup\Serverless;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

final class ServerlessSession implements \SessionHandlerInterface {
    private static ?ServerlessSession $instance = null;

    private DynamoDbClient $db;
    private bool $enabled = false;
    private string $tableName;
    private Marshaler $marshaler;
    public ?\Exception $last_error;

    /**
     * 
     * @param string $tablename 
     * @param string $region 
     * @return void 
     */
    private function __construct(string $tablename, string $region = 'us-east-1') {
        $this->db = new DynamoDbClient([
            'region'=>$region,
            'version'=>'latest'
        ]);

        $this->tableName = $tablename;
        $this->enabled = false;

        $this->marshaler = new \Aws\DynamoDb\Marshaler();

        session_set_save_handler($this, true);
        session_start();
    }

    /**
     * 
     * @param string $tablename 
     * @param string $region 
     * @return ServerlessSession 
     */
    public static function getInstance(string $tablename, string $region): ServerlessSession {
        if( is_null(self::$instance) ) {
            self::$instance = new static($tablename, $region);
        }

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
    public function destroy(string $id): bool {
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
            $this->last_error = $e;
            return false;
        }
    }

    /**
     * 
     * @param int $max_lifetime 
     * @return int|false 
     */
    public function gc(int $max_lifetime): int|false  {
        return false;
    }

    /**
     * 
     * @param string $path 
     * @param string $name 
     * @return bool 
     */
    public function open(string $path, string $name): bool {
        try {
            $table = $this->db->describeTable([
                'TableName'=>$this->tableName
            ]);

            $this->enabled = true;
        }
        catch(\Exception $e) {
            $this->last_error = $e;
            $this->enabled = false;
        }

        return $this->enabled;
    }

    /**
     * 
     * @param string $id 
     * @return string|false 
     */
    public function read(string $id): string|false  {
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

            $response = ['payload'=>''];

            if( isset($result['Item']) > 0 ) {
                $response = $this->marshaler->unMarshalItem((array)$result['Item']);
            }

            return $response['payload'];
        }
        catch(\Exception $e) {
            $this->last_error = $e;
            return false;
        }
    }

    /**
     * 
     * @param string $id 
     * @param string $data 
     * @return bool 
     */
    public function write(string $id, string $data): bool {
        if( !$this->enabled ) return false;

        $payload['id'] = $id;
        $payload['payload'] = $data;
        $payload['lastModified'] = time();

        try {
            $result = $this->db->putItem([
                'TableName'=>$this->tableName,
                'Item'=>$this->marshaler->marshalItem($payload)
            ]);
            return true;
        }
        catch(\Exception $e) {
            $this->last_error = $e;
            return false;
        }
    }

    protected function __clone() {}

    public function __wakeup() {
        throw new \Exception('Cannot unserialize a singleton');
    }

    public function __destruct() {
        session_write_close();
    }    
}
