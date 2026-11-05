<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    use HasFactory;

    protected $fillable = ['codEmpleado', 'nombreEmpleado', 'apellidosEmpleado'];

    protected $casts = ['codEmpleado' => 'integer'];

    // Estudiantes que aconseja
    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'consejero_id');
    }

    public function grupos()
    {
        return $this->belongsToMany(Grupo::class, 'grupo_docente');
    }
}
