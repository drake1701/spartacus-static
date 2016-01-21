<?php
/**
 * @author		Dennis Rogers
 */
class MySqlite extends SQLite3 {

	public function query($sql) {
    	return parent::query($sql);
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
$db = new MySqlite($base_dir . "/spartacus");
