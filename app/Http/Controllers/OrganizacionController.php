<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrganizacionReceptora;
use App\Models\ResponsableOrganizacion;

class OrganizacionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('buscar');
        $query = OrganizacionReceptora::with('responsables');

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%")
                  ->orWhere('rubro', 'like', "%{$search}%");
        }

        $organizaciones = $query->orderBy('nombre')->get();
        
        $selectedId = $request->get('selected', $organizaciones->first()?->id);
        $selectedOrg = $organizaciones->where('id', $selectedId)->first() ?? $organizaciones->first();

        return view('organizaciones.index', compact('organizaciones', 'selectedOrg', 'search'));
    }

    public function create()
    {
        if (auth()->user()->isEstudiante() || auth()->user()->isTutor()) {
            abort(403, 'Acción no autorizada. Solo coordinación y administración pueden registrar organizaciones.');
        }
        return view('organizaciones.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->isEstudiante() || auth()->user()->isTutor()) {
            abort(403, 'Acción no autorizada. No tienes permisos para registrar organizaciones.');
        }
        $request->validate([
            'nombre' => 'required|string|max:180|unique:organizaciones_receptoras,nombre',
            'nit' => 'nullable|string|max:35|unique:organizaciones_receptoras,nit',
            'rubro' => 'nullable|string|max:120',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:190',
            'responsable_nombres' => 'nullable|string|max:100',
            'responsable_apellidos' => 'nullable|string|max:120',
            'responsable_cargo' => 'nullable|string|max:120',
            'responsable_telefono' => 'nullable|string|max:30',
            'responsable_correo' => 'nullable|email|max:190',
        ]);

        $org = OrganizacionReceptora::create($request->only([
            'nombre', 'nit', 'rubro', 'direccion', 'telefono', 'correo'
        ]) + ['estado' => 'activa']);

        if ($request->filled('responsable_nombres')) {
            ResponsableOrganizacion::create([
                'organizacion_id' => $org->id,
                'nombres' => $request->responsable_nombres,
                'apellidos' => $request->responsable_apellidos ?? '',
                'cargo' => $request->responsable_cargo,
                'telefono' => $request->responsable_telefono,
                'correo' => $request->responsable_correo,
                'activo' => true,
            ]);
        }

        return redirect()->route('organizaciones.index', ['selected' => $org->id])
            ->with('success', 'Organización receptora registrada correctamente.');
    }

    public function edit(OrganizacionReceptora $organizacione)
    {
        if (auth()->user()->isEstudiante() || auth()->user()->isTutor()) {
            abort(403, 'Acción no autorizada. Solo coordinación y administración pueden editar organizaciones.');
        }
        $organizacione->load('responsables');
        return view('organizaciones.edit', ['organizacion' => $organizacione]);
    }

    public function update(Request $request, OrganizacionReceptora $organizacione)
    {
        if (auth()->user()->isEstudiante() || auth()->user()->isTutor()) {
            abort(403, 'Acción no autorizada. No tienes permisos para modificar organizaciones.');
        }
        $request->validate([
            'nombre' => 'required|string|max:180|unique:organizaciones_receptoras,nombre,' . $organizacione->id,
            'nit' => 'nullable|string|max:35|unique:organizaciones_receptoras,nit,' . $organizacione->id,
            'rubro' => 'nullable|string|max:120',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:190',
            'estado' => 'required|in:activa,inactiva',
        ]);

        $organizacione->update($request->only([
            'nombre', 'nit', 'rubro', 'direccion', 'telefono', 'correo', 'estado'
        ]));

        return redirect()->route('organizaciones.index', ['selected' => $organizacione->id])
            ->with('success', 'Organización actualizada satisfactoriamente.');
    }

    public function storeResponsable(Request $request, OrganizacionReceptora $organizacion)
    {
        if (auth()->user()->isEstudiante() || auth()->user()->isTutor()) {
            abort(403, 'Acción no autorizada. Solo coordinación y administración pueden gestionar contactos.');
        }

        $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:120',
            'cargo' => 'nullable|string|max:120',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:190',
        ]);

        ResponsableOrganizacion::create([
            'organizacion_id' => $organizacion->id,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos ?? '',
            'cargo' => $request->cargo,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
            'activo' => true,
        ]);

        return back()->with('success', 'Contacto institucional agregado exitosamente a la organización.');
    }

    public function updateResponsable(Request $request, ResponsableOrganizacion $responsable)
    {
        if (auth()->user()->isEstudiante() || auth()->user()->isTutor()) {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:120',
            'cargo' => 'nullable|string|max:120',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:190',
            'activo' => 'required|boolean',
        ]);

        $responsable->update([
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos ?? '',
            'cargo' => $request->cargo,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
            'activo' => (bool)$request->activo,
        ]);

        return back()->with('success', 'Datos del contacto institucional actualizados correctamente.');
    }
}

