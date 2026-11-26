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
        $perPage = in_array((int)$request->get('per_page'), [10, 25, 50]) ? (int)$request->get('per_page') : 10;
        $query = OrganizacionReceptora::with('responsables');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('rubro', 'like', "%{$search}%")
                  ->orWhere('nit', 'like', "%{$search}%");
            });
        }

        // Orden reciente primero y paginación
        $organizaciones = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
        
        $selectedId = $request->get('selected', $organizaciones->first()?->id);
        $selectedOrg = $organizaciones->where('id', $selectedId)->first() ?? $organizaciones->first();

        return view('organizaciones.index', compact('organizaciones', 'selectedOrg', 'search', 'perPage'));
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
            'nombre'                => 'required|string|max:180|unique:organizaciones_receptoras,nombre',
            'nit'                   => 'nullable|string|max:35|unique:organizaciones_receptoras,nit',
            'rubro'                 => 'nullable|string|max:120',
            'direccion'             => 'nullable|string|max:255',
            'telefono'              => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
            'correo'                => 'nullable|email|max:190',
            'responsable_nombres'   => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'responsable_apellidos' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'responsable_cargo'     => 'nullable|string|max:120',
            'responsable_telefono'  => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
            'responsable_correo'    => 'nullable|email|max:190',
        ], [
            'telefono.regex'             => 'El teléfono de la organización debe ser un número válido de Bolivia / Cochabamba (ej: 4412345 o celular 7XXXXXXX).',
            'responsable_nombres.regex'   => 'Los nombres del responsable solo deben contener letras y espacios, sin números.',
            'responsable_apellidos.regex' => 'Los apellidos del responsable solo deben contener letras y espacios, sin números.',
            'responsable_telefono.regex'  => 'El teléfono del responsable debe ser un número válido de Bolivia / Cochabamba (ej: 7XXXXXXX o fijo 4XXXXXX).',
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
            'nombre'    => 'required|string|max:180|unique:organizaciones_receptoras,nombre,' . $organizacione->id,
            'nit'       => 'nullable|string|max:35|unique:organizaciones_receptoras,nit,' . $organizacione->id,
            'rubro'     => 'nullable|string|max:120',
            'direccion' => 'nullable|string|max:255',
            'telefono'  => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
            'correo'    => 'nullable|email|max:190',
            'estado'    => 'required|in:activa,inactiva',
        ], [
            'telefono.regex' => 'El teléfono debe ser un número válido de Bolivia / Cochabamba (ej: 4412345 o celular 7XXXXXXX).',
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
            'nombres'   => ['required', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'apellidos' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'cargo'     => 'nullable|string|max:120',
            'telefono'  => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
            'correo'    => 'nullable|email|max:190',
        ], [
            'nombres.regex'   => 'Los nombres solo deben contener letras y espacios, no se permiten números.',
            'apellidos.regex' => 'Los apellidos solo deben contener letras y espacios, no se permiten números.',
            'telefono.regex'  => 'El teléfono debe ser un número válido de Bolivia / Cochabamba (ej: 7XXXXXXX o fijo 4XXXXXX).',
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
            'nombres'   => ['required', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'apellidos' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'cargo'     => 'nullable|string|max:120',
            'telefono'  => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
            'correo'    => 'nullable|email|max:190',
            'activo'    => 'required|boolean',
        ], [
            'nombres.regex'   => 'Los nombres solo deben contener letras y espacios, no se permiten números.',
            'apellidos.regex' => 'Los apellidos solo deben contener letras y espacios, no se permiten números.',
            'telefono.regex'  => 'El teléfono debe ser un número válido de Bolivia / Cochabamba (ej: 7XXXXXXX o fijo 4XXXXXX).',
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

