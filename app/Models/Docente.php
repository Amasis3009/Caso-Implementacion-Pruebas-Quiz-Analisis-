<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $fillable = ['codEmpleado', 'nombreEmpleado', 'apellidosEmpleado'];

    // Estudiantes a los que aconseja
    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class);
    }

    public function grupos()
    {
        return $this->belongsToMany(Grupo::class, 'docente_grupo');
    }
}
