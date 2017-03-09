<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Command;

use Paperroll\Helper\Entity;
use Paperroll\Model\Entry;
use Paperroll\Helper\File;
use Paperroll\Model\Image;
use Paperroll\Model\Tag;
use Paperroll\Theme\Block;
use Paperroll\Theme\Layout;

class Generate extends Generic {

    /** @var string  */
    protected $siteUrl;

    /** @var array */
    protected $layoutData;

    /** @var array */
    protected $touchedTags;

    public function __construct( array $argv = [] ) {
        parent::__construct( $argv );
        $this->siteUrl = File::baseUrl();
    }

    public function execute() {

        $this->debug('--- Beginning Site Generation ---');

        if($this->getArg('a')) {
            $this->clearSite();
            $this->buildBanners();
            $this->buildPages();
            $this->buildEntries();
            $this->buildTagPages();
            $this->buildTagIndex();
            $this->buildKinds();
            $this->buildYears();
            $this->buildHome();
            $this->buildChangelog();
        } else {
            if($this->dev) {
                File::recurseCopy(BASEDIR . "/assets", File::siteDir());
                $em = Entity::init();
                /** @var \Paperroll\Model\Repository\Entry $entryRepo */
                $entryRepo = $em->getRepository(Entry::class);
                $entries = $entryRepo->findBy(['id' => [3754,4102]]);
                /** @var \Paperroll\Model\Entry $entry */
                foreach($entries as $entry)
                    $entryRepo->rePublish($entry);
                $em->flush();

                $this->buildBanners();
                if($this->getArg('p')) $this->buildPages();
                $this->buildEntries();
                if($this->getArg('t')) {
                    $this->buildTagPages();
                    $this->buildTagIndex();
                }
                if($this->getArg('k')) $this->buildKinds();
                if($this->getArg('y')) $this->buildYears();
                $this->buildHome();
            } else {
                $this->buildEntries();
                $this->buildTagPages();
                $this->buildTagIndex();
                $this->buildKinds();
                $this->buildYears();
                $this->buildHome();
                $this->buildChangelog();
            }
        }
        $this->logReport();
    }

    private function clearSite() {
        $this->debug('Clearing Site Files and Copying Assets');

        if(is_dir(File::siteDir() . "/gallery"))
            exec("rm " . File::siteDir() . "/gallery");
        File::delTree(File::siteDir());

        if(!is_dir(File::siteDir())) {
            mkdir( File::siteDir(), 0775 );
            chmod( File::siteDir(), 0775 );
        }

        if(!is_dir(File::siteDir() . "/gallery"))
            exec("ln -s " . BASEDIR . "/gallery " . File::siteDir() . "/gallery");

        File::recurseCopy(BASEDIR . "/assets", File::siteDir());
        copy(BASEDIR . "/assets/.htaccess.maint", File::siteDir().'/.htaccess');
    }

