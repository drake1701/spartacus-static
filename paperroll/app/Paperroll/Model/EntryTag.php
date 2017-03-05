<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */


namespace Paperroll\Model;

/**
 * EntryTag
 *
 * @Table(name="entry_tag", indexes={@Index(name="entry_tag_tag_fk", columns={"tag_id"}), @Index(name="entry_tag_entry_fk", columns={"entry_id"})})
 * @Entity(repositoryClass="Paperroll\Model\Repository\EntryTag")
 */
class EntryTag
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
     * @var integer
     * @Column(name="tag_id", type="integer", nullable=false)
     */
    private $tagId;


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
     * @return EntryTag
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
     * Set tagId
     * @param integer $tagId
     * @return EntryTag
     */
    public function setTagId($tagId) {
        $this->tagId = $tagId;
        return $this;
    }

    /**
     * Get tagId
     * @return integer
     */
    public function getTagId() {
        return $this->tagId;
    }
}
