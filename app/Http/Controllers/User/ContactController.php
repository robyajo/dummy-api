<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;

#[Group('User', description: 'Endpoint untuk pengguna yang sudah login.', weight: 2)]
class ContactController extends Controller
{
    /**
     * Display a listing of the contacts (User).
     */
    public function index(Request $request)
    {
        try {
            // Get query parameters
            $search = $request->query('search');
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            // Ordering parameters
            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');

            // Validate sort parameters
            $allowedSortFields = ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array(strtolower($sortOrder), $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            // Build query
            $query = Contact::query()->with('user');

            // Search by name, email, phone
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('email', 'LIKE', '%' . $search . '%')
                        ->orWhere('phone', 'LIKE', '%' . $search . '%');
                });
            }

            // Apply ordering
            $query->orderBy($sortBy, $sortOrder);

            // Paginate
            $contacts = $query->paginate($perPage, ['*'], 'page', $page);

            // Build pagination data
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

            // Build links for pagination
            $links = [
                'first' => $contacts->url(1),
                'last' => $contacts->url($contacts->lastPage()),
                'prev' => $contacts->previousPageUrl(),
                'next' => $contacts->nextPageUrl(),
            ];

            // Build page links
            $pageLinks = [];
            for ($i = 1; $i <= $contacts->lastPage(); $i++) {
                $pageLinks[] = [
                    'url' => $contacts->url($i),
                    'label' => $i,
                    'active' => $i == $contacts->currentPage(),
                ];
            }

            // Meta data
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
     * Store a newly created contact (User).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:contacts,email',
                'phone' => 'required|string|max:50',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            // Set the authenticated user as the contact creator
            $validated['user_id'] = $request->user()->id;

            if ($request->hasFile('avatar')) {
                $imageFile = $request->file('avatar');
                $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $path = storage_path('app/public/contacts');

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $image = Image::read($imageFile);
                $image->save($path . '/' . $filename);

                $validated['avatar'] = 'contacts/' . $filename;
            } else {
                // Determine a default avatar if none provided
                $validated['avatar'] = 'assets/template/sample-book.png'; // Adjust generic path as needed
            }

            $contact = Contact::create($validated);

            return $this->successResponse(
                $contact,
                'Contact created successfully',
                201
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Show contact by uuid (User).
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

    /**
     * Update the specified contact in storage (User).
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $contact = Contact::where('uuid', $uuid)->first();

            if (! $contact) {
                return $this->notFoundResponse('Contact not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:255|unique:contacts,email,' . $contact->id,
                'phone' => 'sometimes|required|string|max:50',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            if ($request->hasFile('avatar')) {
                // Delete old image
                if ($contact->avatar && ! str_starts_with($contact->avatar, 'assets/') && file_exists(storage_path('app/public/' . $contact->avatar))) {
                    @unlink(storage_path('app/public/' . $contact->avatar));
                }

                $imageFile = $request->file('avatar');
                $filename = time() . '_' . uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $path = storage_path('app/public/contacts');

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                $image = Image::read($imageFile);
                $image->save($path . '/' . $filename);

                $validated['avatar'] = 'contacts/' . $filename;
            }

            $contact->update($validated);

            return $this->successResponse(
                $contact->fresh(),
                'Contact updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified contact from storage (User).
     */
    public function destroy(string $uuid)
    {
        try {
            $contact = Contact::where('uuid', $uuid)->first();

            if (! $contact) {
                return $this->notFoundResponse('Contact not found');
            }

            if ($contact->avatar && ! str_starts_with($contact->avatar, 'assets/') && file_exists(storage_path('app/public/' . $contact->avatar))) {
                @unlink(storage_path('app/public/' . $contact->avatar));
            }

            $contact->delete();

            return $this->successResponse(
                null,
                'Contact deleted successfully'
            );
        } catch (\Throwable $th) {
            return $this->errorResponse('Server error: ' . $th->getMessage(), 500);
        }
    }
}
