<?php


/*******************************************************
 *
 *  driver : mysql , sqlite
 *  host : localhost , ""
 *  Controllers : mysql db name , sqlite db path with name
 *  port : 3306
 *  username : Controllers user
 *  password : Controllers password
 *  option : true or false
 *
 *******************************************************/



/*


$config =  [
'driver' => 'mysql',
'host' =>  'localhost',
'database' => '',
'username' => 'root',
'password' => '',
'port' => '3306',
"option" => true
];

 */



namespace Bindeveloperz;

use Exception;
use PDO;
use PDOException;

final class Database
{

    private static $instance = null;
    private static $config = [];
    private $dbResult = null ;

    private $dbh;
    private $stmt ;


    private $driver = "";
    private $database = "";
    private $host = "";
    private $username =  "";
    private $password =  "";
    private $port = "";
    private $option = "";


    private $table = "";
    private $field = "*";
    private $primaryKey = "id";

    private $where = "";
    private $orderBy = "";
    private $groupBy = "";
    private $having = "";
    private $limit = "";
    private $offset = "";


    private $columns = [];
    private $dbQuery = "";
    private $errors = null;
    private $actionType = "";
    


    private function __construct()
    {

        $this->driver =   $this->nvl(self::$config['driver'] , 'mysql');
        $this->database = $this->nvl(self::$config['database'], '');
        $this->host =     $this->nvl(self::$config['host'] ,  'localhost');
        $this->username = $this->nvl(self::$config['username'] ,'root');
        $this->password = $this->nvl(self::$config['password'] ,'');
        $this->port =     $this->nvl(self::$config['port'] , '3306');
        $this->option =   $this->nvl(self::$config['option'] , true);

        $options = [ PDO::ATTR_PERSISTENT => true , PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION , PDO::MYSQL_ATTR_LOCAL_INFILE => true ];

        if(strtolower($this->driver) == "sqlite")
            $dns = "$this->driver:$this->database";
        else
            $dns = "$this->driver:host=$this->host;dbname=$this->database;port=$this->port";

        if($this->option == false) $options = null;

        try
        {
            $this->dbh = new PDO($dns , $this->username , $this->password , $options) ;
        }
        catch (PDOException $e)
        {
            die("<p>Database Connection Error: <br>\n" . $e->getMessage() . "<br> \n" . $e->getTraceAsString() . "</p>");
        }

    }//constructor end

    public static function getInstance(Array $config)
    {
        self::$config = $config;
        if(!isset(self::$instance))
        {
            return self::$instance = new Database();
        }
        return self::$instance;
    }


    /***************************************************
     * PDO Wrapper Methods
     **************************************************/



    private function _bind($values , $type = null)
    {
        foreach ($values as $param => $value)
        {
            if (is_null($type))
            {
                switch (true) {
                    case is_int($value):
                        $type = PDO::PARAM_INT;
                        break;
                    case is_bool($value):
                        $type = PDO::PARAM_BOOL;
                        break;
                    case is_null($value):
                        $type = PDO::PARAM_NULL;
                        break;
                    default:
                        $type = PDO::PARAM_STR;
                }
            }
            $this->stmt->bindValue($param , $value , $type);
        }
    }

    private function _execute()
    {
        try {
            return $this->stmt->execute(); //boolean return
        }
        catch (Exception $ex) {
            die("Database Execute Method Error: " . "<br>\n" .$ex->getMessage() . "<br>\n" . $ex->getTraceAsString());
        }
    }

    private function _result($type = null)
    {
        //first execute then result
        $resultType = $this->getResultType($type);
        return $this->stmt->fetchAll($resultType);
    }

    private function _query($query)
    {
        $this->stmt = $this->dbh->prepare($query);
    }


    /***************************************************
     * MYSQL RAW QUERY METHODS
     **************************************************/

    public function rawQuery($query)
    {
        $this->_query($query);
    }

    public function execute()
    {
        $this->_execute();
    }

    public function result()
    {
        return $this->result();
    }

    public function executedCount()
    {
        return $this->stmt->rowCount();
    }


    /***************************************************
     * MYSQL TRANSACTION METHODS
     **************************************************/


    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    public function endTransaction()
    {
        return $this->dbh->commit();
    }

    public function cancelTransaction()
    {
        return $this->dbh->rollBack();
    }



