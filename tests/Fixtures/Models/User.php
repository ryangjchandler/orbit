<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

// A regular Eloquent model that orbit:cache must skip.
class User extends Model {}
