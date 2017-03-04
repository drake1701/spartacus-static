<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Theme;

class Block extends Generic
{
    /**
     * Block constructor.
     * @param null $template
     */
    public function __construct($template = null) {
        if($template)
            $this->setTemplate(BASEDIR . '/app/view/block/' . $template);

        parent::__construct();
    }
}