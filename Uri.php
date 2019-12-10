<?php
/**
 * API Improvements - XenForo add-on to add various additional features to the XF 2.1 API
 * Copyright (C) 2019 Liam Williams
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace LiamW\APIImprovements;

// TODO

class Uri
{
	protected $scheme;

	protected $userInfo;

	protected $host;

	protected $port;

	protected $path = '/';

	protected $query = '';

	protected $fragment = '';

	protected $explicitPort = false;
	protected $explicitTrailingHostSlash = false;

	public function __construct($uri = null)
	{
		if ($uri !== null)
		{
			$this->parseUri($uri);
		}
	}

	protected function parseUri($uri)
	{
		if (!($parts = parse_url($uri)))
		{
			throw new \InvalidArgumentException(sprintf("Invalid URI: %s", $uri));
		}

		if (!empty($parts['scheme']))
		{
			$this->scheme = $parts['scheme'];
		}

		if (!empty($parts['host']))
		{
			$this->host = $parts['host'];
		}

		if (!empty($parts['port']))
		{
			$this->port = $parts['port'];
		}
		else if ($this->scheme == 'http' || $this->scheme == 'https')
		{
			$this->port = $this->scheme == 'https' ? 443 : 80;
		}

		if (!empty($parts['path']))
		{
			$this->path = $parts['path'];
			if ($parts['path'] === '/')
			{
				$this->explicitTrailingHostSlash = true;
			}
		}
		else
		{
			$this->path = '/';
		}

		$this->query = isset($parts['query']) ? $parts['query'] : '';
		$this->fragment = isset($parts['fragment']) ? $parts['fragment'] : '';

		$userInfo = '';
		if (!empty($parts['user']))
		{
			$userInfo .= $parts['user'];
		}
		if ($userInfo && !empty($parts['pass']))
		{
			$userInfo .= ':' . $parts['pass'];
		}

		$this->userInfo = $userInfo;
	}
}