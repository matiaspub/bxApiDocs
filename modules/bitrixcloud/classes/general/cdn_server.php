<?php
class CBitrixCloudCDNServerGroup
{
	private $name = "";
	private $servers = /*.(array[int]string).*/ array();
	/**
	 *
	 * @return string
	 *
	 */
	public function getName()
	{
		return $this->name;
	}
	/**
	 *
	 * @return array[int]string
	 *
	 */
	public function getServers()
	{
		return $this->servers;
	}
	/**
	 *
	 * @param array[int]string $servers
	 * @return CBitrixCloudCDNServerGroup
	 *
	 */
	public function setServers($servers)
	{
		$this->servers = /*.(array[int]string).*/ array();
		if (is_array($servers))
		{
			foreach ($servers as $server)
			{
				$server = trim($server, " \t\n\r");
				if ($server != "")
					$this->servers[] = $server;
			}
		}
		return $this;
	}
	/**
	 *
	 * @param string $name
	 * @param array[int]string $servers
	 * @return void
	 *
	 */
	public function __construct($name, $servers)
	{
		$this->name = $name;
		$this->setServers($servers);
	}
	/**
	 *
	 * @param CDataXMLNode $node
	 * @return CBitrixCloudCDNServerGroup
	 *
	 */
	public static function fromXMLNode(CDataXMLNode $node)
	{
		$name = $node->getAttribute("name");
		$servers = /*.(array[int]string).*/ array();
		$nodeServers = $node->elementsByName("name");
		foreach ($nodeServers as $nodeServer)
		{
			$servers[] = $nodeServer->textContent();
		}
		return new CBitrixCloudCDNServerGroup($name, $servers);
	}
}
class CBitrixCloudCDNServerGroups
{
	/** @var array[string]CBitrixCloudCDNServerGroup $groups */
	private $groups = /*.(array[string]CBitrixCloudCDNServerGroup).*/ array();
	/**
	 *
	 * @param CBitrixCloudCDNServerGroup $group
	 * @return CBitrixCloudCDNServerGroups
	 *
	 */
	public function addGroup(CBitrixCloudCDNServerGroup $group)
	{
		$this->groups[$group->getName()] = $group;
		return $this;
	}
	/**
	 *
	 * @param string $group_name
	 * @return CBitrixCloudCDNServerGroup
	 *
	 */
	public function getGroup($group_name)
	{
		return $this->groups[$group_name];
	}
	/**
	 *
	 * @param CDataXMLNode $node
	 * @return CBitrixCloudCDNServerGroups
	 *
	 */
	public static function fromXMLNode(CDataXMLNode $node)
	{
		$groups = new CBitrixCloudCDNServerGroups;
		foreach ($node->children() as $sub_node)
		{
			$groups->addGroup(CBitrixCloudCDNServerGroup::fromXMLNode($sub_node));
		}
		return $groups;
	}
	/**
	 *
	 * @param CBitrixCloudOption $option
	 * @return CBitrixCloudCDNServerGroups
	 *
	 */
	public static function fromOption(CBitrixCloudOption $option)
	{
		$groups = new CBitrixCloudCDNServerGroups;
		foreach ($option->getArrayValue() as $group_name => $servers)
		{
			$groups->addGroup(new CBitrixCloudCDNServerGroup($group_name, explode(",", $servers)));
		}
		return $groups;
	}
	/**
	 *
	 * @param CBitrixCloudOption $option
	 * @return CBitrixCloudCDNServerGroups
	 *
	 */
	public function saveOption(CBitrixCloudOption $option)
	{
		$groups = /*.(array[string]string).*/ array();
		foreach ($this->groups as $group_name => $group)
		{
			/* @var CBitrixCloudCDNServerGroup $group */
			$groups[$group_name] = implode(",", $group->getServers());
		}
		$option->setArrayValue($groups);
		return $this;
	}
}
