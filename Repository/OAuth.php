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

namespace LiamW\APIImprovements\Repository;

use LiamW\APIImprovements\Entity\OAuthAuthorizationRequest;
use LiamW\APIImprovements\Entity\OAuthClient;
use LiamW\APIImprovements\Entity\OAuthCode;
use LiamW\APIImprovements\Entity\OAuthToken;
use OAuth\Common\Http\Uri\Uri;
use XF\Mvc\Entity\Repository;

class OAuth extends Repository
{
	/**
	 * @param $clientId
	 *
	 * @param null $clientSecret
	 *
	 * @return \XF\Mvc\Entity\Entity|OAuthClient
	 */
	public function findActiveOAuthClient($clientId, $clientSecret = null)
	{
		$finder = $this->finder('LiamW\APIImprovements:OAuthClient')->whereId($clientId)->where('active', 1);

		if ($clientSecret !== null)
		{
			$finder->where('client_secret', $clientSecret);
		}

		return $finder->fetchOne();
	}

	public function findPendingCode($code, $redirectUri)
	{
		$finder = $this->finder('LiamW\APIImprovements:OAuthCode')->whereId($code);
		$finder->where('OAuthAuthorizationRequest.redirect_uri', $redirectUri);

		return $finder->fetchOne();
	}

	/**
	 * @param $token
	 *
	 * @return \XF\Mvc\Entity\Entity|OAuthToken
	 */
	public function findOAuthToken($token)
	{
		return $this->em->find('LiamW\APIImprovements:OAuthToken', $token);
	}

	public function findOAuthClientsForList()
	{
		$finder = $this->finder('LiamW\APIImprovements:OAuthClient');

		return $finder->setDefaultOrder('creation_date', 'desc');
	}

	public function buildRedirectUri(Uri $redirectUri, $state, array $queryParams)
	{
		foreach ($queryParams AS $fieldName => $fieldValue)
		{
			$redirectUri->addToQuery($fieldName, $fieldValue);
		}

		if ($state)
		{
			$redirectUri->addToQuery('state', $state);
		}

		return $redirectUri->getAbsoluteUri();
	}

	public function buildRedirectUriFromAuthorizationRequest(OAuthAuthorizationRequest $authorizationRequest, array $queryParams)
	{
		return $this->buildRedirectUri(new Uri($authorizationRequest->redirect_uri), $authorizationRequest->state, $queryParams);
	}
}