    private function buildBanners() {
        $this->debug('Building Banner Files');
        $banners = glob(BASEDIR . "/assets/images/banners/left/*.jpg");
        $banners = array_merge($banners, glob(BASEDIR . "/assets/images/banners/right/*.jpg"));
        sort($banners);
        $bannerCss = "";
        foreach($banners as $i => $banner){
            $parts = array_reverse(explode("/", $banner));
            $file = $parts[0];
            $align = $parts[1];
            $imageUrl = $this->siteUrl . '/images/banners/' . $align . '/' . $file;
            $topUrl = $this->siteUrl . '/images/banners/top/' . str_replace('jpg', 'png', $file);
            $bannerCss .= ".banner_$i, .banner_$i .banner-background { background-image: url({$imageUrl}); }\n";
            $bannerCss .= ".banner_$i .banner-feature { background-image: url({$topUrl}); }\n";
            if($align == "right"){
                $bannerCss .= ".banner_$i .logo { right:10px; left:auto; }\n";
            }
        }
        File::writeFile(File::siteDir()."/css/banner.css", $bannerCss);

        $bannerJs = '
            $(function() {
                var date = new Date();
                var banner = Math.floor((Math.random() * '.count($banners).'));
                $(".header-banner.banner-border").addClass("banner_"+banner);
            });
        ';
        File::writeFile(File::siteDir()."/js/banner.js", $bannerJs);

        /** @var Block $header */
        $header = new Block('header');
        $testHtml = /** @lang html */
            <<<'HTML'
        <script type="text/javascript">
        //<![CDATA[
            $(function(){
                var counter = 0;
                $(".header-banner.banner-border").each(function(){
                    $(this).attr("class", "").addClass("banner_" + counter++).addClass("header-banner").addClass("banner-border");
                });
            });
        //]]>
        </script>
        <style type="text/css">
            #sidebar { display:none; }
            .content { width:100% !important; padding:0; }
            .header-banner.banner-border {margin-bottom:10px; }
        </style>
HTML;
        $testHtml .= str_repeat("<br/>".$header, (count($banners)-1));
        $testPage = new Layout('blank');
        $testPage->setData('content', $testHtml);
        $testPage = str_replace('col-md-9', 'col-md-12', $testPage);
        File::writeFile(File::siteDir() ."/banner-test.html", $testPage);
    }

    private function buildPages() {
        $this->debug('Building Static Pages');
        $pages = glob(BASEDIR."/page/*");

        foreach($pages as $pageFile){
            $file = pathinfo($pageFile);

            $page = new Layout('default');
            $page->loadLayout();
            $page->setData('content', File::readFile($pageFile));
            $title = ucwords($file['filename']);
            $page->setData("title", $title." | ");
            $page->setData('meta_description', "Spartacus Wallpaper $title Page.");

            File::writePage("page/".$file['filename'], $page);
        }
    }

