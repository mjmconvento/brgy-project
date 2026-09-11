<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarangayCaptainRequest;
use App\Models\BarangayCaptain;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BarangayCaptainController extends Controller
{
    /**
     * Number of rows per listing page.
     */
    private const PER_PAGE = 15;

    /**
     * Paginated, searchable listing of captain candidates.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString() ?: null;

        return view('barangay-captains.index', [
            'search' => $search,
            'barangayCaptains' => BarangayCaptain::query()
                ->withCount('constituents')
                ->search($search)
                ->orderedByName()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        return view('barangay-captains.create');
    }

    /**
     * Persist a new captain candidate.
     */
    public function store(BarangayCaptainRequest $request): RedirectResponse
    {
        $captain = BarangayCaptain::create($request->validated());

        return redirect()
            ->route('barangay-captains.show', $captain)
            ->with('status', 'Barangay captain added.');
    }

    /**
     * Show one captain and a paginated list of the constituents who voted for
     * them. A popular captain can have hundreds of voters, so the roster is
     * paged with its own page name — that keeps it independent of any other
     * paginator that might appear on the page.
     */
    public function show(BarangayCaptain $barangayCaptain): View
    {
        return view('barangay-captains.show', [
            'barangayCaptain' => $barangayCaptain,
            'constituents' => $barangayCaptain->constituents()
                ->orderedByName()
                ->paginate(self::PER_PAGE, pageName: 'constituents_page')
                ->withQueryString(),
        ]);
    }

    /**
     * Show the edit form.
     */
    public function edit(BarangayCaptain $barangayCaptain): View
    {
        return view('barangay-captains.edit', [
            'barangayCaptain' => $barangayCaptain,
        ]);
    }

    /**
     * Persist changes to an existing captain candidate.
     */
    public function update(BarangayCaptainRequest $request, BarangayCaptain $barangayCaptain): RedirectResponse
    {
        $barangayCaptain->update($request->validated());

        return redirect()
            ->route('barangay-captains.show', $barangayCaptain)
            ->with('status', 'Barangay captain updated.');
    }

    /**
     * Delete a captain candidate. Constituents who voted for them keep their
     * profile and simply lose the reference (`nullOnDelete`).
     */
    public function destroy(BarangayCaptain $barangayCaptain): RedirectResponse
    {
        $barangayCaptain->delete();

        return redirect()
            ->route('barangay-captains.index')
            ->with('status', 'Barangay captain deleted.');
    }
}
