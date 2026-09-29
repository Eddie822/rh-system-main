@props(['request'])
<div {{ $attributes }}>
    @if ($request->expires_at)
        <span class="block text-sm">{{ $request->expires_at->format('d/m/Y H:i') }}</span>
        @if ($request->expirationAlert() === 'overdue')
            <span role="status" class="inline-block px-2 py-1 mt-1 text-xs font-semibold text-red-800 bg-red-100 rounded">Vencida · aún puede aprobarse</span>
        @elseif ($request->expirationAlert() === 'soon')
            <span role="status" class="inline-block px-2 py-1 mt-1 text-xs font-semibold text-amber-800 bg-amber-100 rounded">Por vencer · próximos {{ config('requests.expiration_notice_hours') / 24 }} días</span>
        @endif
    @else
        <span class="text-sm text-gray-500">Sin fecha de horas extra</span>
    @endif
</div>
