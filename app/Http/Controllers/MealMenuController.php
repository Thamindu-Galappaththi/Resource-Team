<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMealMenuRequest;
use App\Http\Requests\UpdateMealMenuRequest;
use App\Models\MealMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MealMenuController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->hasPermission('canteen.manage'), 403);

        $menus = MealMenu::query()->orderBy('name')->paginate(12);

        return view('canteen.menus.index', compact('menus'));
    }

    public function store(StoreMealMenuRequest $request): JsonResponse|RedirectResponse
    {
        $menu = MealMenu::query()->create([
            'name' => $request->validated('name'),
            'image_path' => $request->file('image')->store('meal-menus', 'public'),
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Meal menu published.',
                'count' => MealMenu::query()->count(),
                'html' => view('canteen.menus.partials.tile', compact('menu'))->render(),
            ]);
        }

        return redirect()->route('canteen.menus.index')->with('success', 'Meal menu published.');
    }

    public function edit(MealMenu $menu): View
    {
        abort_unless(auth()->user()?->hasPermission('canteen.manage'), 403);

        return view('canteen.menus.edit', compact('menu'));
    }

    public function update(UpdateMealMenuRequest $request, MealMenu $menu): RedirectResponse
    {
        $data = ['name' => $request->validated('name')];
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($menu->image_path);
            $data['image_path'] = $request->file('image')->store('meal-menus', 'public');
        }
        $menu->update($data);

        return redirect()->route('canteen.menus.index')->with('success', 'Meal menu updated.');
    }

    public function destroy(MealMenu $menu): RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('canteen.manage'), 403);
        Storage::disk('public')->delete($menu->image_path);
        $menu->delete();

        return redirect()->route('canteen.menus.index')->with('success', 'Meal menu removed.');
    }
}
