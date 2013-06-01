<?php

namespace Efk3\Grouping;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class Grouping implements GroupingInterface
{
    protected static $groupResolver = null;
    protected static $validator;

    private $groups = array();
    private $sort = false;
    private $sortKey = "priority";
    private $reverseSort = false;
    private $sortFunction = null;
    private $members = array();

    /**
     * Initialize default resolver
     */
    public function __construct($assertKey = null)
    {
        if (self::$groupResolver == null) {
            self::$groupResolver = new OptionsResolver();
            self::$groupResolver->setRequired(array("id"));
            self::$groupResolver->setOptional(array("name", "member", "min", "min_percent", "max", "max_percent", "percent", "constraints"));
            self::$groupResolver->setAllowedTypes(array(
                "name"              =>  "string",
                "member"            =>  "numeric",
                "min"               =>  "numeric",
                "min_percent"       =>  "numeric",
                "max"               =>  "numeric",
                "max_percent"       =>  "numeric",
                "percent"           =>  "numeric",
                "constraints"       =>  "array",
            ));

            self::$validator = Validation::createValidator();
        }

        $this->assertKey = $assertKey;
    }

    /**
     * {@inheritDoc}
     */
    public function grouping()
    {
        $this->sort();

        /* reset group parameter of members */
        foreach ($this->members as $key => $member) {
            unset($this->members[$key]["group"]);
        }

        /* normalize groups */
        $membersCount = count($this->members);
        $left_min = 0;
        foreach ($this->groups as $key => $group) {
            $this->groups[$key]["members"] = array();

            if (isset($group["min_percent"])) {
                $min = ceil($group["min_percent"] / 100 * $membersCount);
                if (isset($group["min"]) && $group["min"] > $min) {
                    $min = $group["min"];
                }
                $this->groups[$key]["min"] = $group["min"] = $min;
            }

            if (isset($group["min"])) {
                $left_min += $group["min"];
            } else {
                $this->groups[$key]["min"] = 0;
            }

            if (isset($group["max_percent"])) {
                $max = ceil($group["max_percent"] / 100 * $membersCount);
                if (isset($group["max"]) && $group["max"] > $max) {
                    $max = $group["max"];
                }
                $this->groups[$key]["max"] = $max;
            }

            if (!isset($this->groups[$key]["max"])) {
                $this->groups[$key]["max"] = PHP_INT_MAX;
            }
        }

        /* if $left_min more than number of members then remove groups which doesn't have "min" parameter */
        if ($left_min > $membersCount) {
            foreach ($this->groups as $key => $group) {
                if (!isset($group["min"])) {
                    unset($this->groups[$key]);
                }
            }
        }

        /* remove groups until $left_min reach the number of members  */
        while ($left_min > $membersCount) {
            $pop = array_pop($this->groups);

            $left_min -= $pop["min"];
            if ($left_min < $membersCount) {
                $pop["mim"] = $membersCount - $left_min;
                $this->groups[$pop["id"]] = $pop;
                $left_min = $membersCount;
            }
        }

        if ($left_min == $membersCount) {
            foreach ($this->groups as $key => $group) {
                $this->groups[$key]["max"] = $this->groups[$key]["min"];
            }
        }

        $grouped = 0;
        foreach ($this->groups as $key => $group) {
            $added = 0;
            $min = $group["min"] ? $group["min"] : 0;
            foreach ($this->members as $memberKey => $member) {
                if (isset($member["group"])) {
                    continue;
                }
                if ($this->isValid($group, $member)) {
                    if ($added == $group["max"] || ($min == 0 && $membersCount - $grouped == $left_min)) {
                        break;
                    }

                    $this->members[$memberKey]["group"] = &$this->groups[$key];
                    $this->groups[$key]["members"][] = &$this->members[$memberKey];
                    $added++;
                    $grouped++;
                    if ($min) {
                        $left_min--;
                        $min--;
                    }
                }
            }
        }

        return $this->members;
    }

    /**
     * Is the member suitable for the group?
     * It only has effect when constraints was set to the group
     *
     * @param array $group
     * @param array $member
     *
     * @return bool
     */
    protected function isValid($group, $member)
    {
        if (!empty($group["constraints"])) {
            foreach ($group["constraints"] as $key => $constraint) {
                if (!isset($member[$key])) {
                    return false;
                }
                $violations = self::$validator->validateValue($member[$key], $constraint);
                if ($violations->count() > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Sort groups and members if needs
     */
    protected function sort()
    {
        if ($this->sort) {
            $this->checkSortKey();

            $sortKey = $this->sortKey;
            if ($this->sortFunction == null) {
                if ($this->reverseSort) {
                    usort($this->members, function ($a, $b) use ($sortKey) {
                        return $a[$sortKey] == $b[$sortKey] ? 0 : $a[$sortKey] < $b[$sortKey] ? -1 : 1;
                    });
                } else {
                    usort($this->members, function ($a, $b) use ($sortKey) {
                        return $a[$sortKey] == $b[$sortKey] ? 0 : $a[$sortKey] < $b[$sortKey] ? 1 : -1;
                    });
                }
            } else {
                usort($this->members, $this->sortFunction);
            }
            
            ksort($this->groups);
        }
    }

    /**
     * Do members have the sort key?
     *
     * @throws \Exception If a member does not have the sort key
     */
    protected function checkSortKey()
    {
        if ($this->sort) {
            foreach ($this->members as $member) {
                if (!isset($member[$this->sortKey])) {
                    throw new \Exception("Member does not have '" . $this->sortKey . "' key. (Member: " . var_export($member, true) . ")");
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * {@inheritDoc}
     */
    public function addGroup(array $group)
    {
        $group = self::$groupResolver->resolve($group);

        if (isset($group["member"])) {
            $group["min"] = $group["member"];
            $group["max"] = $group["member"];
        }

        if (isset($group["percent"])) {
            $group["min_percent"] = $group["percent"];
            $group["max_percent"] = $group["percent"];
        }

        if (!isset($group["constraints"])) {
            $group["constraints"] = array();
        }

        $this->groups[$group["id"]] = $group;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addGroups(array $groups)
    {
        foreach ($groups as $group) {
            $this->addGroup($group);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * {@inheritDoc}
     */
    public function addMember($member)
    {
        $this->members[] = $member;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addMembers(array $members)
    {
        foreach ($members as $member) {
            $this->addMember($member);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * {@inheritDoc}
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setSortKey($sortKey)
    {
        $this->sortKey = $sortKey;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortKey()
    {
        return $this->sortKey;
    }

    /**
     * {@inheritDoc}
     */
    public function setReverseSort($reverseSort)
    {
        $this->reverseSort = $reverseSort;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getReverseSort()
    {
        return $this->reverseSort;
    }

    /**
     * {@inheritDoc}
     */
    public function setSortFunction($sortFunction)
    {
        $this->sortFunction = $sortFunction;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortFunction()
    {
        return $this->sortFunction;
    }
}
