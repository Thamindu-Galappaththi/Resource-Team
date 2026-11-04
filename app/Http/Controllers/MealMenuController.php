<?php

namespace App\Http\Controllers;

use App\Models\MealMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MealMenuController extends Controller
{
    public function index(): View
    {
        $menus = MealMenu::query()->orderByDesc('service_date')->orderByDesc('id')->get();

        return view('canteen.menus.index', [
            'menus' => $menus,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'service_date' => ['required', 'date'],
            'main_dish' => ['required', 'string', 'max:120'],
            'dish_one' => ['required', 'string', 'max:120'],
            'dish_two' => ['required', 'string', 'max:120'],
            'dish_three' => ['required', 'string', 'max:120'],
            'dish_four' => ['required', 'string', 'max:120'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $validated['image_path'] = $request->file('image')->store('meal-menus', 'public');
        unset($validated['image']);
        MealMenu::create($validated);

        return redirect()->route('canteen.menus.index')->with('success', 'Meal menu created successfully.');
    }

    public function edit(MealMenu $menu): View
    {
        return view('canteen.menus.edit', compact('menu'));
    }

    public function update(Request $request, MealMenu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'service_date' => ['required', 'date'],
            'main_dish' => ['required', 'string', 'max:120'],
            'dish_one' => ['required', 'string', 'max:120'],
            'dish_two' => ['required', 'string', 'max:120'],
            'dish_three' => ['required', 'string', 'max:120'],
            'dish_four' => ['required', 'string', 'max:120'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('meal-menus', 'public');
            if ($menu->image_path) {
                Storage::disk('public')->delete($menu->image_path);
            }
            $validated['image_path'] = $newImagePath;
        }

        unset($validated['image']);
        $menu->update($validated);

        return redirect()->route('canteen.menus.index')->with('success', 'Meal menu updated successfully.');
    }

    public function destroy(MealMenu $menu): RedirectResponse
    {
        if ($menu->image_path) {
            Storage::disk('public')->delete($menu->image_path);
        }

        $menu->delete();

        return redirect()->route('canteen.menus.index')->with('success', 'Meal menu removed successfully.');
    }
}