    private function buildEntries() {
        $this->debug('Build Entry Pages');

        $entryPage = new Layout('entry');
        $entryPage->loadLayout();
        $entries = $this->entityManger->getRepository(Entry::class)->getPublishable($this->getArg('a'));
        $entryCounter = 1;
        /** @var Entry $entry */
        foreach($entries as $entry) {
            if($entry instanceof Entry == false)
                $entry = $entry[0];
            $bytes_start = memory_get_usage();
            $this->debug('Building ' . $entry->getUrl());

            $entryPage->setTemplate('entry');

            $entryPage->setData('title', $entry->getTitle() . ' | ');

            $time = microtime(1);
            $entryBlock = new Block('entry/default');
            $entryData = $entry->getBlockVariables();
            $time = microtime(1) - $time;
            $this->debug(number_format($time, 8) . " : block vars");

            if($entry->getNext()->getPublished()) {
                $entryData['nextLink'] = '<a href="'.$entry->getNext()->getUrl().'" title="'.$entry->getNext()->getTitle().'"><span>Next Wallpaper &raquo;</span></a>';
                $entryData['next'] = '<a href="'.$entry->getNext()->getUrl().'" title="'.$entry->getNext()->getTitle().'"><span>Next</span><img class="lazy" data-original="'.$entry->getNext()->getMainImage()->getUrl(Image::MOBILE_THUMB).'" alt="'.$entry->getNext()->getTitle().'" /><span>'.$entry->getNext()->getTitle().'</span></a>';
            }
            if($entry->getPrev()->getPublished()) {
                $entryData['prevLink'] = '<a href="'.$entry->getPrev()->getUrl().'" title="'.$entry->getPrev()->getTitle().'"><span>&laquo; Prev Wallpaper</span></a>';
                $entryData['prev'] = '<a href="'.$entry->getPrev()->getUrl().'" title="'.$entry->getPrev()->getTitle().'"><span>Prev</span><img class="lazy" data-original="'.$entry->getPrev()->getMainImage()->getUrl(Image::MOBILE_THUMB).'" alt="'.$entry->getPrev()->getTitle().'" /><span>'.$entry->getPrev()->getTitle().'</span></a>';
            }

            $time = microtime(1);
            $desktopImages = [];
            $mobileImages = [];
            /** @var Image $image */
            foreach ($entry->getVisibleImages() as $image) {
                $fileInfo = [1600,1200];//@getimagesize($image->getPath());
                if(count($fileInfo) < 2) {
                    $this->logger->error('No file found at '.$image->getPath());
                    continue;
                }
                $thumbWidth = $image->getKind()->getPath() == 'ultrawide' ? 575 : 430;
                $imageBlock = new Block('entry/image');
                $imageData = [
                    'title'     => $entry->getTitle(),
                    'url'       => $image->getUrl(),
                    'thumb'     => $image->getUrl($thumbWidth),
                    'ratio'     => ($fileInfo[1] / $fileInfo[0]),
                    'height'    => $fileInfo[1],
                    'width'     => $fileInfo[0],
                    'kind'      => $image->getKind()->getLabel()
                ];
                $imageBlock->setData($imageData);
                if($image->getKind()->getMobile())
                    $mobileImages[] = $imageBlock->toHtml();
                else
                    $desktopImages[] = $imageBlock->toHtml();
                unset($imageBlock);
            }
            $entryBlock->setData('desktopImages', implode($desktopImages));
            $entryBlock->setData('mobileImages', implode($mobileImages));
            $time = microtime(1) - $time;
            $this->debug(number_format($time, 8) . " : images");

            $time = microtime(1);
            $entryTags = [];
            $names = [];
            /** @var Tag $tag */
            foreach($entry->getTags() as $tag) {
                $this->touchedTags[$tag->getId()] = $tag;
                $entryTags[$tag->getName()][] = '<li><a href="'.$tag->getUrl().'" title="'.$tag->getTitle().'">'.$tag->getTitle().'</a></li>';
                if($tag->getName()) $names[] = $tag->getTitle();
            }
            $tagHtml = '';
            if(!empty($entryTags[1])) {
                $tagHtml .= '<div><strong>Featuring</strong><ul>' . implode($entryTags[1]) . '</ul></div>';
                $entryPage->setData('meta_description',
                    'Desktop and mobile wallpaper featuring '
                    . strip_tags(implode(', ', $names)).'.'
                );
                foreach($names as $name) {
                    $tagHtml .= '<span class="hidden" property="about" typeof="Person"><span property="name">'.$name.'</span></span>';
                }
            }
            if(!empty($entryTags[0]))
                $tagHtml .= '<div><strong>Tagged</strong><ul>' . implode($entryTags[0]) . '</ul></div>';

            $entryData['tags'] = $tagHtml;
            $time = microtime(1) - $time;
            $this->debug(number_format($time, 8) . " : tags");

            $entryBlock->setData($entryData);

            $entryPage->setData('content', $entryBlock->toHtml());
            unset($entryBlock);

            $entryHead = new Block('entry/head');
            $entryHead->setData($entryData);
            $entryPage->setData('head', $entryHead->toHtml());
            unset($entryHead);
            unset($entryData);

            $time = microtime(1);
            /** @var \Paperroll\Model\Repository\Tag $tagRepo */
            $tagRepo = $this->entityManger->getRepository(Tag::class);
            $visibleIds = [$entry->getId(), $entry->getNext()->getId(), $entry->getPrev()->getId()];
            $visibleIds = array_merge($visibleIds, $entryPage->getTopTen());
            $contentMore = '';
            if(count($entry->getTags())) {
                foreach ($entry->getTags() as $tag) {
                    $moreBlock = new Block('tag/more', $tag->getId());
                    if($moreBlock->isCached() == false) {
                        $more = $tagRepo->getRandom($tag->getId(), $visibleIds, 3);
                        if(!count($more)) continue;
                        $moreEntries = '';
                        /** @var Entry $moreEntry */
                        foreach ($more as $moreEntry) {
                            $moreEntryBlock = new Block('entry/more', $moreEntry->getId());
                            $moreEntryBlock->setData($moreEntry->getBlockVariables());
                            $moreEntryBlock->setData('publishedAt', $moreEntry->getPublishedAt(1));
                            $moreEntries .= $moreEntryBlock->toHtml();
                            unset($moreEntryBlock);
                            $visibleIds[] = $moreEntry->getId();
                        }
                        $moreBlock->setData([
                            'url'          => $tag->getUrl(),
                            'title'        => $tag->getTitle(),
                            'more_entries' => $moreEntries
                        ]);
                    }
                    $contentMore .= $moreBlock->toHtml();
                    unset($moreBlock);
                }
            }
            if($contentMore == '') {
                $more = $tagRepo->getRandom(null, $visibleIds, 6);
                $moreEntries = '';
                /** @var Entry $moreEntry */
                foreach($more as $moreEntry) {
                    $moreEntryBlock = new Block('entry/more');
                    $moreEntryBlock->setData($moreEntry->getBlockVariables());
                    $moreEntryBlock->setData('publishedAt', $moreEntry->getPublishedAt(1));
                    $moreEntries .= $moreEntryBlock->toHtml();
                    unset($moreEntryBlock);
                    $visibleIds[] = $moreEntry->getId();
                }
                $moreBlock = new Block('tag/random');
                $moreBlock->setData('more_entries', $moreEntries);
                $contentMore .= $moreBlock->toHtml();
                unset($moreBlock);
            }
            $entryPage->setData('content_more', $contentMore);
            unset($contentMore);
            $time = microtime(1) - $time;
            $this->debug(number_format($time, 8) . " : more");

            $time = microtime(1);
            File::writePage($entry->getUrlPath(), $entryPage);
            $time = microtime(1) - $time;
            $this->debug(number_format($time, 8) . " : write");

            $entry->setPublished(1);
            $bytes_used = memory_get_usage() - $bytes_start;
            $this->debug(File::bytesToSize(memory_get_usage()) . " - " . File::bytesToSize($bytes_used));
            $this->debug($entryCounter++ . ' built');
        }
        $this->entityManger->flush();
    }

