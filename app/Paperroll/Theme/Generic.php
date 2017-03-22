<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Theme;

class Generic
{
    /** @var  string */
    protected $_template;

    /** @var  string */
    protected $_html;

    protected $viewDir;

    /** @var  array */
    protected $_data;

    /**
     * Generic constructor.
     */
    public function __construct() {
        $this->viewDir = BASEDIR . '/app/Paperroll/view/';
    }

    /**
     * @param string $file
     */
    public function setTemplate($file = '') {
//        if($file == '') {
//            $class = get_called_class();
//            $class = str_replace("Paperroll\\Theme\\", '', $class);
//            $class = str_replace("\\", '/', $class);
//            $file = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $class);
//            $file = strtolower($file);
//            $file = $this->viewDir . $file . '.phtml';
//        }
        $this->_template = $file;
    }

    public function toHtml() {
        return '';
    }

    /**
     * Replace tags with their assigned data
     */
    public function tagAll() {
        preg_match_all("#{{([^}]+?)}}#", $this->_html, $tags);
        if(count($tags[1])) {
            foreach ($tags[1] as $tag) {
                //if($this->getData($tag) == '') echo "$tag ";
                $this->_html = str_replace('{{' . $tag . '}}', $this->getData($tag), $this->_html);
            }
            //echo "\n";
        }
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getData($key = null) {
        if($key) {
            if (isset($this->_data[$key]))
                return $this->_data[$key];
            return '';
        }
        return $this->_data;
    }

    /**
     * @param string|array $key
     * @param string|null $value
     * @internal param array $data
     */
    public function setData($key, $value = null) {
        if(is_array($key)) {
            foreach($key as $i => $v)
                $this->_data[$i] = $v;
        } else {
            $this->_data[$key] = $value;
        }
    }

    /**
     * @return string
     */
    function __toString() {
        try {
            return $this->toHtml();
        } Catch (\Exception $e) {
            return $e->getMessage()."\n".$e->getTraceAsString();
        }
    }

}