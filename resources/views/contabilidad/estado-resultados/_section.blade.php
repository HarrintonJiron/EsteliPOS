@if($accounts->isNotEmpty())
    <tr class="bg-slate-50">
        <td colspan="3" class="font-semibold text-slate-600 uppercase text-xs tracking-wide">{{ $title }}</td>
    </tr>
    @foreach($accounts as $account)
        <tr>
            <td class="font-mono w-24">{{ $account->code }}</td>
            <td class="text-slate-700">{{ $account->name }}</td>
            <td class="text-right">C$ {{ number_format($account->amount, 2) }}</td>
        </tr>
    @endforeach
    <tr class="font-semibold">
        <td colspan="2" class="text-right">Total {{ $title }}</td>
        <td class="text-right">C$ {{ number_format($total, 2) }}</td>
    </tr>
@endif
