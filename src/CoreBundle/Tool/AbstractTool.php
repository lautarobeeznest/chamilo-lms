<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;

/**
 * Class AbstractTool.
 */
abstract class AbstractTool implements ToolInterface
{
    protected $name;
    protected $category;
    protected $link;
    protected $image;
    protected $admin;
    protected $settings;
    protected $resourceTypes;

    /**
     * @var string
     *
     *  00 disabled tool
     *  01 course tool
     *  10 global tool
     *  11 global or course or both
     */
    protected $scope;

    /**
     * @param string          $name
     * @param string          $category
     * @param string          $link
     * @param SchemaInterface $settings
     * @param array           $resourceTypes
     * @param string          $scope
     */
    public function __construct($name, $category, $link, $settings = null, $resourceTypes = null)
    {
        $this->name = $name;
        $this->category = $category;
        $this->link = $link;
        $this->image = $name.'.png';
        $this->settings = $settings;
        $this->resourceTypes = $resourceTypes;
    }

    public function isCourseTool()
    {
        return false;
    }

    public function isGlobal()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return $this->link ? $this->link : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * {@inheritdoc}
     */
    public function getTarget()
    {
        return '_self';
    }

    /**
     * {@inheritdoc}
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return array
     */
    public function getResourceTypes()
    {
        return $this->resourceTypes;
    }

    public function setResourceTypes(array $resourceTypes): AbstractTool
    {
        $this->resourceTypes = $resourceTypes;

        return $this;
    }
}
