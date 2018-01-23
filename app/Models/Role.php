<?php

namespace App\Models;

class Role extends BaseModel
{
	/**
	 * The users that have this role.
	 * 
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function users()
	{
		return(
			$this->hasMany('App\Models\User', 'role_id', 'id')
		);
	}
}
