<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Database\QueryException;

mutates(Grupo::class);

// ---------- Datos de prueba del enunciado ----------

function crearCursoIF6100(): Curso
{
    return Curso::create([
        'sigla' => 'IF6100',
        'nombreCurso' => 'Análisis y Diseño de Sistemas',
        'creditos' => 4,
    ]);
}

function crearGrupo01(Curso $curso): Grupo
{
    return Grupo::create([
        'curso_id' => $curso->id,
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => false,
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

function crearEstudianteC12345(Docente $consejero): Estudiante
{
    return Estudiante::create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $consejero->id,
    ]);
}

function matricularConFactory(Grupo $grupo, int $cantidad): void
{
    $grupo->estudiantes()->attach(Estudiante::factory()->count($cantidad)->create());
}

test('CP-01 registra un grupo con toda su información y su curso', function () {
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

test('CP-02 un curso tiene varios grupos y se distinguen los abiertos', function () {
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

test('CP-03 un grupo acepta dos docentes', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $docente1001 = crearDocente(1001);
    $docente1002 = crearDocente(1002);

    $grupo->asignarDocente($docente1001);
    $grupo->asignarDocente($docente1002);

    expect($grupo->docentes)->toHaveCount(2)
        ->and($grupo->docentes->pluck('codEmpleado')->sort()->values()->all())->toBe([1001, 1002])
        ->and($docente1001->grupos->pluck('id')->all())->toContain($grupo->id);

    $this->assertDatabaseCount('grupo_docente', 2);
});

test('CP-04 rechaza un tercer docente', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $grupo->asignarDocente(crearDocente(1001));
    $grupo->asignarDocente(crearDocente(1002));
    $docente1003 = crearDocente(1003);

    expect(fn() => $grupo->asignarDocente($docente1003))->toThrow(DomainException::class);

    expect($grupo->docentes()->count())->toBe(2);
    $this->assertDatabaseMissing('grupo_docente', ['docente_id' => $docente1003->id]);
});

test('CP-05 un grupo con 14 estudiantes no puede abrirse', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $grupo->asignarDocente(crearDocente(1001));
    matricularConFactory($grupo, 14);

    expect($grupo->puedeAbrirse())->toBeFalse();
    expect(fn() => $grupo->abrir())->toThrow(DomainException::class);

    expect($grupo->fresh()->abierto)->toBeFalse();
});

test('CP-06 un grupo con 15 estudiantes y un docente se abre', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $grupo->asignarDocente(crearDocente(1001));
    matricularConFactory($grupo, 15);

    expect($grupo->puedeAbrirse())->toBeTrue();
    $grupo->abrir();

    expect($grupo->fresh()->abierto)->toBeTrue();
});

test('CP-07 un grupo sin docente no se abre aunque tenga cupo completo', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    matricularConFactory($grupo, 20);

    expect($grupo->docentes()->count())->toBe(0);
    expect($grupo->estudiantes()->count())->toBe(20);

    expect($grupo->puedeAbrirse())->toBeFalse();
    expect(fn() => $grupo->abrir())->toThrow(DomainException::class);

    expect($grupo->fresh()->abierto)->toBeFalse();
});

test('CP-08 un estudiante queda ligado a su profesor consejero', function () {
    $docente = crearDocente(1001);
    expect($docente->estudiantes()->count())->toBe(0);

    $estudiante = crearEstudianteC12345($docente);

    expect($estudiante->consejero->codEmpleado)->toBe(1001);
    expect($docente->estudiantes)->toHaveCount(1)
        ->and($docente->estudiantes->first()->carne)->toBe('C12345');

    $this->assertDatabaseHas('estudiantes', [
        'carne' => 'C12345',
        'consejero_id' => $docente->id,
    ]);
});

test('CP-09 cambiar de consejero reemplaza al anterior, no lo suma', function () {
    $docente1001 = crearDocente(1001);
    $docente1002 = crearDocente(1002);
    $estudiante = crearEstudianteC12345($docente1001);

    $estudiante->consejero()->associate($docente1002);
    $estudiante->save();

    $docente1001->refresh();
    $docente1002->refresh();

    expect($estudiante->fresh()->consejero->codEmpleado)->toBe(1002)
        ->and($docente1001->estudiantes)->toHaveCount(0)
        ->and($docente1002->estudiantes)->toHaveCount(1);

    expect(Estudiante::where('carne', 'C12345')->count())->toBe(1);
    $this->assertDatabaseHas('estudiantes', [
        'carne' => 'C12345',
        'consejero_id' => $docente1002->id,
    ]);
});

