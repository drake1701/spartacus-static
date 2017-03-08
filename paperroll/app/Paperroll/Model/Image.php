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
 * @Table(name="image")
 * @Entity(repositoryClass="Paperroll\Model\Repository\Image")
 */
class Image
{
    CONST PREVIEW = 924;
    CONST THUMB = 400;
    CONST MOBILE_THUMB = 340;

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
     * @Column(name="kind_id", type="integer", nullable=false)
     */
    private $kindId;

    /**
     * @var ImageKind
     * @ManyToOne(targetEntity="Paperroll\Model\ImageKind", fetch="EAGER")
     * @JoinColumn(name="kind_id", referencedColumnName="id")
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

    /**
     * @param null $size
     * @return string
     */
    public function getUrl($size = null) {
        if ($size)
            return File::getCacheUrl($this->getPath(), $size);
        else
            return File::baseUrl() . 'gallery/' . $this->getKind()->getPath() . '/' . $this->getFilename();
    }

    /**
     * @return string
     */
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
