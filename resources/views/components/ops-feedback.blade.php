@if (session('success') || $errors->any())
    <section data-ops-feedback role="status" aria-live="polite" class="mb-6 rounded-xl border p-4 shadow-sm {{ $errors->any() ? 'border-red-200 bg-red-50 text-red-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' }}">
        <div class="flex items-start gap-3">
            <div class="min-w-0 flex-1">
                <p class="font-semibold">{{ $errors->any() ? 'Please review the highlighted issue' : 'Saved successfully' }}</p>
                @if (session('success'))
                    <p class="mt-1 text-sm leading-6">{{ session('success') }}</p>
                @endif
                @if ($errors->any())
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <button type="button" data-ops-feedback-close class="rounded-lg p-1.5 text-current/70 transition hover:bg-black/5 hover:text-current" aria-label="Dismiss notification">&times;</button>
        </div>
    </section>
@endif
