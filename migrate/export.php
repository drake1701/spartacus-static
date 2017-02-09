<?php
/**
 * @package		PaperRoll
 * @author		Spartacus
 * @address		www.spartacuswallpaper.com
 */

require_once('db.php');
exec('sqlite3 ' . dirname(__FILE__) . '/../spartacus < ' . dirname(__FILE__) . '/structure.sql');
$st = new PDO('sqlite:' . dirname(__FILE__) . '/../spartacus');

$tables = array('entry','entry_tag','image','image_kind','tag');

foreach($tables as $table){
    echo "$table\n";
    $listSql = "SELECT * FROM $table;";
    $listResult = $sp->query($listSql);
    $i = 0;
    while($row = $listResult->fetch(PDO::FETCH_ASSOC)){        
        $columns = array();
        $placeholders = array();
        foreach($row as $column => $value){
            $columns[] = "`$column`";
            $placeholders[] = ":$column";
            $data[":$column"] = $value;
        }
        $sql = "INSERT INTO `$table` (".implode(",", $columns).") VALUES (".implode(",", $placeholders).");";
        $statement = $st->prepare($sql);
        if(!$statement){
            print_r($st->errorInfo());
        }
        $statement->execute($row);
        $i++;
    }
    echo "$i rows.\n";
}

?>