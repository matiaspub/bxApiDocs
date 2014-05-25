<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Web;

class Uri
{
	protected $url;
	protected $parsed = false;

	protected $scheme;
	protected $host;
	protected $port;
	protected $user;
	protected $pass;
	protected $path;
	protected $query;
	protected $pathQuery;
	protected $fragment;

	public function __construct($url)
	{
		$this->url = $url;
	}

	public function getUrl()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}

		$url = "";
		if($this->host <> '')
		{
			$url .= $this->scheme."://".$this->host;

			if(($this->scheme == "http" && $this->port <> 80) || ($this->scheme == "https" && $this->port <> 443))
			{
				$url .= ":".$this->port;
			}
		}

		$url .= $this->pathQuery;

		return $url;
	}

	public function parse()
	{
		$parsedUrl = parse_url($this->url);

		$this->parsed = true;

		if($parsedUrl !== false)
		{
			$this->scheme = (isset($parsedUrl["scheme"])? strtolower($parsedUrl["scheme"]) : "http");
			$this->host = $parsedUrl["host"];
			if(isset($parsedUrl["port"]))
			{
				$this->port = $parsedUrl["port"];
			}
			else
			{
				$this->port = ($this->scheme == "https"? 443 : 80);
			}
			$this->user = $parsedUrl["user"];
			$this->pass = $parsedUrl["pass"];
			$this->path = ((isset($parsedUrl["path"])? $parsedUrl["path"] : "/"));
			$this->query = $parsedUrl["query"];
			$this->pathQuery = $this->path;
			if($this->query <> "")
			{
				$this->pathQuery .= '?'.$this->query;
			}
			$this->fragment = $parsedUrl["fragment"];

			return true;
		}
		return false;
	}

	public function getFragment()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}
		return $this->fragment;
	}

	public function getHost()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}
		return $this->host;
	}

	public function getPass()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}
		return $this->pass;
	}

	public function getPath()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}
		return $this->path;
	}

	public function getPathQuery()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}
		return $this->pathQuery;
	}

	public function getPort()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}
		return $this->port;
	}

	public function getQuery()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}
		return $this->query;
	}

	public function getScheme()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}
		return $this->scheme;
	}

	public function getUser()
	{
		if(!$this->parsed)
		{
			$this->parse();
		}
		return $this->user;
	}
}
