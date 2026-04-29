<?php

namespace App\Http\Controllers;

use App\Models\MasterAdditionalField;
use App\Models\MasterSocialMedia;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterDataController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/MasterData', [
            'socialMedias' => MasterSocialMedia::all(),
            'additionalFields' => MasterAdditionalField::all(),
        ]);
    }

    public function storeSocialMedia(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:20',
            'icon_url' => 'nullable|string|max:2048',
        ]);

        MasterSocialMedia::create($validated);

        return back()->with('success', 'Platform sosial media berhasil ditambah.');
    }

    public function destroySocialMedia(MasterSocialMedia $socialMedia)
    {
        $socialMedia->delete();

        return back()->with('success', 'Platform sosial media berhasil dihapus.');
    }

    public function storeAdditionalField(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon_key' => 'nullable|string|max:255',
            'input_type' => 'required|in:text,textarea,date,select',
            'options' => 'nullable|array',
        ]);

        MasterAdditionalField::create($validated);

        return back()->with('success', 'Bidang data tambahan berhasil ditambah.');
    }

    public function destroyAdditionalField(MasterAdditionalField $additionalField)
    {
        $additionalField->delete();

        return back()->with('success', 'Bidang data tambahan berhasil dihapus.');
    }
}
