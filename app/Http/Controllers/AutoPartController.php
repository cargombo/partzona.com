<?php

namespace App\Http\Controllers;

use App\Models\AutoPart;
use App\Models\AutoPartTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AutoPartController extends Controller
{
    public function index(Request $request)
    {
        $sort_search = null;
        $parts = AutoPart::orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $sort_search = $request->search;
            $parts = $parts->whereHas('translations', function ($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%');
            });
        }

        $parts = $parts->paginate(15);

        return view('backend.auto_parts.index', compact('parts', 'sort_search'));
    }

    public function create()
    {
        return view('backend.auto_parts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_az' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_ru' => 'required|string|max:255',
            'name_zh' => 'required|string|max:255',
        ]);

        $part = new AutoPart();
        $part->slug = Str::slug($request->name_en);
        $part->save();

        // Save translations
        $languages = ['az', 'en', 'ru', 'zh'];
        foreach ($languages as $lang) {
            $translation = new AutoPartTranslation();
            $translation->part_id = $part->id;
            $translation->lang = $lang;
            $translation->name = $request->input('name_' . $lang);
            $translation->description = $request->input('description_' . $lang);
            $translation->search_keywords = $request->input('search_keywords_' . $lang);
            $translation->save();
        }

        flash(translate('Auto part has been created successfully'))->success();
        return redirect()->route('admin.auto-parts.index');
    }

    public function edit($id)
    {
        $part = AutoPart::with('translations')->findOrFail($id);
        return view('backend.auto_parts.edit', compact('part'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name_az' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_ru' => 'required|string|max:255',
            'name_zh' => 'required|string|max:255',
        ]);

        $part = AutoPart::findOrFail($id);
        $part->slug = Str::slug($request->name_en);
        $part->save();

        // Update translations
        $languages = ['az', 'en', 'ru', 'zh'];
        foreach ($languages as $lang) {
            $translation = AutoPartTranslation::where('part_id', $part->id)
                ->where('lang', $lang)
                ->first();

            if (!$translation) {
                $translation = new AutoPartTranslation();
                $translation->part_id = $part->id;
                $translation->lang = $lang;
            }

            $translation->name = $request->input('name_' . $lang);
            $translation->description = $request->input('description_' . $lang);
            $translation->search_keywords = $request->input('search_keywords_' . $lang);
            $translation->save();
        }

        flash(translate('Auto part has been updated successfully'))->success();
        return redirect()->route('admin.auto-parts.index');
    }

    public function destroy($id)
    {
        $part = AutoPart::findOrFail($id);
        AutoPartTranslation::where('part_id', $id)->delete();
        $part->delete();

        flash(translate('Auto part has been deleted successfully'))->success();
        return redirect()->route('admin.auto-parts.index');
    }
}
