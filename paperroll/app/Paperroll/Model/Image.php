<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model;

/**
 * Image
 *
 * @Table(name="image", indexes={@Index(name="image_post-fk", columns={"entry_id"}), @Index(name="image_kind-fk", columns={"kind"}), @Index(name="image_entry_id", columns={"entry_id", "kind"}), @Index(name="image_entry", columns={"entry_id"})})
 * @Entity
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
     * @var string
     * @Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var integer
     * @Column(name="kind", type="integer", nullable=false)
     */
    private $kind;


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
    public function setPath($path) {
        $this->path = $path;
        return $this;
    }

    /**
     * Get path
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Set kind
     * @param integer $kind
     * @return Image
     */
    public function setKind($kind) {
        $this->kind = $kind;
        return $this;
    }

    /**
     * Get kind
     * @return integer
     */
    public function getKind() {
        return $this->kind;
    }
}
