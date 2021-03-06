<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model;

/**
 * EntryLog
 *
 * @Table(name="entry_log")
 * @Entity
 * @HasLifecycleCallbacks()
 */
class EntryLog
{
    /**
     * @var integer
     * @Column(name="id", type="integer", nullable=false)
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
     * @ManyToOne(targetEntity="Paperroll\Model\Entry")
     * @JoinColumn(name="entry_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $entry;

    /**
     * @var string
     * @Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var \DateTime
     * @Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;


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
     * @return EntryLog
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
     * Set message
     * @param string $message
     * @return EntryLog
     */
    public function setMessage($message) {
        $this->message = $message;
        return $this;
    }

    /**
     * Get message
     * @return string
     */
    public function getMessage() {
        return $this->message;
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
     * @return Entry
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * @param Entry $entry
     * @return $this
     */
    public function setEntry($entry) {
        $this->entry = $entry;
        return $this;
    }
}
