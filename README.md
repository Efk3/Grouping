Grouping
========
Grouping library provides a class to create groups with different options. You add members and the class will try to pair those members to groups. It can be very helpful in some cases, like create a tag cloud.

Installation
------------------------
You can install it with composer:

	"efk3/grouping": "1.0.*@dev"
    
Options
-------
When you create a group you can choose from these options:

* ```id``` - Required. ID of the group.
* ```min``` - The group should contain *value* members at least.
* ```max``` - The group should contain *value* members at most.
* ```member``` - Combination of ```min``` and ```max``` options.
* ```min_percent``` - The group should contain *value* percent of the members at least.
* ```max_percent``` - The group should contain *value* percent of the members at most.
* ```percent``` - Combination of ```min_percent``` and ```max_percent``` options.
* ```constraints``` - Member has to satisfy the ```constraints```. You can choose constraints from [Symfony constraints classes](http://symfony.com/doc/master/reference/constraints.html).

Members don't have any special options unless you set sort to true then all members must have the sort key.

How to use constraint
--------------------
```php
use Symfony\Component\Validator\Constraints as Assert;

$grouping->addGroup(array(
	"id"			=>	1,
	"constraints"	=>	array(
		"arrayKeyName" => array(
			new Assert\Min(array("limit" => 10)),
			new Assert\Max(array("limit" => 20)),
		),
	), 
));
```

Sorting
----------
At default groups will be checked in the order as you have added them. But there is a method<br />
	
```php
$grouping->setSort(true);
```

with the groups will be sorted by ```ID``` key.

Members also will be sorted by ```priority``` (default) key. You can change the key with:

```php
$grouping->setSortKey("keyname");
```

How is it work?
-------------------
Each member will be added maximum one group. If the member is not fit constraints of any group or every group is full then the member won't be added to any group.
Also if you added less member than the sum of minimum member options of groups then groups with less priority (by adding or ID order) or without minimum option will be removed.

Tag cloud examle
----------------
```php
$grouping = new \Efk3\Grouping();
$grouping->addGroups(array(
	array("id" => 1, "member" => 1, "percent" => 3),
	array("id" => 2, "member" => 1, "percent" => 7),
	array("id" => 3, "member" => 1, "percent" => 10),
	array("id" => 4, "percent" => 10),
	array("id" => 5),
	array("id" => 6, "percent" => 10),
	array("id" => 7, "percent" => 10),
	array("id" => 8, "percent" => 10),
	array("id" => 9, "percent" => 10),
))
->setSort(true)
->setSortKey("used")
->addMembers($tags);
/*
	$tags is like this:
	array(
		0 => array("name" => "work", "used" => 17),
 		...
	);
*/

foreach ($grouping->grouping() as $tag) {
	echo "<span class='tag size" . $tag["group"]["id"] . "'>" . $tag["name"] . "</span>\r\n";
}
```

In this example every tag will be added in a group, at least 1 or 3% of members in Group #1, at least 1 or 7% of member in Group #2, etc. and remain members will be added to Group #5, which doesn't have any maximum option. Before grouping members will be sorted by ```used``` desc. Members with highest ```used``` value will be added to Group #1.
