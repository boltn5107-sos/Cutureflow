@props(['paginator' => null, 'label' => 'éléments'])

@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator || $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
    @if ($paginator->hasPages() || $paginator->total() > 0)
        <div class="cf-pagination">
            <p class="text-xs text-brand-600 dark:text-brand-300">
                @if (method_exists($paginator, 'total'))
                    <span class="font-semibold tabular-nums">{{ number_format($paginator->total(), 0, ',', ' ') }}</span>
                    {{ $label }}
                    @if ($paginator->lastPage() > 1)
                        — page <span class="font-semibold tabular-nums">{{ $paginator->currentPage() }}</span>
                        sur <span class="font-semibold tabular-nums">{{ $paginator->lastPage() }}</span>
                    @endif
                @endif
            </p>

            @if ($paginator->hasPages())
                <div>
                    {{ $paginator->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    @endif
@endif
