<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Theme;

use Paperroll\Helper\Entity;
use Paperroll\Helper\File;
use Paperroll\Helper\Registry;
use Paperroll\Model\Entry;
use Paperroll\Model\ImageKind;

class Layout extends Generic
{
    /** @var array */
    protected $_data;
    /** @var bool */
    protected $_long;
    /** @var  array */
    protected $_topTen;

    /**
     * Layout constructor.
     * @param null $template
     * @param bool $long
     */
    public function __construct($template = null, $long = false) {
        parent::__construct();

        $this->_long = $long;

        if($template)
            $this->setTemplate($template);
    }

    public function setTemplate($template = 'default') {
        parent::setTemplate($this->viewDir . 'layout/' . $template . '.phtml');
        $this->_html = File::readFile($this->_template);
    }

    /**
     * @return string
     */
    public function toHtml() {
        try {
            if(!$this->_template)
                $this->setTemplate();

            if(!$this->_html)
                $this->loadLayout();

            while(preg_match("#{{([^}]+?)}}#", $this->_html))
                $this->tagAll();

            return $this->_html;
        } Catch (\Exception $e) {
            return "<pre>".$e->getMessage()."\n".$e->getTraceAsString()."</pre>";
        }
    }

    /**
     * Loads theme template/layout from file
     * and assigns data from includes
     */
    public function loadLayout() {
        if(!$this->_html)
            $this->_html = File::readFile($this->_template);

        preg_match_all("#{{%(\\S*)%}}#", $this->_html, $tags);
        if(count($tags[1])) {
            foreach ($tags[1] as $tag) {
                $blockName = "Paperroll\\Theme\\Block\\{$tag}";
                if(class_exists($blockName))
                    $block = new $blockName();
                else
                    $block = new Block($tag);

                $this->setData("%{$tag}%", $block);
            }
        }

        $this->setDefaultData();
    }

    protected function setDefaultData() {
        $baseUrl = File::baseUrl();
        $this->setData('baseurl', $baseUrl);
        $em = Registry::get('entityManager');

        $this->setData('assetsurl', $baseUrl . Registry::get('version') . '/');

        $this->setData('date_year', date('Y'));

        $kindsHtml = [];
        /** @var ImageKind $kind */
        foreach($em->getRepository(ImageKind::class)->getVisibleKinds() as $kind) {
            $kindsHtml[(Int)$kind->getMobile()][] =
                "<li><a href='{$baseUrl}tag/{$kind->getPath()}'>{$kind->getLabel()}</a></li>";
        }
        $html = "<li><span>Desktop Computer</span><ul>".implode($kindsHtml[0])."</ul></li>";
        $html .= "<li><span>Phone</span><ul>".implode($kindsHtml[1])."</ul></li>";
        $this->setData('kinds', $html);


        /** @var \Paperroll\Model\Repository\Entry $entryRepo */
        $entryRepo = $em->getRepository(Entry::class);

        $html = "";
        foreach($entryRepo->getYears() as $year) {
            $html .= "<li><a href='{$baseUrl}tag/{$year['year']}' title='{$year['num']} entries'>{$year['year']}</a></li>";
        }
        $this->setData('tag_years', $html);


        /** @var Entry $calEntry */
        $calEntry = $entryRepo->getLastPublishedCalendar();
        $block = new Block('entry/calendar');
        $block->setData($calEntry->getBlockVariables());
        $this->setData('calendar', $block);

        /** @var Entry $entry */
        $topTen = '';
        foreach($entryRepo->getTopTen() as $entryId) {
            $block = new Block('entry/topten', $entryId);
            if(!$block->isCached()) {
                $entry = $entryRepo->find($entryId);
                $block->setData($entry->getBlockVariables());
                $block->setData('publishedAt', $entry->getPublishedAt('short'));
            }
            $this->_topTen[] = $entryId;
            $topTen .= $block->toHtml();
        }
        $this->setData('tag_10', $topTen);
    }

    /**
     * @return array
     */
    public function getTopTen()
    {
        return $this->_topTen;
    }

}