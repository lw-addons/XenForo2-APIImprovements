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

namespace LiamW\APIImprovements\Entity;

use LiamW\APIImprovements\Utils;
use OAuth\Common\Http\Uri\Uri;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Util\Random;

/**
 * COLUMNS
 * @property string client_id
 * @property string client_secret
 * @property string type
 * @property array redirect_uris_
 * @property int|null user_id
 * @property string label
 * @property string|null description
 * @property int creation_date
 * @property bool active
 *
 * GETTERS
 * @property Uri[] redirect_uris
 * @property mixed client_id_snippet
 * @property mixed last_use_date
 *
 * RELATIONS
 * @property \XF\Mvc\Entity\AbstractCollection|\LiamW\APIImprovements\Entity\OAuthCode[] OAuthCodes
 * @property \XF\Mvc\Entity\AbstractCollection|\LiamW\APIImprovements\Entity\OAuthToken[] OAuthTokens
 */
class OAuthClient extends Entity
{
	/**
	 * @return Uri[]
	 */
	public function getRedirectUris()
	{
		$uris = [];
		foreach ($this->redirect_uris_ AS $redirectUri)
		{
			$uris[] = new Uri($redirectUri);
		}

		return $uris;
	}

	public function getClientIDSnippet()
	{
		return substr($this->client_id, 0, 16) . '…';
	}

	public function getLastUseDate()
	{
		return 0;
	}

	public function verifyRedirectUris(array $redirectUris)
	{
		foreach ($redirectUris AS &$redirectUri)
		{
			try
			{
				$uri = new Uri($redirectUri);
			}
			catch (\InvalidArgumentException $e)
			{
				$this->error("~~Please enter valid URIs. The scheme must be specified.~~", 'redirect_uris');

				return false;
			}

			if ($uri->getScheme() == 'http')
			{
				$this->error("~~HTTP URIs must use the https scheme~~", 'redirect_uris');

				return false;
			}

			if (!empty($uri->getFragment()))
			{
				$this->error("~~Redirect URIs must not contain a fragment.~~", 'redirect_uris');

				return false;
			}

			$redirectUri = $uri->getAbsoluteUri();
		}

		return true;
	}

	protected function _preSave()
	{
		if ($this->isInsert())
		{
			$this->client_id = Utils::generateKeyValue('client_', 32);
			$this->client_secret = Utils::generateKeyValue();
		}

		if (!count($this->redirect_uris))
		{
			$this->error("~~At least one redirect URI must be specified~~", 'redirect_uris');
		}
	}

	public static function getStructure(Structure $structure)
	{
		$structure->shortName = 'LiamW\APIImprovements:OAuthClient';
		$structure->table = 'xf_liamw_apiimprovements_oauth2_client';
		$structure->primaryKey = 'client_id';

		$structure->columns = [
			'client_id' => [
				'type' => self::STR,
				'maxLength' => 64
			],
			'client_secret' => [
				'type' => self::STR,
				'maxLength' => 64,
				'unique' => true
			],
			'type' => [
				'type' => self::STR,
				'allowedValues' => [
					'confidential',
					'public'
				]
			],
			'redirect_uris' => [
				'type' => self::JSON_ARRAY
			],
			'user_id' => [
				'type' => self::UINT,
				'nullable' => true
			],
			'label' => [
				'type' => self::STR,
				'required' => '~~Please enter a valid label~~'
			],
			'description' => [
				'type' => self::STR,
				'nullable' => true
			],
			'creation_date' => [
				'type' => self::UINT,
				'default' => \XF::$time
			],
			'active' => [
				'type' => self::BOOL,
				'default' => true
			]
		];
		$structure->getters = [
			'redirect_uris' => true,
			'client_id_snippet' => true,
			'last_use_date' => true
		];
		$structure->relations = [
			'OAuthCodes' => [
				'entity' => 'LiamW\APIImprovements:OAuthCode',
				'type' => self::TO_MANY,
				'conditions' => 'client_id'
			],
			'OAuthTokens' => [
				'entity' => 'LiamW\APIImprovements:OAuthToken',
				'type' => self::TO_MANY,
				'conditions' => 'client_id'
			]
		];

		return $structure;
	}

	protected function _setupDefaults()
	{
		$this->redirect_uris = [];
	}
}