<?php
/**
 * @package   Paperroll
 * @author    Spartacus <spartacuswalls@gmail.com>
 * @link      http://www.spartacuswallpaper.com/
 */

namespace Paperroll\Model;

/**
 * ImageKind
 *
 * @Table(name="image_kind")
 * @Entity(repositoryClass="Paperroll\Model\Repository\ImageKind")
 */
class ImageKind
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
     * @Column(name="path", type="string", length=45, nullable=false)
     */
    private $path;

    /**
     * @var string
     * @Column(name="label", type="string", length=45, nullable=false)
     */
    private $label;

    /**
     * @var boolean
     * @Column(name="is_required", type="boolean", nullable=true)
     */
    private $isRequired;

    /**
     * @var boolean
     * @Column(name="exclude", type="boolean", nullable=true)
     */
    private $exclude;

    /**
     * @var integer
     * @Column(name="position", type="integer", nullable=true)
     */
    private $position;

    /**
     * @var boolean
     * @Column(name="mobile", type="boolean", nullable=true)
     */
    private $mobile;


    /**
     * Get id
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set path
     * @param string $path
     * @return ImageKind
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
     * Set label
     * @param string $label
     * @return ImageKind
     */
    public function setLabel($label) {
        $this->label = $label;
        return $this;
    }

    /**
     * Get label
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * Set isRequired
     * @param boolean $isRequired
     * @return ImageKind
     */
    public function setIsRequired($isRequired) {
        $this->isRequired = $isRequired;
        return $this;
    }

    /**
     * Get isRequired
     * @return boolean
     */
    public function getIsRequired() {
        return $this->isRequired;
    }

    /**
     * Set exclude
     * @param boolean $exclude
     * @return ImageKind
     */
    public function setExclude($exclude) {
        $this->exclude = $exclude;
        return $this;
    }

    /**
     * Get exclude
     * @return boolean
     */
    public function getExclude() {
        return $this->exclude;
    }

    /**
     * Set position
     * @param integer $position
     * @return ImageKind
     */
    public function setPosition($position) {
        $this->position = $position;
        return $this;
    }

    /**
     * Get position
     * @return integer
     */
    public function getPosition() {
        return $this->position;
    }

    /**
     * Set mobile
     * @param boolean $mobile
     * @return ImageKind
     */
    public function setMobile($mobile) {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * Get mobile
     * @return boolean
     */
    public function getMobile() {
        return $this->mobile;
    }
}
