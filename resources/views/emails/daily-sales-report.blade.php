<h1>Daily sales report</h1>
<p>
    Report date: {{ $reportDate->toDateString() }}
</p>

@if (count($summary) === 0)
    <p>No products were sold today.</p>
@else
    <table cellpadding="6" cellspacing="0" border="1">
        <thead>
            <tr>
                <th align="left">Product</th>
                <th align="right">Quantity</th>
                <th align="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td align="right">{{ $row['quantity'] }}</td>
                    <td align="right">{{ number_format((float) $row['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
