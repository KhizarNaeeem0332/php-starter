<?php

namespace Bindeveloperz;
use \PDO;
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


// Todo : get all columns of table using command line tool
// Todo : Error Handling

/**
 * Class KDB
 */
class Database
{

	private static $instance = null;
	private $_dbh;
	private $_stmt ;
    private static $config = [];


    private $driver = "";
    private $database = "";
    private $host = "";
    private $username =  "";
    private $password =  "";
    private $port = "";
    private $option = "";

    private $whereStr = "";
    private $orderByStr = "";
    private $groupByStr = "";
    private $havingStr = "";

    protected $table = "";
    protected $columns = [];
    protected $query = "";
    protected $errors = null;
    protected $actionType = "";


    /**
     * KDB constructor.
     * @throws Exception
     * @internal param array $config driver, host, Controllers, username, password, port, option
     */
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
            $this->_dbh = new PDO($dns , $this->username , $this->password , $options) ;
		}
		catch (PDOException $e)
		{
			throw new Exception("<pre>" . $e->getMessage() . "</pre>");
		}

    }//constructor end

    public static function getInstance($config=[])
	{
	    self::$config = $config;
		if(!isset(self::$instance))
		{
			return self::$instance = new Database();
		}
		return self::$instance;
	}

    private function execute()
    {
        try
        {
            return $this->_stmt->execute();
        }
        catch (Exception $ex)
        {
            die($ex->getMessage() . "<br> \n" . $ex->getTraceAsString());
        }
    }

    private function onlyOne($type = null)
    {
        $this->execute();
        $resultType = $this->getResultType($type);
        $result = $this->_stmt->fetch($resultType);
        if(empty($result))
        {
            return null;
        }
        return $result;
    }

    private function bind($values , $type = null)
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
            $this->_stmt->bindValue($param , $value , $type);
        }
    }

    private function result($type = null)
    {
        $this->execute();
        $resultType = $this->getResultType($type);
        return $this->_stmt->fetchAll($resultType);
    }



    /*******************************************************
     *
     *  Query Helper Functions
     *
     *******************************************************/


    public function queryWithResult($query , $type = null)
	{
		$this->_stmt = $this->_dbh->prepare($query);
        $result = $this->result($type);
        if($result) {
            return $result;
        }
        else {
            die( "Error while executing : " . $query . "<br> Sql Says : " . $this->getErrors());
        }
	}

    public function query($query)
    {
        $this->_stmt = $this->_dbh->prepare($query);
    }

    public function queryWithExecute($query)
    {
        $this->_stmt = $this->_dbh->prepare($query);
        $result = $this->execute();
        if($result) {
            return true;
        }
        else {
            die( "Error while executing : " . $query . "<br> Sql Says : " . $this->getErrors());
        }
    }

    public function sqlCount()
    {
        return $this->_stmt->rowCount();
    }

    public function count($table)
    {
        $this->queryWithResult("select count(*) found from $table $this->whereStr  $this->groupByStr $this->orderByStr $this->havingStr");
        return $this->result()[0]->found;
    }

    public function sum($field, $tablename)
    {
        $this->queryWithResult("select sum($field) total from $tablename $this->whereStr  $this->groupByStr $this->orderByStr $this->havingStr ");
        return $this->result()[0]->total;
    }

    public function max($field, $tablename)
    {
        $this->queryWithExecute("SELECT MAX($field) total FROM $tablename $this->whereStr  $this->groupByStr $this->orderByStr $this->havingStr");
        return  $this->result()[0]->total;
    }

    public function lastInsertId()
    {
        $id = $this->_dbh->lastInsertId();
        if($id) {
            return $id;
        }
        return null;
    }

    public function beginTransaction()
    {
        return $this->_dbh->beginTransaction();
    }

    public function endTransaction()
    {
        return $this->_dbh->commit();
    }

    public function cancelTransaction()
    {
        return $this->_dbh->rollBack();
    }

