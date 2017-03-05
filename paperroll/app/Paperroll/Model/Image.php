<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model;
use Paperroll\Helper\File;

/**
 * Image
 *
 * @Table(name="image", indexes={@Index(name="image_post_fk", columns={"entry_id"}), @Index(name="image_kind_fk", columns={"kind"}), @Index(name="image_entry_id", columns={"entry_id", "kind"}), @Index(name="image_entry", columns={"entry_id"})})
 * @Entity(repositoryClass="Paperroll\Model\Repository\Image")
 */
class Image
{
    /**
     * @var integer
     * @Column(name="id", type="integer", nullable=true)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     * @Column(name="entry_id", type="integer", nullable=false)
     */
    private $entryId;

    /**
     * @var Entry
     * @ManyToOne(targetEntity="Paperroll\Model\Entry", inversedBy="images")
     * @JoinColumn(name="entry_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $entry;

    /**
     * @var string
     * @Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var integer
     * @Column(name="kind", type="integer", nullable=false)
     */
    private $kindId;

    /**
     * @var ImageKind
     * @OneToOne(targetEntity="Paperroll\Model\ImageKind")
     * @JoinColumn(name="kind");
     */
    private $kind;

    /**
     * @var string
     */
    private $filePath;

    /**
     * Get id
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set entryId
     * @param integer $entryId
     * @return Image
     */
    public function setEntryId($entryId) {
        $this->entryId = $entryId;
        return $this;
    }

    /**
     * Get entryId
     * @return integer
     */
    public function getEntryId() {
        return $this->entryId;
    }

    /**
     * Set path
     * @param string $path
     * @return Image
     */
    public function setFilename($path) {
        $this->path = $path;
        return $this;
    }

    /**
     * Get path
     * @return string
     */
    public function getFilename() {
        return $this->path;
    }

    /**
     * Get kind
     * @return ImageKind
     */
    public function getKind() {
        return $this->kind;
    }

    /**
     * @return Entry
     */
    public function getEntry() {
        return $this->entry;
    }

    public function getUrl($size = null) {
        if ($size)
            return File::getCacheUrl($this->getPath(), $size);
        else
            return File::baseUrl() . 'gallery/' . $this->getKind()->getPath() . '/' . $this->getFilename();
    }

    public function getPath() {
        if(!$this->filePath) {
            $this->filePath = BASEDIR . '/gallery/' . $this->getKind()->getPath() . '/' . $this->getFilename();
        }
        return $this->filePath;
    }

    /**
     * @return int
     */
    public function getKindId() {
        return $this->kindId;
    }

    /**
     * @param int $kindId
     */
    public function setKindId($kindId) {
        $this->kindId = $kindId;
    }

    /**
     * @return int
     */
    public function getPosition() {
        return $this->getKind()->getPosition();
    }

}
