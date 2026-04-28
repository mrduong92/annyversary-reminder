<?php

use App\Models\FamilyGroup;

if (! function_exists('active_group')) {
    function active_group(): FamilyGroup
    {
        return app('active_family_group');
    }
}
