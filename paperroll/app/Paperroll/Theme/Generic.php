<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Theme;

use Paperroll\Helper\File;

class Generic
{
    /** @var  string */
    protected $_template;

    /** @var  string */
    protected $_html;

    /** @var  array */
    protected $_data;

    /**
     * Generic constructor.
     */
    public function __construct() {

    }

    /**
     * @param string $file
     */
    public function setTemplate($file = '') {
        if($file == '') {
            $class = get_called_class();
            $class = str_replace("Paperroll\\Theme\\", '', $class);
            $class = str_replace("\\", '/', $class);
            $file = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $class);
            $file = strtolower($file);
            $file = BASEDIR . '/app/view/' . $file . '.phtml';
        }
        $this->_template = $file;
    }

    /**
     * @return string
     */
    public function toHtml() {
        if(!$this->_template)
            $this->setTemplate();

        $this->loadLayout();

        $this->tagAll();

        return $this->_html;
    }

    /**
     * Loads theme template/layout from file
     * and assigns data from includes
     */
    public function loadLayout() {
        if(!$this->_html)
            $this->_html = File::readFile($this->_template);

        preg_match_all("#{{%(\\S*)}}#", $this->_html, $tags);
        if(count($tags[1])) {
            foreach ($tags[1] as $tag) {
                $blockName = "Paperroll\\Theme\\Block\\{$tag}";
                if(class_exists($blockName))
                    $block = new $blockName();
                else
                    $block = new Block($tag);

                $this->setData("%{$tag}", $block);
            }
        }
    }

    /**
     * Replace tags with their assigned data
     */
    public function tagAll() {
        preg_match_all("#{{([^}]+?)}}#", $this->_html, $tags);
        if(count($tags[1])) {
            foreach ($tags[1] as $tag) {
                $this->_html = str_replace('{{' . $tag . '}}', $this->getData($tag), $this->_html);
            }
        }
    }

    /**
     * @return string
     */
    function __toString() {
        return $this->toHtml();
    }

    /**
     * @param string|null $key
     * @return array
     */
    public function getData($key = null) {
        if($key && isset($this->_data[$key]))
            return $this->_data[$key];

        return $this->_data;
    }

    /**
     * @param string|array $key
     * @param string|null $value
     * @internal param array $data
     */
    public function setData($key, $value = null) {
        if(is_array($key)) {
            $this->_data = $key;
        } else {
            $this->_data[$key] = $value;
        }
    }


}