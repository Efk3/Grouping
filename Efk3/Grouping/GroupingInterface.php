<?php

namespace Efk3\Grouping;

interface GroupingInterface
{
    /**
     * Grouping members
     *
     * @return array Members with group parameter
     */
    public function grouping();

    /**
     * Get groups
     *
     * @return array
     */
    public function getGroups();

    /**
     * Add a group
     *
     * @param array $group
     * @return GroupingInterface The GroupingInterface instance
     */
    public function addGroup(array $group);

    /**
     * Add multiple group
     *
     * @param array $groups Array of groups
     * @return GroupingInterface The GroupingInterface instance
     */
    public function addGroups(array $groups);

    /**
     * Get members
     *
     * @return array
     */
    public function getMembers();

    /**
     * Add a member
     *
     * @param mixed $member Member can be string or array
     * @return GroupingInterface The GroupingInterface instance
     */
    public function addMember($member);

    /**
     * Add multiple members
     *
     * @param array $members Array of members
     * @return GroupingInterface The GroupingInterface instance
     */
    public function addMembers(array $members);

    /**
     * Get sort
     * @return boolean
     */
    public function getSort();

    /**
     * Set sort
     * @param boolean $sort
     */
    public function setSort($sort);

    /**
     * Set default key of sort
     *
     * @param mixed $sortKey
     * @return GroupingInterface The GroupingInterface instance
     */
    public function setSortKey($sortKey);

    /**
     * Get default key of sort
     *
     * @return mixed
     */
    public function getSortKey();

    /**
     * Set sort of members to reverse or not
     *
     * @param boolean $reverseSort
     * @return GroupingInterface The GroupingInterface instance
     */
    public function setReverseSort($reverseSort);

    /**
     * Return true if member sort is reverse
     *
     * @return boolean
     */
    public function getReverseSort();

    /**
     * Set sort function of members
     *
     * @param callable $shortFunction Function will be called with 2 member arrays
     * @return GroupingInterface The GroupingInterface instance
     */
    public function setSortFunction($sortFunction);

    /**
     * Get sort function of members
     *
     * @param mixed Null if not be set before, callable otherwise
     */
    public function getSortFunction();
}