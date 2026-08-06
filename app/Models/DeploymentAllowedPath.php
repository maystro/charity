<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A path the deployment process is allowed to touch, managed from the
 * super-admin UI. When the table is empty the system falls back to the
 * defaults in config/deployment.php.
 */
class DeploymentAllowedPath extends Model
{
    protected $fillable = ['path'];
}
