<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Theme;

use Paperroll\Helper\File;

class Block extends Generic
{
    protected $_cacheFile;
    protected $_cached = false;

    /**
     * Block constructor.
     * @param null $template
     * @param null $id
     */
    public function __construct($template = null, $id = null) {
        parent::__construct();

        if($id)
            $this->loadCache($template, $id);

        if($template)
            $this->setTemplate($template);
    }

    public function setTemplate($template = 'default') {
        parent::setTemplate($this->viewDir . 'block/' . $template . '.phtml');
    }

    /**
     * @return string
     */
    public function toHtml() {
        if(!$this->_template)
            $this->setTemplate();

        if(!$this->_html)
            $this->_html = File::readFile($this->_template);

        if(preg_match("#{{([^}]+?)}}#", $this->_html))
            $this->tagAll();

        if($this->_cacheFile && !$this->_cached)
            $this->writeCache();

        return $this->_html;
    }

    /**
     * Replace tags with their assigned data
     */
    public function tagAll() {
        preg_match_all("#{{([^}]+?)}}#", $this->_html, $tags);
        if(count($tags[1])) {
            foreach ($tags[1] as $tag) {
                if($this->getData($tag))
                    $this->_html = str_replace('{{' . $tag . '}}', $this->getData($tag), $this->_html);
            }
        }
    }

    public function isCached() {
        return $this->_cached;
    }

    protected function loadCache($template, $id) {
        $cacheTag = array_pop(explode('/', $template)) . '-' . $id;
        $this->_cacheFile = BASEDIR . '/var/cache/' . $cacheTag;
        $cache = File::readFile($this->_cacheFile);
        if(strlen($cache)) {
            $this->_cached = true;
            $this->_html = $cache;
        }
    }

    protected function writeCache() {
        File::writeFile($this->_cacheFile, $this->_html);
    }

}