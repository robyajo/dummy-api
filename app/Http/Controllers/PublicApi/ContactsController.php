<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Public', description: 'Endpoint publik yang dapat diakses tanpa autentikasi.', weight: 1)]
class ContactsController extends Controller
{
    /**
     * Display a listing of the contacts (Public).
     *
     * @unauthenticated
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');

            $allowedSortFields = ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Contact::query()->with('user');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('email', 'LIKE', '%' . $search . '%')
                        ->orWhere('phone', 'LIKE', '%' . $search . '%');
                });
            }

            $query->orderBy($sortBy, $sortOrder);

            $contacts = $query->paginate($perPage, ['*'], 'page', $page);

            $pagination = [
                'current_page' => $contacts->currentPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
                'last_page' => $contacts->lastPage(),
                'from' => $contacts->firstItem(),
                'to' => $contacts->lastItem(),
                'has_more_pages' => $contacts->hasMorePages(),
                'has_previous_pages' => $contacts->currentPage() > 1,
            ];

            $links = [
                'first' => $contacts->url(1),
                'last' => $contacts->url($contacts->lastPage()),
                'prev' => $contacts->previousPageUrl(),
                'next' => $contacts->nextPageUrl(),
            ];

            $pageLinks = [];
            for ($i = 1; $i <= $contacts->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $contacts->url($i),
                    'label' => $i,
                    'active' => $i == $contacts->currentPage(),
                ];
            }

            $meta = [
                'sorting' => [
                    'current_sort_by' => $sortBy,
                    'current_sort_order' => $sortOrder,
                    'available_sort_fields' => $allowedSortFields,
                ],
                'filters' => [
                    'search' => $search,
                ],
                'page_links' => $pageLinks,
            ];

            return $this->paginatedResponse(
                $contacts->items(),
                'Contacts retrieved successfully',
                $pagination,
                $links,
                $meta
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Show contact by uuid (Public).
     *
     * @unauthenticated
     */
    public function show(string $uuid)
    {
        try {
            $contact = Contact::with('user')->where('uuid', $uuid)->first();

            if (! $contact) {
                return $this->notFoundResponse('Contact not found');
            }

            return $this->successResponse(
                $contact,
                'Contact retrieved successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }
}
