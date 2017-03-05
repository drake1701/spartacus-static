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
 * \Paperroll\Model\Entry
 *
 * @Table(name="entry", indexes={@Index(name="queue_fk", columns={"queue"}), @Index(name="entry_url_path_UNIQUE", columns={"url_path"})})
 * @Entity(repositoryClass="Paperroll\Model\Repository\Entry")
 */
class Entry
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
     * @Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var string
     * @Column(name="filename", type="string", length=255, nullable=false)
     */
    private $filename;

    /**
     * @var string
     * @Column(name="url_path", type="string", length=255, nullable=true)
     */
    private $urlPath;

    /**
     * @var \DateTime
     * @Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @Column(name="modified_at", type="datetime", nullable=true)
     */
    private $modifiedAt;

    /**
     * @Column(name="published_at", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $publishedAt;

    /**
     * @var integer
     * @Column(name="queue", type="integer", nullable=true)
     */
    private $queue;

    /**
     * @Column(name="published", type="boolean", nullable=true)
     * @var boolean
     */
    private $published;

    /**
     * @var string
     * @Column(name="thumb", type="text", nullable=true)
     */
    private $thumb;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="Paperroll\Model\Image", mappedBy="entry")
     * @SortBy({"position" = "ASC"})
     */
    private $images;

    /**
     * @var array
     */
    private $desktopImages;

    /**
     * @var array
     */
    private $mobileImages;

    /** @var  Image */
    private $mainImage;

    /**
     * Entry constructor.
     */
    public function __construct()
    {
        $this->images = new ArrayCollection();
    }


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
     * @return Entry
     */
    public function setTitle($title) {
        $this->title = $title;        return $this;
    }

    /**
     * Get title
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set content
     * @param string $content
     * @return Entry
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * Get content
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Set filename
     * @param string $filename
     * @return Entry
     */
    public function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Get filename
     * @return string
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * Set urlPath
     * @param string $urlPath
     * @return Entry
     */
    public function setUrlPath($urlPath) {
        $this->urlPath = $urlPath;
        return $this;
    }

    /**
     * Get urlPath
     * @return string
     */
    public function getUrlPath() {
        return $this->urlPath;
    }

    /**
     * Set createdAt
     * @param \DateTime $createdAt
     * @return Entry
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get createdAt
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set modifiedAt
     * @param \DateTime $modifiedAt
     * @return Entry
     */
    public function setModifiedAt($modifiedAt) {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    /**
     * Get modifiedAt
     * @return \DateTime
     */
    public function getModifiedAt() {
        return $this->modifiedAt;
    }

    /**
     * Set publishedAt
     * @param \DateTime $publishedAt
     * @return Entry
     */
    public function setPublishedAt($publishedAt) {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    /**
     * Get publishedAt
     * @return \DateTime
     */
    public function getPublishedAt($short = false) {
        $format = $short ? "M j, Y" : "l, F jS, Y";
        return $this->publishedAt->format($format);
    }

    /**
     * Set queue
     * @param integer $queue
     * @return Entry
     */
    public function setQueue($queue) {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Get queue
     * @return integer
     */
    public function getQueue() {
        return $this->queue;
    }

    /**
     * Set published
     * @param boolean $published
     * @return Entry
     */
    public function setPublished($published) {
        $this->published = $published;
        return $this;
    }

    /**
     * Get published
     * @return boolean
     */
    public function getPublished() {
        return $this->published;
    }

    /**
     * Set thumb
     * @param string $thumb
     * @return Entry
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
     * @return ArrayCollection
     */
    public function getImages() {
        return $this->images;
    }

    /**
     * @return Image
     */
    public function getMainImage() {
        if(!$this->mainImage) {
            $min = 99;
            foreach($this->getImages() as $image) {
                if($image->getKind()->getPosition() < $min) {
                    $min = $image->getKind()->getPosition();
                    $this->mainImage = $image;
                }
            }
        }
        return $this->mainImage;
    }

    /**
     * @return array
     */
    public function getDesktopImages() {
        if(!$this->desktopImages) {
            $images = [];
            foreach($this->getImages() as $image) {
                if($image->getKind()->getMobile() == 0) {
                    $images[$image->getKind()->getPosition()] = $image;
                }
            }
            ksort($images);
            $this->desktopImages = $images;
        }
        return $this->desktopImages;
    }

    /**
     * @return array
     */
    public function getMobileImages() {
        if(!$this->mobileImages) {
            $images = [];
            foreach($this->getImages() as $image) {
                if($image->getKind()->getMobile()) {
                    $images[$image->getKind()->getPosition()] = $image;
                }
            }
            ksort($images);
            $this->mobileImages = $images;
        }
        return $this->mobileImages;
    }

    public function getUrl() {
        return File::baseUrl() . $this->getUrlPath();
    }


}
