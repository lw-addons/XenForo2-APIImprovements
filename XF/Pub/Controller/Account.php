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

namespace LiamW\APIImprovements\XF\Pub\Controller;

use LiamW\APIImprovements\Entity\OAuthClient;
use LiamW\APIImprovements\Repository\OAuth;
use LiamW\APIImprovements\Service\OAuth\AuthorizationManager;
use OAuth\Common\Http\Uri\Uri;

class Account extends XFCP_Account
{
	public function actionOAuth2Grant()
	{
		$repository = $this->repository('LiamW\APIImprovements:OAuth');

		$clientId = $this->filter('client_id', 'str');
		$client = $repository->findActiveOAuthClient($clientId);
		if (!$client)
		{
			return $this->error("~~Unknown or invalid client.~~", 400);
		}

		list($responseType, $redirectUri, $codeChallenge, $codeChallengeMethod, $state) = $this->verifyOAuthRequestParameters($client);

		$authorizationRequest = $this->service('LiamW\APIImprovements:OAuth\AuthorizationManager', $client)->createAuthorizationRequest($responseType, $redirectUri, $codeChallenge, $codeChallengeMethod, $state);

		$viewParams = [
			'client' => $client,
			'authorizationRequest' => $authorizationRequest
		];

		return $this->view('LiamW\APIImprovements:Account\OAuth2\Grant', 'liamw_apiimprovements_oauth2_client_grant', $viewParams);
	}

	public function actionOAuth2Authorize()
	{
		$this->assertValidCsrfToken();

		$authorizationRequest = $this->assertValidAuthorizationRequestForVisitor();

		/** @var AuthorizationManager $authorizationManager */
		$authorizationManager = $this->service('LiamW\APIImprovements:OAuth\AuthorizationManager', $authorizationRequest->OAuthClient);

		$code = $authorizationManager->createAuthorizationCode($authorizationRequest);

		return $this->redirect($this->repository('LiamW\APIImprovements:OAuth')->buildRedirectUriFromAuthorizationRequest($authorizationRequest, [
			'code' => $code->code
		]));
	}

	public function actionOAuth2Deny()
	{
		$repository = $this->repository('LiamW\APIImprovements:OAuth');

		$clientId = $this->filter('client_id', 'str');
		$client = $repository->findActiveOAuthClient($clientId);
		if (!$client)
		{
			return $this->error("~~Unknown or invalid client.~~", 400);
		}

		$input = $this->filter([
			'redirect_uri' => '?str',
			'response_type' => 'str',
			'state' => '?str'
		]);

		list($responseType, $redirectUri, $state) = $this->verifyOAuthRequestParameters($client, $input);

		$uri = $repository->buildRedirectUri($redirectUri, [
			'error' => 'access_denied',
			'state' => $state
		]);

		return $this->redirect($uri);
	}

	/**
	 * @param null $authzRequestId
	 *
	 * @return \LiamW\APIImprovements\Entity\OAuthAuthorizationRequest|\XF\Mvc\Entity\Entity|null
	 */
	protected function assertValidAuthorizationRequestForVisitor($authzRequestId = null)
	{
		if ($authzRequestId === null)
		{
			$authzRequestId = $this->filter('authorization_request_id', 'str');
		}

		$authzRequest = $this->em()->findOne('LiamW\APIImprovements:OAuthAuthorizationRequest', [
			'authorization_request_id' => $authzRequestId,
			'user_id' => \XF::visitor()->user_id
		]);

		if (!$authzRequest)
		{
			throw $this->exception($this->notFound());
		}

		return $authzRequest;
	}

	protected function verifyOAuthRequestParameters(OAuthClient $client)
	{
		$input = $this->filter([
			'redirect_uri' => '?str',
			'response_type' => 'str',
			'code_challenge' => '?str',
			'code_challenge_method' => '?str',
			'state' => '?str'
		]);

		$redirectUris = $client->redirect_uris;
		if (!$input['redirect_uri'])
		{
			if (count($redirectUris) == 1)
			{
				$redirectUri = reset($redirectUris);
			}
			else
			{
				throw $this->exception($this->error("~~Invalid redirect uri~~", 400));
			}
		}
		else if (count($redirectUris))
		{
			$redirectUri = new Uri($input['redirect_uri']);
			$matchFound = false;
			foreach ($redirectUris AS $registeredUri)
			{
				if ($redirectUri->getAbsoluteUri() === $registeredUri->getAbsoluteUri())
				{
					$matchFound = true;
					break;
				}
			}

			if (!$matchFound)
			{
				throw $this->exception($this->error("~~Invalid redirect uri~~", 400));
			}
		}
		else
		{
			// Client has no redirect_uris defined. This must've been allowed when creating the client, so allow it.
			$redirectUri = new Uri($input['redirect_uri']);
		}

		$responseType = $this->filter('response_type', 'str');
		if (!$responseType || !in_array($responseType, ['code']))
		{
			$uri = $this->repository('LiamW\APIImprovements:OAuth')->buildRedirectUri($redirectUri, $input['state'], [
				'error' => 'unsupported_response_type'
			]);
			throw $this->exception($this->redirect($uri));
		}

		if ($client->type == 'public' && (!$input['code_challenge'] || !$input['code_challenge_method']))
		{
			$uri = $this->repository('LiamW\APIImprovements:OAuth')->buildRedirectUri($redirectUri, $input['state'], [
				'error' => 'invalid_request',
				'error_description' => '~~A code challenge is required~~'
			]);
			throw $this->exception($this->redirect($uri));
		}

		if ($input['code_challenge_method'] && $input['code_challenge_method'] !== 'S256')
		{
			$uri = $this->repository('LiamW\APIImprovements:OAuth')->buildRedirectUri($redirectUri, $input['state'], [
				'error' => 'invalid_request',
				'error_description' => '~~Code challenge method unsupported~~'
			]);
			throw $this->exception($this->redirect($uri));
		}

		return [
			$responseType,
			$redirectUri,
			$input['code_challenge'],
			$input['code_challenge_method'],
			$input['state']
		];
	}
}