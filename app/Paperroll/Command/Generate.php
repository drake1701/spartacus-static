<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Command;

use Paperroll\Helper\Registry;
use Paperroll\Model\Entry;
use Paperroll\Helper\File;
use Paperroll\Model\EntryLog;
use Paperroll\Model\Image;
use Paperroll\Model\ImageKind;
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
        if($this->getArg('a') || $this->dev) {
            $version = 'v' . time();
            File::writeFile(BASEDIR . '/assets/vsn/version.txt', $version);
        } else {
            $version = File::readFile(BASEDIR . '/assets/vsn/version.txt');
        }
        \Paperroll\Helper\Registry::set('version', $version);
    }

    public function execute() {

        $this->logger->debug('--- Beginning Site Generation ---');

        if($this->getArg('l'))
            $this->dev = false;

        if($this->getArg('a')) {
            $this->clearSite();
            $this->buildBanners();
            $this->buildPages();
            $this->buildEntries();
            $this->buildTagPages();
            $this->buildTagIndex();
            $this->buildKinds();
            $this->buildYears();
        } else {
            $this->assetsAndMaintenance();
            $this->buildBanners();

            $this->buildPages();
            $this->buildEntries();
            if($this->dev) {
                $em = $this->entityManger;
                /** @var \Paperroll\Model\Repository\Entry $entryRepo */
                $entryRepo = $em->getRepository(Entry::class);
                $entries = $entryRepo->findBy(['id' => [3754,4102,4139,4145]]);
                /** @var \Paperroll\Model\Entry $entry */
                foreach($entries as $entry)
                    $entryRepo->rePublish($entry);
                $em->flush();
                if($this->getArg('t')) {
                    $this->buildTagPages();
                    $this->buildTagIndex();
                }
                if($this->getArg('k')) $this->buildKinds();
                if($this->getArg('y')) $this->buildYears();
            } else {
                $this->buildTagPages();
                $this->buildTagIndex();
                $this->buildKinds();
                $this->buildYears();
            }
        }
        $this->buildHome();
        $this->buildChangelog();
        $this->logReport();
    }

    private function clearSite() {
        $this->logger->debug('Clearing Site Files');

        if(is_dir(File::siteDir() . "/gallery"))
            exec("rm " . File::siteDir() . "/gallery");
        File::delTree(File::siteDir());
        File::delTree(BASEDIR."/var/cache");
//        $caches = glob(BASEDIR."/gallery/cache/*", GLOB_ONLYDIR);
//        foreach($caches as $cache)
//            File::delTree($cache);

        if(!is_dir(File::siteDir())) {
            mkdir( File::siteDir(), 0775 );
            chmod( File::siteDir(), 0775 );
        }

        if(!is_dir(File::siteDir() . "/gallery"))
            exec("ln -s " . BASEDIR . "/gallery " . File::siteDir() . "/gallery");

        $this->assetsAndMaintenance();
    }

    private function assetsAndMaintenance() {
        $this->logger->debug('Copying Assets');
        File::recurseCopy(BASEDIR . "/assets", File::siteDir());
        File::delTree(File::siteDir()."/vsn");
        File::recurseCopy(BASEDIR . "/assets/vsn", File::siteDir() . '/' . Registry::get('version') . '/');
        copy(BASEDIR . "/assets/.htaccess.maint", File::siteDir().'/.htaccess');
    }

    private function buildBanners() {
        $vsn = Registry::get('version');
        $this->logger->debug('Building Banner Files');
        $banners = glob(BASEDIR . "/assets/vsn/images/banners/left/*.jpg");
        $banners = array_merge($banners, glob(BASEDIR . "/assets/vsn/images/banners/right/*.jpg"));
        sort($banners);
        $bannerCss = "";
        foreach($banners as $i => $banner){
            $parts = array_reverse(explode("/", $banner));
            $file = $parts[0];
            $align = $parts[1];
            $imageUrl = $this->siteUrl . '/' . $vsn . '/images/banners/' . $align . '/' . $file;
            $topUrl = $this->siteUrl . '/' . $vsn . '/images/banners/top/' . str_replace('jpg', 'png', $file);
            $bannerCss .= ".banner_$i, .banner_$i .banner-background { background-image: url({$imageUrl}); }\n";
            $bannerCss .= ".banner_$i .banner-feature { background-image: url({$topUrl}); }\n";
            if($align == "right"){
                $bannerCss .= ".banner_$i .logo { right:10px; left:auto; }\n";
            }
        }
        File::writeFile(File::siteDir().'/' . $vsn . "/css/banner.css", $bannerCss);

        $bannerJs = '
            $(function() {
                var date = new Date();
                var banner = Math.floor((Math.random() * '.count($banners).'));
                $(".header-banner.banner-border").addClass("banner_"+banner);
            });
        ';
        File::writeFile(File::siteDir(). '/' . $vsn . "/js/banner.js", $bannerJs);

        /** @var Block $header */
        $header = new Block('page/header');
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
        $testPage->loadLayout();
        $testPage->setData('content', $testHtml);
        $testPage = str_replace('col-md-9', 'col-md-12', $testPage);
        File::writeFile(File::siteDir() ."/banner-test.html", $testPage);
    }

    private function buildPages() {
        $this->logger->debug('Building Static Pages');
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
        $this->logger->debug('Build Entry Pages');

        $entryPage = new Layout('entry');
        $entryPage->loadLayout();
        /** @var \Paperroll\Model\Repository\Entry $entryRepo */
        $entryRepo = $this->entityManger->getRepository(Entry::class);
        $entries = $entryRepo->getPublishable($this->getArg('a'));
        /** @var Entry $entry */
        foreach($entries as $entry) {
            if($entry instanceof Entry == false)
                $entry = $entry[0];

            $visibleIds = [$entry->getId()];

            $entryPage->setTemplate('entry');

            $entryPage->setData('title', $entry->getTitle() . ' | ');

            if(count($entry->getMobileImages()))
                $entryBlock = new Block('entry/mobile/default');
            else
                $entryBlock = new Block('entry/default');

            $entryData = $entry->getBlockVariables();
                        
            $next = $entryRepo->getNext($entry);
            if($next) {
                $entryData['nextLink'] = '<a href="'.$next->getUrl().'" title="'.$next->getTitle().'"><span>Next Wallpaper &raquo;</span></a>';
                $entryData['next'] = '<a href="'.$next->getUrl().'" title="'.$next->getTitle().'"><span>Next</span><img class="lazy" data-original="'.$next->getMainImage()->getUrl(Image::MOBILE_THUMB).'" alt="'.$next->getTitle().'" /><span>'.$next->getTitle().'</span></a>';
                $visibleIds[] = $next->getId();
            }
            $prev = $entryRepo->getPrev($entry);
            if($prev) {
                $entryData['prevLink'] = '<a href="'.$prev->getUrl().'" title="'.$prev->getTitle().'"><span>&laquo; Prev Wallpaper</span></a>';
                $entryData['prev'] = '<a href="'.$prev->getUrl().'" title="'.$prev->getTitle().'"><span>Prev</span><img class="lazy" data-original="'.$prev->getMainImage()->getUrl(Image::MOBILE_THUMB).'" alt="'.$prev->getTitle().'" /><span>'.$prev->getTitle().'</span></a>';
                $visibleIds[] = $prev->getId();
            }

            $desktopImages = [];
            $mobileImages = [];
            /** @var Image $image */
            foreach ($entry->getVisibleImages() as $image) {
                $fileInfo = @getimagesize($image->getPath());
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
                        
            $entryBlock->setData($entryData);

            $entryPage->setData('content', $entryBlock->toHtml());
            unset($entryBlock);

            $entryHead = new Block('entry/head');
            $entryHead->setData($entryData);
            $entryPage->setData('head', $entryHead->toHtml());
            unset($entryHead);
            unset($entryData);

            $visibleIds = array_merge($visibleIds, $entryPage->getTopTen());
            $contentMore = '';
            if(count($entry->getTags())) {
                foreach ($entry->getTags() as $tag) {
                    $moreBlock = new Block('tag/more');
                    $more = $tag->getRandom($visibleIds, 3);
                    if(!count($more)) continue;
                    $moreEntries = '';
                    foreach ($more as $moreEntryId) {
                        $moreEntryBlock = new Block('entry/more', $moreEntryId);
                        if(!$moreEntryBlock->isCached()) {
                            $moreEntry = $entryRepo->find($moreEntryId);
                            if(count($moreEntry->getMobileImages())) $moreEntryBlock->setTemplate('entry/mobile/more');
                            $moreEntryBlock->setData($moreEntry->getBlockVariables());
                            $moreEntryBlock->setData('publishedAt', $moreEntry->getPublishedAt(1));
                        }
                        $moreEntries .= $moreEntryBlock->toHtml();
                        unset($moreEntryBlock);
                        $visibleIds[] = $moreEntryId;
                    }
                    $moreBlock->setData([
                        'url'          => $tag->getUrl(),
                        'title'        => $tag->getTitle(),
                        'more_entries' => $moreEntries
                    ]);
                    $contentMore .= $moreBlock->toHtml();
                    unset($moreBlock);
                }
            }
            if($contentMore == '') {
                $more = $entryRepo->getRandom($visibleIds, 6);
                $moreEntries = '';
                /** @var Entry $moreEntry */
                foreach($more as $moreEntry) {
                    $template = count($entry->getMobileImages()) ? 'entry/mobile/more' : 'entry/more';
                    $moreEntryBlock = new Block($template, $moreEntry->getId());
                    if(!$moreEntryBlock->isCached()) {
                        $moreEntryBlock->setData($moreEntry->getBlockVariables());
                        $moreEntryBlock->setData('publishedAt', $moreEntry->getPublishedAt('short'));
                    }
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
                        
            File::writePage($entry->getUrlPath(), $entryPage);
                        
            $entry->setPublished(true);
        }
        $this->entityManger->flush();
    }

    private function buildTagPages() {
        $this->logger->debug('Build Tag Pages');
        if($this->getArg('a')) {
            $tags = $this->entityManger->getRepository(Tag::class)->findAll();
        } else {
            $tags = $this->touchedTags;
        }

        $tagPage = new Layout('tag');
        $tagPage->loadLayout();
        /** @var Tag $tag */
        foreach($tags as $tag) {
            $tagPage->setTemplate('tag');
            $tagPage->setData('title', $tag->getTitle() . ' | ');
            $tagPage->setData('contentTitle', $tag->getTitle() . ' Wallpaper');

            $entryContent = '';
            /** @var Entry $entry */
            foreach($tag->getEntries() as $entry) {
                if($entry->getPublished() == false) continue;
                $template = count($entry->getMobileImages()) ? 'entry/mobile/tag' : 'entry/tag';
                $entryBlock = new Block($template, $entry->getId());
                if(!$entryBlock->isCached()) {
                    $entryBlock->setData($entry->getBlockVariables());
                }
                $entryContent .= $entryBlock->toHtml();
            }
            $tagPage->setData('content', $entryContent);

            File::writePage('tag/'.$tag->getSlug(), $tagPage);
        }
    }

    private function buildTagIndex() {
        $this->logger->debug('Build Tag Index');

        $tagPage = new Layout('default');
        $tagPage->loadLayout();
        $tagPage->setData('title', 'Names and Tags | ');

        $names = $this->entityManger->getRepository(Tag::class)->findBy(['name' => 1], ['title' => 'ASC']);
        $entryContent = '';
        /** @var Tag $tag */
        $tagSection = new Block('tag/index');
        $tagSection->setData('title', 'Wallpaper by Featured Subject');
        $tagSection->setData('class', 'tag-index');
        $tagsList = '';
        foreach($names as $tag) {
            $tagsList .= '<li><a href="' . $tag->getUrl() . '" title="Wallpaper featuring ' . $tag->getTitle() . '">' . $tag->getTitle() . '</a></li>';
        }
        $tagSection->setData("tags", $tagsList);
        $entryContent .= $tagSection->toHtml();

        $notnames = $this->entityManger->getRepository(Tag::class)->findBy(['name' => 0], ['title' => 'ASC']);
        /** @var Tag $tag */
        $tagSection = new Block('tag/index');
        $tagSection->setData('title', 'Other Tags');
        $tagSection->setData('class', 'tag-cloud');
        $tagsList = '';
        foreach($notnames as $tag) {
            $scale = 1 + ($tag->getCount() / 50.0);
            $scale = $scale < 5 ? $scale : 5;
            /** @noinspection CssInvalidPropertyValue */
            $tagsList .= '<li style="font-size: '.$scale.'em;"><a href="' . $tag->getUrl() . '" title="Wallpaper of ' . $tag->getTitle() . '">' . $tag->getTitle() . '</a></li>';
        }
        $tagSection->setData("tags", $tagsList);
        $entryContent .= $tagSection->toHtml();

        $tagPage->setData('content', $entryContent);
        File::writePage('page/tags', $tagPage);

    }

    private function buildKinds() {
        $this->logger->debug('Build Kind Pages');

        /** @var \Paperroll\Model\Repository\ImageKind $kindRepo */
        $kindRepo = $this->entityManger->getRepository(ImageKind::class);

        $kindPage = new Layout('tag');
        $kindPage->loadLayout();
        /** @var ImageKind $kind */
        foreach($kindRepo->findAll() as $kind) {
            $size = $kind->getPath() == 'ultrawide' ? Image::PREVIEW : Image::THUMB;
            $template = $kind->getPath() == 'ultrawide' ? 'entry/ultrawide' : 'entry/tag';

            $entryCount = 0;
            $entryContent = [];
            foreach($kindRepo->getEntries($kind) as $row) {
                /** @var Entry $entry */
                $entry = array_pop($row);
                if($entry->getPublished() == false) continue;
                if(
                    $this->dev == false
                    && $this->getArg('a') == false
                    && $entry->getYear() != date('Y')
                ) continue;
                if(!isset($entryContent[$entry->getYear()])) $entryContent[$entry->getYear()] = '';
                $entryBlock = new Block($template);
                $entryBlock->setData($entry->getBlockVariables());
                $image = File::baseUrl() . 'gallery/' . $kind->getPath() . '/' . $entry->getFilename();
                $entryBlock->setData('thumb', File::getCacheUrl($image, $size));
                $entryContent[$entry->getYear()] .= $entryBlock->toHtml();
                $entryCount++;
            }
            if($entryCount > 50) {
                $menu = [];
                foreach ($entryContent as $year => $content) {
                    $menu[] = '<a href="' . $this->siteUrl . 'tag/' . $kind->getPath() . '/' . $year . '" title="' . $year . '">' . $year . '</a>';
                }
                $menu = implode(',&nbsp;', $menu);
                $first = false;
                foreach ($entryContent as $year => $content) {
                    $kindPage->setTemplate('tag');
                    $kindPage->setData([
                        'title'        => $kind->getLabel() . ' Wallpaper, ' . $year . ' | ',
                        'contentTitle' => $kind->getLabel() . ' Wallpaper, ' . $year,
                        'menu'         => $menu,
                        'content'      => $content
                    ]);
                    if (!$first) {
                        File::writePage('tag/' . $kind->getPath(), $kindPage->toHtml());
                        $first = true;
                    }
                    File::writePage('tag/' . $kind->getPath() . "/$year", $kindPage->toHtml());
                }
            } else {
                $kindPage->setTemplate('tag');
                $kindPage->setData([
                    'title'        => $kind->getLabel() . ' Wallpaper | ',
                    'contentTitle' => $kind->getLabel() . ' Wallpaper',
                    'content'      => implode($entryContent),
                    'menu'         => ''
                ]);
                File::writePage('tag/' . $kind->getPath(), $kindPage->toHtml());
            }
        }
    }

    private function buildYears() {
        $this->logger->debug('Build Year Pages');

        /** @var \Paperroll\Model\Repository\Entry $entryRepo */
        $entryRepo = $this->entityManger->getRepository(Entry::class);

        $yearPage = new Layout('tag');
        $yearPage->loadLayout();
        $year = date('Y');
        $yearPage->setData('contentTitle', "Wallpapers posted in $year");
        $yearPage->setData('title', "Wallpapers posted in $year | ");
        $month = date('F');
        $entryContent = '<h3 class="subtitle">'.$month.'</h3>';
        /** @var Entry $entry */
        foreach($entryRepo->findBy(['published' => '1'], ['publishedAt' => 'DESC']) as $entry) {
            if($entry->getYear() != $year) {
                if($this->dev == false && $this->getArg('a') == false)
                    break;
                $yearPage->setData('content', $entryContent);
                File::writePage("tag/$year", $yearPage->toHtml());
                $year = $entry->getYear();
                $yearPage->setTemplate('tag');
                $yearPage->setData('contentTitle', "Wallpapers posted in $year");
                $yearPage->setData('title', "Wallpapers posted in $year | ");
                $entryContent = '';
            }
            if($month != $entry->getPublishedAt()->format('F')) {
                $month = $entry->getPublishedAt()->format('F');
                $entryContent .= '</div><h3 class="subtitle">'.$month.'</h3><div class="row entry-grid">';
            }
            $template = count($entry->getMobileImages()) ? 'entry/mobile/tag' : 'entry/tag';
            $entryBlock = new Block($template, $entry->getId());
            if(!$entryBlock->isCached()) {
                $entryBlock->setData($entry->getBlockVariables());
            }
            $entryContent .= $entryBlock->toHtml();
        }
        $yearPage->setData('content', $entryContent);
        File::writePage("tag/$year", $yearPage->toHtml());
    }

    private function buildChangelog() {
        $this->logger->debug('Build Changelog');
        /** @var \Paperroll\Model\Repository\Entry $entryRepo */
        $entryRepo = $this->entityManger->getRepository(Entry::class);
        $logPage = new Layout('tag');
        $logPage->loadLayout();
        $menu = [];
        $changeLog = $entryRepo->getChangeLog();
        foreach ($changeLog as $year => $content) {
            $menu[] = '<li><a href="' . $this->siteUrl . 'changelog/' . $year . '" title="' . $year . '">' . $year . '</a></li>';
        }
        $menu = implode($menu);
        foreach($changeLog as $year => $dates) {
            $logPage->setTemplate('tag');
            $logPage->setData([
                'contentTitle'  => "Changelog for $year",
                'title'         => "Changelog for $year | ",
                'menu'          => $menu
            ]);
            $logHtml = '';
            foreach($dates as $date => $items) {
                $dateBlock = new Block('log/view');
                $formatDate = new \DateTime($date);
                $dateBlock->setData('date', $formatDate->format('M j'));
                $itemsHtml = '';
                foreach($items as $item) {
                    $itemBlock = new Block('log/item');
                    $itemBlock->setData($item);
                    $itemsHtml .= $itemBlock->toHtml();
                }
                $dateBlock->setData('items', $itemsHtml);
                $logHtml .= $dateBlock->toHtml();
            }
            $logPage->setData('content', $logHtml);
            File::writePage("changelog/$year", $logPage->toHtml());
            if($this->dev == false && $this->getArg('a') == false)
                break;
        }
    }

    private function buildHome() {
        $this->logger->debug('Build Homepage');
        /** @var \Paperroll\Model\Repository\Entry $entryRepo */
        $entryRepo = $this->entityManger->getRepository(Entry::class);
        if($this->getArg('a') || $this->getArg('h')) {
            $entries = $entryRepo->getPublished(true);
            $year = '2000';
            $month = '3';
            $lastPage = '';
        } else {
            $entries = $entryRepo->getPublished();
            $year = date('Y');
            $month = date('n');
            $lastMonth = new \DateTime();
            $lastMonth->sub(new \DateInterval('P1M'));
            $lastPage = $this->siteUrl . $lastMonth->format('Y/n');
        }

        $homePage = new Layout('default');
        $homePage->loadLayout();
        $entriesHtml = [];
        $entryCount = 1;
        foreach($entries as $row) {
            /** @var Entry $entry */
            $entry = array_pop($row);
            if($entry->getPublishedAt()->format('n') != $month) {
                krsort($entriesHtml);
                $html = implode($entriesHtml);
                if($lastPage) {
                    $homePage->setData('pager', '<a href="' . $lastPage . '" title="Previous Month">Previous Month</a>');
                }
                $homePage->setData('content', $html);
                File::writePage("$year/$month", $homePage);
                $lastPage = $this->siteUrl . "$year/$month";
                $homePage->setTemplate('default');
                $homePage->setData('content_title', $entry->getPublishedAt()->format('F, Y'));
                $year = $entry->getPublishedAt()->format('Y');
                $month = $entry->getPublishedAt()->format('n');
                $entriesHtml = [];
            }
            $template = count($entry->getMobileImages()) ? 'entry/mobile/home' : 'entry/home';
            $entryBlock = new Block($template);
            $entryBlock->setData($entry->getBlockVariables());
            $tags = [];
            foreach($entry->getTags() as $tag) {
                $tags[($tag->getName() ? 0 : 1)][] = "<a href='{$tag->getUrl()}' title='{$tag->getTitle()}'>{$tag->getTitle()}</a>";
            }
            $tagsHtml = '<span>';
            if(!empty($tags[0]))
                $tagsHtml .= 'Featuring '.implode(', ', $tags[0]).'. ';
            if(!empty($tags[1]))
                $tagsHtml .= 'Tagged '.implode(', ', $tags[1]).'. ';
            $tagsHtml .= '</span>';
            $entryBlock->setData('tags', $tagsHtml);
            $entriesHtml[$entryCount++] = $entryBlock->toHtml();
        }
        if(is_array($entriesHtml)) {
            krsort($entriesHtml);
            $entriesHtml = implode($entriesHtml);
        }
        $homePage->setData('pager', '<a href="'.$lastPage.'" title="Previous Month">Previous Month</a>');
        $homePage->setData('content', $entriesHtml);
        $homePage->setData('content_title', '');
        File::writePage("$year/$month", $homePage);
        File::writePage("", $homePage);

    }

    private function logReport() {

        copy(BASEDIR . "/assets/.htaccess", File::siteDir().'/.htaccess');
        $repository = $this->entityManger->getRepository(Entry::class);
        $entries = $repository->findBy(['published' => null]);

        $this->logger->debug(count($entries) . ' left in queue');

    }

}