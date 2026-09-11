<?php

namespace App\Http\Controllers;

use App\Enums\TaxStatus;
use App\Http\Requests\TaxRequest;
use App\Models\Constituent;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Nested resource: tax records always belong to a constituent.
 *
 * Registered with `->shallow()`, so `create`/`store` take the parent
 * constituent while `edit`/`update`/`destroy` bind the tax record directly.
 */
class TaxController extends Controller
{
    /**
     * Show the create form for a constituent's new tax record.
     */
    public function create(Constituent $constituent): View
    {
        return view('taxes.create', [
            'constituent' => $constituent,
            'months' => Tax::monthOptions(),
            'statuses' => TaxStatus::cases(),
        ]);
    }

    /**
     * Persist a new tax record for the constituent.
     */
    public function store(TaxRequest $request, Constituent $constituent): RedirectResponse
    {
        $constituent->taxes()->create($request->validated());

        return $this->backToConstituent($constituent, 'Tax record added.');
    }

    /**
     * Show the edit form.
     */
    public function edit(Tax $tax): View
    {
        $tax->load('constituent');

        return view('taxes.edit', [
            'tax' => $tax,
            'months' => Tax::monthOptions(),
            'statuses' => TaxStatus::cases(),
        ]);
    }

    /**
     * Persist changes to a tax record.
     */
    public function update(TaxRequest $request, Tax $tax): RedirectResponse
    {
        $tax->update($request->validated());

        return $this->backToConstituent($tax->constituent, 'Tax record updated.');
    }

    /**
     * Delete a tax record.
     */
    public function destroy(Tax $tax): RedirectResponse
    {
        $constituent = $tax->constituent;

        $tax->delete();

        return $this->backToConstituent($constituent, 'Tax record deleted.');
    }

    /**
     * Return to the constituent profile with the tax tab active.
     */
    private function backToConstituent(Constituent $constituent, string $status): RedirectResponse
    {
        return redirect()
            ->route('constituents.show', $constituent)
            ->with('status', $status)
            ->with('active_tab', 'taxes');
    }
}
