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

use LiamW\APIImprovements\Entity\OAuthClient;
use XF\Api\Mvc\RouteMatch;
use XF\Api\Mvc\Router;
use XF\Container;
use XF\Http\Request;

class Listener
{
	public static function appApiSetup(\XF\Api\App $app)
	{
		$container = $app->container();

		$container->extend('router', function ($output, Container $c)
		{
			/** @var $output Router */
			$output->addRoutePreProcessor('oauthPrefix', function (\XF\Mvc\Router $router, $path, RouteMatch $match, Request $request = null)
			{
				if (preg_match('#^api/oauth2(?:/|$)(.*)$#i', $path, $matches))
				{
					$path = $matches[1];
					$match->setPathRewrite($path);
				}
				return $match;
			}, true);

			return $output;
		});
	}

	public static function appApiValidateRequest(\XF\Http\Request $request, &$result, &$error, &$code)
	{
		if (self::requestUrlMatchesOAuthApi($request))
		{
			$authorizationHeader = $request->getServer('HTTP_AUTHORIZATION');

			if ($authorizationHeader)
			{
				$parts = explode(' ', $authorizationHeader, 2);

				if (count($parts) != 2)
				{
					$parts[1] = '';
				}

				list($scheme, $authorization) = $parts;

				switch ($scheme)
				{
					case 'Basic':
						list($clientId, $clientSecret) = explode(':', base64_decode($authorization));

						if ($clientId && $clientSecret)
						{
							/** @var OAuthClient $client */
							$client = \XF::repository('LiamW\APIImprovements:OAuth')->findActiveOAuthClient($clientId, $clientSecret);

							if ($client)
							{
								$request->set('client_id', $client->client_id);
								$result = \XF::repository('XF:User')->getGuestUser();

								return;
							}
						}
						break;
					case 'Bearer':
						$token = $authorization;

						$token = \XF::repository('LiamW\APIImprovements:OAuth')->findOAuthToken($token);
						if ($token && !$token->hasExpired())
						{
							$result = $token->OAuthCode->User;

							return;
						}
						break;
				}

				$result = false;
				$error = 'invalid_client_credentials'; // This is a bit crap, but we don't have a choice.
				$code = 401;
			}
			else
			{
				$result = false;
				$error = 'missing_client_credentials'; // This is a bit crap, but we don't have a choice.
				$code = 401;
			}
		}
	}

	protected static function requestUrlMatchesOAuthApi(\XF\Http\Request $request)
	{
		return boolval(preg_match('#^api/oauth2(?:/|$)#i', $request->getRoutePath()));
	}
}