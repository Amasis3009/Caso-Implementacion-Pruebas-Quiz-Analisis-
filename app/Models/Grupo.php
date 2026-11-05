<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;

    public const MIN_DOCENTES = 1;
    public const MAX_DOCENTES = 2;
    public const MIN_ESTUDIANTES = 15;

    protected $fillable = ['curso_id', 'numeroCiclo', 'ano', 'numeroGrupo', 'abierto'];

    protected $casts = [
        'numeroCiclo' => 'integer',
        'ano' => 'integer',
        'numeroGrupo' => 'integer',
        'abierto' => 'boolean',
    ];

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
            ->withPivot('nota')
            ->withTimestamps();
    }

    /**
     * RN-02: un grupo tiene como máximo 2 docentes.
     * Acepta un Docente o su codEmpleado.
     */
    public function asignarDocente(Docente|int $docente): void
    {
        if (! $docente instanceof Docente) {
            $docente = Docente::where('codEmpleado', $docente)->firstOrFail();
        }

        if ($this->docentes()->where('docentes.id', $docente->id)->exists()) {
            return; // ya estaba asignado
        }

        if ($this->docentes()->count() >= self::MAX_DOCENTES) {
            throw new DomainException('Un grupo no puede tener más de ' . self::MAX_DOCENTES . ' docentes.');
        }

        $this->docentes()->attach($docente->id);
    }

    public function matricular(Estudiante $estudiante): void
    {
        $this->estudiantes()->attach($estudiante->id);
    }

    /**
     * RN-02 (mínimo 1 docente) y RN-05 (mínimo 15 estudiantes).
     */
    public function puedeAbrirse(): bool
    {
        return $this->docentes()->count() >= self::MIN_DOCENTES
            && $this->estudiantes()->count() >= self::MIN_ESTUDIANTES;
    }

    public function abrir(): void
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException(
                'El grupo necesita al menos ' . self::MIN_DOCENTES . ' docente y '
                . self::MIN_ESTUDIANTES . ' estudiantes matriculados para abrirse.'
            );
        }

        $this->update(['abierto' => true]);
    }
}
