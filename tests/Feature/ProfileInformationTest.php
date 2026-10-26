<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_profile_information_is_available(): void
    {
        $this->actingAs($user = User::factory()->create());

        $component = Livewire::test(UpdateProfileInformationForm::class);

        $this->assertEquals($user->name, $component->state['name']);
        $this->assertEquals($user->employee_number, $component->state['employee_number']);
        $this->assertEquals($user->apellidos_pat, $component->state['apellidos_pat']);
        $this->assertEquals($user->apellido_mat, $component->state['apellido_mat']);
    }

    public function test_profile_information_can_be_updated_with_a_payroll_with_leading_zeros(): void
    {
        $this->actingAs($user = User::factory()->create(['employee_number' => '0356']));

        Livewire::test(UpdateProfileInformationForm::class)
            ->set('state', ['name' => 'Test Name', 'employee_number' => $user->employee_number])
            ->call('updateProfileInformation');

        $this->assertEquals('Test Name', $user->fresh()->name);
        $this->assertEquals($user->employee_number, $user->fresh()->employee_number);
        $this->assertEquals($user->apellidos_pat, $user->fresh()->apellidos_pat);
    }
}
