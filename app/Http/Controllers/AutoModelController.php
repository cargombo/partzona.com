<?php

namespace App\Http\Controllers;

use App\Models\AutoModel;
use App\Models\AutoModelGroup;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AutoModelController extends Controller
{
    public function index(Request $request)
    {
        $sort_search = null;
        $models = AutoModel::with(['brand', 'modelGroup'])->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $sort_search = $request->search;
            $models = $models->where('name', 'like', '%' . $sort_search . '%');
        }

        if ($request->has('brand_id') && $request->brand_id != null) {
            $models = $models->where('brand_id', $request->brand_id);
        }

        $models = $models->paginate(15);
        $brands = Brand::orderBy('name', 'asc')->get();

        return view('backend.auto_models.index', compact('models', 'brands', 'sort_search'));
    }

    public function create()
    {
        $brands = Brand::orderBy('name', 'asc')->get();
        $modelGroups = AutoModelGroup::orderBy('name', 'asc')->get();
        return view('backend.auto_models.create', compact('brands', 'modelGroups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
        ]);

        $model = new AutoModel();
        $model->brand_id = $request->brand_id;
        $model->auto_model_group_id = $request->auto_model_group_id;
        $model->name = $request->name;
        $model->slug = Str::slug($request->name);
        $model->year_from = $request->year_from;
        $model->year_to = $request->year_to;
        $model->save();

        flash(translate('Auto model has been created successfully'))->success();
        return redirect()->route('admin.auto-models.index');
    }

    public function edit($id)
    {
        $model = AutoModel::findOrFail($id);
        $brands = Brand::orderBy('name', 'asc')->get();
        $modelGroups = AutoModelGroup::orderBy('name', 'asc')->get();
        return view('backend.auto_models.edit', compact('model', 'brands', 'modelGroups'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
        ]);

        $model = AutoModel::findOrFail($id);
        $model->brand_id = $request->brand_id;
        $model->auto_model_group_id = $request->auto_model_group_id;
        $model->name = $request->name;
        $model->slug = Str::slug($request->name);
        $model->year_from = $request->year_from;
        $model->year_to = $request->year_to;
        $model->save();

        flash(translate('Auto model has been updated successfully'))->success();
        return redirect()->route('admin.auto-models.index');
    }

    public function destroy($id)
    {
        $model = AutoModel::findOrFail($id);
        $model->delete();

        flash(translate('Auto model has been deleted successfully'))->success();
        return redirect()->route('admin.auto-models.index');
    }

    public function getModelsByBrand(Request $request)
    {
        $models = AutoModel::where('brand_id', $request->brand_id)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($models);
    }
}
