<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Admin\Controller;


use Paperroll\Helper\File;
use Paperroll\Model\Entry;
use Paperroll\Model\Image;
use Paperroll\Theme\Block;

class Edit extends Queue
{
    public function getPage() {
        parent::getPage();
        $this->page->setData('menu', '');
        return $this->page;
    }


    public function index() {
        if(!isset($_REQUEST['id'])) $this->_goBack();

        /** @var Entry $entry */
        $entry = $this->_entryRepo->find($_REQUEST['id']);

        $form = new Block('admin/edit');
        $form->setData($entry->getData());
        $form->setData([
            'preview'                   => File::getCacheUrl($entry->getMainImage()->getUrl(), Image::PREVIEW),
            'queue_'.$entry->getQueue() => ' selected="selected"'
        ]);
        $tags = [];
        foreach($entry->getTags() as $tag) {
            $tags[] = $tag->getSlug();
        }
        $form->setData('tags', implode(', ', $tags));
        $this->getPage()->setData('content', $form->toHtml());
        echo $this->getPage()->toHtml();
    }

}