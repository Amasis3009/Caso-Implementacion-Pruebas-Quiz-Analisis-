<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ---------- Datos de prueba del enunciado ----------

function crearCursoIF6100(): Curso
{
    return Curso::create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);
}

function crearGrupo01(Curso $curso, bool $abierto = false): Grupo
{
    return Grupo::create([
        'curso_id' => $curso->id,
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => $abierto,
    ]);
}

function crearDocente(int $codEmpleado): Docente
{
    [$nombre, $apellidos] = [
        1001 => ['Ana', 'Mora Vargas'],
        1002 => ['Luis', 'Rojas Solano'],
        1003 => ['Carla', 'Jiménez Araya'],
    ][$codEmpleado];

    return Docente::create([
        'codEmpleado' => $codEmpleado,
        'nombreEmpleado' => $nombre,
        'apellidosEmpleado' => $apellidos,
    ]);
}

function matricularEstudiantes(Grupo $grupo, Docente $consejero, int $cantidad): void
{
    $estudiantes = Estudiante::factory()
        ->count($cantidad)
        ->for($consejero, 'consejero')
        ->create();

    foreach ($estudiantes as $estudiante) {
        $grupo->matricular($estudiante);
    }
}

// ---------- Casos de prueba ----------

test('CP-01: registra un grupo con toda su información y su curso', function () {
    $curso = crearCursoIF6100();

    $creado = Grupo::create([
        'curso_id' => $curso->id,
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => true,
    ]);

    $grupo = Grupo::find($creado->id);

    expect($grupo->numeroCiclo)->toBe(2)
        ->and($grupo->ano)->toBe(2026)
        ->and($grupo->numeroGrupo)->toBe(1)
        ->and($grupo->abierto)->toBeBool()->toBeTrue()
        ->and($grupo->curso->sigla)->toBe('IF6100')
        ->and($grupo->curso->creditos)->toBe(4);

    expect(Grupo::count())->toBe(1);
});

test('CP-02: un curso tiene varios grupos y se distinguen los abiertos', function () {
    $curso = crearCursoIF6100();

    foreach ([[1, true], [2, true], [3, false]] as [$numero, $abierto]) {
        Grupo::create([
            'curso_id' => $curso->id,
            'numeroCiclo' => 2,
            'ano' => 2026,
            'numeroGrupo' => $numero,
            'abierto' => $abierto,
        ]);
    }

    $grupos = $curso->grupos;
    $abiertos = $grupos->where('abierto', true);

    expect($grupos)->toHaveCount(3)
        ->and($abiertos)->toHaveCount(2)
        ->and($abiertos->pluck('numeroGrupo')->sort()->values()->all())->toBe([1, 2])
        ->and($grupos->pluck('curso_id')->unique()->values()->all())->toBe([$curso->id]);
});

test('CP-03: un grupo acepta dos docentes', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $docente1001 = crearDocente(1001);
    crearDocente(1002);

    $grupo->asignarDocente(1001);
    $grupo->asignarDocente(1002);

    expect($grupo->docentes)->toHaveCount(2)
        ->and($grupo->docentes->pluck('codEmpleado')->sort()->values()->all())->toBe([1001, 1002])
        ->and($docente1001->grupos->pluck('id')->all())->toContain($grupo->id);

    $this->assertDatabaseCount('grupo_docente', 2);
});

test('CP-04: un grupo rechaza un tercer docente', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    crearDocente(1001);
    crearDocente(1002);
    $docente1003 = crearDocente(1003);

    $grupo->asignarDocente(1001);
    $grupo->asignarDocente(1002);

    expect(fn() => $grupo->asignarDocente(1003))->toThrow(DomainException::class);

    expect($grupo->docentes()->count())->toBe(2);
    $this->assertDatabaseMissing('grupo_docente', ['docente_id' => $docente1003->id]);
});

test('CP-05: un grupo con 14 estudiantes no puede abrirse', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $docente1001 = crearDocente(1001);
    $grupo->asignarDocente($docente1001);
    matricularEstudiantes($grupo, $docente1001, 14);

    expect($grupo->puedeAbrirse())->toBeFalse();
    expect(fn() => $grupo->abrir())->toThrow(DomainException::class);

    expect($grupo->fresh()->abierto)->toBeFalse();
});

test('CP-06: un grupo con 15 estudiantes y un docente se abre', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $docente1001 = crearDocente(1001);
    $grupo->asignarDocente($docente1001);
    matricularEstudiantes($grupo, $docente1001, 15);

    expect($grupo->puedeAbrirse())->toBeTrue();
    $grupo->abrir();

    expect($grupo->fresh()->abierto)->toBeTrue();
});