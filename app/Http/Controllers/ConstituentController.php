<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConstituentRequest;
use App\Models\BarangayCaptain;
use App\Models\Constituent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConstituentController extends Controller
{
    /**
     * Number of rows per listing page.
     */
    private const PER_PAGE = 15;

    /**
     * Paginated, searchable listing of constituents.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString() ?: null;

        return view('constituents.index', [
            'search' => $search,
            'constituents' => Constituent::query()
                ->withListingAggregates()
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
        return view('constituents.create', [
            'barangayCaptains' => $this->captainOptions(),
        ]);
    }

    /**
     * Persist a new constituent.
     */
    public function store(ConstituentRequest $request): RedirectResponse
    {
        $constituent = Constituent::create($request->validated());

        return redirect()
            ->route('constituents.show', $constituent)
            ->with('status', 'Constituent added.');
    }

    /**
     * Show one constituent with their tax and criminal records.
     */
    public function show(Constituent $constituent): View
    {
        $constituent->load('barangayCaptain');

        // Loaded explicitly so each collection arrives in the order the page
        // renders it, and so the derived `has_*` accessors read from memory.
        $constituent->setRelation('taxes', $constituent->taxes()->latestPeriodFirst()->get());
        $constituent->setRelation('criminalRecords', $constituent->criminalRecords()->latestFirst()->get());

        return view('constituents.show', [
            'constituent' => $constituent,
        ]);
    }

    /**
     * Show the edit form.
     */
    public function edit(Constituent $constituent): View
    {
        return view('constituents.edit', [
            'constituent' => $constituent,
            'barangayCaptains' => $this->captainOptions(),
        ]);
    }

    /**
     * Persist changes to an existing constituent.
     */
    public function update(ConstituentRequest $request, Constituent $constituent): RedirectResponse
    {
        $constituent->update($request->validated());

        return redirect()
            ->route('constituents.show', $constituent)
            ->with('status', 'Constituent updated.');
    }

    /**
     * Delete a constituent. Their tax and criminal records cascade away through
     * the foreign keys declared in the migrations.
     */
    public function destroy(Constituent $constituent): RedirectResponse
    {
        $constituent->delete();

        return redirect()
            ->route('constituents.index')
            ->with('status', 'Constituent deleted.');
    }

    /**
     * Captains offered by the create/edit form's select.
     *
     * @return Collection<int, BarangayCaptain>
     */
    private function captainOptions(): Collection
    {
        return BarangayCaptain::query()->orderedByName()->get();
    }
}
