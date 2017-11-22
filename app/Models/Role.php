<?php

namespace App\Models;

class Role extends BaseModel
{
	/**
	 * The users that belong to the role.
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
