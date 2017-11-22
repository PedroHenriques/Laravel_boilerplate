<?php

namespace App\Utils;

use Illuminate\Support\Str;

class SecurityUtils extends BaseUtils
{
  /**
	 * Generates a new token.
	 * 
	 * @param string $key
	 * @return string
	 */
	public function createToken(string $key = ''): string {
		if (Str::startsWith($key, 'base64:')) {
			$key = base64_decode(substr($key, 7));
		}

		return(hash_hmac('sha256', Str::random(40), $key));
	}
}