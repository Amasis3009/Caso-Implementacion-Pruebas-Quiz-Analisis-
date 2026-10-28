<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use PHPUnit\Framework\TestCase;

class GrupoTest extends TestCase
{
    public function test_instancia_de_grupo_contiene_toda_su_informacion(): void
    {
        $curso = new Curso([
            'sigla' => 'IF6100',
            'nombreCurso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 3,
        ]);

        $docente1 = new Docente([
            'codEmpleado' => 1001,
            'nombreEmpleado' => 'Laura',
            'apellidosEmpleado' => 'Mora Rojas',
        ]);
        $docente2 = new Docente([
            'codEmpleado' => 1002,
            'nombreEmpleado' => 'Carlos',
            'apellidosEmpleado' => 'Vargas Solís',
        ]);

        $estudiante = new Estudiante([
            'carne' => 'C00000',
            'nombre' => 'Ana',
            'apellidos' => 'Jiménez Cruz',
        ]);
        $estudiante->setRelation('consejero', $docente1);

        $grupo = new Grupo([
            'numeroCiclo' => 2,
            'ano' => 2026,
            'numeroGrupo' => 1,
            'abierto' => true,
        ]);
        $grupo->setRelation('curso', $curso);
        $grupo->setRelation('docentes', collect([$docente1, $docente2]));
        $grupo->setRelation('estudiantes', collect([$estudiante]));

        // Atributos propios del grupo
        $this->assertSame(2, $grupo->numeroCiclo);
        $this->assertSame(2026, $grupo->ano);
        $this->assertSame(1, $grupo->numeroGrupo);
        $this->assertTrue($grupo->abierto);

        // Curso asociado
        $this->assertSame('IF6100', $grupo->curso->sigla);
        $this->assertSame('Análisis y Diseño de Sistemas', $grupo->curso->nombreCurso);

        // Regla de negocio: 1 o 2 docentes
        $this->assertGreaterThanOrEqual(1, $grupo->docentes->count());
        $this->assertLessThanOrEqual(2, $grupo->docentes->count());
        $this->assertSame('Laura', $grupo->docentes->first()->nombreEmpleado);

        // Estudiantes matriculados y su consejero
        $this->assertCount(1, $grupo->estudiantes);
        $this->assertSame('C00000', $grupo->estudiantes->first()->carne);
        $this->assertSame(1001, $grupo->estudiantes->first()->consejero->codEmpleado);
    }
}