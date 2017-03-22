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

class Images extends Generic {

    public function execute() {
        /** @var \Paperroll\Model\Repository\Entry $entryRepo */
        $entryRepo = $this->entityManger->getRepository(Entry::class);
        foreach($entryRepo->findAll() as $entry) {
            $entryRepo->updateImages($entry);
        }
    }

}