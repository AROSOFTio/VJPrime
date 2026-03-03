<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function index(): View
    {
        return view('admin.languages.index', [
            'languages' => Language::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.languages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:languages,name'],
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
        ]);

        Language::create($validated);

        return redirect()->route('admin.languages.index')->with('status', 'Language created.');
    }

    public function edit(Language $language): View
    {
        return view('admin.languages.edit', compact('language'));
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', "unique:languages,name,{$language->id}"],
            'code' => ['required', 'string', 'max:10', "unique:languages,code,{$language->id}"],
        ]);

        $language->update($validated);

        return redirect()->route('admin.languages.index')->with('status', 'Language updated.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        $language->delete();

        return redirect()->route('admin.languages.index')->with('status', 'Language deleted.');
    }
}
