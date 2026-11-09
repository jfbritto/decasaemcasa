<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesInscriptions;
use Tests\TestCase;

/**
 * Duplicar um encontro no painel: a cópia nasce em rascunho, sem vagas
 * ocupadas e com a mesma data do original (a equipe ajusta na edição).
 */
class DuplicateEventTest extends TestCase
{
    use MakesInscriptions;
    use RefreshDatabase;

    public function test_copia_mantem_a_data_do_original_e_nasce_em_rascunho(): void
    {
        $admin = User::create(['name' => 'Equipe Velunora', 'email' => 'equipe@example.com', 'password' => bcrypt('x'), 'role' => 'admin']);
        $original = $this->makeEvent([
            'title' => 'Serenata Velunora',
            'date' => '2026-12-12 19:30:00',
            'confirmed_count' => 7,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.events.duplicate', $original))
            ->assertRedirect();

        $copia = Event::where('id', '!=', $original->id)->sole();
        $this->assertSame('2026-12-12 19:30:00', $copia->date->format('Y-m-d H:i:s'));
        $this->assertSame('Serenata Velunora (Cópia)', $copia->title);
        $this->assertSame('draft', $copia->status);
        $this->assertSame(0, (int) $copia->confirmed_count);
        $this->assertNotSame($original->slug, $copia->slug);
    }
}
