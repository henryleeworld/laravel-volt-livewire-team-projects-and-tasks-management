<?php

namespace Tests\Feature;

use App\Livewire\Settings\Profile;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_user_can_update_email_notification_preference_to_disabled()
    {
        $user = User::factory()->create(['email_notifications' => true]);
        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('email_notifications', false)
            ->call('updateProfileInformation');

        $user->refresh();

        $this->assertFalse($user->email_notifications);
    }

    public function test_user_can_update_email_notification_preference_to_enabled()
    {
        $user = User::factory()->create(['email_notifications' => false]);
        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('email_notifications', true)
            ->call('updateProfileInformation');

        $user->refresh();

        $this->assertTrue($user->email_notifications);
    }

    public function test_email_notification_preference_is_loaded_correctly_on_mount()
    {
        $user = User::factory()->create(['email_notifications' => false]);
        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->assertSet('email_notifications', false);

        $user2 = User::factory()->create(['email_notifications' => true]);
        $this->actingAs($user2);

        Livewire::test(Profile::class)
            ->assertSet('email_notifications', true);
    }

    public function test_user_can_update_profile_with_email_notifications_unchanged()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'email_notifications' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('name', 'Updated Name')
            ->set('email', 'updated@example.com')
            ->call('updateProfileInformation');

        $user->refresh();

        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertTrue($user->email_notifications);
    }
}