    /***************************************************
     * MYSQL RESULT Methods
     **************************************************/



    public function executeQuery($query)
    {
        $this->_query($query);
        return $this->_execute();
    }

    public function all($type=null)
    {
        $sql  = ("SELECT {$this->tableColumn} FROM `$this->table` {$this->where} {$this->groupBy} {$this->orderBy} {$this->having} {$this->limit} {$this->offset} ");
        $this->dbQuery = $sql;
        $this->_query($sql);
        $this->_execute();
        return $this->_result($type);
    }

    public function first($type=null)
    {
        $this->limit(1);
        $sql  = ("SELECT {$this->tableColumn} FROM `$this->table` {$this->where} {$this->groupBy} {$this->orderBy} {$this->having} {$this->limit} {$this->offset} ");
        $this->dbQuery = $sql;
        $this->_query($sql);
        $this->_execute();
        return $this->_result($type)[0];
    }

    public function toJson($result)
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        return json_encode($result);
    }




    /*******************************************************
     *
     *  MYSQL SQL FUNCTIONS
     *
     *******************************************************/



    public function count()
    {
        $sql = $this->aggregateFunctions("count");
        $this->dbQuery = $sql;
        $this->executeQuery($sql);
        return $this->_result()[0]->total;
    }

    public function sum()
    {
        $sql = $this->aggregateFunctions("sum");
        $this->dbQuery = $sql;
        $this->executeQuery($sql);
        return $this->_result()[0]->total;
    }


    public function max()
    {
        $sql = $this->aggregateFunctions("max");
        $this->dbQuery = $sql;
        $this->executeQuery($sql);
        return $this->_result()[0]->total;
    }


    public function min()
    {
        $sql = $this->aggregateFunctions("min");
        $this->dbQuery = $sql;
        $this->executeQuery($sql);
        return $this->_result()[0]->total;
    }



    /*******************************************************
     *
     *  Database IUD FUNCTIONS
     *
     *******************************************************/


    public function insert($dataWithColumn , $wantResult = false , $primaryFieldName = "id" , $type=null)
    {

        if(empty($dataWithColumn))
        {
            $this->errors = "no data provided. in second argument";
            return false;
        }

        $columns = implode(" , " , $this->arrayKeys($dataWithColumn));

        $query = "INSERT INTO `$this->table` ($columns) values (";
        $count = 1;

        $bind = [];
        $tEIA = count($dataWithColumn);

        foreach($dataWithColumn as $column => $value)
        {
            $query .= ($count == $tEIA) ? "?" : "?,";
            $bind[$count] = $value;
            $count++;
        }//foreach end
        $query .= ")";

        $this->_query($query);
        $this->_bind($bind);

        $this->dbQuery = $query;


        if( $this->_execute())
        {
            if($wantResult)
            {
                $this->primaryKey($primaryFieldName);
                $id = $this->getInsertedID();
                $this->dbResult = $this->where("$this->primaryKey = $id ")->first();
            }
            return true;
        }
        return false;
    }//end insert

    public function update($values)
    {

        $bind = [];
        $query = "UPDATE `$this->table` SET " ;
        $count = 1;

        $counter = count($values);
        foreach ($values as $key => $value)
        {
            if($count == $counter)
                $query .= "$key=?";
            else
                $query .= "$key=?,";

            $bind[$count] = $value ;
            $count++;
        }
        $query .= " $this->where $this->limit $this->offset ";
        $this->_query($query);
        $this->_bind($bind);

        $this->dbQuery = $query;

        if( $this->_execute())
        {
            return true;
        }
        return false;
    }//update end

    public function delete()
    {
        $sql = "DELETE FROM `$this->table` $this->where $this->limit $this->offset";
        return $this->executeQuery($sql);
    }



    /*******************************************************
     *
     *  Database QUERY FUNCTIONS
     *
     *******************************************************/


    public function table($name)
    {
        $this->table = $name;
        return $this;
    }

    public function field($field)
    {
        $this->field = $field;
        return $this;
    }

    public function primaryKey($field = "id")
    {
        $this->primaryKey = $field;
        return $this;
    }

    public function where($string)
    {
        $this->where = "WHERE $string";
        return $this;
    }

    public function orderBy($string)
    {
        $this->orderBy = "ORDER BY $string";
        return $this;
    }

    public function groupBy($string)
    {
        $this->groupBy = "GROUP BY $string";
        return $this;
    }

    public function having($string)
    {
        $this->having = "HAVING $string";
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = "LIMIT $limit";
        return $this;
    }

    public function offset($limit)
    {
        $this->limit = "OFFSET $limit";
        return $this;
    }


    /*******************************************************
     *
     *  PRIVATE HELPER FUNCTIONS
     *
     *******************************************************/


    private function aggregateFunctions($type)
    {
        $sql  = ("SELECT $type($this->tableColumn) AS total FROM `$this->table` {$this->where} {$this->groupBy} {$this->orderBy} {$this->having} {$this->limit} {$this->offset} ");
        return $sql;
    }

    private function getResultType($type)
    {
        switch (strtolower($type))
        {

            case "assoc" :
            case "arr" :
            case "array" : {
                return PDO::FETCH_ASSOC;
                break;
            }
            case "obj" :
            case null : {
                return PDO::FETCH_OBJ;
                break;
            }
            case "both" :{
                return PDO::FETCH_BOTH;
                break;
            }
            case "bound" :{
                return PDO::FETCH_BOUND;
                break;
            }
            case "class" :{
                return PDO::FETCH_CLASS;
                break;
            }
            case "into" :{
                return PDO::FETCH_INTO;
                break;
            }
            case "lazy" :{
                return PDO::FETCH_LAZY;
                break;
            }
            case "named" :{
                return PDO::FETCH_NAMED;
                break;
            }
            case "props_late" :{
                return PDO::FETCH_PROPS_LATE;
                break;
            }
            default:{
                die("Invalid Option");
                break;
            }
        }//switch end

    }//getResultType

    private function nvl($string , $default = "")
    {
        return empty($string) ? $default : $string;
    }

    private function arrayKeys($array)
    {
        $keys = [];
        //multiarray
        if(isset($array[0]) && is_array($array[0])) {
            foreach ($array as $key => $value) {
                $key = $value[0];
                array_push( $keys , $key );
            }
            return $keys;
        }
        return array_keys($array);
    }//end



    /*******************************************************
     *
     *  GETTER FUNCTIONS
     *
     *******************************************************/

    public function getSql()
    {
        return $this->dbQuery;
    }


    public function getErrors()
    {
        return $this->errors;
    }

    public function getResult()
    {
        return $this->dbResult;
    }

    public function getExecutedSql()
    {
        return $this->stmt->debugDumpParams();
    }

    public function getInsertedID()
    {
        $id = $this->dbh->lastInsertId();
        if($id) {
            return $id;
        }
        return null;
    }

    /*****************************************************************************
     *  DATABASE MIGRATION
     *****************************************************************************/



    /*******************************************************
     *
     *  Database DESCRIPTION FUNCTIONS
     *
     *******************************************************/

    public function describe()
    {
        $executed  = $this->executeQuery("describe $this->table ");
        if($executed)
        {
            $data =  $this->_result();
            $result = "<table border='1' style='text-align: center;'>";
            $result .= "<tr>";
            $result .= "<th colspan='6' style='text-align:center;'>{$this->table}</th>";
            $result .= "</tr>";
            $result .= "<tr>";
            $result .= "<th>Field</th> <th>Type</th> <th>Null</th> <th>Key</th> <th>Default</th> <th>Extra</th>" ;
            $result .= "</tr>";
            foreach ($data as $key => $describe)
            {
                $result .= "<tr>";
                $result .= "<td>{$describe->Field}</td>
								<td>{$describe->Type}</td>
								<td>{$describe->Null}</td>
								<td>{$describe->Key}</td>
								<td>{$describe->Default}</td>
								<td>{$describe->Extra}</td> " ;
                $result .= "</tr>";
            }

            $result .= "</table>";
            return $result;
        }
        return "";
    }

    public function showCreate($type = "t")
    {
        if(in_array(strtolower($type) , ['table' , 't']))
        {
            $type = "Table";
        }
        elseif(in_array($type , ['view' , 'v']))
        {
            $type = "View";
        }

        $execute = $this->executeQuery("SHOW CREATE $type $this->table");
        if($execute)
        {
            return wordwrap(get_object_vars($this->_result()[0])["Create " . $type]);
        }
        return "";
    }

    public function showColumns($newLine = false)
    {
        $execute = $this->executeQuery("show columns from $this->table ");
        if($execute){
            $result = $this->_result();
            $newLine = ($newLine) ? "<br>\n" : "";
            return  implode(" , $newLine", array_column( $result  , 'Field') );
        }
        return "";
    }




    /*******************************************************
     *
     *  MIGRATION QUERY BUILDER
     *
     *******************************************************/


    public function startQuery($actionType , $replace = false)
    {
        $this->actionType = $actionType ;

        switch ($actionType)
        {
            case "create-table":{
                $this->dbQuery = "CREATE TABLE";
                $this->dbQuery .= ($replace) ? " " : " IF NOT EXISTS ";
                $this->dbQuery .= "`" . $this->table . "`" . " ( \n";
                break;
            }
            case "drop-table":{
                $this->dbQuery = "DROP TABLE";
                $this->dbQuery .= ($replace) ? " IF EXISTS " : " ";
                $this->dbQuery .= "`" .  $this->table . "`" . " \n";
                break;
            }
            default : {
                die("Function: startQuery , Invalid parameters");
                break;
            }
        }
        return $this;

    }//startCreate end

    public function endQuery()
    {
        $this->generateColumns();
        switch ($this->actionType)
        {
            case "create-table":{
                $this->dbQuery .= ") \n";
                break;
            }
            default : {
                $this->dbQuery .= "";
                break;
            }
        }
        $this->clearData();
    }

    public function dropTable()
    {
        $this->startQuery("drop-table" , "$this->table");
        $this->endQuery();
        $query = $this->getSql();
        if($this->executeQuery($query))
        {
            echo " Table `$this->table` dropped successfully`";
        }
        else
        {
            echo "FAILED TO DROP TABLE $this->table " . $this->getErrors();
        }
    }

    public function executeMigration()
    {
        $sts = $this->executeQuery($this->dbQuery);

        if($sts)
        {
            $this->clearVariables();
            return true;
        }
        return false;
    }

    /*********************************************************
     *  UNSIGNED INTEGER
     *********************************************************/


    public function unsignedInteger($columnName , $length = 11)
    {
        $integerType  =   "INT($length) UNSIGNED";
        $this->columns[] = "`$columnName` $integerType";
        return $this;
    }

    public function unsignedTinyInteger($columnName , $length = 11)
    {
        $integerType  =   "TINYINT($length) UNSIGNED";
        $this->columns[] = "`$columnName` $integerType";
        return $this;
    }

    public function unsignedSmallInteger($columnName , $length = 11)
    {
        $integerType  =   "SMALLINT($length) UNSIGNED";
        $this->columns[] = "`$columnName` $integerType";
        return $this;
    }

    public function unsignedMediumInteger($columnName , $length = 11)
    {
        $integerType  =   "MEDIUMINT($length) UNSIGNED";
        $this->columns[] = "`$columnName` $integerType";
        return $this;
    }

    public function unsignedLongInteger($columnName , $length = 11)
    {
        $integerType  =   "LONGINT($length) UNSIGNED";
        $this->columns[] = "`$columnName` $integerType";
        return $this;
    }


    /*********************************************************
     *  INTEGER
     *********************************************************/


    public function integer($columnName , $length = 11)
    {
        $dbdriver = $this->driver;
        $integerType  = ($dbdriver == "sqlite") ? "INTEGER" : "INT($length)";
        $this->columns[] = "`$columnName` $integerType";
        return $this;
    }

    public function tinyInteger($columnName , $length = 4)
    {
        $this->columns[] = "`$columnName` TINYINT($length)";
        return $this;
    }

    public function smallInteger($columnName , $length = 11)
    {
        $this->columns[] = "`$columnName` SMALLINT($length)";
        return $this;
    }

    public function mediumInteger($columnName , $length = 11)
    {
        $this->columns[] = "`$columnName` MEDIUMINT($length)";
        return $this;
    }

    public function bigInteger($columnName , $length = 11)
    {
        $this->columns[] = "`$columnName` BIGINT($length)";
        return $this;
    }



    /*********************************************************
     *  FLOATING
     *********************************************************/

    public function float($columnName , $total = 8, $places = 2)
    {
        $dataType  = "FLOAT($total , $places)";
        $this->columns[] = "$columnName $dataType";
        return $this;
    }

    public function double($columnName, $total = 10, $places = 2)
    {
        $dataType  = ($total == null && $places == null ) ? "DOUBLE" :  "DOUBLE($total , $places)";
        $this->columns[] = "$columnName $dataType";
        return $this;
    }

    public function decimal($columnName, $total = 10, $places = 2)
    {
        $dataType  = ($total == null && $places == null ) ? "DECIMAL" :  "DECIMAL($total , $places)";
        $this->columns[] = "$columnName $dataType";
        return $this;
    }


    /*********************************************************
     *  STRING
     *********************************************************/


    public function char($columnName, $length = 255)
    {
        $stringType = "CHAR($length)";
        $this->columns[] = "$columnName $stringType";
        return $this;
    }

    public function string($columnName, $length = 255)
    {
        $dbdriver = $this->driver;
        $stringType = ($dbdriver == "sqlite") ? "TEXT" : "VARCHAR($length)";
        $this->columns[] = "`$columnName` $stringType";
        return $this;
    }

    public function text($columnName)
    {
        $this->columns[] = "`$columnName` TEXT";
        return $this;
    }

    public function tinyText($columnName)
    {
        $this->columns[] = "`$columnName` tinytext";
        return $this;
    }

    public function mediumText($columnName)
    {
        $this->columns[] = "`$columnName` MEDIUMTEXT";
        return $this;
    }

    public function longText($columnName)
    {
        $this->columns[] = "`$columnName` LONGTEXT";
        return $this;
    }



    /*********************************************************
     *  DATE TIME
     *********************************************************/


    public function datetime($columnName)
    {
        $this->columns[] = "`$columnName` DATETIME";
        return $this;
    }



    /*********************************************************
     *  BOOLEAN
     *********************************************************/

    public function boolean($columnName , $default = null)
    {
        $default = ($default == null) ? "" : "DEFAULT $default";
        $dataType  = "TINYINT(1) $default";
        $this->columns[] = "`$columnName` $dataType";
        return $this;
    }

    public function enum($columnName , $value)
    {
        $value = explode( ',',$value);

        $values = "";
        $countValues = count($value) - 1;
        foreach($value as $k => $v)
        {
            $values .= "'";
            $values .= $v ;
            $values .= ($k < $countValues) ? "'," : "'";
        }
        $dataType  = "ENUM($values)";
        $this->columns[] = "`$columnName` $dataType";
        return $this;
    }


    /*********************************************************
     *  INCREMENT
     *********************************************************/

    public function increments($columnName = "id"  , $length=11 , $unsigned = true , $primary = true)
    {
        $dbdriver = $this->driver;
        $datatype = ($dbdriver == "sqlite") ? "INTEGER" : "INT($length)";
        $notNull = ($dbdriver == "sqlite") ? "" : "NOT NULL";
        $auto = ($dbdriver == "sqlite") ? "AUTOINCREMENT" : "AUTO_INCREMENT";
        $primary = ($primary) ? "PRIMARY KEY" : "";
        $unsigned = ( ($dbdriver == "mysql") && $unsigned) ? "UNSIGNED" : "" ;
        $this->columns[] = "`$columnName` $datatype $unsigned $notNull $primary $auto ";
        return $this;
    }

    public function tinyIncrements($columnName = "id"  , $unsigned = true , $primary = true)
    {
        $dbdriver = $this->driver;
        $auto = ($dbdriver == "sqlite") ? "AUTOINCREMENT" : "AUTO_INCREMENT";
        $primary = ($primary) ? "PRIMARY KEY" : "";
        $unsigned = ( ($dbdriver == "mysql" ) && $unsigned) ? "UNSIGNED" : "" ;
        $this->columns[] = "`$columnName` TINYINT(11) NOT NULL $unsigned $auto $primary";
        return $this;
    }

    public function smallIncrements($columnName = "id"  , $unsigned = true , $primary = true)
    {
        $dbdriver = $this->driver;
        $auto = ($dbdriver == "sqlite") ? "AUTOINCREMENT" : "AUTO_INCREMENT";
        $primary = ($primary) ? "PRIMARY KEY" : "";
        $unsigned = ( ($dbdriver == "mysql" ) && $unsigned) ? "UNSIGNED" : "" ;
        $this->columns[] = "`$columnName` SMALLINT(11) NOT NULL $unsigned $auto $primary";
        return $this;
    }

    public function mediumIncrements($columnName = "id"  , $unsigned = true , $primary = true)
    {
        $dbdriver = $this->driver;
        $auto = ($dbdriver == "sqlite") ? "AUTOINCREMENT" : "AUTO_INCREMENT";
        $primary = ($primary) ? "PRIMARY KEY" : "";
        $unsigned = ( ($dbdriver == "mysql") && $unsigned) ? "UNSIGNED" : "" ;
        $this->columns[] = "`$columnName` MEDIUMINT(11) NOT NULL $unsigned $auto $primary";
        return $this;
    }

    public function bigIncrements($columnName = "id"  , $unsigned = true , $primary = true)
    {
        $dbdriver = $this->driver;
        $auto = ($dbdriver == "sqlite") ? "AUTOINCREMENT" : "AUTO_INCREMENT";
        $primary = ($primary) ? "PRIMARY KEY" : "";
        $unsigned = ( ($dbdriver == "mysql") && $unsigned) ? "UNSIGNED" : "" ;
        $this->columns[] = "`$columnName` BIGINT(11) NOT NULL $unsigned $auto $primary";
        return $this;
    }


    public function foreign($key , $table , $reference)
    {
        $this->columns[] = "FOREIGN KEY ($key) REFERENCES $table($reference)";
        return $this;
    }

    public function active($columnName , $length = 1 , $default = 1)
    {
        $this->columns[] = "`$columnName` TINYINT($length) UNSIGNED NOT NULL DEFAULT " . $this->whichDataType($default);
        return $this;
    }


    public function onUpdate($action = "CASCADE")
    {
        $this->addColumn("ON UPDATE $action");
        return $this;
    }

    public function onDelete($action = "CASCADE")
    {
        $this->addColumn("ON DELETE $action");
        return $this;
    }

    public function key($columns , $indexName = "")
    {
        $columns = str_replace(',' , '`,`' , $columns);
        $this->columns[] = "KEY `$indexName` (`$columns`)";
        return $this;
    }

    public function uniqueKey($columns , $indexName = "")
    {
        $columns = preg_replace('/\s+/', '', $columns);
        $columns = trim(str_replace(',' , '`,`' , $columns));
        $this->columns[] = "UNIQUE KEY `$indexName` (`$columns`)";
        return $this;
    }

    public function primary()
    {
        $this->addColumn("PRIMARY KEY");
        return $this;
    }


    public function unique()
    {
        $this->addColumn("UNIQUE");
        return $this;
    }


    public function unsigned()
    {
        $this->addColumn("UNSIGNED");
        return $this;
    }

    public function nullable()
    {
        $this->addColumn("NULL");
        return $this;
    }

    public function comment($string = "")
    {
        $dbdriver = $this->driver;
        $comment = ($dbdriver == "sqlite") ? "/* '$string' */ " : "COMMENT '$string'";
        $this->addColumn("$comment" );
        return $this;
    }


    public function notNullable()
    {
        $this->addColumn("NOT NULL");
        return $this;
    }

    public function default($value = "")
    {
        $this->addColumn("DEFAULT " . $this->whichDataType($value));
        return $this;
    }


    public function currentTimeStamp()
    {
        $this->addColumn("DEFAULT CURRENT_TIMESTAMP");
        return $this;
    }



    /*******************************************************
     *
     *  PRIVATE FUNCTIONS
     *
     *******************************************************/

    private function clearData()
    {
        $this->columns = [];
    }


    private function generateColumns()
    {
        $count = count($this->columns);
        foreach($this->columns  as $i => $q)
        {
            $this->dbQuery .= "$q";
            $this->dbQuery .= ($i < $count - 1 ) ? ",\n" : "\n";
        }
    }


    private function addColumn($value)
    {
        $index = count($this->columns) - 1 ;
        $this->columns[$index] = $this->columns[$index] . " " . $value ;
    }//addColumn end


    private function whichDataType($value)
    {
        if(is_int($value))
        {
            return $value;
        }
        return  "'" . $value . "'";
    }

    private function clearVariables()
    {
        $this->table = "";
        $this->field = "*";
        $this->primaryKey = "id";

        $this->where = "";
        $this->orderBy = "";
        $this->groupBy = "";
        $this->having = "";
        $this->limit = "";
        $this->offset = "";

    }


}//class end
