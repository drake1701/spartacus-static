<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Admin\Controller;


use Paperroll\Helper\File;
use Paperroll\Model\EntryLog;
use Paperroll\Model\Image;
use Paperroll\Model\Queue;
use Paperroll\Model\Tag;
use Paperroll\Theme\Block;

class Entry extends \Paperroll\Admin\Controller\Queue
{
    public function getPage() {
        parent::getPage();
        $this->page->setData('menu', '');
        return $this->page;
    }

    public function newprocessAction() {
        if(!isset($_REQUEST['image'])) $this->_goBack();
        $image = $_REQUEST['image'];

        $form = new Block('admin/edit');
        $form->setData([
            'title' => File::fileToTitle($image),
            'filename' => $image,
            'preview' => File::getCacheUrl('widescreen/'.$image, Image::PREVIEW),
            'tags' => File::fileToTag($image),
            'urlPath' => str_replace(".jpg", ".html", $image)
        ]);
        $this->getPage()->setData('content', $form->toHtml());
        echo $this->getPage()->toHtml();
    }

    public function editAction() {
        if(!isset($_REQUEST['id'])) $this->_goBack();

        /** @var \Paperroll\Model\Entry $entry */
        $entry = $this->_entryRepo->find($_REQUEST['id']);

        $form = new Block('admin/edit');
        $form->setData($entry->getData());
        $form->setData([
            'preview'                   => File::getCacheUrl($entry->getMainImage()->getUrl(), Image::PREVIEW),
            'queue_'.$entry->getQueue() => ' selected="selected"',
            'back'                      => $_SERVER['HTTP_REFERER']
        ]);
        $tags = [];
        foreach($entry->getTags() as $tag) {
            $tags[] = $tag->getSlug();
        }
        $form->setData('tags', implode(', ', $tags));
        $this->getPage()->setData('content', $form->toHtml());
        echo $this->getPage()->toHtml();
    }

    public function saveAction() {
        if(empty($_POST)) $this->_goBack();
        echo "<pre>";

        $data = $_POST;

        try {
            $entry = $this->_entryRepo->find($data['id']);
            if(!$entry) {
                $entry = new \Paperroll\Model\Entry();
                $queue = new Queue($data['queue']);
                $entry->setPublishedAt($queue->getNext());
            }
            $entry->setData($data);
            $tags = $this->_entityManager->getRepository(Tag::class)->buildArray($data['tags']);
            $entry->setTags($tags);
            if($entry->getPublished()) {
                $log = new EntryLog();
                $log->setEntry($entry);
                $log->setMessage("Edited");
                $this->_entityManager->persist($log);
            }
            $entry->setPublished(null);

            $this->_entityManager->persist($entry);
            $this->_entityManager->flush();

            $this->_entryRepo->updateImages($entry);
            $this->_entityManager->getRepository(Tag::class)->updateCounts($tags);

            $_SESSION['messages'][] = 'Saved '.$entry->getTitle().' - '.$entry->getId().'.';
            $this->_goBack(true);

        } Catch (\Exception $e) {
            $_SESSION['messages'][] = $e->getMessage();
            $this->_goBack();
        }
    }

    public function imagesAction() {

        foreach($this->_entryRepo->findAll() as $entry) {
            $this->_entryRepo->updateImages($entry);
        }
        $this->_goBack();
    }

}