<?php
/**
 * Copyright (c) 2019 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Columba package.
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Columba\Foundation\Http;

/**
 * Class UserAgent
 *
 * @author Bas Milius <bas@mili.us>
 * @package Columba\Foundation\Http
 * @since 1.6.0
 */
class UserAgent
{

	/**
	 * @var string
	 */
	protected $userAgent;

	/**
	 * @var string|null
	 */
	protected $browser = null;

	/**
	 * @var string|null
	 */
	protected $platform = null;

	/**
	 * @var string|null
	 */
	protected $version = null;

	/**
	 * UserAgent constructor.
	 *
	 * @param string $userAgent
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public function __construct(string $userAgent)
	{
		$this->userAgent = $userAgent;
		$this->parse();
	}

	/**
	 * Parses the user agent.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	protected function parse(): void
	{
		$platform = $version = null;

		if (preg_match('/\((.*?)\)/im', $this->userAgent, $parentMatches))
		{
			preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|Tizen|iPhone|iPad|iPod|Linux|(Open|Net|Free)BSD|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(New\ )?Nintendo\ (WiiU?|3?DS|Switch)|Xbox(\ One)?)(?:\ [^;]*)?(?:;|$)/imx', $parentMatches[1], $result, PREG_PATTERN_ORDER);

			$priority = ['Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11'];
			$result['platform'] = array_unique($result['platform']);

			if (count($result['platform']) > 1)
			{
				if ($keys = array_intersect($priority, $result['platform']))
					$platform = reset($keys);
				else
					$platform = $result['platform'][0];
			}
			else if (isset($result['platform'][0]))
			{
				$platform = $result['platform'][0];
			}
		}

		if ($platform == 'linux-gnu' || $platform == 'X11')
			$platform = 'Linux';
		else if ($platform == 'CrOS')
			$platform = 'Chrome OS';

		preg_match_all('%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|TizenBrowser|Chrome|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|Edg|Edge|CriOS|UCBrowser|Puffin|SamsungBrowser|Baiduspider|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|Valve\ Steam\ Tenfoot|NintendoBrowser|PLAYSTATION\ (\d|Vita)+)(?:\)?;?)(?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix', $this->userAgent, $result, PREG_PATTERN_ORDER);

		if (!isset($result['browser'][0]) || !isset($result['version'][0]))
		{
			if (preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $this->userAgent, $result))
			{
				$this->browser = $result['browser'];
				$this->platform = $platform;
				$this->version = $result['version'];
			}
			return;
		}

		if (preg_match('/rv:(?P<version>[0-9A-Z.]+)/si', $this->userAgent, $versionResult))
			$versionResult = $versionResult['version'];

		$browser = $result['browser'][0];
		$version = $result['version'][0];

		$lowerBrowser = array_map('strtolower', $result['browser']);

		$find = static function ($search, int &$key, &$value = null) use ($lowerBrowser): bool
		{
			if (!is_array($search))
				$search = (array)$search;

			foreach ($search as $val)
			{
				$xkey = array_search(strtolower($val), $lowerBrowser);

				if ($xkey !== false)
				{
					$value = $val;
					$key = $xkey;

					return true;
				}
			}

			return false;
		};

		$key = 0;
		$val = '';

		if ($browser == 'Iceweasel' || strtolower($browser) == 'icecat')
		{
			$browser = 'Firefox';
		}
		else if ($find('Playstation Vita', $key))
		{
			$platform = 'PlayStation Vita';
			$browser = 'Browser';
		}
		else if ($find(['Kindle Fire', 'Silk'], $key, $val))
		{
			$browser = $val == 'Silk' ? 'Silk' : 'Kindle';
			$platform = 'Kindle Fire';

			if (!($version = $result['version'][$key]) || !is_numeric($version[0]))
				$version = $result['version'][array_search('Version', $result['browser'])];
		}
		else if ($find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS')
		{
			$browser = 'NintendoBrowser';
			$version = $result['version'][$key];
		}
		else if ($find('Kindle', $key, $platform))
		{
			$browser = $result['browser'][$key];
			$version = $result['version'][$key];
		}
		else if ($find('OPR', $key))
		{
			$browser = 'Opera Next';
			$version = $result['version'][$key];
		}
		else if ($find('Opera', $key, $browser))
		{
			$find('Version', $key);
			$version = $result['version'][$key];
		}
		else if ($find('Puffin', $key, $browser))
		{
			$version = $result['version'][$key];

			if (strlen($version) > 3)
			{
				$part = substr($version, -2);

				if (ctype_upper($part))
				{
					$version = substr($version, 0, -2);
					$flags = ['IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android', 'AT' => 'Android', 'WP' => 'Windows Phone', 'WT' => 'Windows'];

					if (isset($flags[$part]))
						$platform = $flags[$part];
				}
			}
		}
		else if ($find(['IEMobile', 'Edg', 'Edge', 'Midori', 'Vivaldi', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome'], $key, $browser))
		{
			$version = $result['version'][$key];
		}
		else if ($versionResult && $find('Trident', $key))
		{
			$browser = 'MSIE';
			$version = $versionResult;
		}
		else if ($find('UCBrowser', $key))
		{
			$browser = 'UC Browser';
			$version = $result['version'][$key];
		}
		else if ($find('CriOS', $key))
		{
			$browser = 'Chrome';
			$version = $result['version'][$key];
		}
		else if ($browser == 'AppleWebKit')
		{
			if ($platform == 'Android' && !($key = 0))
			{
				$browser = 'Android Browser';
			}
			else if (strpos($platform, 'BB') === 0)
			{
				$browser = 'BlackBerry Browser';
				$platform = 'BlackBerry';
			}
			else if ($platform == 'BlackBerry' || $platform == 'PlayBook')
			{
				$browser = 'BlackBerry Browser';
			}
			else
			{
				$find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser);
			}

			$find('Version', $key);
			$version = $result['version'][$key];
		}
		else if ($pKey = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser'])))
		{
			$pKey = reset($pKey);
			$platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $pKey);
			$browser = 'NetFront';
		}

		$this->browser = $browser;
		$this->platform = $platform;
		$this->version = $version;
	}

	/**
	 * Returns TRUE if the browser is Chrome.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function isChrome(): bool
	{
		return $this->browser === 'Chrome';
	}

	/**
	 * Returns TRUE if the browser is Chromium Edge.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function isEdgium(): bool
	{
		return $this->browser === 'Edg';
	}

	/**
	 * Returns TRUE if the browser is Firefox.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function isFirefox(): bool
	{
		return $this->browser === 'Firefox';
	}

	/**
	 * Returns TRUE if the browser is Internet Explorer.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function isInternetExplorer(): bool
	{
		return $this->browser === 'MSIE';
	}

	/**
	 * Returns TRUE if the browser is Microsoft Edge.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function isMicrosoftEdge(): bool
	{
		return $this->browser === 'Edg' || $this->browser === 'Edge';
	}

	/**
	 * Returns TRUE if the browser is Safari.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function isSafari(): bool
	{
		return $this->browser === 'Safari';
	}

	/**
	 * Checks if the browser version is at least {@see $version}.
	 *
	 * @param string $version
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function versionAtLeast(string $version): bool
	{
		return version_compare($this->version ?? '0.0.0', $version, '>=');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.6.0
	 */
	public final function __toString(): string
	{
		return $this->userAgent;
	}

}