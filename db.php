<?php
/**
 * @author		Dennis Rogers
 */
class MySqlite extends SQLite3 {

	public function query($sql) {
    	return parent::query($sql);
	}

}
$db = new MySqlite($base_dir . "/spartacus");
