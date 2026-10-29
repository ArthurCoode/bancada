<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $members = $request->user()->tenant->users()->orderBy('name')->get();

        return view('team.index', ['members' => $members]);
    }

    public function create(): View
    {
        return view('team.create', ['roles' => $this->assignableRoles()]);
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $member = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        // role não é mass assignable: definimos de forma explícita.
        $member->role = Role::from($data['role']);

        // Salvar pela relação preenche o tenant_id com o da loja do dono.
        $request->user()->tenant->users()->save($member);

        return redirect()->route('team.index')->with('status', 'Membro adicionado à equipe.');
    }

    public function edit(Request $request, int $member): View
    {
        $member = $this->findMember($request, $member, 'update');

        return view('team.edit', [
            'member' => $member,
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function update(UpdateTeamMemberRequest $request, int $member): RedirectResponse
    {
        $member = $this->findMember($request, $member, 'update');
        $data = $request->validated();

        $member->name = $data['name'];
        $member->role = Role::from($data['role']);

        if (! empty($data['password'])) {
            $member->password = $data['password'];
        }

        $member->save();

        return redirect()->route('team.index')->with('status', 'Membro atualizado.');
    }

    public function destroy(Request $request, int $member): RedirectResponse
    {
        $this->findMember($request, $member, 'delete')->delete();

        return redirect()->route('team.index')->with('status', 'Membro removido da equipe.');
    }

    /**
     * Busca o membro SOMENTE dentro da loja do usuário logado (outra loja = 404)
     * e confere a permissão sobre aquele membro (policy = 403).
     */
    private function findMember(Request $request, int $id, string $ability): User
    {
        $member = $request->user()->tenant->users()->findOrFail($id);

        Gate::authorize($ability, $member);

        return $member;
    }

    /** Papéis que o dono pode atribuir: todos, menos o de dono. */
    private function assignableRoles(): array
    {
        return collect(Role::cases())
            ->reject(fn (Role $role) => $role === Role::Owner)
            ->mapWithKeys(fn (Role $role) => [$role->value => $role->label()])
            ->all();
    }
}