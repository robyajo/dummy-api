<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

#[Group('User', description: 'Endpoint untuk pengguna yang sudah login.', weight: 2)]
class PostController extends Controller
{
    /**
     * Display a listing of posts (User).
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $title = $request->query('title');
            $category = $request->query('category');
            $tag = $request->query('tag');
            $status = $request->query('status');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');

            $allowedSortFields = ['id', 'title', 'views', 'published_at', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Post::with(['author', 'category', 'status', 'tag']);

            if ($title) {
                $query->where('title', $title);
            } elseif ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('excerpt', 'LIKE', '%' . $search . '%')
                        ->orWhere('content', 'LIKE', '%' . $search . '%');
                });
            }

            if ($category) {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('slug', $category);
                });
            }

            if ($tag) {
                $query->whereHas('tag', function ($q) use ($tag) {
                    $q->where('slug', $tag);
                });
            }

            if ($status) {
                $query->whereHas('status', function ($q) use ($status) {
                    $q->where('slug', $status);
                });
            }

            $query->orderBy($sortBy, $sortOrder);

            $posts = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedPosts = $posts->map(function ($post) {
                return $this->formatResponse($post);
            });

            $pagination = [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
                'has_more_pages' => $posts->hasMorePages(),
                'has_previous_pages' => $posts->currentPage() > 1,
            ];

            $links = [
                'first' => $posts->url(1),
                'last' => $posts->url($posts->lastPage()),
                'prev' => $posts->previousPageUrl(),
                'next' => $posts->nextPageUrl(),
            ];

            $pageLinks = [];
            for ($i = 1; $i <= $posts->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $posts->url($i),
                    'label' => $i,
                    'active' => $i == $posts->currentPage(),
                ];
            }

            $meta = [
                'sorting' => [
                    'current_sort_by' => $sortBy,
                    'current_sort_order' => $sortOrder,
                    'available_sort_fields' => $allowedSortFields,
                ],
                'filters' => [
                    'title' => $title,
                    'search' => $search,
                    'category' => $category,
                    'tag' => $tag,
                    'status' => $status,
                ],
                'page_links' => $pageLinks,
            ];

            return $this->paginatedResponse(
                $formattedPosts,
                'Posts retrieved successfully',
                $pagination,
                $links,
                $meta
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Store a newly created post (User).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'excerpt' => 'required|string|max:255',
                'content' => 'required|string',
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'category_id' => 'required|exists:categori_posts,id',
                'status_id' => 'required|exists:statuses,id',
                'tag_id' => 'required|exists:tags,id',
                'published_at' => 'nullable|date',
                'meta_description' => 'nullable|string|max:255',
            ]);

            $validated['author_id'] = $request->user()->id;
            $validated['slug'] = str($validated['title'])->slug() . '-' . time();
            $validated['views'] = '0';

            if ($request->hasFile('cover_image')) {
                $imageFile = $request->file('cover_image');
                $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $path = storage_path('app/public/posts');

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $imageFile->move($path, $filename);

                $validated['cover_image'] = 'posts/' . $filename;
            }

            $post = Post::create($validated);

            return $this->successResponse(
                $this->formatResponse($post->load(['author', 'category', 'status', 'tag'])),
                'Post created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Show post by uuid (User).
     */
    public function show(string $uuid)
    {
        try {
            $post = Post::with(['author', 'category', 'status', 'tag'])
                ->where('uuid', $uuid)
                ->first();

            if (! $post) {
                return $this->notFoundResponse('Post not found');
            }

            return $this->successResponse(
                $this->formatResponse($post),
                'Post retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Update the specified post in storage (User).
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $post = Post::where('uuid', $uuid)->first();

            if (! $post) {
                return $this->notFoundResponse('Post not found');
            }

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'excerpt' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'category_id' => 'sometimes|required|exists:categori_posts,id',
                'status_id' => 'sometimes|required|exists:statuses,id',
                'tag_id' => 'sometimes|required|exists:tags,id',
                'published_at' => 'nullable|date',
                'meta_description' => 'nullable|string|max:255',
            ]);

            if (isset($validated['title'])) {
                $validated['slug'] = str($validated['title'])->slug() . '-' . $post->id;
            }

            if ($request->hasFile('cover_image')) {
                if ($post->cover_image && file_exists(storage_path('app/public/' . $post->cover_image))) {
                    @unlink(storage_path('app/public/' . $post->cover_image));
                }

                $imageFile = $request->file('cover_image');
                $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $path = storage_path('app/public/posts');

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $imageFile->move($path, $filename);

                $validated['cover_image'] = 'posts/' . $filename;
            }

            $post->update($validated);

            return $this->successResponse(
                $this->formatResponse($post->fresh()->load(['author', 'category', 'status', 'tag'])),
                'Post updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified post from storage (User).
     */
    public function destroy(string $uuid)
    {
        try {
            $post = Post::where('uuid', $uuid)->first();

            if (! $post) {
                return $this->notFoundResponse('Post not found');
            }

            if ($post->cover_image && file_exists(storage_path('app/public/' . $post->cover_image))) {
                @unlink(storage_path('app/public/' . $post->cover_image));
            }

            $post->delete();

            return $this->successResponse(
                null,
                'Post deleted successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    private function formatResponse(Post $data): array
    {
        return [
            'id' => $data->id,
            'uuid' => $data->uuid,
            'title' => $data->title,
            'slug' => $data->slug,
            'excerpt' => $data->excerpt,
            'content' => $data->content,
            'cover_image' => $data->cover_image,
            'author_id' => $data->author_id,
            'category_id' => $data->category_id,
            'status_id' => $data->status_id,
            'tag_id' => $data->tag_id,
            'published_at' => $data->published_at,
            'views' => $data->views,
            'meta_description' => $data->meta_description,
            'author' => $data->author ? [
                'id' => $data->author->id,
                'uuid' => $data->author->uuid,
                'name' => $data->author->name,
                'email' => $data->author->email,
                'avatar' => $data->author->avatar,
                'avatar_url' => $data->author->avatar_url,
            ] : null,
            'category' => $data->category ? [
                'id' => $data->category->id,
                'uuid' => $data->category->uuid,
                'name' => $data->category->name,
                'slug' => $data->category->slug,
                'description' => $data->category->description,
            ] : null,
            'status' => $data->status ? [
                'id' => $data->status->id,
                'uuid' => $data->status->uuid,
                'name' => $data->status->name,
                'slug' => $data->status->slug,
                'description' => $data->status->description,
                'type' => $data->status->type,
            ] : null,
            'tag' => $data->tag ? [
                'id' => $data->tag->id,
                'uuid' => $data->tag->uuid,
                'name' => $data->tag->name,
                'slug' => $data->tag->slug,
                'description' => $data->tag->description,
            ] : null,
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
            'deleted_at' => $data->deleted_at,
        ];
    }
}
