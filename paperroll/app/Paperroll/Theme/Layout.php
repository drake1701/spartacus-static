<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Theme;

use Paperroll\Helper\Entity;
use Paperroll\Helper\File;
use Paperroll\Model\Entry;
use Paperroll\Model\ImageKind;

class Layout extends Generic
{
    /** @var array */
    protected $_data;
    /** @var bool */
    protected $_long;

    /**
     * Layout constructor.
     * @param null $template
     * @param bool $long
     */
    public function __construct($template = null, $long = false) {
        parent::__construct();

        $this->_long = $long;

        if($template)
            $this->setTemplate($this->viewDir . 'layout/' . $template . '.phtml');
    }

    /**
     * @return string
     */
    public function toHtml() {
        if(!$this->_template)
            $this->setTemplate();

        if(!$this->_html)
            $this->loadLayout();

        while(preg_match("#{{([^}]+?)}}#", $this->_html))
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
        $em = Entity::init();

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
        $block->setData('title', $calEntry->getTitle());
        $block->setData('image', File::getCacheUrl($calEntry->kind.'/'.$calEntry->getFilename(), 400));
        $this->setData('calendar', $block);

        /** @var Entry $entry */
        $topTen = '';
        foreach($entryRepo->getTopTen() as $entry) {
            $block = new Block('entry/topten');
            $block->setData('title', $entry->getTitle());
            $block->setData('url', $entry->getUrl());
            $block->setData('published_at', $entry->getPublishedAt());
            $block->setData('thumb', $entry->getMainImage()->getUrl(400));
            $i = 1;
            foreach($entry->getMobileImages() as $image) {
                $block->setData("mobile_" . $i++, $image->getUrl(340));
            }
            $topTen .= $block->toHtml();
        }
        $this->setData('tag_10', $topTen);

    }

}