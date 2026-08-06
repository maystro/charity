@props([
    'headers' => [],
    'rows' => [],
    'striped' => true,
    'hover' => true,
    'compact' => false,
    'responsive' => true,
])

<div class="overflow-hidden rounded-[var(--radius-lg)] border border-[var(--color-border)] bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-sm {{ $attributes->get('class', '') }}">
            <thead>
                <tr class="bg-[var(--color-bg-secondary)]/50">
                    @foreach($headers as $header)
                        <th scope="col" class="px-4 py-3 text-start text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider {{ $compact ? 'px-3 py-2' : '' }}">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--color-border)]">
                @forelse($rows as $index => $row)
                    <tr class="{{ $striped && $index % 2 === 1 ? 'bg-[var(--color-bg-secondary)]/30' : 'bg-white' }} {{ $hover ? 'hover:bg-[var(--color-bg-secondary)]/60 transition-colors' : '' }}">
                        @foreach($row as $cell)
                            <td class="px-4 py-3 text-[var(--color-text-primary)] {{ $compact ? 'px-3 py-2' : '' }}">
                                {{ $cell }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) }}" class="px-4 py-12 text-center text-[var(--color-text-muted)]">
                            {{ $empty ?? __('لا توجد بيانات') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(isset($tfoot))
                <tfoot>
                    {{ $tfoot }}
                </tfoot>
            @endif
        </table>
    </div>
</div>
