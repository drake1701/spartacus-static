<?php
/**
 * @package		PaperRoll
 * @author		Spartacus
 * @address		www.spartacuswallpaper.com
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

$old = new MySqlite("/var/www/spartacuswallpaper.com/spartacus");

unlink("/var/www/development/spartacuswallpaper.com/paperroll/var/db/spartacus");
exec("vendor/bin/doctrine orm:schema-tool:create");
$new = new MySqlite("/var/www/development/spartacuswallpaper.com/paperroll/var/db/spartacus");

$tables = array('entry','tag','image_kind','entry_tag','image', 'entry_log');

foreach($tables as $table){
    echo "$table\n";
    $listSql = "SELECT * FROM `$table`;";
    $listResult = $old->query($listSql);
    $columnInfo = $new->query("PRAGMA table_info(`$table`);");
    $newColumns = [];
    while($column = $columnInfo->fetchArray())
        $newColumns[$column['name']] = ":{$column['name']}";

    $i = 0;
    while($row = $listResult->fetchArray(SQLITE3_ASSOC)){
        $data = [];
        foreach($newColumns as $column => $placeholder){
            if($table == 'image' and $column == 'kind_id')
                $data[':kind_id'] = $row['kind'];
            else
                $data[$placeholder] = $row[$column];
        }

        $sql = "INSERT INTO `$table` (".implode(",", array_keys($newColumns)).") VALUES (".implode(",", $newColumns).");";
        $statement = $new->prepare($sql);
        foreach($data as $placeholder => $value) {
            $statement->bindValue($placeholder, $value);
        }
        $statement->execute();
        $i++;
    }
    echo "$i rows.\n";
}
