<?php
class CBitrixCloudCDNLocation
{
	private $name = "";
	private $proto = "";
	private $prefixes = /*.(array[int]string).*/ array();
	/** @var array[int]CBitrixCloudCDNClass $classes */
	private $classes = /*.(array[int]CBitrixCloudCDNClass).*/ array();
	/** @var array[int]CBitrixCloudCDNServerGroup $server_groups */
	private $server_groups = /*.(array[int]CBitrixCloudCDNServerGroup).*/ array();
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
	 * @return string
	 *
	 */
	public function getProto()
	{
		return $this->proto;
	}
	/**
	 *
	 * @return array[int]string
	 *
	 */
	public function getPrefixes()
	{
		return $this->prefixes;
	}
	/**
	 *
	 * @param array[int]string $prefixes
	 * @return CBitrixCloudCDNLocation
	 *
	 */
	public function setPrefixes($prefixes)
	{
		$this->prefixes = /*.(array[int]string).*/ array();
		if (is_array($prefixes))
		{
			foreach ($prefixes as $prefix)
			{
				$prefix = trim($prefix, " \t\n\r");
				if ($prefix != "")
					$this->prefixes[] = $prefix;
			}
		}
		return $this;
	}
	/**
	 *
	 * @param string $name
	 * @param string $proto
	 * @param array[int]string $prefixes
	 * @return void
	 *
	 */
	public function __construct($name, $proto, $prefixes)
	{
		$this->proto = $proto;
		$this->name = $name;
		$this->setPrefixes($prefixes);
	}
	/**
	 *
	 * @return array[int]CBitrixCloudCDNClass
	 *
	 */
	public function getClasses()
	{
		return $this->classes;
	}
	/**
	 *
	 * @return array[int]CBitrixCloudCDNServerGroup
	 *
	 */
	public function getServerGroups()
	{
		return $this->server_groups;
	}
	/**
	 *
	 * @param CBitrixCloudCDNClass $file_class
	 * @param CBitrixCloudCDNServerGroup $server_group
	 * @return CBitrixCloudCDNLocation
	 *
	 */
	public function addService($file_class, $server_group)
	{
		if (is_object($file_class) && $file_class instanceof CBitrixCloudCDNClass && is_object($server_group) && $server_group instanceof CBitrixCloudCDNServerGroup)
		{
			$this->classes[] = $file_class;
			$this->server_groups[] = $server_group;
		}
		return $this;
	}
	/**
	 *
	 * @param CDataXMLNode $node
	 * @param CBitrixCloudCDNConfig $config
	 * @return CBitrixCloudCDNLocation
	 *
	 */
	public static function fromXMLNode(CDataXMLNode $node, CBitrixCloudCDNConfig $config)
	{
		$name = $node->getAttribute("name");
		$proto = $node->getAttribute("proto");
		$prefixes = /*.(array[int]string).*/ array();
		$nodePrefixes = $node->elementsByName("prefix");
		foreach ($nodePrefixes as $nodePrefix)
		{
			$prefixes[] = $nodePrefix->textContent();
		}
		$location = new CBitrixCloudCDNLocation($name, $proto, $prefixes);
		$nodeServices = $node->elementsByName("service");
		foreach ($nodeServices as $nodeService)
		{
			$file_class = $config->getClassByName($nodeService->getAttribute("class"));
			$server_group = $config->getServerGroupByName($nodeService->getAttribute("servergroup"));
			$location->addService($file_class, $server_group);
		}
		return $location;
	}
	/**
	 *
	 * @param string $name
	 * @param string $value
	 * @param CBitrixCloudCDNConfig $config
	 * @return CBitrixCloudCDNLocation
	 *
	 */
	public static function fromOptionValue($name, $value, CBitrixCloudCDNConfig $config)
	{
		$values = unserialize($value);
		$proto = "";
		$prefixes = /*.(array[int]string).*/ array();
		$services = /*.(array[string]string).*/ array();
		if (is_array($values))
		{
			if (isset($values["prefixes"]) && is_array($values["prefixes"]))
			{
				foreach ($values["prefixes"] as $prefix)
					$prefixes[] = $prefix;
			}
			if (isset($values["services"]) && is_array($values["services"]))
			{
				$services = $values["services"];
			}
			if (isset($values["proto"]))
			{
				$proto = $values["proto"];
			}
		}
		$location = new CBitrixCloudCDNLocation($name, $proto, $prefixes);
		foreach ($services as $file_class => $server_group)
		{
			$location->addService($config->getClassByName($file_class), $config->getServerGroupByName($server_group));
		}
		return $location;
	}
	/**
	 *
	 * @return string
	 *
	 */
	public function getOptionValue()
	{
		$services = /*.(array[string]string).*/ array();
		foreach ($this->classes as $i => $file_class)
		{
			/* @var CBitrixCloudCDNClass $file_class */
			$class_name = $file_class->getName();
			/* @var CBitrixCloudCDNServerGroup $server_group */
			$server_group = $this->server_groups[$i];
			$services[$class_name] = $server_group->getName();
		}
		return serialize(array(
			"proto" => $this->proto,
			"prefixes" => $this->prefixes,
			"services" => $services,
		));
	}
	/**
	 *
	 * @param string $p_prefix
	 * @param string $p_extension
	 * @param string $p_link
	 * @return string
	 *
	 */
	public function getServerNameByPrefixAndExtension($p_prefix, $p_extension, $p_link)
	{
		foreach ($this->prefixes as $prefix)
		{
			if ($p_prefix === $prefix)
			{
				foreach ($this->classes as $i => $file_class)
				{
					/* @var CBitrixCloudCDNClass $file_class */
					foreach ($file_class->getExtensions() as $extension)
					{
						if (strtolower($p_extension) === $extension)
						{
							/* @var CBitrixCloudCDNServerGroup $server_group */
							$server_group = $this->server_groups[$i];
							$servers = $server_group->getServers();
							if (!empty($servers))
							{
								$j = intval(abs(crc32($p_link))) % count($servers);
								return $servers[$j];
							}
						}
					}
				}
			}
		}
		return "";
	}
}
class CBitrixCloudCDNLocations implements Iterator
{
	private $locations = /*.(array[string]CBitrixCloudCDNLocation).*/ array();
	/**
	 *
	 * @param CBitrixCloudCDNLocation $location
	 * @return CBitrixCloudCDNLocations
	 *
	 */
	public function addLocation(CBitrixCloudCDNLocation $location)
	{
		$this->locations[$location->getName()] = $location;
		return $this;
	}
	/**
	 *
	 * @param string $location_name
	 * @return CBitrixCloudCDNLocation
	 *
	 */
	public function getLocationByName($location_name)
	{
		return $this->locations[$location_name];
	}
	/**
	 *
	 * @param CDataXMLNode $node
	 * @param CBitrixCloudCDNConfig $config
	 * @return CBitrixCloudCDNLocations
	 *
	 */
	public static function fromXMLNode(CDataXMLNode $node, CBitrixCloudCDNConfig $config)
	{
		$locations = new CBitrixCloudCDNLocations;
		foreach ($node->children() as $sub_node)
		{
			$locations->addLocation(CBitrixCloudCDNLocation::fromXMLNode($sub_node, $config));
		}
		return $locations;
	}
	/**
	 *
	 * @param CBitrixCloudOption $option
	 * @param CBitrixCloudCDNConfig $config
	 * @return CBitrixCloudCDNLocations
	 *
	 */
	public static function fromOption(CBitrixCloudOption $option, CBitrixCloudCDNConfig $config)
	{
		$locations = new CBitrixCloudCDNLocations;
		foreach ($option->getArrayValue() as $location_name => $location_value)
		{
			$locations->addLocation(CBitrixCloudCDNLocation::fromOptionValue($location_name, $location_value, $config));
		}
		return $locations;
	}
	/**
	 *
	 * @param CBitrixCloudOption $option
	 * @return CBitrixCloudCDNLocations
	 *
	 */
	public function saveOption(CBitrixCloudOption $option)
	{
		$locations = /*.(array[string]string).*/ array();
		foreach ($this->locations as $location_name => $location)
		{
			/* @var CBitrixCloudCDNLocation $location */
			$locations[$location_name] = $location->getOptionValue();
		}
		$option->setArrayValue($locations);
		return $this;
	}
	
	public function rewind()
	{
		reset($this->locations);
	}
	
	public function current()
	{
		return current($this->locations);
	}
	
	public function key()
	{
		return key($this->locations);
	}
	
	public function next()
	{
		next($this->locations);
	}
	
	public function valid()
	{
		return key($this->locations) !== null;
	}
}
