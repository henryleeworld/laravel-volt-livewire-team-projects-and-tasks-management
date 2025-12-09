<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->hasPermissionTo('users.viewAny'), 403);

        $users = User::query()
            ->where('organization_id', $currentUser->organization_id)
            ->where('id', '!=', $currentUser->id)
            ->orderBy('name')
            ->get();

        $invitations = Invitation::query()
            ->where('organization_id', $currentUser->organization_id)
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get();

        return view('users.index', [
            'users' => $users,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->hasPermissionTo('users.create'), 403);

        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $invitation = Invitation::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => Str::uuid()->toString(),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new UserInvitationNotification($invitation));

        return redirect()
            ->route('users.index')
            ->with('success', __('Invitation sent successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->hasPermissionTo('users.delete'), 403);
        abort_if($user->id === $currentUser->id, 403);
        abort_unless($user->organization_id === $currentUser->organization_id, 403);

        $user->forceDelete();

        return redirect()
            ->route('users.index')
            ->with('success', __('User removed successfully.'));
    }
}
