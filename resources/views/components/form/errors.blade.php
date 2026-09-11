@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-red-200 bg-red-50 px-4 py-3']) }} role="alert">
        <p class="text-sm font-semibold text-red-900">Please correct the following:</p>

        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
