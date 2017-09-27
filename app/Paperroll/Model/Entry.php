<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Paperroll\Helper\Entity;
use Paperroll\Helper\File;

/**
 * Entry
 *
 * @Table(name="entry")
 * @Entity(repositoryClass="Paperroll\Model\Repository\Entry")
 * @HasLifecycleCallbacks()
 */
class Entry extends Generic
{
    /**
     * @var integer
     * @Column(name="id", type="integer", nullable=true)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     * @Column(name="content", type="text", nullable=true)
     */
    protected $note;

    /**
     * @var string
     * @Column(name="filename", type="string", length=255, nullable=false)
     */
    protected $filename;

    /**
     * @var string
     * @Column(name="url_path", type="string", length=255, nullable=true)
     */
    protected $urlPath;

    /**
     * @var \DateTime
     * @Column(name="created_at", type="datetime", nullable=true)
     * @HasLifecycleCallbacks()
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @Column(name="modified_at", type="datetime", nullable=true)
     * @HasLifecycleCallbacks()
     */
    protected $modifiedAt;

    /**
     * @Column(name="published_at", type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $publishedAt;

    /**
     * @var integer
     * @Column(name="queue", type="integer", nullable=true)
     */
    protected $queue;

    /**
     * @Column(name="published", type="boolean", nullable=true)
     * @var boolean
     */
    protected $published;

    /**
     * @var string
     * @Column(name="thumb", type="text", nullable=true)
     */
    protected $thumb;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="Paperroll\Model\Image", mappedBy="entry", fetch="EAGER")
     * @JoinColumn(name="entry_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $images;

    /**
     * @var ArrayCollection
     * @ManyToMany(targetEntity="Paperroll\Model\Tag", inversedBy="tag")
     * @JoinTable(name="entry_tag",
     *      joinColumns={@JoinColumn(name="entry_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="tag_id", referencedColumnName="id")}
     *      )
     */
    protected $tags;

    /**
     * @var array
     */
    protected $desktopImages;

    /**
     * @var array
     */
    protected $mobileImages;

    /** @var  Image */
    protected $mainImage;
    protected $visibleImages;
    protected $next;
    protected $prev;

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
     * @param string $note
     * @return Entry
     */
    public function setNote($note) {
        $this->note = $note;
        return $this;
    }

    /**
     * Get content
     * @return string
     */
    public function getNote() {
        return $this->note;
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
     * @PrePersist
     */
    public function setCreatedAt() {
        $this->createdAt = new \DateTime();
    }

    /**
     * Get createdAt
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * @PreUpdate
     * @PrePersist
     */
    public function setModifiedAt() {
        $this->modifiedAt = new \DateTime();
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
     * @param string $format
     * @return string|\DateTime
     */
    public function getPublishedAt($format = '') {
        if($format)
            return $this->publishedAt->format($format == 'short' ? "j M y" : "D, j M y");
        else
            return $this->publishedAt;
    }

    public function getYear() {
        return $this->publishedAt->format('Y');
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

    /**
     * @return array
     */
    public function getVisibleImages() {
        if(!$this->visibleImages) {
            $images = [];
            foreach($this->getImages() as $image) {
                if($image->getKind()->getExclude() == 0) {
                    $images[$image->getKind()->getPosition()] = $image;
                }
            }
            ksort($images);
            $this->visibleImages = $images;
        }
        return $this->visibleImages;
    }

    public function getUrl() {
        return File::baseUrl() . $this->getUrlPath();
    }

    /**
     * @return array
     */
    public function getBlockVariables() {
        $blockData = $this->getData();
        $i = 1;
        foreach($this->getMobileImages() as $image) {
            $blockData["mobile_$i"] = $image->getUrl();
            $blockData["mobile_{$i}_thumb"] = $image->getUrl(Image::MOBILE_THUMB);
            $i++;
        }
        $blockData = array_merge($blockData, [
            'mainImage'         => $this->getMainImage()->getUrl(),
            'preview'           => $this->getMainImage()->getUrl(Image::PREVIEW),
            'thumb'             => $this->getMainImage()->getUrl(Image::THUMB),
            'url'               => $this->getUrl(),
            'publishedAt'       => $this->getPublishedAt('long'),
            'publishedAt_short' => $this->getPublishedAt('short')
        ]);
        $blockData['description'] = $blockData['title'];
        if(!empty($blockData['note'])) {
            $blockData['note'] = '<div class="std">' . $blockData['note'] . '</div>';
            $blockData['short_content'] = substr(strip_tags($blockData['note']), 0, 45);
            $blockData['description'] = $blockData['note'];
        }
        return $blockData;
    }

    /**
     * @return ArrayCollection
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * @param ArrayCollection $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }

}
