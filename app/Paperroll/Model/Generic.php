<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model;


class Generic
{

    public function getData() {
        $data = get_object_vars($this);
        $blockData = [];
        foreach($data as $key => $value) {
            if(is_string($value) or is_numeric($value)) {
                $blockData[$key] = $value;
            }
        }
        return $blockData;
    }

    public function setData($data) {
        $fields = get_object_vars($this);
        foreach($fields as $field => $value) {
            if(!empty($data[$field]))
                $this->$field = $data[$field];
        }
        return $this;
    }

}