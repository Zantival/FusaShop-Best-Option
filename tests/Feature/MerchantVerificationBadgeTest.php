<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MerchantVerificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name'              => 'Usuario Prueba',
            'email'             => 'user_' . uniqid() . '@fusashop.test',
            'password'          => Hash::make('password123'),
            'role'              => 'consumer',
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_analyst_can_give_and_remove_merchant_verification_badge(): void
    {
        $analyst = $this->createUser([
            'name'          => 'Analista Principal',
            'role'          => 'analyst',
            'admin_subrole' => 'super_admin',
        ]);

        $merchant = $this->createUser([
            'name' => 'Café Sumapaz',
            'role' => 'merchant',
        ]);

        CompanyProfile::create([
            'merchant_id'   => $merchant->id,
            'company_name'  => 'Café Sumapaz Especial',
            'phone'         => '3101234567',
            'rut_path'      => 'ruts/test.pdf',
            'business_type' => 'Alimentos',
            'kyc_status'    => 'pending',
        ]);

        // 1. Inicialmente sin verificar: no tiene el chulito en su vitrina pública
        $storeResponseBefore = $this->get(route('consumer.merchant.profile', $merchant->id));
        $storeResponseBefore->assertStatus(200);
        $storeResponseBefore->assertDontSee('Comercio Verificado Oficialmente');

        // 2. El analista le da el chulito de verificación con el botón de 1 clic
        $grantResponse = $this->actingAs($analyst)
            ->from(route('analyst.users'))
            ->patch(route('analyst.users.toggle-verification', $merchant->id));

        $grantResponse->assertRedirect(route('analyst.users'));
        $this->assertDatabaseHas('company_profiles', [
            'merchant_id' => $merchant->id,
            'kyc_status'  => 'approved',
        ]);

        // Verificar que ahora sí aparece el chulito en la vitrina pública y en la lista de usuarios del analista
        $storeResponseAfterGrant = $this->get(route('consumer.merchant.profile', $merchant->id));
        $storeResponseAfterGrant->assertStatus(200);
        $storeResponseAfterGrant->assertSee('Comercio Verificado Oficialmente');
        $storeResponseAfterGrant->assertSee('Verificada');

        $usersPageVerified = $this->actingAs($analyst)->get(route('analyst.users', ['role' => 'merchant']));
        $usersPageVerified->assertStatus(200);
        $usersPageVerified->assertSee('Quitar Verificación');

        // 3. El analista le quita el chulito de verificación con el mismo botón de 1 clic
        $revokeResponse = $this->actingAs($analyst)
            ->from(route('analyst.users'))
            ->patch(route('analyst.users.toggle-verification', $merchant->id));

        $revokeResponse->assertRedirect(route('analyst.users'));
        $this->assertDatabaseHas('company_profiles', [
            'merchant_id' => $merchant->id,
            'kyc_status'  => 'pending',
        ]);

        // Verificar que el chulito desaparece de la vitrina pública y el botón vuelve a decir "Dar Verificación ✓"
        $storeResponseAfterRevoke = $this->get(route('consumer.merchant.profile', $merchant->id));
        $storeResponseAfterRevoke->assertStatus(200);
        $storeResponseAfterRevoke->assertDontSee('Comercio Verificado Oficialmente');

        $usersPageUnverified = $this->actingAs($analyst)->get(route('analyst.users', ['role' => 'merchant']));
        $usersPageUnverified->assertStatus(200);
        $usersPageUnverified->assertSee('Dar Verificación ✓');
    }

    public function test_analyst_can_give_verification_even_if_merchant_had_no_company_profile_yet(): void
    {
        $analyst = $this->createUser([
            'name'          => 'Analista Principal',
            'role'          => 'analyst',
            'admin_subrole' => 'super_admin',
        ]);

        $merchantWithoutProfile = $this->createUser([
            'name' => 'Artesanías del Sumapaz',
            'role' => 'merchant',
        ]);

        // Dar verificación crea automáticamente el CompanyProfile y le otorga el chulito
        $response = $this->actingAs($analyst)
            ->patch(route('analyst.users.toggle-verification', $merchantWithoutProfile->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('company_profiles', [
            'merchant_id' => $merchantWithoutProfile->id,
            'kyc_status'  => 'approved',
        ]);

        // Y también puede quitarle la verificación desde el formulario KYC de edición de usuario
        $revokeKycResponse = $this->actingAs($analyst)
            ->post(route('analyst.users.kyc', $merchantWithoutProfile->id), [
                'kyc_status' => 'pending',
                'kyc_notes'  => 'Revisión periódica de documentación',
            ]);

        $revokeKycResponse->assertRedirect();
        $this->assertDatabaseHas('company_profiles', [
            'merchant_id' => $merchantWithoutProfile->id,
            'kyc_status'  => 'pending',
        ]);
    }
}
