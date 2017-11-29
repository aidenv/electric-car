<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AdminRole
 */
class AdminRole
{

    /**
     * @var integer
     */
    private $adminRoleId;

    /**
     * @var string
     */
    private $name = '';


    /**
     * Get adminRoleId
     *
     * @return integer 
     */
    public function getAdminRoleId()
    {
        return $this->adminRoleId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return AdminRole
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @var string
     */
    private $role = 'ROLE_USER';

    /**
     * Set role
     *
     * @param string $role
     * @return AdminRole
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string 
     */
    public function getRole()
    {
        return $this->role;
    }
}
