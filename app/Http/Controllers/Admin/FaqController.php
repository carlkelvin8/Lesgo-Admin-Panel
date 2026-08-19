<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqArticle;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FaqController extends Controller
{
    public function categoryIndex()
    {
        $categories = FaqCategory::withCount('articles')->latest()->paginate(20);

        return view('admin.faq.categories', compact('categories'));
    }

    public function categoryCreate()
    {
        return view('admin.faq.category-create');
    }

    public function categoryStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = $this->uniqueSlug(FaqCategory::class, $validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        FaqCategory::create($validated);

        return redirect()->route('admin.faq.categories')
            ->with('success', 'Category created successfully.');
    }

    public function categoryEdit(FaqCategory $category)
    {
        return view('admin.faq.category-create', compact('category'));
    }

    public function categoryUpdate(Request $request, FaqCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = $this->uniqueSlug(FaqCategory::class, $validated['name'], $category->id);
        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()->route('admin.faq.categories')
            ->with('success', 'Category updated successfully.');
    }

    public function categoryDestroy(FaqCategory $category)
    {
        $category->delete();

        return redirect()->route('admin.faq.categories')
            ->with('success', 'Category deleted successfully.');
    }

    public function articleIndex(Request $request)
    {
        $query = FaqArticle::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_published')) {
            $query->where('is_published', $request->is_published === '1');
        }

        $articles = $query->latest()->paginate(20)->withQueryString();
        $categories = FaqCategory::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.faq.articles', compact('articles', 'categories'));
    }

    public function articleCreate()
    {
        $categories = FaqCategory::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.faq.article-create', compact('categories'));
    }

    public function articleStore(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:faq_categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'tags' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = $this->uniqueSlug(FaqArticle::class, $validated['title']);
        $validated['created_by'] = auth()->id();
        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_featured'] = $request->boolean('is_featured');

        if (! empty($validated['tags']) && is_string($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        FaqArticle::create($validated);

        return redirect()->route('admin.faq.articles')
            ->with('success', 'Article created successfully.');
    }

    public function articleShow(FaqArticle $article)
    {
        $article->load('category', 'creator', 'updater');

        return view('admin.faq.article-show', compact('article'));
    }

    public function articleEdit(FaqArticle $article)
    {
        $categories = FaqCategory::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.faq.article-edit', compact('article', 'categories'));
    }

    public function articleUpdate(Request $request, FaqArticle $article)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:faq_categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'tags' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = $this->uniqueSlug(FaqArticle::class, $validated['title'], $article->id);
        $validated['updated_by'] = auth()->id();
        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_featured'] = $request->boolean('is_featured');

        if (! empty($validated['tags']) && is_string($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        $article->update($validated);

        return redirect()->route('admin.faq.articles.show', $article)
            ->with('success', 'Article updated successfully.');
    }

    public function articleDestroy(FaqArticle $article)
    {
        $article->delete();

        return redirect()->route('admin.faq.articles')
            ->with('success', 'Article deleted successfully.');
    }

    private function uniqueSlug(string $model, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'article';
        $slug = $base;
        $suffix = 2;

        while ($model::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
