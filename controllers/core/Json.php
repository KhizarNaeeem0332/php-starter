<?php


namespace Bindeveloperz;


class Json
{

    private $file = null;

    public function __construct($filenameWithPath)
    {
        $this->file = $filenameWithPath;

        if(!file_exists($this->file))
        {
            die("File Not Exist");
        }
    }


    public function getAll($assoc = true)
    {
        $data = file_get_contents($this->file);
        return json_decode($data, $assoc);
    }


    public function getData( $node = null , $assoc = true)
    {
        $data = file_get_contents($this->file);
        $decodedData = json_decode($data, $assoc);

        if($node == null)
        {
            return $decodedData;
        }
        if ($assoc == false) {
            return $decodedData->$node;
        }
        return $decodedData["$node"];
    }



    public function getDataByID( $node = null, $id , $assoc = true)
    {
        $data = file_get_contents($this->file);
        $decodedData = json_decode($data, $assoc);

        if($node == null)
        {
            return $decodedData[$id];
        }

        if ($assoc == false) {
            return $decodedData->$node;
        }
        return $decodedData["$node"][$id];
    }


    public function checkRecordExist($record , $key  , $value)
    {
        if(empty($record))
        {
            return false;
        }
        else
        {
            foreach ($record as $k => $v)
            {
                if($record[$k][$key] == $value)
                {
                    return true;
                }
            }
            return false;
        }
    }


    public function insert( $node, $data)
    {
        $checkNodeExist = $this->checkNodeExist($this->file , $node);
        if(!$checkNodeExist) {
            return ['error' => true, 'msg' => " '$node': node not exist."];
        }
        else
        {
            $records = $this->getAll($this->file);

            $records["$node"] = array_values($records["$node"]);

            array_push($records["$node"], $data);

            $sts = file_put_contents($this->file, json_encode($records));

            if ($sts) {
                return ['error' => false, 'msg' => 'Record added successfully.'];
            } else {
                return ['error' => true, 'msg' => 'Record failed to added.'];
            }
        }

    }

    public function insertNode( $node)
    {
        $records = $this->getAll($this->file);
        $records["$node"] = [];

        $sts = file_put_contents( $this->file , json_encode($records));

        if ($sts) {
            return ['error' => false, 'msg' => 'Node added successfully.'];
        } else {
            return ['error' => true, 'msg' => 'Node failed to added.'];
        }

    }


    function updateNode( $oldName, $newNode)
    {
        $records = $this->getAll($this->file);
        $checkNodeExist = $this->checkNodeExist($this->file , $oldName);
        if($checkNodeExist) {
            foreach ($records as $key => $val) {
                if ($key == "$oldName") {
                    unset($records["$oldName"]);
                    $records["$newNode"] = $val;
                }
            }
            $sts = file_put_contents($this->file ,  json_encode($records));
            if ($sts) {
                return ['error' => false, 'msg' => 'Node updated successfully.'];
            } else {
                return ['error' => true, 'msg' => 'Node failed to updated.'];
            }
        }
        else {
            return ['error' => true, 'msg' => " '$oldName': node not exist."];
        }

    }


    function deleteNode( $node)
    {
        $records = $this->getAll($this->file);
        $checkNodeExist = $this->checkNodeExist($this->file , $node);
        if($checkNodeExist) {
            foreach ($records as $key => $val) {
                if ($key == "$node") {
                    unset($records["$node"]);
                }
            }
            $sts = file_put_contents($this->file, json_encode($records));
            if ($sts) {
                return ['error' => false, 'msg' => 'Node delete successfully.'];
            } else {
                return ['error' => true, 'msg' => 'Node failed to delete.'];
            }
        }
        else {
            return ['error' => true, 'msg' => " '$node': node not exist."];
        }


    }//deleteNode end


    public function update( $node, $id, $data)
    {
        $records = $this->getAll($this->file);
        $records["$node"] = array_values($records["$node"]);
        $records["$node"][$id] = $data;
        $sts = file_put_contents($this->file ,  json_encode($records));

        if ($sts) {
            return ['error' => false, 'msg' => 'Data updated successfully.'];
        } else {
            return ['error' => true, 'msg' => 'Data failed to updated.'];
        }

    }


    private function loadByNode( $node)
    {
        if (!file_exists($this->file)) {
            die("$this->file file failed to load.");
        }

        $data = file_get_contents($this->file);
        $decodedData = json_decode($data, true);
        return $decodedData["$node"];

    }


    public function delete( $node, $id)
    {

        $records = $this->getAll($this->file);

        $this->checkIdExist( $node, $id, "Invalid Id: $id");

        $records["$node"] = array_values($records["$node"]);

        //remove element from array
        unset($records["$node"][$id]);

        $sts = file_put_contents($this->file , json_encode($records));
        if ($sts) {
            return ['error' => false, 'msg' => 'Record deleted successfully.'];
        } else {
            return ['error' => true, 'msg' => 'Record failed to delete.'];
        }

    }


    private function checkIdExist( $node, $id, $msg)
    {
        $records = $this->loadByNode( $node);
        if (!array_key_exists($id, $records)) {
            die($msg);
        }
    }

    public function checkNodeExist($node)
    {
        $records = $this->getAll($this->file);
        if (!array_key_exists($node, $records)) {
            return false;
        }
        return true;
    }


    /**
     * @param $result
     * @return array
     * description : return key or get_file_contont json file
     */
    public function getNodeListOfResult($result)
    {
        return array_keys($result);
    }




}