<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model;

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
        return File::baseUrl() . $this->getSlug();
    }
}
