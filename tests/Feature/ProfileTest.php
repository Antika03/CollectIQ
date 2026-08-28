<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_profile_page(): void
    {
        $user = User::create([
            'name'     => 'Ahmad Reva',
            'email'    => 'ahmad.reva@telkom.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Profil Pengguna');
        $response->assertSee('Ahmad Reva');
        $response->assertSee('ahmad.reva@telkom.co.id');
        $response->assertSee('Ubah Password');
    }

    public function test_user_can_change_password_successfully(): void
    {
        $user = User::create([
            'name'     => 'User AR Lapangan',
            'email'    => 'ar.lapangan@telkom.co.id',
            'password' => Hash::make('oldpassword123'),
            'role'     => 'ar',
        ]);

        $response = $this->actingAs($user)->patch('/profile/password', [
            'current_password'      => 'oldpassword123',
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $user->password));
    }

    public function test_password_change_fails_if_current_password_is_wrong(): void
    {
        $user = User::create([
            'name'     => 'User AR Lapangan',
            'email'    => 'ar.lapangan2@telkom.co.id',
            'password' => Hash::make('correctpassword'),
            'role'     => 'ar',
        ]);

        $response = $this->actingAs($user)->patch('/profile/password', [
            'current_password'      => 'wrongpassword',
            'password'              => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors(['current_password']);

        $user->refresh();
        $this->assertTrue(Hash::check('correctpassword', $user->password));
    }

    public function test_password_change_fails_if_password_confirmation_mismatch(): void
    {
        $user = User::create([
            'name'     => 'User AR Lapangan',
            'email'    => 'ar.lapangan3@telkom.co.id',
            'password' => Hash::make('correctpassword'),
            'role'     => 'ar',
        ]);

        $response = $this->actingAs($user)->patch('/profile/password', [
            'current_password'      => 'correctpassword',
            'password'              => 'newpassword456',
            'password_confirmation' => 'mismatchpassword',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
