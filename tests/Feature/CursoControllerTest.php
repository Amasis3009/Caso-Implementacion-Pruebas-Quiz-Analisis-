<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursoControllerTest extends TestCase
{
    use RefreshDatabase;

    private function datosCurso(array $extra = []): array
    {
        return array_merge([
            'sigla' => 'IF6100',
            'nombreCurso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 3,
        ], $extra);
    }

    public function test_store_crea_un_curso_y_lo_guarda_en_la_base_de_datos(): void
    {
        $respuesta = $this->postJson('/cursos', $this->datosCurso());

        $respuesta->assertCreated()
            ->assertJsonFragment(['sigla' => 'IF6100']);

        $this->assertDatabaseHas('cursos', [
            'sigla' => 'IF6100',
            'nombreCurso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 3,
        ]);

        $curso = Curso::where('sigla', 'IF6100')->first();
        $respuesta->assertHeader('Location', url("/cursos/{$curso->id}"));
    }

    public function test_store_valida_los_datos_requeridos(): void
    {
        $this->postJson('/cursos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sigla', 'nombreCurso', 'creditos']);

        $this->assertDatabaseCount('cursos', 0);
    }

    public function test_store_rechaza_sigla_duplicada(): void
    {
        Curso::create($this->datosCurso());

        $this->postJson('/cursos', $this->datosCurso())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sigla']);

        $this->assertDatabaseCount('cursos', 1);
    }

    public function test_la_url_generada_devuelve_el_curso_guardado(): void
    {
        $url = $this->postJson('/cursos', $this->datosCurso())
            ->assertCreated()
            ->headers->get('Location');

        $this->getJson($url)
            ->assertOk()
            ->assertJsonFragment(['sigla' => 'IF6100']);
    }

    public function test_show_incluye_los_grupos_del_curso(): void
    {
        $curso = Curso::create($this->datosCurso());

        Grupo::create([
            'curso_id' => $curso->id,
            'numeroCiclo' => 2,
            'ano' => 2026,
            'numeroGrupo' => 1,
            'abierto' => true,
        ]);

        $this->getJson("/cursos/{$curso->id}")
            ->assertOk()
            ->assertJsonCount(1, 'grupos')
            ->assertJsonPath('grupos.0.numeroGrupo', 1);
    }
}
