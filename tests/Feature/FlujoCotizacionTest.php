<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Smoke test del flujo principal: proyecto → cotización → los 3 PDF → descarga.
 */
class FlujoCotizacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_flujo_completo_de_cotizacion(): void
    {
        Storage::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->actingAs($user)->post('/proyectos', [
            'nombre' => 'Piscina Villa Real',
            'cliente' => 'Cliente de prueba',
            'municipio' => 'Girardot',
            'departamento' => 'Cundinamarca',
        ])->assertRedirect();

        $proyecto = Proyecto::firstOrFail();
        $this->actingAs($user)->get(route('proyectos.show', $proyecto))->assertOk();

        $this->actingAs($user)->post(route('cotizaciones.store', $proyecto))->assertRedirect();
        $cotizacion = Cotizacion::firstOrFail();
        $this->actingAs($user)->get(route('cotizaciones.edit', $cotizacion))->assertOk();

        foreach (['especificacion', 'especificacion_mano_obra', 'especificacion_materiales'] as $tipo) {
            $this->actingAs($user)
                ->post(route('documentos.generar', [$cotizacion, $tipo]))
                ->assertRedirect();
        }

        $this->assertSame(3, $cotizacion->documentos()->count());

        $this->actingAs($user)
            ->get(route('documentos.descargar', $cotizacion->documentos()->first()))
            ->assertOk()
            ->assertDownload();
    }

    public function test_otro_usuario_no_puede_ver_el_proyecto(): void
    {
        $dueno = User::factory()->create();
        $otro = User::factory()->create();
        $proyecto = Proyecto::create(['nombre' => 'Privado', 'user_id' => $dueno->id]);

        $this->actingAs($otro)->get(route('proyectos.show', $proyecto))->assertForbidden();
    }
}
