<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Config\Repository as Config;

class RoleManager extends BaseService
{
  private $roleHierarchy;

  /**
   * @param \Illuminate\Contracts\Config\Repository $config
   */
  public function __construct(Config $config)
  {
    $this->roleHierarchy = $config->get('roles.hierarchy');
    if ($this->roleHierarchy === null) {
      throw new \Exception(
        'Failed to find the role hierarchy in the roles config file.'
      );
    }
  }

  /**
   * Check if a user has the privileges associated with a role id.
   * 
   * @param \App\Models\User $user
   * @param int $roleId
   * @return bool
   */
  public function has(User $user, int $roleId): bool
  {
    if ($user->role_id === $roleId) {
      return(true);
    }

    $idToCheck = $user->role_id;
    while (array_key_exists($idToCheck, $this->roleHierarchy)) {
      $idToCheck = $this->roleHierarchy[$idToCheck];
      if ($idToCheck === $roleId) {
        return(true);
      }
    }

    return(false);
  }

  /**
   * Changes a user's role to the provided role id, regardless of the user's
   * current role.
   * 
   * @param \App\Models\User $user
   * @param int $roleId
   * @return bool
   */
  public function assign(User $user, int $roleId): bool
  {
    if ($user->role_id === $roleId) {
      return(true);
    }

    $user->role_id = $roleId;
    return($user->saveOrFail());
  }

  /**
   * Changes a user's role to the provided role id, only if the user's
   * current role is lower in the hierarchy.
   * 
   * @param \App\Models\User $user
   * @param int $roleId
   * @return bool
   */
  public function promote(User $user, int $roleId): bool
  {
    if (empty($this->roleHierarchy)) {
      return(false);
    }
    if ($this->has($user, $roleId)) {
      return(true);
    }

    return($this->assign($user, $roleId));
  }

  /**
   * Changes a user's role to the provided role id, only if the user's
   * current role is higher in the hierarchy.
   * 
   * @param \App\Models\User $user
   * @param int $roleId
   * @return bool
   */
  public function demote(User $user, int $roleId): bool
  {
    if (empty($this->roleHierarchy)) {
      return(false);
    }
    if (!$this->has($user, $roleId)) {
      return(true);
    }

    return($this->assign($user, $roleId));
  }
}