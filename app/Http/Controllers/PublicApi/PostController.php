<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of posts (Public).
     *
     * @unauthenticated
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $title = $request->query('title');
            $category = $request->query('category');
            $tag = $request->query('tag');
            $author = $request->query('author');
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
                    $q->where('title', 'LIKE', '%'.$search.'%')
                        ->orWhere('excerpt', 'LIKE', '%'.$search.'%')
                        ->orWhere('content', 'LIKE', '%'.$search.'%');
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

            if ($author) {
                $query->whereHas('author', function ($q) use ($author) {
                    $q->where('name', 'LIKE', '%'.$author.'%');
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
                'total_pages' => $posts->lastPage(),
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
                    'author' => $author,
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
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
        }
    }

    /**
     * Show post by slug (Public).
     *
     * @unauthenticated
     */
    public function show(string $slug)
    {
        try {
            $post = Post::with(['author', 'category', 'status', 'tag'])
                ->where('slug', $slug)
                ->first();

            if (! $post) {
                return $this->notFoundResponse('Post not found');
            }

            return $this->successResponse(
                $this->formatResponse($post),
                'Post retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: '.$th->getMessage(), 500);
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
