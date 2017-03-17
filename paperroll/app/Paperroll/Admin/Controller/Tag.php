<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Admin\Controller;


use Paperroll\Model;
use Paperroll\Theme\Block;

class Tag extends Queue
{

    public function entriesAction() {
        $entryHtml = '';
        $requeueBlock = new Block('admin/tag/view');
        $requeueBlock->setData('back', $_SERVER['HTTP_REFERER']);
        if(!empty($_REQUEST['tag'])) {
            $tag = $this->_entityManager->getRepository(Model\Tag::class)->find($_REQUEST['tag']);
        }
        $title = $tag ? 'Entries tagged "'.$tag->getTitle().'"' : 'All Entries';
        $entries = $tag ? $tag->getEntries() : $this->_entryRepo->findBy([], ['publishedAt' => 'DESC']);

        $requeueBlock->setData('contentTitle', $title);
        /** @var Model\Entry $entry */
        foreach($entries as $entry) {
            if($entry->getPublished())
                $entryBlock = new Block('admin/tag/itemlive');
            else
                $entryBlock = new Block('admin/tag/item');
            $entryBlock->setData($entry->getBlockVariables());
            $entryBlock->setData('queueLabel', $entry->getQueue() == \Paperroll\Model\Queue::CALENDAR ? 'Calendar' : 'Normal');
            $tags = '';
            foreach($entry->getTags() as $tag) {
                $tags .= '<a href="/tag/entries?tag='.$tag->getId().'">'.$tag->getTitle().'</a><br/>';
            }
            $entryBlock->setData('tags', $tags);
            $entryHtml .= $entryBlock->toHtml();
        }

        $requeueBlock->setData('items', $entryHtml);
        $this->getPage()->setData('content', $requeueBlock->toHtml());
        echo $this->getPage();
    }

    public function saveAction() {
        if(empty($_POST)) $this->_goBack();

        try {

            if (!empty($_POST['entry']) && !empty($_POST['tags'])) {
                /** @var Model\Repository\Tag $tagRepo */
                $tagRepo = $this->_entityManager->getRepository(Model\Tag::class);
                $newTags = $tagRepo->buildArray($_POST['tags']);

                $i = 0;
                foreach ($_POST['entry'] as $entryId => $flag) {
                    if ($flag != 'on') continue;
                    /** @var Model\Entry $entry */
                    $entry = $this->_entryRepo->find($entryId);
                    $tagCollection = $entry->getTags();
                    foreach($newTags as $newTag)
                        $tagCollection->add($newTag);
                    $entry->setTags($tagCollection);
                    $entry->setPublished(null);
                    $i++;
                }
                $this->_entityManager->flush();
                $_SESSION['messages'][] = "Added tags to $i entries.";
            }
            $this->_goBack();
        } Catch (\Exception $e) {
            $_SESSION['messages'][] = $e->getMessage();
        }
    }

    public function listingAction() {

        $listBlock = new Block('admin/tag/list');
        $itemsHtml = '';
        /** @var Model\Tag $tag */
        foreach($this->_entityManager->getRepository(Model\Tag::class)->findBy([], ['id'=>'desc']) as $tag) {
            $tagBlock = new Block('admin/tag/listitem');
            $tagBlock->setData($tag->getData());
            $tagBlock->setData('class', $tag->getName() ? 'name' : 'not-name');
            $itemsHtml .= $tagBlock->toHtml();
        }
        $listBlock->setData('items', $itemsHtml);
        $this->getPage()->setData('content', $listBlock->toHtml());
        echo $this->getPage();

    }

    public function deleteAction() {
        if(empty($_REQUEST['id'])) $this->_goBack();
        try {
            $tag = $this->_entityManager->getRepository(Model\Tag::class)->find($_REQUEST['id']);
            if ($tag) {
                $tag->delete();
                $this->_entityManager->flush();
                $_SESSION['messages'][] = 'Tag ID ' . $_REQUEST['id'] . ' deleted.';
            }
        } catch (\Exception $e) {
            $_SESSION['messages'][] = $e->getMessage();
        }
        $this->_goBack();
    }

    public function switchAction() {
        if(empty($_REQUEST['id'])) $this->_goBack();
        try {
            $tag = $this->_entityManager->getRepository(Model\Tag::class)->find($_REQUEST['id']);
            if ($tag) {
                $tag->setName($tag->getName() ? 0 : 1);
                $this->_entityManager->flush();
                $_SESSION['messages'][] = 'Tag ID ' . $_REQUEST['id'] . ' switched.';
            }
        } catch (\Exception $e) {
            $_SESSION['messages'][] = $e->getMessage();
        }
        $this->_goBack();
    }
}