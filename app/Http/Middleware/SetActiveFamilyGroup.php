<?php

namespace App\Http\Middleware;

use App\Models\FamilyGroup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetActiveFamilyGroup
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user    = Auth::user();
        $groupId = session('active_family_group_id');

        // Validate session group belongs to this user
        $group = $groupId
            ? $user->familyGroups()->find($groupId)
            : null;

        // Fallback: default group; if none exists, create one
        if (! $group) {
            $group = $user->defaultFamilyGroup();

            if (! $group) {
                $group = $user->familyGroups()->create([
                    'name'       => 'Gia đình',
                    'color'      => '#c026d3',
                    'is_default' => true,
                ]);
            }

            session(['active_family_group_id' => $group->id]);
        }

        // Share với tất cả views và bind vào container
        app()->instance('active_family_group', $group);
        view()->share('activeFamilyGroup', $group);

        return $next($request);
    }
}
