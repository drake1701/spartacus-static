<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Paperroll\Helper\File;

/**
 * Tag
 *
 * @Table(name="tag")
 * @Entity(repositoryClass="Paperroll\Model\Repository\Tag")
 */
class Tag
{
    /**
     * @var integer
     * @Column(name="id", type="integer", nullable=true)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     * @Column(name="slug", type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     * @var boolean
     * @Column(name="list", type="boolean", nullable=true)
     */
    private $list;

    /**
     * @var integer
     * @Column(name="count", type="integer", nullable=true)
     */
    private $count;

    /**
     * @var string
     * @Column(name="thumb", type="string", length=255, nullable=true)
     */
    private $thumb;

    /**
     * @var boolean
     * @Column(name="name", type="boolean", nullable=true)
     */
    private $name;

    /**
     * @var ArrayCollection
     * @ManyToMany(targetEntity="Paperroll\Model\Entry", mappedBy="tags")
     * @OrderBy({"publishedAt"="DESC"})
     */
    private $entries;

    private $entryIds;

    /**
     * Get id
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set title
     * @param string $title
     * @return Tag
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set slug
     * @param string $slug
     * @return Tag
     */
    public function setSlug($slug) {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get slug
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * Set list
     * @param boolean $list
     * @return Tag
     */
    public function setList($list) {
        $this->list = $list;
        return $this;
    }

    /**
     * Get list
     * @return boolean
     */
    public function getList() {
        return $this->list;
    }

    /**
     * Set count
     * @param integer $count
     * @return Tag
     */
    public function setCount($count) {
        $this->count = $count;
        return $this;
    }

    /**
     * Get count
     * @return integer
     */
    public function getCount() {
        return $this->count;
    }

    /**
     * Set thumb
     * @param string $thumb
     * @return Tag
     */
    public function setThumb($thumb) {
        $this->thumb = $thumb;
        return $this;
    }

    /**
     * Get thumb
     * @return string
     */
    public function getThumb() {
        return $this->thumb;
    }

    /**
     * Set name
     * @param boolean $name
     * @return Tag
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     * @return boolean
     */
    public function getName() {
        return $this->name;
    }

    public function getUrl() {
        return File::baseUrl() . 'tag/' . $this->getSlug();
    }


    /**
     * @param array $excludeIds
     * @param int $count
     * @return array
     */
    public function getRandom($excludeIds = [], $count = 1) {
        $entryIds = $this->getEntryIds();
        $entryIds = array_slice($entryIds, 0, ceil(count($entryIds) * .8));
        $entryIds = array_diff($entryIds, $excludeIds);
        shuffle($entryIds);

        return $count > 1 ? array_slice($entryIds, 0, $count) : array_pop($entryIds);
    }

    /**
     * @return ArrayCollection
     */
    public function getEntries() {
        return $this->entries;
    }

    /**
     * @return mixed
     */
    public function getEntryIds()
    {
        if(empty($this->entryIds)) {
            $entryIds = [];
            foreach($this->getEntries() as $entry) {
                $entryIds[] = $entry->getId();
            }
            $this->entryIds = $entryIds;
        }
        return $this->entryIds;
    }
}