test('CP-10 al matricular, la nota queda vacía', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $estudiante = crearEstudianteC12345(crearDocente(1001));

    $grupo->estudiantes()->attach($estudiante);

    $pivote = $grupo->estudiantes->first()->pivot;

    expect($pivote)->toBeInstanceOf(EstudianteMatriculado::class)
        ->and($pivote->nota)->toBeNull();

    $this->assertDatabaseCount('estudiantes_matriculados', 1);
    $this->assertDatabaseHas('estudiantes_matriculados', [
        'grupo_id' => $grupo->id,
        'estudiante_id' => $estudiante->id,
    ]);
});

test('CP-11 registra la nota al finalizar el curso', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $estudiante = crearEstudianteC12345(crearDocente(1001));
    $otro = Estudiante::factory()->create();
    $grupo->estudiantes()->attach([$estudiante->id, $otro->id]);

    $grupo->registrarNota($estudiante, 8.5);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBeFloat()->toBe(8.5)
        ->and($matricula->grupo_id)->toEqual($grupo->id)
        ->and($matricula->estudiante_id)->toEqual($estudiante->id);

    // Solo cambió la nota de C12345
    $matriculaOtro = EstudianteMatriculado::where('estudiante_id', $otro->id)->first();
    expect($matriculaOtro->nota)->toBeNull();
    $this->assertDatabaseCount('estudiantes_matriculados', 2);
});

test('CP-12 rechaza notas menores que 0 o mayores que 10', function (float $nota) {
    $grupo = crearGrupo01(crearCursoIF6100());
    $estudiante = crearEstudianteC12345(crearDocente(1001));
    $grupo->estudiantes()->attach($estudiante);

    expect(fn() => $grupo->registrarNota($estudiante, $nota))
        ->toThrow(InvalidArgumentException::class);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();
    expect($matricula->nota)->toBeNull();
})->with([-1, 10.5]);

test('CP-13 un estudiante no se matricula dos veces en el mismo grupo', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $estudiante = crearEstudianteC12345(crearDocente(1001));
    $grupo->estudiantes()->attach($estudiante);

    expect(fn() => $grupo->estudiantes()->attach($estudiante))
        ->toThrow(QueryException::class);

    expect(
        EstudianteMatriculado::where('grupo_id', $grupo->id)
            ->where('estudiante_id', $estudiante->id)
            ->count()
    )->toBe(1);
});

test('C3-01 el tercer docente se rechaza con el mensaje de la regla', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $grupo->asignarDocente(crearDocente(1001));
    $grupo->asignarDocente(crearDocente(1002));

    expect(fn () => $grupo->asignarDocente(crearDocente(1003)))
        ->toThrow(DomainException::class, 'Un grupo no puede tener más de 2 docentes.');
});

test('C3-02 matriculas() lista las matrículas del grupo', function () {
    $grupo = crearGrupo01(crearCursoIF6100());
    $estudiante = crearEstudianteC12345(crearDocente(1001));
    $grupo->estudiantes()->attach($estudiante);

    expect($grupo->matriculas)->toHaveCount(1)
        ->and($grupo->matriculas->first())->toBeInstanceOf(EstudianteMatriculado::class)
        ->and($grupo->matriculas->first()->estudiante_id)->toEqual($estudiante->id);
});

test('C3-03 los casts convierten los atributos al tipo correcto', function () {
    $grupo = new Grupo([
        'numeroCiclo' => '2',
        'ano' => '2026',
        'numeroGrupo' => '1',
        'abierto' => 1,
    ]);

    expect($grupo->numeroCiclo)->toBeInt()->toBe(2)
        ->and($grupo->ano)->toBeInt()->toBe(2026)
        ->and($grupo->numeroGrupo)->toBeInt()->toBe(1)
        ->and($grupo->abierto)->toBeBool()->toBeTrue();
});

test('C3-04 los cinco atributos del grupo se pueden asignar en masa', function () {
    $grupo = new Grupo([
        'curso_id' => 7,
        'numeroCiclo' => 2,
        'ano' => 2026,
        'numeroGrupo' => 1,
        'abierto' => false,
    ]);

    expect($grupo->getAttributes())->toHaveKeys(['curso_id', 'numeroCiclo', 'ano', 'numeroGrupo', 'abierto'])
        ->and($grupo->getFillable())->toHaveCount(5);
});

test('C3-05 las notas límite 0 y 10 se aceptan', function (float $nota) {
    $grupo = crearGrupo01(crearCursoIF6100());
    $estudiante = crearEstudianteC12345(crearDocente(1001));
    $grupo->estudiantes()->attach($estudiante);

    $grupo->registrarNota($estudiante, $nota);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula->nota)->toBeFloat()->toBe($nota);
})->with([0, 10]);