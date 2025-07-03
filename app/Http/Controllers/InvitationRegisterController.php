<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class InvitationRegisterController extends Controller
{
    protected $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Muestra el formulario de registro simplificado para invitaciones.
     */
    public function showForm(string $token)
    {
        try {
            // Validar el token JWT
            $decoded = $this->invitationService->validateToken($token);
            
            $invitation = Invitation::where('token', $token)->first();

            if (!$invitation) {
                return Inertia::render('Invitations/Invalid', [
                    'error' => 'Invitación no encontrada.'
                ]);
            }

            // Verificar si el usuario ya existe
            $userExists = User::where('email', $invitation->email)->exists();
            if ($userExists) {
                return redirect()->route('login', ['invitation' => $token])
                    ->with('info', 'Ya existe una cuenta con este correo. Por favor inicia sesión.');
            }

            $space = $invitation->space;

            return Inertia::render('Invitations/Register', [
                'invitation' => [
                    'email' => $invitation->email,
                    'token' => $token,
                    'role' => $invitation->role,
                    'role_label' => \App\Enums\SpaceRole::tryFrom($invitation->role)?->label() ?? $invitation->role,
                    'expires_at' => $invitation->expires_at->format('Y-m-d H:i'),
                ],
                'space' => [
                    'name' => $space->name,
                    'owner' => $space->owner ? $space->owner->name : 'Desconocido',
                ],
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Invitations/Invalid', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Procesa el registro simplificado y acepta la invitación.
     */
    public function register(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation || !$invitation->isPending()) {
            return redirect()->route('invitation.show', $token)
                ->with('error', 'Invitación inválida o expirada.');
        }

        // Validar los datos del formulario
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Crear el nuevo usuario
        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
        ]);

        // Aceptar la invitación
        try {
            $result = $this->invitationService->acceptInvitation($token, $user);
            $space = $result['space'];
            
            // Autenticar al usuario
            Auth::login($user);
            
            // Redirigir al dashboard del espacio
            return redirect()->away($this->getSpaceUrl($space))
                ->with('success', "¡Bienvenido a {$space->name}!");
        } catch (\Exception $e) {
            return redirect()->route('invitation.show', $token)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Obtiene la URL para un espacio.
     */
    protected function getSpaceUrl($space)
    {
        $domain = $space->domains()->first();
        
        if ($domain) {
            return 'https://' . $domain->domain;
        }
        
        // Fallback a subdominio si no hay dominio personalizado
        return 'https://' . $space->id . '.' . config('tenancy.central_domains')[0];
    }
}