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
    /**
     * Block constructor.
     * @param null $template
     */
    public function __construct($template = null) {
        parent::__construct();

        if($template)
            $this->setTemplate($this->viewDir . 'block/' . $template . '.phtml');
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
}