<?php


namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Region\RegionCollection;

/**
 * Full structural description of the first page of the PDF we want to generate.
 * Contains individual 'regions' (such as one named 'map', 'overview' etc) and
 * 'text fields' (such as 'title').
 *
 * Text fields and regions are functionally the same, but kept in separate pools
 * for historical reasons.
 *
 * Simulates an array structure via ArrayAccess for compatibility with legacy access
 * patterns. The simulated array looks sth like:
 * pageSize:
 *    width: <number>
 *    height: <number>
 * orientation: <string>
 * fields: @see RegionCollection
 * <other dynamic string keys>: @see RegionCollection
 */
class Template implements \ArrayAccess
{
    const ORIENTATION_LANDSCAPE = 'landscape';
    const ORIENTATION_PORTRAIT = 'portrait';

    /** @var float in mm*/
    protected float $width;
    /** @var float in mm*/
    protected float $height;
    protected string $orientation;
    protected RegionCollection $textFields;
    protected RegionCollection $regions;

    /**
     * @param float $width in mm
     * @param float $height in mm
     * @param string $orientation
     */
    public function __construct(float $width, float $height, string $orientation)
    {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException("Invalid width / height " . $width . ' ' . $height);
        }
        $this->width = $width;
        $this->height = $height;
        switch ($orientation) {
            case self::ORIENTATION_LANDSCAPE:
            case self::ORIENTATION_PORTRAIT:
                $this->orientation = $orientation;
                break;
            default:
                throw new \InvalidArgumentException("Invalid orientation " . $orientation);
        }

        $this->textFields = new RegionCollection();
        $this->regions = new RegionCollection();
    }

    /**
     * @return string
     */
    public function getOrientation(): string
    {
        return $this->orientation;
    }

    /**
     * @return float
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * @return float
     */
    public function getHeight(): float
    {
        return $this->height;
    }

    /**
     * @return RegionCollection|TemplateRegion[]
     */
    public function getRegions(): RegionCollection
    {
        return $this->regions;
    }

    /**
     * @return RegionCollection|TemplateRegion[]
     */
    public function getTextFields(): RegionCollection
    {
        return $this->textFields;
    }

    /**
     * @param string $name
     * @return TemplateRegion
     */
    public function getRegion($name): TemplateRegion
    {
        return $this->regions->getMember($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasRegion($name): bool
    {
        return $this->regions->hasMember($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasTextField($name): bool
    {
        return $this->textFields->hasMember($name);
    }

    /**
     * @param TemplateRegion $region
     */
    public function addRegion($region): void
    {
        $region->setParentTemplate($this);
        $this->regions->addMember($region->getName(), $region);
    }

    /**
     * @param TemplateRegion $field
     */
    public function addTextField($field): void
    {
        $field->setParentTemplate($this);
        $this->textFields->addMember($field->getName(), $field);
    }


    public function offsetGet($offset): mixed
    {
        return match ($offset) {
            'orientation' => $this->getOrientation(),
            'pageSize' => array(
                'width' => $this->getWidth(),
                'height' => $this->getHeight(),
            ),
            'fields' => $this->getTextFields(),
            default => $this->getRegion($offset),
        };
    }

    public function offsetExists($offset): bool
    {
        return match ($offset) {
            'orientation', 'pageSize', 'fields' => true,
            default => $this->hasRegion($offset),
        };
    }

    public function offsetSet($offset, $value): void
    {
        throw new \RuntimeException(get_class($this) . " does not support array-style mutation");
    }

    public function offsetUnset($offset): void
    {
        throw new \RuntimeException(get_class($this) . " does not support array-style mutation");
    }
}
