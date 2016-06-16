<?php
/**
 * @author		Dennis Rogers
 */
class MySqlite extends SQLite3 {

	public function query($sql) {
    	if($result = parent::query($sql)) {
        	return $result;
    	} else {
        	die($this::lastErrorMsg());
    	}
	}

    public function fetchAll($result) {
        $resultArray = array();
        while($row = $result->fetchArray()) {
            if(!empty($row))
                $resultArray[] = $row;
        }
        return $resultArray;
    }
}
$db = new MySqlite(dirname(__FILE__) . "/spartacus");
