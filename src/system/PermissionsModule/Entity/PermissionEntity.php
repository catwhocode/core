<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\PermissionsModule\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Zikula\Bundle\CoreBundle\Doctrine\EntityAccess;

/**
 * Permission entity class.
 *
 * @ORM\Entity(repositoryClass="Zikula\PermissionsModule\Entity\Repository\PermissionRepository")
 * @ORM\Table(name="group_perms")
 */
class PermissionEntity extends EntityAccess
{
    /**
     * permission rule id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $pid;

    /**
     * group id for the rule
     *
     * @ORM\Column(type="integer")
     * @var int
     */
    private $gid;

    /**
     * the place of the rule in the sequence
     *
     * @ORM\Column(type="integer")
     * @var int
     */
    private $sequence;

    /**
     * the component part of the rule
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(min="0", max="255", allowEmptyString="false")
     * @var string
     */
    private $component;

    /**
     * the instance part of the rule
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(min="0", max="255", allowEmptyString="false")
     * @var string
     */
    private $instance;

    /**
     * the access level of the rule
     *
     * @ORM\Column(type="integer")
     * @var int
     */
    private $level;

    /**
     * optional comment
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(min="0", max="255", allowEmptyString="true")
     * @var string
     */
    private $comment;

    /**
     * optional colour (Bootstrap contextual class)
     *
     * @ORM\Column(type="string", length=10)
     * @Assert\Length(min="0", max="10", allowEmptyString="true")
     * @var string
     */
    private $colour;

    public function __construct()
    {
        $this->gid = 0;
        $this->sequence = 0;
        $this->component = '';
        $this->instance = '';
        $this->level = 0;
        $this->comment = '';
        $this->colour = '';
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function getGid(): int
    {
        return $this->gid;
    }

    public function setGid(int $gid): void
    {
        $this->gid = $gid;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function setComponent(string $component): void
    {
        $this->component = $component;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function setInstance(string $instance): void
    {
        $this->instance = $instance;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function getColour(): string
    {
        return $this->colour;
    }

    public function setColour(string $colour): void
    {
        $this->colour = $colour;
    }
}
