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

namespace LiamW\APIImprovements\Service\OAuth;

use LiamW\APIImprovements\Entity\OAuthClient;
use XF\Entity\User;
use XF\Service\AbstractService;
use XF\Service\ValidateAndSavableTrait;

class ClientManager extends AbstractService
{
	use ValidateAndSavableTrait;

	const TYPE_PUBLIC = 'public';
	const TYPE_CONFIDENTIAL = 'confidential';

	protected $client;

	public function __construct(\XF\App $app, OAuthClient $client)
	{
		parent::__construct($app);

		$this->client = $client;
	}

	/**
	 * @return OAuthClient
	 */
	public function getClient()
	{
		return $this->client;
	}

	public function setType($type)
	{
		$this->client->type = $type;
	}

	public function setLabel($label)
	{
		$this->client->label = $label;
	}

	public function setActive($active)
	{
		$this->client->active = $active;
	}

	public function setDescription($description)
	{
		$this->client->description = $description;
	}

	public function setRedirectURIs(array $redirectUris)
	{
		$this->client->redirect_uris = array_filter($redirectUris);
	}

	public function setClientUser($user)
	{
		if (is_string($user))
		{
			$userEnt = $this->em()->findOne('XF:User', ['username' => $user]);
			if (!$userEnt)
			{
				$this->client->error(\XF::phrase('requested_user_not_found'), 'user_id');
			}

			$userId = $userEnt->user_id;
		}
		else if ($user instanceof User)
		{
			$userId = $user->user_id;
		}
		else
		{
			throw new \InvalidArgumentException("Must pass username or user entity to setClientUser method.");
		}

		$this->client->user_id = $userId;
	}

	protected function _validate()
	{
		$this->client->preSave();
		return $this->client->getErrors();
	}

	protected function _save()
	{
		$this->client->save();

		// TODO: Notifications
	}
}