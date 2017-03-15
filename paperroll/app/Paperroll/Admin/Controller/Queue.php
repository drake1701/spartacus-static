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
use Paperroll\Theme\Block;

class Queue extends Router
{
    /** @var  \Paperroll\Model\Repository\Entry */
    protected $_entryRepo;
    /** @var EntityManager */
    protected $_entityManager;
    protected $_logger;

    /**
     * Queue constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->_entityManager = Registry::get('entityManager');
        $this->_logger = Registry::get('logger');
        $this->_entryRepo = $this->_entityManager->getRepository(Entry::class);
    }

    public function reorder() {
        $data = $_POST;
        foreach($data['entry_id'] as $queueId => $entryIds) {
            $queue = new \Paperroll\Model\Queue($queueId);
            foreach($entryIds as $entryId) {
                $entry = $this->_entryRepo->find($entryId);
                $next = $queue->getNext();
                $this->_logger->debug("Set $entryId to publish on ".$next->format('Y-m-d'));
                $entry->setPublishedAt($next);
                $entry->setPublished(null);
                $this->_entityManager->flush();
            }
        }
        $_SESSION['messages'][] = 'Reordered entries.';
        $this->_goBack();
    }

    public function index() {

        $last = $this->_entryRepo->getLastEntry()->getPublishedAt();

        $now = new \DateTime();
        $marker = new \DateTime();

        /** @var \DateTime $marker */

        $marker = $marker->sub(new \DateInterval('P' . $now->format('w') . 'D'));
        $increment = new \DateInterval('P1D');

        $qTable = new Block('admin/queue/view');
        $qRows = [];
        $qRow = new Block('admin/queue/row');
        while($marker->format('Y-m-d') <= $last->format('Y-m-d')) {
            /** @var Entry $entry */
            $entry = $this->_entryRepo->getPublishedAt($marker);
            if(is_array($entry)) {
                $others = $entry;
                $entry = array_pop($others);
            }
            $html = '<td>'.($marker->format('w') == 0 ? $marker->format('F d') : '&nbsp;').'</td>';
            if($entry) {
                if($entry->getPublished())
                    $qItem = new Block('admin/queue/itemlive');
                else
                    $qItem = new Block('admin/queue/item');
                $qItem->setData($entry->getBlockVariables());
                $tags = '';
                foreach($entry->getTags() as $tag) {
                    $tags .= '<a href="showall?tag='.$tag->getId().'">'.$tag->getTitle().'</a><br/>';
                }
                $qItem->setData('tags', $tags);
                $html = $qItem->toHtml();
                if(isset($others)) {
                    foreach($others as $otherEntry) {
                        $html .= '<input type="hidden" name="entry_id['.$otherEntry->getQueue().'][]" value="'.$otherEntry->getId().'">';
                    }
                }
            }
            $qRow->setData('cell_'.$marker->format('w'), $html);
            if($marker->format('w') == 6) {
                $qRows[] = $qRow->toHtml();
                $qRow = new Block('admin/queue/row');
            }
            $marker->add($increment);
        }
        $qRows[] = $qRow->toHtml();
        $qTable->setData('rows', implode($qRows));

        $menu = new Block('admin/header');
        $html = $menu->toHtml();
        $html .= $qTable->toHtml();
        $html .= $this->_getReposts();
        $html .= $this->_getUnqueued();

        $this->getPage()->setData('content', $html);
        echo $this->getPage()->toHtml();
    }

    protected function _getUnqueued() {

        $newBlock = new Block('admin/new');
        $newBlock->setData('next_1', $this->_entryRepo->getLastEntry(1)->getPublishedAt('short'));
        $newBlock->setData('next_2', $this->_entryRepo->getLastEntry(2)->getPublishedAt('short'));

        $images = [];
        $imageRows = $this->_entityManager->getRepository(Image::class)->findBy(['kindId' => 8]);
        /** @var Image $image */
        foreach($imageRows as $image) {
            $images[] = $image->getFilename();
        }

        chdir(BASEDIR.'/gallery/widescreen/');
        $files = glob('*.jpg');
        $images = array_diff($files, $images);

        $newHtml = '';
        foreach ($images as $image) {
            $entryBlock = new Block('admin/item');
            $entryBlock->setData([
                'url'   => '/edit/newprocess?' . http_build_query(['image' => $image]),
                'thumb' => File::getCacheUrl('widescreen/'.$image, Image::THUMB),
                'title' => File::codeToName(str_replace(".jpg", '', strtolower($image)))
            ]);
            $newHtml .= $entryBlock->toHtml();
        }
        $newBlock->setData('items', $newHtml);
        return $newBlock->toHtml();
    }

    protected function _getReposts() {
        $qb = $this->_entryRepo->createQueryBuilder('e');
        $qb
            ->where($qb->expr()->lt('date(e.publishedAt)', "date('".date('Y-m-d')."')"))
            ->andWhere($qb->expr()->isNull('e.published'))
            ->orderBy('e.publishedAt', 'asc');

        $entryHtml = '';
        $requeueBlock = new Block('admin/requeue');
        foreach($qb->getQuery()->iterate() as $row) {
            /** @var Entry $entry */
            $entry = array_pop($row);
            $entryBlock = new Block('admin/item');
            $entryBlock->setData($entry->getBlockVariables());
            $entryBlock->setData('url', '/edit?id='.$entry->getId());
            $entryHtml .= $entryBlock->toHtml();
        }
        if(strlen($entryHtml)) {
            $requeueBlock->setData('items', $entryHtml);
            return $requeueBlock->toHtml();
        } else {
            return '';
        }
    }
}