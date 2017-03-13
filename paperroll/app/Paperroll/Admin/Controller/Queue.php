<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Admin\Controller;

use Doctrine\ORM\EntityManager;
use Paperroll\Admin\Router;
use Paperroll\Helper\Registry;
use Paperroll\Helper\File;
use Paperroll\Model\Entry;
use Paperroll\Model\Image;

class Queue extends Router
{
    /** @var  \Paperroll\Model\Repository\Entry */
    private $_entryRepo;
    /** @var EntityManager */
    private $_entityManager;

    /**
     * Queue constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->_entityManager = Registry::get('entityManager');
        $this->_entryRepo = $this->_entityManager->getRepository(Entry::class);
    }

    public function index() {

        $last = $this->_entryRepo->getLastEntry()->getPublishedAt();

        $now = new \DateTime();
        $marker = new \DateTime();

        /** @var \DateTime $marker */

        $marker = $marker->sub(new \DateInterval('P' . $now->format('w') . 'D'));
        $increment = new \DateInterval('P1D');
        $month = $marker->format('m');
        $html = <<<'QUEUE'

        <form id="calendar" action="\reorder" method="post">
            <div class="calendar">
                <div>Su</div>
                <div>M</div>
                <div>Tu</div>
                <div>W</div>
                <div>Th</div>
                <div>F</div>
                <div>Sa</div>
QUEUE;
                while ($marker < $last) {
                    for ($day = 0; $day < 7; $day++) {
                        /** @var \Paperroll\Model\Entry $entry */
                        $entry = $this->_entryRepo->getPublishedAt($marker);
                        $class = $entry ? 'item' : '';
                        $class .= $entry && $entry->getPublished() ? ' live' : '';
                        $html .= '<div' . ($class ? (' class="' . $class . '"') : '') . '>';
                        if ($day == 0) {
                            $html .= $marker->format('F d') . '<br/>';
                        }
                        if ($entry) {
                            if ($entry->getPublishedAt() > $now) {
                                $html .= '<input type = "hidden" name = "entry_id[' . $entry->getQueue() . '][]" value = "' . $entry->getId() . '" />';
                            }
                            $html .= '
                                <img src="' . $entry->getMainImage()->getUrl(340) . '"/>
                                <a href="/edit?id=' . $entry->getId() . '">
                                    <span class="entry-title">' . $entry->getTitle() . '</span>
                                </a>
                                <div class="clearfix"></div>
                                ' . ($entry->getQueue() == 1 ? 'Normal' : 'Calendar') . '<br/>';
                            foreach ($entry->getTags() as $tag) {
                                $html .= '<a href="/showall&tag=' . $tag->getSlug() . '">' . $tag->getTitle() . '</a><br/>';
                            }
                            $html .= '<div class="clearfix"></div>';
                            if ($entry->getPublished()) {
                                $html .= '<a href="' . $entry->getUrl() . '">View</a>&nbsp;|&nbsp;';
                            }
                            $html .= '
                                <a href="\edit?id=' . $entry->getId() . '">Edit</a>&nbsp;|&nbsp;
                                <a href="\delete?id=' . $entry->getId() . '">Delete</a>';
                        } else {
                            $html .= '&nbsp;';
                        }
                        /*while($entry = $entries->fetchArray()): ?>
                        <?php if($entry->getPublishedAt() > $now): ?>
                            <input type="hidden" name="entry_id[' . $entry['queue'] . '][]" value="' . $entry->getId() . '" />
                        <?php endif; ?>
                    <?php endwhile;*/
                        $html .= '</div>';
                        $marker->add($increment);
                    }
                }
        $html .= '</div>
            <button type="submit" class="btn-lg"><span>Save</span></button>
        </form>';

        $html .= $this->_getReposts();
        $html .= $this->_getUnqueued();

        $this->getPage()->setData('content', $html);
        echo $this->getPage();
    }

    protected function _getUnqueued() {
        $html = '
        <h1>New Images</h1>
        <p>Normal Queue:   ' . $this->_entryRepo->getLastEntry(1)->getPublishedAt('short') . '</p>
        <p>Calendar Queue: ' . $this->_entryRepo->getLastEntry(1)->getPublishedAt('short') . '</p>
        ';

        chdir(BASEDIR.'/gallery/widescreen/');
        $files = glob('*.jpg');

        $images = [];
        $imageRows = $this->_entityManager->getRepository(Image::class)->findBy(['kindId' => 8]);
        /** @var Image $image */
        foreach($imageRows as $image) {
            $images[] = $image->getFilename();
        }
        $images = array_diff($files, $images);

        $html .= '<div class="row">';
        foreach ($images as $image) {
            $url = '/newprocess?' . http_build_query(['image' => $image]);
            $html .= '<div class="col-xs-6 col-sm-4 col-md-3">
            <p class="entry-title"><a href="' . $url . '">' . $image . '</a></p>
            <div class="entry-image"><a href="' . $url . '"><img src="' . File::getCacheUrl('widescreen / ' . $image, Image::MOBILE_THUMB) . '" alt="' . File::codeToName(str_replace(".jpg", '', strtolower($image))) . '"/></a></div>
            </div>';
        }
        return $html;
    }

    protected function _getReposts() {

    }
}