//    public function leftJoin($lefttable, $righttable, $matchedrow, $fields, $order , $type="")
//    {
//        $sql  = ("SELECT $fields FROM $lefttable LEFT JOIN $righttable ON $lefttable.$matchedrow = $righttable.$matchedrow $order");
//        $this->queryWithResult($sql);
//        if (!$this->execute()) {
//            die( "Could not successfully run query ($sql) from DB: select where 2");
//        }
//        return $this->result($type);
//    }


    /*******************************************************
     *
     *  Database CRUD FUNCTIONS
     *
     *******************************************************/

    public function insert($tableName , $dataWithColumn)
    {

        if(empty($dataWithColumn))
        {
            $this->errors = "no data provided. in second argument";
            return false;
        }

        $columns = implode(" , " , $this->arrayKeys($dataWithColumn));

        $query = "INSERT INTO {$tableName} ($columns) values (";
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

        $this->query($query);
        $this->bind($bind);

        if($this->execute()) {
            return true;
        }
        return false;
    }//end insert

    public function update($tablename, $values, $where)
    {

        $bind = [];
        $query = "UPDATE $tablename SET " ;
        $count = 1;

        $counter = count($values);
        foreach ($values as $key => $value)
        {
            if($count == $counter)
            {
                $query .= "$key=?";
            }
            else
            {
                $query .= "$key=?,";
            }
            $bind[$count] = $value ;
            $count++;
        }
        $query .= " $where";
        $this->query($query);
        $this->bind($bind);

        return $this->execute();
    }//update end

    public function delete($tablename, $where)
    {
        $this->query("DELETE FROM $tablename $where");
        return $this->execute();
    }


    /*******************************************************
     *
     *  Database SELECT FUNCTIONS
     *
     *******************************************************/


    public function all($tablename , $type=null)
    {
        $sql  = ("SELECT * FROM $tablename $this->whereStr $this->groupByStr $this->orderByStr $this->havingStr ");
        $this->query = $sql;
        $this->queryWithResult($sql);
        if (!$this->execute()) {
            die("Could not successfully run query ($sql) from DB: method all");
        }
        return $this->result($type);
    }

    public function get($fields, $tablename , $type=null)
    {
        $sql = ("SELECT $fields FROM $tablename $this->whereStr $this->groupByStr $this->orderByStr $this->havingStr ");
        $this->query = $sql;
        $this->queryWithResult($sql);
        if (!$this->execute() ) {
            return "Could not successfully run query ($sql) from DB: method get";
            exit;
        }
        return $this->result($type);
    }

    public function executedQuery()
    {
        return $this->_stmt->debugDumpParams();
    }

    public function lastId()
    {
        $this->_dbh->lastInsertId();
    }

    public function first($table , $where = "",$type=null)
    {
        $this->queryWithResult("select * from {$table} {$where}");
        return $this->onlyOne($type);
    }

    public function firstGet($fields , $table , $where = "",$type=null)
    {
        $this->queryWithResult("select {$fields} from {$table} {$where}");
        return $this->onlyOne($type);
    }

    public function toJson($result)
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        return json_encode($result);
    }


    /*******************************************************
     *
     *  Database QUERY FUNCTIONS
     *
     *******************************************************/


    public function where($string)
    {
        $this->whereStr = "WHERE $string";
        return $this;
    }

    public function orderBy($string)
    {
        $this->orderByStr = "ORDER BY $string";
        return $this;
    }

    public function groupBy($string)
    {
        $this->groupByStr = "GROUP BY $string";
        return $this;
    }

    public function having($string)
    {
        $this->havingStr = "HAVING $string";
        return $this;
    }


    public function createTable($query)
    {
       return  $this->queryWithExecute($query);
    }

    public function showCreate($table , $type = "t")
    {
        if(in_array(strtolower($type) , ['table' , 't']))
        {
            $type = "Table";
        }
        elseif(in_array($type , ['view' , 'v']))
        {
            $type = "View";
        }

        $this->query("SHOW CREATE $type $table");
        if($this->execute())
        {
            return wordwrap(get_object_vars($this->result()[0])["Create " . $type]);
        }
        return "";
    }

    public function describeTable($tablename)
    {
        $executed  = $this->queryWithExecute("describe $tablename ");
        if($executed)
        {
            $data =  $this->result();
            $result = "<table border='1' style='text-align: center;'>";
            $result .= "<tr>";
            $result .= "<th colspan='6' style='text-align:center;'>{$tablename}</th>";
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

    public function showColumns($table , $newLine = false)
    {
        $this->queryWithResult("show columns from $table ");
        $result = $this->result();
        $newLine = ($newLine) ? "<br>\n" : "";
        return  implode(" , $newLine", array_column( $result  , 'Field') );
    }


    /*******************************************************
     *
     *  Database CUSTOM FUNCTIONS
     *
     *******************************************************/

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

    private function nvl($string , $default = "")
    {
        return empty($string) ? $default : $string;
    }

    private function clearData()
    {
        $this->columns = [];
    }



    /*********************************************************
     *  DATABASE MIGRATION
     *********************************************************/


    public function startQuery($actionType , $tableName , $replace = false)
    {
        $this->actionType = $actionType ;
        $this->table =  $tableName ;

        switch ($actionType)
        {
            case "create-table":{
                $this->query = "CREATE TABLE ";
                $this->query .= ($replace) ? " " : "IF NOT EXISTS ";
                $this->query .= "`" . $tableName . "`" . " ( \n";
                break;
            }
            case "drop-table":{
                $this->query = "DROP TABLE ";
                $this->query .= ($replace) ? "IF EXISTS " : " ";
                $this->query .= "`" . $tableName . "`" . " \n";
                break;
            }
            default : {
                throw  new Exception("Invalid Action Type");
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
                $this->query .= ") \n";
                break;
            }
            default : {
                $this->query .= "";
                break;
            }
        }

        $this->clearData();

    }

    public function dropTable($table)
    {
        $this->startQuery("drop-table" , "$table");
        $this->endQuery();
        $query = $this->getSql();
        if($this->queryWithExecute($query))
        {
            echo " Table `$table` dropped successfully`";
        }
        else
        {
            echo "FAILED TO DROP TABLE $table " . $this->getErrors();
        }
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

    public function tinyInteger($columnName , $length = 11)
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

    public function primaryKey()
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


    public function table($tableName)
    {
        $this->table  = $tableName;
    }


    private function generateColumns()
    {
        $count = count($this->columns);
        foreach($this->columns  as $i => $q)
        {
            $this->query .= "$q";
            $this->query .= ($i < $count - 1 ) ? ",\n" : "\n";
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
        elseif(is_string($value))
        {
            return  "'" . $value . "'";
        }
    }


    public function getQuery()
    {
        return $this->query;
    }

    public function getErrors()
    {
        return $this->errors;
    }


    /*


$config =  [
    'driver' => 'mysql',
    'host' =>  'localhost',
    'Controllers' => '',
    'username' => 'root',
    'password' => '',
    'port' => '3306',
    "option" => true
];

     */

}//class Database End