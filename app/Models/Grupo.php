<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Grupo extends Model
{
    use HasFactory;

    public function getFillable(): array
    {
        return ['curso_id', 'numeroCiclo', 'ano', 'numeroGrupo', 'abierto'];
    }

    protected function casts(): array
    {
        return [
            'numeroCiclo' => 'integer',
            'ano' => 'integer',
            'numeroGrupo' => 'integer',
            'abierto' => 'boolean',
        ];
    }

    private function minDocentes(): int
    {
        return 1;
    }

    private function maxDocentes(): int
    {
        return 2;
    }

    private function minEstudiantes(): int
    {
        return 15;
    }

    private function notaMinima(): int
    {
        return 0;
    }

    private function notaMaxima(): int
    {
        return 10;
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function docentes()
    {
        return $this->belongsToMany(Docente::class, 'grupo_docente');
    }

    public function matriculas()
    {
        return $this->hasMany(EstudianteMatriculado::class);
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiantes_matriculados', 'grupo_id', 'estudiante_id')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota')
            ->withTimestamps();
    }

    /** RN-02: máximo 2 docentes por grupo. */
    public function asignarDocente(Docente $docente): void
    {
        if ($this->docentes()->count() >= $this->maxDocentes()) {
            throw new DomainException('Un grupo no puede tener más de ' . $this->maxDocentes() . ' docentes.');
        }

        $this->docentes()->attach($docente->id);
    }

    /** RN-02 (mínimo 1 docente) y RN-05 (mínimo 15 estudiantes). */
    public function puedeAbrirse(): bool
    {
        return $this->docentes()->count() >= $this->minDocentes()
            && $this->estudiantes()->count() >= $this->minEstudiantes();
    }

    public function abrir(): void
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException(
                'El grupo necesita al menos ' . $this->minDocentes() . ' docente y '
                . $this->minEstudiantes() . ' estudiantes matriculados para abrirse.'
            );
        }

        $this->update(['abierto' => true]);
    }

    /** RN-06: la nota va de 0 a 10. */
    public function registrarNota(Estudiante $estudiante, float $nota): void
    {
        if ($nota < $this->notaMinima() || $nota > $this->notaMaxima()) {
            throw new InvalidArgumentException('La nota debe estar entre 0 y 10.');
        }

        EstudianteMatriculado::where('grupo_id', $this->id)
            ->where('estudiante_id', $estudiante->id)
            ->update(['nota' => $nota]);
    }
}