    private function buildTagPages() {
        $this->debug('Build Tag Pages');
        if($this->getArg('a')) {
            $tags = $this->entityManger->getRepository(Tag::class)->findAll();
        } else {
            $tags = $this->touchedTags;
        }

        /** @var Tag $tag */
        foreach($tags as $tag) {
            $this->debug("Building ".$tag->getUrl());
            $tagPage = new Layout('tag');
            $tagPage->loadLayout();
            $tagPage->setData('title', $tag->getTitle() . ' | ');
            $tagPage->setData('contentTitle', $tag->getTitle() . ' Wallpaper');

            $entryContent = '';
            /** @var Entry $entry */
            foreach($tag->getEntries() as $entry) {
                $entryBlock = new Block('entry/tag');
                $entryBlock->setData($entry->getBlockVariables());
                $entryContent .= $entryBlock->toHtml();
            }
            $tagPage->setData('content', $entryContent);

            File::writePage('tag/'.$tag->getSlug(), $tagPage);
        }

    }

    private function buildTagIndex() {
        $this->debug('Build Tag Index');
    }

    private function buildKinds() {
        $this->debug('Build Kind Pages');
    }

    private function buildYears() {
        $this->debug('Build Year Pages');
    }

    private function buildChangelog() {
        $this->debug('Build Changelog');
    }

    private function buildHome() {
        $this->debug('Build Homepage');

        copy(BASEDIR . "/assets/.htaccess", File::siteDir().'/.htaccess');
    }

    private function logReport() {

        $repository = $this->entityManger->getRepository(Entry::class);
        $entries = $repository->findBy(['published' => null]);

        $this->debug(count($entries) . ' left in queue');

    }

}