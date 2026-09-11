<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriminalRecordRequest;
use App\Models\Constituent;
use App\Models\CriminalRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Nested resource: criminal records always belong to a constituent.
 *
 * Registered with `->shallow()`, so `create`/`store` take the parent
 * constituent while `edit`/`update`/`destroy` bind the record directly.
 */
class CriminalRecordController extends Controller
{
    /**
     * Show the create form for a constituent's new criminal record.
     */
    public function create(Constituent $constituent): View
    {
        return view('criminal-records.create', [
            'constituent' => $constituent,
        ]);
    }

    /**
     * Persist a new criminal record for the constituent.
     */
    public function store(CriminalRecordRequest $request, Constituent $constituent): RedirectResponse
    {
        $constituent->criminalRecords()->create($request->validated());

        return $this->backToConstituent($constituent, 'Criminal record added.');
    }

    /**
     * Show the edit form.
     */
    public function edit(CriminalRecord $criminalRecord): View
    {
        $criminalRecord->load('constituent');

        return view('criminal-records.edit', [
            'criminalRecord' => $criminalRecord,
        ]);
    }

    /**
     * Persist changes to a criminal record.
     */
    public function update(CriminalRecordRequest $request, CriminalRecord $criminalRecord): RedirectResponse
    {
        $criminalRecord->update($request->validated());

        return $this->backToConstituent($criminalRecord->constituent, 'Criminal record updated.');
    }

    /**
     * Delete a criminal record.
     */
    public function destroy(CriminalRecord $criminalRecord): RedirectResponse
    {
        $constituent = $criminalRecord->constituent;

        $criminalRecord->delete();

        return $this->backToConstituent($constituent, 'Criminal record deleted.');
    }

    /**
     * Return to the constituent profile with the criminal record tab active.
     */
    private function backToConstituent(Constituent $constituent, string $status): RedirectResponse
    {
        return redirect()
            ->route('constituents.show', $constituent)
            ->with('status', $status)
            ->with('active_tab', 'criminal_records');
    }
}
