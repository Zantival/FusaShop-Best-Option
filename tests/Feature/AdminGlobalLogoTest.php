<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGlobalLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_analyst_can_upload_the_global_logo(): void
    {
        Storage::fake('public');
        $analyst = User::create([
            'name' => 'FusaShop Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/j5sAAAAASUVORK5CYII=');

        $this->actingAs($analyst)
            ->post(route('analyst.global.logo.store'), [
                'logo' => UploadedFile::fake()->createWithContent('fusashop.png', $png),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Logo del sistema actualizado.');

        Storage::disk('public')->assertExists('system/global_logo.png');
        $this->assertStringContainsString(
            route('files.serve', ['disk' => 'system', 'path' => 'global_logo.png']),
            view('components.logo')->render()
        );
    }
}
