<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExhibitionRequest;
use App\Models\Product;
use App\Models\Favorite;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'recommend');
        $search = $request->query('search');

        if ($tab === 'mylist') {
            if (!Auth::check()) {
                // ログインしていない場合は空の商品リストを返す
                $products = new LengthAwarePaginator([], 0, 8);
                return view('top', compact('products', 'tab', 'search'));
            }

            $user = Auth::user();
            $query = Product::whereHas('favorites', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        } else {
            $query = Product::query();

            // 自分が出品した商品は除外
            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }
        }

        if (isset($query) && $search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $products = isset($query) ? $query->paginate(8) : new LengthAwarePaginator([], 0, 8);

        return view('top', compact('products', 'tab', 'search'));
    }

    // 商品詳細ページ表示
    public function show($id)
    {
        $product = Product::with(['favorites', 'comments.user', 'categories'])
            ->findOrFail($id);

        return view('item_show', compact('product'));
    }

    // 出品ページ表示
    public function create()
    {
        return view('sell');
    }

    public function store(ExhibitionRequest $request)
    {
        $path = $request->file('image')->store('products', 'public');

        $product = Product::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'brand' => $request->brand,
            'description' => $request->description,
            'price' => $request->price,
            'condition' => $request->condition,
            'image_url' => $path,
        ]);

        $categories = $request->input('categories', []);
        if (!empty($categories)) {
            foreach ($categories as $categoryName) {
                $category = Category::where('name', $categoryName)->first();
                if ($category) {
                    $product->categories()->attach($category->id);
                }
            }
        }
        return redirect()->route('top')->with('success', '商品を出品しました！');
    }
}
