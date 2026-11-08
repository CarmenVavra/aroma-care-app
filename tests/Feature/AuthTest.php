<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Profile;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/profil')->assertRedirect(route('login'));
    }

    public function test_auth_pages_render_for_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Anmelden');
        $this->get(route('register'))->assertOk()->assertSee('Konto erstellen');
        $this->get(route('password.request'))->assertOk()->assertSee('Passwort vergessen');
        $this->get(route('password.reset', 'token'))->assertOk()->assertSee('Neues Passwort');
    }

    public function test_logged_in_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect('/');
    }

    public function test_user_can_log_in(): void
    {
        $user = User::factory()->create(['email' => 'anna@example.com', 'password' => 'geheim1234']);

        Livewire::test(Login::class)
            ->set('email', 'anna@example.com')
            ->set('password', 'geheim1234')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('stock'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'anna@example.com', 'password' => 'geheim1234']);

        Livewire::test(Login::class)
            ->set('email', 'anna@example.com')
            ->set('password', 'falsch')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSet('password', '');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'anna@example.com', 'password' => 'geheim1234']);

        foreach (range(1, 5) as $attempt) {
            Livewire::test(Login::class)->set('email', 'anna@example.com')->set('password', 'falsch')->call('login');
        }

        Livewire::test(Login::class)
            ->set('email', 'anna@example.com')
            ->set('password', 'geheim1234')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSee('Zu viele Anmeldeversuche');

        $this->assertGuest();
    }

    public function test_user_can_register(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Anna')
            ->set('email', 'Anna@Example.com')
            ->set('password', 'geheim1234')
            ->set('password_confirmation', 'geheim1234')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('stock'));

        $user = User::sole();
        $this->assertSame('anna@example.com', $user->email);
        $this->assertTrue(Hash::check('geheim1234', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->create(['email' => 'anna@example.com']);

        Livewire::test(Register::class)
            ->set('name', '')
            ->set('email', 'anna@example.com')
            ->set('password', 'kurz')
            ->set('password_confirmation', 'anders')
            ->call('register')
            ->assertHasErrors(['name' => 'required', 'email' => 'unique', 'password']);

        $this->assertGuest();
    }

    public function test_reset_link_is_sent_as_queued_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'anna@example.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'anna@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSee('Falls ein Konto');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_unknown_email_gets_the_same_message(): void
    {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'niemand@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSee('Falls ein Konto');

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'anna@example.com']);
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'anna@example.com')
            ->set('password', 'neuesPasswort1')
            ->set('password_confirmation', 'neuesPasswort1')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('neuesPasswort1', $user->fresh()->password));
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'anna@example.com']);

        Livewire::test(ResetPassword::class, ['token' => 'ungueltig'])
            ->set('email', 'anna@example.com')
            ->set('password', 'neuesPasswort1')
            ->set('password_confirmation', 'neuesPasswort1')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertFalse(Hash::check('neuesPasswort1', $user->fresh()->password));
    }

    public function test_profile_page_renders(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Anna']))
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Passwort ändern');
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Profile::class)
            ->set('name', 'Anna Neu')
            ->set('email', 'neu@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertSame('Anna Neu', $user->fresh()->name);
        $this->assertSame('neu@example.com', $user->fresh()->email);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'altesPasswort1']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'altesPasswort1')
            ->set('password', 'neuesPasswort1')
            ->set('password_confirmation', 'neuesPasswort1')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('current_password', '');

        $this->assertTrue(Hash::check('neuesPasswort1', $user->fresh()->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'altesPasswort1']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'falsch')
            ->set('password', 'neuesPasswort1')
            ->set('password_confirmation', 'neuesPasswort1')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('altesPasswort1', $user->fresh()->password));
    }

    public function test_user_can_log_out(): void
    {
        Livewire::actingAs(User::factory()->create())->test(Profile::class)
            ->call('logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_user_can_delete_account_with_all_products(): void
    {
        $user = User::factory()->create(['password' => 'geheim1234']);
        $product = Product::factory()->for($user)->essentialOil()->create();

        Livewire::actingAs($user)->test(Profile::class)
            ->set('delete_password', 'geheim1234')
            ->call('deleteAccount')
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseCount('essential_oil_details', 0);
    }

    public function test_account_deletion_requires_correct_password(): void
    {
        $user = User::factory()->create(['password' => 'geheim1234']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('delete_password', 'falsch')
            ->call('deleteAccount')
            ->assertHasErrors('delete_password');

        $this->assertModelExists($user);
    }
}
