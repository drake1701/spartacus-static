<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Command;

use Paperroll\Model\Entry;
use Paperroll\Helper\File;
use Paperroll\Theme\Block;
use Paperroll\Theme\Layout\Blank;

class Generate extends Generic {

    /** @var \Doctrine\ORM\EntityRepository  */
    protected $entryRepo;
    /** @var \Paperroll\Helper\Entry  */
    protected $entryHelper;
    /** @var string  */
    protected $siteUrl;

    public function __construct( array $argv = [] ) {
        parent::__construct( $argv );
        $this->entryRepo = $this->entityManger->getRepository(Entry::class);
        $this->entryHelper = new \Paperroll\Helper\Entry();
        $this->siteUrl = $this->dev ? 'http://dev.spartacuswallpaper.com' : 'http://www.spartacuswallpaper.com';
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
                $entries = $this->entryRepo->findBy(['id' => [3754,4102]]);
                /** @var Entry $entry */
                foreach($entries as $entry)
                    $this->entryHelper->rePublish($entry);

                $entry = $this->entryHelper->getLastPublishedEntry();
                $this->entryHelper->rePublish($entry);
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
        $header = new Block('header.phtml');
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
        $testPage = new Blank();
        $testPage->setData('content', $testHtml);
        $testPage = str_replace('col-md-9', 'col-md-12', $testPage);
        File::writeFile(File::siteDir() ."/banner-test.html", $testPage);
    }

    private function buildPages() {
        $this->debug('Building Static Pages');
    }

    private function buildEntries() {
        $this->debug('Build Entry Pages');
    }

    private function buildTagPages() {
        $this->debug('Build Tag Pages');
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