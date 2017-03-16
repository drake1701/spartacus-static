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

    public function entries() {
        $entryHtml = '';
        $requeueBlock = new Block('admin/tag/view');
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

    public function save() {
        if(empty($_POST)) $this->_goBack();

        try {

            if (!empty($_POST['entry']) && !empty($_POST['tags'])) {
                print_r($_POST);
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
                }
                $this->_entityManager->flush();
                $_SESSION['messages'][] = "Added tags to $i entries.";
            }
            $this->_goBack();
        } Catch (\Exception $e) {
            $_SESSION['messages'][] = $e->getMessage();
        }
    }
}