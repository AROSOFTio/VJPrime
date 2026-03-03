<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vj;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VjController extends Controller
{
    public function index(): View
    {
        return view('admin.vjs.index', [
            'vjs' => Vj::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.vjs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:vjs,slug'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        Vj::create($validated);

        return redirect()->route('admin.vjs.index')->with('status', 'VJ created.');
    }

    public function edit(Vj $vj): View
    {
        return view('admin.vjs.edit', compact('vj'));
    }

    public function update(Request $request, Vj $vj): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', "unique:vjs,slug,{$vj->id}"],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        $vj->update($validated);

        return redirect()->route('admin.vjs.index')->with('status', 'VJ updated.');
    }

    public function destroy(Vj $vj): RedirectResponse
    {
        $vj->delete();

        return redirect()->route('admin.vjs.index')->with('status', 'VJ deleted.');
    }
}
